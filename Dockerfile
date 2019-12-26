FROM ubuntu:bionic as source
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y && \
    rm -rf /var/lib/apt/lists/*

COPY . selfoss/

FROM composer:1.9 as composer

COPY --from=source /selfoss /selfoss

RUN cd /selfoss && composer install --ignore-platform-reqs --optimize-autoloader --no-dev



FROM node:13-buster as npm

COPY --from=composer /selfoss /selfoss

RUN cd /selfoss && npm install && npm install --prefix assets/ && npm run build



FROM php:7.4-apache-buster

COPY --from=npm /selfoss /var/www/html/

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y libpng-dev \
    cron \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/

RUN mkdir config && ln -s config/config.ini config.ini \
    && a2enmod rewrite

VOLUME /var/www/html/data
VOLUME /var/www/html/config/
RUN ls utils/docker

ENTRYPOINT [ "bash" ]
CMD [ "utils/docker/entrypoint.sh" ]