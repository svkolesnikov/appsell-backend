# Набор docker host

Docker Host – машина, на которой будут запускаться контейнеры приложений

#### Использование

`ansible-playbook -K -b -u <пользователь> -i hosts.ini -l docker-hosts docker-host/main.yml`

#### Добавление группы docker пользователю

Необходимо для того, чтобы пользователь мог использовать `docker` команды
без `sudo`, т. к. по-умолчанию доступ к сокету docker-демона разрешен только
root и группе docker.

`ansible -u <current_user> -i hosts.ini docker-hosts -b -K -m user -a 'name=<username> append=yes groups=docker'`