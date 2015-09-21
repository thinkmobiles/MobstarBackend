-- check that entry views are the same as in table entry_view

select e.entry_id, e.entry_views, views.entry_views as real_entry_views, e.entry_views_added, views.entry_views_added as real_entry_views_added
from entries e
  left join (
    select entry_view_entry_id as entry_id,
      sum( if( entry_view_user_id = 1, 1, 0 ) ) as entry_views_added,
      sum( if( entry_view_user_id <> 1, 1, 0 ) ) as entry_views
    from entry_views
    group by entry_view_entry_id) as views on e.entry_id=views.entry_id
where
  e.entry_views <> views.entry_views
  or e.entry_views_added <> views.entry_views_added
;
