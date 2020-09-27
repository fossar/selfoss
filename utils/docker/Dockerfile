FROM composer:1.9 as composer

ADD . /selfoss

RUN cd /selfoss && git reset HEAD && git checkout . && git clean -xdf && rm -fr .git \
    && composer install --ignore-platform-reqs --optimize-autoloader --no-dev



FROM node:12-stretch as npm

COPY --from=composer /selfoss /selfoss

RUN cd /selfoss && npm i && npm install --prefix assets/ && npm run build



FROM php:7.4-apache-buster

COPY --from=npm /selfoss /var/www/html/

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y libpng-dev \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/

RUN mkdir config && ln -s config/config.ini config.ini \
    && a2enmod rewrite

CMD [ "/var/www/html/utils/docker/entrypoint.sh" ]
