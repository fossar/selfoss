### Stage 1: build client
FROM node:18 as client-builder
WORKDIR /client-builder

# Install node packages
COPY package.json .
COPY client/package.json client/
COPY client/package-lock.json client/
RUN npm run install-dependencies:client

# Build client
COPY client/ client/
RUN npm run build


### Stage 2: final container
FROM php:8.2-apache
RUN apt-get update \
    && apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y cron unzip libjpeg62-turbo-dev libpng-dev libpq-dev libonig-dev libtidy-dev \
    && update-ca-certificates --fresh \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
    && docker-php-ext-install gd mbstring pdo_pgsql pdo_mysql tidy

RUN a2enmod headers rewrite

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

# Install dependencies
COPY composer.json .
COPY composer.lock .
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev

# Setup cron
RUN echo '* * * * * curl http://localhost/update' | tee /etc/cron.d/selfoss \
    && chmod 0644 /etc/cron.d/selfoss \
    && crontab /etc/cron.d/selfoss

WORKDIR /var/www/html

COPY . .

COPY --from=client-builder /client-builder/public /var/www/html/public

RUN chown -R www-data:www-data /var/www/html/data

# Overload default command to run cron in the background
RUN sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground

VOLUME /var/www/html/data
