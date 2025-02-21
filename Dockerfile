FROM php:8.2.1-apache as base

RUN apt-get update && apt-get install -y --no-install-recommends \
    libbz2-dev \
    libc-client-dev \
    libkrb5-dev \
    libxslt-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install zip mysqli pdo pdo_mysql shmop bz2

# Apply default PHP configuration
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Development stage (only builds if --target=dev is used)
FROM base as dev
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Production stage (builds by default)
FROM base as prod