FROM php:8.2-cli-alpine

# install system dependencies for sqlite and common tools
RUN apk add --no-cache \
    bash \
    sqlite \
    sqlite-dev \
    libzip-dev \
    oniguruma-dev \
    curl \
    git \
    unzip \
    make \
    gcc \
    g++ \
    autoconf \
    tzdata

# install and enable pdo_sqlite
RUN docker-php-ext-install pdo pdo_sqlite

# install composer
ENV COMPOSER_HOME=/tmp/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

# copy composer files first to leverage build cache, then install deps
COPY composer.json composer.lock ./
# install dependencies (including dev for interview/test runs) and optimize autoloader
RUN composer install --no-interaction --no-ansi --no-progress && \
    composer dump-autoload -o

# copy rest of the application
COPY . .

# ensure var and logs are writable (and vendor for IDEs that mount)
RUN mkdir -p var vendor && chown -R www-data:www-data var logs vendor || true

# expose port used by built-in server
EXPOSE 8080

# install container entrypoint which also starts a lightweight cron (runs prune job)
COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/crontabs/www-data /etc/crontabs/www-data
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
