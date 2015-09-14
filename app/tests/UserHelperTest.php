<?php
use MobStar\UserHelper;

class UserHelperTest extends TestCase
{

    private $socialUsers = array(
        'facebook' => array(
            1975 => array(
                'user_name' => 'Naldinho Novato',
                'user_display_name' => 'Naldinho Novato',
                'user_full_name' => 'Naldinho'
            ),
            1972 => array(
                'user_name' => 'Romain Close',
                'user_display_name' => 'Romain Close',
                'user_full_name' => 'Romain'
            )
        ),
        'google' => array(
            790 => array(
                'user_name' => 'Kwstantinos',
                'user_display_name' => 'Kwstantinos Neratzoulis',
                'user_full_name' => 'Kwstantinos Neratzoulis'
            ),
            789 => array(
                'user_name' => 'Shayla Dixon',
                'user_display_name' => 'Shayla',
                'user_full_name' => 'Shayla'
            )
        )
    );


    private $usersToSocial = array(
        4902 => array(
            'facebook',
            1975
        ),
        4897 => array(
            'facebook',
            1972
        ),
        4876 => array(
            'google',
            789
        ),
        4887 => array(
            'google',
            790
        )
    );


    private function assertSameNames($etalon, $user)
    {
        $this->assertEquals($etalon['user_name'], $user['user_name'] );
        $this->assertEquals($etalon['user_display_name'], $user['user_display_name'] );
        $this->assertEquals($etalon['user_full_name'], $user['user_full_name']);
    }


    private function checkGoogleUserNames($googleUserId, $socialNames)
    {
        $this->assertArrayHasKey($googleUserId, $this->socialUsers['google'], 'not etalon google user');

        $this->assertNotEmpty($socialNames);
        $this->assertArrayHasKey('google', $socialNames);
        $this->assertArrayHasKey($googleUserId, $socialNames['google']);

        $this->assertSameNames(
            $this->socialUsers['google'][$googleUserId],
            $socialNames['google'][$googleUserId]
        );
    }


    private function checkFacebookUserNames($facebookUserId, $socialNames)
    {
        $this->assertArrayHasKey($facebookUserId, $this->socialUsers['facebook'], 'not etalon facebook user');

        $this->assertNotEmpty($socialNames);
        $this->assertArrayHasKey('facebook', $socialNames);
        $this->assertArrayHasKey($facebookUserId, $socialNames['facebook']);

        $this->assertSameNames(
            $this->socialUsers['facebook'][$facebookUserId],
            $socialNames['facebook'][$facebookUserId]
        );
    }


    private function checkUserBasicInfo( $userId, $basicInfo )
    {
        $this->assertNotEmpty( $basicInfo );
        $this->assertArrayHasKey( $userId, $basicInfo );

        $this->assertArrayHasKey( $userId, $this->usersToSocial, 'not etalon user' );

        $userNames = $this->socialUsers[ $this->usersToSocial[ $userId ][0] ][ $this->usersToSocial[ $userId ][1] ];

        $this->assertSameNames( $userNames, $basicInfo[ $userId ] );
    }


    public function testGetUsersInfo_notexistingUser()
    {
        $userId = 99999999;

        $usersInfo = UserHelper::getUsersInfo(array(
            $userId
        ));

        $this->assertEmpty($usersInfo);
    }


    public function testGetSocialUserNames_oneUser()
    {
        // facebook user
        $facebookUserId = 1975;

        $socialNames = UserHelper::getSocialUserNames(array(
            'facebook' => array( $facebookUserId )
        ));
        error_log( 'social names: '.print_r( $socialNames, true ) );

        $this->checkFacebookUserNames( $facebookUserId, $socialNames );

        // google user
        $googleUserId = 790;

        $socialNames = UserHelper::getSocialUserNames(array(
            'google' => array( $googleUserId )
        ));

        $this->checkGoogleUserNames( $googleUserId, $socialNames );

        // one user of facebook and google
        $socialNames = UserHelper::getSocialUserNames(array(
            'facebook' => array( $facebookUserId ),
            'google' => array( $googleUserId )
        ));

        $this->checkFacebookUserNames( $facebookUserId, $socialNames );
        $this->checkGoogleUserNames( $googleUserId, $socialNames );
    }


    public function testGetSocialUserNames_manyUsers()
    {

        // facebook users
        $facebookUserIds = array(
            1975,
            1972
        );


        $socialNames = UserHelper::getSocialUserNames(array(
            'facebook' => $facebookUserIds
        ));

        foreach ($facebookUserIds as $facebookUserId) {
            $this->checkFacebookUserNames($facebookUserId, $socialNames);
        }

        // google users
        $googleUserIds = array(
            790,
            789
        );

        $socialNames = UserHelper::getSocialUserNames(array(
            'google' => $googleUserIds
        ));

        foreach ($googleUserIds as $googleUserId) {
            $this->checkGoogleUserNames($googleUserId, $socialNames);
        }

        // both facebook and google users
        $socialNames = UserHelper::getSocialUserNames(array(
            'facebook' => $facebookUserIds,
            'google' => $googleUserIds
        ));

        foreach ($facebookUserIds as $facebookUserId) {
            $this->checkFacebookUserNames($facebookUserId, $socialNames);
        }
        foreach ($googleUserIds as $googleUserId) {
            $this->checkGoogleUserNames($googleUserId, $socialNames);
        }
    }


    public function testGetUsersInfo_basicInfo_oneUser()
    {
        $userId = 4902;

        $usersInfo = UserHelper::getUsersInfo(array(
            $userId
        ));
        error_log( print_r( $usersInfo, true ));

        $this->checkUserBasicInfo( $userId, $usersInfo );
    }


    public function testGetUsersInfo_basicInfo_manyUsers()
    {
        $userIds = array( 4902, 4897, 4876, 4887 );

        $usersInfo = UserHelper::getUsersInfo( $userIds );

        foreach( $userIds as $userId )
        {
            $this->checkUserBasicInfo( $userId, $usersInfo );
        }
    }

}
