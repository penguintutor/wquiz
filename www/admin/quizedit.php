<?php

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
// action should be new or edit
// note edit rather than save (save is used in post) as that is what we are doing in this
$action = '';
$quizname = '';

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");
// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");
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
	elseif ($_POST['action'] == 'save') {$action = 'edit';}
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
		if ($action=='edit' && !array_key_exists($_POST['quizname'], $quiz_array)) 
		{
			$err = Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Not a valid quizname (does not exist)");
			exit (0);
		}
		// if it's not existing then make sure it doesn't exist
		elseif ($action=='new' && array_key_exists($_POST['quizname'], $quiz_array)) 
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
		if ($_POST['numquestions'] >= 0 && $_POST['numquestions'] <= $settings->getSetting('quiz_max_questions'))
		{
			$post_details['numquestions'] = $_POST['numquestions'];
		}
		else
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
		if ($_POST['numquestionsoffline'] >= 0 && $_POST['numquestionsoffline'] <= $settings->getSetting('quiz_max_questions'))
		{
			$post_details['numquestionsoffline'] = $_POST['numquestionsoffline'];
		}
		else
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

	// if it's a new one we have just created now change to edit and add this quiz
	if ($action=='new')
	{
		$action = 'edit';
		// load this quiz and add to the all quiz
		// Not needed (we don't use all_quiz again (just load the current quiz)
		//$new_quiz = $qdb->getQuiz($quizname);
		//$all_quizzes->addQuiz(new Quiz($new_quiz));
	}
	
}
// else if it's a edit with quizname provided
elseif (isset($_GET['quiz']) && ctype_alnum($_GET['quiz']))
{
	// check it's valid existing
	if ($all_quizzes->validateQuizname($_GET['quiz']))
	{
		$quizname = $_GET['quiz'];
		$action = 'edit';
	}
	else
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Invalid quizname");
		exit (0);
	}
}
elseif (isset($_GET['action']) && $_GET['action'] == 'new')
{
	$action = 'new';
}
// no parameter - not allowed
else
{
	$err = Errors::getInstance();
	$err->errorEvent(ERROR_PARAMETER, "Missing action request");
	exit (0);
}


// no quizname for edit - error and back to index page
if ($action == 'edit' && $quizname == '')
{
	$err = Errors::getInstance();
	$err->errorEvent(WARNING_PARAMETER, "A valid quiz name has not been provided");
	// redirect to admin index page
	if (!$debug) {header("Location: ".ADMIN_FILE."?status=".WARNING_PARAMETER);}
	exit (0);
}
else
{
	$this_quiz = $qdb->getQuiz($quizname);
	// if quiz didn't load properly
	if ($this_quiz['quizname'] != $quizname)
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_INTERNAL, "Unable to read the quiz information");
		exit (0);
	}
		
}		




/* At this point we have loaded the current quiz - (or it's a new) so display form */
require_once ($include_dir."adminmenu.php");


print "<h2>Edit Quiz</h2>\n";
print "<p>Do NOT use apostrophes etc, instead use their html equivelant.<br />(e.g. &amp;quot; = &quot;  &amp;amp;=&amp;)</p>\n";
print "<form action=\"".ADMIN_EDIT_QUIZ_FILE."\" method=\"post\">\n";
if ($action == 'new') {print "<h3>New quiz</h3>\n";}
else {print "<h3>Quiz name: ".$this_quiz['title']." ($quizname)</h3>\n";}

if ($action == 'new')
{
	print "<input type=\"hidden\" name=\"action\" value=\"new\" /></h3>\n";
}
else
{
	print "<input type=\"hidden\" name=\"quizname\" value=\"".$quizname."\" /></h3>\n";
	print "<input type=\"hidden\" name=\"action\" value=\"save\" /></h3>\n";
}

// Short quizname - only if new (cannot edit this field)
if ($action == 'new')
{
	print "Quizname (shortname alpha numeric): "; 
	print "<input type=\"text\" name=\"quizname\" value=\"\"><br />\n";
}


// Title
if ($action != 'new') {$value = $this_quiz['title'];}
else {$value = "";}
print "Title: "; 
print "<input type=\"text\" name=\"title\" value=\"$value\"><br />\n";



// Number of questions
if ($action != 'new') {$value = $this_quiz['numquestions'];}
else {$value = "";}
print "Number of questions: "; 
print "<input type=\"text\" name=\"numquestions\" value=\"$value\"><br />\n";


// Number of questions (offline)
if ($action != 'new') {$value = $this_quiz['numquestionsoffline'];}
else {$value = "";}
print "Number of offline questions: "; 
print "<input type=\"text\" name=\"numquestionsoffline\" value=\"$value\"><br />\n";


// Quiz Intro
if ($action != 'new') {$value = $this_quiz['quizintro'];}
else {$value = "";}
print "Quiz introduction text:<br />\n";
print "<textarea name=\"quizintro\" cols=\"60\" rows=\"20\">";
print $value;
print "</textarea><br />\n";



// Priority
if ($action != 'new') {$value = $this_quiz['priority'];}
else {$value = "1";}
print "Priority (highest first): "; 
print "<input type=\"text\" name=\"priority\" value=\"$value\"><br />\n";


// enable (default yes)
print "Enable online:\n";
if ($action == 'new' || $this_quiz['enableonline'])
{
	print "<input type=\"checkbox\" name=\"enableonline\" checked=\"checked\"><br />\n";
}
else
{
	print "<input type=\"checkbox\" name=\"enableoffline\"><br />\n";
}

// enable offline (default yes)
print "Enable offline:\n";
if ($action == 'new' || $this_quiz['enableoffline'])
{
	print "<input type=\"checkbox\" name=\"enableoffline\" checked=\"checked\"><br />\n";
}
else
{
	print "<input type=\"checkbox\" name=\"enableoffline\"><br />\n";
}




print "<input type=\"submit\" value=\"Save\" />\n";

print "</form>\n";


// Display delete form
print "<form action=\"".ADMIN_DEL_QUIZ_FILE."\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"quizname\" value=\"".$quizname."\" />\n";
print "<input type=\"submit\" value=\"Delete\" />\n";
print "</form>\n";


// footer template
$templates->includeTemplate('footer', 'admin');



?>
