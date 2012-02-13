<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);


// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");


// Array of valid goto / location entries
// prefixed with a in case we want to use authentication in main quiz in future
// uses the #define entries - so put after setup
$locations = array('aindex'=>ADMIN_FILE, 'aquestions'=>ADMIN_Q_FILE);

// create authentication object
// this needs to be before we output anything as it uses sessions (cookies)
$auth = new SimpleAuth ($settings->getSetting('admin_login_username'), $settings->getSetting('admin_login_password'), $settings->getSetting('admin_login_expirytime'));


/*** Authentication - Is this a login attempt (ie. with username & password) ***/
// note we only exit if we successfully login - otherwise we continue with showing login form
if (isset($_POST['username']) && isset($_POST['password']))
{
	// check that they are only using valid characters
	if ($auth->securityCheck('username', $_POST['username']) && $auth->securityCheck('password', $_POST['password']))
	{
		//check login is correct
		if ($auth->loginNow($_POST['username'], $_POST['password']))
		{
			// do we have a valid return location - if so go there, otherwise back to admin index page
			if (isset($_POST['location']) && $auth->securityCheck('location', $_POST['location'], $locations))
			{
				$goto = $_POST['location'];
				// goto new location
				header("Location: ".$locations[$goto]);
				exit (0); // need to stop script after redirected
			}
			else {header("Location: ".ADMIN_FILE);}
			exit (0); // need to stop script after redirected
		}
		else
		{
			$err = Errors::getInstance();
			$err->errorEvent(WARNING_FAILED_LOGIN, "Failed login attempt for username". $_POST['username']);
			$message = "Username / Password incorrect - contact your system adminstrator for a password reset if required";
		}
	}
	else
	{
		$err = Errors::getInstance();
		// note we don't include username as it may contain invalid characters
		$err->errorEvent(WARNING_INVALID_LOGIN, "Invalid login attempt");
		$message = "Invalid login - contact your system adminstrator for a password reset if required";
	}
}



/***if not from a post or if a failed then we give login screen***/

// show expired message
if ($message == '' && isset($_GET['status']) && $_GET['status'] == '-2')
{
	$message = "Login expired";
}

// default is to go to index page
$goto = 'aindex';
// do we have a valid alternative to go to
if (isset($_POST['location']) && $auth->securityCheck('location', $_POST['location'], $locations)) {$goto = $_POST['location'];}
// could be a GET instead of POST if it's not from a login attempt
elseif (isset($_GET['location']) && $auth->securityCheck('location', $_GET['location'], $locations)) {$goto = $_GET['location'];}

// header template
$templates->includeTemplate('header', 'admin');

print "<h1>Adminstration Login</h1>";

// Error message if appropriate
print "<p class=\"".CSS_CLASS_MESSAGE."\">$message</p>\n";

$auth->loginForm(ADMIN_LOGIN_FILE, $goto);


// footer template
$templates->includeTemplate('footer', 'admin');



?>
