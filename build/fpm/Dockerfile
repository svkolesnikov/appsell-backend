FROM php:7.2-fpm-alpine

RUN apk add --no-cache \
    libpq \
    git \
    nodejs \
    nodejs-npm \
    libevent \
    'su-exec>=0.2'

RUN apk add --no-cache --virtual .build-deps \
    libxml2-dev \
    postgresql-dev \
    autoconf \
    gcc \
    musl-dev \
    make \
    pcre-dev \
    libevent-dev \
 && docker-php-ext-install \
    mbstring \
    xml \
    pdo \
    pgsql \
    pdo_pgsql \
    zip \
    opcache \
    sockets \
 && pecl install event && docker-php-ext-enable event \
 && mv /usr/local/etc/php/conf.d/docker-php-ext-event.ini \
       /usr/local/etc/php/conf.d/docker-php-ext-zz-event.ini \
 && apk del .build-deps

WORKDIR /var/www/app
ENV COMPOSER_ALLOW_SUPERUSER 1

COPY ./composer.*   /var/www/app/
COPY ./symfony.lock /var/www/app/symfony.lock
COPY ./public       /var/www/app/public

# Установка вендоров на основании composer.lock
RUN curl -sS https://getcomposer.org/installer | php \
 && chmod +x composer.phar \
 && php composer.phar install --no-interaction --no-scripts --no-autoloader

# Копирование кода приложения
COPY ./.bowerrc      /var/www/app/
COPY ./bower.json    /var/www/app/
COPY ./bin           /var/www/app/bin
COPY ./config        /var/www/app/config
COPY ./src           /var/www/app/src
COPY ./templates     /var/www/app/templates
COPY ./translations  /var/www/app/translations

# Итоговое построение карты autoload
RUN php composer.phar dump-autoload --optimize --classmap-authoritative

RUN npm install -g bower
RUN bower install --allow-root

VOLUME /var/www/app
VOLUME /var/www/app/var

EXPOSE 9000

COPY ./build/fpm/php-fpm.conf           /usr/local/etc/php-fpm.conf
COPY ./build/fpm/www.conf               /usr/local/etc/php-fpm.d/www.conf
COPY ./build/fpm/docker-entry-point.sh  /usr/local/bin/docker-entry-point

RUN chmod +x /usr/local/bin/docker-entry-point

ENTRYPOINT ["docker-entry-point"]

CMD ["php-fpm"]