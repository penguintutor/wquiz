<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);
//$debug = false;
$debug = false;

// this is where we create the html response to the user
$response_text = '';

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
	header("Location: ".INDEX_FILE."?status=expired");
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

/*** Handle marking of the answers if review complete or review not allowed ***/
if (isset ($_POST['reviewcomplete']) || $settings->getSetting('review_enabled') == 'false')
{
	// change status to prevent from changing and resubmitting
	$quiz_session->setStatus(SESSION_STATUS_COMPLETE);
	// mark answers and provide result
	$score = 0;
	for ($i=0; $i<$num_questions; $i++)
	{
		$this_question = new Question($qdb->getQuestion($questions_array[$i]));
		if ($this_question->markAnswer($answers_array[$i]) == true) {$score ++;}
	}
	// todo customise - css and/or text
	$response_text .= "<h3>Score</h3>\n<p>$score out of $num_questions</p>";
	$percentage = round($score * 100 / $num_questions);
	$response_text .= "<p>$percentage %</p>";
	// todo add some comments about score
}
else
{
	// todo - make customisable
	// ask user if they want to review
	$response_text .= $settings->getSetting('review_text')."\n";
	
	// Do we display if any answers have not got a solution
	if ($settings->getSetting('review_show_unanswered') != 'false')
	{
		$num_unanswered = 0;
		// have we got any entries with default -1 value
		foreach ($answers_array as $this_answer)
		{
			if ($this_answer == -1) {$num_unanswered++;}
		}
		if ($num_unanswered > 0) {$response_text .= "<p>$num_unanswered questions have not been answered.</p>\n";}
	}
	
	// add form buttons for answer / review
	$response_text .= "<div id=\"".CSS_ID_REVIEW."\">\n";
	$response_text .= "<form method=\"post\" action=\"".QUESTION_FILE."\">\n";
	$response_text .= "<input type=\"submit\" value=\"Review Answers\" />\n";
	$response_text .= "</form>\n</div>\n";
	$response_text .= "<div id=\"".CSS_ID_MARK."\">\n";
	$response_text .= "<h3>Finished reviewing?</h3>";
	$response_text .= "<form method=\"post\" action=\"".END_FILE."\">\n";
	$response_text .= "<input type=\"hidden\" name=\"reviewcomplete\" />\n";
	$response_text .= "<input type=\"submit\" value=\"Complete\" />\n";
	$response_text .= "</form>\n</div>\n";
}

	
	
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
