# syntax=docker/dockerfile:1

### Stage 1: build client
FROM node:20 as client-builder
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
# Install runtime & development package dependencies & php extensions
# then clean-up dev package dependencies
RUN export DEBIAN_FRONTEND=noninteractive \
    && apt update \
    && apt install -y --no-install-recommends \
      unzip \
      libjpeg62-turbo libpng16-16 libpq5 libonig5 libtidy5deb1 \
      libjpeg62-turbo-dev libpng-dev libpq-dev libonig-dev libtidy-dev \
    && update-ca-certificates --fresh \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd mbstring pdo_pgsql pdo_mysql tidy \
    && apt remove -y libjpeg62-turbo-dev libpng-dev libpq-dev libonig-dev libtidy-dev \
    && apt autoremove -y \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# Install Apache modules
RUN a2enmod headers rewrite

# Install Selfoss PHP dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json .
COPY composer.lock .
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev
RUN rm /usr/bin/composer

# Install Selfoss and copy frontend from the first stage
WORKDIR /var/www/html
COPY . .
COPY --from=client-builder /client-builder/public /var/www/html/public

# Use www-data user as owner and drop root user
RUN chown -R www-data:www-data /var/www/html/data
USER www-data

VOLUME /var/www/html/data
