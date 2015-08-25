<?php


class LoginTest extends TestCase {


  public function deleteUser( $userId )
  {
    // @todo delete using api interface. Right now deleting derectly in database

    $user = User::find( $userId );
    $this->assertNotEmpty( $user );

    if( $user->user_facebook_id ) {
      FacebookUser::find( $user->user_facebook_id )->delete();
    }

    if( $user->user_google_id ) {
      GoogleUser::find( $user->user_google_id )->delete();
    }

    if( $user->user_twitter_id )
    {
      TwitterUser::find( $user->user_twitter_id );
    }

    $user->delete();

  }


  public function testGoogleLoginWithUnicode()
  {
    $googleLoginInfo = array(
      'userId' => '123456789',
      'displayName' => 'Тестовий Користувач Google',
      'userName' => 'Користувач',
      'fullName' => 'Тестовий користувач',
    );

    $response = $this->call( 'POST', '/login/google', $googleLoginInfo );

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'token', $content );
    $this->assertObjectHasAttribute( 'userId', $content );
    $userId = $content->userId;
    $this->assertObjectHasAttribute( 'userDisplayName', $content );
    $this->assertEquals( $googleLoginInfo['displayName'], $content->userDisplayName );

    // remove added user
    $this->deleteUser( $userId );

  }
}
