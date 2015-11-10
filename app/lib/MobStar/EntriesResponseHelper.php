<?php

namespace MobStar;

class EntriesResponseHelper extends ResponseHelper
{
    /**
     * Returns response when no entries found.
     *
     * @return array
     */
    public static function noEntries()
    {
        $code = 404;
        $data = array( 'error' => 'No Entries Found' );

        return array( 'code' => $code, 'data' => $data );
    }


    /**
     * Returns response, when no entries found and user is provided in request.
     *
     * @param int $userId
     * @param int $sessionUserId
     * @return array
     */
    public static function onlyUser( $userId, $sessionUserId )
    {
        $current = array();
        $current[ 'id' ] = null;
        $current[ 'user' ] = ResponseHelper::oneUser( $userId, $sessionUserId );

        $starFlags = ResponseHelper::getStarFlags( $userId, $sessionUserId );
        $current['user']['isMyStar'] = (int)$starFlags['isMyStar'];
        $current['user']['iAmStar'] = (int)$starFlags['iAmStar'];
        $current[ 'category' ] = null;
        $current[ 'type' ] = null;
        $current[ 'name' ] = null;
        $current[ 'description' ] = null;
        $current[ 'created' ] = null;
        $current[ 'modified' ] = null;

        $data = array();
        $data[ 'entries' ][ ][ 'entry' ] = $current;

        $starredBy = ResponseHelper::getFollowers( $userId );
        $data[ 'starredBy' ] = $starredBy;
        $data['fans'] = count($starredBy);
        $status_code = 200;

        return array( 'code' => $status_code, 'data' => $data );
    }


    public static function getOneEntry( $entry, $sessionUserId, $showFeedback, array $fields = NULL )
    {
        if ( $fields )
            return self::getOneEntryWithFields( $entry, $sessionUserId, $showFeedback, $fields );

        $data = self::oneEntryById( $entry->entry_id, $sessionUserId, true );
        if( empty( $data ) )
        {
            return false;
        }

        if( $showFeedback == 1 )
        {
            $data['feedback'] = array();
            foreach( $entry->comments as $comment )
            {
                $data['feedback'][] = array(
                    'comment'        => $comment->comment_content,
                    'commentDate'    => $comment->comment_added_date,
                    'commentDeleted' => (bool)$comment->comment_deleted
                );
            }
        }

        return $data;
    }


    public static function getEntryInfo( $entryId, $sessionUserId )
    {
        $entryInfo = self::oneEntryInfo( $entryId, $sessionUserId );

        $statusCode = 200;
        $data = array();

        if( (! $entryInfo) OR (count($entryInfo) < 5) ) {

            $statusCode = 404;
            $data = array( 'error' => 'Entry Not Found' );

        } else {

            $data['entries'][]['entry'] = $entryInfo;
        }

        return array( 'data' => $data, 'code' => $statusCode );
    }


    private static function getOneEntryWithFields( $entry, $fields, $sessionUserId, $showFeedback )
    {
        $data = array();

        if ( in_array( 'id', $fields ) ) {
            $data['id'] = $entry->entry_id;
        }

        if ( in_array( 'user', $fields ) ) {
            $data['user']['userId'] = $entry->entry_user_id;
            $data['user'][ 'userName'] = $entry->User->user_name;
        }

        if ( in_array( "userName", $fields ) ) {
            $data[ 'user' ] = ResponseHelper::oneUser( $entry->entry_user_id, $sessionUserId );
        }

        if( in_array( "category", $fields ) ) {
            $data[ 'category' ] = $entry->category->category_name;
        }

        if( in_array( "type", $fields ) )
        {
            $data[ 'type' ] = $entry->entry_type;
        }

        if( in_array( "name", $fields ) )
        {
            $data[ 'name' ] = $entry->entry_name;
        }

        if( in_array( "description", $fields ) )
        {
            $data[ 'description' ] = $entry->entry_description;
        }

        if( in_array( "created", $fields ) )
        {
            $data[ 'created' ] = $entry->entry_created_date;
        }

        if( in_array( "modified", $fields ) )
        {
            $data[ 'modified' ] = $entry->entry_modified_date;
        }

        if( in_array( "tags", $fields ) )
        {
            $data[ 'tags' ] = array();
            foreach( $entry->entryTag as $tag )
            {
                $data[ 'tags' ][] = \Tag::find( $tag->entry_tag_tag_id )->tag_name;
            }
        }

        if( in_array( "entryFiles", $fields ) )
        {
            $data[ 'entryFiles' ] = array();
            if( self::isEntryFilesValid( $entry, $entry->file ) )
            {
                return false;
            }

            foreach( $entry->file as $file )
            {
                $data['entryFiles'][] = self::entryFile( $file );
            }
            $data['videoThumb'] = self::entryThumb( $entry, $entry->file );

            $entryVotes = self::getEntryVotes( $entry->entry_id );
            $data[ 'upVotes' ] = $entryVotes->votes_up;
            $data[ 'downVotes' ] = $entryVotes->votes_down;

            if( in_array( "rank", $fields ) )
            {
                $data[ 'rank' ] = $entry->entry_rank;
            }

            if( in_array( "language", $fields ) )
            {
                $data[ 'language' ] = $entry->entry_language;
            }

            if( $entry->entry_deleted )
            {
                $data[ 'deleted' ] = true;
            }
            else
            {
                $data[ 'deleted' ] = false;
            }

            return $data;
        }
    }


