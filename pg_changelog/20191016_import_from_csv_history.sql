
create table actiondata.import_from_csv_log (
    id bigserial primary key,
    filename varchar(200),
    user_id uuid references userdata.user(id),
    click_id uuid references actiondata.offer_execution(id),
    event_name varchar(50),
    error varchar (100),
    data text,
    ctime timestamp
);

create unique index import_csv_click_event on actiondata.import_from_csv_log (click_id, event_name);

grant select,update,delete,insert on actiondata.import_from_csv_log to backend;
grant select,usage on actiondata.import_from_csv_log_id_seq to backend;
