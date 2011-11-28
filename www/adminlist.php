<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

// must add this before we require the menu 
$admin_menu = 'list';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// Authentication class required for admin functions
require_once("includes/SimpleAuth.php");
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

/** - not yet used - for managing quizzes **/
/*// get all the quizzes and add to object
$all_quizzes = new Quizzes();
$quiz_array = $qdb->getQuizzesAll();
// add this one to allQuizzes
foreach ($quiz_array as $this_quiz_array)
{
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}*/

// header template
$templates->includeTemplate('header', 'admin');

// questions
$questions_array = $qdb->getQuestionIDs();


require_once ($include_dir."adminmenu.php");

print "<h1>Questions</h1>";
// question = 0 used for create new
print "<a href=\"".ADMIN_EDIT_FILE."?question=0\">Add new question</a><br />\n";


print <<< EOT
<table>
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

foreach ($questions_array as $this_question_entry)
{
	print "<tr>\n";
	//print "<td>$this_question_entry</td>";
	$this_question = new Question($qdb->getQuestion($this_question_entry));
	print "<td>$this_question_entry</td>";
	//print "<td>-</td>";
	print "<td><a href=\"".ADMIN_EDIT_FILE."?question=".$this_question->getQuestionID()."\">".$this_question->getSummary()."</a></td>";
	print "<td>".$this_question->getType()."</td>";
	print "<td>".$this_question->getQuizzes()."</td>";
	print "<td>".$this_question->getCreated()."</td>";
	print "<td>".$this_question->getReviewed()."</td>\n";
	print "<td><a href=\"".ADMIN_Q_FILE."?question=".$this_question->getQuestionID()."\">Test</a></td>\n";
	print "</tr>\n";
}

print "</table>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



?>
