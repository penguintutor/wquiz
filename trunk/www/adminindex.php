<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);


//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");


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

$quizzes = ADMIN_QUIZZES_FILE;
$questions = ADMIN_LIST_FILE;
$settings_file = ADMIN_EDIT_SETTINGS_FILE;
print <<< EOT
<h1>Administration</h1>
<h2>Tasks</h2>
<ul>
<li><a href="$quizzes">Quizzes</a></li>
<li><a href="$questions">Questions</a></li>
</ul>
<ul>
<li><a href="$settings_file">Settings</a></li>
</ul>

EOT;



// footer template
$templates->includeTemplate('footer', 'admin');



?>
