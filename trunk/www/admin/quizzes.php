<?php

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

// must add this before we require the menu 
$admin_menu = 'listquizzes';
//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
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

// get all the quizzes and add to object
$all_quizzes = new Quizzes();
$quiz_array = $qdb->getQuizzesAll();
// add this one to allQuizzes
foreach ($quiz_array as $this_quiz_array)
{
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}

// header template
$templates->includeTemplate('header', 'admin');

require_once ($include_dir."adminmenu.php");

print "<div id=\"".CSS_ID_ADMIN_MAIN."\">\n";

print "<h1>Questions</h1>";
// question = 0 used for create new
print "<form method=\"get\" action=\"".ADMIN_EDIT_QUIZ_FILE."\">\n";
print "<input type=\"hidden\" name=\"action\" value=\"new\" />\n";
print "<input type=\"submit\" value=\"Add new quiz\">\n";
print "</form>\n";

print "<table class=\"".CSS_CLASS_ADMIN_TABLE."\">\n";
print <<< EOT
<tr>
	<th>Quiz name</th>
	<th>Title</th>
	<th></th>
</tr>
EOT;

/** Display questions - could use a table formatter function, but for now coded in this file **/

foreach ($all_quizzes->getQuizNameArray() as $this_quizname=>$this_title)
{
	print "<tr>\n";
	print "<td><a href=\"".ADMIN_EDIT_QUIZ_FILE."?quiz=".$this_quizname."\">$this_quizname</a></td>";
	print "<td><a href=\"".ADMIN_EDIT_QUIZ_FILE."?quiz=".$this_quizname."\">$this_title</a></td>";
	// this column left if we want to add a list option
	print "<td></td>\n";
	print "</tr>\n";
}

print "</table>\n";
print "</div>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



?>
