<?php
namespace MobStar;

use DB;

class UserHelper
{

    private static $basicInfo = array();

    private static $socialInfo = array();

    private static $starsInfo = array();

    private static $starNamesReady = array();

    private static $starNamesInfo = array();

    private static $votesInfo = array();

    private static $phonesInfo = array();

    private static $emptyVotes = array(
        'my' => array(
            'up' => 0,
            'down' => 0,
            'up_deleted' => 0,
            'down_deleted' => 0,
        ),
        'me' => array(
            'up' => 0,
            'down' => 0,
            'up_deleted' => 0,
            'down_deleted' => 0,
        ),
    );

    private static $emptyStars = array(
        'my' => array(),
        'me' => array(),
    );

    /**
     * Return array of user information, indexed with user id.
     *
     * Basic user info is retuned anyway. Also, next info may be requested (using $fields array):
     *     stars - info about stars, both user stars and starred by.
     *     stars.users - star array contain basic user info about users, who made this star.
     *     votes - count of up and down votes, made by user.
     *     phones - info about user phones
     *
     * @param array $userIds
     *            array of userId, which info to return.
     * @param array $fields
     *            what info to include.
     * @return array: array of user information indexed with user ids. Each element is array, describing an user, ready to jsonify in response
     */
    public static function prepareUsers( array $userIds, array $fields = array() )
    {
        self::prepareBasicInfo( $userIds );

        if ( in_array( 'stars.users', $fields ) OR in_array( 'stars', $fields ) )
            self::prepareStarsInfo( $userIds, in_array( 'stars.users', $fields ) );

        if( in_array( 'votes', $fields ) )
            self::prepareVotesInfo( $userIds );

        if( in_array( 'phones', $fields ) )
            self::preparePhonesInfo( $userIds );

    }


    public static function prepareBasicInfo( array $userIds )
    {
        $newUserIds = array();

        foreach( $userIds as $userId )
            if (!isset( self::$basicInfo[ $userId ] ) ) $newUserIds[] = $userId;

        if( empty( $newUserIds ) ) return;
        self::verbose( 'BasicInfo', $newUserIds );

        $connection = DB::connection();
        $curFetchMode = $connection->getFetchMode();
        $connection->setFetchMode( \PDO::FETCH_ASSOC );
        $rows = $connection->table('users')->whereIn('user_id', $newUserIds)->get();
        $connection->setFetchMode( $curFetchMode );

        if (empty($rows))
            return array();

        $newUsers = array();
        foreach( $rows as $row ) {
            $newUsers[ $row['user_id'] ] = $row;
        }

        $newUsers = self::fixUserNames($newUsers);

        foreach( $newUsers as $userId => $user )
            self::$basicInfo[ $userId ] = $user;
    }


