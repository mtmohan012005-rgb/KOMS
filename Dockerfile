FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install MariaDB server, client, GD, and dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-server \
    mariadb-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

# Configure Apache DirectoryIndex to prioritize index.php and index.html
RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/mods-enabled/dir.conf

# Enable AllowOverride All for /var/www/html
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Configure PHP production defaults
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'upload_max_filesize=20M'; \
    echo 'post_max_size=25M'; \
    echo 'memory_limit=256M'; \
} > /usr/local/etc/php/conf.d/koms-recommended.ini

WORKDIR /var/www/html
COPY . /var/www/html/

# Setup entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure uploads directory and permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80 10000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["bash", "-lc", "php /var/www/html/scripts/import_students_from_env.php || true; exec apache2-foreground"]
