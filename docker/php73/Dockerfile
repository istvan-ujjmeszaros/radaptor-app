FROM php:7.3-fpm-alpine

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS

RUN apk add --no-cache gettext-dev tidyhtml-dev

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN pecl install redis-6.0.2

RUN docker-php-ext-install gettext pdo_mysql tidy opcache

RUN docker-php-ext-enable redis

RUN apk del .build-deps
