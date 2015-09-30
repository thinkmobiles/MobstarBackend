<?php

use MobStar\EntryHelper;

class EntryHelperTest extends TestCase
{
    private $entryBasicInfo = array(
        485 => array(
            'entry_name' => 'Matilda Thompson',
            'entry_description' => 'Middle 8 in the making!',
        ),
        532 => array(
            'entry_name' => 'Matilda Thompson',
            'entry_description' => 'An old song I wrote 2 yrs ago, Waiting for December !',
        ),
        800 => array(
            'entry_name' => 'seanwaldronmusic',
            'entry_description' => "15 Second JukeBOX - Sam Smith \"I'm Not The Only One\"",
        ),
        3301 => array(
            'entry_name' => 'Devika Khanduja',
            'entry_description' => 'Monster - Rihanna ',
        ),
    );


    private $commentCounts = array(
        485 => 15,
        532 => 17,
        800 => 9,
        3301 => 0,
    );


    private $filesInfo = array(
        485 => array(
            array( 'entry_file_name' => '1r6jtkRxZpIw', 'entry_file_type' => 'mp3' ),
            array( 'entry_file_name' => '1r6jtkRxZpIw', 'entry_file_type' => '.jpg'),
        ),
        532 => array(
            array( 'entry_file_name' => 'HL9G4oGhn1eP', 'entry_file_type' => 'mp4' ),
        ),
        800 => array(
            array( 'entry_file_name' => 'OPAqRoQV2W08', 'entry_file_type' => 'mp4' ),
        ),
        3301 => array(
            array( 'entry_file_name' => 'nHpUdol9fKRd', 'entry_file_type' => 'mp4' ),
        ),
    );


    private $tagNamesInfo = array(
      485 => array( 'music', 'artist', 'studio' ),
      532 => array( 'music', 'artist', 'matildathompson' ),
      800 => array( 'sam smith', 'Acoustic Guitar' ),
      3301 => array( 'singing', 'cover', 'vocals', 'rihanna' ),
    );


    private $totalVotesInfo = array(
        485 => array( 'up' => 7, 'down' => 4 ),
        532 => array( 'up' => 84, 'down' => 23 ),
        800 => array( 'up' => 48, 'down' => 8 ),
        3301 => array( 'up' => 5, 'down' => 2 ),
    );


    private $votedByUserInfo = array(
        519 => array(
            485 => array( 'up' => false, 'down' => true ),
            532 => array( 'up' => true, 'down' => false ),
            800 => array( 'up' => true, 'down' => false ),
            3301 => array( 'up' => false, 'down' => false ),
        )
    );


    private function assertEntryBasicInfo( $basicInfo )
    {
        foreach( $basicInfo as $entryId => $info ) {
            $this->assertArrayHasKey( $entryId, $this->entryBasicInfo );

            $etalonInfo = $this->entryBasicInfo[ $entryId ];

            $this->assertEquals( $etalonInfo['entry_name'], $info->entry_name );
            $this->assertEquals( $etalonInfo['entry_description'], $info->entry_description );
        }
    }


    private function assertCommentCounts( $commentCounts )
    {
        foreach( $commentCounts as $entryId => $commentCount ) {
            $this->assertArrayHasKey( $entryId, $this->commentCounts );
            $this->assertEquals( $this->commentCounts[ $entryId ], $commentCount );
        }
    }


    private function assertFilesInfo( $filesInfo )
    {
        foreach( $filesInfo as $entryId => $info ) {
            $this->assertArrayHasKey( $entryId, $this->filesInfo );

            $etalonInfo = $this->filesInfo[ $entryId ];

            $this->assertEquals( count( $etalonInfo ), count( $info ) );

            if( count( $info ) ) {

                $fileInfo = array_pop( $info );
                $etalonFileInfo = array_pop( $etalonInfo );
                $this->assertEquals( $etalonFileInfo['entry_file_name'], $fileInfo->entry_file_name );
                $this->assertEquals( $etalonFileInfo['entry_file_type'], $fileInfo->entry_file_type );
            }
        }
    }


