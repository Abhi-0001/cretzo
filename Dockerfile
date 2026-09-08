# Matches production runtime (Hostinger runs PHP 8.0; composer.json pins the
# platform to 7.4 only so facebook/graph-sdk resolves — see application/config
# notes). Used for local repro (docker-compose) and the GitHub Actions smoke test.
FROM php:8.0-apache

# deb.debian.org resets connections mid-download often enough to fail CI on a
# clean tree (seen as "read (104: Connection reset by peer)" on a random .deb),
# so give apt its own retries/timeouts and retry the whole update+install cycle.
#
# NOTE: every comment about this step lives OUT here. Docker joins a backslash-
# continued RUN into a SINGLE shell line, so a '#' placed inside the block below
# would comment out every command that follows it on that line.
#
# There is deliberately no --fix-missing on the apt-get install. It makes apt exit
# 0 while SKIPPING any package it could not fetch, so the retry loop sets
# installed=1, breaks, and reports success - and the build then dies further down
# in the gd configure with a confusing "png.h not found" instead of the actual
# download failure. A package that did not install has to fail the apt step, which
# is the thing the retry loop exists to retry.
#
# The dpkg -s line then confirms the gd/zip build dependencies are really
# installed, so a partial install is caught there rather than inside ./configure
# output. It asks dpkg rather than looking for header files: header paths are
# arch-dependent (an earlier attempt globbed /usr/include/*/jpeglib.h, but Debian
# ships that at /usr/include/jpeglib.h and only jconfig.h under the multiarch
# directory, so the check failed a build that was in fact fine).
#
# curl and mbstring are NOT in the ext-install list: php:8.0-apache is built
# --with-curl and --enable-mbstring (both visible in the image's own config blob),
# so they are compiled into the binary already and rebuilding them just produces a
# second copy of an extension that is always resident.
#
# WHY default-mysql-client IS NOT IN THIS LIST ANY MORE.
#
# The build started failing on a 404, not a timeout:
#
#     E: Failed to fetch .../mariadb-10.5/mariadb-common_10.5.29-0+deb11u1_all.deb
#        404  Not Found
#
# That is the stale-index-versus-pool failure, and the retry loop below cannot fix
# it: `apt-get update` had returned an index naming 10.5.29, but the pool node no
# longer carried that revision, so every retry asked for the same missing file and
# failed identically five times over - which is why the run reported "not a
# transient mirror error". It is transient in origin and permanent in effect.
#
# mariadb-common was only ever pulled in as a dependency of default-mysql-client,
# and nothing in this image uses the mysql CLI. The db container's healthcheck runs
# `mysqladmin` inside the mysql:8.0 image, which ships its own client. And the PHP
# extensions do not need it either: mysqli and pdo_mysql are built against the
# bundled mysqlnd driver, not libmysqlclient.
#
# So the package was pure weight, and dropping it removes the whole dependency
# branch the 404 came from. Bullseye is oldstable and its security pool will keep
# rotating revisions, so fewer packages here is durably better.
#
# A SECOND, SEPARATE apt failure also showed up, independent of the package list:
#
#     E: Release file for http://deb.debian.org/debian-security/dists/bullseye-security/InRelease
#        is expired (invalid since 4h 58min 13s). Updates for this repository will not be applied.
#
# Confirmed against a bare `debian:bullseye-slim` image with nothing else installed -
# this is not caused by anything in this Dockerfile. Bullseye is past its regular
# security-support window, and the bullseye-security Release file's own Valid-Until
# timestamp has lapsed with no fresher one being republished. The retry loop cannot
# fix this: every retry re-fetches the identical, identically-expired file.
#
# Acquire::Check-Valid-Until "false" is the standard, narrow fix for exactly this
# case. It does NOT disable GPG verification - apt still rejects an unsigned or
# tampered Release file - it only stops treating a validly-signed file's lapsed
# self-declared expiry as fatal. Verified locally: apt-get update fails against
# debian:bullseye-slim without this line and succeeds with it.
RUN set -eux; \
    printf 'Acquire::Retries "5";\nAcquire::http::Timeout "30";\nAcquire::https::Timeout "30";\nAcquire::http::No-Cache "true";\nAcquire::http::Pipeline-Depth "0";\nAcquire::Check-Valid-Until "false";\n' \
        > /etc/apt/apt.conf.d/99-network-resilience; \
    installed=0; \
    for attempt in 1 2 3 4 5; do \
        if apt-get update && apt-get install -y --no-install-recommends \
                libzip-dev \
                libpng-dev \
                libjpeg62-turbo-dev \
                libfreetype6-dev \
                libcurl4-openssl-dev \
                libonig-dev \
                unzip \
                git; then \
            installed=1; break; \
        fi; \
        echo "apt attempt ${attempt} failed (likely a mirror hiccup); retrying in $((attempt * 5))s"; \
        rm -rf /var/lib/apt/lists/*; \
        sleep $((attempt * 5)); \
    done; \
    [ "$installed" = 1 ]; \
    dpkg -s libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev > /dev/null; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd zip bcmath exif; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# Let .htaccess (mod_rewrite, cache headers) take effect for the whole app root
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Forward docker-compose's `environment:` vars (CI_ENV, DB_HOST, ...) from Apache's
# own process environment into $_SERVER for PHP scripts.
#
# Without this, the app silently booted as ENVIRONMENT=production instead of the
# `testing` docker-compose.yml sets, and the homepage rendered a Database Error
# (mysqli: Connection refused) rather than the real page - confirmed by dumping
# $_SERVER['CI_ENV'] via an actual HTTP request against this image.
#
# Root cause: index.php resolves ENVIRONMENT from `$_SERVER['CI_ENV']`, not
# getenv('CI_ENV'). Setting a variable via Docker's `environment:` (or `docker run
# -e`) puts it in the process environment that Apache's mod_php can read with
# getenv() - confirmed separately, that part works - but Apache does NOT copy
# arbitrary process env vars into the CGI-style $_SERVER space that PHP scripts see;
# it only forwards the specific ones it is told to via mod_env's PassEnv (already
# enabled in this base image - `apache2ctl -M` shows env_module loaded).
#
# Scoped to exactly the variables docker-compose.yml sets for this container, not a
# blanket forward of the whole environment.
RUN { \
        echo 'PassEnv CI_ENV'; \
        echo 'PassEnv DB_HOST'; \
        echo 'PassEnv DB_PORT'; \
        echo 'PassEnv DB_USER'; \
        echo 'PassEnv DB_PASS'; \
        echo 'PassEnv DB_NAME'; \
    } > /etc/apache2/conf-available/passenv-ci.conf; \
    a2enconf passenv-ci

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
