<?php

use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  basePath="http://api.mobstar.com"
 * )
 */
class SettingsController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/settings/account",
	 *   description="Operations for settings",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get the current users linked accounts details",
	 *       notes="Returns user account details for the logged in user",
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No entries found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function account()
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find( $session->token_user_id );
		$return[ 'user' ][ 'id' ] = $session->token_user_id;

		if( $user->user_facebook_id != 0 )
		{
			$facebook_user = FacebookUser::find( $user->user_facebook_id );

			$return[ 'user' ][ 'facebook' ] = [
				'id'          => $facebook_user->facebook_user_facebook_id,
				'displayName' => $facebook_user->facebook_user_display_name,
				'userName'    => $facebook_user->facebook_user_user_name,
				'email'       => $facebook_user->facebook_user_email,
				'gender'      => $facebook_user->facebook_user_gender,
				'fullName'    => $facebook_user->facebook_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'facebook' ] = false;
		}

		if( $user->user_twitter_id != 0 )
		{
			$twitter_user = TwitterUser::find( $user->user_twitter_id );

			$return[ 'user' ][ 'twitter' ] = [
				'id'          => $twitter_user->twitter_user_twitter_id,
				'displayName' => $twitter_user->twitter_user_display_name,
				'userName'    => $twitter_user->twitter_user_user_name,
				'fullName'    => $twitter_user->twitter_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'twitter' ] = false;
		}

		if( $user->user_google_id != 0 )
		{
			$google_user = GoogleUser::find( $user->user_google_id );

			$return[ 'user' ][ 'google' ] = [
				'id'          => $google_user->google_user_google_id,
				'displayName' => $google_user->google_user_display_name,
				'userName'    => $google_user->google_user_user_name,
				'fullName'    => $google_user->google_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'google' ] = false;
		}

		$response = Response::make( $return, 200 );

		return $response;
	}

	public function addAccount()
	{
		$type = Input::get( 'type' );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find( $session->token_user_id );

		try
		{
			switch( $type )
			{
				case "facebook":
					$facebook_user = FacebookUser::firstOrNew( array( 'facebook_user_facebook_id' => Input::get( 'userId' ) ) );

					$facebook_user->facebook_user_display_name = Input::get( 'displayName' );
					$facebook_user->facebook_user_user_name = Input::get( 'userName' );
					$facebook_user->facebook_user_email = Input::get( 'email' );
					$facebook_user->facebook_user_gender = Input::get( 'gender' );
					$facebook_user->facebook_user_full_name = Input::get( 'fullName' );

					$facebook_user->save();

					$user->user_facebook_id = $facebook_user->facebook_user_id;
					$user->save();
					$return = [ 'info' => 'Facebook user added' ];
					break;

				case "twitter":
					$twitter_user = TwitterUser::firstOrNew( array( 'twitter_user_twitter_id' => Input::get( 'userId' ) ) );

					$twitter_user->twitter_user_twitter_id = Input::get( 'userId' );
					$twitter_user->twitter_user_display_name = Input::get( 'displayName' );
					$twitter_user->twitter_user_full_name = Input::get( 'fullName' );
					$twitter_user->twitter_user_user_name = Input::get( 'userName' );

					$twitter_user->save();

					$user->user_twitter_id = $twitter_user->twitter_user_id;
					$user->save();
					$return = [ 'info' => 'Twitter user added' ];
					break;

				case "twitter":
					$google_user = GoogleUser::firstOrNew( array( 'google_user_google_id' => Input::get( 'userId' ) ) );

					$google_user->google_user_google_id = Input::get( 'userId' );
					$google_user->google_user_display_name = Input::get( 'displayName' );
					$google_user->google_user_user_name = Input::get( 'userName' );
					$google_user->google_user_full_name = Input::get( 'fullName' );

					$user->user_google_id = $google_user->google_user_id;
					$user->save();
					$return = [ 'info' => 'Google user added' ];

					break;

				default:
					$return = [ 'error' => 'Nothing added - incorrect type, please select "facebook", "twitter" or "google"' ];
			}

		}
		catch( Exception $ex )
		{
			return [ 'error' => "An error occurred, nothing was added" ];
		}

		return $return;
	}
}