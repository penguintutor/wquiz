<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);


//$debug = true;

// Must be a post to actually delete - must have confirm=yes 
// 'question'=questionid 
// 'confirm'=yes			// does not need further confirmation

// can use GET, but will prompt to confirm (same as post without confirm=yes)
// 'question'=questionid

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");


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

// If questionid is provided, but not confirm=yes
if ((isset($_GET['questionid']) && is_numeric($_POST['questionid'])) || (isset($_POST['questionid']) && is_numeric($_POST['questionid']) && !isset($_POST['confirm'])))
{
	// Check questionid is valid and show "Are you sure?"

	// header template
	$templates->includeTemplate('header', 'normal');
	
	// footer template
	$templates->includeTemplate('footer', 'normal');
	
	
}

****







?>
