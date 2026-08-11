# Backup & Restore Database SIPKUD

Database PostgreSQL SIPKUD menyimpan data keuangan riil banyak desa — backup
otomatis **wajib** berjalan sebelum sistem dipakai produksi. Dokumen ini
menjelaskan cara memasang backup harian dan prosedur restore yang teruji.

---

## 0. Backup & Restore dari Panel Super Admin (cara termudah)

Super Admin dapat mengelola backup langsung dari aplikasi:
**menu Backup Database** (`/backup`), dengan kemampuan:

- **Buat Backup Sekarang** — dump penuh database (pgsql `.dump` / mysql `.sql.gz`)
- **Unduh / Unggah** file backup (mis. pindah antar server)
- **Restore** — mengganti seluruh isi database dari file backup, dengan:
  - konfirmasi eksplisit (wajib mengetik `RESTORE`),
  - *safety snapshot* otomatis sebelum restore (selalu bisa kembali),
  - mode maintenance selama proses,
  - `migrate --force` + **verifikasi integritas akuntansi otomatis** sesudahnya
- Semua aksi tercatat di audit log

Backup terjadwal juga berjalan otomatis setiap malam 01:30 via
`php artisan db:backup --keep=14` (kontainer scheduler) — file tersimpan di
`storage/app/backups` (volume `sipkud-storage`) dan muncul di panel.

> Tetap **unduh backup secara berkala ke luar server** — file di server yang
> sama tidak melindungi dari kegagalan VPS. Bagian di bawah ini menjelaskan
> alternatif backup dari sisi host/cron.

---

## 1. Backup otomatis harian

Skrip: [`scripts/backup-db.sh`](../scripts/backup-db.sh)

Yang dilakukan skrip:

1. `pg_dump` (format custom, terkompresi) dari container `postgres-db`
2. Verifikasi hasil dump (file tidak kosong + `pg_restore --list` berhasil)
3. Hapus backup yang lebih tua dari `RETENTION_DAYS` (default 14 hari)

### Pemasangan di VPS

```bash
# Uji jalan manual dulu
cd /var/www/sipkud
BACKUP_DIR=/var/backups/sipkud ./scripts/backup-db.sh

# Pasang cron harian jam 01:00 (di HOST, bukan di dalam container)
crontab -e
# Tambahkan:
0 1 * * * /var/www/sipkud/scripts/backup-db.sh >> /var/log/sipkud-backup.log 2>&1
```

### Konfigurasi (environment variable, semuanya opsional)

| Variabel | Default | Keterangan |
|---|---|---|
| `DB_CONTAINER` | `postgres-db` | Nama container PostgreSQL |
| `DB_DATABASE` | dari `.env` | Nama database |
| `DB_USERNAME` | dari `.env` | User database |
| `BACKUP_DIR` | `/var/backups/sipkud` | Folder tujuan backup |
| `RETENTION_DAYS` | `14` | Umur maksimal file backup |

### Simpan salinan di luar server (penting)

Backup di server yang sama tidak melindungi dari kegagalan disk/VPS.
Sinkronkan folder backup ke lokasi lain minimal harian, misalnya dengan
`rclone` ke object storage (S3/B2/GDrive):

```bash
# Contoh: sinkron ke remote rclone bernama "backup-remote"
30 1 * * * rclone sync /var/backups/sipkud backup-remote:sipkud-db >> /var/log/sipkud-backup.log 2>&1
```

---

## 2. Restore

> **Selalu uji prosedur ini di database kosong/staging secara berkala.**
> Backup yang tidak pernah diuji restore sama dengan tidak punya backup.

### 2.1 Restore penuh ke database baru

```bash
# 1. Salin file backup ke dalam container
docker cp /var/backups/sipkud/sipkud-SIPKUDDB-20260811-010000.dump postgres-db:/tmp/restore.dump

# 2. Buat database tujuan (kosong)
docker exec postgres-db createdb -U <DB_USERNAME> sipkud_restore

# 3. Restore
docker exec postgres-db pg_restore -U <DB_USERNAME> \
    --dbname=sipkud_restore --no-owner --clean --if-exists /tmp/restore.dump

# 4. Bersihkan
docker exec postgres-db rm /tmp/restore.dump
```

### 2.2 Mengganti database produksi (disaster recovery)

```bash
# 1. Hentikan aplikasi agar tidak ada tulisan baru
docker compose stop app queue scheduler

# 2. Restore ke database produksi (drop objek lama lalu buat ulang)
docker cp <file.dump> postgres-db:/tmp/restore.dump
docker exec postgres-db pg_restore -U <DB_USERNAME> \
    --dbname=<DB_DATABASE> --no-owner --clean --if-exists /tmp/restore.dump
docker exec postgres-db rm /tmp/restore.dump

# 3. Jalankan aplikasi kembali
docker compose start app queue scheduler

# 4. Verifikasi integritas akuntansi setelah restore
docker compose exec app php artisan accounting:verify-integrity
```

### 2.3 Uji restore berkala (disarankan bulanan)

```bash
docker exec postgres-db createdb -U <DB_USERNAME> sipkud_verify
docker cp <file backup terbaru> postgres-db:/tmp/verify.dump
docker exec postgres-db pg_restore -U <DB_USERNAME> --dbname=sipkud_verify --no-owner /tmp/verify.dump
docker exec postgres-db psql -U <DB_USERNAME> -d sipkud_verify -c "SELECT COUNT(*) FROM jurnal;"
docker exec postgres-db dropdb -U <DB_USERNAME> sipkud_verify
docker exec postgres-db rm /tmp/verify.dump
```

---

## 3. Apa saja yang perlu dibackup

| Data | Lokasi | Mekanisme |
|---|---|---|
| Database PostgreSQL | container `postgres-db` | `scripts/backup-db.sh` (dokumen ini) |
| File upload (logo, favicon) | volume `sipkud-storage` (`storage/app/public`) | sertakan dalam `rclone sync` / tar berkala |
| File `.env` | `/var/www/sipkud/.env` | salin manual ke penyimpanan kredensial yang aman (berisi `APP_KEY` — tanpa ini data terenkripsi tidak bisa dibaca) |

> **`APP_KEY` di `.env` wajib ikut diamankan.** Kehilangan `APP_KEY` berarti
> session/2FA secret terenkripsi tidak dapat didekripsi lagi setelah restore.
