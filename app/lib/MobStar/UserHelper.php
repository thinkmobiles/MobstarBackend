<?php
namespace MobStar;

use User;
use DB;
use Illuminate\Support\Arr;

class UserHelper
{

    /**
     * Return array of user information, indexed with user id.
     *
     * Basic user info is retuned anyway. Also, next info may be requested (using $fields array):
     *     stars - info about stars, both user stars and starred by.
     *     stars.users - star array contain basic user info about users, who made this star.
     *     votes - count of up and down votes, made by user.
     *
     * @param array $userIds
     *            array of userId, which info to return.
     * @param array $fields
     *            what info to include.
     * @return array: array of user information indexed with user ids. Each element is array, describing an user, ready to jsonify in response
     */
    public static function getUsersInfo( array $userIds, array $fields = array() )
    {
        $users = self::getBasicInfo($userIds);
        if (empty($users))
            return array(); // no users found

        if ( in_array( 'stars.users', $fields ) OR in_array( 'stars', $fields ) )
            $users = self::addStars( $users, in_array( 'stars.users', $fields ) );

        if( in_array( 'votes', $fields ) )
            $users = self::addVotes( $users );

        return $users;
    }


    protected static function getBasicInfo( $userIds )
    {
        $users = User::whereIn('user_id', $userIds)->get();

        if (empty($users))
            return array();

        $users = $users->keyBy('user_id');

        $users = self::fixUserNames($users);

        $users = $users->toArray();

        return $users;
    }


    protected static function fixUserNames( $users )
    {
        $socialIds = array();
        $usersToFix = array();

        // get users which needs name fix
        foreach ($users as $index => $user) {

            if (empty($user->user_name) || empty($user->user_display_name) || empty($user->user_full_name)) {

                $socialInfo = null;

                if ($user->user_facebook_id) {
                    $socialInfo = array( 'facebook', $user->user_facebook_id );
                } elseif ($user->user_google_id) {
                    $socialInfo = array( 'google', $user->user_google_id );
                } elseif ($user->user_twitter_id) {
                    $socialInfo = array( 'twitter', $user->user_twitter_id );
                }

                if ($socialInfo) {
                    $socialIds[ $socialInfo[0] ][] = $socialInfo[1];
                    $usersToFix[ $user->user_id ] = $socialInfo;
                }
            }
        }

        if (empty($usersToFix))
            return $users; // all names are set

        $socialNames = self::getSocialUserNames($socialIds);

        foreach ($usersToFix as $index => $socialInfo) {

            $socialType = $socialInfo[0];
            $socialId = $socialInfo[1];

            $names = isset( $socialNames[ $socialType ][ $socialId ] )
                ? $socialNames[ $socialType ][ $socialId ]
                : null;

            if ($names) {

                $user = &$users[$index];

                if (empty($user->user_name))
                    $user->user_name = $names['user_name'];

                if (empty($user->user_display_name))
                    $user->user_display_name = $names['user_display_name'];

                if (empty($user->user_full_name))
                    $user->user_full_name = $names['user_full_name'];
            }
        }

        return $users;
    }


    public static function getSocialUserNames( $socialIds )
    {
        $socialNames = array(
            'facebook' => array(),
            'google' => array(),
            'twitter' => array()
        );

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
            return $socialNames;

        $result = $query->get();

        if (empty($result))
            return $socialNames;

        foreach ($result as $row) {

            $socialNames[ $row->usertype ][ $row->user_id ] = array(
                'user_name' => $row->user_name,
                'user_display_name' => $row->display_name,
                'user_full_name' => $row->full_name
            );
        }

        return $socialNames;
    }


    private static function addStars( array $users, $includeUsersInfo = false )
    {
        $stars = self::getStars( array_keys( $users ), $includeUsersInfo );

        foreach( $stars as $userId => $starInfo ) {

            $users[ $userId ]['stars_info'] = $starInfo;
        }

        return $users;
    }


    public static function getStars( array $userIds, $includeUsersInfo = false )
    {
        $queryMyStars = DB::table( 'user_stars' )
            ->select(
                'user_star_user_id as user_id',
                'user_star_created_date as star_date',
                DB::Raw("'my' as star_type"),
                'user_star_star_id as star_user_id')
            ->where('user_star_deleted', '=', 0)
            ->whereIn( 'user_star_user_id', $userIds );

        $queryMeStars = DB::table( 'user_stars' )
            ->select(
                'user_star_star_id as user_id',
                'user_star_created_date as star_date',
                DB::Raw("'me' as star_type"),
                'user_star_user_id as star_user_id')
                ->where('user_star_deleted', '=', 0)
                ->whereIn( 'user_star_star_id', $userIds );

        $query = $queryMyStars
            ->union( $queryMeStars );

        // workaround to sord result by star_date
        $sql = $query->toSql().' order by star_date desc';

        $rows = DB::select( $sql, $query->getBindings() );

        $stars = array();
        $starUsers = array();

        foreach( $rows as $row ) {

            $user_id = $row->user_id;
            if( empty( $stars[ $user_id ] ) ) // create an array, describing user stars
                $stars[ $user_id ] = array( 'my' => array(), 'me' => array() );

            $star = array(
                'user_id' => $row->user_id,
                'star_date' => $row->star_date,
                'star_type' => $row->star_type,
                'star_user_id' => $row->star_user_id,
            );
            $stars[ $user_id ][ $row->star_type ][] = &$star;

            $starUsers[ $star['star_user_id'] ][] = &$star;

            unset( $star ); // to keep links correct
        }

        if( $includeUsersInfo AND $starUsers ) { // add basic user info to stars

            $userInfo = self::getUsersInfo( array_keys( $starUsers ) );

            foreach( $starUsers as $userId => $userStars ) {

                foreach( $userStars as &$star ) {

                    $star['user_info'] = isset( $userInfo[ $userId ] ) ? $userInfo[ $userId ] : array();
                }
                unset( $star );
            }
        }

        return $stars;
    }


    private static function addVotes( $users )
    {
        $votes = self::getVotes( array_keys( $users ) );

        foreach( $votes as $userId => $voteInfo ) {
            $users[ $userId ]['votes'] = $voteInfo;
        }

        return $users;
    }


    public static function getVotes( array $userIds )
    {
        $query = DB::table( 'votes')
            ->select(
                'vote_user_id as user_id',
                DB::raw('sum( if( vote_up > 0, 1, 0 ) ) as up'),
                DB::raw('sum( if( vote_down > 0, 1, 0 ) ) as down'))
            ->where( 'vote_deleted', '=', 0 )
            ->whereIn( 'vote_user_id', $userIds )
            ->groupBy( 'vote_user_id' );

        $rows = $query->get();

        $votes = array();

        foreach( $rows as $row ) {

            $votes[ $row->user_id ] = array(
                'user_id' => $row->user_id,
                'up' => $row->up,
                'down' => $row->down,
            );
        }

        return $votes;
    }
}
