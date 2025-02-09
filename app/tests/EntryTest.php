<?php

class EntryTest extends TestCase {


  // check in tokens table
  protected $authInfo = array(
    'tokenId' => '07516258357',
    'userId' => 301,
  );

  protected $users = array(
      'withFilter' => array(
        'tokenId' => 'vXISaFByuOlWyju6z7uu3CyAtjBAnNJzIhYEQWnI',
        'userId' => 310,
      ),
      'noFilter' => array(
          'userId' => 311,
          'tokenId' => '7NQLtJXXEABt3CeJ5FKDp0dph7EAXeOzuhMW4J9r'
      ),
  );


  protected function dumpJSONResponse( $response )
  {
    $data = json_decode( $response->getContent() );
    error_log( print_r( $data, true ) );
  }


  protected function getEntries( $pars = array() )
  {
    $response = $this->call( 'GET', '/entry', $pars, array(), array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] ) );

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );
    $entries = $content->entries;

    return $entries;
  }


  protected function getEntryIds( $entries )
  {
    $ids = array();

    foreach( $entries as $entryObj )
    {
      $ids[] = $entryObj->entry->id;
    }

    return $ids;
  }


  public function testDebug()
  {
    $response = $this->call( 'GET', '/debug' );

    $this->assertTrue( strlen( $response->getContent() ) > 250 );
  }


  public function testGetEntries()
  {

    $response = $this->call( 'GET', '/entry', array(), array(), array( 'HTTP_X-API-TOKEN' => '07516258357' ) );

    $this->assertEquals( 200, $response->getStatusCode() );

    $contentJSON = $response->getContent();

    $this->assertNotEmpty( $contentJSON );

    $content = json_decode( $contentJSON );

    $this->assertINstanceOf( 'stdClass', $content );

    $this->assertObjectHasAttribute( 'entries', $content );
    $this->assertObjectHasAttribute( 'next', $content );

    $entries = $content->entries;
    $this->assertTrue( count( $entries ) == 20 );
  }


  public function testGetEntries_withGeo()
  {
      // set user geo filter
      $user = $this->users['withFilter'];
      // set filter to Africa (1)
      $response = $this->call(
          'POST',
          '/settings/continentFilter',
          array( 'continentFilter' => json_encode( array( 1 ) ) ),
          array(),
          array( 'HTTP_X-API-TOKEN' => $user['tokenId'] )
      );
      $this->assertEquals( 200, $response->getStatusCode() );

      \Config::set( 'app.force_include_all_world', false );

      $response = $this->call(
          'GET',
          '/entry',
          array(),
          array(),
          array( 'HTTP_X-API-TOKEN' => $user['tokenId'] )
      );
      $this->assertEquals( 200, $response->getStatusCode() );

      $content = json_decode( $response->getContent() );
      $this->assertTrue( count( $content->entries ) < 10 );
  }


  public function testProfileEntriesNotAppearOnCategoryListing()
  {
    $entries = $this->getEntries( array(
      'category' => 4, // dance
      'excludeVotes' => true,
      'orderBy' => 'latest',
      'page' => 1,
      'limit' => 20,
    ));

    $this->assertTrue( count($entries) > 0, 'No entries in category' );

    // get categories of entries
    $categories = array();
    foreach( $entries as $entryObj )
    {
      $categories[] = $entryObj->entry->category;
    }

    $this->assertFalse(
      in_array( 'Profile Content', $categories ),
      'Profile entries appear in category listing'
    );
  }


  public function testGetFirstPageOfEntries()
  {
    $response = $this->call(
      'GET',
      '/entry',
      array(
        'excludeVotes' => true,
        'orderBy' => 'latest',
        'page' => 1
      ),
      array(),
      array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 200, $response->getStatusCode() );
  }


  public function testPostEntry()
  {
    $testFilename = __DIR__.'/files/test_movie.mp4';
    $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

    $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );

    $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
      $uploadedFilename,
      'test_file.mp4'
    );

    $response = $this->call( 'POST', '/entry',
      array(
        'category' => 7,
        'type' => 'video',
        'language' => 'english',
        'name' => 'Test',
        'description' => 'Test Video',
      ),
      array( 'file1' => $file ),
      array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 201, $response->getStatusCode() );
  }


  public function testPostEntry_SplitScreen()
  {
      $baseEntryId = 4600;

      $testFilename = __DIR__.'/files/test_movie.mp4';
      $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

      $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );

      $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
          $uploadedFilename,
          'test_file.mp4'
      );

      $response = $this->call( 'POST', '/entry',
          array(
              'category' => 7,
              'type' => 'video',
              'language' => 'english',
              'name' => 'Test',
              'description' => 'Test Video',
              'splitVideoId' => $baseEntryId,
          ),
          array( 'file1' => $file ),
          array( 'HTTP_X-API-TOKEN' => '07516258357' )
      );

      $this->assertEquals( 201, $response->getStatusCode() );

      $content = json_decode( $response->getContent() );
      $this->assertNotEmpty( $content );
      $this->assertObjectHasAttribute( 'entry_id', $content );
      $uploadedEntryId = $content->entry_id;
      $this->assertNotEmpty( $uploadedEntryId );

      // get list of entries
      $entries = $this->getEntries( array( 'orderBy' => 'latest', 'user' => $this->authInfo['userId'] ) );
      $this->assertNotEmpty( $entries );

      foreach( $entries as $entryObj )
      {
          $entry = $entryObj->entry;
          if( $entry->id != $uploadedEntryId ) continue;
          $this->assertObjectHasAtribute( 'splitVideoId', $entry );
          $this->assertEquals( $baseEntryId, $entry->splitVideoId );
      }

  }


  public function testPostEntry_SetContinent()
  {
      // user with continent 1 (Africa)
      $authInfo = array(
          'userId' => 303,
          'token' => 'B1LCrnzS7Xdugs1KIwt3JbbrReLF8wlYIK29JOCB'
      );
      $userContinent = 1; // africa
      // check that user has set its continent
      $user = \User::find( $authInfo['userId'] );
      $user->user_continent = $userContinent;
      $user->save();

      $testFilename = __DIR__.'/files/test_movie.mp4';
      $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

      $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );


      $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
          $uploadedFilename,
          'test_file.mp4'
      );

      $response = $this->call( 'POST', '/entry',
          array(
              'category' => 1,
              'type' => 'video',
              'language' => 'english',
              'name' => 'Test',
              'description' => 'Test Video',
          ),
          array( 'file1' => $file ),
          array( 'HTTP_X-API-TOKEN' => $authInfo['token'] )
      );

      $this->assertEquals( 201, $response->getStatusCode() );

      $content = json_decode( $response->getContent() );
      $this->assertNotEmpty( $content );
      $this->assertObjectHasAttribute( 'entry_id', $content );
      $uploadedEntryId = $content->entry_id;
      $this->assertNotEmpty( $uploadedEntryId );

      // get list of entries
      $entries = $this->getEntries( array( 'orderBy' => 'latest', 'user' => $authInfo['userId'] ) );
      $this->assertNotEmpty( $entries );

      $entryFound = false;
      foreach( $entries as $entryObj )
      {
          $entry = $entryObj->entry;
          if( $entry->id != $uploadedEntryId ) continue;
          $entryFound = true;
      }
      $this->assertTrue( $entryFound, 'Uploaded entry not found' );

      // check that entry has its continent set to $userContinent
      $entry = \Entry::findOrFail( $uploadedEntryId );

      $this->assertEquals( $userContinent, $entry->entry_continent );
  }


  public function testPostEntryMusicCategory()
  {
    $testFilename = __DIR__.'/files/test_movie.mp4';
    $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

    $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );

    $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
        $uploadedFilename,
        'test_file.mp4'
    );

    $response = $this->call( 'POST', '/entry',
        array(
          'category' => 1,
          'type' => 'video',
          'language' => 'english',
          'name' => 'Test',
          'description' => 'Test Video',
        ),
        array( 'file1' => $file ),
        array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 201, $response->getStatusCode() );

  }


  public function testPostEntryUnicodeUserName()
  {
    $testFilename = __DIR__.'/files/test_movie.mp4';
    $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

    $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );

    $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
        $uploadedFilename,
        'test_file.mp4'
    );

    $userName = 'Користувач';
    $category = 7; // user profile

    // add entry to user profile
    $response = $this->call( 'POST', '/entry',
        array(
          'category' => 7,
          'type' => 'video',
          'language' => 'english',
          'name' => $userName,
          'description' => 'Test Video',
        ),
        array( 'file1' => $file ),
        array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 201, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );
    $entry_id = $content->entry_id;

    // get entries from user profile
    $response = $this->call(
      'GET',
      '/entry',
      array(
        'category' => $category,
        'orderBy' => 'latest'
      ),
      array(),
      array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 200, $response->getStatusCode() );
    $content = json_decode( $response->getContent() );

    $entries = $content->entries;

    // find uploaded entry
    $uploadedEntry = null;
    foreach( $entries as $entryObj ) {
      if( $entryObj->entry->id == $entry_id ) {
        $uploadedEntry = $entryObj->entry;
        break;
      }
    }
    $this->assertFalse( empty( $uploadedEntry ), 'uploaded entry not found in entries first page' );

    $this->assertEquals( $userName, $uploadedEntry->name );
  }


  public function testSearch()
  {
      $user = $this->users['withFilter'];
      // set filter to Africa (1)
      $response = $this->call(
          'POST',
          '/settings/continentFilter',
          array( 'continentFilter' => json_encode( array( 1 ) ) ),
          array(),
          array( 'HTTP_X-API-TOKEN' => $user['tokenId'] )
      );
      $this->assertEquals( 200, $response->getStatusCode() );

      \Config::set( 'app.force_include_all_world', false );

      $response = $this->call(
          'GET',
          '/entry/search4',
          array( 'term' => 'Vas' ),
          array(),
          array( 'HTTP_X-API-TOKEN' => $this->users['withFilter']['tokenId'] )
      );

      $this->assertEquals( 200, $response->getStatusCode() );

      $content = json_decode( $response->getContent() );
      error_log( print_r( $content, true ) );
  }


  public function testAddView()
  {
      $userIdForAddedViews = 1;
      $userId = 427;

      $entryId = 404;

      $entry = \Entry::findOrFail( $entryId );

      $entryViews = $entry->entry_views;
      $entryViewsAdded = $entry->entry_views_added;

      // verify current views
      $this->assertEquals(
          $entryViews,
          $entry->entryViews()->where( 'entry_view_user_id', '<>', $userIdForAddedViews )->count()
      );
      $this->assertEquals(
          $entryViewsAdded,
          $entry->entryViews()->where( 'entry_view_user_id', '=', $userIdForAddedViews )->count()
      );

      // add added view
      \Entry::addView( $entryId, $userIdForAddedViews );
      $entryViewsAdded = $entryViewsAdded + 1;

      // reget entry
      $entry = \Entry::findOrFail( $entryId );

      $this->assertEquals( $entryViews, $entry->entry_views );
      $this->assertEquals( $entryViewsAdded, $entry->entry_views_added );

      $this->assertEquals(
          $entryViews,
          $entry->entryViews()->where( 'entry_view_user_id', '<>', $userIdForAddedViews )->count()
      );
      $this->assertEquals(
          $entryViewsAdded,
          $entry->entryViews()->where( 'entry_view_user_id', '=', $userIdForAddedViews )->count()
      );

      // add view
      \Entry::addView( $entryId, $userId );
      $entryViews = $entryViews + 1;

      // reget entry
      $entry = \Entry::findOrFail( $entryId );

      $this->assertEquals( $entryViews, $entry->entry_views );
      $this->assertEquals( $entryViewsAdded, $entry->entry_views_added );

      $this->assertEquals(
          $entryViews,
          $entry->entryViews()->where( 'entry_view_user_id', '<>', $userIdForAddedViews )->count()
      );
      $this->assertEquals(
          $entryViewsAdded,
          $entry->entryViews()->where( 'entry_view_user_id', '=', $userIdForAddedViews )->count()
      );
  }
}
