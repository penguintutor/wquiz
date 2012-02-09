<?php



/** Create new DB (if required) 
For version 0.4.0
****/


// These must be the same as setup.php
// This script does not use setup.php, but all others do
define ("ADMIN_DIR", "admin");	 							// Admin directory
//define ("ADMIN_INSTALL_FILE", ADMIN_DIR."/install.php"); 	// Install / setup script

// relative path to the apps directory (used on url references)
$rel_path = "../";

//$default_cfg_file = 'default.cfg';
$default_cfg_file = 'test.cfg';

// url link to use in forms - we have hardcoded install.php which must be the name of this file 
//$post_filename = $rel_path.ADMIN_INSTALL_FILE;
$post_filename = "install.php";

// action_required holds the next step in the config process
// 'cfgfile', 'database', 'tables', 'settings', 'secure'
$action_required = 'cfgfile';

// Use status_msg to track status of install process
$status_msg = "";

$quiz_tables = array 
( 
   	'quizzes' => 'quiz_quiz',
   	'questions' => 'quiz_questions',
   	'rel' => 'quiz_questionrel',
   	'settings' => 'quiz_settings'
);


// get directory
if (defined('__DIR__')) {$app_dir = __DIR__;}
else {$app_dir = dirname(__FILE__);}

// strip the admin part of the directory
$app_dir = preg_replace ("#/".ADMIN_DIR."/?$#", "", $app_dir);

$first_config_file = $app_dir."/".$default_cfg_file; 

