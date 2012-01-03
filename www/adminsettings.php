<?php

/*** Warning do not use as it - search for //- for where changes needed ***/

/* New / Edit / Save quiz */
/* Note error checking is quite brutal - we rely on javascript to provide more
user friendly error checking before we get to this page */
/* This is even more brutal than questions to stop breaking the quiz
eg. if number is not numeric we break rather than setting to default */


/* We get to here from one of the following
$action=new		(create new)
$quiz=quizname	(edit existing)
POST action=	(either 'new' or 'save')
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


/** Edit or Save ? **/
// save - otherwise we just go to view/edit
if (isset($_POST['settings']) && $_POST['settings'] == 'admin')
{
	//- add handling of a save
}

// Now display settings regardless of whether we changed or not
// Set appropriate displays for each value
// need to be done in this way to be able to insert the array entries
$formstartprint = "<form method=\"post\" action=\"".ADMIN_EDIT_SETTINGS_FILE."\">";
$hiddenprint = "<input type=\"hidden\" name=\"settings\" value=\"admin\" />\n";

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
		if ($settings->getSetting($this_setting[0]) == 0) {$fieldprint .= "<option value=\"false\" selected=\"selected\">false</option>\n<option value=\"true\">true</option>\n";}
		else {$fieldprint .= "<option value=\"false\">false</option>\n<option value=\"true\" selected=\"selected\">true</option>\n";}
		$fieldprint .= "</select>";
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
		$fieldprint .= $this_setting[2]." <textarea name=\"".$this_setting[0]."\">".$settings->getSetting($this_setting[0])."</textarea>";		
		break;
	// As array is within this file shouldn't get this - but just in case
	default :
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_INTERNAL, "Error in adminSettings - invalid type ".$this_setting[1]);
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
