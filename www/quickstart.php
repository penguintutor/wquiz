<?php
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


/* Quickstart - displays menu then passes on to index.php
does not include any headers or processing of forms
designed to be embedded within a page as a php include */

//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

if ($debug) {print "Loading Quizzes \n";}
// get all the quizzes and add to object
$all_quizzes = new Quizzes();
$quiz_array = $qdb->getQuizzesAll();
// add this one to allQuizzes
foreach ($quiz_array as $this_quiz_array)
{
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}

// No quizzes found - most likely not setup
if ($all_quizzes->count() < 1) {header("Location: ".FIRST_FILE); exit(0);}


if ($debug) {print "Reading parameters \n";}

printMenu($all_quizzes);


// Debug mode - display any errors / warnings
if (isset($debug) && $debug)
{
	$err =  Errors::getInstance();
	if ($err->numEvents(INFO_LEVEL) > 0)
	{
		print "Errors:\n";
		print $err->listEvents(INFO_LEVEL);
	}
}




/*** Functions ***/

// show here as we will do when we get a warning as well
function printMenu ($quiz_object)
{
	global $IndexFile;
	
	if (isset ($IndexFile)) {$index_file = $IndexFile;}
	else {$index_file = INDEX_FILE;}
	
	// Display menu
	print "<div id=\"".CSS_ID_MENU."\">\n";
	print "<span class=\"".CSS_ID_MENU_TITLE."\"></span>\n";
	print ("<form method=\"post\" action=\"".$index_file."\" target=\"_top\">");

	print <<<EOT
<fieldset>
<input name="style" value="default" type="hidden">


<!-- <p><label for="name">Please enter your name:</label>
<input id="name" name="name" type="text"></p> -->
<p><label for="quiz">Please select a quiz:</label>
EOT;
	print $quiz_object->htmlSelect('online');

	print <<<EOT

<input value=" Go " type="submit"></p>
</fieldset>
</form>

</div>

EOT;
}



?>
