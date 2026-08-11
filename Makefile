# =============================================================================
# SIPKUD - Makefile
#
# Development memakai docker-compose.dev.yml (stack lengkap, self-contained).
# Production memakai docker-compose.yml (infra bersama atau profil bundled).
#
#   make help          daftar semua perintah
#   make setup         setup pertama kali (dev)
#   make up / down     nyalakan / matikan stack dev
# =============================================================================

DC_DEV  := docker compose -f docker-compose.dev.yml
DC_PROD := docker compose -f docker-compose.yml

.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
# Umum
# -----------------------------------------------------------------------------
.PHONY: help
help: ## Tampilkan daftar perintah
	@echo "SIPKUD - perintah yang tersedia:"
	@echo ""
	@grep -hE '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# -----------------------------------------------------------------------------
# Development
# -----------------------------------------------------------------------------
.PHONY: setup
setup: ## Setup dev pertama kali: env, build, up, composer, key, migrate, assets
	@test -f .env || (cp .env.example .env && echo ".env dibuat dari .env.example")
	$(DC_DEV) build
	$(DC_DEV) up -d
	$(DC_DEV) exec app composer install
	$(DC_DEV) exec app php artisan key:generate --no-interaction
	$(DC_DEV) exec app php artisan migrate --no-interaction
	$(DC_DEV) exec app php artisan storage:link || true
	$(DC_DEV) run --rm node sh -c "npm install && npm run build"
	@echo ""
	@echo "Selesai. Aplikasi: http://localhost:8000 | Mailpit: http://localhost:8025"

.PHONY: up
up: ## Nyalakan stack dev (app, queue, scheduler, postgres, redis, mailpit)
	$(DC_DEV) up -d
	@echo "Aplikasi: http://localhost:8000 | Mailpit: http://localhost:8025"

.PHONY: down
down: ## Matikan stack dev
	$(DC_DEV) down

.PHONY: restart
restart: ## Restart stack dev
	$(DC_DEV) restart

.PHONY: rebuild
rebuild: ## Build ulang image dev lalu nyalakan
	$(DC_DEV) build
	$(DC_DEV) up -d

.PHONY: ps
ps: ## Status container dev
	$(DC_DEV) ps

.PHONY: logs
logs: ## Ikuti log seluruh container dev
	$(DC_DEV) logs -f

.PHONY: shell
shell: ## Masuk shell container app
	$(DC_DEV) exec app bash

.PHONY: tinker
tinker: ## Laravel Tinker
	$(DC_DEV) exec app php artisan tinker

.PHONY: artisan
artisan: ## Jalankan artisan, contoh: make artisan CMD="route:list"
	$(DC_DEV) exec app php artisan $(CMD)

.PHONY: composer
composer: ## Jalankan composer, contoh: make composer CMD="require foo/bar"
	$(DC_DEV) exec app composer $(CMD)

.PHONY: migrate
migrate: ## Jalankan migrasi database (dev)
	$(DC_DEV) exec app php artisan migrate

.PHONY: fresh
fresh: ## Reset database + seed ulang (HAPUS SEMUA DATA dev)
	$(DC_DEV) exec app php artisan migrate:fresh --seed

.PHONY: test
test: ## Jalankan test (Pest)
	$(DC_DEV) exec app php artisan config:clear
	$(DC_DEV) exec app ./vendor/bin/pest

.PHONY: pint
pint: ## Perbaiki code style (Laravel Pint)
	$(DC_DEV) exec app ./vendor/bin/pint

.PHONY: pint-test
pint-test: ## Cek code style tanpa mengubah file
	$(DC_DEV) exec app ./vendor/bin/pint --test

.PHONY: verify
verify: ## Verifikasi integritas akuntansi
	$(DC_DEV) exec app php artisan accounting:verify-integrity

.PHONY: npm-install
npm-install: ## Install dependensi Node
	$(DC_DEV) run --rm node npm install

.PHONY: npm-build
npm-build: ## Build aset frontend (Vite)
	$(DC_DEV) run --rm node npm run build

.PHONY: npm-dev
npm-dev: ## Vite dev server dengan HMR di :5173 (Ctrl+C untuk berhenti)
	$(DC_DEV) --profile vite up vite

.PHONY: clean
clean: ## Matikan stack dev dan HAPUS volume (database dev ikut terhapus)
	$(DC_DEV) down -v

# -----------------------------------------------------------------------------
# Production
# -----------------------------------------------------------------------------
.PHONY: prod-init
prod-init: ## Buat network frontend/backend (sekali saja per server)
	docker network create frontend 2>/dev/null || true
	docker network create backend 2>/dev/null || true
	@echo "Network frontend & backend siap."

.PHONY: prod-build
prod-build: ## Build image production
	$(DC_PROD) build

.PHONY: prod-up
prod-up: ## Nyalakan production (memakai infra bersama postgres-db/redis-cache)
	$(DC_PROD) up -d

.PHONY: prod-up-bundled
prod-up-bundled: ## Nyalakan production mandiri (PostgreSQL & Redis ikut dari compose)
	$(DC_PROD) --profile bundled up -d

.PHONY: prod-down
prod-down: ## Matikan production (data DB bundled tetap tersimpan di volume)
	$(DC_PROD) --profile bundled down

.PHONY: prod-deploy
prod-deploy: ## Deploy: pull kode, build, up, migrate, cache ulang
	git pull
	$(DC_PROD) build app
	$(DC_PROD) up -d
	$(DC_PROD) exec app php artisan migrate --force
	$(DC_PROD) exec app php artisan config:cache
	$(DC_PROD) exec app php artisan route:cache
	$(DC_PROD) exec app php artisan view:cache
	@echo "Deploy selesai."

.PHONY: prod-migrate
prod-migrate: ## Jalankan migrasi database (production)
	$(DC_PROD) exec app php artisan migrate --force

.PHONY: prod-logs
prod-logs: ## Ikuti log production
	$(DC_PROD) logs -f

.PHONY: prod-ps
prod-ps: ## Status container production
	$(DC_PROD) ps

.PHONY: prod-shell
prod-shell: ## Masuk shell container app production
	$(DC_PROD) exec app bash

.PHONY: prod-verify
prod-verify: ## Verifikasi integritas akuntansi (production)
	$(DC_PROD) exec app php artisan accounting:verify-integrity

.PHONY: prod-backup
prod-backup: ## Backup database PostgreSQL (lihat docs/BACKUP_RESTORE.md)
	./scripts/backup-db.sh
