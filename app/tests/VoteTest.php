<?php

class VoteTest extends TestCase {


  // testVoteDown must be run before testVoteUp, otherwise after test there was no upvoted entries,
  // and next run will skip testGetUserUpvotes test


  // check in tokens table
  protected $authInfo = array(
    'tokenId' => '07516258357',
    'userId' => 301,
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


  protected function getVotes( $pars )
  {
    $response = $this->call( 'GET', '/vote', $pars, array(), array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] ) );

    if( $response->getStatusCode() == 404 ) // no entries
    {
      return array();
    }

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'votes', $content );

    $votes = $content->votes;

    return $votes;
  }


  protected function voteEntry( $entryId, $voteType )
  {
    $this->assertTrue( in_array( $voteType, array( 'up', 'down' ) ), 'wrong vote type' );

    $response = $this->call(
      'POST',
      '/vote',
      array(
        'entry' => $entryId,
        'type' => $voteType,
      ),
      array(),
      array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] )
    );

    $this->assertEquals( 201, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );
    $this->assertEquals( 'vote added', $content->message );
  }


  protected function getUserUpVotedEntries()
  {
    $pars = array(
      'user' => $this->authInfo['userId'],
      'type' => 'up',
      'page' => 1,
    );

    $votesCollection = $this->getVotes( $pars );

    $entries = array();

    foreach( $votesCollection as $voteObj )
    {
      $entryObj = new stdClass();
      $entryObj->entry = $voteObj->vote->entry;
      $entries[] = $entryObj;
    }

    return $entries;
  }


  public function testGetUserUpvotes()
  {
    // get up votes
    $pars = array(
      'user' => $this->authInfo['userId'],
      'type' => 'up',
      'page' => 1
    );

    $response = $this->call( 'GET', '/vote', $pars, array(), array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] ) );

    if( $response->getStatusCode() == 404 ) // no entries found
    {
      $this->markTestSkipped( 'no upvote entries found' );
      return;
    }

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'votes', $content );
  }


  public function testGetUserDownvotes() // not sure that this is ever needed
  {
    // get down votes
    $pars = array(
      'user' => $this->authInfo['userId'],
      'type' => 'down',
      'page' => 1
    );

    $response = $this->call( 'GET', '/vote', $pars, array(), array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] ) );

    if( $response->getStatusCode() == 404 ) // no entries found
    {
      $this->markTestSkipped( 'no downvote entries found' );
      return;
    }

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'votes', $content );
  }


  public function testVoteDown()
  {
    // when voting down entry must disappear from feed

    // get list of entries
    $entries = $this->getEntries( array( 'excludeVotes' => 'true' ) );

    $entryToVote = $entries[0]->entry;

    // vote down entry
    $this->voteEntry( $entryToVote->id, 'down' );

    // check that downvoted entry disappear from feed
    // renew list of entries
    $entries = $this->getEntries( array( 'excludeVotes' => 'true' ) );

    // get list of entries ids
    $entryIds = $this->getEntryIds( $entries );

    // check that downvoted entry is not in the list
    $this->assertFalse( in_array( $entryToVote->id, $entryIds ) );

    // check that voted down entry is not shown in TALENT CONNECT/MY VOTES ( GET /vote?user=<votedup user> )
    // get voted up entries of the user
    $upvotedEntries = $this->getUserUpVotedEntries();

    $this->assertFalse( in_array( $entryToVote->id, $this->getEntryIds( $upvotedEntries ) ) );
  }


  public function testVoteUp()
  {
    // when voting up entry must stay in feed and be shown in TALENT CONNECT/MY VOTES ( GET /vote?user=<upvoted user> )
    // @TODO upvoted entry can be voted up multiple times by an user

    // get list of entries
    $entries = $this->getEntries( array( 'excludeVotes' => 'true' ) );

    $entryToVote = $entries[0]->entry;

    // vote up entry
    $this->voteEntry( $entryToVote->id, 'up' );

    // renew list of entries
    $entries = $this->getEntries( array( 'excludeVotes' => 'true' ) );

    // get list of entries ids
    $entryIds = $this->getEntryIds( $entries );

    // check that voted up entry is in the list
    $this->assertTrue( in_array( $entryToVote->id, $entryIds ) );

    // check that voted up entry is shown in TALENT CONNECT/MY VOTES ( GET /vote?user=<upvoted user> )
    // get voted up entries of the user
    $upvotedEntries = $this->getUserUpVotedEntries();
    $upvotedEntryIds = $this->getEntryIds( $upvotedEntries );

    $this->assertTrue( in_array( $entryToVote->id, $upvotedEntryIds ) );
  }
}
