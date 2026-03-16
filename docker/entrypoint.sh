#!/bin/bash
set -e

# Ensure storage directories exist (required for uploads: logo, Livewire temp)
mkdir -p /var/www/html/storage/app/public/pengaturan
mkdir -p /var/www/html/storage/app/private/livewire-tmp
mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions for www-data (Apache)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if not exists (for asset('storage/...'))
if [ ! -L /var/www/html/public/storage ]; then
    (cd /var/www/html && php artisan storage:link) 2>/dev/null || true
fi

# Run the main process (Apache for app, or CMD from docker-compose for queue/scheduler)
exec "$@"
