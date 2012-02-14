<?php
# Load this first for all admin functions
define ("ADMIN_DIR", "admin");	 	

// get directory
if (defined('__DIR__')) {$app_dir = __DIR__;}
else {$app_dir = dirname(__FILE__);}
// strip the admin part of the directory
$app_dir = preg_replace ("#/".ADMIN_DIR."/?$#", "", $app_dir);
require_once($app_dir."/includes/setup.php");

// Note that these are in the ADMIN_DIR
// Normally we only call pages on urls within the same heirarchy
define ("ADMIN_FILE", "index.php");
define ("ADMIN_LOGIN_FILE", "login.php");
define ("ADMIN_LIST_FILE", "list.php");						// List of questions
define ("ADMIN_Q_FILE", "questions.php"); 					// view / test question
define ("ADMIN_EDIT_FILE", "edit.php");		 				// edit question
define ("ADMIN_QUIZZES_FILE", "quizzes.php");				// List of quizzes
define ("ADMIN_EDIT_QUIZ_FILE", "quizedit.php"); 			// edit quiz
define ("ADMIN_DEL_Q_FILE", "questiondel.php"); 			// del question
define ("ADMIN_DEL_QUIZ_FILE", "quizdel.php"); 				// del quiz
define ("ADMIN_EDIT_SETTINGS_FILE", "settings.php"); 		// edit settings
define ("ADMIN_LOGOUT_FILE", "logout.php"); 				// logout
define ("ADMIN_USER_FILE", "user.php"); 					// user administration (eg. password change)
define ("ADMIN_INSTALL_FILE", "install.php"); 				// Install / setup script

?>
