<?php

namespace MobStar;

use DB;
use Config;

class SnsHelper
{

    private static $client;

    private static $apps;

    private static $updateTopic;


    public static function sendNotification( $toUserId, $messageText, $messageData )
    {
        if( Config::get('app.disable_sns') ) return;

        self::init();

        // get user devices
        $devices = DB::table( 'users as u' )
            ->leftJoin( 'device_registrations as d', 'u.user_id', '=', 'd.device_registration_user_id' )
            ->where( 'd.device_registration_device_token', '<>', 'mobstar')
            ->where( 'd.device_registration_device_token', '<>', '')
            ->where( 'u.user_id', '=', $toUserId )
            ->get();

        foreach( $devices as $device )
        {
            try
            {
                if( $device->user_deleted ) // user was deleted< remove all registered devices
                {
                    return self::deleteUserDevices( $toUserId );
                }

                $endpointArn = self::getEndpointArnForDevice( $device );

                if( ! $endpointArn ) { // can ot create arn for this device
                    continue;
                }

                $publishData = self::getPublishData($endpointArn, $device, $messageText, $messageData);

                self::send( $publishData );
            }
            catch( \Exception $e )
            {
                error_log( 'can not send sns to device '.$device->device_registration_id.': '.$e->getMessage() );
            }
        }
    }


    public static function sendBroadcast( $message, $messageData )
    {
        if( Config::get('app.disable_sns') ) return;

        self::init();

        $data = self::getBroadcastPublishData( $message, $messageData );

        try {
            $ret = self::$client->publish( $data );

            $ret = $ret->toArray();
        }
        catch( \Exception $e )
        {
            error_log( $e->getMessage() );
        }
    }


    public static function subscribeSession( $session )
    {
        if( Config::get('app.disable_sns') ) return;

        self::init();

        try {

            $subscribeStatus = -1;

            $deviceId = self::getSessionDeviceId( $session );

            if( $deviceId ) {

                $device = DB::table( 'device_registrations' )
                    ->where( 'device_registration_id', '=', $deviceId )
                    ->first();

                if( $device ) {
                    $endpoint = self::getEndpointArnForDevice( $device );

                    $arn = self::subscribeToTopic( self::$updateTopic, $endpoint );

                    $subscribeStatus = $arn ? 1 : -1;
                }
            }
        } catch( \Exception $e )
        {
            error_log( $e->getMessage() );
        }

        DB::table( 'tokens' )->
            where( 'token_id', '=', $session->token_id )
            ->update( array( 'token_is_subscribed' => $subscribeStatus) );
    }


    private static function getBroadcastPublishData( $message, $data )
    {
        $appleData = $data;

        $googleData = $data;

        $prepData = array(
            'TopicArn' => self::$updateTopic,
            'MessageStructure' => 'json',
            'Message' => json_encode( array(
                'default' => $message,
                'APNS' => json_encode( array(
                    'aps' => $appleData,
                )),
                'APNS_SANDBOX' => json_encode( array(
                    'aps' => $appleData,
                )),
                'GCM' => json_encode( array(
                    'data' => $googleData,
                ))
            ))
        );

        return $prepData;
    }


    private static function getSessionDeviceId( $session )
    {
        $deviceId = $session->token_device_registration_id;

        if( $deviceId > 0 ) return $deviceId;

        if( $deviceId == 0 ) { // try to find device for session
            $deviceId = linkDeviceToSession( $session->token_id );
            if( $deviceId > 0 ) return $deviceId;
        }

        return null;  // no device for session
    }


    private static function subscribeToTopic( $topic, $endpoint )
    {
        try{
            $ret = self::$client->subscribe( array(
                'Endpoint' => $endpoint,
                'Protocol' => 'application',
                'TopicArn' => $topic
            ));

            $ret = $ret->toArray();
            return isset( $ret['SubscriptionArn'] ) ? $ret['SubscriptionArn'] : null;

        } catch( \Exception $e )
        {
            return null;
        }
    }


