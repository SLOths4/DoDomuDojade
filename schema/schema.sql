-- ENUM type for announcement status
create type  announcement_status as enum ('PENDING', 'APPROVED', 'REJECTED');

-- Users table
create table if not exists users (
    id            serial primary key,
    username      varchar(255) not null unique,
    password_hash varchar(255) not null,
    is_active     boolean default true not null,
    created_at    timestamp not null
);

-- Announcements table
-- user_id can be NULL for anonymous/free-form announcements
create table if not exists announcement (
    id          varchar(255) primary key,
    title       text not null,
    text        text not null,
    date        date not null,
    created_at  date not null,
    valid_until date not null,
    user_id     integer,
    decided_at  timestamp,
    decided_by  integer,
    status      announcement_status default 'PENDING'::announcement_status,

    constraint announcements_user_id_fkey
        foreign key (user_id) references "user"(id) on delete set null on update cascade,

    constraint announcements_decided_consistency
        check ((decided_at is not null and decided_by is not null)
            or (decided_at is null and decided_by is null)),

    constraint announcements_valid_dates
        check (date <= valid_until)
);

-- Words table (fetched from external API)
create table if not exists word (
    id         serial primary key,
    word       varchar(255) not null unique,
    ipa        text not null,
    definition text not null,
    fetched_on timestamp not null
);

-- Quotes table (fetched from external API)
create table if not exists quote (
    id         serial primary key,
    quote      text not null,
    author     varchar(255) not null,
    fetched_on timestamp not null
);

-- Weather table (fetched from external API)
create table if not exists weather (
    id         serial primary key,
    payload    jsonb not null,
    fetched_on timestamp not null
);

-- Modules table (settings/configuration)
create table if not exists module (
    id          serial primary key,
    module_name varchar(255) not null unique,
    is_active   boolean default false not null,
    start_time  time not null,
    end_time    time not null,

    constraint modules_time_check
        check (start_time < end_time)
);

-- Countdowns table (user-specific)
create table if not exists countdown (
    id       serial primary key,
    title    text not null,
    count_to date not null,
    user_id  integer not null,

    constraint countdowns_user_id_fkey
        foreign key (user_id) references "user"(id) on delete cascade on update cascade
);

-- Indexes for performance
create index if not exists idx_announcement_user_id on announcement(user_id);
create index if not exists idx_announcement_status on announcement(status);
create index if not exists idx_announcement_date on announcement(date);
create index if not exists idx_countdown_user_id on countdown(user_id);
create index if not exists idx_word_word on word(word);
create index if not exists idx_quote_author on quote(author);
create index if not exists idx_weather_fetched_on on weather(fetched_on desc);
