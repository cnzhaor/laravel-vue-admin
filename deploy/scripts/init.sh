#!/bin/sh
set -eu

test -f .env || cp .env.example .env
test -f backend/.env || cp backend/.env.example backend/.env

docker compose build app
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD/backend:/var/www/backend" \
  -w /var/www/backend laravelvue-app composer install --no-interaction
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD/backend:/var/www/backend" \
  -w /var/www/backend laravelvue-app php artisan key:generate --force
docker compose up -d mysql redis
docker compose run --rm app php artisan migrate:fresh --seed --force
docker compose up -d

echo "初始化完成：http://localhost:${APP_PORT:-8080}"
echo "默认账号：admin / Admin@123456（生产部署前必须修改）"
