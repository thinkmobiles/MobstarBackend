<?php

use MobStar\Storage\Token\TokenRepository as Token;
use MobStar\Pager;


class SearchController extends BaseController
{

    public function __construct( Token $token )
    {
        $this->token = $token;
    }


    public function usersBeginsWith( $searchStr )
    {
        $token = Request::header( "X-API-TOKEN" );

        $session = $this->token->get_session( $token );

        $pager = new Pager(
            url( Request::path() ),
            Input::get( 'limit', 20 ),
            Input::get( 'page', 1 )
        );

        $sql = '
select
 user_id,
 coalesce(user_name, fb_user_name, g_user_name, tw_user_name ) as user_name
from(
select
  user_id,
  group_concat( user_name ) as user_name,
  group_concat( fb_user_name ) as fb_user_name,
  group_concat( g_user_name ) as g_user_name,
  group_concat( tw_user_name ) as tw_user_name
from (
select user_id, user_display_name as user_name, null as fb_user_name, null as g_user_name, null as tw_user_name
from users
where user_display_name like ?
union
select u.user_id as user_id, null as user_name, fb.facebook_user_display_name as fb_user_name, null as g_user_name, null as tw_user_name
from facebook_users fb
  left join users u on fb.facebook_user_id = u.user_facebook_id
where fb.facebook_user_display_name like ?
union
select u.user_id as user_id, null as user_name, null as fb_user_name, g.google_user_display_name as g_user_name, null as tw_user_name
from google_users g
  left join users u on g.google_user_id = u.user_google_id
where g.google_user_display_name like ?
union
select u.user_id as user_id, null as user_name, null as fb_user_name, null as g_user_name, tw.twitter_user_display_name as tw_user_name
from twitter_users tw
  left join users u on tw.twitter_user_id = u.user_twitter_id
where tw.twitter_user_display_name like ?
) i
group by user_id
) n
where user_id > 0
order by user_name';

        $limit = $pager->getLimit();
        $offset = $pager->getOffset();

        if( $limit ) {
            $sql .= "\n limit ".(int)($limit+2); // try to get more users to see if there is next page
        }

        if( $offset ) {
            $sql .= "\n offset ".(int)$offset;
        }

        $val = $searchStr.'%';

        $params = array( $val, $val, $val, $val );

        $rows = DB::select( $sql, $params );

        $rows = $pager->getAdjustedToPage( $rows );

        $data = array();

        foreach( $rows as $row ) {
            $data['users'][] = array(
                'userId' => $row->user_id,
                'userName' => $row->user_name
            );
        }

        if( $pager->needPrevLink() ) {
            $data['previous'] = $pager->getPrevLink();
        }
        if( $pager->needNextLink() ) {
            $data['next'] = $pager->getNextLink();
        }


        $data['searchStr'] = $searchStr;

        $response = Response::make( $data, 200 );

        return $response;
    }
}
