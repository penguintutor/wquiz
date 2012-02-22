<?
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
		'theme_directory', 
		'directory', 
		'Template directory', 
		'Path the the themes from the document root\neg. /quiz/themes'
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
