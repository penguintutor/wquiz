<?php
/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2012

This file is part of wQuiz.

wQuiz is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

wQuiz is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wQuiz.  If not, see <http://www.gnu.org/licenses/>.
**/


// Enable debugging
//error_reporting(E_ALL);
//ini_set('display_errors', true);
//$debug = false;

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
if (isset ($_POST['reviewcomplete']) || $settings->getSetting('review_enable') == 'false' || $quiz_info['status'] == SESSION_STATUS_COMPLETE)
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
	
	// Link to review answers if enabled
	if ($settings->getSetting('answer_view_enable') != 'false')
	{
		$response_text .= "<div id=\"".CSS_ID_RESULTS_BUTTON."\">\n";
		$response_text .= "<form method=\"POST\" action=\"".SUMMARY_FILE."\">\n";
		$response_text .= "<input type=\"submit\" value=\"Detailed results\" />\n";
		$response_text .= "</form>\n";
		$response_text .= "</div>\n";
	}
	
	$response_text .= "<div id=\"".CSS_ID_RESTART_BUTTON."\">\n";
	$response_text .= "<form method=\"GET\" action=\"".INDEX_FILE."\">\n";
	$response_text .= "<input type=\"submit\" value=\"Start again\" />\n";
	$response_text .= "</form>\n";
	$response_text .= "</div>\n";
		
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
		if ($num_unanswered > 0) {$response_text .= "<p class=\"".CSS_CLASS_END_NOTANSWERED."\">$num_unanswered questions have not been answered.</p>\n";}
	}

	
	
	// add form buttons for answer / review
	$response_text .= "<div id=\"".CSS_ID_REVIEW."\">\n";
	$response_text .= "<form method=\"post\" action=\"".QUESTION_FILE."\">\n";
	$response_text .= "<input type=\"submit\" value=\"Review answers\" />\n";
	$response_text .= "</form>\n</div>\n";
	$response_text .= "<div id=\"".CSS_ID_MARK."\">\n";
	$response_text .= "<h3>Finished reviewing?</h3>";
	$response_text .= "<form method=\"post\" action=\"".END_FILE."\">\n";
	$response_text .= "<input type=\"hidden\" name=\"reviewcomplete\" />\n";
	$response_text .= "<input type=\"submit\" value=\"Mark answers\" />\n";
	$response_text .= "</form>\n</div>\n";
}

	
	
/*** Create the html ***/
// We reach this point whether we are Query review or actual marked the answer

// Set variables prior to loading template
$settings->setTempSetting ("quiz_title", $quiz_info['quiztitle']);

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
