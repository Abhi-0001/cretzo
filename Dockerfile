# Matches production runtime (Hostinger runs PHP 8.0; composer.json pins the
# platform to 7.4 only so facebook/graph-sdk resolves — see application/config
# notes). Used for local repro (docker-compose) and the GitHub Actions smoke test.
FROM php:8.0-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libcurl4-openssl-dev \
        libonig-dev \
        default-mysql-client \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd curl mbstring zip bcmath exif \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Let .htaccess (mod_rewrite, cache headers) take effect for the whole app root
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# --ignore-platform-reqs: same reason as local XAMPP dev (see cretzo-local-setup memory) —
# facebook/graph-sdk 5.7 declares php ^5.4|^7.0, composer.json's config.platform pin to 7.4
# doesn't survive composer 2's stricter lock-vs-real-platform check on PHP 8.x runtimes.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-reqs \
    && mkdir -p application/logs application/cache uploads \
    && chown -R www-data:www-data application/logs application/cache uploads

EXPOSE 80
