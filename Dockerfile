# Matches production runtime (Hostinger runs PHP 8.0; composer.json pins the
# platform to 7.4 only so facebook/graph-sdk resolves — see application/config
# notes). Used for local repro (docker-compose) and the GitHub Actions smoke test.
FROM php:8.0-apache

# deb.debian.org resets connections mid-download often enough to fail CI on a
# clean tree (seen as "read (104: Connection reset by peer)" on a random .deb),
# so give apt its own retries/timeouts and retry the whole update+install cycle.
RUN set -eux; \
    printf 'Acquire::Retries "5";\nAcquire::http::Timeout "30";\nAcquire::https::Timeout "30";\n' \
        > /etc/apt/apt.conf.d/99-network-resilience; \
    installed=0; \
    for attempt in 1 2 3 4 5; do \
        if apt-get update && apt-get install -y --no-install-recommends --fix-missing \
                libzip-dev \
                libpng-dev \
                libjpeg62-turbo-dev \
                libfreetype6-dev \
                libcurl4-openssl-dev \
                libonig-dev \
                default-mysql-client \
                unzip \
                git; then \
            installed=1; break; \
        fi; \
        echo "apt attempt ${attempt} failed (likely a mirror hiccup); retrying in $((attempt * 5))s"; \
        rm -rf /var/lib/apt/lists/*; \
        sleep $((attempt * 5)); \
    done; \
    [ "$installed" = 1 ]; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd curl mbstring zip bcmath exif; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# Let .htaccess (mod_rewrite, cache headers) take effect for the whole app root
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# --ignore-platform-reqs: same reason as local XAMPP dev (see cretzo-local-setup memory) —
# facebook/graph-sdk 5.7 declares php ^5.4|^7.0, composer.json's config.platform pin to 7.4
# doesn't survive composer 2's stricter lock-vs-real-platform check on PHP 8.x runtimes.
# Retried for the same reason as apt above: packagist/github downloads also flake.
RUN set -eux; \
    installed=0; \
    for attempt in 1 2 3; do \
        if composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-reqs; then \
            installed=1; break; \
        fi; \
        echo "composer attempt ${attempt} failed; retrying in $((attempt * 5))s"; \
        sleep $((attempt * 5)); \
    done; \
    [ "$installed" = 1 ]; \
    mkdir -p application/logs application/cache uploads; \
    chown -R www-data:www-data application/logs application/cache uploads

EXPOSE 80
