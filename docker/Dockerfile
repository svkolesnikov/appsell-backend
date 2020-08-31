FROM php:7.4.7-fpm-alpine

# Добавим в контейнер supervisor и nginx

RUN apk add --no-cache nginx
RUN apk add --no-cache supervisor

# Это относится к PHP

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
    libzip

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

WORKDIR /var/www/app
ENV COMPOSER_ALLOW_SUPERUSER 1

COPY ./composer.* /var/www/app/
COPY ./symfony.lock /var/www/app/symfony.lock

# Установка вендоров на основании composer.lock
RUN curl -sS https://getcomposer.org/installer | php \
 && chmod +x composer.phar \
 && php composer.phar install --no-interaction --no-scripts --no-autoloader

# Копирование кода приложения
COPY ./.bowerrc      /var/www/app/
COPY ./bower.json    /var/www/app/
COPY ./public        /var/www/app/public
COPY ./bin           /var/www/app/bin
COPY ./config        /var/www/app/config
COPY ./src           /var/www/app/src
COPY ./templates     /var/www/app/templates
COPY ./translations  /var/www/app/translations
RUN cd /var/www/app \
 && mkdir var \
 && chmod 0755 var \
 && chown www-data:www-data var

# Итоговое построение карты autoload
RUN php composer.phar dump-autoload --optimize --classmap-authoritative

RUN npm install -g bower
RUN bower install --allow-root

COPY ./docker/php-fpm.conf      /usr/local/etc/php-fpm.conf
COPY ./docker/www.conf          /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/supervisord.conf  /etc/supervisord.conf
COPY ./docker/nginx.conf        /etc/nginx/nginx.conf
COPY ./docker/nginx.vhost.conf  /etc/nginx/conf.d/default.conf

# Скрипт старта
#COPY ./docker/docker-entry-point.sh  /usr/local/bin/docker-entry-point
#RUN chmod +x /usr/local/bin/docker-entry-point
#ENTRYPOINT ["docker-entry-point"]

EXPOSE 80

CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]