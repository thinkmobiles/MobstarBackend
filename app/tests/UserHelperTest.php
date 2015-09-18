<?php
use MobStar\UserHelper;

class UserHelperTest extends TestCase
{

    private $socialUsers = array(
        'facebook' => array(
            1975 => array(
                'name' => 'Naldinho Novato',
                'display_name' => 'Naldinho Novato',
                'full_name' => 'Naldinho'
            ),
            1972 => array(
                'name' => 'Romain Close',
                'display_name' => 'Romain Close',
                'full_name' => 'Romain'
            )
        ),
        'google' => array(
            790 => array(
                'name' => 'Kwstantinos',
                'display_name' => 'Kwstantinos Neratzoulis',
                'full_name' => 'Kwstantinos Neratzoulis'
            ),
            789 => array(
                'name' => 'Shayla Dixon',
                'display_name' => 'Shayla',
                'full_name' => 'Shayla'
            )
        )
    );


    private $socialUserNames = array(
        4902 => array(
            'facebook' => array(
                'name' => 'Naldinho Novato',
                'display_name' => 'Naldinho Novato',
                'full_name' => 'Naldinho'
            ),
        ),
        4897 => array(
            'facebook' => array(
                'name' => 'Romain Close',
                'display_name' => 'Romain Close',
                'full_name' => 'Romain'
            ),
        ),
        4876 => array(
            'google' => array(
                'name' => 'Shayla Dixon',
                'display_name' => 'Shayla',
                'full_name' => 'Shayla'
            ),
        ),
        4887 => array(
            'google' => array(
                'name' => 'Kwstantinos',
                'display_name' => 'Kwstantinos Neratzoulis',
                'full_name' => 'Kwstantinos Neratzoulis'
            ),
        ),
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
        440 => array(
            'my' => array( 'up' => 1, 'down' => 1 ),
            'me' => array( 'up' => 0, 'down' => 0 ),
            'user_id' => 440,
        ),
        462 => array(
            'my' => array( 'up' => 7, 'down' => 2 ),
            'me' => array( 'up' => 39, 'down' => 51 ),
            'user_id' => 462,
        ),
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
        $this->assertEquals($etalon['name'], $user['name'] );
        $this->assertEquals($etalon['display_name'], $user['display_name'] );
        $this->assertEquals($etalon['full_name'], $user['full_name']);
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


    private function assertUserSocialNames( $userId, $socialNames )
    {
        $this->assertArrayHasKey($userId, $this->socialUserNames, 'not etalon google user');

        $this->assertNotEmpty( $socialNames );
        $this->assertArrayHasKey( $userId, $socialNames );

        $this->assertEquals( $this->socialUserNames[ $userId ], $socialNames[ $userId ] );
    }


    private function checkUserBasicInfo( $userId, $basicInfo )
    {
        $this->assertNotEmpty( $basicInfo );
        $this->assertArrayHasKey( $userId, $basicInfo );

        $this->assertArrayHasKey( $userId, $this->usersToSocial, 'not etalon user' );

        $userNames = $this->socialUsers[ $this->usersToSocial[ $userId ][0] ][ $this->usersToSocial[ $userId ][1] ];

        $this->assertSameNames( $userNames, $basicInfo[ $userId ] );
    }


    private function assertUserVotes( $userId, $votes )
    {
        $this->assertArrayHasKey( $userId, $this->userVotes, 'not etalon user' );

        $this->assertEquals( $this->userVotes[ $userId ], $votes );
    }


    private function assertUserPhone( $userId, $phoneInfo )
    {
        $this->assertArrayHasKey( $userId, $this->userPhones, 'not etalon user' );

        $this->assertEquals( $this->userPhones[ $userId ], $phoneInfo );
    }


    public function testGetUsersInfo_notexistingUser()
    {
        UserHelper::clear();

        $userId = 99999999;

        $usersInfo = UserHelper::getUsersInfo(array(
            $userId
        ));

        $this->assertEmpty($usersInfo);
    }


    public function testGetSocialUserNames_oneUser()
    {
        UserHelper::clear();

        // facebook user
        $userId = 4902;
        $users = UserHelper::getBasicInfo(array($userId) );

        $socialNames = UserHelper::getSocialUserNames( $users );

        $this->assertUserSocialNames( $userId, $socialNames );

        // google user
        $userId = 4887;
        $users = UserHelper::getBasicInfo(array($userId) );

        $socialNames = UserHelper::getSocialUserNames( $users );

        $this->assertUserSocialNames( $userId, $socialNames );

        // one user of facebook and google
        $googleUserId = 4887;
        $facebookUserId = 4902;

        $userIds = array( $googleUserId, $facebookUserId );

        $users = UserHelper::getBasicInfo( $userIds );

        $socialNames = UserHelper::getSocialUserNames( $users );

        foreach( $userIds as $userId ) {
            $this->assertUserSocialNames( $userId, $socialNames );
        }
    }


    public function testGetSocialUserNames_manyUsers()
    {
        UserHelper::clear();

        // facebook users
        $facebookUserIds = array(
            4902,
            4897
        );

        $users = UserHelper::getBasicInfo( $facebookUserIds );
        $socialNames = UserHelper::getSocialUserNames( $users );

        foreach ($facebookUserIds as $facebookUserId) {
            $this->assertUserSocialNames( $facebookUserId, $socialNames );
        }

        // google users
        $googleUserIds = array(
            4876,
            4887
        );

        $users = UserHelper::getBasicInfo( $googleUserIds );
        $socialNames = UserHelper::getSocialUserNames( $users );

        foreach ($googleUserIds as $googleUserId) {
            $this->assertUserSocialNames( $googleUserId, $socialNames );
        }

        // both facebook and google users
        $userIds = array_merge( $facebookUserIds, $googleUserIds );

        $users = UserHelper::getBasicInfo( $userIds );
        $socialNames = UserHelper::getSocialUserNames( $users );

        foreach ($userIds as $userId) {
            $this->assertUserSocialNames( $userId, $socialNames );
        }
    }


    public function testGetUsersInfo_basicInfo_oneUser()
    {
        UserHelper::clear();

        $userId = 4902;

        $usersInfo = UserHelper::getUsersInfo(array(
            $userId
        ));

        $this->checkUserBasicInfo( $userId, $usersInfo );
    }


    public function testGetUsersInfo_basicInfo_manyUsers()
    {
        UserHelper::clear();

        $userIds = array( 4902, 4897, 4876, 4887 );

        $usersInfo = UserHelper::getUsersInfo( $userIds );

        foreach( $userIds as $userId )
        {
            $this->checkUserBasicInfo( $userId, $usersInfo );
        }
    }


    public function testGetStars_oneUser()
    {
        UserHelper::clear();

        $userId = 462;

        $stars = UserHelper::getStars( array( $userId ) );

        $this->assertArrayHasKey( $userId, $stars );
        $userStars = $stars[ $userId ];
        $this->checkUser462Stars( $userStars );
    }


    public function testGetStars_oneUser_withUserInfo()
    {
        UserHelper::clear();

        $userId = 462;

        $stars = UserHelper::getStars( array( $userId ), true );

        $this->assertArrayHasKey( $userId, $stars );
        $userStars = $stars[ $userId ];
        $this->checkUser462Stars_withUserInfo( $userStars );
    }


    public function testGetStars_manyUsers()
    {
        UserHelper::clear();

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
        UserHelper::clear();

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
        UserHelper::clear();

        $userId = 462;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'stars' ) );

