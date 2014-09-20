<?php

function getUserProfile($user, $session){

	$return['token'] = $session->token_value;
	$return['userId'] = $user->user_id;
	$return['userName'] = $user->user_name;
	$return['userFullName'] = $user->user_full_name;

	if((empty($user->user_display_name)) && $session->token_type != 'Native')
	{
		if($session->token_type == 'Twitter'){
			if(empty($user->user_display_name))
				$return['userDisplayName'] = $user->TwitterUser->twitter_user_display_name;
		}
		elseif($session->token_type == 'Facebook'){
			if(empty($user->user_display_name))
				$return['userDisplayName'] = $user->FacebookUser->facebook_user_display_name;
		}
		elseif($session->token_type == 'Google'){
			//var_dump($user->GoogleUser);
			if(empty($user->user_display_name))
				$return['userDisplayName'] = $user->GoogleUser->google_user_display_name;
			if(!isset($user->user_name))
				$return['userName'] = $user->GoogleUser->google_user_user_name;
		}
	}
	else
	{
		$return['userDisplayName'] = $user->user_display_name;
	}

	return $return;

}

function oneUser( $user, $includeStars = false )
{

	$return = [ 'id'           => $user->user_id,
				'userName'     => $user->user_name,
				'displayName'  => $user->user_display_name,
				'fullName'     => $user->user_full_name,
				'email'        => $user->user_email,
				'profileImage' => ( !empty( $user->user_profile_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '',
				'profileCover' => ( !empty( $user->user_cover_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '',
	];

	if( $includeStars )
	{
		$stars = [ ];

		foreach( $user->Stars as $star )
		{
			if( $star->user_star_deleted == 0 )
			{

				$stars[ ] = [ 'starId'      => $star->Stars->user_id,
							  'starName'    => $star->Stars->user_display_name,
							  'profileImage' => ( !empty( $star->Stars->user_profile_image ) )
									  ? 'http://' . $_ENV[ 'URL' ] . '/' . $star->Stars->user_profile_image : '',
				];

			}
		}

		$return[ 'stars' ] = $stars;

		$starredBy = [ ];

		foreach( $user->StarredBy as $starred )
		{
			if( $starred->user_star_deleted == 0 )
			{
				$starredBy[ ] = [ 'starId'      => $starred->User->user_id,
								  'starName'    => $starred->User->user_display_name,
								  'profileImage' => ( !empty( $starred->User->user_profile_image ) )
										  ? 'http://' . $_ENV[ 'URL' ] . '/' . $starred->User->user_profile_image
										  : '',
				];
			}

		}

		$return[ 'starredBy' ] = $starredBy;
	}

	return $return;
}

?>