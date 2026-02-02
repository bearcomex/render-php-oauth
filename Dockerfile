FROM php:8.2-apache

# Install Postgres PDO driver
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql

# Copy app files
COPY . /var/www/html/

# Enable Apache rewrite module
RUN a2enmod rewrite

EXPOSE 80
