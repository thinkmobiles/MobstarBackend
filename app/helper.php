<?php

use Aws\S3\S3Client;
use Aws\Sns\SnsClient;

// prevent multiple inclusion
if (defined( 'HELPER_INCLUDED_EYDTTEYGD' ))
{
  return;
}
else
{
//do not delete next line
define( 'HELPER_INCLUDED_EYDTTEYGD', true );

// start your helper funcs from here

function getUserProfile( $user, $session, $normal = false )
{
	$client = getS3Client();

	$profileImage = '';
	$profileCover = '';
	if($normal)
	{
		$profileImage = ( isset( $user->user_profile_image ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '';
		$profileCover = ( isset( $user->user_cover_image ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '';
	}
	else
	{
		$profileImage = ( isset( $user->user_profile_image ) ) ? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+720 minutes' ) : '';
		$profileCover = ( isset( $user->user_cover_image ) )   ? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+720 minutes' ) : '';
	}

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
	elseif( ( !empty( $user->user_display_name ) ) && $session->token_type != 'Native' )
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

	$return[ 'userTagline' ] = (!empty($user->user_tagline)) ? $user->user_tagline : '';
	$return[ 'userBio' ] = (!empty($user->user_bio)) ? $user->user_bio : '';

	/*$return[ 'profileImage' ] = ( isset( $user->user_profile_image ) )
		? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+60 minutes' ) : '';

	$return[ 'profileCover' ] = ( isset( $user->user_cover_image ) )
		? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+60 minutes' ) : '';
	*/
	$return[ 'profileImage' ] = $profileImage;
	$return[ 'profileCover' ] = $profileCover;
	return $return;

}

function oneUser( $user, $session, $includeStars = false, $normal = false )
{
	$client = getS3Client();
	$profileImage = '';
	$profileCover = '';
	if($normal)
	{
		$profileImage = ( isset( $user->user_profile_image ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '';
		$profileCover = ( isset( $user->user_cover_image ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '';
	}
	else
	{
		$profileImage = ( isset( $user->user_profile_image ) ) ? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+720 minutes' ) : '';
		$profileCover = ( isset( $user->user_cover_image ) )   ? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+720 minutes' ) : '';
	}
	$return = [ 'id'           => $user->user_id,
				'email'        => $user->user_email,
				'tagLine'      => (!empty($user->user_tagline)) ? $user->user_tagline : '',
				'bio'      	   => (!empty($user->user_bio)) ? $user->user_bio :'',
				'usergroup'      	   => (!empty($user->user_user_group)) ? $user->user_user_group :'',
				/*'profileImage' => ( isset( $user->user_profile_image ) )
					? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+60 minutes' ) : '',
				'profileCover' => ( isset( $user->user_cover_image ) )
					? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+60 minutes' ) : '',*/
				'profileImage' => $profileImage	,
				'profileCover' => $profileCover,
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
		$iAmStarFlag = Star::where( 'user_star_user_id', '=', $user->user_id )->where( 'user_star_star_id', '=', $session->token_user_id )->where( 'user_star_deleted', '!=', '1' )->count();
		if($iAmStarFlag > 0)
		{
			$return[ 'iAmStar' ] = 1;
		}
		else
		{
			$return[ 'iAmStar' ] = 0;
		}
	}

	$stars = [ ];
	$starredBy = [ ];
	$lookup_array=[ ];

	if( $includeStars )
	{
		//mail('anil@spaceotechnologies.com','Star',print_r($user->Stars, true));
		foreach( $user->Stars as $star )
		{

			if( !in_array( $star->Stars->user_id, $lookup_array ) )
			{
				if( $star->user_star_deleted == 0 )
				{
					$starNames = [];
					$starNames = userDetails($star->Stars);

					/*
					// Stats
					$entries = Entry::where('entry_user_id', '=', $star->Stars->user_id)->get();
					$stats = 100000;
					foreach($entries as $entry)
					{
						if(($entry->entry_category_id != 7 || $entry->entry_category_id != 8) && $entry->entry_deleted == 0)
						{
							if( $entry->entry_rank < $stats && $entry->entry_rank != 0 )
							{
								$stats = $entry->entry_rank;
							}
						}
					}
					if ($stats == 100000)
						$stats = 0;
					// End Stats
					// Rank
					$entries_star = DB::table('entries')
					->select('entries.*')
					->join('users', 'entries.entry_user_id', '=', 'users.user_id')
					->where('entries.entry_deleted', '=', '0')
					->where(function($query)
						{
							$query->where('entries.entry_rank', '!=', 0);
						})
					->orderBy( 'entry_rank', 'asc' )
					->get();
					$users = [ ];
					$tmp_star[ 'talents' ] = [];

					$rank = 1;

					foreach( $entries_star as $entry_star )
					{
						if(($entry_star->entry_category_id != 7 || $entry_star->entry_category_id != 8) && $entry_star->entry_deleted == 0)
						{
							if( !in_array( $entry_star->entry_user_id, $users ) )
							{
								$User = User::where('user_id' , '=', $entry_star->entry_user_id)->first();
								$user1[ 'rank' ] = $rank;
								$user1[ 'id' ] = $User->user_id;
								$tmp_star[ 'talents' ][ ][ 'talent' ] = $user1;
								$users[ ] = $entry_star->entry_user_id;
								$rank++;
							}
						}
					}
					$myrank = 0;
					for($i=0;$i<count($tmp_star['talents']);$i++)
					{
						if($tmp_star['talents'][$i]['talent']['id'] == $star->Stars->user_id)
						{
							$myrank = $tmp_star['talents'][$i]['talent']['rank'];
						}
					}
					// End Rank
					*/


						$lookup_array[ ]= $star->Stars->user_id;

						$stars[ ] = [ 'starId'       => $star->Stars->user_id,
									  //'starName'     => $starNames['displayName'],
									  'starName'     => ( isset( $starNames['displayName'] ) ) ? $starNames['displayName'] : '',
									  'starredDate'  => $star->user_star_created_date,
									  'profileImage' => ( isset( $star->Stars->user_profile_image ) )
										  ? $client->getObjectUrl( Config::get('app.bucket'), $star->Stars->user_profile_image, '+720 minutes' )
										  : '',
									  'profileCover' => ( isset( $star->Stars->user_cover_image ) )
										  ? $client->getObjectUrl( Config::get('app.bucket'), $star->Stars->user_cover_image, '+720 minutes' ) : '',
									  //'rank'     => $myrank,
									  'rank'     => DB::table('users')->where( 'user_id', '=', $star->Stars->user_id )->pluck('user_rank'),
									  //'stats'     => $stats,
									  'stat'     => DB::table('users')->where( 'user_id', '=', $star->Stars->user_id )->pluck('user_entry_rank'),
						];

				}
			}
		}


		$starredBy = [ ];

		foreach( $user->StarredBy as $starred )
		{
			if( $starred->user_star_deleted == 0 )
			{
				$starNames = [];
				$starNames = userDetails($starred->User);

				$starredBy[ ] = [ 'starId'       => ( isset( $starred->User->user_id ) ) ? $starred->User->user_id : '',
								  'starName'     => ( isset( $starNames['displayName'] ) ) ? $starNames['displayName'] : '',
								  'starredDate'  => $starred->user_star_created_date,
								  'profileImage' => ( isset( $starred->User->user_profile_image ) )
									  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->User->user_profile_image, '+720 minutes' )
									  : '',
								  'profileCover' => ( isset( $starred->User->user_cover_image ) )
								  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->User->user_cover_image, '+720 minutes' ) : '',
				];
			}
		}

	}

	$return[ 'stars' ] = $stars;

	$return[ 'starredBy' ] = $starredBy;
	$excludeCategory = [7,8];
	$entries = Entry::with('vote')->where('entry_user_id', '=', $user->user_id)->whereNotIn( 'entry_category_id', $excludeCategory )->where('entry_deleted', '=', '0')->get();

	$rank = 0;
	//$rank = 100000;
	$votes = 0;
	foreach($entries as $entry)
	{
			/*if($entry->entry_rank < $rank && $entry->entry_rank != 0)
				$rank = $entry->entry_rank;*/
			foreach($entry->vote as $vote)
			{
				$tmp[]  = $vote->vote_entry_id;
				if($vote->vote_deleted == 0 && $vote->vote_up == 1)
					$votes++;
			}
	}

	/*if ($rank == 100000)
		$rank = 0;
	$return['rank'] = $rank;
	*/
	$return['rank'] = $user->user_rank;
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
	$current[ 'totalviews' ] = $entry->entryViews->count();
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
		$signedUrl = $client->getObjectUrl( Config::get('app.bucket'), $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
		$current[ 'entryFiles' ][ ] = [
			'fileType' => $file->entry_file_type,
			'filePath' => $signedUrl ];

		$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
			$client->getObjectUrl( Config::get('app.bucket'), 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
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
  if( Config::get('app.disable_sns') ) { // SNS disabled in config
    // get backtrace
    ob_start();
    debug_print_backtrace();
    $backtrace = ob_get_clean();
    error_log( 'called getSNSClient while SNS is disabled in configuration. Stack backtrace is: '.$backtrace );
    return;
  }
  return false; // for testing
	$config = array(
		'key'    => Creds::ENV_KEY,
		'secret' => Creds::ENV_SECRET,
		'region' => 'eu-west-1'
	);

	return SnsClient::factory( $config );
}

function userDetails($user)
{
	$return = [];
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
	return $return;
}
function getusernamebyid($userid)
{
	$userdata = User::find($userid);

	if(!empty($userdata->user_display_name ))
	{
		return $userdata->user_display_name;
	}
	elseif(!empty($userdata->user_facebook_id))
	{
		$facebookuserdata = FacebookUser::find($userdata->user_facebook_id);
		return $facebookuserdata->facebook_user_display_name;
	}
	elseif(!empty($userdata->user_twitter_id))
	{
		$twitterkuserdata = TwitterUser::find($userdata->user_twitter_id);
		return $twitterkuserdata->twitter_user_display_name;
	}
	elseif(!empty($userdata->user_google_id))
	{
		$googleuserdata = GoogleUser::find($userdata->user_google_id);
		return $googleuserdata->google_user_display_name;
	}
	else
	{
		return 'Guest';
	}
}
function particUser( $user, $session, $includeStars = false )
{
	$client = getS3Client();

	$return = [ 'userId'           => $user->user_id,
				'profileImage' => ( isset( $user->user_profile_image ) )
					? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+720 minutes' ) : '',
				'profileCover' => ( isset( $user->user_cover_image ) )
								  ? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+720 minutes' ) : '',
	];

	if( ( $user->user_display_name == '' ) || ( is_null( $user->user_name ) ) || ( is_null( $user->user_email ) ) )
	{
		if( $user->user_facebook_id != 0 )
		{
			$return[ 'displayName' ] = $user->FacebookUser->facebook_user_display_name;
		}
		elseif( $user->user_twitter_id != 0 )
		{
			$return[ 'displayName' ] = $user->TwitterUser->twitter_user_display_name;
		}
		elseif( $user->user_google_id != 0 )
		{
			$return[ 'displayName' ] = $user->GoogleUser->google_user_display_name;
		}
	}
	else
	{
		$return[ 'displayName' ] = $user->user_display_name;
	}
	return $return;
}


function getMediaDurationInSec( $filename )
{
  $bin_ffprobe = Config::get( 'app.bin_ffprobe' );
  $cmd = $bin_ffprobe.' '.$filename.' 2>&1';

  $output = `$cmd`;

  $duration = false;
  if( preg_match( '|Duration:\s(\d\d):(\d\d):(\d\d)\.(\d\d),|m', $output, $matches ) )
  {
    $duration = $matches[1]*3600 + $matches[2]*60 + $matches[3];
    if( $matches[4] >= 50 ) $duration++;
  }

  return $duration;
}


function getMediaInfo( $filename )
{
  // get ouput from ffprobe
  $bin_ffprobe = Config::get( 'app.bin_ffprobe' );
  $cmd = $bin_ffprobe.' '.$filename.' 2>&1';

  $output = `$cmd`;

  $info = array();

  // get media type
  $info['type'] = 'unknown';
  if( preg_match_all( '#^\s*Stream.*(Video|Audio).*$#m', $output, $matches ) )
  {
    $streamTypes = $matches[1];
    unset( $matches );
    if( in_array( 'Video', $streamTypes ) ) $info['type'] = 'video';
    elseif( in_array( 'Audio', $streamTypes ) ) $info['type'] = 'audio';
  }

  // get media duration
  $info['duration'] = false;
  if( preg_match( '#^\s*Duration:\s(\d\d):(\d\d):(\d\d)\.(\d\d),#m', $output, $matches ) )
  {
    $duration = $matches[1]*3600 + $matches[2]*60 + $matches[3];
    if( $matches[4] >= 50 ) $duration++;
    $info['duration'] = $duration;
  }

  // get video rotation
  $info['rotate'] = false;
  if( $info['type'] == 'video' )
  {
    if( preg_match( '#^\s*rotate\s*:\s(\d+)\s*$#m', $output, $matches ) )
    {
      $info['rotate'] = $matches[1];
    } else {
      $info['rotate'] = 0;
    }
  }

  return $info;
}


function makeVideoThumbnail( $videoPath, $thumbnailPath, $videoInfo = false  )
{
  $transpose = '';
  $rotation_angel = '';
  $display_angel = '';

  if( empty( $videoInfo ) ) getMediaInfo( $videoPath );

  if( empty( $videoInfo ) )
  {
    error_log( 'can not get media info from file: '.$videoPath );
  }
  else
  {
    switch( $videoInfo['rotate'] )
    {
      case 90:
        $transpose = '';
        $rotation_angel = '90';
        break;
      case 180:
        $transpose = '';
        $rotation_angel = '180';
        break;
      case 270:
        $transpose = '';
        $rotation_angel = '270';
        break;
    }
  }

  $cmd = Config::get( 'app.bin_ffmpeg' )
    . ' -i ' . $videoPath
    . $transpose
    . ' -vframes 1 -an -s 300x300 -ss 00:00:00.10 '
    . $thumbnailPath;

  shell_exec( $cmd );
}


// end of prevent multiple inclusion block
}
