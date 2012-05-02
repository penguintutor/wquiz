<?php
/* New / Edit / Save question */
/* Note error checking is quite brutal - we rely on javascript to provide more
user friendly error checking before we get to this page */

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

// must add this before we require the menu 
$admin_menu = 'edit';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';
$questionid = -1;

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

/*** Further setup ***/
// get all the quizzes and add to object
$all_quizzes = new Quizzes();
$quiz_array = $qdb->getQuizzesAll();
// add this one to allQuizzes
foreach ($quiz_array as $this_quiz_array)
{
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}
// get list of all quizzes to show / check for updates
$quiz_array = $all_quizzes->getQuizNameArray();


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

// by default create new - otherwise changes to save
$action = 'new';

// header template
$templates->includeTemplate('header', 'admin');


/** Edit or Save ? **/
// if post it's a save (or perhaps a navigation - see $action below)
if (isset($_POST['questionid']) && is_numeric($_POST['questionid']))
{
	$questionid = $_POST['questionid'];
	// if questionid is 0 then this is a create instead of an update
	if ($debug) {print "\nSave on question $questionid\n";}
	
	if (isset($_POST['save']))
	{
		switch ($_POST['save'])
		{
		case 'Save': 
			$action = 'save';
			break;
		case 'Save and next':
			$action = 'save-next';
			break;
		case 'Next (without save)':
			$action = 'next';
			break;
		}
			
	}
	else // default save
	{
		$action = 'save';
	}
	
	// we validate all details before storing them into an array (we then use this to save to DB)
	$post_details = array();
	// store quizzes seperately as those are not saved in the question table in the DB
	$post_quizzes = array();
	
	// Quizzes
	// we need to check all possible quizzes
	if ($debug) {print "Quizzes: ";}
	for ($i =0; $i < $all_quizzes->count(); $i++)
	{
		if (isset ($_POST["quiz_".$i])) 
		{
			// only add if is a valid quiz - if invalid we just ignore
			if ($all_quizzes->validateQuizname($_POST["quiz_".$i]))
			{
				$post_quizzes[] = $_POST["quiz_".$i];
				if ($debug) {print $_POST["quiz_".$i]." ";}
			}
		}
	}
	if ($debug) {print "\n";}
	
	
	
	// If this is just a next no save
	// this will redirect with a header - we do not continue after this point
	if ($action == 'next')
	{
		getNextQuestion($questionid);
		// exit not really neccessary - makes it obvious we are not continuing
		exit (0);
	}
	

	// Intro
	if (isset ($_POST['intro'])) 
	{
		// Do not apply strict security validation as this can only be added by an administrator
		// This means that they could inject javascript etc, the same as if you allowed them to edit
		// a html template etc.
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['intro'] = stripslashes($_POST['intro']); }
		else {$post_details['intro'] = $_POST['intro'];}
		if ($debug) {print "Intro: ".$post_details['intro']."\n";}
	}
	
	
	// Input
	if (isset ($_POST['input'])) 
	{
		// Do not apply strict security validation as this can only be added by an administrator
		// This means that they could inject javascript etc, the same as if you allowed them to edit
		// a html template etc.
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['input'] = stripslashes($_POST['input']); }
		else {$post_details['input'] = $_POST['input'];}
		if ($debug) {print "Input: ".$post_details['input']."\n";}
	}
	
	
	// Type
	if (isset ($_POST['type']))
	{
		// check that it's a valid entry
		// if not we fail
		if (isset ($question_types[$_POST['type']]))
		{
			$post_details['type'] = $_POST['type'];
		}
		else
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Invalid question type");
			exit (0);
		}
		if ($debug) {print "Type: ".$post_details['type']."\n";}
	}
	
	
	// Answer
	if (isset ($_POST['answer'])) 
	{
		// As per Intro only minimal security checks
		//- perhaps in future add additional checking to ensure we don't have an invalid answer for the type
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['answer'] = stripslashes($_POST['answer']); }
		else {$post_details['answer'] = $_POST['answer'];}
	}
	else {$post_details['answer'] == '';}
	if ($debug) {print "Answer: ".$post_details['answer']."\n";}
	
	
	// Reason
	if (isset ($_POST['reason'])) 
	{
		// As per Intro only minimal security checks - this is a block of text
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['reason'] = stripslashes($_POST['reason']); }
		else {$post_details['reason'] = $_POST['reason'];}
	}
	else {$post_details['reason'] == '';}
	if ($debug) {print "Reason: ".$post_details['reason']."\n";}

	
	// Reference
	if (isset ($_POST['reference'])) 
	{
		// As per Intro only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['reference'] = stripslashes($_POST['reference']); }
		else {$post_details['reference'] = $_POST['reference'];}
	}	
	else {$post_details['reference'] == '';}
	if ($debug) {print "Reference: ".$post_details['reference']."\n";}
	
	
	// Hint
	if (isset ($_POST['hint'])) 
	{
		// As per Intro only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['hint'] = stripslashes($_POST['hint']); }
		else {$post_details['hint'] = $_POST['hint'];}
	}
	else {$post_details['hint'] == '';}
	if ($debug) {print "Hint: ".$post_details['hint']."\n";}
	
	// Image
	if (isset ($_POST['image'])) 
	{
		// As per Intro only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['image'] = stripslashes($_POST['image']); }
		else {$post_details['image'] = $_POST['image'];}
	}	
	else {$post_details['image'] == '';}
	if ($debug) {print "Image: ".$post_details['image']."\n";}
	

	// Comment
	if (isset ($_POST['comments'])) 
	{
		// As per Intro only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['comments'] = stripslashes($_POST['comments']); }
		else {$post_details['comments'] = $_POST['comments'];}
	}	
	else {$post_details['comments'] == '';}
	if ($debug) {print "Comment: ".$post_details['comments']."\n";}


	// Contributer
	if (isset ($_POST['qfrom'])) 
	{
		// As per Intro only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['qfrom'] = stripslashes($_POST['qfrom']); }
		else {$post_details['qfrom'] = $_POST['qfrom'];}
	}	
	else {$post_details['qfrom'] == '';}
	if ($debug) {print "Contributer: ".$post_details['qfrom']."\n";}


	// Email
	if (isset ($_POST['email'])) 
	{
		// As per Intro only minimal security checks
		//- may want to check for valid email format in future (or perhaps do that in Javascript)
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['email'] = stripslashes($_POST['email']); }
		else {$post_details['email'] = $_POST['email'];}
	}	
	else {$post_details['email'] == '';}	
	if ($debug) {print "Email: ".$post_details['email']."\n";}
	
	
	// Created
	// if it's a new question then created is today
	if ($questionid == 0)
	{
		$post_details['created'] = date('c');
	}
	else if (isset ($_POST['created']))
	{
		// check this is a valid date (don't neccessarily match end - if there is some time part on the string it will get dropped as we used $created_date[1]
		if (preg_match ('/^(\d{2,4})-(\d{2})-(\d{2})/', $_POST['created'], $created_date))
		{
			// use checkdate to make sure it's valid - note checkdate is middle endian
			if (checkdate ($created_date[2], $created_date[3], $created_date[1]))
			{
				$post_details['created'] = $created_date[0];
			}
		}
	}
	// if it didn't pass the tests set created to 0000-00-00
	if (!isset($post_details['created'])) {$post_details['created'] = '0000-00-00';}
	if ($debug) {print "Created: ".$post_details['created']."\n";}


	// set updated to current date
	$post_details['reviewed'] = date('c');

	
	// *** read through all parameters now perform save / update
	
	// then set $questionid so that we go back to editing this entry 
	if ($questionid == 0) 
	{
		$questionid = $qdb->addQuestion($post_details);
		$message .= "<p class=\"".CSS_CLASS_ADMIN_EDIT_MESSAGE."\">New question saved</p>";
	}
	else 
	{
		// we save even if no changes - more work for sql, but less checking within PHP
		// add questionid to array
		$post_details['questionid'] = $questionid;
		$qdb->updateQuestion($post_details);
		$message .= "<p class=\"".CSS_CLASS_ADMIN_EDIT_MESSAGE."\">Changes saved</p>";
	}
	
	
	
	if ($debug) {print "\nSave completed - questionid is $questionid\n";}
	
	if ($debug) {print "\nUpdating relationship entries for question / quiz\n";}
	
	// update the associated quiz rel entries
	// load question - this is a temp. We won't use this later, but instead reload with the updated rel entries 
	$question_temp = new Question($qdb->getQuestion($questionid));
	// now check that we have loaded question correctly - check that the db read was valid
	if ($questionid != $question_temp->getQuestionID()) 
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_INTERNAL, "Error reading back loaded file");
		exit (0);
	}
	
	// it's the shortname that we are using call - $this_quiz
	foreach ($quiz_array as $this_quiz=>$long_quizname)
	{
		// if currently set, but removed in save
		if ($question_temp->isInQuiz($this_quiz) && !in_array($this_quiz, $post_quizzes))
		{
			$qdb->delQuestionQuiz($this_quiz, $questionid);
		}
		// not set, but needs to be
		else if (!$question_temp->isInQuiz($this_quiz) && in_array($this_quiz, $post_quizzes))
		{
			$qdb->addQuestionQuiz($this_quiz, $questionid);
		}
	}

}
// note get is deliberately different to post (question instead of questionid)
// check it's a number - note is_int doesn't work 
elseif (isset($_GET['question']) && is_numeric($_GET['question']))
{
	$questionid = $_GET['question']; 
}


