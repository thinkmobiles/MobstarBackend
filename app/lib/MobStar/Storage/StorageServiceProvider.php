<?php namespace MobStar\Storage;
 
use Illuminate\Support\ServiceProvider;
 
class StorageServiceProvider extends ServiceProvider {
 
  public function register()
  {
  	//Bind Eloquent Entry to Entry Repo
    $this->app->bind(
      'MobStar\Storage\Entry\EntryRepository',
      'MobStar\Storage\Entry\EloquentEntryRepository'
    );

    //Bind Eleoquent Token to token repo
    $this->app->bind(
      'MobStar\Storage\Token\TokenRepository',
      'MobStar\Storage\Token\EloquentTokenRepository'
    );

    //Bind Eleoquent vote to vote repo
    $this->app->bind(
      'MobStar\Storage\Vote\VoteRepository',
      'MobStar\Storage\Vote\EloquentVoteRepository'
    );

    //Bind Eleoquent Message to message repo
    $this->app->bind(
      'MobStar\Storage\Message\MessageRepository',
      'MobStar\Storage\Message\EloquentMessageRepository'
    );

    //Bind Eleoquent Message2 to message2 repo
    $this->app->bind(
      'MobStar\Storage\Message2\Message2Repository',
      'MobStar\Storage\Message2\EloquentMessage2Repository'
    );
  }
 
}