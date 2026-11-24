FROM php:8.3-apache

# Install PDO MySQL extension and finfo (fileinfo) support
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Apache: serve from /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY . /var/www/html

# Writable upload directories (uploads/ is used by the vulnerable app,
# secure_uploads/ is OUTSIDE the document root and used by the secure app)
RUN mkdir -p /var/www/html/uploads/profiles /var/www/html/secure_uploads \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/secure_uploads \
    && chmod -R 775 /var/www/html/uploads /var/www/html/secure_uploads

# Prevent PHP execution inside the public upload directory even in the
# vulnerable app (defense that does not rely on app-level validation).
RUN printf '<Directory "/var/www/html/uploads">\n    php_admin_flag engine off\n    AddType text/plain .php .php3 .php4 .php5 .phtml\n</Directory>\n' > /etc/apache2/conf-available/no-php-in-uploads.conf \
    && a2enconf no-php-in-uploads

EXPOSE 80
