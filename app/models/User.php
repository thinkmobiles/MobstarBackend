<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends \Eloquent implements UserInterface, RemindableInterface
{

	protected $table = "users";
	protected $primaryKey = "user_id";
	// Use fillable as a white list
	protected $fillable = array( 'user_name', 'user_email', 'user_display_name', 'user_full_name', 'user_password', 'user_twitter_id', 'user_google_id' );
	protected $guarded = array( 'user_user_group' );
	protected $hidden = array( 'user_password' );

	public function group()
	{
		return $this->hasOne( 'Group', 'user_group_id', 'user_user_group' );
	}

	public function entries()
	{
		return $this->hasMany( 'Entry', 'entry_user_id', 'user_id' );
	}

	public function TwitterUser()
	{
		return $this->hasOne( 'TwitterUser', 'twitter_user_id', 'user_twitter_id' );
	}

	public function GoogleUser()
	{
		return $this->hasOne( 'GoogleUser', 'google_user_id', 'user_google_id' );
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

}