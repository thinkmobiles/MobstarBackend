<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends \Eloquent implements UserInterface, RemindableInterface
{

	protected $table = "users";
	protected $primaryKey = "user_id";
	// Use fillable as a white list
	protected $fillable = array( 'user_name', 'user_email', 'user_display_name', 'user_full_name', 'user_password', 'user_twitter_id', 'user_google_id', 'user_password', 'user_profile_image', 'user_cover_image', 'user_facebook_id', 'user_tagline', 'user_rank', 'user_entry_rank' );
	protected $guarded = array( 'user_user_group' );
	protected $hidden = array( 'user_password' );

	public function entries()
	{
		return $this->hasMany( 'Entry', 'entry_user_id', 'user_id' );
	}
	public function ProfileContent()
	{
		return $this->hasMany( 'ProfileContent', 'content_user_id', 'user_id' );
	}
	public function TwitterUser()
	{
		return $this->hasOne( 'TwitterUser', 'twitter_user_id', 'user_twitter_id' );
	}

	public function GoogleUser()
	{
		return $this->hasOne( 'GoogleUser', 'google_user_id', 'user_google_id' );
	}

	public function FacebookUser()
	{
		return $this->hasOne( 'FacebookUser', 'facebook_user_id', 'user_facebook_id' );
	}

	public function Stars()
	{
		return $this->hasMany( 'Star', 'user_star_user_id', 'user_id' )->orderBy('user_star_created_date', 'DESC');
	}

	public function StarredBy()
	{
		return $this->hasMany( 'Star', 'user_star_star_id', 'user_id' )->orderBy('user_star_created_date', 'DESC');
	}

	public function getAuthIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->user_password;
	}

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return $this->user_email;
	}

	public function getRememberToken()
	{
		return $this->remember_token;
	}

	public function setRememberToken( $value )
	{
		$this->remember_token = $value;
	}

	public function getRememberTokenName()
	{
		return 'remember_token';
	}


	public function getContinentFilter()
	{
	    if( $this->user_continent_filter )
	    {
	        $filter = json_decode( $this->user_continent_filter );
	        if( ! $filter ) $filter = array();
	    }
	    else
	    {
	        $filter = array();
	    }

	    return $filter;
	}


	public function setContinentFilter( array $filter )
	{
	    $this->user_continent_filter = json_encode( $filter );
	}


	public function oneUser( $user, $session, $includeStars = false )
	{

		$return = [ 'id'           => $user->user_id,
					'userName'     => $user->user_name,
					'displayName'  => $user->user_display_name,
					'fullName'     => $user->user_full_name,
					'email'        => $user->user_email,
					'tagLine'      => (!empty($user->user_tagline)) ? $user->user_tagline : '' ,
					'bio'      => (!empty($user->user_bio)) ? $user->user_bio : '',
					'profileImage' => ( !empty( $user->user_profile_image ) )
							? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '',
					'profileCover' => ( !empty( $user->user_cover_image ) )
							? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '',
		];

		if($session->token_user_id != $user->user_id)
			$return[ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $user->user_id )->count();

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


	public function save( array $options = array() )
	{
	  if( is_null( $this->user_bio ) ) $this->user_bio = '';

	  parent::save( $options );
	}
}