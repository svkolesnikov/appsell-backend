FROM php:7.4.7-fpm-alpine

RUN apk update

RUN apk add --no-cache \
    libpq \
    libpng \
    libjpeg-turbo \
    freetype \
    git \
    nodejs \
    nodejs-npm \
    libevent \
    rabbitmq-c \
    libzip \
    curl \
    mc \
    zip

RUN apk add --no-cache --virtual .build-deps \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev \
    autoconf \
    gcc \
    musl-dev \
    make \
    pcre-dev \
    libevent-dev \
    rabbitmq-c-dev \
    libzip-dev \
    oniguruma-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    gd \
    exif \
    mbstring \
    xml \
    pdo \
    pgsql \
    pdo_pgsql \
    zip \
    opcache \
    sockets \
 && pecl install amqp-1.9.4 && docker-php-ext-enable amqp \
 && apk del .build-deps

RUN apk add --no-cache \
    bash

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --1 --filename=composer
RUN composer global require hirak/prestissimo

COPY ./.docker/php/php.ini /usr/local/etc/php/

WORKDIR /app

#COPY ./composer.json ./composer.json
#COPY ./composer.lock ./composer.lock

#RUN composer install --optimize-autoloader --no-scripts

#COPY ./ ./

COPY ./.docker/php/start-php.sh /start-php.sh

EXPOSE 8080