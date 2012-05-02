<?php
/*** 
Loads the templates 
This pulls in external templates
eg. header.php / footer.php
***/

/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2012

This file is part of wQuiz.

wQuiz is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

wQuiz is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wQuiz.  If not, see <http://www.gnu.org/licenses/>.
**/

/* The following can be used within the theme files to customise the theme
eg <?=$template_directory>

$template_directory	- directory to the current theme (includes trailing /)

*/


class Templates
{
	//private $template_dir = '';
	// note we don't store any of the template settings we get them from the settings object as required
	private $settings;
	// Add javascript within the header section
	private $header_javascript = '';
	
	// header files
	// perhaps add seperate for quickstart
	private $filenames = array 
	(
		'admin_header' => 'admin_header.php',
		'admin_footer' => 'admin_footer.php',
		'normal_header' => 'quiz_header.php',
		'normal_footer' => 'quiz_footer.php',
		'iframe_header' => 'iframe_header.php',
		'iframe_footer' => 'iframe_footer.php',
		// Note the offline header and footer and only used in popup mode - not in normal mode
		'offline_header' => 'offline_header.php',
		'offline_footer' => 'offline_footer.php'
	);
	
	

    // mode determines what template to output (eg. we use different headers if it's normal mode vs. iframe
	// the modes are as defined in the settings database
	// template_<mode>_header / template_<mode>_footer
	// mode normal uses template_normal_header and template_normal_footer    
    public function __construct () 
    {
    	// create reference to settings object (it's a singleton) - and get info relevant to templates
    	$this->settings = Settings::getInstance();
    }
    
    // javascript should be added without any <script> tags as they are harndled within the template
    public function addHeaderJavascript ($new_javascript)
    {
    	$this->header_javascript .= $new_javascript."\n";
    }
    

    // template_name = header / footer etc. 
    // we access the settings directly to extract the theme information rather than having to pass to the class
    // them
	public function includeTemplate ($template_name, $mode)
	{
		// pull in application directory from original setup / adminsetup
    	global $app_dir;
    	
		// the local directory is not dependant upon incoming php file
    	$template_dir_local = $app_dir."/themes/";
    	// the url directory is dependant upon whether we are in admin or not
		if ($mode == 'admin') 
		{
			$template_dir_url = "../themes/";
			$template_theme_dir = $this->settings->getSetting("theme_admin"). "/";
		}
		
		// test entries have been moved to the main directory (instead of admin) so that they don't  
		// break themes where rel directories are used within the theme
		elseif ($mode == 'test') 
		{
			$template_dir_url = "themes/";
			$template_theme_dir = $this->settings->getSetting("theme_quiz")."/";
			// set mode to normal so we load the standard headers
			$mode = 'normal';
		}
		else 
		{
			$template_dir_url = "themes/";
			$template_theme_dir = $this->settings->getSetting("theme_quiz")."/";
		}
		$template_filename = $this->filenames[$mode."_".$template_name];
		
		
		/* Settings that can be used within the template files */
		
		//%%Title%%
		$template_variables['Title'] = $this->settings->getSetting("html_title");
		
		//%%Description%%
		$template_variables['Description'] = $this->settings->getSetting("html_description");
		
		//%%QuizTitle%%
		$template_variables['QuizTitle'] = $this->settings->getSetting("quiz_title");
		
		//%%QuestionNumber%%
		$template_variables['QuestionNumber'] = $this->settings->getSetting("question_number");
		
		//%%HeaderJavascript (created by addHeaderJavascript function)
		if ($this->header_javascript != '') {$template_variables['HeaderJavascript'] = "<script type=\"text/javascript\">\n".$this->header_javascript."</script>\n";}
		else {$template_variables['HeaderJavascript'] = '';}
		
		//%%ThemeDirectory%%
		// Note use directory in the variable name rather than shortened to dir as we have done for the internal variables
		// This is the path to the theme directory that can be used in a url (relative to current file)
		$template_variables['ThemeDirectory'] = $template_dir_url.$template_theme_dir;
		
		// only action if template is set - if blank or not in db then we ignore
		// load the template file and parse initial variables
		if ($template_filename != "")
		{
			// include the app_dir as rel_dir is relative to that
			//include($template_dir_local.$template_theme_dir.$template_filename);
			$template_fh = fopen ($template_dir_local.$template_theme_dir.$template_filename, 'r');
			while ($this_string = fgets($template_fh))
			{
				// parse variables
				foreach ($template_variables as $this_variable_key=>$this_variable_value)
				{
					$this_string = preg_replace ("/%%$this_variable_key%%/i", $this_variable_value, $this_string);
				}
				// replaced relevant variables now check for permitted php includes
				if (preg_match ('/(.*)<\?php\s+include\s*\(?[\'\"]([^\'\"]*)[\'\"]\)?\s*;\s*\?>(.*)/', $this_string, $matches))
				{
					// print before string - do the include - then print after string
					// this is why only one per line (could add loop or recursive, but shouldn't need to have more than one include per line - especially as you can include an include etc.)
					// before include
					print $matches[1];
					// include string
					// don't check it exists here - perhaps add in future
					// if not enabled then we ignore - strip out the include and replace with a comment warning
					if ($this->settings->getSetting('template_allow_include'))
					{
						include ($matches[2]);
					}
					else 
					{
						print "<!-- PHP Includes are disabled in the wquiz settings -->";
					}
					// after include
					print $matches[3];
				}
				else // otherwise just print the line as is
				{
					print $this_string;
				}
					
			}
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
	
	
	public function textToJavascript ($in_text)
	{
		$edit_text = trim($in_text);
		$edit_text = preg_replace ('/\n/', '\\n', $edit_text);
		return ($edit_text);
	}
    
    
    
}
?>
