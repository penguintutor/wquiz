<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);


//$debug = true;

// Must be a post to actually delete - must have confirm=yes 
// 'quizname'=quizname 
// 'confirm'=yes			// does not need further confirmation

// can use GET, but will prompt to confirm (same as post without confirm=yes)
// as other functions we use question on a get (where visible), but questionid on a POST
// 'quiz'=quizname

// Unlike most of the other files where we reach the end except on an error we have multiple 
// exit statements in this page because of the different outputs we may want to give 
// eg. confirm / not exist / delete / not exists on confirm etc.

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
if ((isset($_GET['quiz']) && ctype_alnum($_GET['quiz'])) || (isset($_POST['quizname']) && ctype_alnum($_POST['quizname']) && !isset($_POST['confirm'])))
{
	// Check quizname is valid and show "Are you sure?"
	// Bit more forgiving on errors here - just in case this is a repost of a question that's already been deleted - in which case we just state doesn't exist
	
	if (isset($_GET['quiz'])) {$quizname = $_GET['quiz'];}
	else {$quizname = $_POST['quizname'];}
	
	// header template
	$templates->includeTemplate('header', 'admin');
	
	if ($qdb->checkQuiz($quizname)) 
	{
		print "<h3>Are you sure?</h3>\n";
		print "<form action=\"".ADMIN_DEL_QUIZ_FILE."\" method=\"post\">\n";
		print "<input type=\"hidden\" name=\"quizname\" value=\"".$quizname."\" />\n";
		print "<input type=\"hidden\" name=\"confirm\" value=\"yes\" />\n";
		print "<input type=\"submit\" value=\"Yes\" />\n";
		print "</form>\n";
		print "<p><a href=\"".ADMIN_FILE."\">No (return to index)</a></p>\n";
	}
	else	// Quiz already deleted?
	{
		print "<h3>Quiz does not exist</h3>\n";
		print "<p>That quiz does not exist, possibly already deleted.</p>\n";
		print "<p><a href=\"".ADMIN_FILE."\">Return to index</a></p>\n";
	}
	
	// footer template
	$templates->includeTemplate('footer', 'admin');
	exit (0);
}
// If we do have confirm = yes we can delete
elseif (isset($_POST['quizname']) && ctype_alnum($_POST['quizname']) && isset($_POST['confirm']))
{
	$quizname = $_POST['quizname'];
	
	// check exists
	if (!$qdb->checkQuiz($quizname))
	{
		$templates->includeTemplate('header', 'admin');
		print "<h3>Quiz does not exist</h3>\n";
		print "<p>That quiz does not exist, possibly already deleted.</p>\n";
		print "<p><a href=\"".ADMIN_FILE."\">Return to index</a></p>\n";
		$templates->includeTemplate('footer', 'admin');
		exit (0);
	}
	// delete it
	else
	{
		// Delete the rel entries first (so we don't end up with orphened rel entries in case del fails
		// note that it returns true even if no entries to delete - false is a fail
		if (!$qdb->delQuestionQuizQuizname($quizname))
		{
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_DATABASE, "Error trying to delete rel entries for $quizname");
			exit (0);
		}
		
		if (!$qdb->delQuiz ($quizname))
		{
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_DATABASE, "Error trying to delete quiz entry for $quizname");
			exit (0);
		}
		
		// reach here we have deleted - redirect to question list
		header("Location: ".ADMIN_QUIZZES_FILE);
		
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
