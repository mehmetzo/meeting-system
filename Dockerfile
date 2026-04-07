FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libldap2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zlib1g-dev \
    locales \
    zip unzip curl git \
    && echo "tr_TR.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen tr_TR.UTF-8 \
    && update-locale LANG=tr_TR.UTF-8 \
    && docker-php-ext-configure ldap \
       --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-configure gd \
       --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        ldap \
        zip \
        gd \
        mbstring \
        opcache \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ENV LANG=tr_TR.UTF-8
ENV LC_ALL=tr_TR.UTF-8
ENV LANGUAGE=tr_TR.UTF-8

COPY apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80