/* Does .cfg file(s) already exist */
if (file_exists($first_config_file))
{
	// try loading it
	@include ($first_config_file);
	// do we now have a secondary cfg file - if so load that as well
	if (isset($cfgfile) && $cfgfile!='')
	{
		@include ($cfgfile);
	}
	
	// If we don't have the database details here then either corrupt or not pointing to correct secondary file
	if (!isset($dbsettings))
	{
		$second_config_file = (isset($cfgfile)) ? $cfgfile : "";
		displayConfigError($first_config_file, $second_config_file);	
		exit (0);
	}
	// If we have database settings we can proceed with creating database etc
	else {$action_required = 'database';}
}
// No .cfg file
// Do we have details to create .cfg file - if so create
else
{
	// check all parameters (quizname is for filename - rather than a parameter)
	$database_settings_required = array('quizname', 'dbtype', 'username', 'password', 'hostname', 'database', 'tableprefix');
	// optional tickbox (create database is not checked here)
	// not save - ask for details
	if (!isset($_POST['action']) || $_POST['action']!='save')
	{
		displayInitialForm ("");
		exit (0);
	}
	
	// this must be a save of file	
	// check we have all parameters as we give a "must not be blank" first
	foreach ($database_settings_required as $this_setting)
	{
		if (!isset($_POST[$this_setting]) || $_POST[$this_setting] == '')
		{
			// this is optional
			if ($this_setting == 'tableprefix') {continue;} 
			displayInitialForm ("$this_setting is a required field");
			exit(0);
		}
		
	}
	// If no errors found - perform detailed validation checks for each field
	/* validate certain fields */
	// some of these are more restrictive than mysql - if need to use other charactors not included then can still configure manually in .cfg file.
	
	// quizname is slightly different in that we just remove any special characters to get the filename
	$quizname = $_POST['quizname'];
	preg_replace ("/[^\w]/g", '', $quizname);
	if ($quizname == '')	// make sure it isn't blank
	{
			displayInitialForm ("Short name invalid");
			exit(0);
	}
	
	// check for dbtype
	// if we have clicked no on continue then we reissue the form
	if (isset($_POST['confirmdbtype']) && $_POST['confirmdbtype'] != 'yes')
	{
			displayInitialForm ("Please select a different database type (mysql recommended)");
			exit(0);	
	}
	
	// perform confirm check if not mysql
	if (!preg_match ("/^[\w-_]+$/", $_POST['dbtype']))
	{
			displayInitialForm ("dbtype contains illegal charactors");
			exit(0);
	}
	// known but unsupported 
	elseif ($_POST['dbtype'] == 'mssql' && (!isset($_POST['confirmdbtype']) || $_POST['confirmdbtype'] != 'yes') )
	{
		displayConfirm ("MS Sql is not officially supported - do you wish to continue?", 'dbtype', $_POST);
		exit (0);
	}
	// unknown 
	elseif ($_POST['dbtype'] != 'mysql' && (!isset($_POST['confirmdbtype']) || $_POST['confirmdbtype'] != 'yes') )
	{
		displayConfirm ("DB type is not supported this will need manual configuration - do you wish to continue?", 'dbtype', $_POST);
		exit (0);
	}
	
	// basic check for password field - just blocks some ncharacters not allowed by mysql
	// will still allow some characters not allowed by mysql (eg. accentuated characters, but they are not considered a security risk)
	// unsure whether " and ' are allowed in mysql, but we don't allow them anyway in case they cause problems
	if (preg_match ("/[:&+\"\']/", $_POST["password"]))
	{
			displayInitialForm ("password contains illegal charactors");
			exit(0);
	}
	// just basic check for username - could check for max 16 chars etc. but leave that for mysql to enforce - as long as we don't allow dangerous characters
	if (preg_match ("/[:&+\"\'\s]/", $_POST["username"]))
	{
			displayInitialForm ("username contains illegal charactors");
			exit(0);
	}	
	// Does not check for valid hostname, just checks for valid characters
	if (!preg_match ("/^[\w\.-_:]+$/", $_POST['hostname']))
	{
			displayInitialForm ("hostname contains illegal charactors");
			exit(0);
	}
	// more stringent db table names etc. 
	// This should work in all normal implementations 
	// if hosting provider requires something other than allowed then will need to be configured
	// manually - if so it can be ammended
	if (!preg_match ("/^[\w-_]+$/", $_POST['database']))
	{
			displayInitialForm ("database contains illegal charactors");
			exit(0);
	}
	if (isset($_POST['tableprefix']) && $_POST['tableprefix'] != '' && !preg_match ("/^[\w-_]+$/", $_POST['tableprefix']))
	{
			displayInitialForm ("table prefix contains illegal charactors");
			exit(0);
	}		
	
			
	/* create the config files */
	// Create the secondary config file (if this fails then our initial test fails)
	$second_config_file = $app_dir."/".$quizname.".cfg";
	// First check if it exists
	if (file_exists($second_config_file)) {$status_msg .= "\n\n$second_config_file already exists <br />\n\n";}
	else 
	{
		//print "Creating $second_config_file<br />\n";
		
		$second_config_text = "<?php\n//wQuiz configuration file\n//Database details\n\$dbsettings = array(\n";
		$second_config_text .= "'dbtype' => '".$_POST['dbtype']."',\n";
		$second_config_text .= "'username' => '".$_POST['username']."',\n";
		$second_config_text .= "'password' => '".$_POST['password']."',\n";
		$second_config_text .= "'hostname' => '".$_POST['hostname']."',\n";
		$second_config_text .= "'database' => '".$_POST['database']."',\n";
		$second_config_text .= "'tableprefix' => '".$_POST['tableprefix']."'\n";
		$second_config_text .= ");\n?>\n";
		
		$fh = fopen($second_config_file, 'w');
		if ($fh)
		{
			fwrite ($fh, $second_config_text);
			fclose ($fh);
		}
		else
		{
			displayManualConfig("Unable to write to $second_config_file", $second_config_text);
			exit (0);
		}
		// finally check that it was created (don't do a checksum or anything special)
		if (!file_exists($second_config_file)) 
		{
			displayManualConfig("Error creating $second_config_file", $second_config_text);
			exit (0);
		}
		else
		{
			$status_msg .= "\n\nNew configuration created $second_config_file<br />\n\n";
		}

	}
	// Already checked that the file doesn't exist so now create
	//print "Creating $first_config_file";
	$first_config_text = "<?php\n//wQuiz configuration file\n//Do not edit this directly\n//Link to custom config file\n\$cfgfile = '$second_config_file';\n?>\n";
	
	$fh = fopen($first_config_file, 'w');
	if ($fh)
	{
		fwrite ($fh, $first_config_text);
		fclose ($fh);
	}
	else
	{
		displayManualConfig("Unable to write to $first_config_file", $first_config_text);
		exit (0);
	}
	// finally check that it was created (don't do a checksum or anything special)
	if (!file_exists($first_config_file)) 
	{
		displayManualConfig("Error creating $first_config_file", $first_config_text);
		exit (0);
	}
	else
	{
		$status_msg .= "\n\nNew configuration created $first_config_file<br />\n\n";
	}

	
}

