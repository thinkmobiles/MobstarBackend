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


    private $userVotes = array(
        440 => array( 'up' => 1, 'down' => 1 ),
        462 => array( 'up' => 7, 'down' => 2 ),
    );


    private $userPhones = array(
        462 => array(
            'user_id' => 462,
            'number' => '2549792724',
            'country' => 1,
            'verification_code' => 4820,
            'verified' => 1
        ),
        692 => array(
            'user_id' => 692,
            'number' => '7878060124',
            'country' => 91,
            'verification_code' => 4683,
            'verified' => 0
        ),
    );


    private function assertSameNames($etalon, $user)
    {
        $this->assertEquals($etalon['user_name'], $user['user_name'] );
        $this->assertEquals($etalon['user_display_name'], $user['user_display_name'] );
        $this->assertEquals($etalon['user_full_name'], $user['user_full_name']);
    }


    private function checkUser462Stars( $stars )
    {
        $userId = 462;
        $this->assertArrayHasKey( 'my', $stars );
        $this->assertArrayHasKey( 'me', $stars );
        $this->assertCount( 2, $stars['my'] );
        $this->assertCount( 24, $stars['me'] );
        // if order is Ok, first row must be user own star
        $this->assertEquals( $userId, $stars['my'][0]['star_user_id'] );
        $this->assertEquals( $userId, $stars['me'][8]['star_user_id'] );
    }


    private function checkUser462Stars_withUserInfo( $stars )
    {
        $userId = 462;
        $this->checkUser462Stars( $stars );
        $this->assertArrayHasKey( 'user_info', $stars['my'][0] );
        $this->assertArrayHasKey( 'user_info', $stars['me'][8] );
        $this->assertNotEmpty( $stars['my'][0]['user_info'] );
        $this->assertNotEmpty( $stars['me'][8]['user_info'] );
        $this->assertEquals( $userId, $stars['my'][0]['user_info']['user_id'] );
        $this->assertEquals( $userId, $stars['me'][8]['user_info']['user_id'] );
    }


    private function checkUser440Stars( $stars )
    {
        $this->assertArrayHasKey( 'my', $stars );
        $this->assertArrayHasKey( 'me', $stars );
        $this->assertCount( 2, $stars['my'] );
        $this->assertCount( 0, $stars['me'] );
    }


    private function checkUser440Stars_withUserInfo( $stars )
    {
        $this->checkUser440Stars( $stars );
        $this->assertNotEmpty( $stars['my'][0]['user_info'] );
        $this->assertEquals( 450, $stars['my'][0]['user_info']['user_id'] );
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


    private function checkUserVotes( $userId, $votes )
    {
        $this->assertArrayHasKey( $userId, $this->userVotes, 'not etalon user' );

        $this->assertArrayHasKey( 'up', $votes );
        $this->assertArrayHasKey( 'down', $votes );
        $this->assertEquals( $this->userVotes[ $userId ]['up'], $votes['up'] );
        $this->assertEquals( $this->userVotes[ $userId ]['down'], $votes['down'] );
    }


    private function checkUserPhone( $userId, $phoneInfo )
    {
        $this->assertArrayHasKey( $userId, $this->userPhones, 'not etalon user' );

        $this->assertEquals( $this->userPhones[ $userId ], $phoneInfo );
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


    public function testGetStars_oneUser()
    {
        $userId = 462;

        $stars = UserHelper::getStars( array( $userId ) );

        $this->assertArrayHasKey( $userId, $stars );
        $userStars = $stars[ $userId ];
        $this->checkUser462Stars( $userStars );
    }


    public function testGetStars_oneUser_withUserInfo()
    {
        $userId = 462;

        $stars = UserHelper::getStars( array( $userId ), true );

        $this->assertArrayHasKey( $userId, $stars );
        $userStars = $stars[ $userId ];
        $this->checkUser462Stars_withUserInfo( $userStars );
    }


    public function testGetStars_manyUsers()
    {
        $user1 = 462;
        $user2 = 440;

        $stars = UserHelper::getStars( array( $user1, $user2 ) );

        // check first user
        $this->assertArrayHasKey( $user1, $stars );
        $user1Stars = $stars[ $user1 ];
        $this->checkUser462Stars($user1Stars);

        // check second user
        $this->assertArrayHasKey( $user2, $stars );
        $user2Stars = $stars[ $user2 ];
        $this->checkUser440Stars($user2Stars);
    }


    public function testGetStars_manyUsers_withUserInfo()
    {
        $user1 = 462;
        $user2 = 440;

        $stars = UserHelper::getStars( array( $user1, $user2 ), true );

        // check first user
        $this->assertArrayHasKey( $user1, $stars );
        $user1Stars = $stars[ $user1 ];
        $this->checkUser462Stars_withUserInfo($user1Stars);

        // check second user
        $this->assertArrayHasKey( $user2, $stars );
        $user2Stars = $stars[ $user2 ];
        $this->checkUser440Stars_withUserInfo($user2Stars);
    }


    public function testGetUsersInfo_withStars_oneUser()
    {
        $userId = 462;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'stars' ) );

        $this->assertArrayHasKey( $userId, $usersInfo );
        $userInfo = $usersInfo[ $userId ];
        $this->assertArrayHasKey( 'stars_info', $userInfo );
        $this->checkUser462Stars( $userInfo['stars_info'] );
    }


    public function testGetUsersInfo_withStars_withUserInfo_oneUser()
    {
        $userId = 462;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users' ) );

        $this->assertArrayHasKey( $userId, $usersInfo );
        $userInfo = $usersInfo[ $userId ];
        $this->assertArrayHasKey( 'stars_info', $userInfo );
        $this->checkUser462Stars_withUserInfo( $userInfo['stars_info'] );
    }


    public function testGetUsersInfo_withStars_manyUsers()
    {
        $user1 = 462;
        $user2 = 440;

        $usersInfo = UserHelper::getUsersInfo( array( $user1, $user2 ), array( 'stars' ) );

        $this->assertArrayHasKey( $user1, $usersInfo );
        $this->assertArrayHasKey( $user2, $usersInfo );
        $this->assertArrayHasKey( 'stars_info', $usersInfo[ $user1 ] );
        $this->assertArrayHasKey( 'stars_info', $usersInfo[ $user2 ] );
        $this->checkUser462Stars( $usersInfo[ $user1 ]['stars_info'] );
        $this->checkUser440Stars( $usersInfo[ $user2 ]['stars_info'] );
    }


    public function testGetUsersInfo_withStars_withUserInfo_manyUsers()
    {
        $user1 = 462;
        $user2 = 440;

        $usersInfo = UserHelper::getUsersInfo( array( $user1, $user2 ), array( 'stars.users' ) );

        $this->assertArrayHasKey( $user1, $usersInfo );
        $this->assertArrayHasKey( $user2, $usersInfo );
        $this->assertArrayHasKey( 'stars_info', $usersInfo[ $user1 ] );
        $this->assertArrayHasKey( 'stars_info', $usersInfo[ $user2 ] );
        $this->checkUser462Stars_withUserInfo( $usersInfo[ $user1 ]['stars_info'] );
        $this->checkUser440Stars_withUserInfo( $usersInfo[ $user2 ]['stars_info'] );
    }


    public function testGetVotes_oneUser()
    {
        $userId = 440;

        $votes = UserHelper::getVotes( array( $userId ) );

        $this->assertNotEmpty( $votes );
        $this->assertArrayHasKey( $userId, $votes );
        $this->checkUserVotes( $userId, $votes[ $userId ] );
    }


    public function testGetVotes_manyUsers()
    {
        $userIds = array( 440, 462 );

        $votes = UserHelper::getVotes( $userIds );

        $this->assertNotEmpty( $votes );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $votes );
            $this->checkUserVotes( $userId, $votes[ $userId ] );
        }
    }


    public function testGetUsersInfo_withVotes_oneUser()
    {
        $userId = 440;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'votes' ) );

        $this->assertNotEmpty( $usersInfo );
        $this->assertArrayHasKey( $userId, $usersInfo );
        $this->assertArrayHasKey( 'votes', $usersInfo[ $userId ] );
        $this->checkUserVotes( $userId, $usersInfo[ $userId ]['votes'] );
    }


    public function testGetUserInfo_withVotes_manyUsers()
    {
        $userIds = array( 440, 462 );

        $usersInfo = UserHelper::getUsersInfo( $userIds, array( 'votes' ) );

        $this->assertNotEmpty( $usersInfo );
        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $usersInfo );
            $this->assertArrayHasKey( 'votes', $usersInfo[ $userId ] );
            $this->checkUserVotes( $userId, $usersInfo[ $userId ]['votes'] );
        }
    }


    public function testGetPhones_oneUser()
    {
        $userId = 462;

        $phones = UserHelper::getPhones( array( $userId ) );

        $this->assertNotEmpty( $phones );
        $this->assertArrayHasKey( $userId, $phones );
        $this->checkUserPhone( $userId, $phones[ $userId ] );
    }


    public function testGetPhones_manyUsers()
    {
        $userIds = array( 462, 692 );

        $phones = UserHelper::getPhones( $userIds );

        $this->assertNotEmpty( $phones );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $phones );
            $this->checkUserPhone( $userId, $phones[ $userId ] );
        }
    }


    public function testGetUsersInfo_withPhones_oneUser()
    {
        $userId = 692;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'phones' ) );

        $this->assertNotEmpty( $usersInfo );
        $this->assertArrayHasKey( $userId, $usersInfo );
        $this->assertArrayHasKey( 'phone_info', $usersInfo[ $userId ] );
        $this->checkUserPhone( $userId, $usersInfo[ $userId ]['phone_info'] );
    }


    public function testGetUsersInfo_withPhones_manyUsers()
    {
        $userIds = array( 462, 692 );

        $usersInfo = UserHelper::getUsersInfo( $userIds, array( 'phones' ) );

        $this->assertNotEmpty( $usersInfo );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $usersInfo );
            $this->assertArrayHasKey( 'phone_info', $usersInfo[ $userId ] );
            $this->checkUserPhone( $userId, $usersInfo[ $userId ]['phone_info'] );
        }
    }


    public function testAddStarNames_oneUser()
    {
        $userId = 462;

        // first get user info
        $usersInfo = UserHelper::getUsersInfo( array( $userId ) );

        $usersInfo = UserHelper::addStarNames( $usersInfo );

        $this->assertNotEmpty( $usersInfo );

        $this->assertArrayHasKey( $userId, $usersInfo );
        $userInfo = $usersInfo[ $userId ];
        $this->assertArrayHasKey( 'stars_info', $userInfo );
        $this->checkUser462Stars_withUserInfo( $userInfo['stars_info'] );
    }


    public function testAddStarNames_manyUsers()
    {
        $user1 = 440;
        $user2 = 462;

        // first get user info
        $usersInfo = UserHelper::getUsersInfo( array( $user1, $user2 ) );

        $usersInfo = UserHelper::addStarNames( $usersInfo );

        $this->assertNotEmpty( $usersInfo );

        // check first user
        $this->assertArrayHasKey( $user1, $usersInfo );
        $user1Info = $usersInfo[ $user1 ];
        $this->assertArrayHasKey( 'stars_info', $user1Info );
        $this->checkUser440Stars_withUserInfo($user1Info['stars_info']);

        // check second user
        $this->assertArrayHasKey( $user2, $usersInfo );
        $user2Info = $usersInfo[ $user2 ];
        $this->assertArrayHasKey( 'stars_info', $user2Info );
        $this->checkUser462Stars_withUserInfo($user2Info['stars_info']);

    }
}
