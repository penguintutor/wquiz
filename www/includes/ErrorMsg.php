<?php

// class to hold an individual error msg
// must have Errors class included to provide constants?
class ErrorMsg 
{
	private $error_num;
	private $error_text;
	
	public function __construct($error_num, $error_txt)
	{
		$this->error_num = $error_num;
		$this->error_txt = $error_txt;
	}

	// gets msgs in a human readable format (num - text)
	// error_level is an optional parameter	
	// no error level return all
	// with int passed only return if error_num is less than the defined level
	public function getMsg ($error_level = INFO_LEVEL)
	{
		if ($this->error_num < $error_level) {return $this->error_num." - ".$this->error_txt;}
		else {return "";}
	}
	
	public function getLevel ()
	{
		return $this->error_num;
	}
	
}
	