$action_required = 'database';
print $status_msg;
$status_msg = "";

/* If not already loaded load the relevant config files */
if (!isset($dbsettings))
{
	@include ($first_config_file);
	// do we now have a secondary cfg file - if so load that as well
	if (isset($cfgfile) && $cfgfile!='')
	{
		@include ($cfgfile);
	}
}

// check that settings loaded OK
// If didn't load then something has gone wrong 
// corrupt file / write failed part way through
if (!isset($dbsettings))
{
	$second_config_file = (isset($cfgfile)) ? $cfgfile : "";
	displayConfigError($first_config_file, $second_config_file);
	exit (0);
}



/* Config files exist so now try creating database skeleton */


/* Setup database connection */
// Uses Database class directly (not QuizDB as is used by main code)
require_once ($app_dir."/includes/Database.php");
$db = new Database($dbsettings);
// Connected
if ($db->getStatus() == 1)
{
	// status = 1 - means that we have connected to database and the database exists (use db)
	
	// we are not asking user to confirm that they are installing into these database
	// in many cases the database will need to be created outside of the install script so don't 
	// want to impose too many "are you sure?" questions
	// we will still not overwrite as we check tables don't exist before creating
	
		$action_required = 'tables';
}
// Connected to db server, but not to specific database (eg. database does not exist)
elseif ($db->getStatus() == -2)
{
	// We can only create db if we are on mysql (in this version)
	if ($dbsettings['dbtype'] == 'mysql') 
	{
		// Try creating database
		if (!$db->createDb($dbsettings['database'])) 
		{
			// unable to create database - most likely permissions - hosted accounts may need to create
			// the database using the hosting cpanel etc.
			
			displayDbError ("Unable to create new database ".$dbsettings['database']." <br />\nThis is normally due to insufficient permissions. If using a hosting account on a shared server you may need to use cpanel or ask your hosting provider for how to create a database<br />\nPlease read the install documentation for more details and then create the database manually before reloading this page.\n");
			exit (0);
		}
		else 
		{
			// now connect to the new database 
			if (!$db->connectDb($dbsettings['database']))
			{
				// shouldn't get this as if we have permission to create the database we should be able to connect to it. Perhaps we have lost our network connection 
				$error_msg = $db->getError();
				displayDbError ("Unable to connect to the new database ".$dbsettings['database']." <br />\nError $error_msg.\n");
				exit (0);
			}
			else
			{
				$action_required = 'tables';
			}
		}
	}
	else
	{
		$error_msg = $db->getError();
		displayDbError ("Unable to connect to the database ".$error_msg."\n<br />If not using mysql then you will need to create the database manually. Please read the install documenation for more details\n");
	}
	
}
// Otherwise if connection fails completely
else
{
	$error_msg = $db->getError();
	displayDbError ("Unable to connect to the database ".$error_msg."\n<br />\n");
	exit (0);
}


/* connected to DB */
// If there is another error we didn't catch
if ($action_required != 'tables') {displayInternalError("Invalid status returned after DB connect"); exit(0);}


