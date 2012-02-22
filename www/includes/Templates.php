<?php
/*** 
Loads the templates 
This pulls in external templates
eg. header.php / footer.php
***/

/* The following can be used within the theme files to customise the theme
eg <?=$template_directory>

$template_directory	- directory to the current theme (includes trailing /)

*/


class Templates
{
	//private $template_dir = '';
	// note we don't store any of the template settings we get them from the settings object as required
	private $settings;
	// header files
	// perhaps add seperate for quickstart
	private $filenames = array 
	(
		'admin_header' => 'admin_header.php',
		'admin_footer' => 'admin_footer.php',
		'normal_header' => 'quiz_header.php',
		'normal_footer' => 'quiz_footer.php',
		'iframe_header' => 'iframe_header',
		'iframe_footer' => 'iframe_footer',
		'offline_header' => 'offline_header',
		'offline_footer' => 'offline_footer'
	);

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
    // we access the settings directly to extract the theme information rather than having to pass to the class
    // them
	public function includeTemplate ($template_name, $mode)
	{
		// use a different template for admin
		$template_directory = $this->settings->getSetting("theme_directory");
		// check $template_directory ends with a /
		if (!preg_match("/\/$/", $template_directory)) {$template_directory.="/";}
		// now add the relevant admin / quiz theme to the path
		if ($mode == 'admin') { $template_directory .= $this->settings->getSetting("theme_admin"). "/";}
		else {$template_directory = $this->settings->getSetting("theme_quiz")."/";}
		$template_filename = $this->filenames[$mode."_".$template_name];
		
		
		// only action if template is set - if blank or not in db then we ignore
		// need to check if file exists rather than filename
		if ($template_filename != "")
		{
			$document_root = getenv("DOCUMENT_ROOT");
			// note when we include the file we need to add the Document Root
			include($document_root.$template_directory.$template_filename);
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
