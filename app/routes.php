<?php

use Swagger\Swagger;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

use Whoops\Handler\PrettyPageHandler;

// Use the Laravel IoC container to get the Whoops\Run instance, if whoops
// is available (which will be the case, by default, in the dev
// environment)
if( App::bound( "whoops" ) )
{
	// Retrieve the whoops handler in charge of displaying exceptions:
	$whoopsDisplayHandler = App::make( "whoops.handler" );

	// Laravel will use the PrettyPageHandler by default, unless this
	// is an AJAX request, in which case it'll use the JsonResponseHandler:
	if( $whoopsDisplayHandler instanceof PrettyPageHandler )
	{

		// Set a custom page title for our error page:
		$whoopsDisplayHandler->setPageTitle( "Houston, we've got a problem!" );

		// Set the "open:" link for files to our editor of choice:
		$whoopsDisplayHandler->setEditor( "sublime" );
	}
}

Route::get( '/', function()
{

});

Route::get( 'debug/', function ()
{
  ob_start();

  echo '<pre>';

  echo 'Api url: ', $_ENV['URL'], "\n";
  echo 'Admin url: ', Config::get( 'app.url_admin' ), "\n";

  echo 'App home path: ', Config::get( 'app.home' ), "\n";
  echo 'App tmp path: ', Config::get( 'app.tmp' ), "\n";

  echo 'ffmpeg bin: ', Config::get( 'app.bin_ffmpeg' ), "\n";
  echo 'ffprobe bin: ', Config::get( 'app.bin_ffprobe' ), "\n";

	echo "tmp read:";
	var_dump( is_readable( Config::get( 'app.tmp' ) ) );
	echo "\n";
	echo "home read:";
	var_dump( is_readable( Config::get( 'app.home' ).'/public/uploads' ) );

	echo "\n";
	echo "tmp write:";
	var_dump( is_writable( Config::get( 'app.tmp' ) ) );

	echo "\n";
	echo "tmp read:";
	var_dump( is_writable( Config::get( 'app.home' ).'/public/uploads' ) );
	echo "\n";

  /* commented out due to output from ffmpeg while running tests
	echo 'sonus supported formats: ', "\n";
	print_r( Sonus::getSupportedFormats() );
	*/

	var_dump($_ENV);

	echo "\n";

	echo 'current environment: ', print_r( App::environment(), true ), "\n\n";
	echo 'mysql database config:', "\n";
	print_r( Config::get( 'database.connections' )['mysql'] );
	echo 'AWS bucket: ', Config::get( 'app.bucket' ), "\n";

	echo 'AWS SNS disabled: ', Config::get('app.disable_sns') ? 'yes' : 'no' , "\n";

	echo 'youtube upload disabled: ', Config::get('app.disable_youtube_upload') ? 'yes' : 'no' , "\n";

	echo 'keep uploaded entry files: ', Config::get('app.keep_uploaded_entry_files') ? 'yes' : 'no' , "\n";

	echo "\n", '</pre>';

	return ob_get_clean();
//     Force the execution to fail by throwing an exception:
//    throw new RuntimeException("Oopsie!");
} );

