FROM php:7.2-fpm

ARG APP_ENV

COPY ./install.sh /tmp
RUN bash /tmp/install.sh

COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf.default
COPY ./php.ini /usr/local/etc/php/conf.d
COPY ./supervisor/supervisord.conf /etc/supervisor
COPY ./supervisor/labelbot.conf /etc/supervisor/conf.d

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get autoremove --purge \
 && apt-get -y clean \
 && rm -rf /var/lib/apt/lists/*

RUN usermod -u 1000 www-data

#CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
