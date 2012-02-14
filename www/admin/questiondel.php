<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);


//$debug = true;

// Must be a post to actually delete - must have confirm=yes 
// 'questionid'=questionid 
// 'confirm'=yes			// does not need further confirmation

// can use GET, but will prompt to confirm (same as post without confirm=yes)
// as other functions we use question on a get (where visible), but questionid on a POST
// 'question'=questionid

// Unlike most of the other files where we reach the end except on an error we have multiple 
// exit statements in this page because of the different outputs we may want to give 
// eg. confirm / not exist / delete / not exists on confirm etc.

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");


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
if ((isset($_GET['question']) && is_numeric($_GET['question'])) || (isset($_POST['questionid']) && is_numeric($_POST['questionid']) && !isset($_POST['confirm'])))
{
	// Check questionid is valid and show "Are you sure?"
	// Bit more forgiving on errors here - just in case this is a repost of a question that's already been deleted - in which case we just state doesn't exist
	
	if (isset($_GET['question'])) {$questionid = intval($_GET['question']);}
	else {$questionid = intval($_POST['questionid']);}
	
	// header template
	$templates->includeTemplate('header', 'admin');
	
	if ($qdb->checkQuestion($questionid)) 
	{
		print "<h3>Are you sure?</h3>\n";
		print "<form action=\"".ADMIN_DEL_Q_FILE."\" method=\"post\">\n";
		print "<input type=\"hidden\" name=\"questionid\" value=\"".$questionid."\" />\n";
		print "<input type=\"hidden\" name=\"confirm\" value=\"yes\" />\n";
		print "<input type=\"submit\" value=\"Yes\" />\n";
		print "</form>\n";
		print "<p><a href=\"".ADMIN_FILE."\">No (return to index)</a></p>\n";
	}
	else	// Question already deleted?
	{
		print "<h3>Question does not exist</h3>\n";
		print "<p>That question does not exist, possibly already deleted.</p>\n";
		print "<p><a href=\"".ADMIN_FILE."\">Return to index</a></p>\n";
	}
	
	// footer template
	$templates->includeTemplate('footer', 'admin');
	exit (0);
}
// If we do have confirm = yes we can delete
elseif (isset($_POST['questionid']) && is_numeric($_POST['questionid']) && isset($_POST['confirm']))
{
	$questionid = intval($_POST['questionid']);
	
	// check exists
	if (!$qdb->checkQuestion($questionid))
	{
		$templates->includeTemplate('header', 'admin');
		print "<h3>Question does not exist</h3>\n";
		print "<p>That question does not exist, possibly already deleted.</p>\n";
		print "<p><a href=\"".ADMIN_LOGIN_FILE."\">Return to index</a></p>\n";
		$templates->includeTemplate('footer', 'admin');
		exit (0);
	}
	// delete it
	else
	{
		// Delete the rel entries first (so we don't end up with orphened rel entries in case del fails
		// note that it returns true even if no entries to delete - false is a fail
		if (!$qdb->delQuestionQuizQuestionid($questionid))
		{
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_DATABASE, "Error trying to delete rel entries for $questionid");
			exit (0);
		}
		
		if (!$qdb->delQuestion ($questionid))
		{
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_DATABASE, "Error trying to delete Question entry for $questionid");
			exit (0);
		}
		
		// reach here we have deleted - redirect to question list
		header("Location: ".ADMIN_LIST_FILE);
		
	}
}
	
// invalid paramter
else
{
	$err =  Errors::getInstance();
	$err->errorEvent(ERROR_PARAMETER, "Error invalid or incorrect parameters provided");
	exit (0);
}



?>
