<?php

use MobStar\Pager;

class PagerTest extends TestCase
{

    private $results5 = array(
        1,
        2,
        3,
        4,
        5
    );


    public function testNoPages()
    {
        $pager = new Pager( 'test', 0 );

        $this->assertEquals( 0, $pager->getLimit() );
        $this->assertEquals( 0, $pager->getOffset() );
        $this->assertEquals( $this->results5, $pager->getAdjustedToPage( $this->results5 ) );
        $this->assertFalse( $pager->needPrevLink() );
        $this->assertFalse( $pager->needNextLink() );
    }


    public function testNextLink()
    {
        $pager = new Pager( 'test', 4 );

        $this->assertEquals( 5, $pager->getLimit() );
        $this->assertEquals( 0, $pager->getOffset() );
        $this->assertCount( 4, $pager->getAdjustedToPage( $this->results5 ) );
        $this->assertFalse( $pager->needPrevLink() );
        $this->assertTrue( $pager->needNextLink() );

        $this->assertEquals( 'test?limit=4&page=2', $pager->getNextLink() );


        $pager = new Pager( 'test', 4, 2 );

        $this->assertEquals( 5, $pager->getLimit() );
        $this->assertEquals( 4, $pager->getOffset() );
        $this->assertCount( 1, $pager->getAdjustedToPage( array(1) ) );
        $this->assertTrue( $pager->needPrevLink() );
        $this->assertFalse( $pager->needNextLink() );

        $this->assertEquals( 'test?limit=4&page=1', $pager->getPrevLink() );
    }


    public function testNoNext()
    {
        $pager = new Pager( 'test', 4, 2 );

        $this->assertEquals( 5, $pager->getLimit() );
        $this->assertEquals( 4, $pager->getOffset() );
        $this->assertCount( 1, $pager->getAdjustedToPage( array(1) ) );
        $this->assertTrue( $pager->needPrevLink() );
        $this->assertFalse( $pager->needNextLink() );

        $this->assertEquals( 'test?limit=4&page=1', $pager->getPrevLink() );
    }


    public function testPrevLink()
    {
        $pager = new Pager( 'test', 4, 2 );

        $this->assertEquals( 5, $pager->getLimit() );
        $this->assertEquals( 4, $pager->getOffset() );
        $this->assertCount( 4, $pager->getAdjustedToPage( $this->results5 ) );
        $this->assertTrue( $pager->needPrevLink() );
        $this->assertTrue( $pager->needNextLink() );

        $this->assertEquals( 'test?limit=4&page=1', $pager->getPrevLink() );
        $this->assertEquals( 'test?limit=4&page=3', $pager->getNextLink() );
    }
}
