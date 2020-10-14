FROM alpine:3.12
LABEL maintainer="zhj <github@zhangSTC>" version="1.0"

WORKDIR /opt/www/tool-net-novel

COPY . /opt/www/tool-net-novel

RUN set -ex \
    && apk --update add php7 php7-opcache php7-json php7-curl php7-mbstring php7-dom php7-zip php7-pdo_sqlite\
    php7-xml php7-xmlwriter php7-xmlreader php7-tokenizer php7-session php7-fileinfo composer sqlite\
    && rm -rf /var/cache/apk/* \
    # show php version and extensions
    && php -v \
    && php -m \
    # show composer version and init verdor
    && composer --version \
    && composer install --no-dev --optimize-autoloader --profile \
    # laravel setting
    && php artisan config:cache \
    && php artisan route:cache \
    && chmod 777 bootstrap/cache \
    && chmod 777 storage \
    # create db
    && cat database/tool-net-novel.sql | sqlite3 database/tool-net-novel.db \
    # init crontab
    && echo "* * * * * php /opt/www/tool-net-novel/artisan schedule:run >> /dev/null 2>&1" | tee cronjob \
    && crontab cronjob \
    && crontab -l \
    # start command
    && { \
        echo "crond"; \
        echo "php artisan serve --host=0.0.0.0 --port=80"; \
    } | tee start.sh

EXPOSE 80

ENTRYPOINT ["sh", "start.sh"]
