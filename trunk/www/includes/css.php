<?php
/****************************************
* CSS IDs / Tags etc.
* Used by multiple files (Question.php / QuizMenu.php)
*****************************************/

// css tags that the customer should use in css file
// Whenever an entry is added - add this to css-entities
define ("CSS_ID_QUESTION", "wquiz-questionintro");
define ("CSS_ID_QUESTION_INPUT", "wquiz-questioninput");
define ("CSS_ID_QUIZ_INTRO", "wquiz-quizintro");			// div
define ("CSS_ID_QUIZ_TITLE", "wquiz-quiztitle");			// h3
define ("CSS_CLASS_IMAGE", "wquiz-questionimage");
define ("CSS_CLASS_QUESTION_P", "wquiz-questiontext");
define ("CSS_CLASS_MESSAGE", "wquiz-questionmessage"); 		// p - used for message to user eg. answer invalid
define ("CSS_CLASS_STATUS", "wquiz-questionstatus");		// position in quiz (eg. 1 of 10)
define ("CSS_ID_NAVIGATION", "wquiz-questionnavigation");	// div
define ("CSS_ID_MENU", "wquiz-quizmenu");
define ("CSS_ID_MENU_TITLE", "wquiz-quizmenutitle");
define ("CSS_ID_OPTION_QUIZ", "wquiz-quizoption");
define ("CSS_ID_FORM", "wquiz-form");
define ("CSS_ID_REVIEW", "wquiz-review-yes");						// div - review = yes
define ("CSS_ID_MARK", "wquiz-review-mark");						// div - review = answer
define ("CSS_ID_BUTTONS", "wquiz-quizbuttons");				// div - for main direction buttons etc.
define ("CSS_ID_NAVSUBMIT", "wquiz-navsubmit-");			// prefix for navigation buttons (followed by previous, next etc.	




// classless function - test to see if we have paragraph or similar html code
// if there is <p> at the front of the document then return true
// if return false then means we may nedd to add 
function isParagraph ($in_text)
{
	if (preg_match ("/^\s<p/", $in_text)) {return true;}
	return false;
}



?>
