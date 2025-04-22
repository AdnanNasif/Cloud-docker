FROM php:7.4-apache

# Install required PHP extensions for PHPMailer and general functionality
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    unzip \
    zip \
    git \
    && docker-php-ext-install mysqli zip

# Enable Apache mod_rewrite (optional, good for clean URLs)
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory inside the container
WORKDIR /var/www/html
