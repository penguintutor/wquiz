<?php
/* Upgrade from version 0.3.* (from perl to PHP version) */

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

/*$debug = false;

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);*/


// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';


// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

// must add this before we require the menu 
$admin_menu = 'home';

/*** Authentication ***/
// user must be logged in for any admin functions
// this needs to be before we output anything as it uses sessions (cookies)
$auth = new SimpleAuth ($settings->getSetting('admin_login_username'), $settings->getSetting('admin_login_password'), $settings->getSetting('admin_login_expirytime'));
// if not logged in redirect to login page
$status = $auth->checkLogin();
if ($status != 1) 
{
	// no from as use default which goes back to this page
	header("Location: ".ADMIN_LOGIN_FILE."?status=$status&location=aupgrade");
	// header will redirect to a new page so we just close this script
	exit (0);	//Important to stop script here
}
// If we reach here then login was successful
$sessionUsername = $auth->getUser();

// Have we get the filename of the old version in the post
if (isset($_POST['oldfile']) && $_POST['oldfile']!="")
{
	$oldfile = $_POST['oldfile'];
	// check that the file exists
	if (!file_exists($oldfile)) {printMenu("File does not exist - please try again"); exit(0);}
	
	// open file and read in appropriate entries
	if (!$ofh = fopen ($oldfile, "r")) {printMenu("Unable to read from file"); exit (0);}
	while ($this_line = fgets($ofh))
	{
		// get appropriate entries
		
		/**** WARNING - when reading below code perl variable names are used within php array
		/* this means $dbname (perl variable) is shown as a string '$dbname' ****/
		
		// database (previous version only supported mysql so look for the DBI string)
		if (preg_match ('/\$dbname\s?\=\s?"DBI:mysql:([\w_-]+):([\.\w_-]+)";/', $this_line, $matches))
		{
			// old entries are not used for database access - but are required for validation check
			$old_entries['$dbname'] = $matches[1];
			$old_entries['$dbhost'] = $matches[2];
			// database entries to access old db
			$old_db_entries['hostname'] = $matches[2];
			$old_db_entries['database'] = $matches[1];
		}
		elseif (preg_match ('/\$dbuser\s?=\s?"(\w+)";/', $this_line, $matches))
		{
			$old_entries['$dbuser'] = $matches[1];
			// database username
			$old_db_entries['username'] = $matches[1];
		}
		elseif (preg_match ('/\$dbpass\s?=\s?"(\w+)";/', $this_line, $matches))
		{
			$old_entries['$dbpass'] = $matches[1];
			// database password
			$old_db_entries['password'] = $matches[1];
			
		}
		elseif (preg_match ('/\$dbtable\s?=\s?"(\w+)";/', $this_line, $matches))
		{
			$old_entries['$dbtable'] = $matches[1];
		}
		
		// quizzes - all entries must be on a single line - may need to edit %quizintro line to reflect that
		elseif (preg_match ('/%quiznames\s?=\s?\(([^)]*)\);/', $this_line, $matches))
		{
			// don't breakout into individual entries at this stage
			$old_entries['%quiznames'] = $matches[1];
		}
		elseif (preg_match ('/%numquestions\s?=\s?\(([^)]*)\);/', $this_line, $matches))
		{
			// don't breakout into individual entries at this stage
			$old_entries['%numquestions'] = $matches[1];
		}
		elseif (preg_match ('/%quizintro\s?=\s?\((.*)\);/', $this_line, $matches))
		{
			// don't breakout into individual entries at this stage
			$old_entries['%quizintro'] = $matches[1];
		}
		// general settings
		elseif (preg_match ('/\$offlinequiz\s?=\s?(\d)\s?;/', $this_line, $matches))
		{
			$old_entries['$offlinequiz'] = $matches[1];
		}
		
	}
	//if ($debug) {print "Old dbname = ".$matches[1]."\n";}
	// check that we have all the important entries
	$db_entries_required = array('$dbname', '$dbhost', '$dbuser', '$dbpass', '$dbtable', '%quiznames', '%numquestions', '%quizintro');
	foreach ($db_entries_required as $this_db_entry)
	{
		if (!isset($old_entries[$this_db_entry]) || $old_entries[$this_db_entry] == '')
		{
			// %<variable> and $<variable> will be shown as $variable - minor bug
			printMenu ("Unable to find entry $this_db_entry in the old quiz.cfg file<br />\nPlease check it exists and is on a single line.");
			exit (0);
		}
		//if ($debug) {print "$this_db_entry is ".$old_entries[$this_db_entry]."\n";} 
	}
	
	
	$quiznames = splitPerlHash ($old_entries['%quiznames']);
	//if ($debug) {print "Quiz names:<br />\n"; print_r ($quiznames);}
	$numquestions = splitPerlHash ($old_entries['%numquestions']);
	//if ($debug) {print "Num questions:<br />\n"; print_r ($numquestions);}
	$quizintro = splitPerlHash ($old_entries['%quizintro']);
	//if ($debug) {print "Quiz intro:<br />\n"; print_r ($quizintro);}
	
	// Finished reading the config file
	
	// Create quiz entries if don't already exist
	// Load existing quizzes
	$all_quizzes = new Quizzes();
	$quiz_array = $qdb->getQuizzesAll();
	// add this one to allQuizzes
	foreach ($quiz_array as $this_quiz_array)
	{
		$all_quizzes->addQuiz(new Quiz($this_quiz_array));
	}
	
	if ($debug) {print "Checking for new quizzes <br />\n";}
	
	// Now look at old quiznames and check if they exist
	// use the validateQuizname function on the $all_quizzes
	foreach ($quiznames as $key=>$value)
	{
		// not found so create
		if ($all_quizzes->validateQuizname($key) == false)
		{
			if ($debug) {print "New quiz found $key <br />\n";}
			$new_quiz = array();
			// use new_quizname as well as the array to make it easier to follow (rather than nesting arrays)
			$new_quizname = $new_quiz['quizname'] = $key;
			$new_quiz['title'] = $quiznames[$new_quizname];
			$new_quiz['numquestions'] = $numquestions[$new_quizname];
			// offline quiz was not set on a per quiz basis on old version
			// set the same as online and then use enable to turn on / off
			$new_quiz['numquestionsoffline'] = $new_quiz['numquestions'];
			$new_quiz['quizintro'] = $quizintro[$new_quizname];
			// priority is a new setting
			$new_quiz['priority'] = 1;
			// if offline previously enabled then set appropriate - otherwise set to disabled
			if (isset($old_entries['$offlinequiz']) && ($old_entries['$offlinequiz'] == 1))
			{
				$new_quiz['enableoffline'] = true;
			}
			else
			{
				$new_quiz['enableoffline'] = false;
			}
			// online is always enabled
			$new_quiz['enableonline'] = true;
			
			/* Save new entry */
			$qdb->addQuiz($new_quiz);
			// we do not add to the array instead we will reload the quizzes once complete
			
		}
	}
	// re initialise array by reloading entries
	$all_quizzes = new Quizzes();
	$quiz_array = array();
	$quiz_array = $qdb->getQuizzesAll();
	// add this one to allQuizzes
	foreach ($quiz_array as $this_quiz_array)
	{
		$all_quizzes->addQuiz(new Quiz($this_quiz_array));
	}
	
	
	/*** Load questions from old database and store in new database ***/
	// Connect to the old database
	$olddb = new Database($old_db_entries);
	if ($db->getStatus() != 1) {die ("Unable to connect to database ".$old_db_entries['hostname']." ".$old_db_entries['database']);}
	
	// create sql to load questions
	$old_sql = "SELECT * from ".$old_entries['$dbtable'];
	
	// Loads all questions - note that this requires a significant 
	// amount of available memory - depending upon the number of questions and php settings
	// If this fails due to insufficient memory then check memory_limit in php.ini
	// Questions take up very little memory in the database so it would need to be a very large number of questions to be of concern
	$old_questions_array = $olddb->getRowsAll($old_sql);
	
	foreach ($old_questions_array as $this_question)
	{
		// create new question
		
		$this_questionid = $qdb->addQuestion($this_question);
		if ($debug) {print "Quizzes are ".$this_question['quiz']."<br />\n\n";}
		$this_question_quizzes = explode (',', $this_question['quiz']);
		if (!empty($this_question_quizzes))
		{
			foreach ($this_question_quizzes as $this_quiz)
			{
				if ($debug) {print "Adding $this_quiz<br />\n";}
				$qdb->addQuestionQuiz($this_quiz, $this_questionid);
			}
		}
		
	}
	
	//complete - confirm to user
	$templates->includeTemplate('header', 'admin');
	
	$admin_file = ADMIN_FILE;
	print <<< EOT
<h1>Upgrade complete</h1>
<p>The upgrade is complete. <a href="$admin_file">Go to admin index page</a>.</p>
EOT;
	
	
	$templates->includeTemplate('footer', 'admin');
	
}
else
{
	printMenu('');
}