// if this is a save & next we go to the next page
// this will redirect with a header - we do not continue after this point
if ($action == 'save-next')
{
	getNextQuestion($questionid);
	// exit not really neccessary - makes it obvious we are not continuing
	exit (0);
}

// no questionid - error and back to index page
// 0 is used for new rather than edit
if ($questionid < 0)
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_QUESTION, "Unable to load question $questionid");
	// redirect to admin index page
	if (!$debug) {header("Location: ".ADMIN_FILE."?status=".WARNING_QUESTION);}
	exit (0);
}
elseif ($questionid > 0)
{
	// load questionid 
	$question = new Question($qdb->getQuestion($questionid));

	// now check that we have loaded question correctly - check that the db read was valid
	if ($questionid != $question->getQuestionID()) 
	{
		$err = Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Question parameter missing on edit page");
		// redirect to admin index page
		if (!$debug) {header("Location: ".ADMIN_FILE."?status=".WARNING_PARAMETER);}
		exit (0);
	}
}


/* At this point we have loaded the current question - (or it's a new) so display form */
require_once ($include_dir."adminmenu.php");


print "<h2>Edit Question</h2>\n";
print $message."\n";
print "<p>Do NOT use apostrophes etc, instead use their html equivelant.<br />(e.g. &amp;quot; = &quot;  &amp;amp;=&amp;)</p>\n";
print "<form action=\"".ADMIN_EDIT_FILE."\" method=\"post\">\n";
if ($questionid == 0) {print "<h3>New question</h3>\n";}
else {print "<h3>Question number: ".$questionid."</h3>\n";}
print "<input type=\"hidden\" name=\"questionid\" value=\"".$questionid."\" />\n";
print "Quizzes\n<ul>\n";



