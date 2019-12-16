
create table actiondata.followed_user
(
    id bigserial primary key,
    who_user_id uuid,
    whom_user_id uuid,
    earned_amount numeric,
    ctime timestamp
);

create index on actiondata.followed_user (ctime);
create index on actiondata.followed_user (who_user_id, ctime desc);