<?php

function getUserProfile( $user, $session )
{

	$return[ 'token' ] = $session->token_value;
	$return[ 'userId' ] = $user->user_id;
	$return[ 'userName' ] = $user->user_name;
	$return[ 'userFullName' ] = $user->user_full_name;

	if( ( empty( $user->user_display_name ) ) && $session->token_type != 'Native' )
	{
		if( $session->token_type == 'Twitter' )
		{
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->TwitterUser->twitter_user_display_name;
			}
		}
		elseif( $session->token_type == 'Facebook' )
		{
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->FacebookUser->facebook_user_display_name;
			}
		}
		elseif( $session->token_type == 'Google' )
		{
			//var_dump($user->GoogleUser);
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->GoogleUser->google_user_display_name;
			}
			if( !isset( $user->user_name ) )
			{
				$return[ 'userName' ] = $user->GoogleUser->google_user_user_name;
			}
		}
	}
	else
	{
		$return[ 'userDisplayName' ] = $user->user_display_name;
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

				$stars[ ] = [ 'starId'       => $star->Stars->user_id,
							  'starName'     => $star->Stars->user_display_name,
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
				$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
								  'starName'     => $starred->User->user_display_name,
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

function oneEntry( $entry, $session, $includeUser = false )
{

	$current = array();

	$up_votes = 0;
	$down_votes = 0;
	foreach( $entry->vote as $vote )
	{
		if( $vote->vote_up == 1 && $vote->vote_deleted == 0 )
		{
			$up_votes++;
		}
		elseif( $vote->vote_down == 1 && $vote->vote_deleted == 0 )
		{
			$down_votes++;
		}

	}

	$current[ 'id' ] = $entry->entry_id;
	$current[ 'category' ] = $entry->category->category_name;
	$current[ 'type' ] = $entry->entry_type;

	if( $includeUser )
	{
		$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
		$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
		$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
		$current[ 'user' ][ 'email' ] = $entry->User->user_email;
		$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->User->user_profile_image ) )
			? $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
		$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
			? $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
		$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();
	}

	$current[ 'name' ] = $entry->entry_name;
	$current[ 'description' ] = $entry->entry_description;
	$current[ 'created' ] = $entry->entry_created_date;
	$current[ 'modified' ] = $entry->entry_modified_date;

	$current[ 'tags' ] = array();
	foreach( $entry->entryTag as $tag )
	{
		$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
	}

	//break;

	$current[ 'entryFiles' ] = array();
	foreach( $entry->file as $file )
	{
		$url = 'http://' . $_ENV[ 'URL' ] . '/' . $file->entry_file_location . "/" . $file->entry_file_name . "." . $file->entry_file_type;
		$current[ 'entryFiles' ][ ] = [
			'fileType' => $file->entry_file_type,
			'filePath' => $url ];
	}

	$current[ 'upVotes' ] = $up_votes;
	$current[ 'downVotes' ] = $down_votes;
	$current[ 'rank' ] = $entry->entry_rank;
	$current[ 'language' ] = $entry->entry_language;
	// /print_r($entry);

	if( $entry->entry_deleted )
	{
		$current[ 'deleted' ] = true;
	}
	else
	{
		$current[ 'deleted' ] = false;
	}

	return $current;
}

?>