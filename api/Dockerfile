# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

FROM php:8.4-fpm-alpine
RUN docker-php-ext-install pcntl pdo_mysql
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN rm -f bootstrap/cache/*.php && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
CMD ["sh", "-c", "chown -R www-data:www-data storage bootstrap/cache && php artisan migrate --force && php-fpm"]
