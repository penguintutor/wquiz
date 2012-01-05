<?php

/* Edit most settings */
/* Does not support array settings (yet) */
/* Does not change username / password (in useradmin code instead) */



/* We get to here from one of the following
GET (display edit)
POST action=save	(save changes)
POST (display edit) 
*/

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

// must add this before we require the menu 
$admin_menu = 'editsettings';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';
// action should be new or edit
// note edit rather than save (save is used in post) as that is what we are doing in this
$action = '';
$quizname = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");
// Details of the settings
require_once("includes/adminSettings.php");

/*** Authentication ***/
// user must be logged in for any admin functions
// this needs to be before we output anything as it uses sessions (cookies)
$auth = new SimpleAuth ($settings->getSetting('admin_login_username'), $settings->getSetting('admin_login_password'), $settings->getSetting('admin_login_expirytime'));
// if not logged in redirect to login page
$status = $auth->checkLogin();
if ($status != 1) 
	{
	// no from as use default which goes back to this page
	header("Location: ".ADMIN_LOGIN_FILE."?status=$status");
	// header will redirect to a new page so we just close this script
	exit (0);	//Important to stop script here
	}
// If we reach here then login was successful
$sessionUsername = $auth->getUser();


// header template
$templates->includeTemplate('header', 'admin');


/** Save **/
// save - otherwise we just go to view/edit
if (isset($_POST['action']) && $_POST['action'] == 'save')
{
	// array to hold changed / new settings 
	// store as 2D array (key, value)
	$update_settings = array();
	// iterate through all possible option 
	foreach ($setting_types as $this_setting)
	{
		// split into separate variables to make it easier to understand
		$this_key = $this_setting[0];
		$this_type = $this_setting[1];
		// $this_value is not set until we have validated input - so it is clear that a $_POST could still be unchecked
		// validate entry and compare to existing before changing
		// $this_setting[1] is the type
		if ($this_type == 'custom') {continue;}	// ignore custom
		if (!isset ($_POST[$this_key])) {continue;}	// ignore if not set
		switch ($this_type)
		{
		case 'boolean':
			// check it's valid
			if ($_POST[$this_key] != 'true' && $_POST[$this_key] != 'false')
			{
				$err = Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Invalid value for parameter $this_type");
				exit (0);
			}
			$this_value = $_POST[$this_key]; 
			break;
		case 'alphanum':
		case 'text':
		case 'textblock':
			// escape the text string
			if (get_magic_quotes_gpc()) { $this_value = stripslashes($_POST[$this_key]); }
			else {$this_value = $_POST[$this_key];}
			break;
		case 'directory':
			// check for valid chars for a directory
			if (!preg_match("/^[\w\/]*$/", $_POST[$this_key]))
			{
				$err = Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Invalid characters for directory $this_key");
				exit (0);
			}
			$this_value = $_POST[$this_key];
			// add trailing / if not already (can make this check after check for update as it would have been saved with trailing /
			if (!preg_match("/\/$/", $this_value)) {$this_value.="/";}
			break;
		// same as directory, but without adding trailing /
		case 'directory_':
			// check for valid chars for a directory
			if (!preg_match("/^[\w\/]*$/", $_POST[$this_key]))
			{
				$err = Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Invalid characters for directory $this_key");
				exit (0);
			}
			$this_value = $_POST[$this_key];
			break;
		case 'int':
			if (!is_numeric($_POST[$this_key])) 
			{
				$err = Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Entry is not a number $this_key");
				exit (0);
			}
			$this_value = $_POST[$this_key];
			break;
		case 'default':
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_INTERNAL, "Error in adminSettings - invalid type ".$this_setting[1]);
			exit (0);
		}
		// if changed add to update_settings array
		if ($this_value != $settings->getSetting($this_key)) {$update_settings[] = array($this_key, $this_value);}
		
	}
	// Now add any / update any entries in the database
	// This is done now rather than above in case of error
	// trying to avoid partial save
	foreach ($update_settings as $this_setting)
	{
		if ($debug) {print "Updating $this_setting[0] with value $this_setting[1]<br />\n";}
		// setSetting will insert or update as required - and update current loaded value
		if (!$settings->setSetting ($this_setting[0], $this_setting[1])) 
		{
			// should be caught in DB save if there is a problem
			print "Error - unable to save settings";
			exit (0);
		}
	}
}

// Now display settings regardless of whether we changed or not
// Set appropriate displays for each value
// need to be done in this way to be able to insert the array entries
$formstartprint = "<form method=\"post\" action=\"".ADMIN_EDIT_SETTINGS_FILE."\">";
$hiddenprint = "<input type=\"hidden\" name=\"action\" value=\"save\" />\n";

$fieldprint = "";

foreach ($setting_types as $this_setting)
{
	// if optional bits are not included replace with appropriate
	if (!isset($this_setting[2])) {$this_setting[2] = $this_setting[0];}
	if (!isset($this_setting[3])) {$this_setting[3] = $this_setting[2];}
	
	// switch based on type
	// first check for custom option as we ignore that completely
	if ($this_setting[1] == 'custom') {continue;}
	switch ($this_setting[1])
	{
	case 'boolean':
		$fieldprint .= $this_setting[2]." <select name=\"".$this_setting[0]."\">\n";
		if ($settings->getSetting($this_setting[0]) == 'false') {$fieldprint .= "<option value=\"false\" selected=\"selected\">false</option>\n<option value=\"true\">true</option>\n";}
		else {$fieldprint .= "<option value=\"false\">false</option>\n<option value=\"true\" selected=\"selected\">true</option>\n";}
		$fieldprint .= "</select>\n";
		break;
	// form is the same - text form for the rest
	case 'int':
	case 'text':
	case 'alphanum':
	case 'directory':
	case 'regexp':
		$fieldprint .= $this_setting[2]." <input type=\"text\" name=\"".$this_setting[0]."\" value=\"".$settings->getSetting($this_setting[0])."\" />";		
		break;
	case 'textblock':
		$fieldprint .= $this_setting[2]." <textarea name=\"".$this_setting[0]."\">".$settings->getSetting($this_setting[0])."</textarea>\n";		
		break;
	// As array is within this file shouldn't get this - but just in case of error in adminSettings.php file
	default :
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_INTERNAL, "Error in adminSettings - invalid type ".$this_setting[1]);
		exit (0);
	}
	$fieldprint .= "<input type=\"button\" value=\"?\" onClick=\"alert('".$this_setting[3]."');\"><br />\n";
}
$formendprint = "<input type=\"submit\" value=\"Save\" />\n</form>\n";


print <<< MAINFORM
$formstartprint
$hiddenprint
$fieldprint
$formendprint
MAINFORM;


// footer template
$templates->includeTemplate('footer', 'admin');



?>
