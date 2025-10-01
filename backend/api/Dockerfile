# Imagen base de PHP con FPM (para trabajar con Nginx o Apache)
FROM php:8.2-fpm-alpine

# Instalar extensiones requeridas
RUN docker-php-ext-install pdo pdo_mysql

# Instalar utilidades + Composer
RUN apk add --no-cache git unzip curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Carpeta de trabajo
WORKDIR /var/www/html

# Copiar archivos de Composer primero (para cachear dependencias)

COPY composer.json composer.lock ./

# Instalar dependencias PHP (prod)
RUN composer install --no-dev --optimize-autoloader

# Copiar el código backend
COPY backend/api .
# Comando por defecto
CMD ["php-fpm", "-F"]
