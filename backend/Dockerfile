# Developer Hosting Platform — Laravel backend image (Phase 1)
# Uses prebuilt Alpine php83-* packages (no source compilation, no clang/llvm)
FROM alpine:3.20

WORKDIR /app

# PHP 8.3 + all needed extensions from prebuilt Alpine packages
RUN apk add --no-cache \
    php83-fpm \
    php83-pdo \
    php83-pdo_pgsql \
    php83-mbstring \
    php83-curl \
    php83-intl \
    php83-bcmath \
    php83-gd \
    php83-zip \
    php83-exif \
    php83-fileinfo \
    php83-session \
    php83-openssl \
    php83-xml \
    php83-dom \
    php83-cli \
    php83-tokenizer \
    git \
    zip \
    unzip \
    composer

# composer runs as root to install into /var/composer-home, then we copy
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/var/composer-home

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Create Laravel storage directories as root before switching users
RUN mkdir -p /app/storage/logs /app/storage/framework/sessions /app/storage/framework/cache /app/storage/framework/views

# Run as non-root
RUN adduser -D -u 10001 devhost
RUN chown -R devhost:devhost /app/storage
# The vendor/composer tree above is owned by root, so the optimized-autoloader
# dump must run as root here (before USER devhost) to avoid "Permission denied".
RUN composer dump-autoload --optimize --no-interaction --no-scripts
USER devhost

COPY --chown=devhost:devhost . .

# Create bootstrap/cache AFTER COPY (COPY overwrites the whole bootstrap/ dir,
# wiping the cache/ dir created earlier). This dir is required by the
# ProviderRepository to load cached providers.
RUN mkdir -p /app/bootstrap/cache
# --no-scripts: the post-autoload-dump script runs `php artisan package:discover`,
# which connects to the `db` host. That host is unavailable during Docker build
# (no Docker Compose network yet), so scripts must be skipped here.

# NOTE: We intentionally do NOT run `php artisan package:discover` at build time.
# It connects to the `db` host (unavailable during build) and would fail. Laravel
# regenerates bootstrap/cache/services.php on first boot at runtime, when the DB
# IS reachable. This also clears any stale services.php that caused the
# "Class files does not exist" error.

RUN chmod +x artisan 2>/dev/null || true

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
