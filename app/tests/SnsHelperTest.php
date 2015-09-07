<?php

use MobStar\SnsHelper;

class SnsHelperTest extends TestCase{

    public function testSendNotification()
    {
        Config::set( 'app.disable_sns', false );

        $toUserId = 4478; // Vasia Lipcha
        $message = 'Your entry usedEntryName has been collaborated on by creatorName. Check it out...';
        $data = array(
            'Type' => 'splitScreen',
            'badge' => 5, // set count of messages to user
            'usedEntryName' => 'usedEntryName',
            'creatorName' => 'creatorName',
            'createdEntryId' => 4787,
        );
        SnsHelper::sendNotification( $toUserId, $message, $data );
    }
}
