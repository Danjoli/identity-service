# syntax=docker/dockerfile:1.7
FROM composer:2.8 AS dependencies
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --classmap-authoritative

FROM php:8.5-fpm-alpine AS app
RUN apk add --no-cache libpq \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && apk del .build-deps
WORKDIR /var/www/app
COPY --chown=www-data:www-data . .
COPY --from=dependencies --chown=www-data:www-data /app/vendor ./vendor
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var
ENV APP_ENV=prod APP_DEBUG=0
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 CMD ["php-fpm", "-t"]
CMD ["php-fpm", "-F"]

FROM nginx:1.29-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/app/public /var/www/app/public
RUN sed -i 's|^pid.*|pid /tmp/nginx.pid;|' /etc/nginx/nginx.conf \
    && chown -R nginx:nginx /var/cache/nginx /etc/nginx/conf.d
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 CMD ["wget", "-q", "--spider", "http://127.0.0.1:8080/health"]
USER nginx
