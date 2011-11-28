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
if (isset($_POST['questionid']))
{
	// a post so save
	//-- add save code here
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
if ($quesionid == 0) {print "<h3>New question</h3>\n";}
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


print "Section (eg. chapter / subcategory):";
print "<input type=\"text\" name=\"section\" value=\"\"><br />\n";

print "Intro:<br />\n";
print "<textarea name=\"intro\" cols=\"60\" rows=\"20\"></textarea><br />\n";

print "Question Type:";
print "<select name=\"type\">\n";
print " <option value=\"text\">text</option>\n";
print " <option value=\"TEXT\">TEXT</option>\n";
print " <option value=\"number\">number</option>\n";
print " <option value=\"radio\">radio</option>\n";
print " <option value=\"checkbox\">checkbox</option>\n";
print "</select><br />\n";
// selected="selected"

print "Answer (radio = number from 0; number = min,max; text = perl regexp no /; checkbox=digits of answer starting 0): <br />\n";
print "<textarea name=\"answer\" cols=\"60\" rows=\"20\">";
print "</textarea><br />\n";

print "Reason (use &lt;b&gt; around the actual answer):<br />\n";
print "<textarea name=\"reason\" cols=\"60\" rows=\"10\">";
print "</textarea><br />\n";

print "Reference: "; 
print "<input type=\"text\" name=\"reference\" value=\"\"><br />\n";

print "Hint: ";
print "<input type=\"text\" name=\"hint\" value=\"\"><br />\n";

print "Image (URL): ";
print "<input type=\"text\" name=\"image\" value=\"\"><br />\n";

print "Comment (not shown to the user):<br />\n";
print "<textarea name=\"comment\" cols=\"60\" rows=\"5\">";
print "</textarea><br />\n";

print "Contributor: "; 
print "<input type=\"text\" name=\"qfrom\" value=\"\"><br />\n";

print "Contributor Email: ";
print "<input type=\"text\" name=\"email\" value=\"\"><br />\n";
print "<input type=\"hidden\" name=\"created\" value=\"0000-00-00\"><br />\n";
print "<input type=\"submit\" value=\"Save\" />\n";

print "</form>\n";

// footer template
$templates->includeTemplate('footer', 'admin');



?>
