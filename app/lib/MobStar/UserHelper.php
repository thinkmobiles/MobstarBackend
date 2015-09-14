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
}
