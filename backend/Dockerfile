FROM composer:2.8 AS composer

FROM php:8.4-cli-bookworm AS development
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev unzip && docker-php-ext-install mysqli curl mbstring && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /workspace/backend
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts

COPY --chown=www-data:www-data public ./public
COPY --chown=www-data:www-data src ./src
COPY --chown=www-data:www-data scripts ./scripts
COPY --chown=www-data:www-data tests ./tests
COPY --chown=www-data:www-data database ./database

USER www-data
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
