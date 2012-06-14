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
//error_reporting(E_ALL);
//ini_set('display_errors', true);


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
	if ($debug) {print "Adding quiz ".$this_quiz_array['title']."\n";}
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}

// No quizzes found - most likely not setup
if ($all_quizzes->count() < 1) {header("Location: ".FIRST_FILE); exit(0);}

// header template - moved later to allow for quiz variable to be included
//$templates->includeTemplate('header', 'normal');

if ($debug) {print "Reading parameters \n";}
// first look for url get as expired (indicates question.php send us here due to an expired entry)
// we don't do anything differently other than tell the user that's why they were redirected there
if (isset($_GET['status']) && ($_GET['status'] == 'expired'))
{
	// todo - make customisable
	$message = "Session expired";
}

// is this result of POST - if so setup page, otherwise display menu
// note going to this page outside of quiz can result in rerunning session creation - hence new quiz
if (array_key_exists('quizname', $_POST))
{
	$quiz = $_POST['quizname'];
	//first check that this is just a string - no 
	if (!ctype_alnum($quiz)) 
	{
		$templates->includeTemplate('header', 'normal');
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_SECURITY, "Error security violation - quizname is invalid"); 
	}
	// default is online - changed by post value
	$quiz_type = 'online';
	// moved offline to a seperate file
	// if we want to allow offline option from main page (as a checkbox)
	// then we can instead repost to OFFLINE_FILE
	// not implemented
	//if (array_key_exists('offline', $_POST) && ($_POST == 'yes')) {$quiz_type = 'offline';}
	

	//check that this is a valid quizname
	// handle this as a warning using the errorEvent - we then provide a more user friendly error
	// this is not a security event, but is still wrong
	if (!$all_quizzes->validateQuizname($quiz))
	{
		// we handle error in more user friendly way than if we suspect attempt to hack
		$err =  Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Warning parameter incorrect - quizname is invalid");
		// we don't give an error just show menu
		$templates->includeTemplate('header', 'normal');
		printMenu($all_quizzes);
	}
	else
	{
		if ($debug) {print "Getting Quiz \n";}
		// Get quizobject for this particular quiz
		$this_quiz = $all_quizzes->getQuiz($quiz);
		// check this quiz is enabled in this mode - and is set to at least 1 question
		if ($this_quiz->isEnabled($quiz_type) == false || $this_quiz->getNumQuestions($quiz_type) < 1)
		{
			// quiz is disabled
			$templates->includeTemplate('header', 'normal');
			print "<h3>Selected quiz is disabled for $quiz_type use</h3>\n";
			$err =  Errors::getInstance();
			$err->errorEvent(INFO_QUIZSTATUS, "quiz $quiz is disabled for $quiz_type use");
			printMenu($all_quizzes);
		}
		else
		{
			// quiz is enabled if we have got this far
			// Get all the questions as question array (sql format - not objects)
			if ($debug) {print "Getting questions \n";}
			$question_array = $qdb->getQuestionQuiz($quiz);
			// random order array to randomise questions - we can then just take the first x number of questions
			if ($debug) {print "Randomise questions \n";}
			shuffle ($question_array);
			// check we have sufficient questions
			if (count($question_array) <= $this_quiz->getNumQuestions($quiz_type)) 
			{
				$templates->includeTemplate('header', 'normal');
				print "<h3>Insufficient questions in selected quiz</h3>\n";
				$err =  Errors::getInstance();
				$err->errorEvent(WARNING_QUIZQUESTIONS, "insufficient questions in $quiz, requires: "+$this_quiz->getNumQuestions($quiz_type)+" - has "+count($question_array));
				printMenu($all_quizzes);
			
			}
			else
			{
				if ($debug) {print "Have sufficient questions \n";}
				$random_questions = array();
				$answers = array();
				for ($i = 0; $i < $this_quiz->getNumQuestions($quiz_type); $i++)
				{
					// get the question numbers from the array (we have already shuffled to random order
					$random_questions[$i] = $question_array[$i]['questionid'];
					// set array with answers set to -1
					$answers[$i] = -1;
					//print "<p>question ".$random_questions[$i]." selected</p>\n"; 
				}
				/** We now have the questions - now create the session etc.**/
				if ($debug) {print "Creating session \n";}
				// store questions into session
				$quiz_session->setQuestions ($random_questions);
				$quiz_session->setAnswers ($answers);
				$quiz_session->setQuizName ($quiz);
				$quiz_session->setQuizTitle ($this_quiz->getTitle());
				$quiz_session->setStatus (SESSION_STATUS_ACTIVE);
				
				// Set the Quiz Title into the settings - used for html templates
				$settings->setTempSetting ("quiz_title", $this_quiz->getTitle());
				
				$templates->includeTemplate('header', 'normal');
				
				// Form starts at the top as future pages use options within form
				print "<form id=\"".CSS_ID_FORM."\" method=\"post\" action=\"".QUESTION_FILE."\">\n";
				

				print "<div id=\"".CSS_ID_QUIZ_INTRO."\">\n";
				print "<h3 id=\"".CSS_ID_QUIZ_TITLE."\">".$this_quiz->getTitle()."</h3>\n\n";
				// if this does not already include paragraphs then add
				$this_intro = $this_quiz->getIntro();
				if (!isParagraph($this_intro)) {print "<p>\n$this_intro\n</p>\n";}
				else {print $this_intro;}
				print "\n</div><!-- ".CSS_ID_QUIZ_INTRO." -->\n";

				// Add start button
				// Basic text button here - but replace with graphical buttons using CSS
				print "<div id=\"".CSS_ID_BUTTONS."\">";
				print "<input type=\"submit\" value=\"Start\" name=\"start\" />\n";
				print "</div><!-- ".CSS_ID_BUTTONS." -->\n";
								
				print "</form>\n";
				
				
				// do we have a start_text
				$start_text_file = $settings->getSetting ('start_text_file');
				
				if ($start_text_file != '')
				{
					print "<div id=\"".CSS_ID_INTRO_START."\">\n";
					include ($start_text_file); 
					print "</div>";
				
				}
				
				
			}
			
		}
		
	}
	
}
else 
{

	$templates->includeTemplate('header', 'normal');
	// show message if there is one
	if ($message != '') {print "<p class=\"".CSS_CLASS_MESSAGE."\">$message</p>\n";}
	printMenu($all_quizzes);

}

// footer template
$templates->includeTemplate('footer', 'normal');


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
	
	// getInstance of settings for later use within the function
	$settings = Settings::getInstance();
	
	// Display menu
	print "<div id=\"".CSS_ID_MENU."\">\n";
	print "<span class=\"".CSS_CLASS_MENU_TITLE."\"></span>\n";
	print ("<form method=\"post\" action=\"".INDEX_FILE."\" target=\"_top\">");

	$css_for_id = CSS_ID_OPTION_QUIZ;
	print <<<EOT
<fieldset>
<input name="style" value="default" type="hidden">


<p><label for="$css_for_id">Please select a quiz:</label>

EOT;
	print $quiz_object->htmlSelect('online');

	print <<<EOT

<input value=" Go " type="submit"></p>
</fieldset>
</form>

</div>

EOT;

	// do we have a start_text
	$index_text_file = $settings->getSetting ('index_text_file');
	
	if ($index_text_file != '')
	{
		print "<div id=\"".CSS_ID_INTRO_START."\">\n";
		include ($index_text_file); 
		print "</div>";
	
	}
}


?>
