#!/bin/bash
set -e

touch /var/www/html/database/database.sqlite
chmod 777 /var/www/html/database/database.sqlite

php artisan migrate --force

PORT=${PORT:-80}
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

apache2-foreground
