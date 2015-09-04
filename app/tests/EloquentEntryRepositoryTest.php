<?php

use MobStar\Storage\Entry\EloquentEntryRepository as EntryRepository;

class EloquentEntryRepositoryTest extends TestCase {


  protected $authInfo = array(
    'tokenId' => '07516258357',
    'userId' => 301,
  );


  private function getEntryRepository()
  {
    return new EntryRepository();
  }


  private function getEntryIds( $entries )
  {
    $ids = array();
    foreach( $entries as $entryObj )
    {
      $ids[] = $entryObj->entry_id;
    }
    return $ids;
  }


  private function getEntriesUsingComplextExclude( $pars, $exclude )
  {
    $entryRepository = $this->getEntryRepository();

    $entries = $entryRepository->allComplexExclude(
      empty( $pars['userId'] ) ? 0 : $pars['userId'],
      empty( $pars['categoryId'] ) ? 0 : $pars['categoryId'],
      empty( $pars['tagId'] ) ? 0 : $pars['tagId'],
      $exclude,
      empty( $pars['orderBy'] ) ? 0 : $pars['orderBy'],
      empty( $pars['order'] ) ? 0 : $pars['order'],
      empty( $pars['limit'] ) ? 20 : $pars['limit'],
      empty( $pars['offset'] ) ? 0 : $pars['offset'],
      empty( $pars['count'] ) ? false: true,
      empty( $pars['withAll'] ) ? false : true
    );

    return $entries;
  }


  private function getEntryIdsUsingComplextExclude( $pars, $exclude )
  {
    $entries = $this->getEntriesUsingComplextExclude( $pars, $exclude );

    return $this->getEntryIds( $entries );
  }


  private function getEntryCountUsingComplextExclude( $pars, $exclude )
  {
    $localPars = $pars;
    $localPars['count'] = true;

    $count = $this->getEntriesUsingComplextExclude( $pars, $exclude );

    return $count;
  }


  private function getEntriesUsingExcludeIds( $pars, $exclude )
  {
    $entryRepository = $this->getEntryRepository();

    $entries = $entryRepository->allComplexExclude_convertToIds(
      empty( $pars['userId'] ) ? 0 : $pars['userId'],
      empty( $pars['categoryId'] ) ? 0 : $pars['categoryId'],
      empty( $pars['tagId'] ) ? 0 : $pars['tagId'],
      $exclude,
      empty( $pars['orderBy'] ) ? 0 : $pars['orderBy'],
      empty( $pars['order'] ) ? 0 : $pars['order'],
      empty( $pars['limit'] ) ? 20 : $pars['limit'],
      empty( $pars['offset'] ) ? 0 : $pars['offset'],
      empty( $pars['count'] ) ? false: true,
      empty( $pars['withAll'] ) ? false : true
    );

    return $entries;
  }


  private function getEntryIdsUsingExcludeIds( $pars, $exclude )
  {
    $entries = $this->getEntriesUsingExcludeIds( $pars, $exclude );

    return $this->getEntryIds( $entries );
  }


  private function getEntryCountUsingExcludeIds( $pars, $exclude )
  {
    $localPars = $pars;
    $localPars['count'] = true;

    $count = $this->getEntriesUsingExcludeIds( $pars, $exclude );

    return $count;
  }





  public function testGetExcludeIdsForComplexExclude()
  {
    $entryRepository = $this->getEntryRepository();

    $ids = $entryRepository->getExcludeIdsForComplexExclude( array(
      'notPopular' => true,
      'category' => array( 7, 8 ),
      'excludeVotes' => $this->authInfo['userId'],
    ));

    $this->assertNotEmpty( $ids );

    $this->assertTrue( in_array( 406, $ids ), 'excluded by vote down' );
  }


  public function testAll_MainFeed()
  {
    $params = array(
      'userId' => 0,
      'categoryId' => 0,
      'tagId' => 0,
      'orderBy' => 'entry_id',
      'order' => 'asc',
      'limit' => 20,
      'offset' => 0,
      'count' => false,
      'withAll' => false,
    );

    $exclude = array();

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['category'] = array( 7, 8 );

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['excludeVotes'] = $this->authInfo['userId'];

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['notPopular'] = true;

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );
  }


  public function testAll_User()
  {
    $params = array(
      'userId' => 1287,
      'categoryId' => 0,
      'tagId' => 0,
      'orderBy' => 'entry_id',
      'order' => 'asc',
      'limit' => 20,
      'offset' => 0,
      'count' => false,
      'withAll' => false,
    );

    $exclude = array();

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['category'] = array( 7, 8 );

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['excludeVotes'] = $this->authInfo['userId'];

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['notPopular'] = true;

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );
  }


  public function testAll_Category()
  {
    $params = array(
      'userId' => 0,
      'categoryId' => 1,
      'tagId' => 0,
      'orderBy' => 'entry_id',
      'order' => 'asc',
      'limit' => 20,
      'offset' => 0,
      'count' => false,
      'withAll' => false,
    );

    $exclude = array();

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['category'] = array( 7, 8 );

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['excludeVotes'] = $this->authInfo['userId'];

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['notPopular'] = true;

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );
  }


  public function testAll_Tag()
  {
    $params = array(
      'userId' => 0,
      'categoryId' => 0,
      'tagId' => 2,
      'orderBy' => 'entry_id',
      'order' => 'asc',
      'limit' => 20,
      'offset' => 0,
      'count' => false,
      'withAll' => false,
    );

    $exclude = array();

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['category'] = array( 7, 8 );

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['excludeVotes'] = $this->authInfo['userId'];

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['notPopular'] = true;

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );
  }


  public function testAll_Mix()
  {
    $params = array(
      'userId' => 1287,
      'categoryId' => 1,
      'tagId' => 2,
      'orderBy' => 'entry_id',
      'order' => 'asc',
      'limit' => 20,
      'offset' => 0,
      'count' => false,
      'withAll' => false,
    );

    $exclude = array();

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['category'] = array( 7, 8 );

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['excludeVotes'] = $this->authInfo['userId'];

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );


    $exclude['notPopular'] = true;

    $usingIds = $this->getEntryIdsUsingExcludeIds( $params, $exclude );
    $usingComplexExclude = $this->getEntryIdsUsingComplextExclude( $params, $exclude );

    $this->assertEquals( $usingIds, $usingComplexExclude );

    $this->assertEquals(
        $this->getEntryCountUsingExcludeIds( $params, $exclude ),
        $this->getEntryCountUsingComplextExclude( $params, $exclude )
    );
  }
}
