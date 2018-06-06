#!/bin/bash

DOCKER_DIR="${TMPDIR:-/tmp}/appsell-backend"

APP_DOCKERFILE="${DOCKER_DIR}/build/php-fpm/Dockerfile"
NGINX_DOCKERFILE="${DOCKER_DIR}/build/nginx/Dockerfile"

PUSH_CONTAINERS=false

while [ -n "$1" ]
do
case "$1" in
--push) PUSH_CONTAINERS=true ;;
esac
shift
done

echo "Создадим временный каталог"
rm -rf ${DOCKER_DIR}
mkdir -p ${DOCKER_DIR}

echo "Подготовим исходники приложения"
cp -R ./ ${DOCKER_DIR}

echo "Скомпилируем контейнер приложения"

TAG_PREFIX="10.5.5.8:5000/backend"

APP_TAG="${TAG_PREFIX}/app:latest"
NGINX_TAG="${TAG_PREFIX}/nginx:latest"

docker build -t ${APP_TAG}   -f ${APP_DOCKERFILE}   ${DOCKER_DIR}
docker build -t ${NGINX_TAG} -f ${NGINX_DOCKERFILE} ${DOCKER_DIR}

echo "Удалим код приложения и временный каталог"
rm -rf ${DOCKER_DIR}

if ${PUSH_CONTAINERS}
then
echo "Отправка контейнеров в репозиторий"
docker push ${APP_TAG}
docker push ${NGINX_TAG}
fi

echo "Готово"