    private function assertTagNamesInfo( $tagNamesInfo )
    {
        foreach( $tagNamesInfo as $entryId => $info ) {
            $this->assertArrayHasKey( $entryId, $this->tagNamesInfo );

            $etalonInfo = $this->tagNamesInfo[ $entryId ];

            $this->assertEquals( count( $etalonInfo ), count( $info ) );
            $this->assertEquals( $etalonInfo, $info );
        }
    }


    private function assertTotalVotesInfo( $totalVotesInfo )
    {
        foreach( $totalVotesInfo as $entryId => $info ) {
            $this->assertArrayHasKey( $entryId, $this->totalVotesInfo );

            $etalonInfo = $this->totalVotesInfo[ $entryId ];

            $this->assertEquals( count( $etalonInfo ), count( $info ) );
            $this->assertEquals( $etalonInfo, $info );
        }
    }


    private function assertVotedByUserInfo( $votedByUserInfo, $userId )
    {
        $this->assertArrayHasKey( $userId, $this->votedByUserInfo );

        $etalonInfo = $this->votedByUserInfo[ $userId ];

        foreach( $votedByUserInfo as $entryId => $votedInfo ) {

            $this->assertArrayHasKey( $entryId, $etalonInfo );

            $this->assertEquals( $etalonInfo[ $entryId ], $votedInfo );
        }
    }


    public function testGetBasicInfo()
    {
        EntryHelper::clear();

        // get for one entry
        $entryId = 485;

        $basicInfo = EntryHelper::getBasicInfo( array( $entryId ) );

        $this->assertEntryBasicInfo( $basicInfo );


        // get for meny entries
        $entryIds = array_keys( $this->entryBasicInfo );

        $basicInfo = EntryHelper::getBasicInfo( $entryIds );

        $this->assertEntryBasicInfo( $basicInfo );


        // get for not existent entry
        $entryId = 1000000;

        $basicInfo = EntryHelper::getBasicInfo( array( $entryId ) );

        $this->assertArrayHasKey( $entryId, $basicInfo );
        $this->assertEquals( false, $basicInfo[$entryId] );

    }


    public function testGetCommentCounts()
    {
        EntryHelper::clear();

        // get comments count for one entry
        $entryId = 485;

        $commentCounts = EntryHelper::getCommentCount( array( $entryId ) );

        $this->assertCommentCounts( $commentCounts );


        // get comments count for meny entries
        $entryIds = array_keys( $this->commentCounts );

        $commentCounts = EntryHelper::getCommentCount( $entryIds );

        $this->assertCommentCounts( $commentCounts );


        // get comments count for not existent entry
        $entryId = 1000000;

        $commentCounts = EntryHelper::getCommentCount( array( $entryId ) );

        $this->assertArrayHasKey( $entryId, $commentCounts );
        $this->assertEquals( 0, $commentCounts[$entryId] );
    }


    public function testGetFilesInfo()
    {
        EntryHelper::clear();

        // get files info for one entry
        $entryId = 485;

        $filesInfo = EntryHelper::getFilesInfo( array( $entryId ) );

        $this->assertFilesInfo( $filesInfo );


        // get files info for meny entries
        $entryIds = array_keys( $this->filesInfo );

        $filesInfo = EntryHelper::getFilesInfo( $entryIds );

        $this->assertFilesInfo( $filesInfo );


        // get files info for not existent entry
        $entryId = 1000000;

        $filesInfo = EntryHelper::getFilesInfo( array( $entryId ) );

        $this->assertArrayHasKey( $entryId, $filesInfo );
        $this->assertEquals( 0, count( $filesInfo[ $entryId ] ) );
        $this->assertEquals( array(), $filesInfo[ $entryId ] );
    }


    public function testGetTagNamesInfo()
    {
        EntryHelper::clear();

        // get tag names info for one entry
        $entryId = 485;

        $tagNamesInfo = EntryHelper::getTagNamesInfo( array( $entryId ) );

        $this->assertTagNamesInfo( $tagNamesInfo );


        // get tag names info for meny entries
        $entryIds = array_keys( $this->filesInfo );

        $tagNamesInfo = EntryHelper::getTagNamesInfo( $entryIds );

        $this->assertTagNamesInfo( $tagNamesInfo );


        // get tag names info for not existent entry
        $entryId = 1000000;

        $tagNamesInfo = EntryHelper::getTagNamesInfo( array( $entryId ) );

        $this->assertArrayHasKey( $entryId, $tagNamesInfo );
        $this->assertEquals( 0, count( $tagNamesInfo[ $entryId ] ) );
        $this->assertEquals( array(), $tagNamesInfo[ $entryId ] );
    }


