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

require_once ("adminsetup.php");

// must add this before we require the menu 
$admin_menu = 'list';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

//require_once("includes/setup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

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


/*** Setup some values ***/


// performance debug
if (isset($debug) && $debug) {$start_time = time();}



// header template
$templates->includeTemplate('header', 'admin');

// questions
// We load the questions as array and manipulate directly
// This is not a tidy as using a question object and loading indivdually, but is much faster 
// Improves performance by approx 7 times (on 250 questions)
//$questions_array = $qdb->getQuestionIds();
// load all questions
$all_questions = $qdb->getQuestionQuiz();
// load all relationships
$all_rel = $qdb->getRelAll();

// loop over all_rel and store relationships under array with questionid as index (human readable space seperated)
$quiz_rel = array(); 
foreach ($all_rel as $this_rel)
{
	if (!isset($quiz_rel[$this_rel['questionid']])) {$quiz_rel[$this_rel['questionid']] = $this_rel['quizname'];}
	else {$quiz_rel[$this_rel['questionid']] .= " ".$this_rel['quizname'];} 
}

// Show menu
require_once ($include_dir."adminmenu.php");

print "<div id=\"".CSS_ID_ADMIN_MAIN."\">\n";
print "<h1>Questions</h1>";
// question = 0 used for create new
print "<form method=\"get\" action=\"".ADMIN_EDIT_FILE."\">\n";
print "<input type=\"hidden\" name=\"question\" value=\"0\" />\n";
print "<input type=\"submit\" value=\"Add new question\">\n";
print "</form>\n";

print "<table class=\"".CSS_CLASS_ADMIN_TABLE."\">\n";
print <<< EOT
<tr>
	<th>Question</th>
	<th>Summary</th>
	<th>Type</th>
	<th>Quiz</th>
	<th>Created</th>
	<th>Reviewed</th>
	<th></th>
</tr>
EOT;

/** Display questions - could use a table formatter function, but for now coded in this file **/

/* This is very inefficient and results in very slow load times - need to reduce the number of sql queries */

foreach ($all_questions as $this_question_entry)
{
	print "<tr>\n";
	// Allow either q number or summary to be clicked (as summary may be null - eg. picture quiz)
	print "<td><a href=\"".ADMIN_EDIT_FILE."?question=".$this_question_entry['questionid']."\">".$this_question_entry['questionid']."</a></td>";
	print "<td><a href=\"".ADMIN_EDIT_FILE."?question=".$this_question_entry['questionid']."\">".getSummary($this_question_entry['intro'])."</a></td>";
	print "<td>".$this_question_entry['type']."</td>";
	if (isset($quiz_rel[$this_question_entry['questionid']])) 
	{
		print "<td>".$quiz_rel[$this_question_entry['questionid']]."</td>";
	}
	else
	{
		print "<td></td>";
	}
	print "<td>".$this_question_entry['created']."</td>";
	print "<td>".$this_question_entry['reviewed']."</td>\n";
	print "<td><a href=\"".ADMIN_Q_FILE."?question=".$this_question_entry['questionid']."\">Test</a></td>\n";
	print "</tr>\n";
}

print "</table>\n";
print "</div>\n";

if (isset($debug) && $debug) 
	{
		$end_time = time();
		$total_time = $end_time - $start_time;
		print "Start time: $start_time \n";
		print "End time: $end_time \n";
		print "Time taken: $total_time secs\n";
	}


// footer template
$templates->includeTemplate('footer', 'admin');




// Added directly to this file for performance issues (over using a question object)
function getSummary($intro)
    {
    	global $settings;
    	$summary_length = $settings->getSetting('summary_length');
    	if (strlen($intro) > $summary_length) 
    	{
    		return (substr($intro, 0, $summary_length-4)." ...");
    	}
    	else {return $intro;}
    }



?>