    private static function prepareSocialNames( array $users )
    {
        $socialIds = array();
        $socialUsers = array();

        foreach( $users as $userId => $user ) {

            if (isset( self::$socialInfo[ $userId ] ) ) continue;

            $socialInfo = null;

            if ($user['user_facebook_id']) {
                $socialInfo = array( 'facebook', $user['user_facebook_id'], $userId );
            } elseif ($user['user_google_id']) {
                $socialInfo = array( 'google', $user['user_google_id'], $userId );
            } elseif ($user['user_twitter_id']) {
                $socialInfo = array( 'twitter', $user['user_twitter_id'], $userId );
            }

            if ($socialInfo) {
                $socialIds[ $socialInfo[0] ][ $socialInfo[2] ] = $socialInfo[1];
                $socialUsers[ $userId ] = array( $socialInfo[0], $socialInfo[1] );
            }
        }

        if ( empty( $socialIds ) ) return;

        self::verbose( 'SocialInfo', array_keys( $socialUsers ) );

        $query = $facebookQuery = $googleQuery = $twitterQuery = null;

        if (! empty($socialIds['facebook'])) {

            $facebookQuery = DB::Table('facebook_users')
                ->select(
                    DB::Raw("'facebook' as usertype"),
                    'facebook_user_id as user_id',
                    'facebook_user_user_name as user_name',
                    'facebook_user_display_name as display_name',
                    'facebook_user_full_name as full_name')
                ->whereIn('facebook_user_id', $socialIds['facebook']
            );
        }

        if (! empty($socialIds['google'])) {

            $googleQuery = DB::Table('google_users')
                ->select(
                    DB::Raw("'google' as usertype"),
                    'google_user_id as user_id',
                    'google_user_user_name as user_name',
                    'google_user_display_name as display_name',
                    'google_user_full_name as full_name')
                ->whereIn('google_user_id', $socialIds['google']
            );
        }

        if (! empty($socialIds['twitter'])) {

            $twitterQuery = DB::Table('twitter_users')
                ->select(
                    DB::Raw("'twitter' as usertype"),
                    'twitter_user_id as user_id',
                    'twitter_user_user_name as user_name',
                    'twitter_user_display_name as display_name',
                    'twitter_user_full_name as full_name')
                ->whereIn('twitter_user_id', $socialIds['twitter']
            );
        }

        if ($facebookQuery) {
            $query = $query ? $query->union($facebookQuery) : $facebookQuery;
        }

        if ($googleQuery) {
            $query = $query ? $query->union($googleQuery) : $googleQuery;
        }

        if ($twitterQuery) {
            $query = $query ? $query->union($twitterQuery) : $twitterQuery;
        }

        if (empty($query))
            return;

        $result = $query->get();

        if (empty($result))
            return;

        $socialNames = array();
        foreach ($result as $row) {

            $socialNames[ $row->usertype ][ $row->user_id ] = array(
                'name' => $row->user_name,
                'display_name' => $row->display_name,
                'full_name' => $row->full_name
            );
        }

        foreach( $socialUsers as $userId => $socialInfo ) {

            if (isset( $socialNames[ $socialInfo[0] ][ $socialInfo[1] ] ) ) {
                self::$socialInfo[ $userId ][ $socialInfo[0] ] = $socialNames[ $socialInfo[0] ][ $socialInfo[1] ];
            }
        }

        return;
    }


    public static function prepareStarsInfo( array $userIds, $includeUsers = false )
    {
        $newUserIds = array();

        foreach( $userIds as $userId )
            if (!isset( self::$starsInfo[ $userId ] ) ) $newUserIds[] = $userId;

        if( empty( $newUserIds ) ) {

            if( $includeUsers )
                self::prepareStarNamesInfo( $userIds );

            return;
        }

        self::verbose( 'StarsInfo', $newUserIds );

        $queryMyStars = DB::table( 'user_stars' )
            ->select(
                'user_star_user_id as user_id',
                'user_star_created_date as star_date',
                DB::Raw("'my' as star_type"),
                'user_star_star_id as star_user_id')
                ->where('user_star_deleted', '=', 0)
                ->whereIn( 'user_star_user_id', $newUserIds );

        $queryMeStars = DB::table( 'user_stars' )
        ->select(
            'user_star_star_id as user_id',
            'user_star_created_date as star_date',
            DB::Raw("'me' as star_type"),
            'user_star_user_id as star_user_id')
            ->where('user_star_deleted', '=', 0)
            ->whereIn( 'user_star_star_id', $newUserIds );

        $query = $queryMyStars
            ->union( $queryMeStars );

        // workaround to sord result by star_date
        $sql = $query->toSql().' order by star_date desc';

        $rows = DB::select( $sql, $query->getBindings() );

        $newStars = array();

        foreach( $rows as $row ) {

            $user_id = $row->user_id;
            if( empty( $newStars[ $user_id ] ) ) // create an array, describing user stars
                $newStars[ $user_id ] = self::$emptyStars;

            $star = array(
                'user_id' => $row->user_id,
                'star_date' => $row->star_date,
                'star_type' => $row->star_type,
                'star_user_id' => $row->star_user_id,
            );
            $newStars[ $user_id ][ $row->star_type ][] = $star;
        }

        foreach( $newStars as $userId => $starInfo )
            self::$starsInfo[ $userId ] = $starInfo;

        // mark other users as users with zero stars
        foreach( $newUserIds as $userId ) {

            if( isset( self::$starsInfo[ $userId ] ) ) continue;

            self::$starsInfo[ $userId ] = self::$emptyStars;
            self::$starsInfo[ $userId ]['user_id'] = $userId;
        }

        if( $includeUsers )
            self::prepareStarNamesInfo( $userIds );
    }


