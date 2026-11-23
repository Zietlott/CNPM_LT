FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader


FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && printf '%s\n' 'RedirectMatch 302 "^/$" "/frontend/pages/auth/login.php"' \
        > /etc/apache2/conf-available/fitzone.conf \
    && a2enconf fitzone

COPY --from=vendor /app/vendor/ /var/www/html/vendor/

COPY backend/ /var/www/html/backend/
COPY frontend/ /var/www/html/frontend/