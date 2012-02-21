<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);
//$debug = false;
$debug = false;

// this is where we create the html response to the user
$response_text = '';

require_once("includes/setup.php");

// Are we allowed to view answers?
// If not redirect to end page with no message
if ($settings->getSetting('answer_summary_enable') == 'false')
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_NOTALLOWED, "Answer view is disabled");
	header("Location: ".END_FILE);
	exit (0);
}

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
	header("Location: ".INDEX_FILE."?status=expired");
	exit (0);
}

// Check we have valid status  
// otherwise if not active go back to start (no status message)
if ($quiz_info['status'] != SESSION_STATUS_COMPLETE)
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_SESSION, "Session not complete");
	// Go to end / review page (leave session intact)
	header("Location: ".END_FILE);
	exit (0);
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

$detailed_result_text = "<ul >\n";

// Create output / overview
$score = 0;
for ($i=0; $i<$num_questions; $i++)
{
	//todo customise message
	$this_question = new Question($qdb->getQuestion($questions_array[$i]));
	if ($this_question->markAnswer($answers_array[$i]) == true) 
	{
		$score ++;
		$detailed_result_text .= "<li class=\"".CSS_CLASS_SUMMARY_CORRECT."\"><a href=\"".ANSWER_FILE."?question=".($i+1)."\">".($i+1)." - correct</a></li>\n";
	}
	else if ($answers_array[$i] == -1)
	{
		$detailed_result_text .= "<li class=\"".CSS_CLASS_SUMMARY_NOTANSWERED."\"><a href=\"".ANSWER_FILE."?question=".($i+1)."\">".($i+1)." - not answered</a></li>\n";
	}
	else
	{
		$detailed_result_text .= "<li class=\"".CSS_CLASS_SUMMARY_INCORRECT."\"><a href=\"".ANSWER_FILE."?question=".($i+1)."\">".($i+1)." - incorrect</a></li>\n";
	}
	
}
$detailed_result_text .= "</ul>\n";


// todo customise - css and/or text
$response_text .= "<h2>Score</h2>\n<p>$score out of $num_questions</p>";
$percentage = round($score * 100 / $num_questions);
$response_text .= "<p>$percentage %</p>";
$response_text .= "<h3>Result breakdown</h3>\n<p>$detailed_result_text</p>";

	
/*** Create the html ***/
// We reach this point whether we are Query review or actual marked the answer
// Pull in templates
$templates->includeTemplate('header', 'normal');

print $response_text;


// footer templates
$templates->includeTemplate('footer', 'normal');


// Debug mode - display any errors / warnings
if (isset($debug) && $debug)
{
	$err =  Errors::getInstance();
	if ($err->numEvents(INFO_LEVEL) > 0)
	{
		print "Debug Messages:\n";
		print $err->listEvents(INFO_LEVEL);
	}
}


?>
