FROM php:8.2-fpm

# Устанавливаем базовые зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring  # Оставим на всякий случай

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www

# Копируем только нужные файлы (оптимизация)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Копируем остальные файлы проекта
COPY . .

# Права на storage
RUN chown -R www-data:www-data /var/www/storage
RUN chmod -R 775 /var/www/storage