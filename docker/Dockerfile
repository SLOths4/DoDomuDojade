FROM php:8.3-fpm

RUN apt-get update && apt-get install -y cron curl postgresql-client

COPY . /app
WORKDIR /app

RUN composer install

COPY docker/crontab /etc/cron.d/app-cron
RUN chmod 0644 /etc/cron.d/app-cron

CMD ["php-fpm"]
