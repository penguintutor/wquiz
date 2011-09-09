<?php
/* Provides navigation buttons used in question.php */


// show the navigation buttons (form) 
// current is page we are currently on 
// min is first question (normally 1)
// max is last question (number of questions)
// Does not add a help button - add this seperately
// 2D Array is 2D array used to override defaults
// array('labels'=>array('first'=>'first text'...),'enabled'=>array([0]=>'first'...))
// enabled also determines the ordering
// Note labels will only override those listed - enabled replaces all the array (any not listed will become disabled)
function showNavigation ($current, $min, $max, $override_settings = array())
{
	// get the settings class
	$settings = Settings::getInstance();
	// Text labels (note these are also the submit values
	$labels = unserialize ($settings->getSetting('buttons_navigation_labels'));
	// load any label overrides
	if (isset ($override_settings['labels'])
	{
		foreach ($override_settings['labels'] as $key => $value)
		{
			$labels[$key] = $value;
		}
	}
	// load enabled (if not provided in override)
	if (isset ($override_settings['enabled']) 
	{ 
		$enabled = $override_settings['enabled'];
	}
	else
	{
		$enabled = unserialize ($settings->getSetting('buttons_navigation_enabled'));
	}
	
	foreach ($enabled as $this_button)
	{
		//--here
		// check matching label is defined - if not add as a warning and move to next
		if (!isset $labels[$this_button]) 
		{
			$err =  Errors::getInstance();
    		$err->errorEvent(WARNING_INTERNAL, "No label provided for button $this_button - ignoring");
    		continue;
		}
		print "<input type=\"submit\" name=\"nav\" value=\"".$labels[$this_button]."\">\n";
	}
	
	
/*	// note that the value entries need to be static as these are used 
	// in the check during question.php
	print "<input type=\"submit\" name=\"nav\" value=\"|<<\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\"<<\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\">>\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\">>|\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\"Finish\">\n";*/
}


?>
