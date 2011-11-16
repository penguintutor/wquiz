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
}
// note get is deliberately different to post (question instead of questionid)
// check it's a number
elseif (isset($_GET['question']) && is_int($_GET['question']))
{
	$questionid =$_GET['question']; 
	// Show edit
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
	
	
	
	
}
// no questionid - error and back to index page
else 
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_QUESTION, "Unable to load question $questionid");
	// redirect to admin index page
	header("Location: ".ADMIN_FILE."?status=questionerror");
	exit (0);
}




// questions
$questions_array = $qdb->getQuestionIDs();


require_once ($include_dir."adminmenu.php");


print <<< EOT
<h1>Questions</h1>
<table>
<tr>
	<th>Question</th>
	<th>Summary</th>
	<th>Type</th>
	<th>Quiz</th>
	<th>Created</th>
	<th>Reviewed</th>
</tr>
EOT;

/** Display questions - could use a table formatter function, but for now coded in this file **/

foreach ($questions_array as $this_question_entry)
{
	print "<tr>\n";
	//print "<td>$this_question_entry</td>";
	$this_question = new Question($qdb->getQuestion($this_question_entry));
	print "<td>$this_question_entry</td>";
	//print "<td>-</td>";
	print "<td>".$this_question->getSummary()."</td>";
	print "<td>".$this_question->getType()."</td>";
	print "<td>".$this_question->getQuizzes()."</td>";
	print "<td>".$this_question->getCreated()."</td>";
	print "<td>".$this_question->getReviewed()."</td>\n";
	print "</tr>\n";
}

print "</table>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



?>
