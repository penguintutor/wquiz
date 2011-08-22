<?php
/*** 
Handles the PHP session - treats as raw variables
***/
// Extended by QuizSession.php


class PHPSession
{

	// establish session
    public function __construct () 
    {
    	// start session to get the session uid
    	session_start();
    }
    
    // returns a variable
    public function getValue ($key) 
    {
    	if(isset($_SESSION[$key])) {return $key;}
    	else {return "";}
    }
    
    // returns a variable
    public function setValue ($key, $value) 
    {
    	$_SESSION[$key] = $value;
    }
    
    public function unsetValue ($key)
    {
    	unset($_SESSION[$key]);
    }

	// note if we destroy the session there is no way of recovering and recreating on this page
	// all variables will be lost
	// normally just leave the PHP session 
    public function destroySession ()
    {
    	session_destroy();
    }
    
}
?>
