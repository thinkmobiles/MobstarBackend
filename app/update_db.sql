alter table `entries`
add `entry_duration` int not null default -1
;


-- convert database with tabses to utf8 charset

alter database mobstar default character set utf8;
alter database mobstar default collate utf8_unicode_ci;

 -- get sql queries to convert tables from latin1 to utf8
select concat( 'alter table `', table_name, '` convert to character set utf8;' ) as cmd from information_schema.tables
where
  table_schema = 'mobstar'
  and table_collation like '%latin1%'
;


-- set default values on users table
alter table `users` alter column `user_full_name` set default '';
alter table `users` alter column `user_display_name` set default '';


alter table `notifications` modify column notification_type enum('Entry Vote','Entry Comment','Message','Follow','splitScreen');

-- add column 'splitVideoId' to entries table to hold base entry id on with that split was made
alter table `entries` add column `entry_splitVideoId` int default null comment 'base entry used for split';


-- create continents table
create table `continents` (
  `continent_id` int not null,
  `continent_name` varchar(100) not null,
  primary key (`continent_id`)
) engine InnoDB;

insert into `continents`( `continent_id`, `continent_name` ) values
(0, 'World'),
(1, 'Africa'),
(2, 'Asia'),
(3, 'Europe'),
(4, 'North America'),
(5, 'Oceania'),
(6, 'South America')
;


-- add column `user_continent` to `users` to store the user's continent
alter table `users` add column
  `user_continent` int not null default 0
  comment 'the continent, the user belongs to';

-- add column `entry_continent` to `entries` to store the continent to which the entry belongs
alter table `entries` add column
  `entry_continent` int not null default 0
  comment 'the continent, the entry belong to';

-- add column `user_continent_filter` to table `users` to store JSON array of continent filter
alter table `users` add column
  `user_continent_filter` varchar( 200 ) not null default ''
  comment 'array of continent ids to use as filter';

-- add column `user_category_filter` to table `users` to store JSON array of category filter
alter table `users` add column
  `user_category_filter` varchar( 200 ) not null default ''
  comment 'array of category ids to use as filter';


alter table `entries` add column
  `entry_views` int unsigned not null default 0
  comment 'views count as of entry_views'
  after `entry_duration`;

alter table `entries` add column
  `entry_views_added` int unsigned not null default 0
  comment 'added views count'
  after `entry_views`;


update `entries`
  set `entry_views_added` = (select count(*)
    from `entry_views`
    where `entry_views`.`entry_view_entry_id` = `entries`.`entry_id`
      and `entry_views`.`entry_view_user_id` = 1)
;

update `entries`
  set `entry_views` = (select count(*)
    from `entry_views`
    where `entry_views`.`entry_view_entry_id` = `entries`.`entry_id`
      and `entry_views`.`entry_view_user_id` <> 1)
;


-- add app version to api_key
alter table `api_keys` add column
  `version` int not null default 0
  comment 'App version of this API_KEY';

-- set app version to old apps to 1
update `api_keys`
  set `version`=1
;

-- add new api_key for app version 2
insert into `api_keys`(`key_value`, `version`)
values( '2_xPvd11Vjj1PfgYZ5C5fIWIosTmR4ADEgVIXsXp95', 2 );

-- holds api_key used for this session
alter table `tokens` add column
  `api_key` varchar(45) not null default ''
  comment 'API_KEY for this session';

-- app version of this session
alter table `tokens` add column
  `token_app_version` int not null default 0
  comment 'app vertion for this session';

-- hide entry from feed
alter table `entries` add column
  `entry_hide_on_feed` tinyint not null default 0
  comment 'whether the entry must be hidden on main feed';


-- add category for profile entry
alter table `entries` add column
  `entry_profile_category_id` tinyint not null default 0
  comment 'category for profile entry';


-- add new api_key for app version 3
insert into `api_keys`(`key_value`, `version`)
values( '3_pyZpee2M2qIjLJ5uqqO0Mp65nL4MXwGqBWFxYUWm', 3 );


alter table `tokens` add column
  `token_device_registration_id` int not null default 0
  comment 'linked device registration: 0 - not set yet, -1 - no device registration';

-- add column to store Amazon SNS Endpoint (to avoid its creation each time we need to send PUSH)
alter table `tokens` add column
  `token_is_subscribed` tinyint not null default 0
  comment 'whether this session is subscribed to SNS updates topic: 0 - not subscribed, -1 - no need to subscribe';


-- create indexes to speedup user search
alter table users
add index (user_twitter_id)
;

alter table users
add index (user_display_name(20))
;

alter table google_users
add index (google_user_display_name(20))
;

alter table facebook_users
add index (facebook_user_display_name(20))
;

alter table twitter_users
add index (twitter_user_display_name(20))
;


-- add support to different file location types
alter table entry_files add column
  `entry_file_location_type` varchar( 30 ) not null default ''
  comment 'location type, like: S3, url, local'
  after entry_file_entry_id
;
