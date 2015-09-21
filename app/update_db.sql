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
