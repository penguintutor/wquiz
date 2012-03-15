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


require_once("includes/setup.php");

// mode is how we display 
// if basic then we show the menu options and nomal header / footer
// if popup then we show the more basic version and offline header / footer
$mode = 'basic';
// message is used to provide feedback to the user
// most cases we ignore errors, but for instance if a user does not enter a number in a number field we can notify the user of this when we go in display mode
$message = '';

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
	header("Location: ".OFFLINE_FILE."?status=expired");
	exit (0);
}


// Check we have valid status - if already complete we go to the end page 
// otherwise if not active go back to start (no status message)
if ($quiz_info['status'] != SESSION_STATUS_OFFLINE)
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_SESSION, "Session is not active - status ".$quiz_info['status']);
	// kill session and send to index page
	$quiz_session->destroySession();
	header("Location: ".OFFLINE_FILE);
	exit (0);
}


/* Get all the details of the quiz */
$this_quiz = new Quiz($qdb->getQuiz($quiz_info['quizname']));


// Get the list of question numbers and current answers from session
$questions_array = $quiz_session->getQuestions();
// If we don't have questions then we error
if (count($questions_array)<1) 
{
	$err = Errors::getInstance();
	$err->errorEvent(ERROR_SESSION, "Session does not have any questions defined");
}
else {$num_questions = count($questions_array);}

// get mode from the post - popup we don't show menus - otherwise we do
// also show offline header / footer for popup - but normal for basic
// The type can be either on post or get
if (((isset($_POST['type'])) && ($_POST['type'] == 'popup')) || ((isset($_GET['type'])) && ($_GET['type'] == 'popup')))
{
	$mode = 'popup';
	$template_mode = 'offline';
}
else
// if not in popup mode then we display the normal templates & menus
{
	$template_mode = 'normal';
}

// Pull in templates
$templates->includeTemplate('header', $template_mode);

$quiz_info = $quiz_session->getSessionInfo();

// print titles
// could get quizname from $quiz_session, but we've already got it from before into quiz_info array
print "<h1>".$this_quiz->getTitle()."</h1>\n";
print "<h1>Questions ".$quiz_session->getOfflineId()."</h1>\n";

// load and print the questions
for ($i=0; $i<count($questions_array); $i++)
{
	// load this question - note -1 used to select array position (ie. question 1 = array 0)
	$question = new Question($qdb->getQuestion($questions_array[$i]));
	print "<h2>Question ".($i+1)."</h2>\n";
	print ($question->getOfflineHtmlString());

}

// add navigation buttons - if we are not in popup mode (move to answers)
// add close button if we are in popup mode
print "<div id=\"".CSS_ID_OFFLINE_NAVIGATION."\">\n";
if ($mode != 'popup')
{
	print "<form id=\"CSS_ID_FORM\" method=\"post\" action=\"".OFFLINE_ANSWER_FILE."\">\n";
	print "<input type=\"hidden\" name=\"type\" name=\"basic\" />\n";
	print "<input type=\"submit\" value=\"Answers\" name=\"answers\" />\n";
	print "</form>\n\n";
}
else
{
	// otherwise if we are in popup then we just have a close button
	print "<form method=\"post\">\n";
	print "<input type=\"button\" value=\"Close Window\" onclick=\"window.close()\">\n";
	print "</form>\n\n";
}

print "\n</div><!-- ".CSS_ID_NAVIGATION." -->\n";

// end form
print "</form>\n";


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
