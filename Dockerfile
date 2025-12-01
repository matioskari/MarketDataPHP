FROM php:8.2-apache

# Asenna PDO ja MySQL-tuki
RUN docker-php-ext-install pdo pdo_mysql

# Kopioi kaikki tiedostot konttiin
COPY . /var/www/html/

# Aseta työskentelyhakemisto
WORKDIR /var/www/html/

EXPOSE 80
