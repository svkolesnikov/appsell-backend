
alter table financedata.base_commission alter column "type" type varchar(30);
insert into financedata.base_commission (type, description, ctime, mtime, percent)
    values ('solar_staff_payout', 'Комиссия SolarStaff за вывод средств', now(), now(), 10);