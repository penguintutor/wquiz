<?php

/*** Warning do not use as it - search for //- for where changes needed ***/

/* New / Edit / Save quiz */
/* Note error checking is quite brutal - we rely on javascript to provide more
user friendly error checking before we get to this page */
/* This is even more brutal than questions to stop breaking the quiz
eg. if number is not numeric we break rather than setting to default */


/* We get to here from one of the following
$action=new		(create new)
$quiz=quizname	(edit existing)
POST action=	(either 'new' or 'save')
*/

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

// must add this before we require the menu 
$admin_menu = 'editquiz';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';
// action should be new or save
$action = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

/*** Further setup ***/
// get all the quizzes and add to object (use to check unique quizname)
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


// header template
$templates->includeTemplate('header', 'admin');


/** Edit or Save ? **/
// if post it's a save
if (isset($_POST['action']))
{
	if ($_POST['action'] == 'new') {$action = 'new';}
	else if ($_POST['action'] == 'save') {$action = 'save';}
	else 
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Missing action");
		exit (0);
	}
	
	// Quizname (short) can only be alphanumeric
	if (isset($_POST['quizname']) &&  ctype_alnum($_POST['quizname']))
	{
		// If this is existing then make sure that this exists
		if ($action=='save' && !array_key_exists($_POST['quizname'], $quiz_array)) 
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Not a valid quizname (does not exist)");
			exit (0);
		}
		// if it's not existing then make sure it doesn't exist
		else if ($action=='new' && array_key_exists($_POST['quizname'], $quiz_array)) 
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Not a valid quizname (new quizname)");
			exit (0);
		}
	}
	else
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Quizname missing or contains invalid characters");
		exit (0);
	}
	// now checked it is valid and now save
	$quizname = $_POST['quizname'];
	$post_details['quizname'] = $quizname;
	if ($debug) {print "Quizname is $quizname";}
	
	
	// Title
	if (isset ($_POST['title'])) 
	{
		// Do not apply strict security validation as this can only be added by an administrator
		// This means that they could inject javascript etc, the same as if you allowed them to edit
		// a html template etc.
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['title'] = stripslashes($_POST['title']); }
		else {$post_details['title'] = $_POST['title'];}
	}
	// don't allow an empty title
	else 
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Title not provided");
		exit (0);
	}
	if ($debug) {print "Title: ".$post_details['title']."\n";}
	
	
	// Number of questions
	if (isset ($_POST['numquestions']) && is_numeric($_POST['numquestions']))
	{
		// check it's positive and a sensible number - 0 is acceptable (eg. as well as enabled - but should only be used for disabled quizzes - we only allow enable if more than 0) 
		if ($_POST['numquestions'] < 0 || $_POST['numquestions'] > $settings->getSetting('quiz_max_questions'))
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Invalid number of questions ".$_POST['numquestions']);
			exit (0);
		}
	}
	else
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Numquestions not provided or is not numeric");
		exit (0);
	}

	// Number of questions - offline
	if (isset ($_POST['numquestionsoffline']) && is_numeric($_POST['numquestionsoffline']))
	{
		// check it's positive and a sensible number - 0 is acceptable (eg. as well as enabled - but should only be used for disabled quizzes - we only allow enable if more than 0) 
		if ($_POST['numquestionsoffline'] < 0 || $_POST['numquestionsoffline'] > $settings->getSetting('quiz_max_questions'))
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Invalid number of questions (offline) ".$_POST['numquestionsoffline']);
			exit (0);
		}
	}
	else
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Numquestionsoffline not provided or is not numeric");
		exit (0);
	}

	
	
	// Quizintro
	if (isset ($_POST['quizintro'])) 
	{
		// Only minimal security checks
		// remove magicquotes as they will be added when we put into the database anyway
		if (get_magic_quotes_gpc()) { $post_details['quizintro'] = stripslashes($_POST['quizintro']); }
		else {$post_details['quizintro'] = $_POST['quizintro'];}
	}
	else {$post_details['quizintro'] == '';}
	if ($debug) {print "Quiz Intro: ".$post_details['quizintro']."\n";}
	
	
	//Priority
	if (isset ($_POST['priority']) && is_numeric($_POST['priority']))
	{
		$post_details['priority'] = $_POST['priority']; 
	}
	else
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Numquestionsoffline not provided or is not numeric");
		exit (0);
	}

	
	//Enable online
	if (isset ($_POST['enableonline'])) 
	{
		$post_details['enableonline'] = true;
	}
	else
	{
		$post_details['enableonline'] = false;
	}	
	
	//Enable offline (checkbox)
	if (isset ($_POST['enableoffline'])) 
	{
		$post_details['enableoffline'] = true;
	}
	else
	{
		$post_details['enableoffline'] = false;
	}
	
	
	// *** read through all parameters now perform save / update
	
	// then set $questionid so that we go back to editing this entry 
	if ($action == 'new') {$qdb->addQuiz($post_details);}
	else 
	{
		// we save even if no changes - more work for sql, but less checking within PHP
		$qdb->updateQuiz($post_details);
	}
	
	
	if ($debug) {print "\nSave completed - quiznname is $quizname\n";}

	// if it's a new one now change to edit and add this quiz
	if ($action=='new')
	{
		$action = 'edit';
		//- load this quiz
	}
	
}
elseif (isset($_GET['quiz']) && ctype_alnum($_GET['alphnum']))
{
	// check it's valid existing
	//-
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
 
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
print "<p>Do NOT use apostrophes etc, instead use their html equivelant.<br />(e.g. &amp;quot; = &quot;  &amp;amp;=&amp;)</p>\n";
print "<form action=\"".ADMIN_EDIT_FILE."\" method=\"post\">\n";
if ($questionid == 0) {print "<h3>New question</h3>\n";}
else {print "<h3>Question number: ".$questionid."</h3>\n";}
print "<input type=\"hidden\" name=\"questionid\" value=\"".$questionid."\" /></h3>\n";
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

print "<input type=\"submit\" value=\"Save\" />\n";

print "</form>\n";

// footer template
$templates->includeTemplate('footer', 'admin');



?>