    public static function prepareStarNamesInfo( array $userIds )
    {
        // first add stars
        self::prepareStarsInfo( $userIds );

        $newUserIds = array();

        foreach( $userIds as $userId ) {

            if ( isset( self::$starNamesReady[ $userId ] ) ) continue; // already have star users for this user

            foreach( self::$starsInfo[ $userId ]['my'] as $star_info ) {
                if( !isset( self::$starNamesInfo[ $star_info['star_user_id' ] ] ) )
                    $newUserIds[ $star_info['star_user_id' ] ] = $star_info['star_user_id' ];
            }

            foreach( self::$starsInfo[ $userId ]['me'] as $star_info ) {
                if( !isset( self::$starNamesInfo[ $star_info['star_user_id' ] ] ) )
                    $newUserIds[ $star_info['star_user_id' ] ] = $star_info['star_user_id' ];
            }
        }

        if( empty( $newUserIds ) ) return;

        self::verbose( 'StarNamesInfo', $newUserIds ) ;

        $newStarNames = self::getBasicInfo( $newUserIds );

        foreach( $newStarNames as $userId => $userNames )
            self::$starNamesInfo[ $userId ] = $userNames;

        // update starNamesReady
        foreach( $userIds as $userId )
            self::$starNamesReady[ $userId ] = $userId;
    }


    public static function prepareVotesInfo( array $userIds )
    {
        $newUserIds = array();

        $votes = array();

        foreach( $userIds as $userId ) {
            if (!isset( self::$votesInfo[ $userId ] ) ) {
                $newUserIds[] = $userId;
                $votes[ $userId ] = self::$emptyVotes;
                $votes[ $userId ]['user_id'] = $userId;
            }
        }

        if( empty( $newUserIds ) ) return;

        self::verbose( 'VotesInfo', $newUserIds );

        // get my votes
        $query = DB::table( 'votes as v')
            ->select(
                'v.vote_user_id as user_id',
                DB::raw( 'if(v.vote_deleted <> 0, 1, 0) as deleted'),
                DB::raw('sum( if( v.vote_up > 0, 1, 0 ) ) as up'),
                DB::raw('sum( if( v.vote_down > 0, 1, 0 ) ) as down'))
            ->leftJoin( 'entries as e', 'v.vote_entry_id', '=', 'e.entry_id')
            ->where( 'e.entry_deleted', '=', 0 )
            ->whereNotIn( 'e.entry_category_id', array( 7, 8 ) )
            ->whereIn( 'v.vote_user_id', $newUserIds )
            ->groupBy( 'v.vote_user_id', 'deleted' );

        $rows = $query->get();

        foreach( $rows as $row ) {

            if ( $row->deleted ) {
                $votes[ $row->user_id ]['my']['up_deleted'] = $row->up;
                $votes[ $row->user_id ]['my']['down_deleted'] = $row->down;
            } else {
                $votes[ $row->user_id ]['my']['up'] = $row->up;
                $votes[ $row->user_id ]['my']['down'] = $row->down;
            }
        }

        // get votes for me
        $query = DB::table( 'entries as e')
            ->select(
                'e.entry_user_id as user_id',
                DB::raw('if( v.vote_deleted <> 0, 1, 0) as deleted'),
                DB::raw('sum( if( v.vote_up > 0, 1, 0 ) ) as up'),
                DB::raw('sum( if( v.vote_down > 0, 1, 0 ) ) as down'))
            ->leftJoin( 'votes as v', 'v.vote_entry_id', '=', 'e.entry_id')
            ->where( 'e.entry_deleted', '=', 0 )
            ->whereNotIn( 'e.entry_category_id', array( 7, 8 ) )
//            ->where( 'v.vote_deleted', '=', 0 )
            ->whereIn( 'e.entry_user_id', $newUserIds )
            ->groupBy( 'e.entry_user_id', 'deleted' );

        $rows = $query->get();

        foreach( $rows as $row ) {

            if( $row->deleted ) {
                $votes[ $row->user_id ]['me']['up_deleted'] = $row->up;
                $votes[ $row->user_id ]['me']['down_deleted'] = $row->down;
            } else {
                $votes[ $row->user_id ]['me']['up'] = $row->up;
                $votes[ $row->user_id ]['me']['down'] = $row->down;
            }
        }

        // mark other users as users with zero votes
        foreach( $votes as $userId => $vote  ) {

            self::$votesInfo[ $userId ] = $vote;
        }
    }


