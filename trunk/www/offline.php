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


//$debug = true;

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';

require_once("includes/setup.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

// debug will prevent javascript being included in headers
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

// header template - moved to later to allow embed javascript
// still use normal header / footer
// it's the javascript print option where we use offline headers instead 
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
	// Very important
	// todo 
	// validate field input
	$quiz = $_POST['quizname'];
	//first check that this is just a string - no 
	if (!ctype_alnum($quiz)) 
	{
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_SECURITY, "Error security violation - quizname is invalid");
		exit (0);
	}
	// set quiztype to offline
	$quiz_type = 'offline';
		

	//check that this is a valid quizname
	// handle this as a warning using the errorEvent - we then provide a more user friendly error
	// this is not a security event, but is still wrong
	if (!$all_quizzes->validateQuizname($quiz))
	{
		// include header for menu / error display
		$templates->includeTemplate('header', 'normal');
		// we handle error in more user friendly way than if we suspect attempt to hack
		$err =  Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Warning parameter incorrect - quizname is invalid");
		print "<h3>Invalid quizname specified</h3>\n";
		printMenu($all_quizzes);
		$templates->includeTemplate('footer', 'normal');
		exit (0);
	}
	else
	{
		if ($debug) {print "Getting Quiz \n";}
		// Get quizobject for this particular quiz
		$this_quiz = $all_quizzes->getQuiz($quiz);
		// check this quiz is enabled in this mode - and is set to at least 1 question
		if ($this_quiz->isEnabled($quiz_type) == false || $this_quiz->getNumQuestions($quiz_type) < 1)
		{
			// include header for this error / menu display
			$templates->includeTemplate('header', 'normal');
			// quiz is disabled
			print "<h3>Selected quiz is disabled for $quiz_type use</h3>\n";
			$err =  Errors::getInstance();
			$err->errorEvent(INFO_QUIZSTATUS, "quiz $quiz is disabled for $quiz_type use");
			printMenu($all_quizzes);
			$templates->includeTemplate('footer', 'normal');
			exit (0);
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
				$templates->includeTemplate('footer', 'normal');
				exit (0);
			}
			else
			{
				// to allow header javascript we defer all printing by instead saving into $print_text;
				$print_text = '';
				
				if ($debug) {print "Have sufficient questions \n";}
				$random_questions = array();
				$answers = array();
				for ($i = 0; $i < $this_quiz->getNumQuestions($quiz_type); $i++)
				{
					// get the question numbers from the array (we have already shuffled to random order
					$random_questions[$i] = $question_array[$i]['questionid'];
					// set array with answers set to -1
					$answers[$i] = -1;
				}
				/** We now have the questions - now create the session etc.**/
				// create a random reference id
				$offline_id = $quiz."_".rand(1000, 99999);
				
				if ($debug) {print "Creating session \n";}
				// store questions into session
				$quiz_session->setQuestions ($random_questions);
				$quiz_session->setAnswers ($answers);
				$quiz_session->setStatus (SESSION_STATUS_OFFLINE);
				$quiz_session->setQuizName ($quiz);
				$quiz_session->setOfflineId ($offline_id);
				// $quiz_session->getOfflineId()
				
				$print_text .= "<div id=\"".CSS_ID_QUIZ_INTRO."\">\n";
				$print_text .= "<h3 id=\"".CSS_ID_QUIZ_TITLE."\">".$this_quiz->getTitle()."</h3>\n\n";
				$print_text .= "<h3>Quiz reference: $offline_id</h3>\n\n";
				$print_text .= "<p>Please ensure that you print off both the questions and answers for the above reference number as answers cannot be retrieved at a later date</p>";
				// if this does not already include paragraphs then add
				$this_intro = $this_quiz->getIntro();
				if (!isParagraph($this_intro)) {$print_text .= "<p>\n$this_intro\n</p>\n";}
				else {$print_text .= $this_intro;}
				$print_text .= "\n</div><!-- ".CSS_ID_QUIZ_INTRO." -->\n";

				// Provide buttons to print questions and answers
				// Basic text button here - or replace with graphical buttons using CSS
				// This is a basic form if Javascript is not enabled - if javascript is enabled 
				// then we replace with two buttons - one for question print, the other for answer print

				// Type is "basic" for normal inline display - with link to answers
				// or "popup" if this is a pop-up window and we show basic formatting without links
				// convert some statics to variables so that they work within the heredoc format
				$form_id = CSS_ID_FORM;
				$question_file = OFFLINE_QUESTION_FILE;
				$answer_file = OFFLINE_ANSWER_FILE;
				$start_element_id = CSS_ID_OFFLINE_START;
				$static_html = <<< STATICHTML
<form id="$form_id" method="post" action="$question_file">
<input type="hidden" name="type" name="basic" />
<input type="submit" value="Offline questions" name="start" />
</form>		
STATICHTML;
				
				
				$print_text .= "<div id=\"".CSS_ID_OFFLINE_START."\">";
				// Form within the div 
				$print_text .= $static_html;
				
				$print_text .= "</div><!-- ".CSS_ID_OFFLINE_START." -->\n";
				
				/* Javascript to change button to popup print buttons */
				$start_element_id = CSS_ID_OFFLINE_START;

				$offline_file = OFFLINE_FILE;
				
				// replacement html
				$active_html = <<< ACTIVEHTML

<form action="">
<input type="button" name="questions" value="Print Questions" onclick="offlinePrint(\'questions\', \'$question_file\')"></input>
<input type="button" name="answers" value="Print Answers" onclick="offlinePrint(\'answers\', \'$answer_file\')"></input>
</form>
<form action="$offline_file" method="post">
<input type="submit" name="restart" value="Start again"></input>
</form>
ACTIVEHTML;

				// Javascript for inclusion in header
				// Pass javascript global variables 
				$templates->addHeaderJavascript("var page = 'offline';");
				$templates->addHeaderJavascript("var page_status = 'printquiz';");
				//$templates->addHeaderJavascript("var start_element = document.getElementById('".CSS_ID_OFFLINE_START."');");
				$templates->addHeaderJavascript("var start_element_id = '".CSS_ID_OFFLINE_START."';");
				$templates->addHeaderJavascript("var static_html = '".$templates->textToJavascript($static_html)."';");
				$templates->addHeaderJavascript("var active_html = '".$templates->textToJavascript($active_html)."';");
				//$templates->addHeaderJavascript("window.addEventListener(\"load\", eventWindowLoaded, false);");
				//$templates->addHeaderJavascript("window.onload = offlineEnableActive();");


/*				$print_text .= <<< INLINEJS

<script type="text/javascript">


function activeButtons()
{
	var start_element = document.getElementById("$start_element_id");
	start_element.innerHTML = $active_html;
}





activeButtons();


</script>
				
INLINEJS;
*/

				// print here - deferred from before to allow javascript to be included in headers 
				$templates->includeTemplate('header', 'normal');
				print $print_text;

			}
		}
		
	}
	
}
else 
{
	// print header here for menu
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
	
	// Display menu
	print "<div id=\"".CSS_ID_MENU."\">\n";
	print "<span class=\"".CSS_ID_MENU_TITLE."\"></span>\n";
	print ("<form method=\"post\" action=\"".OFFLINE_FILE."\" target=\"_top\">");

	print <<<EOT
<fieldset>
<input name="style" value="default" type="hidden">


<!-- <p><label for="name">Please provide a reference:</label>
<input id="name" name="name" type="text"></p> -->
<p><label for="quiz">Please select a quiz:</label>
EOT;
	print $quiz_object->htmlSelect('offline');

	print <<<EOT

<input value=" Go " type="submit"></p>
</fieldset>
</form>

</div>

EOT;
}



?>