    private static function send( $data )
    {
        // try to send. If not, try to set 'Enabled' attribute to true
        try
        {
            self::$client->publish( $data );
        }
        catch( \Exception $e )
        {
            if( ! $e instanceof \AWS\Common\Exception\AwsExceptionInterface ) throw $e; // don't know how to handle none AWS

            if( $e->getExceptionCode() != 'EndpointDisabled' ) throw $e;

            // try to enable endpoint

            // get endpoint arn
            $endpointArn = isset( $data['TargetArn'] ) ? $data['TargetArn'] : '';
            if( empty( $endpointArn ) )
            {
                throw new \Exception( 'can not get endpoint arn from publish data: '.print_r( $data, true ) );
            }

            self::$client->setEndpointAttributes( array(
                'EndpointArn' =>  $endpointArn,
                'Attributes' => array(
                    'Enabled' => 'true',
                )
            ));

            // try to re-send message
            self::$client->publish( $data );
        }
    }


    private static function init()
    {
        if( self::$client ) return; // already inited

        self::$client = getSNSClient();

        self::$apps = array(
            'apple' => \Config::get( 'app.apple_arn' ),
            '' => \Config::get( 'app.android_arn' ),
        );

        self::$updateTopic = \Config::get( 'app.updateTopic_arn' );
    }


    private static function deleteUserDevices( $userId )
    {
        DB::table( 'device_registrations' )->where( 'device_registration_user_id', '=', $userId )->delete();
        return true;
    }


    private static function getPublishData( $endpointArn, $device, $messageText, $messageData )
    {
        $preparedData = null;

        if( $device->device_registration_device_type == 'apple')
        {
            $APNS = 'APNS';
            if( strpos( $endpointArn, 'APNS_SANDBOX' ) !== false ) {
                $APNS = 'APNS_SANDBOX';
            }

            $data = $messageData;
            $data['sound'] = 'default';
            $data['alert'] = $messageText;

            $preparedData = array(
                'TargetArn' => $endpointArn,
                'MessageStructure' => 'json',
                'Message' => json_encode( array(
                    'default' => $messageText,
                    //'APNS_SANDBOX' => json_encode(array(
                    $APNS => json_encode( array(
                        'aps' => $data
                    )),
                ))
            );
        }
        else
        {
            $data = $messageData;
            $data['message'] = $messageText;

            $preparedData = array(
                'TargetArn' => $endpointArn,
                'MessageStructure' => 'json',
                'Message' => json_encode( array(
                    'default' => $messageText,
                    'GCM' => json_encode( array(
                        'data' => $data
                    ))
                ))
            );
        }
        return $preparedData;
    }


    /**
     *
     * @param DeviceRegistration $device
     * @return string|null
     * @throws Exception|\AWS\Common\Exception\AwsExceptionInterface>
     */
    private static function getEndpointArnForDevice( $device )
    {
        if( ! $device->device_registration_is_valid )
        {
            return null;
        }

        if( ! empty( $device->device_registration_arn ) ) {
            return $device->device_registration_arn;
        }

        // try to register device
        try {
            $arn = self::registerEndpointArnForDevice(
                $device->device_registration_device_token,
                $device->device_registration_device_type
            );

            $device->device_registration_arn = $arn;
            $device->device_registration_is_valid = 1;
            $device->device_registration_send_try_count = 0;

            DB::table( 'device_registrations' )
                ->where( 'device_registration_id', '=', $device->device_registration_id )
                ->update( array(
                    'device_registration_arn' => $arn,
                    'device_registration_is_valid' => 1,
                    'device_registration_send_try_count' => 0
                )
            );

            return $arn;
        }
        catch( \Exception $e )
        {
            if( ! $e instanceof \AWS\Common\Exception\AwsExceptionInterface ) throw $e; // don't know how to handle none AWS

            if( $e->getExceptionCode() != 'InvalidParameter' ) throw $e;

            DB::table( 'device_registrations' )
                ->where( 'device_registration_id', '=', $device->device_registration_id )
                ->update( array(
                    'device_registration_is_valid' => 0,
                )
            );
        }

        return null;
    }


    /**
     *
     * @param unknown $deviceToken
     * @param unknown $deviceType
     * @throws \Exception in case of error
     */
    private static function registerEndpointArnForDevice( $deviceToken, $deviceType )
    {
        if( $deviceType == "apple" )
        {
            $appArn = self::$apps['apple'];
        }
        else
        {
            $appArn = self::$apps[''];
        }

        $ret = self::$client->createPlatformEndpoint( array(
            'PlatformApplicationArn' => $appArn,
            'Token' => $deviceToken,
        ));

        $ret->toArray();

        return $ret['EndpointArn'];
    }
}
