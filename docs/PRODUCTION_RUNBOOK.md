# Runbook Produksi SIPKUD

> Catatan resmi hasil migrasi produksi **11 Agustus 2026**: dari stack lama
> (nginx + PHP-FPM di host, MySQL di server terpisah) ke stack Docker penuh.
> Dokumen ini merekam arsitektur final, semua masalah yang ditemui beserta
> akar penyebab dan solusinya, serta prosedur operasional rutin.

---

## 1. Arsitektur Final

```
Internet
  │
  ▼
Cloudflare (proxy DNS "orange cloud", TLS publik, anti-DDoS)
  │
  ▼
Nginx Proxy Manager (host gateway; TLS internal + routing domain)
  │  forward: http://10.10.10.43:80
  ▼
Server aplikasi 10.10.10.43 (SIPKUD-apps)
  │
  ├── caddy            : jembatan host:80 → container (network: frontend)
  ├── sipkud-app       : Laravel + Apache (kode di-bake ke image)
  ├── sipkud-queue     : php artisan queue:work redis
  ├── sipkud-scheduler : php artisan schedule:work
  ├── postgres-db      : PostgreSQL 16 (profil "bundled", volume sipkud-pgdata)
  └── redis-cache      : Redis 7 (cache + session + queue)
```

- **Domain**: https://sipkud.trust-idn.id
- **Lokasi kode di server**: `/opt/sipkud` (branch `main`)
- **TLS**: berhenti di Cloudflare/NPM. Caddy di server ini **tidak** memegang
  sertifikat — hanya meneruskan HTTP polos.
- **Server MySQL lama (10.10.10.44)**: purna tugas setelah migrasi; lihat §6.

### Caddyfile (`/opt/caddy/Caddyfile`)

```
:80 {
    reverse_proxy sipkud-app:80
}
```

Dijalankan dengan:

```bash
docker run -d --name caddy --restart unless-stopped --network frontend \
  -p 80:80 \
  -v /opt/caddy/Caddyfile:/etc/caddy/Caddyfile \
  -v caddy-data:/data caddy:2
```

### Pengaturan `.env` produksi yang menentukan

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sipkud.trust-idn.id
DB_CONNECTION=pgsql
DB_HOST=postgres-db
DB_DATABASE=sipkud
DB_USERNAME=sipkud
DB_PASSWORD=<alfanumerik saja - lihat §3.4>
REDIS_HOST=redis-cache
CACHE_STORE=redis        # WAJIB redis, bukan database (lihat §3.6)
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
TRUSTED_PROXIES=*        # agar X-Forwarded-Proto dari NPM dipercaya (link https)
```

### Pengaturan Nginx Proxy Manager

- Proxy host `sipkud.trust-idn.id` → scheme **http**, forward **10.10.10.43:80**
- Sertifikat domain dikelola NPM (tidak ada perubahan saat migrasi)
- Websockets Support: aktif

---

## 2. Ringkasan Prosedur Migrasi yang Berhasil

Urutan yang terbukti jalan (jangan dibalik):

1. **Dump segar** dari MySQL lama — divalidasi isi, bukan sekadar ada file
   (lihat §3.1).
2. Matikan (disable, **belum** uninstall) nginx + php-fpm lama; arsipkan
   `/var/www/sipkud` ke tar.gz.
3. Install Docker (`curl -fsSL https://get.docker.com | sh`) + git + make.
4. Clone repo ke `/opt/sipkud`, isi `.env`, `APP_KEY` dari
   `echo "base64:$(openssl rand -base64 32)"`.
5. `make prod-init` → `make prod-build` (prompt kredensial Flux Pro) →
   `make prod-up-bundled`. **Database dibiarkan kosong** — jangan migrate dulu.
6. **Impor data**: MySQL temporer (`mysql:8.0` + native password) di network
   `backend` → muat dump → **pgloader** konversi ke PostgreSQL.
7. Baru `make prod-migrate` (migrasi baru di atas data lama) →
   `make prod-verify` ("Integritas akuntansi OK").
8. Pasang Caddy sebagai jembatan port 80 → arahkan NPM ke server ini.
9. Uji end-to-end: login, data 145 desa, laporan, buat backup pertama dari
   panel Super Admin.

Perintah pgloader yang dipakai:

