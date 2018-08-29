# Тут у нас переменные всякие будут

FPM_CONTAINER_TAG=11.1.1.5:5000/backend/fpm:latest

# КОМАНДЫ:
# Для локального запуска

all:
	docker-compose build

server.start:
	docker-compose up -d

server.stop:
	docker-compose down

server.restart: server.stop server.start

# Сборка и отправка контейнера в репозиторий

repository.build:
	./build/build.sh

repository.push: repository.build
	docker push $(FPM_CONTAINER_TAG)