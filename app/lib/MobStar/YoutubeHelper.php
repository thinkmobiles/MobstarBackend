<?php

namespace MobStar;

require_once __DIR__.'/../../../vendor/google-api-php-client-master/src/Google/autoload.php';

use Google_Client;
use Google_Service_YouTube;


class YoutubeHelper
{

    private static $client;


    private static function init()
    {
        if( self::$client ) return; // already inited

        $APIKey = \Config::get( 'app.google_apikey' );
        if( empty( $APIKey ) )
        {
            // @todo may be better to throw an exception ?
            $msg = 'No Google API key in config';
            error_log( $msg );
            die( $msg );
        }

        $client = new Google_Client();
        $client->setDeveloperKey( $APIKey );

        self::$client = new Google_Service_YouTube($client);
    }


    public static function getInfoFromUrl( $url )
    {
        $info = array(
            'id' => null,
            'error' => false
        );
        $urlParts = parse_url( $url );
        if( ! $urlParts )
        {
            $info[ 'error' ] = "Invalid url provided";
            return $info;
        }
        if( $urlParts['host'] != 'www.youtube.com' )
        {
            $info[ 'error' ] = "Not YouTube url provided";
            return $info;
        }

        $urlParams = array();
        parse_str( $urlParts['query'], $urlParams );

        if( empty( $urlParams['v'] ) )
        {
            $info[ 'error' ] = "Wrong YouTube url provided";
            return $info;
        }

        $id = $urlParams[ 'v' ];
        $info['id'] = $id;

        $videoInfo = self::getInfo( $id );

        if( $videoInfo['error'] )
        {
            $info['error'] = $videoInfo['error'];
        }
        else
        {
            $info['duration'] = $videoInfo['duration'];
            $info['access'] = $videoInfo['access'];
        }

        return $info;
    }


    public static function getInfo( $id )
    {
        self::init();

        $res = self::$client->videos->listVideos(
            "contentDetails,status",
            array( 'id' => $id )
        );

        $info = array();

        foreach( $res->items as $item )
        {
            if( $item->id != $id ) continue;
            $info['duration'] = self::getDurationFromString( $item['contentDetails']['duration'] );
            $info['access'] = $item['status']['privacyStatus'];
            $info['id'] = $id;
            $info['error'] = false;
        }

        if( empty( $info ) ) // item not found
        {
            $info = array(
                'id' => $id,
                'error' => 'Youtube video not found'
            );
        }

        return $info;
    }


    public static function getDurationFromString( $str )
    {
        $parts = array();
        if( ! preg_match( '/^\s*PT((\d+)H)?((\d+)M)?((\d+)S)?\s*$/', $str, $parts ) )
        {
            return -1;
        }
        $seconds = 0;
        if( isset( $parts[6] ) ) $seconds += $parts[6];
        if( isset( $parts[4] ) ) $seconds += $parts[4]*60;
        if( isset( $parts[2] ) ) $seconds += $parts[2]*3600;

        return $seconds;
    }
}