Route::group( [ "before" => "auth" ], function ()
{

	Route::group( [ "before" => "logged_in" ], function ()
	{
		// -------------------------------------------------------
		// User(s)
		//---------------------------------------------------------

		Route::get( "user/search/", [
			"as"   => "user/search",
			"uses" => "UserController@search"
		] );

		Route::get( "/user", [
			"as"   => "user/index",
			"uses" => "UserController@index"
		] );

		Route::get( "/user/me", [
			"as"   => "user/me",
			"uses" => "UserController@me"
		] );

		Route::get( "user/{id}", [
			"as"   => "user/show",
			"uses" => "UserController@show"
		] );
		Route::put( "user/{user}", [
			"as"   => "user/update",
			"uses" => "UserController@update"
		] );

		Route::post( "user/profile/", [
			"as"   => "user/profile",
			"uses" => "UserController@profile"
		] );

		Route::post( "user/cover/", [
			"as"   => "user/cover",
			"uses" => "UserController@cover"
		] );

		Route::post( "user/password/", [
			"as"   => "user/password",
			"uses" => "UserController@passwordReset"
		] );

		Route::delete( "user/{user}", [
			"as"   => "user/destroy",
			"uses" => "UserController@destroy"
		] );

		Route::post( "user/follow/", [
			"as"   => "user/follow",
			"uses" => "UserController@follow"
		] );

		Route::post( "user/follower/", [
			"as"   => "user/follower",
			"uses" => "UserController@follower"
		] );
		Route::post( "user/following/", [
			"as"   => "user/following",
			"uses" => "UserController@following"
		] );
		Route::post( "user/analytic", [
			"as"   => "user/analytic",
			"uses" => "UserController@analytic"
		] );

		Route::post( "user/logout", [
			"as"   => "user/logout",
			"uses" => "UserController@logout"
		] );

		Route::post( "vote/likes/", [
			"as"   => "vote/likes",
			"uses" => "VoteController@likes"
		] );
		// -------------------------------------------------------
		// Category(s)
		//---------------------------------------------------------

		Route::get( "category/", [
			"as"   => "category/index",
			"uses" => "CategoryController@index"
		] );

		Route::get( "category/{id}", [
			"as"   => "category/show",
			"uses" => "CategoryController@show"
		] );

		// -------------------------------------------------------
		// Mentor(s)
		//---------------------------------------------------------

		Route::get( "mentor/", [
			"as"   => "mentor/index",
			"uses" => "MentorController@index"
		] );

		Route::get( "mentor/{id}", [
			"as"   => "mentor/show",
			"uses" => "MentorController@show"
		] );

		// -------------------------------------------------------
		// Default Notification(s)
		//---------------------------------------------------------

		Route::get( "defaultNotification/", [
			"as"   => "defaultNotification/index",
			"uses" => "DefaultNotificationController@index"
		] );

		// -------------------------------------------------------
		// Welcome Video
		//---------------------------------------------------------

		Route::get( "welcome/", [
			"as"   => "welcome/index",
			"uses" => "WelcomeController@index"
		] );

		Route::get( "video/show", [
		   "as"   => "video/show",
		   "uses" => "ModelingVideoController@show"
		] );

		// -------------------------------------------------------
		// Blogs
		//---------------------------------------------------------

		Route::get( "blogs/", [
			"as"   => "blogs/index",
			"uses" => "BlogsController@index"
		] );

		// -------------------------------------------------------
		// Entries
		//---------------------------------------------------------

		Route::get( "entry/", [
			"as"   => "entry/index",
			"uses" => "EntryController@index"
		] );

		Route::get( "entry/mix", [
			"as"   => "entry/mix",
			"uses" => "EntryController@mix"
		] );

		Route::get( "entry/search", [
			"as"   => "entry/search",
			"uses" => "EntryController@search"
		] );

		Route::get( "entry/search2", [
			"as"   => "entry/search2",
			"uses" => "EntryController@search2"
		] );

		Route::get( "entry/search3", [
			"as"   => "entry/search3",
			"uses" => "EntryController@search3"
		] );

		Route::get( "entry/search4", [
			"as"   => "entry/search4",
			"uses" => "EntryController@search4"
		] );

		Route::get( "entry/mysearch", [
			"as"   => "entry/mysearch",
			"uses" => "EntryController@mysearch"
		] );

		Route::post( "entry/dummytest", [
			"as"   => "entry/dummytest",
			"uses" => "EntryController@dummytest"
		] );

		Route::get( "entry/{id}", [
			"as"   => "entry/show",
			"uses" => "EntryController@show"
		] );

		Route::post( "entry", [
			"as"   => "entry/store",
			"uses" => "EntryController@store"
		] );

		Route::post( "entry/store2", [
			"as"   => "entry/store2",
			"uses" => "EntryController@store2"
		] );

		Route::post( "entry/videoupload", [
			"as"   => "entry/videoupload",
			"uses" => "EntryController@videoupload"
		] );

		Route::put( "entry/{id}", [
			"as"   => "entry/update",
			"uses" => "EntryController@update"
		] );

		Route::post( "entry/tag/{id}", [
			"as"   => "entry/tag",
			"uses" => "EntryController@tagEntry"
		] );

		Route::post( "entry/report/{id}", [
			"as"   => "entry/report",
			"uses" => "EntryController@report"
		] );

		Route::post( "entry/view/{id}", [
			"as"   => "entry/view",
			"uses" => "EntryController@view"
		] );

		Route::post( "entry/updateViewCount", [
			"as"	=> "entry/updateViewCount",
			"uses"	=> "EntryController@updateViewCount"
		] );
		Route::delete( "entry/{entry}", [
			"as"   => "entry/delete",
			"uses" => "EntryController@delete"
		] );
		// -------------------------------------------------------
		// Entry Feedback
		//---------------------------------------------------------

		Route::post( "entryfeedback/{id}", [
			"as"   => "entry/feedback",
			"uses" => "EntryController@storeFeedback"
		] );

		Route::get( "entryfeedback/", [
			"as"   => "entry/feedback",
			"uses" => "EntryController@getFeedback"
		] );


		// -------------------------------------------------------
		// Tags
		//---------------------------------------------------------

		Route::get( "tag/", [
			"as"   => "tag/index",
			"uses" => "TagController@index"
		] );

		// -------------------------------------------------------
		// Votes
		//---------------------------------------------------------

		Route::get( "vote/", [
			"as"   => "vote/index",
			"uses" => "VoteController@index"
		] );


		Route::get( "vote/forme", [
			"as"   => "vote/forme",
			"uses" => "VoteController@forMe"
		] );

		Route::post( "vote", [
			"as"   => "vote/store",
			"uses" => "VoteController@store"
		] );
		Route::delete( "vote/", [
			"as"   => "vote/destroy",
			"uses" => "VoteController@destroy"
		] );

		// -------------------------------------------------------
		// Messges
		//---------------------------------------------------------

		Route::get( "message/", [
			"as"   => "message/index",
			"uses" => "Message2Controller@index"
		] );

		Route::get( "message/{thread}", [
			"as"   => "message/show",
			"uses" => "Message2Controller@show"
		] );

		Route::post( "message/", [
			"as"   => "message/store",
			"uses" => "Message2Controller@store"
		] );

		Route::post( "message/reply", [
			"as"   => "message/reply",
			"uses" => "Message2Controller@reply"
		] );

		Route::post( "message/deleteThread", [
			"as"   => "message/deleteThread",
			"uses" => "Message2Controller@deleteThread"
		] );

		Route::post( "message/bulk", [
			"as"   => "message/bulk",
			"uses" => "Message2Controller@bulk"
		] );

		Route::post( "message/msgcount", [
			"as"   => "message/msgcount",
			"uses" => "Message2Controller@msgcount"
		] );

		Route::post( "message/read", [
			"as"   => "message/read",
			"uses" => "Message2Controller@read"
		] );

		Route::post( "message/showParticipants", [
			"as"   => "message/showParticipants",
			"uses" => "Message2Controller@showParticipants"
		] );

		Route::post( "message/badgeread", [
			"as"   => "message/badgeread",
			"uses" => "Message2Controller@badgeread"
		] );

//		Route::delete( "message/", [
//			"as"   => "message/destroy",
//			"uses" => "MessageController@destroy"
//		] );

		// -------------------------------------------------------
		// Stars
		//---------------------------------------------------------
		Route::post( "star/", [
			"as"   => "star/store",
			"uses" => "StarController@store"
		] );
		Route::delete( "star/{star}", [
			"as"   => "star/destroy",
			"uses" => "StarController@destroy"
		] );

		// -------------------------------------------------------
		// Winners
		//---------------------------------------------------------
		Route::get( "winner/", [
			"as"   => "star/index",
			"uses" => "WinnerController@index"
		] );

		// -------------------------------------------------------
		// Notifications
		//---------------------------------------------------------
		Route::get( "notification/", [
			"as"   => "notification/index",
			"uses" => "NotificationController@index"
		] );

		Route::get( "notification/count", [
			"as"   => "notification/count",
			"uses" => "NotificationController@count"
		] );


		Route::delete( "notification/{id}", [
			"as"   => "notification/delete",
			"uses" => "NotificationController@delete"
		] );

		Route::post( "notification/markread", [
			"as"   => "notification/markread",
			"uses" => "NotificationController@markread"
		] );

		Route::post( "notification/markreadnew", [
			"as"   => "notification/markreadnew",
			"uses" => "NotificationController@markreadnew"
		] );

		// -------------------------------------------------------
		// FAQ(s)
		//---------------------------------------------------------

		Route::get( "faq/", [
			"as"   => "faq/index",
			"uses" => "FaqController@index"
		] );

		// -------------------------------------------------------
		// Report(s)
		//---------------------------------------------------------

		Route::post( "report/", [
			"as"   => "report/store",
			"uses" => "AbuseReportController@store"
		] );

		// -------------------------------------------------------
		// Feedback
		//---------------------------------------------------------

		Route::post( "feedback/", [
			"as"   => "feedback/store",
			"uses" => "FeedbackController@store"
		] );

		// -------------------------------------------------------
		// Profile Content
		//---------------------------------------------------------

		Route::post( "profilecontent", [
			"as"   => "profilecontent/store",
			"uses" => "ProfileContentController@store"
		] );
		Route::get( "pushmessage", [
			"as"   => "profilecontent/pushmessage",
			"uses" => "ProfileContentController@pushmessage"
		] );
		// -------------------------------------------------------
		// Comments
		//---------------------------------------------------------

		Route::post( "comment/{entry}", [
			"as"   => "comment/store",
			"uses" => "CommentController@store"
		] );

		Route::get( "comment/", [
			"as"   => "comment/index",
			"uses" => "CommentController@index"
		] );

		Route::get( "comment/index2", [
			"as"   => "comment/index2",
			"uses" => "CommentController@index2"
		] );

		Route::delete( "comment/{id}", [
			"as"   => "comment/destroy",
			"uses" => "CommentController@destroy"
		] );

		//---------------------------------------------------------
		// Privacy Policy
		//---------------------------------------------------------

		Route::get( "privacy", [
			"as"   => "privacy/index",
			"uses" => "PrivacyController@index"
		] );

		Route::get( "privacy/accept", [
			"as"   => "privacy/store",
			"uses" => "PrivacyController@store"
		] );

		//---------------------------------------------------------
		// Talent Connect
		//---------------------------------------------------------

		Route::get( "talent", [
			"as"   => "talent/index",
			"uses" => "TalentController@index"
		] );


		Route::delete( "talent/{user}", [
			"as"   => "talent/remove",
			"uses" => "TalentController@delete"
		] );

		Route::get( "talent/top", [
			"as"   => "talent/top",
			"uses" => "TalentController@top"
		] );

		Route::get( "talent/topnew", [
			"as"   => "talent/topnew",
			"uses" => "TalentController@topnew"
		] );


		// -------------------------------------------------------
		// Fan Connect
		//---------------------------------------------------------

		Route::get( "fan/feedback", [
			"as"   => "fan/feedback",
			"uses" => "FanController@comments"
		] );


		//---------------------------------------------------------
		// Settings
		//---------------------------------------------------------

		Route::get( "settings/account", [
			"as"   => "settings/account",
			"uses" => "SettingsController@account"
		] );

		Route::post( "settings/account", [
			"as"   => "settings/account",
			"uses" => "SettingsController@addAccount"
		] );

	} );

	// -------------------------------------------------------
	// Login
	//---------------------------------------------------------

	Route::post( "login", [
		"as"   => "login/index",
		"uses" => "LoginController@index"
	] );

	Route::post( "login/verifyCode", [
		"as"   => "login/verifyCode",
		"uses" => "LoginController@verifyCode"
	] );

	Route::post( "login/verifyphonenumber", [
		"as"   => "login/verifyphonenumber",
		"uses" => "LoginController@verifyphonenumber"
	] );

	Route::post( "login/facebook", [
		"as"   => "login/facebook",
		"uses" => "LoginController@facebook"
	] );

	Route::post( "login/twitter", [
		"as"   => "login/twitter",
		"uses" => "LoginController@twitter"
	] );

	Route::post( "login/google", [
		"as"   => "login/google",
		"uses" => "LoginController@google"
	] );

	Route::post( "user", [
		"as"   => "user/store",
		"uses" => "UserController@store"
	] );


} );

