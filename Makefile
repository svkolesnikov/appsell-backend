# Тут у нас переменные всякие будут

FPM_CONTAINER_TAG=10.1.4.5:5000/backend/app:latest

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