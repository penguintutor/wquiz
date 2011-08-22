<?php
/*** 
Handles settings from the 
_settings table in mysql database
- created as a singleton, but needs to be passed mysql database object after constructor
***/


class Settings
{
	private static $_instance;
	
	
    private $qdb_object;
    private $settings = array ();
    
    public function __construct ()
    {
    	
    }
    
    
    // Uses singleton pattern to ensure only one exists
    public static function getInstance() 
    {
        if (empty(self::$_instance)) {self::$_instance = new Settings ();}
        return self::$_instance;
    }
    
    
    /* needs to be passed the database object for class Database */
    // stores the qdb_object for future use
    public function loadSettings ($qdb_object) 
    {
    	$this->qdb_object = $qdb_object;
    	
    	// get the current settings from the database
    	$this->settings = $this->qdb_object->getSettingsAll();
    	// perform error checking
    	if (isset ($this->settings['ERRORS']) && ($this->settings['ERRORS'] != ''))
    	{
    		// fatal error as we need these settings for everything else to work
    		$err = Errors::getInstance();
    		$err->errorEvent(ERROR_SETTINGS, "Error loading settings ".$this->settings['ERRORS']);
    	}
    	
    }
    
    public function getSetting($key)
    {
    	if (isset ($this->settings[$key])) {return $this->settings[$key];}
    	else {return "";}
    }

    
    
}
?>
