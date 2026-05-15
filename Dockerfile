FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
        sqlite3 \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN if [ "${UID}" != "0" ] || [ "${GID}" != "0" ]; then \
        groupadd -g "${GID}" app && useradd -m -u "${UID}" -g app app; \
    fi

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php-fpm"]
