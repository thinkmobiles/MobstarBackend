<?php

class GetOldData extends TestCase
{

    private static $data_dir = '/data/old_version';


    public function testMake_getUserProfile()
    {

        $fileName = __DIR__ . self::$data_dir.'/getUserProfile.txt';

        $testParams = array(
            array( 4902, 1, 'M5OQC3p0qNJaasS41CzYaiptd7SvEtLrxhyLQl5J' ),
            array( 4897, 0, 'MxFGh7T15Z6opKy76ErdZpUXw3C37NYY7gWMn3Zl' ),
            array( 4876, 1, 'VfnJ69dTzBMSdfSaeTkXHUwD4oW4jBQDLAarBeHg' ),
            array( 4887, 0, 'joaVujINzgmJVbMHukeHOWiVVDMX8G3JnYCjRvYT' ),
            array( 440, 1, 'lEZZ7fuPynuAG5V4wGZFQv0bmxUaVJsBoP3Ki9f4' ),
            array( 462, 0, '7cbQQvM3GSvNEmrIrF2J95iosAbeYqzfktccuz0S' ),
            array( 716, 1, '6Ad0iZydDUUj4lmz9HQEYtWOXrTqLebWxnTHrbDL' ),
        );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        $results = array();

        foreach( $testParams as $row ) {
            $user = \User::findOrFail( $row[0] );
            $session = $token->get_session( $row[2] );
            $normal = $row[1];

            $userProfile = getUserProfile( $user, $session, $normal );

            $results[] = array(
                'userId' => $row[0],
                'token' => $row[2],
                'normal' => $row[1],
                'data' => $userProfile,
            );
        }
        file_put_contents(
            $fileName,
            serialize( $results )
        );
    }


    public function testMake_userDetails()
    {
        $fileName = __DIR__ . self::$data_dir.'/userDetails.txt';

        $userIds = array( 4902, 4897, 4876, 4887, 440, 462, 716 );

        $results = array();

        foreach( $userIds as $userId ) {
            $user = \User::findOrFail( $userId );

            $userDetails = userDetails( $user );

            $results[] = array(
                'userId' => $userId,
                'data' => $userDetails,
            );
        }
        file_put_contents(
            $fileName,
            serialize( $results )
        );
    }


    public function testMake_getusernamebyid()
    {
        $fileName = __DIR__ . self::$data_dir.'/getusernamebyid.txt';

        $userIds = array( 4902, 4897, 4876, 4887, 440, 462, 716 );

        $results = array();

        foreach( $userIds as $userId ) {

            $userNames = getusernamebyid( $userId );

            $results[] = array(
                'userId' => $userId,
                'data' => $userNames,
            );
        }
        file_put_contents(
            $fileName,
            serialize( $results )
        );
    }


    public function testMake_particUser()
    {
        $fileName = __DIR__ . self::$data_dir.'/particUser.txt';

        $testParams = array(
            array( 4902, 'M5OQC3p0qNJaasS41CzYaiptd7SvEtLrxhyLQl5J', 1 ),
            array( 4897, 'MxFGh7T15Z6opKy76ErdZpUXw3C37NYY7gWMn3Zl', 0 ),
            array( 4876, 'VfnJ69dTzBMSdfSaeTkXHUwD4oW4jBQDLAarBeHg', 1 ),
            array( 4887, 'joaVujINzgmJVbMHukeHOWiVVDMX8G3JnYCjRvYT', 0 ),
            array( 440, 'lEZZ7fuPynuAG5V4wGZFQv0bmxUaVJsBoP3Ki9f4', 1 ),
            array( 462, '7cbQQvM3GSvNEmrIrF2J95iosAbeYqzfktccuz0S', 0 ),
            array( 716, '6Ad0iZydDUUj4lmz9HQEYtWOXrTqLebWxnTHrbDL', 1 ),
        );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        $results = array();

        foreach( $testParams as $params ) {

            $user = \User::findOrFail( $params[0] );
            $session = $token->get_session( $params[1] );
            $includeStars = $params[2];

            $data = particUser( $user, $session, $includeStars );

            $results[] = array(
                'userId' => $params[0],
                'token' => $params[1],
                'includeStars' => $params[2],
                'data' => $data,
            );
        }
        file_put_contents(
            $fileName,
            serialize( $results )
        );
    }


    public function testMake_oneUser()
    {
        $fileName = __DIR__ . self::$data_dir.'/oneUser.txt';

        $testParams = array(
            array( 4902, 'M5OQC3p0qNJaasS41CzYaiptd7SvEtLrxhyLQl5J', 1, 1 ),
            array( 4897, 'MxFGh7T15Z6opKy76ErdZpUXw3C37NYY7gWMn3Zl', 0, 1 ),
            array( 4876, 'VfnJ69dTzBMSdfSaeTkXHUwD4oW4jBQDLAarBeHg', 1, 0 ),
            array( 4887, 'joaVujINzgmJVbMHukeHOWiVVDMX8G3JnYCjRvYT', 1, 0 ),
            array( 440, 'lEZZ7fuPynuAG5V4wGZFQv0bmxUaVJsBoP3Ki9f4', 1, 1 ),
            array( 462, '7cbQQvM3GSvNEmrIrF2J95iosAbeYqzfktccuz0S', 1, 0 ),
            array( 716, '6Ad0iZydDUUj4lmz9HQEYtWOXrTqLebWxnTHrbDL', 1, 0 ),
            array( 436, 'xRKUddX6LHyEgvYspJReBcCAloLGVTvg7IbE73Y2', 0, 0 ),
            // with other session
            array( 4902, 'joaVujINzgmJVbMHukeHOWiVVDMX8G3JnYCjRvYT', 1, 1 ),
            array( 4897, 'VfnJ69dTzBMSdfSaeTkXHUwD4oW4jBQDLAarBeHg', 0, 1 ),
            array( 4876, 'MxFGh7T15Z6opKy76ErdZpUXw3C37NYY7gWMn3Zl', 1, 0 ),
            array( 4887, 'M5OQC3p0qNJaasS41CzYaiptd7SvEtLrxhyLQl5J', 1, 0 ),
            array( 440, 'xRKUddX6LHyEgvYspJReBcCAloLGVTvg7IbE73Y2', 1, 1 ),
            array( 462, '6Ad0iZydDUUj4lmz9HQEYtWOXrTqLebWxnTHrbDL', 0, 0 ),
            array( 716, '7cbQQvM3GSvNEmrIrF2J95iosAbeYqzfktccuz0S', 1, 0 ),
            array( 436, 'lEZZ7fuPynuAG5V4wGZFQv0bmxUaVJsBoP3Ki9f4', 1, 0 ),
        );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        $results = array();

        foreach( $testParams as $params ) {

            $user = \User::findOrFail( $params[0] );
            $session = $token->get_session( $params[1] );
            $includeStars = $params[2];
            $normal = $params[3];

            $data = oneUser( $user, $session, $includeStars, $normal );

            $results[] = array(
                'userId' => $params[0],
                'token' => $params[1],
                'includeStars' => $params[2],
                'normal' => $params[3],
                'data' => $data,
            );
        }
        file_put_contents(
            $fileName,
            serialize( $results )
        );
    }
}
