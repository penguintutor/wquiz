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
//require_once ($include_dir."Quizzes.php");

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
// check it's a number
elseif (isset($_GET['question']) && is_int($_GET['question']))
{
	$questionid = $_GET['question']; 
	// Show edit
	
}

// no questionid - error and back to index page
if ($questionid < 1)
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_QUESTION, "Unable to load question $questionid");
	// redirect to admin index page
	header("Location: ".ADMIN_FILE."?status=questionerror");
	exit (0);
}


// load questionid 
$question = new Question($qdb->getQuestion($question));
// now check that we have loaded question correctly - check that the db read was valid
if ($question->getQuestionID != $questionid) 
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_PARAMETER, "Question parameter missing on edit page");
	// redirect to admin index page
	header("Location: ".ADMIN_FILE."?status=error");
	exit (0);
}
/* At this point we have loaded the current question - display form */

require_once ($include_dir."adminmenu.php");


print "<h2>Edit Question</h2>\n";
print "<p>Do NOT use apostrophes etc, instead use their html equivelant.<br />(e.g. &amp;quot; = &quot;  &amp;amp;=&amp;)</p>\n";
print "<form action=\"".ADMIN_EDIT_FILE."\" method=\"post\">\n";
print "<h3>Question number: ".$question->getQuestionID()."</h3>\n";
print "<input type=\"hidden\" name=\"questionid\" value=\"".$question->getQuestionID()."\" /></h3>\n";
print "Quiz Categories\n";
//-- add category list option
print "Section (eg. chapter / subcategory):";
/*
Quiz Categories:
<ul>
<li><input type="checkbox" name="quiz_0" value="firstaid" checked="checked">firstaid</li>
<li><input type="checkbox" name="quiz_1" value="radio">radio</li>

<li><input type="checkbox" name="quiz_2" value="medgas">medgas</li>
<li><input type="checkbox" name="quiz_3" value="all" checked="checked">all</li>
<li><input type="checkbox" name="quiz_4" value="ecs" checked="checked">ecs</li>
<li><input type="checkbox" name="quiz_5" value="ambaid">ambaid</li>
<li><input type="checkbox" name="quiz_6" value="ambdriving">ambdriving</li>
<li><input type="checkbox" name="quiz_7" value="babychild">babychild</li>
<li><input type="checkbox" name="quiz_8" value="advanced" checked="checked">advanced</li>
<li><input type="checkbox" name="quiz_9" value="work" checked="checked">work</li>
<li><input type="checkbox" name="quiz_10" value="beginner" checked="checked">beginner</li>

</ul>
Section (e.g. chapter / subcategory): <input type="text" name="section" value=""><br />
Intro:<br /><textarea name="intro" cols="40" rows="10">What does the B stand for in the ABC check?
<p>
<b>A</b>irway<br>
<b>B</b>?<br>
<b>C</b>irculation<br></textarea><br />
Input (pre,actual,post), or (comma seperated radio options): <br /><textarea name="input" cols="40" rows="10">Bleeding,Breathing,Breaks (Fractures),Bones</textarea><br />
Question Type: <select name="type">
<option value="text">text</option>
<option value="TEXT">TEXT</option>
<option value="number">number</option>

<option value="radio" selected="selected">radio</option>
<option value="checkbox">checkbox</option>
</select><br />
Answer (radio = number from 0; number = min,max; text = perl regexp no /; checkbox=digits of answer starting 0): <br /><textarea name="answer" cols="40" rows="10">1</textarea><br />
Reason (use &lt;b&gt; around the actual answer):<br />
<textarea name="reason" cols="40" rows="5"><b>Breathing</b>. This is the ABC check listing the priorities when treating any casualty.</textarea><br />
Reference: <input type="text" name="reference" value="9th edition pg 45"><br />

Hint: <input type="text" name="hint" value=""><br />
Image (URL): <input type="text" name="image" value="/images/questions/firstaid1.gif"><br />
Comment (not shown to the user):<br />
<textarea name="comment" cols="40" rows="5">From old quiz</textarea><br />
Contributor: <input type="text" name="qfrom" value="Stewart Watkiss"><br />
Contributor Email: <input type="text" name="email" value="stewart.quiz@watkissonline.co.uk"><br />
<input type="hidden" name="created" value="0000-00-00"><br />
<input type="submit" value="Save" />*/

print "</form>\n";

// footer template
$templates->includeTemplate('footer', 'admin');



?>
