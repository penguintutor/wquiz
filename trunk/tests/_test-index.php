<?php
// test script - based on subversion revision 16


// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

//$debug = true;


require_once("../includes/setup.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

// get all the quizzes and add to object
$all_quizzes = new Quizzes();
$quiz_array = $qdb->getQuizzesAll();
// add this one to allQuizzes
foreach ($quiz_array as $this_quiz_array)
{
	$all_quizzes->addQuiz(new Quiz($this_quiz_array));
}

// No quizzes found - most likely not setup
if ($all_quizzes->count() < 1) {header("Location: ".FIRST_FILE);}



// header template
$templates->includeTemplate('header', 'normal');


// is this result of POST - if so setup page, otherwise display menu
if (array_key_exists('quizname', $_POST))
{
	// Very important
	// --here 
	// validate field input
	$quiz = $_POST['quizname'];
	//first check that this is just a string - no 
	if (!ctype_alnum($quiz)) 
	{
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_SECURITY, "Error security violoation - quizname is invalid"); 
	}
	// default is online - changed by post value
	$quiz_type = 'online';
	if (array_key_exists('offline', $_POST) && ($_POST == 'yes')) {$quiz_type = 'offline';}
	

	//check that this is a valid quizname
	// handle this as a warning using the errorEvent - we then provide a more user friendly error
	// this is not a security event, but is still wrong
	if (!$all_quizzes->validateQuizname($quiz))
	{
		// here we handle error in more user friendly way than if we suspect attempt to hack
		$err =  Errors::getInstance();
		$err->errorEvent(WARNING_PARAMETER, "Warning parameter incorrect - quizname is invalid");
		//--here we don't give an error just show menu
		printMenu($all_quizzes);
	}
	else
	{
		// Get quizobject for this particular quiz
		$this_quiz = $all_quizzes->getQuiz($quiz);
		// check this quiz is enabled in this mode
		if ($this_quiz->isEnabled($quiz_type) == false)
		{
			// quiz is disabled
			print "<h3>Selected quiz is disabled for $quiz_type use</h3>\n";
			$err =  Errors::getInstance();
			$err->errorEvent(INFO_QUIZSTATUS, "quiz $quiz is disabled for $quiz_type use");
			printMenu($all_quizzes);
		}
		else
		{
			// quiz is enabled if we have got this far
			// Get all the questions as question array (sql format - not objects)
			$question_array = $qdb->getQuestionQuiz($quiz);
			// random order array to randomise questions - we can then just take the first x number of questions
			shuffle ($question_array);
			// check we have sufficient questions
			if (count($question_array) <= $this_quiz->getNumQuestions($quiz_type)) 
			{
				print "<h3>Insufficient questions in selected quiz</h3>\n";
				$err =  Errors::getInstance();
				$err->errorEvent(WARNING_QUIZQUESTIONS, "insufficient questions in $quiz, requires: "+$this_quiz->getNumQuestions($quiz_type)+" - has "+count($question_array));
				printMenu($all_quizzes);
			
			}
			else
			{
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
				// store questions into session
				$quiz_session->setQuestions ($random_questions);
				$quiz_session->setAnswers ($answers);
				$quiz_session->setStatus (SESSION_STATUS_ACTIVE);
				
				// Form starts at the top as future pages use options within form
				print "<form id=\"wquiz-form\" method=\"post\" action=\"question.php\">\n";
				

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
				print "<input type=\"submit\" value=\"start\" name=\"start\" />\n";
				print "</div><!-- ".CSS_ID_BUTTONS." -->\n";
				
				
				print "</form>\n";
			}
		}
		
	}
	
}
else 
{

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
	print "<span class=\"".CSS_ID_MENU_TITLE."\">Start Quiz</span>\n";
	print ("<form method=\"post\" action=\"".INDEX_FILE."\" target=\"_top\">");

	print <<<EOT
<fieldset>
<input name="style" value="default" type="hidden">


<p><label for="name">Please enter your name:</label>
<input id="name" name="name" type="text"></p>
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
