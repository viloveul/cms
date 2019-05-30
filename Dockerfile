FROM debian:stretch-slim

MAINTAINER Fajrul Akbar Zuhdi<fajrulaz@gmail.com>

ENV DEBIAN_FRONTEND=noninteractive

ENV BASICDEP \
    apt-transport-https \
    ca-certificates \
    curl \
    nano \
    wget \
    zip \
    unzip \
    cron \
    supervisor

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests $BASICDEP && \
    rm -rf /var/lib/apt/lists/*

ADD . /viloveul

WORKDIR /viloveul

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests nginx && \
    rm -rf /var/lib/apt/lists/* && \
    rm -f /etc/nginx/sites-enabled/* && \
    cp /viloveul/config/nginx.conf /etc/nginx/conf.d/default.conf && \
    mkdir -p /var/log/supervisor && \
    touch /viloveul/.env

ENV COMPOSER_ALLOW_SUPERUSER 1

ENV SERVERDEP \
    php7.3-cli \
    php7.3-fpm \
    php7.3-zip \
    php7.3-xml \
    php7.3-mysql \
    php7.3-mbstring \
    php7.3-intl \
    php7.3-gd \
    php7.3-curl \
    php7.3-bcmath \
    php7.3-apcu \
    php7.3-redis \
    php7.3-amqp

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php stretch main" | tee /etc/apt/sources.list.d/php7.3.list && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests $SERVERDEP && \
    rm -rf /var/lib/apt/lists/*

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests nodejs && \
    rm -rf /var/lib/apt/lists/*

RUN php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');" && \
    php /tmp/composer-setup.php --install-dir=/usr/bin/ --filename=composer && \
    apt-get autoremove -y && \
    rm -rf /tmp/* && \
    mkdir -p /var/run/php

EXPOSE 19911

CMD ["sh", "/viloveul/sbin/dockerc"]