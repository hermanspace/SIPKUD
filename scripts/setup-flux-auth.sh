#!/usr/bin/env bash
#
# SIPKUD - Setup kredensial Flux Pro (composer.fluxui.dev)
#
# Paket livewire/flux-pro adalah paket Composer privat yang butuh autentikasi
# (email akun Flux + license key). Skrip ini:
#   1. Membuat .env dari .env.example bila belum ada
#   2. Jika COMPOSER_AUTH belum terisi, menampilkan prompt untuk memasukkan
#      email + license key, lalu menyimpannya ke .env
#   3. Memverifikasi kredensial ke composer.fluxui.dev (best-effort)
#
# Dipanggil otomatis oleh: make setup, make rebuild, make prod-build.
# Bisa juga dijalankan manual: ./scripts/setup-flux-auth.sh

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$PROJECT_DIR/.env"

# 1. Pastikan .env ada
if [[ ! -f "$ENV_FILE" ]]; then
    cp "$PROJECT_DIR/.env.example" "$ENV_FILE"
    echo ".env dibuat dari .env.example"
fi

# 2. Sudah terisi? Selesai.
if grep -qE '^COMPOSER_AUTH=.*fluxui' "$ENV_FILE"; then
    echo "Kredensial Flux Pro sudah ada di .env (COMPOSER_AUTH)."
    exit 0
fi

echo ""
echo "=============================================================="
echo "  Kredensial Flux Pro dibutuhkan (paket livewire/flux-pro)"
echo "=============================================================="
echo "Masukkan kredensial akun Flux Anda dari https://fluxui.dev"
echo "(email akun + license key)."
echo ""

read -rp "Email akun Flux : " FLUX_EMAIL
read -rsp "License key      : " FLUX_KEY
echo ""

if [[ -z "${FLUX_EMAIL}" || -z "${FLUX_KEY}" ]]; then
    echo "ERROR: email/license key kosong. Instalasi butuh kredensial Flux Pro." >&2
    echo "Jalankan ulang: ./scripts/setup-flux-auth.sh" >&2
    exit 1
fi

# 3. Verifikasi ke composer.fluxui.dev (best-effort, tidak menggagalkan)
if command -v curl >/dev/null 2>&1; then
    if curl -fsS -u "${FLUX_EMAIL}:${FLUX_KEY}" \
        https://composer.fluxui.dev/packages.json -o /dev/null 2>/dev/null; then
        echo "Kredensial terverifikasi ke composer.fluxui.dev."
    else
        echo "PERINGATAN: kredensial tidak dapat diverifikasi (jaringan atau" >&2
        echo "kredensial salah). Tetap disimpan - build akan gagal bila salah." >&2
    fi
fi

# 4. Simpan sebagai COMPOSER_AUTH di .env (hapus baris lama bila ada)
COMPOSER_AUTH_JSON=$(printf '{"http-basic":{"composer.fluxui.dev":{"username":"%s","password":"%s"}}}' \
    "$FLUX_EMAIL" "$FLUX_KEY")

TMP_FILE=$(mktemp)
grep -vE '^(# )?COMPOSER_AUTH=' "$ENV_FILE" > "$TMP_FILE" || true
printf "COMPOSER_AUTH='%s'\n" "$COMPOSER_AUTH_JSON" >> "$TMP_FILE"
mv "$TMP_FILE" "$ENV_FILE"

echo "COMPOSER_AUTH tersimpan di .env."
