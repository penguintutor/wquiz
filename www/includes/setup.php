<?php 
// This is the only option not held externally - you need to know where to load config from
$default_cfg_file = 'default.cfg';

// debug mode - set to true to enable debug messages
if (!isset ($debug)) {$debug = false;}

// Internal links to other pages
define ("INDEX_FILE", "index.php");
define ("QUESTION_FILE", "question.php");
define ("ANSWER_FILE", "answer.php");
define ("SUMMARY_FILE", "summary.php");
define ("END_FILE", "end.php");
define ("FIRST_FILE", "includes/first.php");				// If install not complete
define ("ADMIN_FILE", "adminindex.php");
define ("ADMIN_LOGIN_FILE", "adminlogin.php");
define ("ADMIN_LIST_FILE", "adminlist.php");				// List of questions
define ("ADMIN_Q_FILE", "adminquestions.php"); 				// view / test question
define ("ADMIN_EDIT_FILE", "adminedit.php"); 				// edit question
define ("ADMIN_QUIZZES_FILE", "adminquizzes.php");			// List of quizzes
define ("ADMIN_EDIT_QUIZ_FILE", "adminquizedit.php"); 		// edit quiz
define ("ADMIN_DEL_Q_FILE", "adminquestiondel.php"); 		// del question
define ("ADMIN_DEL_QUIZ_FILE", "adminquizdel.php"); 		// del question
define ("ADMIN_EDIT_SETTINGS_FILE", "adminsettings.php"); 	// edit settings



// get directory for includes
if (defined('__DIR__')) {$app_dir = __DIR__;}
else {$app_dir = dirname(__FILE__);}
$include_dir = $app_dir."/";

// we now drop the include from the app_dir
$app_dir = substr ($app_dir, 0, strrpos ($app_dir, '/') );

if ($debug) {print "Loading css\n";}
include ($include_dir."css.php");			// constants for css tags
if ($debug) {print "Loading Errors\n";}
include ($include_dir."Errors.php");		// Error handling
if ($debug) {print "Loading Database\n";}
include ($include_dir."Database.php");		// Direct access to DB
if ($debug) {print "Loading Quizdb\n";}
include ($include_dir."QuizDB.php");		// Use DB as quiz parameters
if ($debug) {print "Loading Settings\n";}
include ($include_dir."Settings.php");		// Load config file
if ($debug) {print "Loading QuizSession\n";}
include ($include_dir."QuizSession.php");	// Session handling
if ($debug) {print "Loading Question\n";}
include ($include_dir."Question.php");		// Manage a question
//if ($debug) {print "Loading Quizzes\n";}
//include ($include_dir."Quizzes.php");		// Manage the overall quiz - eg. display menu
if ($debug) {print "Loading Quiz\n";}
require_once ($include_dir."Quiz.php");		// Manage an individual quiz
if ($debug) {print "Loading Templates\n";}
include ($include_dir."Templates.php");		// Templates for html header / footer
if ($debug) {print "Setup Includes / requires complete\n";}


// Create Error Handler - as this is a singleton we don't need to do now - just call when required
//$err = Errors::getInstance();

/*** Load config file ***/
// this will normally just load the name of the real config file
// note can't try/catch around an include so use @include and check it's loaded later
@include ($app_dir."/".$default_cfg_file);
// $cfgfile is in the default_cfg_file and points to the 'real' config file
// $cfgfile is loaded after all the entries in $default_cfg_file
// if no local cfg file so see if master cfg file has been customised
if (!isset($cfgfile) || $cfgfile == '')
{
	// check master file has settings - just check one of them
	if (!isset($dbsettings)) 
	{
		$err = Errors::getInstance();
		$err->errorEvent(ERROR_CFG, "Error loading master config file ($default_cfg_file), or file is corrupt / incomplete");
	}
}
else
{
	// information message - only log if in debug mode
	if (isset($debug) && $debug)
	{
		$err = Errors::getInstance();
		$err->errorEvent (INFO_CFG, "Loaded main config - now loading local config $cfgfile");
	}
	@include ($cfgfile);
	// make sure required dbsettings is loaded
	if (!isset($dbsettings)) {$err->errorEvent(ERROR_CFG, "Error loading local config file ($cfgfile), or file is corrupt / incomplete");}
}

if ($debug) {print "config files loaded\n";}


/*** Connect to database - $db can be used to access by other classes ***/
/*** But prefrably use $qdb below ***/
// null array for options - could add options if required
$db_options = array();

if ($debug) {print "connecting to database\n";}
// create database handler
$db = new Database($dbsettings);
if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

/*** qdb should be used by other classes that need to query the database this calls $db as approprate ***/
$qdb = new QuizDB($db);


/* creating settings class that holds details of user defined settings */
$settings = Settings::getInstance();
// provide settings with the qdb object and initialise object
$settings->loadSettings ($qdb);

/* templates class - handles adding template at header / footer positions */
$templates = new Templates();

// quiz session to maintain session
// just start session here
$quiz_session = new QuizSession();


// array of supported question types
// key is used in database - value could be more useful reference
$question_types = array(
	'text'=>'text',
	'TEXT'=>'TEXT',
	'number'=>'number',
	'radio'=>'radio',
	'checkbox'=>'checkbox');


//debug
if ($debug) {print "Setup complete\n\n";}

?>
