FROM debian:stretch-slim

MAINTAINER Fajrul Akbar Zuhdi<fajrulaz@gmail.com>

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends --no-install-suggests \
    apt-utils \
    lsb-release \
    gnupg \
    autoconf \
    apt-transport-https \
    ca-certificates \
    dpkg-dev \
    file \
    g++ \
    gcc \
    libc-dev \
    make \
    pkg-config \
    re2c \
    curl \
    nano \
    wget \
    zip \
    unzip \
    supervisor

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php stretch main" | tee /etc/apt/sources.list.d/php7.3.list

RUN apt-get update && apt-get install -y --no-install-recommends --no-install-suggests \
    nginx \
    mariadb-server \
    php7.3-common \
    php7.3-dev \
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
    php-pear \
    php-amqp

ENV COMPOSER_ALLOW_SUPERUSER 1

ADD . /www

# WORK
RUN pecl install apcu && \
    echo "extension=apcu.so" > /etc/php/7.3/mods-available/apcu.ini && \
    phpenmod apcu && \
    touch /www/.env && \
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');" && \
    php /tmp/composer-setup.php --install-dir=/usr/bin/ --filename=composer && \
    composer install --no-dev --working-dir=/www && \
    composer run bootstrap --working-dir=/www && \
    composer clear-cache && \
    apt-get autoremove -y && \
    rm -f /etc/nginx/sites-enabled/* && \
    rm -rf /var/lib/apt/lists/* && \
    rm -rf /tmp/* && \
    mkdir -p /var/log/supervisor && \
    mkdir -p /var/run/php && \
    cp /www/nginx.conf  /etc/nginx/conf.d/default.conf && \
    echo "clear_env = no" > /etc/php/7.3/fpm/pool.d/osenv.conf

WORKDIR /www

EXPOSE 19911 3306

ENV DB_HOST=localhost
ENV DB_NAME=viloveul
ENV DB_USERNAME=dev
ENV DB_PASSWD=viloveul

CMD ["sh", "/www/run.sh"]