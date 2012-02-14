<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

// must add this before we require the menu 
$admin_menu = 'list';
//$debug = true;

// hard coded here
// we only have one user on this system so I don't think we need to be able to make it configurable
$min_password_chars = 6;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

// must add this before we require the menu 
$admin_menu = 'home';

// have we successfully changed (if so don't show password change option 
$password_changed = false;

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");;

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
require_once ($include_dir."adminmenu.php");

print "<h1>User administration - admin user</h1>\n";


// action = 'pwchange' to change password
// at end we display confirmation and provide click back to home page
if (isset($_POST['action']) && $_POST['action']=='pwchange')
{
	/**** IMPORTANT - these are all elseif options - only if we reach the 
	else at the end do we change the password - if we match any of the if / elseif 
	then we don't change the password *****/
	// check existing password is entered
	if (!isset($_POST['oldpassword']))
	{
		print "<strong>You need to enter the existing password</strong>\n";
	}
	// is existing password correct
	elseif (!$auth->checkPassword($_POST['oldpassword']))
	{
		print "<strong>Existing password is incorrect</strong>\n";
	}
	// check new password entered, is identical and meets minimum length
	elseif (!isset($_POST['newpassword']))
	{
		print "<strong>New password cannot be left blank</strong>\n";
	}
	elseif (strlen($_POST['newpassword']) < $min_password_chars)
	{
		print "<strong>New password is too short<br />Must be at least $min_password_chars characters long</strong>\n";
	}
	// check for invalid characters in password
	elseif (!$auth->securityCheck('password', $_POST['newpassword']))
	{
		print "<strong>New password contains inavlid characters</strong>\n";
	}
	// check new passwords are identical
	elseif (!isset($_POST['newpassword2']) || $_POST['newpassword'] != $_POST['newpassword2']) 
	{
		print "<strong>New password is not the same as the repeated password</strong>\n";
	}
	// Reach here we can change password
	else
	{
		// get md5 version of password
		$hash_password = $auth->hashPassword($_POST['newpassword2']);
		// save it in the settings
		$settings->setSetting('admin_login_password', $hash_password);
		// if we changed password then we confirm back to the user with link back to main page
		print "<p>Password change successful</p>\n<p><a href=\"".ADMIN_FILE."\">Admin home page</a></p>\n";
		$templates->includeTemplate('footer', 'admin');
		// exit if we succesfully change
		// otherwise we show password change form
		exit (0);
	}
}


// display change password form
print "<form action=\"".ADMIN_USER_FILE."\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"action\" value=\"pwchange\" />\n";

print "Current password:<br />\n<input type=\"password\" name=\"oldpassword\" value=\"\"><br />\n";

print "New password:<br />\n<input type=\"password\" name=\"newpassword\" value=\"\"><br />\n";
print "Repeat password:<br />\n<input type=\"password\" name=\"newpassword2\" value=\"\"><br />\n";


print "<input type=\"submit\" value=\"Change password\" />\n";
print "</form>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



?>
