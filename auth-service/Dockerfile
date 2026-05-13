# Stage 1: Composer dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist

# Stage 2: Node dependencies and assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm install
COPY resources/ ./resources/
COPY public/ ./public/
RUN npm run build

# Stage 3: Runtime
FROM php:8.3-fpm-alpine
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath

# Configure Nginx
COPY .docker/nginx.conf /etc/nginx/http.d/default.conf

# Configure Supervisor
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application code
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build/ ./public/build/
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