// Make sure tables don't already exist
// if they do we stop - as we don't allow tables to be overridden (security risk)
$existing_tables = $db->getTables();
if (!empty($existing_tables))
{
	foreach ($quiz_tables as $this_table)
	{
		// add the prefix
		$test_table = $dbsettings['tableprefix'].$this_table;
		if (in_array ($test_table, $existing_tables))
		{
			displayDbError ("Table $test_table already exists. Unable to continue with install<br />\nYou will need to delete the tables manually to re-run the install or install manually based on the install documentation.");
			exit (0);
		}
	}
}


/* Create the tables */

$create_table_sql = array(
	'quizzes' => "CREATE TABLE IF NOT EXISTS ".$dbsettings['tableprefix'].$quiz_tables['quizzes']." (quizname varchar(255) NOT NULL, title varchar(255) NOT NULL, numquestions int(11) NOT NULL default '0', numquestionsoffline int(11) NOT NULL default '0', quizintro text NOT NULL, priority int(11) NOT NULL default 1, enableonline tinyint(1) NOT NULL default '0', enableoffline tinyint(1) NOT NULL default '0', PRIMARY KEY  (quizname))",
	
	'questions' => "CREATE TABLE IF NOT EXISTS ".$dbsettings['tableprefix'].$quiz_tables['questions']." (questionid int(11) NOT NULL auto_increment, section varchar(254) NOT NULL default '', intro text NOT NULL, input text NOT NULL, type varchar(10) NOT NULL default '', answer varchar(100) NOT NULL default '', reason text NOT NULL, reference varchar(100) NOT NULL default '', hint varchar(254) NOT NULL default '', image varchar(200) NOT NULL default '', audio varchar(200) NOT NULL default '', comments varchar(200) NOT NULL default '', qfrom varchar(50) NOT NULL default '', email varchar(50) NOT NULL default '', created date NOT NULL default '0000-00-00', reviewed date NOT NULL default '0000-00-00', PRIMARY KEY  (questionid))",
	
	'rel' => "CREATE TABLE IF NOT EXISTS ".$dbsettings['tableprefix'].$quiz_tables['rel']." (relid int(11) NOT NULL auto_increment,quizname varchar(255) NOT NULL,questionid int(11) NOT NULL,PRIMARY KEY (relid))",
	
	'settings' => "CREATE TABLE IF NOT EXISTS ".$dbsettings['tableprefix'].$quiz_tables['settings']." (settings_key varchar(50) NOT NULL, settings_value varchar(255) NOT NULL, PRIMARY KEY  (settings_key))"
);

foreach ($create_table_sql as $this_table=>$this_sql)
{
	if ($db->query($this_sql) != 0) 
	{
		$error_msg = $db->getError();
		displayDbError ("Unable to create table $this_table<br />\nPlease check permissions or create the tables manually<br />\nError msg: $error_msg</p><p>$this_sql");
		exit (0);
	}
}

/** Reach this point we have successfully installed the basic layout **/
//- In future add additional security to move second config file somewhere safer
// This needs to be done manually following instructions in the user manual


	print <<< EOT
<html>
<head>
<title>Install complete</title>
</head>
<body>
<h1>Install complete</h1>
<p>
Now edit the settings and add your questions. 
</p>
</body>
</html>
EOT;
exit(0);





// Displays error message and exits
// use this if the others are not more appropriate
function displayInternelError ($message)
{
	print <<< EOT
<html>
<head>
<title>Install error</title>
</head>
<body>
<h1>Install error - Internal error</h1>
<p>
An internal error has occurred.
</p>
<p>
$message
</p>
</body>
</html>
EOT;
		exit (0);	
}



// Displays error message and exits
function displayDbError ($message)
{
	print <<< EOT
<html>
<head>
<title>Install error</title>
</head>
<body>
<h1>Install error - Database</h1>
<p>
An error has when trying to update the database. Please check your database settings.
</p>
<p>
$message
</p>
</body>
</html>
EOT;
		exit (0);	
}
		



