<?php

class ResponseTestCase extends TestCase
{

    protected function assertEntryListEquals( $expected, $actual )
    {
        if( !isset( $expected->entries ) )
        {
            $this->assertEquals( $expected, $actual );
            return;
        }
        $this->assertObjectHasAttribute( 'entries', $expected, 'expected has no entries attribute: '.print_r( $expected, true ) );
        $this->assertObjectHasAttribute( 'entries', $actual, 'actual has no entries attribute: '.print_r( $actual, true ) );

        $this->assertTrue( is_array( $expected->entries ), 'Expected: entries is not an array' );
        $this->assertTrue( is_array( $actual->entries ), 'Actual: entries is not an array' );

        if( count( $expected->entries ) == 1 )
        {
            $this->assertEquals( $expected, $actual );
            return;
        }

        $expectedIds = $this->getEntryIds( $expected->entries );
        $actualIds = $this->getEntryIds( $actual->entries );

        $this->assertEquals( $expectedIds, $actualIds, 'Entry ids mismatch' );

        $this->assertEquals( $expected, $actual );
    }


    protected function getEntryIds( $entries )
    {
        $ids = array();
        if( ! is_array( $entries ) ) return $ids;

        foreach( $entries as $entry )
        {
            $id = null;
            if( is_object( $entry ) )
            {
                if( isset( $entry->entry->id ) )
                {
                    $id = $entry->entry->id;
                }
            }
            if( is_null( $id ) )
            {
                $this->fail( 'no entry id in: '.print_r( $entry, true ) );
            }
            else
            {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
