FROM php:7.3-fpm-alpine

MAINTAINER "Fajrul <fajrulaz@gmail.com>"

RUN apk update
RUN apk add autoconf automake make gcc g++ icu-dev nano curl

# INSTALL APCU CACHE
RUN pecl install apcu && docker-php-ext-enable apcu

# INSTALL REDIS CLIENT
RUN pecl install redis && docker-php-ext-enable redis

# INSTALL intl and mysql pdo connection
RUN docker-php-ext-install intl pdo_mysql

# INSTALL COMPOSER
RUN curl -sS "https://getcomposer.org/installer" | php
RUN mv composer.phar /usr/local/bin/composer

# CLEAR ALL CACHE APK
RUN rm -rf /var/cache/apk && mkdir -p /var/cache/apk