FROM php:8.2-fpm

ARG PHP_VERSION=8.2
ARG ENVIRONMENT=prod

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    mc \
    htop \
    libzip-dev \
    gettext \
    libtidy-dev \
    && docker-php-ext-install gettext pdo_mysql zip tidy opcache \
    && pecl install redis-6.0.2 \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app