Route::get( "entry2/rerank", [
	"as"   => "entry2/rerank",
	"uses" => "EntryController@rerank"
] );
Route::post( "user/userRank", [
	"as"   => "user/userRank",
	"uses" => "UserController@userRank"
] );
Route::get( "entry2/fixfile", [
	"as"   => "entry2/updateFile",
	"uses" => "EntryController@updateFile"
] );


Route::group( [ "before" => "admin" ], function ()
{
	/*Route::delete( "entry/{entry}", [
		"as"   => "entry/delete",
		"uses" => "EntryController@delete"
	] );*/

	Route::get( "restoreentry/{entry}", [
		"as"   => "entry/undelete",
		"uses" => "EntryController@undelete"
	] );

	Route::get( "admin/", [
		"as"   => "admin/index",
		"uses" => "AdminController@index"
	] );

	Route::get( "admin/entry", [
		"as"   => "admin/entry",
		"uses" => "AdminController@addEntry"
	] );

	Route::post( "admin/entry", [
		"as"   => "admin/entry",
		"uses" => "AdminController@insertEntry"
	] );
});




Route::get( "admin/login", [
	"as"   => "admin/login",
	"uses" => "AdminController@login"
] );

Route::post( "admin/validate", [
	"as"   => "admin/validate",
	"uses" => "AdminController@validate"
] );

