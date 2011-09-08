<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);
$debug = true;


require_once("includes/setup.php");
require_once("includes/questionNavigation.php");	// used later for navigation buttons

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

// Get the list of question numbers and current answers from session
$questions_array = $quiz_session->getQuestions();
// If we don't have questions then we error
if (count($questions_array)<1) 
{
	$err = Errors::getInstance();
	$err->errorEvent(ERROR_SESSION, "Session does not have any questions defined");
}
else {$num_questions = count($questions_array);}
$answers_array = $quiz_session->getAnswers();


// get question number from the post
// question number is from 1 upwards - not 0 as the session array does
// Check that this is a number and that it is within the session questions - otherwise we default to 1st question
if (!isset($_POST['questionnum']) || !is_int($_POST['questionnum']) || $_POST['questionnum'] < 1) {$questionnum = 1;}
elseif ($_POST['questionnum'] > $num_questions) {$questionnum = $num_questions;}
else {$questionnum = $_POST['questionnum'];}


// -here Save answer if changed


// -here Handle change in page (eg. Finish / trying to go past first) 


// Pull in templates
$templates->includeTemplate('header', 'normal');

// start form
// Form starts at the top
print "<form id=\"wquiz-form\" method=\"post\" action=\"question.php\">\n";

// load this question - note -1 used to select array position (ie. question 1 = array 0)
$question = new Question(0, $qdb->getQuestion($questions_array[$questionnum-1]));
// first print status bar if req'd (eg. question 1 of 10)
// answer is currently selected -1 = not answered
print ($question->getHtmlString(-1));


// add navigation buttons
print "<div id=\"".CSS_ID_NAVIGATION."\">\n";
showNavigation($questionnum, 1, $num_questions);
print "\n</div><!-- ".CSS_ID_NAVIGATION." -->\n";

//--here end form
print "</form>\n";


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