```bash
docker run -d --name mysql-temp --network backend \
  -e MYSQL_ROOT_PASSWORD=temp123 -e MYSQL_DATABASE=laravel_db \
  mysql:8.0 --default-authentication-plugin=mysql_native_password

# tunggu login BENAR-BENAR diterima (bukan sekadar ping - lihat §3.2)
until docker exec mysql-temp mysql -uroot -ptemp123 -e "SELECT 1;" >/dev/null 2>&1; do sleep 3; done

docker exec -i mysql-temp mysql -uroot -ptemp123 laravel_db < dump.sql

cat > migrate.load <<'EOF'
LOAD DATABASE
  FROM mysql://root:temp123@mysql-temp/laravel_db
  INTO postgresql://sipkud:PASSWORD@postgres-db/sipkud
  WITH include drop, create tables, create indexes, reset sequences
  ALTER SCHEMA 'laravel_db' RENAME TO 'public';
EOF

docker run --rm --network backend -v $PWD/migrate.load:/migrate.load \
  dimitri/pgloader:latest pgloader /migrate.load

# bersih-bersih (migrate.load berisi password!)
docker rm -f mysql-temp && rm migrate.load
```

---

## 3. Masalah yang Ditemui dan Solusinya (WAJIB BACA sebelum migrasi ulang)

### 3.1 File backup otomatis lama ternyata KOSONG
`/var/backups/sipkud/*.sql.gz` berukuran 20 byte = gzip dari aliran kosong;
`gunzip -t` tetap bilang OK. **Pelajaran: keberadaan file backup bukan bukti
backup berhasil.** Validasi isi selalu:

```bash
gunzip -c file.sql.gz | tail -1              # harus "-- Dump completed on ..."
gunzip -c file.sql.gz | grep -cE "^CREATE TABLE"   # harus ±25, bukan 0
```

### 3.2 `mysqladmin ping` lolos sebelum MySQL selesai inisialisasi
Saat container MySQL pertama kali start, ada server sementara yang menjawab
ping **sebelum** password root terpasang — dump yang dimuat saat itu hilang
tanpa error yang jelas. Solusi: tunggu **login sungguhan**:

```bash
until docker exec mysql-temp mysql -uroot -pPASS -e "SELECT 1;" >/dev/null 2>&1; do sleep 3; done
```

### 3.3 pgloader tidak mendukung autentikasi MySQL 8.4
Error: `QMYND:MYSQL-UNSUPPORTED-AUTHENTICATION` (plugin `caching_sha2_password`).
Solusi: pakai image **`mysql:8.0`** dengan flag
`--default-authentication-plugin=mysql_native_password`. Catatan: di MySQL
8.4+ plugin lama sudah dinonaktifkan, `ALTER USER ... IDENTIFIED WITH
mysql_native_password` pun gagal — langsung pakai 8.0 saja untuk container
transit.

### 3.4 pgloader tidak men-decode `%XX` di URI + karakter khusus password
Password mengandung `!!` gagal terus: bentuk mentah bermasalah di URI, bentuk
ter-encode `%21%21` dikirim mentah-mentah oleh pgloader (tidak di-decode).
**Kebijakan: password database HANYA huruf+angka** (`openssl rand -hex 16`).

### 3.5 `POSTGRES_PASSWORD` hanya dibaca saat volume pertama dibuat
Mengubah `DB_PASSWORD` di `.env` **tidak** mengubah password di PostgreSQL
yang volumenya sudah ada. Sinkronkan manual (socket internal tidak butuh
password):

```bash
docker exec postgres-db psql -U sipkud -d sipkud \
  -c "ALTER USER sipkud WITH PASSWORD 'BARU';"
# lalu update .env dan:
docker compose --profile bundled up -d --force-recreate
```

### 3.6 Queue worker crash-loop saat database kosong
`queue:work` membaca cache saat boot; dengan `CACHE_STORE=database` + tabel
belum ada → exit berulang. Container `app` tidak kena karena di compose
di-override ke redis. **Solusi permanen: `CACHE_STORE=redis`,
`SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` di `.env`.**

### 3.7 `env_file` dibaca hanya saat container DIBUAT
Setiap perubahan `.env` wajib diikuti:

```bash
docker compose --profile bundled up -d --force-recreate
```

`docker restart` saja TIDAK cukup.

### 3.8 Migrasi `dropUnique` gagal di database hasil pgloader
Constraint hasil konversi pgloader membawa nama index gaya MySQL, bukan nama
konvensi Laravel (`jurnal_nomor_jurnal_unique`). Migrasi
`2026_08_11_000001_make_nomor_jurnal_unique_per_desa` sudah dibuat tahan
banting (cari index lama via katalog `pg_constraint`/`pg_indexes`, hapus apa
pun namanya). **Pola ini wajib ditiru untuk migrasi dropUnique/dropIndex
berikutnya** — jangan berasumsi nama index.

