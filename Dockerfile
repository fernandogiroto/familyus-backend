FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx supervisor curl sqlite sqlite-dev libpng-dev oniguruma-dev libxml2-dev zip unzip git bash \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql mbstring exif pcntl bcmath gd \
    && docker-php-ext-enable pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN cp .env.example .env \
    && php artisan storage:link || true

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["/start.sh"]
