FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    git \
    curl \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libxml2-dev \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    pdo_pgsql \
    zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