    public static function preparePhonesInfo( array $userIds )
    {
        $newUserIds = array();

        foreach( $userIds as $userId )
            if (!isset( self::$phonesInfo[ $userId ] ) ) $newUserIds[] = $userId;

        if( empty( $newUserIds ) ) return;

        self::verbose( 'PhonesInfo', $newUserIds );

        $query = DB::table( 'user_phones' )
            ->whereIn('user_phone_user_id', $newUserIds );

        $rows = $query->get();

        foreach( $rows as $row ) {

            self::$phonesInfo[ $row->user_phone_user_id ] = array(
                'user_id' => $row->user_phone_user_id,
                'number' => $row->user_phone_number,
                'country' => $row->user_phone_country,
                'verification_code' => $row->user_phone_verification_code,
                'verified' => $row->user_phone_verified,
            );
        }

        // mark other users as users without phones
        foreach( $newUserIds as $userId ) {

            if( !isset( self::$phonesInfo[ $userId ] ) )
                self::$phonesInfo[ $userId ] = false;
        }
    }


    public static function getUsersInfo( array $userIds, array $fields = array() )
    {
        $users = self::getBasicInfo($userIds);

        if (empty($users))
            return array(); // no users found

        if ( in_array( 'stars.users', $fields ) OR in_array( 'stars', $fields ) )
            $users = self::addStars( $users, in_array( 'stars.users', $fields ) );

        if( in_array( 'votes', $fields ) )
            $users = self::addVotes( $users );

        if( in_array( 'phones', $fields ) )
            $users = self::addPhones( $users );

        return $users;
    }


    public static function getBasicInfo( $userIds )
    {
        self::prepareBasicInfo( $userIds );

        $users = array();

        foreach( $userIds as $userId ) {

            if ( isset( self::$basicInfo[ $userId ] ) ) {
                $users[ $userId ] = self::$basicInfo[ $userId ];
            } else {
                error_log( 'can not get basic info info for user: '. $userId );
            }
        }

        return $users;
    }


    protected static function fixUserNames( array $users )
    {
        $socialNames = self::getSocialUserNames( $users );

        $emptyNames = array(
            'name' => '',
            'display_name' => '',
            'full_name' => '',
        );

        foreach( $users as $userId => &$user ) {


            $names = $emptyNames;

            if ( isset($socialNames[ $userId ]['facebook']) ) {
                $names = $socialNames[ $userId ]['facebook'];
            } elseif ( isset($socialNames[ $userId ]['google']) ) {
                $names = $socialNames[ $userId ]['google'];
            } elseif ( isset($socialNames[ $userId ]['twitter']) ) {
                $names = $socialNames[ $userId ]['twitter'];
            }

            $user['name'] = $user['user_name'] ? $user['user_name'] : $names['name'];
            $user['display_name'] = $user['user_display_name'] ? $user['user_display_name'] : $names['display_name'];
            $user['full_name'] = $user['user_full_name'] ? $user['user_full_name'] : $names['full_name'];
        }
        unset( $user );

        return $users;
    }


    public static function getSocialUserNames( array $users )
    {
        if ( empty( $users ) )
            return array();

        self::prepareSocialNames( $users );

        $socialNames = array();

        foreach( $users as $userId => $user ) {

            if ( isset( self::$socialInfo[ $userId ] ) ) {
                $socialNames[ $userId ] = self::$socialInfo[ $userId ];
            } else {
                $socialNames[ $userId ] = false;
            }
        }

        return $socialNames;
    }


    private static function addStars( array $users, $includeUsersInfo = false )
    {
        $stars = $includeUsersInfo
            ? self::getStarsWithUsers( array_keys( $users ) )
            : self::getStars( array_keys( $users ) );

        foreach( $stars as $userId => $starsInfo )
            $users[ $userId ]['stars_info'] = $starsInfo;

        return $users;
    }


