## linux server. Стартовая инициализация 

Linux Server машина, на которой должны быть установлены:

* Системные зависимости
* htop

#### Использование

`ansible-playbook -K -b -u <пользователь> -i hosts.ini -l <нужный хост из hosts> linux-server/main.yml`

#### Отключение IPv6 (делается в linux-server/main.yml)

Необходимо для того, чтобы избежать багов работы ядра линукс с docker.
Иногда при переконфигурировании интерфейсов может случиться пичальное.
Рекомендации:

1. Добавить в `/etc/sysctl.conf`

   `net.ipv6.conf.all.disable_ipv6 = 1`
   
   `net.ipv6.conf.default.disable_ipv6 = 1`
   
2. `sysctl -p`
3. Добавить `AddressFamily inet` в `/etc/ssh/sshd_config`
4. `systemctl restart sshd`