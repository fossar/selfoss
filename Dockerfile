# syntax=docker/dockerfile:1.9@sha256:5510f694edfe648d961b59dcf217026485e560d2663c73e45067b8c8d7a6d247

### Stage 1: build client
FROM node:20 AS client-builder
WORKDIR /client-builder

# Install node packages
RUN --mount=type=bind,source=package.json,target=package.json \
  --mount=type=bind,source=client/package.json,target=client/package.json \
  --mount=type=bind,source=client/package-lock.json,target=client/package-lock.json \
  --mount=type=cache,sharing=locked,id=npmcache,mode=0777,target=/root/.npm \
  npm run install-dependencies-ci:client

# Build client
COPY client/ client/
RUN --mount=type=bind,source=package.json,target=package.json \
  --mount=type=bind,source=client/package.json,target=client/package.json \
  --mount=type=bind,source=client/package-lock.json,target=client/package-lock.json \
  --mount=type=cache,sharing=locked,id=npmcache,mode=0777,target=/root/.npm \
  npm run build


### Stage 2: final container
FROM php:8.3-apache
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
  && docker-php-ext-install -j$(nproc) gd mbstring pdo_pgsql pdo_mysql tidy \
  && apt remove -y libjpeg62-turbo-dev libpng-dev libpq-dev libonig-dev libtidy-dev \
  && apt autoremove -y \
  && apt clean \
  && rm -rf /var/lib/apt/lists/*

# Install Apache modules
RUN a2enmod headers rewrite

# Install Selfoss PHP dependencies
RUN --mount=type=bind,source=composer.json,target=composer.json \
  --mount=type=bind,source=composer.lock,target=composer.lock \
  --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
  COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev

# Install Selfoss and copy frontend from the first stage
WORKDIR /var/www/html
COPY . .
COPY --from=client-builder /client-builder/public /var/www/html/public

# Use www-data user as owner and drop root user
RUN chown -R www-data:www-data /var/www/html/data
USER www-data

VOLUME /var/www/html/data
