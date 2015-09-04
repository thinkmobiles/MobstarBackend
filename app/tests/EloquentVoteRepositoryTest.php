<?php

use MobStar\Storage\Vote\EloquentVoteRepository as VoteRepository;
use Illuminate\Support\Facades\DB;

class EloquentVoteRepositoryTest extends \TestCase {


  protected $authInfo = array(
    'tokenId' => '07516258357',
    'userId' => 301,
  );

  private $votesEntry413;

  public function setUp()
  {
    parent::setUp();
    $this->setupVotesEntry413();
  }


  private function setupVotesEntry413()
  {
    // get votes for entryId 413 directly from DB
    $query = DB::table('votes');
    $query->where( 'vote_entry_id', '=', 413 );

    $rows = $query->get();
    $this->votesEntry413 = array( 'up' => 0, 'down' => 0, 'deleted' => 0 );

    foreach( $rows as $row )
    {
      if( $row->vote_deleted ) {
        $this->votesEntry413['deleted']++;
        continue;
      }
      if( $row->vote_up ) $this->votesEntry413['up']++;
      if( $row->vote_down ) $this->votesEntry413['down']++;
    }
  }


  private function getVotesForEntry( $entryId )
  {
    $query = DB::table('votes');
    $query->where( 'vote_entry_id', '=', $entryId );

    $rows = $query->get();
    $votes = array( 'up' => 0, 'down' => 0, 'deleted' => 0 );

    foreach( $rows as $row )
    {
      if( $row->vote_deleted ) {
        $votes['deleted']++;
        continue;
      }
      if( $row->vote_up ) $votes['up']++;
      if( $row->vote_down ) $votes['down']++;
    }

    return $votes;
  }


  private function getVoteRepository()
  {
    return new VoteRepository();
  }


  public function testCanGetVoteRepository()
  {
    $query = DB::connection()->table('votes');
    $voteRepository = $this->getVoteRepository();

    $this->assertNotEmpty( $voteRepository );
  }


  public function testGetTotalVotesForEntries_oneEntry()
  {
    $repository = $this->getVoteRepository();

    $entryId = 413;

    $votes = $repository->getTotalVotesForEntries( $entryId );

    $votesEtalon = $this->getVotesForEntry( $entryId );

    $this->assertEquals( $votesEtalon['up'], $votes->votes_up );
    $this->assertEquals( $votesEtalon['down'], $votes->votes_down );

  }


  public function testGetTotalVotesForEntries_manyEntries()
  {
    $repository = $this->getVoteRepository();

    $entries = array( 200, 404, 406, 407, 408 );

    $votes = $repository->getTotalVotesForEntries( $entries );

    $this->assertNotEmpty( $votes );
    $this->assertEquals( count($entries), count($votes) );
    $this->assertEquals( $entries, array_keys( $votes ) );

    foreach( $entries as $entryId )
    {
      $votesEtalon = $this->getVotesForEntry( $entryId );

      $this->assertEquals( $votesEtalon['up'], $votes[$entryId]->votes_up );
      $this->assertEquals( $votesEtalon['down'], $votes[$entryId]->votes_down );
    }
  }


  public function testGetTotalVotesForEntries_allEntries()
  {
    $repository = $this->getVoteRepository();

    $votes = $repository->getTotalVotesForEntries();
    $this->assertNotEmpty( $votes );
    $this->assertTrue( count( $votes) > 0 );
  }

}
