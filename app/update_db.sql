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
