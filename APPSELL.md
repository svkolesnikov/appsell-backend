# APPSELL deamon
### Команда для запуска контролирующего процесса

Запускает процесс, который каждые 10 секунд проверяет наличие основного процесса - `sberbank:deamon`. 
Если не находит, то запускает новый.

`${DOCKER_CONTAINER_NAME}` необходимо заменить на ID контейнера `appsell_app`
```sh
cd ../var/www/

docker exec -it ${DOCKER_CONTAINER_NAME} php bin/console sberbank:deamon:controll
```

### Изменения в базе

```
CREATE SCHEMA payments;

CREATE TABLE payments.payments (
id bigint NOT NULL,
rquid character varying(32) NOT NULL,
rqtm timestamp  NOT NULL,
company_id integer NOT NULL,
member_id character varying(32) NOT NULL,
order_number character varying(32) NOT NULL,
order_create_date timestamp  NOT NULL,
position_name character varying(256) NOT NULL,
position_count int NOT NULL,
position_sum integer NOT NULL,
position_description text NOT NULL,
id_qr character varying(20) NOT NULL,
order_sum int NOT NULL,
currency character varying(3) NOT NULL,
order_description text NOT NULL,
order_id character varying(255),
order_from_url character varying(255),
ctime timestamp,
mtime timestamp,
promocode_id int,
seller_id character varying(255) NOT NULL,
status int NOT NULL
);

CREATE SEQUENCE payments.payments_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.payments_id_seq OWNED BY payments.payments.id;

CREATE TABLE payments.promocode (
id bigint NOT NULL,
company_id int NOT NULL,
code character varying(255) NOT NULL,
seller_id character varying(255),
received timestamp NOT NULL,
given timestamp,
usage_status int,
ctime timestamp,
mtime timestamp
);

CREATE SEQUENCE payments.promocode_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.promocode_id_seq OWNED BY payments.promocode.id;

CREATE TABLE payments.promocode_log (
id bigint NOT NULL,
message text,
ctime timestamp
);

CREATE SEQUENCE payments.promocode_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.promocode_log_id_seq OWNED BY payments.promocode_log.id;

CREATE TABLE payments.payments_log (
id bigint NOT NULL,
message text,
ctime timestamp
);

CREATE SEQUENCE payments.payments_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

ALTER SEQUENCE payments.payments_log_id_seq OWNED BY payments.payments_log.id;


CREATE TABLE payments.request_log (
id bigint NOT NULL,
rquid character varying(32) NOT NULL,
message character varying(255) NOT NULL,
request_data json NOT NULL,
response_data json NOT NULL,
ctime timestamp,
mtime timestamp
);

CREATE SEQUENCE payments.request_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.request_log_id_seq OWNED BY payments.request_log.id;

CREATE TABLE payments.companies (
id bigint NOT NULL,
title character varying(255) NOT NULL,
short_title character varying(10) NOT NULL,
ctime timestamp
);

CREATE SEQUENCE payments.companies_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.companies_id_seq OWNED BY payments.companies.id;

```
