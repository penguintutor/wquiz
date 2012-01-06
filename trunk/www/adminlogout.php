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



/*** Are we logged in? ***/
// user must be logged in for any admin functions
// this needs to be before we output anything as it uses sessions (cookies)
$auth = new SimpleAuth ($settings->getSetting('admin_login_username'), $settings->getSetting('admin_login_password'), $settings->getSetting('admin_login_expirytime'));

$status = $auth->checkLogin();
if ($status != 1) 
	{
	// if not logged in just go to index page
	header("Location: ".ADMIN_FILE."?status=$status");
	// header will redirect to a new page so we just close this script
	exit (0);	//Important to stop script here
	}
// If we reach here then login was successful
$sessionUsername = $auth->logout();
// redirect to the first page

header("Location: ".ADMIN_FILE."?status=$status");
	// header will redirect to a new page so we just close this script
	exit (0);	//Important to stop script here

?>
