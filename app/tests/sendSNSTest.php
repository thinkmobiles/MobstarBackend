<?php


class sendSNSTest extends TestCase
{
  public function testSendSNS()
  {
    $userToSend = 4464; // Александр Рябцев
    $usedEntryId = 4788;
    $createdEntryId = 4789;

    $entryController = new EntryController(
        new MobStar\Storage\Entry\EloquentEntryRepository(),
        new MobStar\Storage\Token\EloquentTokenRepository()
    );


    Config::set('app.disable_sns', false );

//    return;
    $entryController->processSplitVideoNotifications(
        $userToSend,
        \Entry::findOrFail( $createdEntryId),
        $usedEntryId
    );
    /*
      $userToSend,
      'Your video was used for creating other video',
        array(
            "badge" => 5,
            "creatorId" => 1,
            "creatorName" => 'Test',
            "createdEntryId" => 1,
            "usedEntryId" => 2,
            "Type" => 'splitScreen',
        )
    );
    */
  }
}
