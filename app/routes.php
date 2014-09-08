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

Route::get( 'debug/', function ()
{
//	var_dump(getenv('ENV'));
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

	echo 'sonus:';
	var_dump( Sonus::getSupportedFormats() );
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

		Route::post( "message/", [
			"as"   => "message/store",
			"uses" => "MessageController@store"
		] );
		Route::delete( "message/", [
			"as"   => "message/destroy",
			"uses" => "MessageController@destroy"
		] );

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

App::missing( function ( $exception )
{
	return Response::make( [ 'error' => 'Endpoint not found' ], 404 );
} );

