# Setup PostgreSQL untuk Development Lokal

## Prasyarat

1. **PostgreSQL** terpasang di mesin Anda (mis. via Homebrew, Postgres.app, atau Docker)
2. **PHP extension** `pdo_pgsql` aktif (Laravel Herd biasanya sudah include)

Cek extension:
```bash
php -m | grep pdo_pgsql
```

## Langkah 1: Buat Database dan User

Jalankan sebagai superuser PostgreSQL (biasanya `postgres`):

```bash
# Via psql
psql -U postgres -f database/setup-postgresql-local.sql

# Atau masuk ke psql dulu
psql -U postgres
\i database/setup-postgresql-local.sql
\q
```

Atau jalankan manual:
```sql
CREATE DATABASE "SIPKUDDB";
CREATE USER sipkuddbuser WITH PASSWORD 'sipkuddbpass';
GRANT ALL PRIVILEGES ON DATABASE "SIPKUDDB" TO sipkuddbuser;
\c SIPKUDDB
GRANT ALL ON SCHEMA public TO sipkuddbuser;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO sipkuddbuser;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO sipkuddbuser;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO sipkuddbuser;
```

## Langkah 2: Konfigurasi .env

Pastikan `.env` berisi:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=SIPKUDDB
DB_USERNAME=sipkuddbuser
DB_PASSWORD=sipkuddbpass
```

## Langkah 3: Jalankan Migrasi

```bash
php artisan migrate
```

## Jika Menggunakan Docker untuk PostgreSQL Lokal

```bash
docker run -d --name postgres-sipkud \
  -e POSTGRES_DB=SIPKUDDB \
  -e POSTGRES_USER=sipkuddbuser \
  -e POSTGRES_PASSWORD=sipkuddbpass \
  -p 5432:5432 \
  postgres:16-alpine
```

Lalu jalankan migrasi seperti di atas.
