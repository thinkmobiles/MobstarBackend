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

	$return['userGroup'] = $user->group->user_group_name;

	return $return;

}

?>