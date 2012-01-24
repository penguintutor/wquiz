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
/*// If we have a message to give back to the user store in here (eg. "database cannot be left blank")
$message = '';
// if we find an error then increment this (eg. missing paramter)
// only use this for soft errors we want to report later rather than critical errors we just stop on
$error_found = 0;*/



// get directory
if (defined('__DIR__')) {$app_dir = __DIR__;}
else {$app_dir = dirname(__FILE__);}

// strip the admin part of the directory
$app_dir = preg_replace ("#/".ADMIN_DIR."/?$#", "", $app_dir);

/* Does .cfg file(s) already exist */
if (file_exists($app_dir.'/'.$default_cfg_file))
{
	// try loading it
	@include ($app_dir."/".$default_cfg_file);
	// do we now have a secondary cfg file - if so load that as well
	if (isset($cfgfile) && $cfgfile!='')
	{
		@include ($cfgfile);
	}
	
	// If we don't have the database details here then either corrupt or not pointing to correct secondary file
	if (!isset($dbsettings))
	{
		print <<< EOT
<html>
<head>
<title>Install error</title>
</head>
<body>
<h1>Install error - Error in config file</h1>
<p>There is an error in the configuration file.<br />
Default configuration file: $default_cfg_file<br />
EOT;
		if (isset($cfgfile) && $cfgfile!='')
		{
			print "Secondary configuration file: $cfgfile\n";
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
			displayInitialForm ("$this_setting is a required field");
			exit(0);
		}
		/*// check for allowed characters
		// quite basic - if you need to include other characters then create default.cfg manully
		// eg. mysql password field allows for more non alphanumeric characters
		elseif (!preg_match ("^[\w\._-]+$", $_POST[$this_setting]))
		{
			displayInitialForm ("Invalid character in $this_setting");
		}*/
		
	}
	// If no errors found - perform detailed validation checks for each field
	/* validate certain fields */
	// some of these are more restrictive than mysql - if need to use other charactors not included then can still configure manually in .cfg file.
	
	// quizname is slightly different in that we just remove any special characters to get the filename
	$quizname = preg_replace ("/[^\w]/g", '', $_POST['quizname']);
	if ($quizname == '')	// make sure it isn't blank
	{
			displayInitialForm ("Short name invalid");
			exit(0);
	}
	// check for dbtype
	// perform confirm check if not mysql
	if (!preg_match ("/^[\w-_]+$/", $_POST['dbtype']))
	{
			displayInitialForm ("dbtype contains illegal charactors");
			exit(0);
	}
	// known but unsupported
	elseif ($_POST['dbtype'] == 'mssql')
	{
		displayConfirm ("MS Sql is not officially supported - do you wish to continue?", 'dbtype', $_POST);
		exit (0);
	}
	// unknown 
	elseif ($_POST['dbtype'] != 'mysql')
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
	if (preg_match ("/^[\w\.-_:]+$/", $_POST['hostname']))
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
	if (!preg_match ("/^[\w-_]+$/", $_POST['tableprefix']))
	{
			displayInitialForm ("table prefix contains illegal charactors");
			exit(0);
	}		
	
			
	/* create the config files */
	// Create the secondary config file (if this fails then our initial test fails)
	$second_config_file = $app_dir."/".$quizname.".cfg";
	// First check if it exists
	print "Creating file";
	if (file_exists($second_config_file)) {print "<br />\n\n$second_config_file exists<br />\n\n";}
	else {print "<br />\n\n$second_config_file does not exist<br />\n\n";}
	//- add this
	
		
	
	
}


/* Now see what form parameters have been sent */





/* If we have database name check if database already exists */
	



