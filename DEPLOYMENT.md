# SIPKUD – Production Docker Deployment

This document describes how to run the SIPKUD Laravel application in production using Docker alongside existing **PostgreSQL**, **Redis**, and **Nginx Proxy Manager** on your VPS.

---

## Prerequisites on the VPS

- Docker and Docker Compose installed
- Existing containers and networks:
  - **PostgreSQL**: container `postgres-db`, port `5432`, network `backend`
  - **Redis**: container `redis-cache`, port `6379`, network `backend`
  - **Nginx Proxy Manager**: network `frontend`
- Networks `frontend` and `backend` already created

---

## 1. Clone / copy the project on the server

```bash
# Example: clone into /var/www/sipkud (or your chosen path)
cd /var/www
git clone <your-repo-url> sipkud
cd sipkud
```

---

## 2. Environment file

Create `.env` from the example and set production values:

```bash
cp .env.example .env
```

Edit `.env` and set at least:

```env
APP_NAME=SIPKUD
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sipkud2026.trust-idn.id

DB_CONNECTION=pgsql
DB_HOST=postgres-db
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

REDIS_CLIENT=phpredis
REDIS_HOST=redis-cache
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 3. (Optional) Build with private Composer repo (Flux Pro)

If the image is built on a machine that needs access to the Flux Pro private repo, set `COMPOSER_AUTH` when building:

```bash
export COMPOSER_AUTH='{"http-basic":{"composer.fluxui.dev":{"username":"YOUR_TOKEN","password":""}}}'
docker compose build --no-cache
```

Or uncomment and set the `ARG`/`ENV` in the Dockerfile and pass the build-arg:

```bash
docker compose build --build-arg COMPOSER_AUTH='{"http-basic":{...}}'
```

---

## 4. Build and start containers

```bash
docker compose build
docker compose up -d
```

Containers:

- **sipkud-app**: Laravel app (Apache, port 80 inside the container)
- **sipkud-queue**: Queue worker (`queue:work redis`)
- **sipkud-scheduler**: Cron replacement (`schedule:run` every minute)

All use networks `frontend` and `backend` (or only `backend` for queue/scheduler) and connect to `postgres-db` and `redis-cache`.

---

## 5. First-deploy commands (run once)

Run these inside the **app** container. Because the project is mounted, these affect the code on the host (e.g. `vendor/`, `.env`, DB).

```bash
# Shell into app container
docker compose exec app bash

# Then run (or run each line with docker compose exec app <cmd>):
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional: link storage for uploads
php artisan storage:link

exit
```

One-liner form (from host):

```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan storage:link
```

---

## 6. Nginx Proxy Manager (reverse proxy)

1. In Nginx Proxy Manager add a **Proxy Host**:
   - **Domain**: `sipkud2026.trust-idn.id`
   - **Scheme**: `http`
   - **Forward Hostname / IP**: `sipkud-app` (Docker service name; ensure NPM is on the same `frontend` network, or use the app container’s IP)
   - **Forward Port**: `80`
2. Enable SSL (e.g. Let’s Encrypt) and force HTTPS if desired.

If NPM is not on the same Docker network as the app, either:

- Attach the NPM container to the `frontend` network, or  
- Use the host IP and the **published** port of the app (e.g. `host:32800` if Compose mapped `32800:80`). Prefer using the same network and `sipkud-app:80`.

---

## 7. Verify

- **App**: https://sipkud2026.trust-idn.id  
- **Containers**: `docker compose ps` (all “Up”)  
- **App health**: `docker compose exec app curl -f http://localhost/` (or rely on the image healthcheck)

---

## 8. Useful commands

| Task | Command |
|------|--------|
| View logs | `docker compose logs -f app` |
| Queue logs | `docker compose logs -f queue` |
| Run artisan | `docker compose exec app php artisan <command>` |
| Composer | `docker compose exec app composer <command>` |
| Restart app | `docker compose restart app` |
| Rebuild after code change | `docker compose build app && docker compose up -d app` |

---

## 9. File permissions and logo upload

The image entrypoint fixes permissions on startup and creates `storage:link` if needed. If logo/favicon upload still fails:

```bash
# Fix permissions manually
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache

# Create storage symlink (required for asset('storage/...'))
docker compose exec app php artisan storage:link

# Ensure upload directories exist
docker compose exec app mkdir -p storage/app/public/pengaturan storage/app/private/livewire-tmp
docker compose exec app chown -R www-data:www-data storage
```

---

## 10. Volumes and data

- **App code**: Mounted from the host (`.:/var/www/html`), so code updates are reflected after reload/restart.
- **Storage**: Named volume `sipkud-storage` is mounted at `/var/www/html/storage` so logs, cache, sessions, and uploads persist across container recreations.

For a fully immutable deployment (no host mount), remove the `.:/var/www/html` volume from the `app`, `queue`, and `scheduler` services and rely on the image build; keep the `sipkud-storage` volume for `storage` only.

---

## Summary

1. Clone project and configure `.env` (DB, Redis, `APP_URL`, etc.).
2. Optionally set `COMPOSER_AUTH` for Flux Pro when building.
3. Run `docker compose build` and `docker compose up -d`.
4. Run first-deploy commands (composer, key:generate, migrate, caches, storage:link).
5. Configure Nginx Proxy Manager for `sipkud2026.trust-idn.id` → `sipkud-app:80`.
6. Check health and HTTPS.
