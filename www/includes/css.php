<?php
/****************************************
* CSS IDs / Tags etc.
* Used by multiple files (Question.php / QuizMenu.php)
* Not used by admin pages
*****************************************/

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


/** The following are hardcoded into Javascript as well as this file 

CSS_ID_FORM
CSS_ID_OFFLINE_START

**/

// css tags that the customer should use in css file
// Whenever an entry is added - add this to css-entities
define ("CSS_ID_QUESTION", "wquiz-questionintro");
define ("CSS_ID_QUESTION_INPUT", "wquiz-questioninput");
define ("CSS_ID_QUIZ_INTRO", "wquiz-quizintro");						// div
define ("CSS_ID_QUIZ_TITLE", "wquiz-quiztitle");						// h3
define ("CSS_CLASS_IMAGE", "wquiz-questionimage");
define ("CSS_CLASS_QUESTION_P", "wquiz-questiontext");
define ("CSS_CLASS_END_NOTANSWERED", "wquiz-end-notanswered");			// used on end page if any questions not answered
define ("CSS_CLASS_SUMMARY_CORRECT", "wquiz-summary-correct");			// used on summary page
define ("CSS_CLASS_SUMMARY_INCORRECT", "wquiz-summary-incorrect");
define ("CSS_CLASS_SUMMARY_NOTANSWERED", "wquiz-summary-notanswered");
define ("CSS_CLASS_ANSWER_CORRECT", "wquiz-answer-correct");			// used on answer page
define ("CSS_CLASS_ANSWER_INCORRECT", "wquiz-answer-incorrect");
define ("CSS_CLASS_ANSWER_NOTANSWERED", "wquiz-answer-notanswered");
define ("CSS_CLASS_ANSWER_REASON", "wquiz-answer-reason");				// reason for answer (eg. "answer 1 was the correct answer")
define ("CSS_CLASS_OFFLINE_QUESTION_ANSWER", "wquiz-offline-question-answer");		// blank or default - normally we want to underline this or similar - on question page rather than answer page
define ("CSS_CLASS_MESSAGE", "wquiz-questionmessage"); 					// p - used for message to user eg. answer invalid
define ("CSS_CLASS_STATUS", "wquiz-questionstatus");					// position in quiz (eg. 1 of 10)
define ("CSS_CLASS_LINK_START", "wquiz-link-start");					// link back to start (eg. start again)
define ("CSS_ID_NAVIGATION", "wquiz-questionnavigation");				// div
define ("CSS_ID_OFFLINE_NAVIGATION", "wquiz-offlinenavigation");		// div
define ("CSS_ID_MENU", "wquiz-quizmenu");
define ("CSS_CLASS_MENU_TITLE", "wquiz-quizmenutitle");
define ("CSS_ID_INTRO_START", "wquiz-introstart");						// Div used at first page to provide information about the quizzes (after the form)
define ("CSS_ID_OPTION_QUIZ", "wquiz-quizoption");
define ("CSS_ID_FORM", "wquiz-form");
define ("CSS_ID_REVIEW_Q", "wquiz-end-review-div");						// DIV for do you want to "Review answers" ID
define ("CSS_ID_REVIEW", "wquiz-review-yes");							// div - review = yes (just button)
define ("CSS_ID_RESULTS", "wquiz-results");								// div - detailed results
define ("CSS_ID_RESULTS_END", "wquiz-results-end");						// div - end of detailed results
define ("CSS_ID_RESULTS_BUTTON", "wquiz-results-button");				// div - detailed results button
define ("CSS_ID_RETURN_BUTTON", "wquiz-return-button");				// div - start again button / return to results
define ("CSS_ID_ANSWER", "wquiz-answer");								// div - detailed results
define ("CSS_ID_ANSWER_BUTTON", "wquiz-answer-button");					// div - answer part of answer.php
define ("CSS_ID_SUMMARY", "wquiz-summary");								// ul for summary restults
define ("CSS_ID_MARK", "wquiz-review-mark");							// div - review = answer
define ("CSS_ID_BUTTONS", "wquiz-quizbuttons");							// div - for main direction buttons etc.
/* Following will have additional text appended eg. -answer -first -previous */
define ("CSS_ID_NAVSUBMIT", "wquiz-navsubmit");							// prefix for navigation buttons (followed by previous, next etc.
define ("CSS_ID_OFFLINE_START", "wquiz-offlinestart");					// div for buttons to print offline buttons
define ("CSS_ID_EDIT_HINT_ANSWER", "wquiz-edit-hint-answer");			// hint in edit on answer field - admin.js uses this value
// Admin entries
define ("CSS_ID_ADMIN_MENU", "wquiz-admin-menu");						// Top menu division
define ("CSS_ID_ADMIN_MAIN", "wquiz-admin-main");						// main division
define ("CSS_CLASS_ADMIN_TABLE", "wquiz-admin-table");					// tables for question list / quiz list
define ("CSS_CLASS_ADMIN_EDIT_MESSAGE", "wquiz-admin-edit-message");	// edit saved / created message


// Quick start
define ("CSS_ID_QS_MENU", "wquiz-quickstart-quizmenu");
define ("CSS_CLASS_QS_MENU_TITLE", "wquiz-quickstart-quizmenutitle");



// classless function - test to see if we have paragraph or similar html code
// if there is <p> at the front of the document then return true
// if return false then means we may nedd to add 
function isParagraph ($in_text)
{
	if (preg_match ("/^\s<p/", $in_text)) {return true;}
	return false;
}



?>
