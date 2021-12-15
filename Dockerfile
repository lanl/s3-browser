FROM webdevops/php-nginx:8.1-alpine
COPY . /app
RUN apk update && apk add ca-certificates
RUN composer install -d /app