        $this->assertArrayHasKey( $userId, $usersInfo );
        $userInfo = $usersInfo[ $userId ];
        $this->assertArrayHasKey( 'stars_info', $userInfo );
        $this->checkUser462Stars( $userInfo['stars_info'] );
    }


    public function testGetUsersInfo_withStars_withUserInfo_oneUser()
    {
        UserHelper::clear();

        $userId = 462;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users' ) );

        $this->assertArrayHasKey( $userId, $usersInfo );
        $userInfo = $usersInfo[ $userId ];
        $this->assertArrayHasKey( 'stars_info', $userInfo );
        $this->checkUser462Stars_withUserInfo( $userInfo['stars_info'] );
    }


    public function testGetUsersInfo_withStars_manyUsers()
    {
        UserHelper::clear();

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
        UserHelper::clear();

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
        UserHelper::clear();

        $userId = 440;

        $votes = UserHelper::getVotes( array( $userId ) );

        $this->assertNotEmpty( $votes );
        $this->assertArrayHasKey( $userId, $votes );
        $this->assertUserVotes( $userId, $votes[ $userId ] );
    }


    public function testGetVotes_manyUsers()
    {
        UserHelper::clear();

        $userIds = array( 440, 462 );

        $votes = UserHelper::getVotes( $userIds );

        $this->assertNotEmpty( $votes );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $votes );
            $this->assertUserVotes( $userId, $votes[ $userId ] );
        }
    }


    public function testGetUsersInfo_withVotes_oneUser()
    {
        UserHelper::clear();

        $userId = 440;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'votes' ) );

        $this->assertNotEmpty( $usersInfo );
        $this->assertArrayHasKey( $userId, $usersInfo );
        $this->assertArrayHasKey( 'votes', $usersInfo[ $userId ] );
        $this->assertUserVotes( $userId, $usersInfo[ $userId ]['votes'] );
    }


    public function testGetUserInfo_withVotes_manyUsers()
    {
        UserHelper::clear();

        $userIds = array( 440, 462 );

        $usersInfo = UserHelper::getUsersInfo( $userIds, array( 'votes' ) );

        $this->assertNotEmpty( $usersInfo );
        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $usersInfo );
            $this->assertArrayHasKey( 'votes', $usersInfo[ $userId ] );
            $this->assertUserVotes( $userId, $usersInfo[ $userId ]['votes'] );
        }
    }


    public function testGetPhones_oneUser()
    {
        UserHelper::clear();

        $userId = 462;

        $phones = UserHelper::getPhones( array( $userId ) );

        $this->assertNotEmpty( $phones );
        $this->assertArrayHasKey( $userId, $phones );
        $this->assertUserPhone( $userId, $phones[ $userId ] );
    }


    public function testGetPhones_manyUsers()
    {
        UserHelper::clear();

        $userIds = array( 462, 692 );

        $phones = UserHelper::getPhones( $userIds );

        $this->assertNotEmpty( $phones );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $phones );
            $this->assertUserPhone( $userId, $phones[ $userId ] );
        }
    }


    public function testGetUsersInfo_withPhones_oneUser()
    {
        UserHelper::clear();

        $userId = 692;

        $usersInfo = UserHelper::getUsersInfo( array( $userId ), array( 'phones' ) );

        $this->assertNotEmpty( $usersInfo );
        $this->assertArrayHasKey( $userId, $usersInfo );
        $this->assertArrayHasKey( 'phone_info', $usersInfo[ $userId ] );
        $this->assertUserPhone( $userId, $usersInfo[ $userId ]['phone_info'] );
    }


    public function testGetUsersInfo_withPhones_manyUsers()
    {
        UserHelper::clear();

        $userIds = array( 462, 692 );

        $usersInfo = UserHelper::getUsersInfo( $userIds, array( 'phones' ) );

        $this->assertNotEmpty( $usersInfo );

        foreach( $userIds as $userId ) {
            $this->assertArrayHasKey( $userId, $usersInfo );
            $this->assertArrayHasKey( 'phone_info', $usersInfo[ $userId ] );
            $this->assertUserPhone( $userId, $usersInfo[ $userId ]['phone_info'] );
        }
    }


    public function testPrepareUsers_oneUser()
    {
        UserHelper::clear();

        $userId = 440;

        UserHelper::prepareUsers( array( $userId ), array( 'stars.users', 'votes' ) );
    }


    public function testPrepareUsers_manyUser()
    {
        UserHelper::clear();

        $user1 = 440;
        $user2 = 462;

        UserHelper::prepareUsers( array( $user1, $user2 ), array( 'stars.users' ) );
    }

}
