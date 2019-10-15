#!/bin/bash

DOCKER_DIR="${TMPDIR:-/tmp}/appsell"

FPM_DOCKERFILE="${DOCKER_DIR}/docker/Dockerfile"

PUSH_CONTAINERS=false

while [[ -n "$1" ]]
do
case "$1" in
--push) PUSH_CONTAINERS=true ;;
esac
shift
done

echo "Создадим временный каталог"
rm -rf "${DOCKER_DIR}"
mkdir -p "${DOCKER_DIR}"

echo "Подготовим исходники приложения"
cp -R ./ "${DOCKER_DIR}"

echo "Скомпилируем контейнер приложения"

TAG_PREFIX="11.1.1.5:5000/appsell"

FPM_TAG="${TAG_PREFIX}/app:latest"

docker build -t ${FPM_TAG} -f "${FPM_DOCKERFILE}" "${DOCKER_DIR}"

echo "Удалим код приложения и временный каталог"
rm -rf "${DOCKER_DIR}"

if ${PUSH_CONTAINERS}
then
echo "Отправка контейнеров в репозиторий"
docker push ${FPM_TAG}
fi

echo "Готово"