// Provide basic ul with checkboxes (if we are expecting a lot of quizzes could change this for a scroll box with the list within that 
$quiz_count = 0;


foreach ($quiz_array as $short_quizname=>$long_quizname)
{
	if ($questionid > 0 && $question->isInQuiz($short_quizname))
	{
		print "<li><input type=\"checkbox\" name=\"quiz_$quiz_count\" value=\"$short_quizname\" checked=\"checked\">$long_quizname</li>\n";
	}
	else
	{
		print "<li><input type=\"checkbox\" name=\"quiz_$quiz_count\" value=\"$short_quizname\">$long_quizname</li>\n";
	}
	$quiz_count++;
}
print "</ul>\n\n";
if ($quiz_count == 0) 
{
	print "<p><strong>No quizzes defined!</strong></p>\n\n";
}


// Intro
if ($questionid >0) {$value = $question->getIntro();}
else {$value = "";}
print "Intro:<br />\n";
print "<textarea name=\"intro\" cols=\"60\" rows=\"20\">";
print $value;
print "</textarea><br />\n";


// Type
print "Question Type:";
print "<select name=\"type\" id=\"type\">\n";
foreach ($question_types as $qtype_key=>$qtype_value)
{
	if ($questionid > 0 && $question->getType() == $qtype_key)
	{
		print " <option value=\"$qtype_key\" selected=\"selected\">$qtype_value</option>\n";
	}
	else
	{
		print " <option value=\"$qtype_key\">$qtype_value</option>\n";
	}
}
print "</select><br />\n";

