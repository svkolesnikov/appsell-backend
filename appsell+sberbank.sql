CREATE SCHEMA IF NOT EXISTS payments;

CREATE TABLE IF NOT EXISTS payments.payments (
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

CREATE SEQUENCE IF NOT EXISTS payments.payments_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.payments_id_seq OWNED BY payments.payments.id;

CREATE TABLE IF NOT EXISTS payments.promocode (
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

CREATE SEQUENCE IF NOT EXISTS payments.promocode_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.promocode_id_seq OWNED BY payments.promocode.id;

CREATE TABLE IF NOT EXISTS payments.promocode_log (
    id bigint NOT NULL,
    message text,
    ctime timestamp
);

CREATE SEQUENCE IF NOT EXISTS payments.promocode_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.promocode_log_id_seq OWNED BY payments.promocode_log.id;

CREATE TABLE IF NOT EXISTS payments.payments_log (
     id bigint NOT NULL,
     message text,
     ctime timestamp
);

CREATE SEQUENCE IF NOT EXISTS payments.payments_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.payments_log_id_seq OWNED BY payments.payments_log.id;


CREATE TABLE IF NOT EXISTS payments.request_log (
    id bigint NOT NULL,
    rquid character varying(32) NOT NULL,
    message character varying(255) NOT NULL,
    request_data json NOT NULL,
    response_data json NOT NULL,
    ctime timestamp,
    mtime timestamp
);

CREATE SEQUENCE IF NOT EXISTS payments.request_log_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.request_log_id_seq OWNED BY payments.request_log.id;

CREATE TABLE IF NOT EXISTS payments.companies (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    short_title character varying(10) NOT NULL,
    ctime timestamp
);

CREATE SEQUENCE IF NOT EXISTS payments.companies_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.companies_id_seq OWNED BY payments.companies.id;

CREATE TABLE IF NOT EXISTS payments.members (id bigint NOT NULL, payment_id character varying(255) NOT NULL, ctime timestamp);
CREATE SEQUENCE IF NOT EXISTS payments.members_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.members_id_seq OWNED BY payments.members.id;

CREATE TABLE IF NOT EXISTS payments.order_numbers (id bigint NOT NULL, payment_id character varying(255) NOT NULL, ctime timestamp);
CREATE SEQUENCE IF NOT EXISTS payments.order_numbers_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE payments.order_numbers_id_seq OWNED BY payments.order_numbers.id;


-- Изменения в существующие таблицы
ALTER TABLE userdata.profile ADD COLUMN id_qr CHARACTER VARYING(20) DEFAULT NULL;

ALTER TABLE offerdata.offer ADD COLUMN price decimal DEFAULT 0;
ALTER TABLE offerdata.offer ADD COLUMN pay_qr boolean DEFAULT false;