//
//Route::get( "entry2/test", [
//	"as"   => "entry2/test",
//	"uses" => "EntryController@test"
//] );
//
//Route::get( "user2/test", [
//	"as"   => "user2/test",
//	"uses" => "UserController@test"
//] );

Route::get( 'api-info/', function ()
{
	// var_dump($_ENV);
	$swagger = new Swagger( Config::get('app.home') . '/app/controllers' );
//header("Content-Type: application/json");
//var_dump($swagger);
	$return = $swagger->getRegistry();

	$return = $swagger->getResourceList( array( 'output' => 'json' ) );

	$response = Response::make( $return, 200 );
	$response->header( 'Content-Type', "application/json" );
	$response->header( 'Access-Control-Allow-Methods', "POST, GET, OPTIONS , PUT" );
	$response->header( 'Access-Control-Allow-Origin', "*" );

	return $response;
} );

Route::get( 'api-info//{resource}', function ( $resource )
{
	$swagger = new Swagger( Config::get('app.home') . '/app/controllers' );
	//header("Content-Type: application/json");
	//var_dump($swagger);
	$return = $swagger->getRegistry();

	$return = $swagger->getResource( "/" . $resource, array( 'output' => 'json' ) );
	$response = Response::make( $return, 200 );
	$response->header( 'Content-Type', "application/json" );
	$response->header( 'Access-Control-Allow-Methods', "POST, GET, OPTIONS , PUT" );
	$response->header( 'Access-Control-Allow-Origin', "*" );

	return $response;
} );