/* **** - this is not install code for this program ****
*******  This is work in progress
$action = "normal";


foreach ($dbtables as $thistable)
{
	$dbtables_p[] = $tableprefix.$thistable;
}


// handle url arguments - do we have an action 
if (isset ($_GET['action']))
{
	// need to validate - note we will check later that this is valid
    	$tempArray = checkAlphaNum ($_GET['action'], 'Action');
    	if ($tempArray[0] == 1) {$action = $tempArray[1];}
    	else {$errors->errorMsg($tempArray[1]);}
}


// Connect to the Database - we need to do this regardless of the action
$db = mysql_connect ($dbhost, $dbuser, $dbpass);
if (!$db) {$errors->errorMsg("Error connecting to the database");}

if ($action == 'normal')
{
	// check that the database doesn't exist already
	if (mysql_select_db($dbname, $db) ) 
	{
		print <<< EOQ
<html>
<head><title>Database setup - check</title></head>
<body>
<h1>Setup Check</h1>
<p>Database $dbname already exists.</p>
<p>
EOQ;
		// Also check to see if the tables already exist - in which case we cancel
		// otherwise we ask if want to install in existing database
		$result = mysql_query("show tables");
		$tablefound = false;
		while ($row = mysql_fetch_row($result))
		{
			if (in_array($row[0], $dbtables_p))
			{
				$tablefound = true;
				print "Table ".$row[0]." already exists.</p>\n";
			}
		}

		// if we found a table we just end the html here
		if ($tablefound)
		{
			print "</p>\n";
			print "<p><strong>Go to <a href=\"../index.php\">admin login</a> to finish setup</strong></p>\n";
			print "<p>or remove tables before running this again.</p>\n";
			print "</body></html>\n";
			exit (0);
		}
		else
		{
			print "</p>\n";
			print "<p><a href=\"createdb.php?action=addtables\">Click here to continue adding tables</a></p>\n";
			print "<p>Or delete the table manually to start again.</p>\n";
			print "</body></html>\n";
			exit (0);
		}
		

	}
	// Get here then the database does not already exist so we can create database
	if (!mysql_query("CREATE DATABASE $dbname", $db)) {$errors->errorMsg("Unable to create database $dbname<br />\nDo you have sufficient permissions<br />\nTry creating the table manually and running again.<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}
	mysql_select_db($dbname, $db);
	
}
elseif ($action == 'addtables')
{
	// check tables don't already exist (shouldn't get this as we prevent adding tables on first pass - but in case someone tries to bypass or hits reload
	mysql_select_db($dbname, $db);
	//$result = mysql_query("SHOW TABLES IN $dbname");
	$result = mysql_query("show tables");
	while ($row = mysql_fetch_row($result))
		{
			if (in_array($row[0], $dbtables_p))
			{
				$tablefound = true;
				print "Table ".$row[0]." already exists.</p>\n";
			}
		}

		// if we found a table we just end the html here
		if ($tablefound)
		{
			print "</p>\n";
			print "</p>\n";
			print "<p><strong>Go to <a href=\"../index.php\">admin login</a> to finish setup</strong></p>\n";
			print "<p>or remove tables before running this again.</p>\n";
			print "</body></html>\n";
			exit (0);
		}	
}
else {$errors->errorMsg("Invalid option");}

// get here then ready to create tables and add content
// Add tables 
// Gallery Category
$sql = "CREATE TABLE IF NOT EXISTS `".$tableprefix."category` (";
$sql .= "`category_id` int(11) NOT NULL AUTO_INCREMENT, ";
$sql .= "`category_slug` varchar(30) NOT NULL, ";
$sql .= "`category_title` varchar(255) NOT NULL, ";
$sql .= "`category_text1` longtext NOT NULL, ";
$sql .= "`category_text2` longtext NOT NULL, ";
$sql .= "`category_keywords` varchar(255) NOT NULL, ";
$sql .= "PRIMARY KEY (`category_id`), ";
$sql .= "UNIQUE KEY `slug` (`category_slug`) ";
$sql .= ")";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error creating table ".$tableprefix."category<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}

// Gallery Album
$sql = "CREATE TABLE IF NOT EXISTS `".$tableprefix."album` (";
$sql .= "`album_id` int(11) NOT NULL AUTO_INCREMENT, "; 
$sql .= "`album_slug` varchar(30) NOT NULL, ";
$sql .= "`album_title` varchar(255) NOT NULL, ";
$sql .= "`album_directory` varchar(255) NOT NULL, ";
$sql .= "`album_text1` longtext NOT NULL, ";
$sql .= "`album_text2` longtext NOT NULL, ";
$sql .= "`album_keywords` varchar(255) NOT NULL, ";
$sql .= "PRIMARY KEY (`album_id`), ";
$sql .= "UNIQUE KEY `album_slug` (`album_slug`) ";
$sql .= ")";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error creating table ".$tableprefix."album<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}

// Gallery Index
$sql = "CREATE TABLE IF NOT EXISTS `".$tableprefix."index` (";
$sql .= "`gallery_index_id` int(11) NOT NULL AUTO_INCREMENT, ";
$sql .= "`category_id` int(11) NOT NULL, ";
$sql .= "`album_id` int(11) NOT NULL, ";
$sql .= "`priority` int(11) NOT NULL DEFAULT '0', ";
$sql .= "PRIMARY KEY (`gallery_index_id`) ";
$sql .= ")";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error creating table ".$tableprefix."index<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}


// Gallery Photo
$sql = "CREATE TABLE IF NOT EXISTS `".$tableprefix."photo` (";
$sql .= "`photo_id` int(11) NOT NULL AUTO_INCREMENT, ";
$sql .= "`album_id` int(11) NOT NULL, ";
$sql .= "`photo_filename` varchar(255) NOT NULL, ";
$sql .= "`photo_title` varchar(255) NOT NULL, ";
$sql .= "`photo_summary` varchar(255) NOT NULL, ";
$sql .= "`photo_text` longtext NOT NULL, ";
$sql .= "`photo_keywords` varchar(255) NOT NULL, ";
$sql .= "PRIMARY KEY (`photo_id`) ";
$sql .= ")";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error creating table ".$tableprefix."photo<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}

// Gallery settings
$sql = "CREATE TABLE IF NOT EXISTS `".$tableprefix."settings` (";
$sql .= "`settings_key` varchar(50) NOT NULL, ";
$sql .= "`settings_value` varchar(255) NOT NULL, ";
$sql .= "PRIMARY KEY (`settings_key`) ";
$sql .= ")";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error creating table ".$tableprefix."settings<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}

// Insert initial content

// Gallery Category - All
$sql = "INSERT INTO `".$tableprefix."category` (`category_id`, `category_slug`, `category_title`, `category_text1`, `category_text2`) VALUES (1, 'index', 'Gallery Index', '', '')";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error adding default content to ".$tableprefix."category<br />\nMySql:<br />$sql<br />\nMySql Error:<br />".mysql_error()."\n");}

// Gallery settings
$sql = "INSERT INTO `".$tableprefix."settings` (`settings_key`, `settings_value`) VALUES ";
$sql .= "('imagedir', '/gallery/'), ";
$sql .= "('showtitle', '0'), ";
$sql .= "('slideshowdelay', '10'), ";
$sql .= "('slideshowautorepeat', '1'), ";
$sql .= "('categorypreviewnum', '3'), ";
$sql .= "('buttondir', '/images/gallerybuttons/'), ";
$sql .= "('login_expiry', '3600'), ";
$sql .= "('gallerypreviewnum', '3'), ";
$sql .= "('installdir', '/penguingallery/'), ";
$sql .= "('excludefiles', ''), ";
$sql .= "('adminuser', 'admin'), ";
$sql .= "('adminpass', 'fd1c707534cbe38b354d5716d01a49ec'), ";
$sql .= "('defaultcategory', '')";

if (!mysql_query($sql, $db)) {$errors->errorMsg("Error adding default content to ".$tableprefix."settings");}



print <<< EOF
<html>
<head><title>Database setup - complete</title></head>
<body>
<h1>Setup Complete</h1>
<p>Database $dbname setup complete.</p>
<p>Please <a href="../index.php">login now</a> to change your password.</p>
<p>
EOF;
*/


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
	print "<input type=\"hidden\" name=\"key\" value=\"$value\" />\n";	
}

print <<< EOT2
<input type="submit" value="yes" />
<form method="post" action=$post_filename>
EOT2;

print "<input type=\"hidden\" name=\"confirm$field\" value=\"no\" />\n";

foreach ($parameters as $key=>$value)
{
	print "<input type=\"hidden\" name=\"key\" value=\"$value\" />\n";	
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




?>
