FROM dunglas/frankenphp:1-php8.4 AS app

WORKDIR /app

RUN install-php-extensions pdo_pgsql intl opcache apcu zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APP_ENV=prod \
    SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1

COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/frankenphp/app.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

# Dépendances d'abord pour profiter du cache Docker
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

COPY . .

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && composer run-script --no-dev post-install-cmd \
    && php bin/console tailwind:build --minify \
    && php bin/console asset-map:compile \
    && mkdir -p public/uploads/images var \
    && rm -rf var/cache/*

EXPOSE 80

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
