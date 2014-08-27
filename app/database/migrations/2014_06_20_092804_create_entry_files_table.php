<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntryFilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('entry_files', function($table)
		{
		    $table->increments('entry_file_id');

			$table->string('entry_file_name', 500);
			$table->integer('entry_file_entry_id')->unsigned();
			$table->string('entry_file_location', 500);
			$table->boolean('entry_file_deleted')->default(0);
			$table->string('entry_file_type');
			$table->datetime('entry_file_created_date');
			$table->datetime('entry_file_updated_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('entry_files');
	}

}
