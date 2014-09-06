<?php

class WinningEntry extends \Eloquent
{
	protected $fillable = [ 'winning_entry_id', 'winning_entry_entry_id', 'winning_entry_awarded_by' ];
	protected $table = "winning_entries";

	public function entry()
	{
		return $this->belongsTo( 'Entry', 'winning_entry_entry_id', 'entry_id' );
	}
}