// Input
if ($questionid >0) {$value = $question->getInput();}
else {$value = "";}
print "Input : <br />\n";
print "<textarea name=\"input\" cols=\"60\" rows=\"5\">";
print $value;
print "</textarea><br />\n";

// Answer
if ($questionid >0) {$value = $question->getAnswer();}
else {$value = "";}
print "Answer (<span id=\"".CSS_ID_EDIT_HINT_ANSWER."\"></span>) : <br />\n";
//(radio = number from 0; number = min,max; text = perl regexp no /; checkbox=digits of answer starting 0)
print "<textarea name=\"answer\" cols=\"60\" rows=\"5\">";
print $value;
print "</textarea><br />\n";

// Reason
if ($questionid >0) {$value = $question->getReason();}
else {$value = "";}
print "Reason (use &lt;b&gt; around the actual answer):<br />\n";
print "<textarea name=\"reason\" cols=\"60\" rows=\"10\">";
print $value;
print "</textarea><br />\n";

// Reference
if ($questionid >0) {$value = $question->getReference();}
else {$value = "";}
print "Reference: "; 
print "<input type=\"text\" name=\"reference\" value=\"$value\"><br />\n";

// Hint
if ($questionid >0) {$value = $question->getHint();}
else {$value = "";}
print "Hint: ";
print "<input type=\"text\" name=\"hint\" value=\"$value\"><br />\n";

// Image
if ($questionid >0) {$value = $question->getImage();}
else {$value = "";}
print "Image (URL): ";
print "<input type=\"text\" name=\"image\" value=\"$value\"><br />\n";

// Comment
if ($questionid >0) {$value = $question->getComments();}
else {$value = "";}
print "Comment (not shown to the user):<br />\n";
print "<textarea name=\"comments\" cols=\"60\" rows=\"5\">";
print $value;
print "</textarea><br />\n";

// Contributer
if ($questionid >0) {$value = $question->getQfrom();}
else {$value = "";}
print "Contributor: "; 
print "<input type=\"text\" name=\"qfrom\" value=\"$value\"><br />\n";

// Email
if ($questionid >0) {$value = $question->getEmail();}
else {$value = "";}
print "Contributor Email: ";
print "<input type=\"text\" name=\"email\" value=\"$value\"><br />\n";

// Created date
if ($questionid >0) {$value = $question->getcreated();}
else {$value = "0000-00-00";}
print "<input type=\"hidden\" name=\"created\" value=\"$value\"><br />\n";

print "<input name=\"save\" type=\"submit\" value=\"Save\" /> <input name=\"save\" type=\"submit\" value=\"Save and next\" /> <input name=\"save\" type=\"submit\" value=\"Next (without save)\" /> \n";

print "</form>\n";


// Display delete form
print "<form action=\"".ADMIN_DEL_Q_FILE."\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"questionid\" value=\"".$questionid."\" />\n";
print "<input type=\"submit\" value=\"Delete\" />\n";
print "</form>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



// Redirect to the question after the current one
// then exit
function getNextQuestion ($question_id)
{
	global $qdb;
	// load all questions
	$all_questions = $qdb->getQuestionQuiz();
	// search through array to find the current position - then identify the next
	foreach ($all_questions as $this_question)
	{
		if ($this_question['questionid'] == $question_id)
		{
			// note that in a foreach loop we have already incremented the pointer - so current shows the next entry in the array
			$next_question = current ($all_questions);
			// redirect to the next question (note if we have gone past end it will have reinitalised to first entry - so effectively wrap around
			header("Location: ".ADMIN_EDIT_FILE."?question=".$next_question['questionid']);
		}
	}
}



?>
