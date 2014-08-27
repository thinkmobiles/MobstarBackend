<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFacebookUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function($table)
        {
            $table->integer('user_facebook_id');
            $table->string('user_facebook_display_name');
            $table->string('user_facebook_email');
            $table->string('user_facebook_dob');
            $table->string('user_facebook_gender');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function($table)
        {
            $table->dropColumn('user_facebook_id');
            $table->dropColumn('user_facebook_display_name');
            $table->dropColumn('user_facebook_email');
            $table->dropColumn('user_facebook_dob');
            $table->dropColumn('user_facebook_gender');
        });
	}

}
