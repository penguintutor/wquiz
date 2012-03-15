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
error_reporting(E_ALL);
ini_set('display_errors', true);
$debug = false;

// Note that most of the error checking from form results just makes us switch to display
// the question (or first / last question as appropriate) - this is not fed back to the user they just the question
// look for $action = 'display' to see where we've switched back to default display

/* Will accept GET or POST for question=
post is used for navigation, but use GET to jump direct if going from summary menu */

require_once("includes/setup.php");
require_once("includes/QuestionNavigation.php");	// used later for navigation buttons

// action is used to see if we are moving between questions
// we do not allow updating any questions
$action = 'display';

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
	// most likely session timed out or gone direct to question.php?
	// provide expired status
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

// class for action (use same navigation as question.php, but going between answer pages)
// we are using default from settings - could override here if required - will also need to pass with showNavigation
$navigation = new QuestionNavigation(1, $num_questions);

//submit buttons
// Determine what action required based on submit
if (isset($_POST['nav']))
{
	// see if this is a valid action - gets action back
	$action = $navigation->getAction ($_POST['nav']);
	if ($action == 'invalid') 
	{
		$err = Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Action invalid - defaulting to display");
		$action = 'display';
	}
}


// get question number from the post
// question number is from 1 upwards - not 0 as the session array does
// Check that this is a number and that it is within the session questions - otherwise we default to 1st question
// if we have to change the question number to default then we also change the action to default - for example should not be saving answer if answer was given to out-of-range question
// note is_int does not work - so using is_numeric instead 
// Uses REQUEST to support GET or POST
// Request will include cookies - but we aren't setting cookies and it's easier for the "user" to manually edit GET than it is for them to edit cookies
if (!isset($_REQUEST['question']) || !is_numeric($_REQUEST['question']) || $_REQUEST['question'] < 1)
	{
		$question_num = 1;
		// set action to default as we didn't have a valid question number
		$err = Errors::getInstance();
		$err->errorEvent(INFO_PARAMETER, "No question number provided - using default display and question 1");
		$action == 'display';
	}
elseif ($_REQUEST['question'] > $num_questions) 
	{
		$question_num = $num_questions;
		$err = Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Parameter question number too high setting to max / display");
		// set action to default as we didn't have a valid question number
		$action == 'display';
	}
else {$question_num = $_REQUEST['question'];}


// what is next action based on which button pressed
if ($action == 'first') {$question_num = 1;}
else if ($action == 'previous') {$question_num--;}
else if ($action == 'next') {$question_num ++;}
// special case with last button - if we are on last page then it goes to review (same as next button)
else if ($action == 'last') 
{
	if ($question_num >= $num_questions) 
	{
		header ("Location: ".END_FILE);
		exit (0);
	}
	else
	{
		$question_num = $num_questions;
	}
}

// Handle change in page (eg. Finish / trying to go past first) 
if ($question_num < 1) {$question_num = 1;}
if ($question_num > $num_questions || $action == 'review')
{
	header ("Location: ".END_FILE);
	exit (0);
}



// Pull in templates
$templates->includeTemplate('header', 'normal');

// start form - still use form to navigate, but not for sending the answer
// Form starts at the top
print "<form id=\"".CSS_ID_FORM."\" method=\"post\" action=\"".ANSWER_FILE."\">\n";

// message is to show the review status
print "<p class=\"".CSS_CLASS_MESSAGE."\">Review answered questions</p>\n";

// show position in quiz
// todo may want to allow this wording to be changed via a setting
print "<p class=\"".CSS_CLASS_STATUS."\">Question $question_num of $num_questions</p>\n";



// load this question - note -1 used to select array position (ie. question 1 = array 0)
$question = new Question($qdb->getQuestion($questions_array[$question_num-1]));
// first print status bar if req'd (eg. question 1 of 10)
// answer is currently selected -1 = not answered
$answer = $quiz_session->getAnswer($question_num-1);
print ($question->getHtmlString($answer));


print "<div id=\"".CSS_ID_ANSWER."\">\n";
print "<h3>Answer</h3>\n";

if ($answer == -1) {print "<p class=\"".CSS_CLASS_SUMMARY_NOTANSWERED."\">Not answered</p>\n";}
// marked is whether this is correct or not (if answered)
else
{
	// has this been answered correctly
	if($question->markAnswer($answer))
	{print "<p class=\"".CSS_CLASS_SUMMARY_CORRECT."\">Correct</p>\n";}
	else {print "<p class=\"".CSS_CLASS_SUMMARY_INCORRECT."\">Incorrect</p>\n";}
}

// Display the actual answer text
print "<p class=\"".CSS_CLASS_ANSWER_REASON."\">\n";
print $question->getReason();
print "</p>\n";

// end answer div
print "</div>\n";



// add navigation buttons
print "<div id=\"".CSS_ID_NAVIGATION."\">\n";
$navigation->showNavigation($question_num);
print "\n</div><!-- ".CSS_ID_NAVIGATION." -->\n";

// end form
print "</form>\n";

// link back to end of review
print "<p><a href=\"".END_FILE."\">Back to result summary</a></p>\n";


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