    public function testGetTotalVotesInfo()
    {
        EntryHelper::clear();

        // get total votes info for one entry
        $entryId = 485;

        $totalVotesInfo = EntryHelper::getTotalVotesInfo( array( $entryId ) );

        $this->assertTotalVotesInfo( $totalVotesInfo );


        // get total votes info for meny entries
        $entryIds = array_keys( $this->totalVotesInfo );

        $totalVotesInfo = EntryHelper::getTotalVotesInfo( $entryIds );

        $this->assertTotalVotesInfo( $totalVotesInfo );


        // get total votes info for not existent entry
        $entryId = 1000000;

        $totalVotesInfo = EntryHelper::getTotalVotesInfo( array( $entryId ) );

        $this->assertArrayHasKey( $entryId, $totalVotesInfo );
        $this->assertEquals( array('up' => 0, 'down' => 0), $totalVotesInfo[ $entryId ] );
    }


    public function testGetVotedByUserInfo()
    {
        EntryHelper::clear();

        $userId = 519;

        // get for one entry
        $entryId = 485;

        $votedByUserInfo = EntryHelper::getVotedByUserInfo( array( $entryId ), $userId );

        $this->assertVotedByUserInfo( $votedByUserInfo, $userId );


        // get for meny entries
        $entryIds = array_keys( $this->votedByUserInfo[ $userId ] );

        $votedByUserInfo = EntryHelper::getVotedByUserInfo( $entryIds, $userId );

        $this->assertVotedByUserInfo( $votedByUserInfo, $userId );


        // get for not existent entry
        $entryId = 1000000;

        $votedByUserInfo = EntryHelper::getVotedByUserInfo( array( $entryId ), $userId );

        $this->assertArrayHasKey( $entryId, $votedByUserInfo );
        $this->assertEquals( array('up' => false, 'down' => false), $votedByUserInfo[ $entryId ] );
    }


    public function testGetEnries()
    {
        EntryHelper::clear();

        $userId = 519;

        $fields = array( 'commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser' );

        // get for one entry
        $entryId = 485;

        $entries = EntryHelper::getEntries( array( $entryId ), $fields, $userId );

        $this->assertEntryBasicInfo( $entries );
        $this->assertCommentCounts( array( $entryId => $entries[ $entryId ]->commentCounts ) );
        $this->assertFilesInfo( array( $entryId => $entries[ $entryId ]->filesInfo ) );
        $this->assertTagNamesInfo( array( $entryId => $entries[ $entryId ]->tagNames ) );
        $this->assertTotalVotesInfo( array( $entryId => $entries[ $entryId ]->totalVotes ) );
        $this->assertVotedByUserInfo( array( $entryId => $entries[ $entryId ]->votedByUser ), $userId );

        // get for meny entries
        $entryIds = array_keys( $this->entryBasicInfo );

        $entries = EntryHelper::getEntries( $entryIds, $fields, $userId );

        $this->assertEntryBasicInfo( $entries );

        foreach( $entries as $entryId => $entryInfo ) {
            $this->assertCommentCounts( array( $entryId => $entryInfo->commentCounts ) );
            $this->assertFilesInfo( array( $entryId => $entryInfo->filesInfo ) );
            $this->assertTagNamesInfo( array( $entryId => $entryInfo->tagNames ) );
            $this->assertTotalVotesInfo( array( $entryId => $entryInfo->totalVotes ) );
            $this->assertVotedByUserInfo( array( $entryId => $entryInfo->votedByUser ), $userId );
        }

        // get for not existent entry
        $entryId = 1000000;

        $entries = EntryHelper::getEntries( array( $entryId ), $fields, $userId );

        $this->assertArrayHasKey( $entryId, $entries );

        $this->assertEquals( false, $entries[ $entryId ] );
    }
}
