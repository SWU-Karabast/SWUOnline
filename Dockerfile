FROM php:8.2.1-apache as base
RUN apt-get update && apt-get install -y --no-install-recommends libbz2-dev \
    libc-client-dev \
    libkrb5-dev \
    libxslt-dev \
    libzip-dev && \
    rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

RUN pecl install -o -f redis \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable redis

RUN docker-php-ext-install zip mysqli pdo pdo_mysql shmop bz2

# use sed to change individual php.ini settings here
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# this layer will only build if we add --target=build to the docker build command
FROM base as dev
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# this layer builds by default and just has the base packages
FROM base as prod