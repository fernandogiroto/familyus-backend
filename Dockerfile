FROM php:8.2-cli-alpine

RUN apk add --no-cache curl sqlite sqlite-dev libpng-dev oniguruma-dev libxml2-dev zip unzip git \
    && docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd \
    && docker-php-ext-enable pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN cp .env.example .env \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD sh -c "php artisan key:generate --force && \
           php artisan migrate --force && \
           php artisan storage:link || true && \
           php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
