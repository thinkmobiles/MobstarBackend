<?php

class DatabaseSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

		$faker = Faker\Factory::create();
		for ($i = 0; $i < 300; $i++)
		{
		  $message = Message::create(array(
		    'message_sender_id' 		=> $faker->randomNumber(1,301),
		    'message_recipient_id' 		=> $faker->randomNumber(1,301),
		    'message_body'				=> $faker->text(600),
		    'message_created_date'		=> $faker->dateTime('now'),
		  ));
		}
	}

}

