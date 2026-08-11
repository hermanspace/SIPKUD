# SIPKUD

**Sistem Informasi Pelaporan Keuangan USP Desa** — aplikasi pelaporan keuangan
Unit Simpan Pinjam / BUM Desa dengan cakupan kabupaten, berbasis akuntansi
double entry.

## Teknologi

- Laravel 12 (PHP 8.2+), Livewire + Volt, Flux UI Pro
- PostgreSQL (produksi & dev), Redis (cache/session/queue produksi)
- Docker (PHP 8.3 + Apache) di belakang Nginx Proxy Manager

## Arsitektur singkat

- **Multi-tenant per desa** dengan hierarki peran: Super Admin (PMD Kabupaten)
  → Admin Kecamatan → Admin Desa → Executive View. Isolasi data lewat global
  scope `HasDesaScope`, gate role, dan `TenantMiddleware`.
- **Dua titik input transaksi**: Kas Harian dan Buku Memorial, plus modul
  Pinjaman → Angsuran. Semua transaksi melewati `AccountingService`
  (validasi debit = kredit, posting ke ledger `neraca_saldo`, penguncian
  periode).
- **Laporan read-only** yang diturunkan dari jurnal: LPP UED, Buku Kas,
  Laporan Akhir USP, Neraca Saldo, Laba Rugi, Neraca — ekspor Excel/PDF.

## Pengembangan lokal

```bash
composer run setup   # install, .env, key, migrate, npm build
composer run dev     # server + queue + log + vite
composer run test    # jalankan test (Pest)
```

Paket `livewire/flux-pro` privat — perlu kredensial `composer.fluxui.dev`
(lihat `.env.example`, variabel `COMPOSER_AUTH`).

## Perintah operasional penting

```bash
php artisan accounting:verify-integrity   # verifikasi integritas double entry
./scripts/backup-db.sh                    # backup PostgreSQL (lihat docs/BACKUP_RESTORE.md)
```

## Dokumentasi

Seluruh dokumen desain, analisis, dan panduan ada di folder [`docs/`](docs/):

- [Deployment produksi (Docker + NPM)](docs/DEPLOYMENT.md)
- [Backup & restore database](docs/BACKUP_RESTORE.md)
- [Dokumentasi sistem akuntansi](docs/ACCOUNTING_SYSTEM_DOCUMENTATION.md)
- [Alur visual akuntansi](docs/ACCOUNTING_VISUAL_FLOW.md)
- [Periode akuntansi (tutup buku)](docs/PERIODE_AKUNTANSI_DOCUMENTATION.md)
