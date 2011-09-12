<?php
/* Provides navigation buttons used in question.php */
// assumes we are not going to override the settings unless - override specifically called.


class QuestionNavigation
{
	
	// stores settings - these are arrays listing what navigation buttons enabled and their labels
	private $labels = array();
	private $enabled = array();
	// minimum and maximum values - can be passed via constructor or by setting using setMinMax
	private $min, $max;
	
	// constructor
	// min is first question (normally 1)
	// max is last question (number of questions)
	// -1 means no min / max (should not be used, but question.php will handle question out-of-range anyway)
	public function __construct ($min = -1, $max = -1) 
    {
    	// get the settings class
		$settings = Settings::getInstance();
		// Text labels (note these are also the submit values
		$this->labels = unserialize ($settings->getSetting('buttons_navigation_labels'));
		$this->enabled = unserialize ($settings->getSetting('buttons_navigation_enabled'));
		$this->min = $min;
		$this->max = $max;
    }
	
    // using this function - must provide both min and max
    public function setMinMax ($min, $max)
    {
    	$this->min = $min;
		$this->max = $max;
    }
    
    // Array is 2D array used to override defaults
	// array('labels'=>array('first'=>'first text'...),'enabled'=>array([0]=>'first'...))
	// enabled also determines the ordering
	// Note labels will only override those listed - enabled replaces all the array (any not listed will become disabled)
    public function overrideDefaults ($override_settings)
    {
    	// load label overrides
		if (isset ($override_settings['labels']))
		{
			foreach ($override_settings['labels'] as $key => $value)
			{
				$this->labels[$key] = $value;
			}
		}
		
		// override enabled
		if (isset ($override_settings['enabled']))
		{ 
			$enabled = $override_settings['enabled'];
		}
		
		
    }
    
	
	// show the navigation buttons (form) 
	// current is page we are currently on 
	// Does not add a help button - add this seperately

	public function showNavigation ($current)
	{
		
		foreach ($this->enabled as $this_button)
		{
			// check matching label is defined - if not add as a warning and move to next
			if (!isset ($this->labels[$this_button])) 
			{
				$err =  Errors::getInstance();
				$err->errorEvent(WARNING_INTERNAL, "No label provided for button $this_button - ignoring");
				continue;
			}
			print "<input type=\"submit\" name=\"nav\" value=\"".$this->labels[$this_button]."\">\n";
		}
		
	}
}

?>
