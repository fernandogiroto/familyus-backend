#!/bin/sh
set -e

# Use Railway's dynamic PORT (default 8080)
PORT=${PORT:-8080}

# Replace port in nginx config
sed -i "s/__PORT__/$PORT/" /etc/nginx/nginx.conf

# Setup storage
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Setup SQLite database
mkdir -p /var/www/html/database
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi
chmod 664 /var/www/html/database/database.sqlite
chown www-data:www-data /var/www/html/database/database.sqlite

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