    public static function getStars( array $userIds, $includeUsersInfo = false )
    {

        if( $includeUsersInfo )
            return self::getStarsWithUsers( $userIds );

        self::prepareStarsInfo( $userIds );

        $stars = array();

        foreach( $userIds as $userId ) {

            if ( isset( self::$starsInfo[ $userId ] ) ) {
                $stars[ $userId ] = self::$starsInfo[ $userId ];
            } else {
                error_log( 'can not get stars info for user: '. $userId );
                $stars[ $userId ] = self::$emptyStars;
                $stars[ $userId ]['user_id'] = $userId;
            }
        }

        return $stars;
    }


    public static function getStarsWithUsers( array $userIds )
    {
        self::prepareStarNamesInfo( $userIds );

        $stars = self::getStars( $userIds );

        foreach( $stars as $userId => &$starsInfo ) {

            foreach( $starsInfo['my'] as &$starInfo ) {

                $starUserId = $starInfo['star_user_id'];

                if ( isset( self::$starNamesInfo[ $starUserId ] ) ) {

                    $starInfo['user_info'] = self::$starNamesInfo[ $starUserId ];
                } else {
                    error_log( 'can not get star user info for user: '.$userId.' star user id: '.$starUserId );
                    $starInfo['user_info'] = false;
                }
            }
            unset( $starInfo );

            foreach( $starsInfo['me'] as &$starInfo ) {

                $starUserId = $starInfo['star_user_id'];

                if ( isset( self::$starNamesInfo[ $starUserId ] ) ) {

                    $starInfo['user_info'] = self::$starNamesInfo[ $starUserId ];
                } else {
                    error_log( 'can not get star user info for user: '.$userId.' star user id: '.$starUserId );
                    $starInfo['user_info'] = false;
                }
            }
            unset( $starInfo );
        }
        unset( $starsInfo );

        return $stars;
    }


    private static function addVotes( array $users )
    {
        $votes = self::getVotes( array_keys( $users ) );

        foreach( $votes as $userId => $voteInfo ) {
            $users[ $userId ]['votes'] = $voteInfo;
        }

        return $users;
    }


    public static function getVotes( array $userIds )
    {
        self::prepareVotesInfo( $userIds );

        $votes = array();

        foreach( $userIds as $userId ) {

            if ( isset( self::$votesInfo[ $userId ] ) ) {
                $votes[ $userId ] = self::$votesInfo[ $userId ];
            } else {
                error_log( 'can not get votes info for user: '. $userId );
                $votes[ $userId ] = self::$emptyVotes;
                $votes[ $userId ]['user_id'] = $userId;
            }
        }

        return $votes;
    }


    private static function addPhones( array $users )
    {
        $phones = self::getPhones( array_keys( $users ) );

        foreach( $phones as $userId => $phoneInfo )
            $users[ $userId ]['phone_info'] = $phoneInfo;

        return $users;
    }


    public static function getPhones( array $userIds )
    {
        self::preparePhonesInfo( $userIds );

        $phones = array();

        foreach( $userIds as $userId ) {

            if ( isset( self::$phonesInfo[ $userId ] ) ) {
                $phones[ $userId ] = self::$phonesInfo[ $userId ];
            } else {
                error_log( 'can not get phones info for user: '. $userId );
                $phones[ $userId ] = false;
            }
        }

        return $phones;
    }


    public static function clear()
    {
        self::$basicInfo = self::$votesInfo = self::$phonesInfo = array();
        self::$starsInfo = self::$starNamesReady = self::$starsInfo = array();
    }


    public static function dump()
    {
        error_log( 'basicInfo: '.print_r( self::$basicInfo, true ) );
        error_log( 'socialInfo: '.print_r( self::$socialInfo, true ) );
        error_log( 'starsInfo: '.print_r( self::$starsInfo, true ) );
        error_log( 'starNamesReady Info: '.print_r( self::$starNamesReady, true ) );
        error_log( 'starNamesInfo: '.print_r( self::$starNamesInfo, true ) );
        error_log( 'votesInfo: '.print_r( self::$votesInfo, true ) );
        error_log( 'phonesInfo: '.print_r( self::$phonesInfo, true ) );
    }


    private static function verbose( $type, $ids )
    {
        //error_log( 'select '.$type.' for '.implode( ', ', $ids ) );
    }
}