    private static function getEntries( $entries, $sessionUserId, $showFeedback, array $fields = NULL )
    {
        $data = array();

        foreach( $entries as $entry ) {

            $entryData = self::getOneEntry( $entry, $sessionUserId, $showFeedback, $fields );

            if( empty( $entryData ) OR (count( $entryData ) < 5) ) continue;

            $data[] = $entryData;
        }

        return $data;
    }


    public static function getForIndex( $entries, $sessionUserId, $showFeedback, array $params = array() )
    {
        self::prepareForEntries( $entries );

        $totalCount = isset( $params['totalCount'] ) ? $params['totalCount'] : 0;
        $userId = isset( $params['userId'] ) ? $params['userId'] : null;

        if ( $totalCount == 0 ) {
            if( $userId ) {
                return EntriesResponseHelper::onlyUser( $userId, $sessionUserId );
            } else {
                return EntriesResponseHelper::noEntries();
            }
        }

        $data = array();

        $fields = isset( $params['fields'] ) ? $params['fields'] : null;

        $entriesData = self::getEntries( $entries, $sessionUserId, $showFeedback, $fields );

        foreach( $entriesData as $entryData ) {

            $data['entries'][]['entry'] = $entryData;
        }

        if ( $userId ) {

            $starredBy = ResponseHelper::getFollowers( $params['userId'] );
            $data['starredBy'] = $starredBy;
            $data['fans'] = count($starredBy);
        }

        if ( isset( $params['debug'] ) AND $params['debug'] !== false )
            $data['debug'] = $params['debug'];

        if( isset( $params['totalCount'] ) ) {
            $data = self::getPagerLinks( $params['totalCount'], $params, $data );
        }

        if ( ! empty( $params['errors'] ) ) {
            $data['errors'] = $params['errors'];
        }

        $data['timestamp'] = time()*1000;

        $statusCode = 200;

        return array( 'code' => $statusCode, 'data' => $data );
    }


    public static function getForMix( $entries, $sessionUserId, $showFeedback, array $params = array() )
    {
        self::prepareForEntries( $entries );

        $totalCount = isset( $params['totalCount'] ) ? $params['totalCount'] : 0;
        $userId = isset( $params['userId'] ) ? $params['userId'] : null;

        if ( $totalCount == 0 ) {
            if( $userId ) {
                return EntriesResponseHelper::onlyUser( $userId, $sessionUserId );
            } else {
                return EntriesResponseHelper::noEntries();
            }
        }

        $data = array();

        $fields = isset( $params['fields'] ) ? $params['fields'] : null;

        foreach( $entries as $entry ) {

            $entryData = self::getOneEntry( $entry, $sessionUserId, $showFeedback, $fields );

            if ( empty( $entryData ) ) continue;

            if ($entry->entry_category_id == 7)
            {
                $votes = \DB::table('votes')->where('vote_user_id', '=', $sessionUserId)->where('vote_entry_id', '=', $entry->entry_id)->where('vote_deleted', '=', '0')->orderBy( 'vote_id','desc')->get();
                $isVotedByYou= 0;
                foreach( $votes as $v )
                {
                    if($v->vote_up == 0)
                    {
                        $isVotedByYou = 0;
                    }
                    else
                    {
                        $isVotedByYou = 1;
                    }
                }
                $entryData[ 'isVotedByYou' ] = $isVotedByYou;
            }

            $data['entries'][]['entry'] = $entryData;
        }

        if ( $userId ) {

            $aj = \User::find( $userId );
            $starredBy = array();
            $data['starredBy'] = $starredBy;
            $data['fans'] = count( $aj->StarredBy ); // @fixme it counts and deleted stars
        }

        if ( isset( $params['debug'] ) AND $params['debug'] !== false )
            $data['debug'] = $params['debug'];

        if( isset( $params['totalCount'] ) ) {
            $data = self::getPagerLinks( $params['totalCount'], $params, $data );
        }

        if ( !empty( $params['errors'] ) ) {
            $data['errors'] = $params['errors'];
        }

        $statusCode = 200;

        return array( 'code' => $statusCode, 'data' => $data );
    }


    private static function prepareForEntries( $entries, $sessionUserId = null )
    {
        $userIds = array();
        foreach( $entries as $entry ) {
            $userIds[ $entry->entry_user_id ] = $entry->entry_user_id;
        }
        UserHelper::prepareUsers( $userIds, array('stars', 'votes') );

        if( $sessionUserId ) {
            UserHelper::prepareStarFlagsInfo( $userIds, $sessionUserId );
        }

        //@todo get 'Categories', 'Comments' , 'Views', 'Tags', 'Files', 'Votes' for all entries
        return;
    }


    private static function getPagerLinks( $count, $params, array $data = array() )
    {
        if ( empty( $params['limit'] ) OR empty( $params['page'] ) ) return $data;

        $limit = $params['limit'];
        $page = $params['page'];

        if ($page > 1) {
            $data['previous'] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
        }

        if ($count > ( $limit * $page ) ) {
            $data['next'] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
        }

        return $data;
    }

}
