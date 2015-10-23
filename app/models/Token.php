<?php

class Token extends \Eloquent {

	protected $primaryKey = "token_id";

	// Use fillable as a white list
    protected $fillable = array('token_id', 'token_value','token_user_id', 'token_created_date', 'token_valid_until', 'token_type', 'token_device_registration_id');
    public $timestamps = false;


    public function user(){
        return $this->hasOne('User', 'user_id', 'token_user_id');
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

}