#!/bin/bash

printf "\n\033[0;44mInstalling system packages for the \"${APP_ENV}\" environment\033[0m\n"

apt-get update
apt-get install -y --no-install-recommends zip unzip \
    vim \
    tmux \
    ranger \
    supervisor \
    htop \
    locales \
    zlib1g-dev \
    librabbitmq-dev \
    libssh-dev \

sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/g' /etc/locale.gen
ln -s /etc/locale.alias /usr/share/locale/locale.alias
locale-gen en_US.UTF-8

ln -snf /usr/share/zoneinfo/Europe/Moscow /etc/localtime
echo Europe/Moscow > /etc/timezone

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

docker-php-ext-install opcache \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    openssl \
    bcmath \
    sockets

pecl install amqp

docker-php-ext-enable amqp