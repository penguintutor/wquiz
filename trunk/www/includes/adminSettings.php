<?
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

// List of settings and types
// [0] = settings_key 
// [1] = type 
// [2] = Title (optional)
// [3] = hint (optional)

//Type can be boolean, alphanum, text, textblock, directory, directory_ (no trailing /), int, custom
//Type custom is ignored by normal template edit and so needs to be handled seperately (not currently supported) 
$setting_types = array 
(
	array
	(
		'html_title',
		'text',
		'Title',
		'Title for html header'
	),
	array
	(
		'html_description',
		'text',
		'Description',
		'Description for html header'
	),
	array
	(
		'theme_admin',
		'text',
		'Admin theme',
		'Theme to use for admin functions'
	),
	
	array
	(
		'theme_quiz',
		'text',
		'Quiz theme',
		'Theme to use for quiz functions'
	),

	array 
	(
		'buttons_navigation_enabled',
		'custom'
	),
	array 
	(
		'buttons_navigation_labels',
		'custom'
	),
	array 
	(
		'buttons_show_answer_button',
		'boolean',
		'Show answer button',
		'Should we show answer button for the question?'
	),
	array
	(
		'review_text',
		'textblock',
		'Review text',
		'HTML text'
	),
	array 
	(
		'review_show_unanswered',
		'boolean',
		'Review show unanswered',
		'Should we show if an answer has been skipped?'
	),
	array
	(
		'review_enable',
		'boolean',
		'Review enable',
		'can the users review their answers?'
	),
	array
	(
		'answer_view_enable',
		'boolean',
		'Answer view enable',
		'can the users see the answers?'
	),
	array
	(
		'answer_summary_enable',
		'boolean',
		'Answer summary enable'
	),
	array 
	(
		'template_allow_include',
		'boolean',
		'Allow PHP includes within the theme template files?'
	),
	// admin username & password not edited through settings
	array 
	(
		'admin_login_username',
		'custom'
	),
	array 
	(
		'admin_login_password',
		'custom'
	),
	array
	(
		'admin_login_expirytime',
		'int',
		'Login expiry time',
		'seconds'
	),
	array
	(
		'quiz_max_questions',
		'int',
		'Maximum number of questions in a quiz',
		'number'
	),
	array
	(
		'summary_length',
		'int',
		'Summary length',
		'Num chars to display in summary view of question (admin view)'
	)
	
);
?>
