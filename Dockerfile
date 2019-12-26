FROM ubuntu:bionic as source

RUN apt-get update &&\
    DEBIAN_FRONTEND=noninteractive && \
    apt-get upgrade -y && \
    apt-get autoremove
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
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/

RUN mkdir config && ln -s config/config.ini config.ini \
    && a2enmod rewrite \
    && /bin/echo -e "#!/bin/bash\nchown -R www-data:www-data /var/www/html/data/cache /var/www/html/data/favicons /var/www/html/data/logs /var/www/html/data/thumbnails /var/www/html/data/sqlite" > /entrypoint.sh \
    && /bin/echo -e "if [ ! -f config/config.ini ]; then cp defaults.ini config/config.ini && sed -i 's/logger_destination=.*$/logger_destination=file:php:\/\/stderr/' config/config.ini; fi" >> /entrypoint.sh \
    && /bin/echo -e "su www-data -s /bin/bash -c 'php /var/www/html/cliupdate.php' >/dev/null 2>&1" >> /entrypoint.sh \
    && /bin/echo -e "(while true; do su www-data -s /bin/bash -c 'php /var/www/html/cliupdate.php'; sleep 900; done;) &" >> /entrypoint.sh \
    && /bin/echo -e "apache2-foreground" >> /entrypoint.sh \
    && chmod a+x /entrypoint.sh

CMD [ "/entrypoint.sh" ]
