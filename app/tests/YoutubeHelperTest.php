<?php

use MobStar\YoutubeHelper;

class YoutubeHelperTest extends TestCase
{

    public function testGetDurationFromString()
    {
        $testData = array(
            'PT3M46S' => 226,
            'PT48S' => 48,
            'PT3M20S' => 200,
            'PT3H2M31S' => 10951,
            'PT1H11S' => 3611,
            'PT1H' => 3600,
            'PT23M' => 1380,
            'PT45S' => 45,
            'PT1H23M' => 4980,
            'PT1H45S' => 3645,
            'PT23M45S' => 1425,
            'PT1H23M45S' => 5025,
        );

        foreach( $testData as $str => $seconds )
        {
            $this->assertEquals(
                YoutubeHelper::getDurationFromString( $str ),
                $seconds
            );
        }
    }


    public function testGetInfo()
    {
        $testData = array(
            'fr-ncvcM8Ro' => array(
                'id' => 'fr-ncvcM8Ro',
                'duration' => 226,
                'access' => 'public',
                'error' => false
            ),
            'fr-ncvcM8Ro_' => array(
                'id' => 'fr-ncvcM8Ro_',
                'error' => 'Item not found'
            ),
            '1dSl5VO8HUA' => array(
                'id' => '1dSl5VO8HUA',
                'duration' => 243,
                'access' => 'public',
                'error' => false
            )
        );

        foreach( $testData as $key => $info )
        {
            $this->assertEquals(
                YoutubeHelper::getInfo( $key ),
                $info
            );
        }
    }


    public function testGetInfoFromUrl()
    {
        $testData = array(
            'http://www.youtube.com/watch?v=fr-ncvcM8Ro' => array(
                'id' => 'fr-ncvcM8Ro',
                'duration' => 226,
                'access' => 'public',
                'error' => false
            ),
            'http://www.youtube.com/watch?v=fr-ncvcM8Ro_' => array(
                'id' => 'fr-ncvcM8Ro_',
                'error' => 'Item not found'
            ),
            'http://www.youtube.com/watch?v=1dSl5VO8HUA' => array(
                'id' => '1dSl5VO8HUA',
                'duration' => 243,
                'access' => 'public',
                'error' => false
            ),
            'http://www.youtube.com/watch?n=fr-ncvcM8Ro' => array(
                'id' => null,
                'error' => 'Wrong YouTube url provided'
            ),
            'http://www.no-youtube.com/watch?v=fr-ncvcM8Ro' => array(
                'id' => null,
                'error' => 'Not YouTube url provided'
            ),
        );

        foreach( $testData as $url => $info )
        {
            $this->assertEquals(
                YoutubeHelper::getInfoFromUrl( $url ),
                $info
            );
        }
    }
}