function printMenu ($message)
{

global $templates;
	
// header template
$templates->includeTemplate('header', 'admin');

if ($message != "") {$message = "<p>$message</p>";}

$main_css = CSS_ID_ADMIN_MAIN;

print <<< EOT
<div id="$main_css">
<h1>Administration</h1>
<h2>Upgrade from 0.3.*</h2>
$message
<p>
Please provide the full path on the local filesystem to the old config file<br />
(eg. /var/www/cgi-bin/quiz/quiz.cfg)
</p>
<form method="post" action="">
<input type="text" name="oldfile" value=""></input><br />
<input type="submit" value="upgrade">
</form>
</div>
EOT;

// footer template
$templates->includeTemplate('footer', 'admin');

	
}


// splits a perl hash array into indvidual components
// recursive function
// array must uses either '' or "" around the key and value (value if strings only)
function splitPerlHash($in_array)
{
	global $debug;
	$this_array = array();
	//if ($debug) {print "in entries : $in_array";}
	
	// make a temp substitution to get around the \" error (if that's included in the string then it matches on the regexp at the wrong point) - we then substitute back later
	// bit of a hack, but should not have these strings in normally 
	
	$in_array = preg_replace ('/\\\"/', '##2', $in_array);
	$in_array = preg_replace ("/\\\'/", '##1', $in_array);
	
	// first look for string, second match looks for number (ie no quotes around it)
	// This will not work if the original quote marker is used within the string (even if escaped)
	// intentionally added an extra bracket set in the second as we don't need to use back references, but still need to have the same number of matches
	if (preg_match ('/(["\'])([^\1]+?)\1\s*=>\s*(["\'])([^\3]+?)\3\s*,?\s*(.*)/', $in_array, $matches) || preg_match ('/(["\'])([^\1]+?)\1\s*=>(\s*)(\d+)\s*,?\s*(.*)/', $in_array, $matches))
	{
		//if ($debug) {print "matches found : \n"; print_r ($matches);}
		
		$key = $matches[2];
		$value = $matches[4];
		$remaining = $matches[5];
		
		// substitute replacements back
		$value = preg_replace ("/##1/", "\\\'", $value);
		$value = preg_replace ("/##2/", '\\\"', $value);
		
		$this_array[$key] = $value;
		if ($remaining != '')
		{
			// Call self
			$returned_array = splitPerlHash($remaining);
			if (isset($returned_array) && !empty($returned_array)) {$this_array = array_merge ($this_array, $returned_array);}
		}
	}

	return $this_array;
	
		
}



?>
