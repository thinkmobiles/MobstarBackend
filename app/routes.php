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
	echo "tmp read:";
	var_dump( is_readable( '/tmp/' ) );
	echo "<br>";
	echo "<br>";
	echo "<br>";
	echo "home read:";
	var_dump( is_readable( '/var/www/api-beta/public/uploads' ) );

	echo "<br>";
	echo "<br>";
	echo "<br>";
	echo "tmp write:";
	var_dump( is_writable( '/tmp/' ) );

	echo "<br>";
	echo "<br>";
	echo "<br>";
	echo "tmp read:";
	var_dump( is_writable( '/var/www/api-beta/public/uploads' ) );
	echo "<br>";
	echo "<br>";
	echo "<br>";

	echo 'sonus:';
	var_dump( Sonus::getSupportedFormats() );

	var_dump($_ENV);
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

		Route::delete( "user/{user}", [
			"as"   => "user/destroy",
			"uses" => "UserController@destroy"
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
		// Welcome Video
		//---------------------------------------------------------

		Route::get( "welcome/", [
			"as"   => "welcome/index",
			"uses" => "WelcomeController@index"
		] );

		// -------------------------------------------------------
		// Entries
		//---------------------------------------------------------

		Route::get( "entry/", [
			"as"   => "entry/index",
			"uses" => "EntryController@index"
		] );

		Route::get( "entry/search", [
			"as"   => "entry/search",
			"uses" => "EntryController@search"
		] );

		Route::get( "entry/{id}", [
			"as"   => "entry/show",
			"uses" => "EntryController@show"
		] );

		Route::post( "entry", [
			"as"   => "entry/store",
			"uses" => "EntryController@store"
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

	} );

	// -------------------------------------------------------
	// Login
	//---------------------------------------------------------

	Route::post( "login", [
		"as"   => "login/index",
		"uses" => "LoginController@index"
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

Route::get( "entry2/fixfile", [
	"as"   => "entry2/updateFile",
	"uses" => "EntryController@updateFile"
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
	$swagger = new Swagger( $_ENV[ 'PATH' ] . '/app/controllers' );
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
	$swagger = new Swagger( $_ENV[ 'PATH' ] . '/app/controllers' );
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

App::missing( function ( $exception )
{

	return Response::make( [ 'error' => 'Endpoint not found' ], 404 );
} );