### 3.9 Healthcheck container worker selalu "unhealthy"
Healthcheck bawaan image (`curl /up`) mustahil lolos di container
queue/scheduler (tidak ada Apache). Sudah di-override di kedua compose:
cek proses PID 1 via `grep -q 'queue:work' /proc/1/cmdline`.

### 3.10 Redirect loop 308 + ACME 404: Caddy vs Cloudflare + NPM
Karena domain di-proxy Cloudflare dan TLS dipegang NPM:
- Let's Encrypt dari Caddy selalu gagal (challenge dijawab Cloudflare, 404);
- Caddyfile dengan nama domain membuat Caddy me-redirect HTTP→HTTPS,
  sementara NPM meneruskan HTTP → **redirect berputar**.

**Solusi: Caddyfile `:80` polos** (tanpa domain, tanpa TLS) — lihat §1.
Jangan pernah menaruh pemegang sertifikat kedua di belakang NPM.

### 3.11 Kredensial database lama sempat terekspos
Password MySQL lama (`laravel_user@10.10.10.44`) tertulis di percakapan /
dokumen kerja selama migrasi. Wajib dirotasi atau server MySQL dimatikan
(lihat §6). Kebijakan ke depan: kredensial tidak ditulis di chat/dokumen;
gunakan `read -s`.

---

## 4. Operasional Rutin

| Kegiatan | Perintah (di `/opt/sipkud`) |
|---|---|
| Deploy versi baru | `make prod-deploy` (pull → build → up → migrate → cache) |
| Status container | `docker compose ps` |
| Log aplikasi | `make prod-logs` |
| Verifikasi integritas akuntansi | `make prod-verify` |
| Masuk shell app | `make prod-shell` |
| Backup manual CLI | `make prod-backup` (atau dari panel Super Admin → Backup) |

Proses otomatis di dalam stack (scheduler):

- **01.30** backup database harian (retensi 14 salinan)
- **02.00** verifikasi integritas akuntansi
- **Tanggal 1, 01.00** penyusutan aset tetap bulanan

**Backup keluar server**: unduh berkas backup dari panel Super Admin secara
berkala ke media terpisah. Backup yang tinggal di server yang sama bukan
mitigasi bencana. Validasi isi file seperti §3.1.

### Rotasi password database (kapan pun diperlukan)

```bash
NEWPASS=$(openssl rand -hex 16)
docker exec postgres-db psql -U sipkud -d sipkud -c "ALTER USER sipkud WITH PASSWORD '$NEWPASS';"
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$NEWPASS|" .env
docker compose --profile bundled up -d --force-recreate
```

---

## 5. Pemulihan Bencana (ringkas)

Server hangus total? Yang dibutuhkan hanya: **berkas backup .dump terakhir** +
repo GitHub + kredensial Flux Pro.

1. Ulangi §2 langkah 3–5 di server baru (Docker, clone, `.env`, stack up).
2. Restore lewat panel Super Admin → Backup (unggah .dump → ketik `RESTORE`),
   atau CLI sesuai `docs/BACKUP_RESTORE.md`.
3. `make prod-verify`, arahkan NPM ke IP baru. Selesai.

---

## 6. Pekerjaan Susulan (status per 11 Agustus 2026)

- [ ] **Setelah stabil beberapa hari**: uninstall nginx + php8.3-fpm di
      SIPKUD-apps (`apt purge nginx nginx-common php8.3-*`), hapus
      `/var/www/sipkud` (arsip tar.gz di `/root` disimpan).
- [ ] **Server MySQL lama 10.10.10.44**: setelah data diverifikasi final,
      `systemctl stop mysql && systemctl disable mysql`; minimal rotasi
      password `laravel_user` (kredensial sempat terekspos, §3.11).
      Simpan dump terakhir sebagai arsip.
- [ ] Rotasi password PostgreSQL sekali lagi (nilai saat ini sempat tampil
      di percakapan kerja) — prosedur di §4.
- [ ] Tinjau peringatan Dependabot (https://github.com/hermanspace/SIPKUD/security/dependabot):
      `composer update` + `npm audit fix` + jalankan test suite.
- [ ] (Opsional) Cloudflare SSL mode *Full (strict)* + Origin Certificate di
      NPM untuk jalur CF→NPM yang lebih ketat.
