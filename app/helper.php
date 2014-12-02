<?php

use Aws\S3\S3Client;
use Aws\Sns\SnsClient;

function getUserProfile( $user, $session )
{
	$client = getS3Client();

	$return[ 'token' ] = $session->token_value;
	$return[ 'userId' ] = $user->user_id;

	if( ( empty( $user->user_display_name ) ) && $session->token_type != 'Native' )
	{
		if( $session->token_type == 'Twitter' )
		{
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->TwitterUser->twitter_user_display_name;
			}
			$return[ 'userName' ] = $user->TwitterUser->twitter_user_user_name;
			$return[ 'fullName' ] = $user->TwitterUser->twitter_user_full_name;

		}
		elseif( $session->token_type == 'Facebook' )
		{
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->FacebookUser->facebook_user_display_name;
			}
			if( empty( $user->user_name ) )
			{
				$return[ 'userName' ] = $user->FacebookUser->facebook_user_user_name;
			}

			$return[ 'fullName' ] = $user->FacebookUser->facebook_user_full_name;

		}
		elseif( $session->token_type == 'Google' )
		{
			//var_dump($user->GoogleUser);
			if( empty( $user->user_display_name ) )
			{
				$return[ 'userDisplayName' ] = $user->GoogleUser->google_user_display_name;
			}
			if( empty( $user->user_name ) )
			{
				$return[ 'userName' ] = $user->GoogleUser->google_user_user_name;
			}

			$return[ 'fullName' ] = $user->GoogleUser->google_user_full_name;
		}
	}
	else
	{
		$return[ 'userDisplayName' ] = $user->user_display_name;
		$return[ 'userName' ] = $user->user_name;
	}

	$return[ 'userTagline' ] = $user->user_tagline;

	$return[ 'profileImage' ] = ( isset( $user->user_profile_image ) )
		? $client->getObjectUrl( 'mobstar-1', $user->user_profile_image, '+10 minutes' ) : '';

	$return[ 'profileCover' ] = ( isset( $user->user_cover_image ) )
		? $client->getObjectUrl( 'mobstar-1', $user->user_cover_image, '+10 minutes' ) : '';

	return $return;

}

function oneUser( $user, $session, $includeStars = false )
{
	$client = getS3Client();

	$return = [ 'id'           => $user->user_id,
				'email'        => $user->user_email,
				'tagLine'      => $user->user_tagline,
				'profileImage' => ( isset( $user->user_profile_image ) )
					? $client->getObjectUrl( 'mobstar-1', $user->user_profile_image, '+10 minutes' ) : '',
				'profileCover' => ( isset( $user->user_cover_image ) )
					? $client->getObjectUrl( 'mobstar-1', $user->user_cover_image, '+10 minutes' ) : '',
	];

	if( ( $user->user_display_name == '' ) || ( is_null( $user->user_name ) ) || ( is_null( $user->user_email ) ) )
	{
		if( $user->user_facebook_id != 0 )
		{
			$return[ 'userName' ] = $user->FacebookUser->facebook_user_user_name;
			$return[ 'displayName' ] = $user->FacebookUser->facebook_user_display_name;
			$return[ 'fullName' ] = $user->FacebookUser->facebook_user_full_name;
		}
		elseif( $user->user_twitter_id != 0 )
		{
			$return[ 'userName' ] = $user->TwitterUser->twitter_user_user_name;
			$return[ 'displayName' ] = $user->TwitterUser->twitter_user_display_name;
			$return[ 'fullName' ] = $user->TwitterUser->twitter_user_full_name;
		}
		elseif( $user->user_google_id != 0 )
		{
			$return[ 'userName' ] = $user->GoogleUser->google_user_user_name;
			$return[ 'displayName' ] = $user->GoogleUser->google_user_display_name;
			$return[ 'fullName' ] = $user->GoogleUser->google_user_full_name;
		}
	}
	else
	{
		$return[ 'userName' ] = $user->user_name;
		$return[ 'displayName' ] = $user->user_display_name;
		$return[ 'fullName' ] = $user->user_full_name;
	}

	if( $session->token_user_id != $user->user_id )
	{
		$return[ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $user->user_id )->where( 'user_star_deleted', '!=', '1' )->count();
	}

	$stars = [ ];
	$starredBy = [ ];

	if( $includeStars )
	{
		$stars = [ ];

		foreach( $user->Stars as $star )
		{

			if( $star->user_star_deleted == 0 )
			{
				$stars[ ] = [ 'starId'       => $star->Stars->user_id,
							  'starName'     => $star->Stars->user_display_name,
							  'starredDate'  => $star->user_display_name,
							  'profileImage' => ( isset( $star->Stars->user_profile_image ) )
								  ? $client->getObjectUrl( 'mobstar-1', $star->Stars->user_profile_image, '+10 minutes' )
								  : '',
				];
			}
		}


		$starredBy = [ ];

		foreach( $user->StarredBy as $starred )
		{
			if( $starred->user_star_deleted == 0 )
			{

				$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
								  'starName'     => $starred->User->user_display_name,
								  'starredDate'  => $starred->user_display_name,
								  'profileImage' => ( isset( $starred->User->user_profile_image ) )
									  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_profile_image, '+10 minutes' )
									  : '',
				];
			}
		}

	}

	$return[ 'stars' ] = $stars;

	$return[ 'starredBy' ] = $starredBy;

	$entries = Entry::with('vote')->where('entry_user_id', '=', $user->user_id)->get();

	$rank = 100000;
	$votes = 0;
	foreach($entries as $entry)
	{
		if($entry->entry_rank < $rank && $entry->entry_rank != 0)
			$rank = $entry->entry_rank;

		foreach($entry->vote as $vote)
		{
			if($vote->vote_deleted == 0)
				$votes++;
		}
	}

	if ($rank == 100000)
		$rank = 0;


	$return['rank'] = $rank;
	$return['fans'] = count($starredBy);
	$return['votes'] = $votes;


	return $return;
}

function oneEntry( $entry, $session, $includeUser = false )
{

	$client = getS3Client();

	$current = array();

	$up_votes = 0;
	$down_votes = 0;
	if(count($entry->vote) != 0)
	{
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
	}
	else{
		$up_votes = 0;
		$down_votes = 0;
	}

	$current[ 'id' ] = $entry->entry_id;
	$current[ 'category' ] = $entry->category->category_name;
	$current[ 'type' ] = $entry->entry_type;

	if( $includeUser )
	{
		$current[ 'user' ] = oneUser( $entry->User, $session );
	}

	$current[ 'name' ] = $entry->entry_name;
	$current[ 'description' ] = $entry->entry_description;
	$current[ 'totalComments' ] = $entry->comments->count();
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
		$signedUrl = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+10 minutes' );
		$current[ 'entryFiles' ][ ] = [
			'fileType' => $file->entry_file_type,
			'filePath' => $signedUrl ];

		$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
			$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+10 minutes' )
			: "";
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

function getS3Client()
{

	$config = array(
		'key'    => Creds::ENV_KEY,
		'secret' => Creds::ENV_SECRET
	);

	return S3Client::factory( $config );
}

function getSNSClient()
{
	$config = array(
		'key'    => Creds::ENV_KEY,
		'secret' => Creds::ENV_SECRET,
		'region' => 'eu-west-1'
	);

	return SnsClient::factory( $config );
}

?>