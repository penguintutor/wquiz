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


//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");;
require_once($include_dir."QuestionNavigation.php");	// used later for navigation buttons


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


// action is a string based on button pressed - determines how we handle the post
// default action is to show question
$action = 'display';
// message is used to provide feedback to the user
// most cases we ignore errors, but for instance if a user does not enter a number in a number field we can notify the user of this when we go in display mode
$message = 'Test mode';

// get question number from the post
// unlike in question.php - this is the actual db questionid - rather than number in session
// we also allow GET so reverse the logic compared to question.php
if (isset($_POST['question']) && is_numeric($_POST['question']) && $qdb->checkQuestion($_POST['question']))
	{
		$questionid = $_POST['question'];
		$action = 'test';
	}
// if get (only allowed on test - not real)
elseif (isset($_GET['question']) && is_numeric($_GET['question']) && $qdb->checkQuestion($_GET['question'])) 
	{
		$questionid = $_GET['question'];
		// set action to default as we didn't have a valid question number
		$action = 'display';
		$answer = -1;
	}
else 
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "no valid questionid provided - test");
	}

	
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
	
	
// question_from is the same (we don't honour the different navigation buttons)
$question = new Question($qdb->getQuestion($questionid));
$question_from = new Question($qdb->getQuestion($questionid));




// check answer
$answer = '';
// check for hidden field to show that this was from an existing question
if ($action != 'display')
{
	// no type - we didn't come from a question display
	if (!isset ($_POST['type']) || !$question_from->validateType($_POST['type'])) 
	{
		$err = Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Parameter type does not match question type");
		$action = 'display';
	}
	// checkbox is handled differently for a checkbox as it can be multiple answers
	else if ($_POST['type'] == 'checkbox')
	{
	for ($i=0; $i<10; $i++)
		{
			if (isset ($_POST['answer-'.$i])) {$answer .= $i;}
		}
		// if none selected set back to default (-1)
		if ($answer == '') {$answer = -1;}
	}
	//  handle default where answer is not set at all (eg. radio with nothing ticked)
	else if (!isset ($_POST['answer']) || $_POST['answer'] == '') 
	{
		$answer = -1;
	}
	else // All others we just have one value from post which is $answers 
	{
		if ($question_from->validateAnswer($_POST['answer']))
		{
			$answer = $_POST['answer'];
		}
		else
		{
		// set message as this may have been a genuine error (eg. seven instead of 7)
		$err = Errors::getInstance();
		$err->errorEvent(INFO_PARAMETER, "Answer provided is not a valid response for ". $question_from->getType());
		$message = 'Answer provided is not a valid response';
		$action = 'display';
		}
	}
}





if ($debug) {print "Action is $action";}
// don't change page we just show this one 

// Pull in templates
$templates->includeTemplate('header', 'test');

// start form
// Form starts at the top
print "<form id=\"".CSS_ID_FORM."\" method=\"post\" action=\"".ADMIN_Q_FILE."\">\n";

print "<input type=\"hidden\" name=\"question\" value=\"$questionid\" />\n";

// show message if there is one
if ($message != '') {print "<p class=\"".CSS_CLASS_MESSAGE."\">$message</p>\n";}

// show position in quiz
// todo may want to allow this wording to be changed via a setting
print "<p class=\"".CSS_CLASS_STATUS."\">Question $questionid</p>\n";



// load this question - note -1 used to select array position (ie. question 1 = array 0)
//$question = new Question($qdb->getQuestion($questionid));
// first print status bar if req'd (eg. question 1 of 10)
// answer is currently selected -1 = not answered
print ($question->getHtmlString($answer));


// add navigation buttons
print "<div id=\"".CSS_ID_NAVIGATION."\">\n";
print "<input type=\"submit\" value=\"Test\" />\n";
print "\n</div><!-- ".CSS_ID_NAVIGATION." -->\n";

// end form
print "</form>\n";


// show correct answer (use $answer)
if ($action != 'display')
{

	if ($answer == -1) {print "Not answered\n";}
	elseif ($question->markAnswer($answer)) {print "Correct\n";}
	else {print "Incorrect\n";}
	
}


// footer template
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
