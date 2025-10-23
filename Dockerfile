# Base image PHP có FPM
FROM php:8.2-fpm

# Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libonig-dev libxml2-dev libzip-dev curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Cài đặt Redis extension (quan trọng cho queue/cache)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Đặt thư mục làm việc
WORKDIR /var/www/html

# Copy toàn bộ source code vào container
COPY . .

# Cấp quyền ghi cho storage & bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

# Cài dependencies PHP (vendor)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Cài đặt Supervisor
RUN apt-get update && apt-get install -y supervisor

# 🟩 Tạo thư mục cần thiết cho Supervisor
RUN mkdir -p /var/log/supervisor /var/run

# 🟩 Copy file cấu hình Supervisor (dùng 1 file chính)
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Expose cổng PHP-FPM
EXPOSE 9000

# ✅ Supervisor sẽ quản lý cả php-fpm và queue worker
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
