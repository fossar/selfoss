FROM ubuntu:bionic as source

# Set frontend mode as noninteractive (default answers to all questions)
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update &&\
    apt-get upgrade -y && \
    apt-get autoremove && \
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

RUN apt-get update && apt-get install --no-install-recommends -y libpng-dev \
    cron \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/

RUN mkdir config && ln -s config/config.ini config.ini \
    && a2enmod rewrite

VOLUME /var/www/html/data
VOLUME /var/www/html/config/

CMD [ "/var/www/html/selfoss/utils/docker/entrypoint.sh" ]