Route::get( 'secure-route', array( 'before' => 'oauth', function ()
{
	return "oauth secured route";
} ) );

Route::post( 'oauth/access_token', function ()
{
	return AuthorizationServer::performAccessTokenFlow();
} );

Route::get( 'eloquent', function ()
{

	phpinfo();
} );

Route::post( "login/forgotpassword", [
	"as"   => "login/forgotpassword",
	"uses" => "LoginController@password"
] );

Route::post( "entry/youtubeUpload", [
			"as"   => "entry/youtubeUpload",
			"uses" => "EntryController@youtubeUpload"
		] );

Route::post( "entry/youtubeDelete", [
	"as"   => "entry/youtubeDelete",
	"uses" => "EntryController@youtubeDelete"
] );

Route::post( "user/team/", [
			"as"   => "user/team",
			"uses" => "UserController@team"
		] );
Route::get( "login/twiml/", [
			"as"   => "login/twiml",
			"uses" => "LoginController@twiml"
		] );
Route::post( "entry/deleteentryfiles", [
			"as"	=> "entry/deleteentryfiles",
			"uses"	=> "EntryController@deleteEntryFiles"
		] );
Route::post( "user/uploadimage", [
			"as"	=> "user/uploadimage",
			"uses"	=> "UserController@uploadimage"
		] );
App::missing( function ( $exception )
{

	return Response::make( [ 'error' => 'Endpoint not found' ], 404 );
} );

