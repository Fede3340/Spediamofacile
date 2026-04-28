# =============================================================================
# Dockerfile — Sprint 7.3 PHP-FPM + nginx multi-stage
# =============================================================================
# Target: Render/Fly/k8s con reverse proxy esterno (Caddy/Cloudflare).
# Pattern: un container, nginx fronte + php-fpm backend via supervisord.
# =============================================================================

FROM php:8.3-fpm-alpine AS base

# ---- Pacchetti di sistema ---------------------------------------------------
# Solo il minimo per build + runtime. Separiamo build-deps (poi rimossi)
# dalle runtime-deps per mantenere l'immagine leggera.
RUN apk add --no-cache \
        # runtime
        nginx \
        supervisor \
        bash \
        curl \
        git \
        icu-libs \
        libpng \
        libjpeg-turbo \
        libwebp \
        freetype \
        libzip \
        postgresql-libs \
        # postgresql-client: pg_dump per backup daily (Sprint 7.5)
        postgresql-client \
        # py3-pip: serve per b2 CLI installata sotto (~20 MB runtime)
        py3-pip \
        oniguruma \
    # build deps (rimosse dopo compilazione estensioni PHP)
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    # Estensioni PHP richieste da Laravel + moneyphp + BRT HTTP client
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        gd \
        intl \
        bcmath \
        zip \
        opcache \
    # Redis per cache/queue (opzionale ma raccomandato per produzione)
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    # b2 CLI (Backblaze) per backup/restore Postgres. Sprint 7.5.
    && pip install --no-cache-dir --break-system-packages b2 \
    && rm -rf /tmp/* /var/cache/apk/* /root/.cache

# ---- Composer ----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- Config PHP production --------------------------------------------------
# opcache preload + settings ottimizzati per web app
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.jit=tracing'; \
        echo 'opcache.jit_buffer_size=64M'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=10M'; \
        echo 'post_max_size=12M'; \
        echo 'max_execution_time=60'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/99-production.ini

# ---- App --------------------------------------------------------------------
WORKDIR /var/www/html

# Copia prima composer.* per sfruttare layer cache: se il codice cambia ma
# le deps no, skippiamo il composer install.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    && rm -rf /root/.composer

# Ora copiamo tutto il codice e finalizziamo autoload + cache Laravel
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ---- nginx + supervisord ----------------------------------------------------
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# ---- entrypoint -------------------------------------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Render e molti PaaS iniettano $PORT — nginx ascolta su quella (default 8080)
ENV PORT=8080
EXPOSE 8080

# Healthcheck Docker nativo (Render lo legge via render.yaml healthCheckPath)
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS "http://localhost:${PORT}/api/health/live" || exit 1

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
