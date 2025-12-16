FROM php:8.2-fpm

# Устанавливаем драйверы PDO для MySQL
RUN docker-php-ext-install pdo pdo_mysql

# (необязательно, но полезно)
RUN mkdir -p /tmp/sessions && chmod -R 777 /tmp/sessions
