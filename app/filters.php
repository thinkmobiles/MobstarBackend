<?php

use MobStar\SnsHelper;

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
	$key =  Request::header("X-API-KEY");

	if(!$key)
	{
        $return = ['error' => 'No API Key provided.'];
		$status_code = 401;
		return Response::make($return, $status_code);
    }

    $check = Key::where('key_value', '=', $key)->count();

    if(!$check)
    {
    	$return = ['error' => 'Invalid API Key provided.'];
		$status_code = 401;
		return Response::make($return, $status_code);
	}


});



Route::filter('logged_in', function()
{

	$token =  Request::header("X-API-TOKEN");

	$key =  Request::header("X-API-KEY");
//	$value = DB::table('tokens')->where('token_value', $token)->first()->api_key;

	if(!$token)
	{
        $return = ['error' => 'No token provided.'];
		$status_code = 401;
		return Response::make($return, $status_code);
    }

    $token = Token::where('token_value', '=', $token)->first();

    if(!$token)
    {
    	$return = ['error' => 'Invalid token provided.'];
		$status_code = 401;
		return Response::make($return, $status_code);
	}
// Disable Token Check
// 	else if($token->token_valid_until < date('Y-m-d H:i:s'))
//     {
//     	$return = ['error' => 'Token expired.'];
// 		$status_code = 401;
// 		return Response::make($return, $status_code);
// 	}

	else{
		$oldApiKey = $token->api_key;

		if($key !== $oldApiKey){

//			$token =  Request::header("X-API-TOKEN");
			$app_version = DB::table('api_keys')->where('key_value', $key)->first()->version;
			$token->api_key = $key;
			$token->token_app_version = $app_version;
//			DB::table('tokens')->where('token_value', $token)->update(array('token_app_version' => $app_version));

		}

		$token->token_valid_until = date("Y-m-d H:i:s", strtotime("now + 1 hour"));
		$token->save();

		if( $token->token_app_version >= 3 ) {
		    if( $token->token_is_subscribed == 0 ) {
		        SnsHelper::subscribeSession( $token );
		    }
		}
	}


});


Route::filter('auth.basic', function()
{
	return Auth::basic();
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() != Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});


Route::filter('admin', function()
{
	$key =  Session::get("pass");

	if(!$key || $key == '')
	{
		return Redirect::to('admin/login');
	}

	$token = Token::where('token_value', '=', $key)->whereIn('token_user_id', [301, 429, 311, 307])->first();

	if(!$token)
	{
		$return = ["error"=> "Endpoint not found "];
		$status_code = 404;
		return Response::make($return, $status_code);
	}

});