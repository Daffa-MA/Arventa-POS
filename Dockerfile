FROM node:22-alpine AS assets

WORKDIR /app

COPY arventa-pos-backend/package*.json ./
RUN npm ci

ARG CAPROVER_GIT_COMMIT_SHA=local
ENV ARVENTA_BUILD_SHA=${CAPROVER_GIT_COMMIT_SHA}
RUN echo "$CAPROVER_GIT_COMMIT_SHA" > /tmp/caprover-git-sha

COPY arventa-pos-backend/resources ./resources
COPY arventa-pos-backend/vite.config.js ./vite.config.js
COPY arventa-pos-backend/public ./public
RUN npm run build

FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod rewrite headers \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ARG CAPROVER_GIT_COMMIT_SHA=local
RUN echo "$CAPROVER_GIT_COMMIT_SHA" > /tmp/caprover-git-sha

COPY arventa-pos-backend/ ./
COPY --from=assets /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/arventa-entrypoint

RUN test -f public/build/manifest.json \
    && composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && rm -rf public/storage \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache public/build public/storage \
    && chmod -R ug+rwX storage bootstrap/cache \
    && find bootstrap/cache -type f ! -name packages.php ! -name services.php -delete \
    && chmod +x /usr/local/bin/arventa-entrypoint

EXPOSE 80

ENTRYPOINT ["arventa-entrypoint"]
CMD ["apache2-foreground"]
