FROM php:8.2-fpm
# Install MySQL extensions yang dibutuhkan (mysqli, pdo, pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql
# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
&& chmod -R 755 /var/www/html
WORKDIR /var/www/html
# Expose port 9000 untuk PHP-FPM
EXPOSE 9000