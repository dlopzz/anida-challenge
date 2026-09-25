FROM php:8.2-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist --no-progress \
    && cp -n .env.example .env \
    && php artisan key:generate --force \
    && touch database/database.sqlite \
    && php artisan migrate --force

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
