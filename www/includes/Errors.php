<?php

// Error classes - each category is based on ERROR, but with +100 
// 101-199 = FATAL
// 201-299 = WARNING
// 301-399 = DEBUG
// 401-499 = INFO

//note 100, 200 etc. are defined as the error levels

define ('ERROR_CFG', 101);				// Error loading / reading config file
define ('ERROR_QUESTION', 102);			// Error with question
define ('ERROR_SETTINGS', 103);			// Error with settings table
define ('ERROR_EXTERNAL', 104);			// Error loading external file (eg. template)
define ('ERROR_SESSION', 105);			// Error with session (eg. no session where required)
define ('ERROR_SECURITY', 106);			// Error with user date failing security check
define ('ERROR_PARAMETER', 107);		// Error with parameter provided by the customer
define ('ERROR_QUIZSTATUS', 108);		// Error with parameter provided by the customer
define ('ERROR_QUIZQUESTIONS', 109);	// insufficient questions etc.
define ('ERROR_DATABASE', 110);			// Other database error
define ('ERROR_LEVEL', 200);
define ('WARNING_CFG', 201);
define ('WARNING_QUESTION', 202);
define ('WARNING_SETTINGS', 203);	
define ('WARNING_EXTERNAL', 204);
define ('WARNING_SESSION', 205);
define ('WARNING_SECURITY', 206);
define ('WARNING_PARAMETER', 207);
define ('WARNING_QUIZSTATUS', 208);
define ('WARNING_QUIZQUESTIONS', 209);
define ('WARNING_DATABASE', 210);		
define ('WARNING_LEVEL', 300);
define ('DEBUG_CFG', 301);
define ('DEBUG_QUESTION', 302);
define ('DEBUG_SETTINGS', 303);
define ('DEBUG_EXTERNAL', 304);
define ('DEBUG_SESSION', 305);
define ('DEBUG_SECURITY', 306);
define ('DEBUG_PARAMETER', 307);
define ('DEBUG_QUIZSTATUS', 308);
define ('DEBUG_QUIZQUESTIONS', 309);
define ('DEBUG_DATABASE', 310);
define ('DEBUG_LEVEL', 400);
define ('INFO_CFG', 401);
define ('INFO_QUESTION', 402);
define ('INFO_SETTINGS', 403);
define ('INFO_EXTERNAL', 404);
define ('INFO_SESSION', 405);
define ('INFO_SECURITY', 406);
define ('INFO_PARAMETER', 407);
define ('INFO_QUIZSTATUS', 408);
define ('INFO_QUIZQUESTIONS', 409);
define ('INFO_DATABASE', 410);
define ('INFO_LEVEL', 500);

require_once ($include_dir."ErrorMsg.php");

class Errors 
{
    private static $_instance;
    private $events= array();
    
    public function __construct () 
    {

    }
    
    // Uses singleton pattern to ensure only one exists
    public static function getInstance() 
    {
        if (empty(self::$_instance)) {self::$_instance = new Errors ();}
        return self::$_instance;
    }
    
    public function errorEvent ($error_num, $error_txt) 
    {
    	// handle fatals first as we don't need to store - we just die
        if ($error_num < ERROR_LEVEL)
        {
        	// die - if PHP warnings on then users will see this
        	// if not then it will just go into log
        	// get previous errors / info to include in output
        	// as this was fatal we provide all previous entries as they may be relevant
        	$previous_events = $this->listEvents(INFO_LEVEL);
        	// note previous_events will already include a trailing \n
        	die ($previous_events.$error_num." - ".$error_txt);
        }
        
        // store message
        $this->events[] = new ErrorMsg ($error_num, $error_txt);
        
    }                
    
    // returns number of Events at error level or above
    public function numEvents ($error_level)
    {
    	$num_events = 0;
    	foreach ($this->events as $this_event)
    	{
    		if ($this_event->getLevel() < $error_level) {$num_events++;}
    	}
    	return $num_events;
    }
    
    // returns with line breaks 
    // min_error_level is the minimum level which error was issued as  
    public function listEvents ($error_level) 
    {
    	$return_string = '';
    	foreach ($this->events as $this_event)
    	{
    		$this_text = $this_event->getMsg($error_level);
    		// only add if contains text
    		if ($this_text != '') {$return_string .= $this_text."\n";}
    	}
    	return ($return_string);
    }
}
?>

