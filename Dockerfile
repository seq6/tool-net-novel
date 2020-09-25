FROM alpine:3.12
LABEL maintainer="zhj <zhuceyong180@163.com>" version="1.0"

RUN set -ex \
    && apk update \
    && apk upgrade \
    && apk add php7 php7-opcache php7-json php7-curl php7-mbstring php7-dom php7-zip php7-pdo_sqlite\
    php7-xml php7-xmlwriter php7-xmlreader php7-tokenizer php7-session php7-fileinfo \
    && apk add composer \
    && apk add sqlite \
    # show php version and extensions
    && php -v \
    && php -m \
    && composer --version

WORKDIR /opt/www/bp-net-novel

COPY . /opt/www/bp-net-novel

RUN composer install --no-dev --optimize-autoloader --profile \
    && php artisan config:cache \
    && php artisan route:cache \
    && chmod 777 bootstrap/cache \
    && chmod 777 storage \
    && cat database/bp-net-novel.sql | sqlite3 database/bp-net-novel.db \
    && echo "* * * * * php /opt/www/net-novel/artisan schedule:run >> /dev/null 2>&1" | tee cronjob \
    && crontab cronjob \
    && crontab -l \
    && crond

EXPOSE 80

ENTRYPOINT ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
