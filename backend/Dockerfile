# Production image for Render (free tier, Docker runtime).
# PHP 8.5 + Apache serving Laravel's public/ folder.
FROM php:8.5-apache

# System packages: unzip lets Composer extract packages.
# PHP extensions: pdo_mysql for MySQL. (Opcache is built into PHP 8.5, so it isn't installed here.)
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Apache: serve from public/, enable .htaccess rewrites (Laravel's front controller),
# and listen on the port Render gives us in $PORT (Apache expands ${PORT} at runtime).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV PORT=8080
RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's!Listen 80!Listen ${PORT}!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first (cached layer: only re-runs when composer files change).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Then copy the app and build the optimized autoloader.
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