// Displays error message and exits
function displayConfigError ($first_config_file, $second_config_file)
{
	print <<< EOT
<html>
<head>
<title>Install error</title>
</head>
<body>
<h1>Install error - Error in config file</h1>
<p>There is an error in the configuration file.<br />
Default configuration file: $first_config_file<br />
EOT;
		if (isset($second_config_file) && $second_config_file!='')
		{
			print "Secondary configuration file: $second_config_file\n";
		}
		print <<< EOT2
</p>
<p>Please check that the configuration files exist and are not corrupt.</p>
<p>To restart install then the above configuration files can be deleted.</p>
</body>
</html>
EOT2;
		exit (0);	
}
		
		

// Displays the initial form regarding database information
//$message is displayed to user (eg. Fill in field ___)
function displayInitialForm ($message)
{
	global $post_filename;
	print <<< EOT
<html>
<head>
<title>Install wquiz</title>
</head>
<body>
<h1>Install wquiz</h1>
<p>Please provide the following details to install and configure wQuiz.</p>
<p><strong>$message</strong></p>
<p>
<form method="post" action=$post_filename>
<input type="hidden" name="action" value="save" />
</p>
<h2>Database information</h2>	
<p>
Provide the information required to administer the database. This must have admin access to allow the install to create the appropriate database tables (if not already defined). The username can be changed to one with lower privilages later.
</p>
<p>
Short title (spaces / special characters ignored) <input type="text" name="quizname" /><br />
Database type (recommend mysql) <input type="text" name="dbtype" value="mysql" /><br />
Database hostname (or ipaddress) <input type="text" name="hostname" value="" /><br />
Database username (admin access required) <input type="text" name="username" value="" /><br />
Database password <input type="password" name="password" value="" /><br />
Database name <input type="text" name="database" value="" /><br />
Database Table prefix (if required) <input type="text" name="tableprefix" value="" />
</p>

<input type="submit" />
</form>
</p>
</body>
</html>
EOT;
	exit (0);	
	
}

// Used to display yes / no before proceeding
// field is used to add an additional hidden field that we have validated this
// eg. dbtype becomes confirmdbtype="yes|no"
// uses 2 forms (one yes, one for no)
// $parameters should normally be set to $_POST so that the previous form is resubmitted 
function displayConfirm ($message, $field, $parameters)
{
	global $post_filename;
	print <<< EOT
<html>
<head>
<title>Install wquiz</title>
</head>
<body>
<h1>Are you sure?</h1>
<p><strong>$message</strong></p>
<p>
<form method="post" action=$post_filename>

EOT;

print "<input type=\"hidden\" name=\"confirm$field\" value=\"yes\" />\n";

foreach ($parameters as $key=>$value)
{
	print "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";	
}

print <<< EOT2
<input type="submit" value="yes" />
<form method="post" action=$post_filename>
EOT2;

print "<input type=\"hidden\" name=\"confirm$field\" value=\"no\" />\n";

foreach ($parameters as $key=>$value)
{
	print "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";	
}

print <<< EOT3
<input type="submit" value="no" />
</form>
</p>
</body>
</html>
EOT3;
	exit (0);	
	
	
}

function displayManualConfig ($message, $config_text)
{
	// convert html special characters (eg. < becomes &lt;
	$config_text = htmlspecialchars($config_text);
	// replace \n with html break
	$config_text = preg_replace ('/\\n/', '<br />', $config_text);  
	// we don't use a post - by using GET this will start the test process from start
	global $post_filename;
	print <<< EOT
<html>
<head>
<title>Install wquiz</title>
</head>
<body>
<h1>File creattion failed</h1>
<p>Unable to create the configuration file</p>
<p><strong>$message</strong></p>
<p>Copy and paste below into new file</p>
<hr />
<p><code>$config_text</code></p>
<hr />
<p>
<ul>
<li><a href="$post_filename">Continue / Retry</a></li>
</ul>
</p>

</body>
</html>
EOT;
	exit (0);	
	

	
	
}




?>
