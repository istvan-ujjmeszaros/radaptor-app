FROM php:8.4-fpm

ARG PHP_VERSION=8.4
ARG ENVIRONMENT=prod

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    mc \
    htop \
    libzip-dev \
    gettext \
    libtidy-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libbrotli-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gettext pdo_mysql zip tidy opcache gd calendar \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install brotli && docker-php-ext-enable brotli

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set memory limit to -1
RUN echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini"

RUN echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY ./radaptor /usr/local/bin/radaptor
RUN chmod +x /usr/local/bin/radaptor

ENV RADAPTOR_APP_ROOT=/app
WORKDIR /app
