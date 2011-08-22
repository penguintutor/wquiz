<?php
/*** 
Loads the templates 
This pulls in external templates
eg. header.php / footer.php
***/


class Templates
{
	//private $template_dir = '';
	// note we don't store any of the template settings we get them from the settings object as required
	private $settings;
	

    // mode determines what template to output (eg. we use different headers if it's normal mode vs. iframe
	// the modes are as defined in the settings database
	// template_<mode>_header / template_<mode>_footer
	// mode normal uses template_normal_header and template_normal_footer    
    public function __construct () 
    {
    	//$this->template_dir = $template_dir;
    	// create reference to settings object (it's a singleton) - and get info relevant to templates
    	$this->settings = Settings::getInstance();
    }
    

    // template_name = header / footer etc. 
	public function includeTemplate ($template_name, $mode)
	{
		$template_filename = $this->settings->getSetting("template_".$mode."_".$template_name);
		$template_directory = $this->settings->getSetting("template_directory");
		// check $template_directory ends with a /
		if (!preg_match("/\/$/", $template_directory)) {$template_directory.="/";}
		// only action if template is set - if blank or not in db then we ignore
	
		if ($template_filename != "")
		{
			include($template_directory.$template_filename);
		}
		else
		{
			// not found - so issue warning
			$err =  Errors::getInstance();
    		//$err->errorEvent(WARNING_EXTERNAL, "Warning, external template file not found - $template_filename");
    		// not an error as such - but likely to be
    		$err->errorEvent(INFO_EXTERNAL, "Warning, external template not defined - $template_name, $mode");
			
		}
	}
    
    
    
}
?>
