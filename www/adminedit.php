<?php

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

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");
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
if (isset($_POST['questionid']))
{
	// if questionid is 0 then this is a create instead of an update
	//-- add save code here
	// we validate all details before storing them into an array (we then use this to save to DB)
	$post_details = array();
	




	// then set $questionid so that we go back to editing this entry 
}
// note get is deliberately different to post (question instead of questionid)
// check it's a number - note is_int doesn't work 
elseif (isset($_GET['question']) && is_numeric($_GET['question']))
{
	$questionid = $_GET['question']; 
}

// no questionid - error and back to index page
// 0 is used for new rather than edit
if ($questionid < 0)
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_QUESTION, "Unable to load question $questionid");
	// redirect to admin index page
	header("Location: ".ADMIN_FILE."?status=".WARNING_QUESTION);
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
		header("Location: ".ADMIN_FILE."?status=".WARNING_PARAMETER);
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

// get list of all quizzes to show
$quiz_array = $all_quizzes->getQuizNameArray();

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

// Type
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
print "<textarea name=\"comment\" cols=\"60\" rows=\"5\">";
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
if ($questionid >0) {$value = $question->getIntro();}
else {$value = "0000-00-00";}
print "<input type=\"hidden\" name=\"created\" value=\"$value\"><br />\n";

print "<input type=\"submit\" value=\"Save\" />\n";

print "</form>\n";

// footer template
$templates->includeTemplate('footer', 'admin');



?>
