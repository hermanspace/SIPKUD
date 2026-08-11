# SIPKUD - Menjalankan dengan Docker (Dev & Production)

Seluruh dependensi sistem sudah dikemas dalam container. Yang perlu ada di
host hanya **Docker (dengan Compose v2)** dan **make** — tidak perlu install
PHP, Composer, Node, PostgreSQL, maupun Redis di mesin host.

---

## 1. Inventaris dependensi

### Di dalam image aplikasi (`Dockerfile`, multi-stage)

| Dependensi | Versi | Keterangan |
|---|---|---|
| PHP | 8.3 (Apache) | Runtime aplikasi |
| Ekstensi PHP | pdo_pgsql, gd, zip, intl, mbstring, bcmath, xml, dom, opcache, redis (pecl) | Semua yang dibutuhkan Laravel + akuntansi (bcmath) + export (gd/zip untuk PhpSpreadsheet & DomPDF) |
| Apache | mod_rewrite, headers | Web server, document root `public/` |
| Composer | 2 | Tersedia di runtime untuk perintah dalam container |
| Node.js + npm | 20 (build stage) | Build aset Vite/Tailwind saat build image |

### Container pendamping

| Service | Image | Dev | Production |
|---|---|---|---|
| `app` | sipkud-app (build lokal) | ✅ port 8000 | ✅ di belakang NPM |
| `queue` | sipkud-app | ✅ | ✅ `queue:work redis` |
| `scheduler` | sipkud-app | ✅ | ✅ `schedule:work` (verifikasi integritas harian) |
| PostgreSQL | postgres:16-alpine | ✅ `postgres` | ✅ infra bersama **atau** profil `bundled` |
| Redis | redis:7-alpine | ✅ `redis` | ✅ infra bersama **atau** profil `bundled` |
| Mailpit | axllent/mailpit | ✅ UI :8025 (tangkap email dev) | ❌ (pakai SMTP asli) |
| Node (on-demand) | node:20-alpine | ✅ `make npm-*` | ❌ (aset di-build dalam image) |
| Vite HMR | node:20-alpine | opsional `make npm-dev` (:5173) | ❌ |
| Nginx Proxy Manager | — | ❌ | ✅ reverse proxy + SSL (infra server) |

### Kredensial yang dibutuhkan saat build

Paket `livewire/flux-pro` privat — butuh email akun Flux + license key.
**Tidak perlu diisi manual**: saat `make setup` / `make rebuild` /
`make prod-build`, skrip `scripts/setup-flux-auth.sh` otomatis menampilkan
prompt untuk memasukkan kredensial bila belum ada, memverifikasinya ke
`composer.fluxui.dev`, lalu menyimpannya sebagai `COMPOSER_AUTH` di `.env`.

Bisa juga dijalankan manual (mis. untuk mengganti kredensial — hapus dulu
baris `COMPOSER_AUTH` di `.env`):

```bash
./scripts/setup-flux-auth.sh
```

---

## 2. Development

```bash
make setup   # prompt kredensial Flux Pro -> build image, up, composer install, migrate, build aset
```

Selesai — buka:

- Aplikasi: http://localhost:8000
- Mailpit (email dev): http://localhost:8025
- PostgreSQL dari host: `localhost:54320` (user/db/pass default: `sipkud`;
  port host sengaja 54320 agar tidak bentrok dengan PostgreSQL lokal —
  override lewat `.env`: `DB_HOST_PORT=...`)

Perintah harian:

```bash
make up / make down         # nyalakan / matikan stack
make logs                   # ikuti log
make shell                  # masuk container app
make test                   # jalankan Pest
make pint                   # perbaiki code style
make migrate / make fresh   # migrasi / reset DB dev
make npm-dev                # Vite HMR di :5173
make artisan CMD="..."      # artisan bebas
make help                   # daftar lengkap
```

Kode di-bind-mount, jadi perubahan PHP langsung aktif tanpa rebuild.
Rebuild image hanya perlu saat mengubah Dockerfile/dependensi sistem:
`make rebuild`.

---

## 3. Production

Dua mode — pilih sesuai kondisi server:

### Mode A: infra bersama (server sudah punya postgres-db & redis-cache)

```bash
make prod-init              # buat network frontend/backend (sekali)
make prod-build
make prod-up
```

### Mode B: mandiri / bundled (server kosong)

PostgreSQL 16 dan Redis 7 ikut dinyalakan dari compose yang sama
(container `postgres-db` & `redis-cache`, data di volume
`sipkud-pgdata` / `sipkud-redisdata`):

```bash
make prod-init
make prod-up-bundled
```

Kedua mode memakai `.env` yang sama (`DB_HOST=postgres-db`,
`REDIS_HOST=redis-cache`) — lihat [DEPLOYMENT.md](DEPLOYMENT.md) untuk
konfigurasi lengkap + Nginx Proxy Manager.

Operasional:

```bash
make prod-deploy            # git pull + build + up + migrate + cache
make prod-logs / prod-ps
make prod-backup            # backup DB (lihat BACKUP_RESTORE.md)
make prod-verify            # verifikasi integritas akuntansi
```

> Setelah deploy pertama: jalankan `docker compose exec app php artisan
> key:generate` bila `APP_KEY` belum terisi, dan pasang cron backup harian
> (lihat [BACKUP_RESTORE.md](BACKUP_RESTORE.md)).
