<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);
$GLOBALS['debug'] = 1;


require_once("includes/setup.php");

// get the list of questions and current status
$quiz_info = $quiz_session->getSessionInfo();
if (!isset($quiz_info['status'])||!is_int($quiz_info['status']))
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_SESSION, "Session status is invalid");
	// kill session and send to index page
	$quiz_session->destroySession();
	// -here - return to main page on error - we need to provide a message to the user 
	// most likely session timed out or gone direct to question.php?
	header("Location: ".INDEX_FILE);
}

// get question number from the post
//if (!isset($_POST['questionnum']) || !is_int($_POST['questionnum'])) {$questionnum = 1;}
//-here


// Pull in templates
$templates->includeTemplate('header', 'normal');
$question = new Question(0, $qdb->getQuestion(2));
// first print status bar if req'd (eg. question 1 of 10)
// answer is currently selected -1 = not answered
print ($question->getHtmlString(-1));

// footer templates
$templates->includeTemplate('footer', 'normal');


// Debug mode - display any errors / warnings
if (isset($debug) && $debug)
{
	$err =  Errors::getInstance();
	if ($err->numEvents(INFO_LEVEL) > 0)
	{
		print "Errors:\n";
		print $err->listEvents(INFO_LEVEL);
	}
}


?>
