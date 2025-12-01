FROM php:8.2-apache

# PostgreSQL-ajurit
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Kopioi kaikki tiedostot konttiin
COPY . /var/www/html/

# Aseta työskentelyhakemisto
WORKDIR /var/www/html/

EXPOSE 80
