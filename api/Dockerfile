FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:8.4-fpm-alpine
RUN docker-php-ext-install pcntl pdo pdo_mysql
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN rm -f bootstrap/cache/*.php && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
CMD ["sh", "-c", "chown -R www-data:www-data storage bootstrap/cache && php artisan migrate --force && php-fpm"]
