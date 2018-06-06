# Пользователи

### Добавление пользователя

Получаем хеш пароля: 

`pip install passlib`
`python -c 'from passlib.hash import sha512_crypt; import getpass; print sha512_crypt.using(rounds=5000).hash(getpass.getpass())'`

Создаем пользователя:

`ansible -u centos -i hosts.ini <hosts> -b -m user -a 'name=<new_username> password=<password_hash>'`

Создаем пользователя с sudo:

`ansible -u centos -i hosts.ini <hosts> -b -m user -a 'name=<new_username> password=<password_hash> append=yes groups=wheel'`

### Добавление public key на сервера

`ansible -u centos -i hosts.ini <hosts> -b -m authorized_key -a "user=<dst_username> state=present key={{ lookup('file', 'id_rsa.pub') }}"`

### Добавление пользователя ansible для deploy через TeamCity

* `ansible -u <current_user> -i hosts.ini <hosts> -b -K -m user -a 'name=ansible append=yes groups=docker'`
* `ansible -u <current_user> -i hosts.ini <hosts> -b -K -m authorized_key -a "user=ansible state=present key={{ lookup('file', './teamcity/.ssh/id_rsa.pub') }}"`