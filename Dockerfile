FROM php:8.2-apache

# Kopioi kaikki tiedostot konttiin
COPY . /var/www/html/

# Ota mysqli tuki käyttöön, jotta tietokanta toimii
RUN docker-php-ext-install mysqli

EXPOSE 80
