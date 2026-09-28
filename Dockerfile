FROM php:8.2-apache

# Install system dependencies (CA certs for TiDB SSL)
RUN apt-get update && apt-get install -y ca-certificates && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

# Create uploads directories and set permissions
RUN mkdir -p /var/www/html/uploads/profile_pictures /var/www/html/uploads/certificates \
    && chown -R www-data:www-data /var/www/html/uploads

# Set working directory
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80
