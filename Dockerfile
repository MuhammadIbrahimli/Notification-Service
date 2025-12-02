FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Включение mod_rewrite для Apache
RUN a2enmod rewrite headers

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование файлов проекта
COPY . .

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader

# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Создание необходимых директорий
RUN mkdir -p storage/logs storage/queue \
    && chown -R www-data:www-data storage

EXPOSE 80

CMD ["apache2-foreground"]
