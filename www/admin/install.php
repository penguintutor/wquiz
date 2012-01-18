<?php



/** Create new DB (if required) 
For version 0.4.0
****/


// These must be the same as setup.php
// This script does not use setup.php, but all others do
define ("ADMIN_DIR", "admin");	 							// Admin directory
define ("ADMIN_INSTALL_FILE", ADMIN_DIR."/install.php"); 	// Install / setup script

// relative path to the apps directory (used on url references)
$rel_path = "../";

//$default_cfg_file = 'default.cfg';
$default_cfg_file = 'test.cfg';

// action_required holds the next step in the config process
// 'cfgfile', 'database', 'tables', 'settings', 'secure'
$action_required = 'cfgfile';
// If we have a message to give back to the user store in here (eg. "database cannot be left blank")
$message = '';
// if we find an error then increment this (eg. missing paramter)
// only use this for soft errors we want to report later rather than critical errors we just stop on
$error_found = 0;


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
	// not save
	if (isset($_POST['action']) && $POST['action']=='save')
	{
		
		// check we have all parameters and they are valid
		foreach ($database_settings_required as $this_setting)
		{
			if (!isset($_POST[$this_setting]) || $_POST[$this_setting] == '')
			{
				$message .= "$this_setting is a required field<br />\n";
				$error_found ++;
			}
			// check for allowed characters
			// quite basic - if you need to include other characters then create default.cfg manully
			elseif (!preg_match ("\w\._-", $_POST[$this_setting]))
			{
				$message .= "Invalid character in $this_setting<br />\n";
			}
		}
		// If no errors found - perform validation checks
		if ($error_found == 0)
		{
			/* validate certain fields */
			//- add this
			
			
			/* create the config files */
			//- add this
		}
		
	}
	
}

// If we are still at status cfgfile - show the database setup form to the customer
$post_filename = $rel_path.ADMIN_INSTALL_FILE;
print <<< EOT
<html>
<head>
<title>wQuiz setup</title>
</head>
<body>
<h1>wQuiz setup</h1>
<p>Please provide the following information for the database setup.
</p>
<p>To create the database you will need to provide a username and password with administrator access (eg. create access). This can be changed later if required.</p>
<p>
<form action="$post_filename" method="POST">
<input type="hidden" name="action" value="save" />
Quizname (short name eg sitename - no spaces): <input type="text" name="quizname" value="" />
Database type (mysql recommended): <select name="dbtype"><option value="mysql">mysql</option><option value="mssql">MS SQL</option><option value="other">Other</option></select>
Database username (admin access):  <input type="text" name="username" value="" />
Database password<input type="password" name="quizname" value="" />
Hostname: <input type="text" name="hostname" value="" />
</form>


</p>
</body>
</html>
EOT;



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


function displayForm ($action)
{
	print <<< EOT
<html>
<head>
<title>Install wquiz</title>
</head>
<body>
<h1>Install wquiz</h1>
<p>Please provide the following details to install and configure wQuiz.</p>
<p>
<form method="post" action="install.php">
<input type="hidden" name="action" value="save" />
</p>
EOT;
	if ($action == '' || $action == 'cfgfile')
	{
	print <<< EOTCFG
<h2>Database information</h2>	
<p>
Provide the information required to administer the database. This must have admin access to allow the install to create the appropriate database tables (if not already defined). The username can be changed to one with lower privilages later.
</p>
<p>
Short title (spaces / special characters ignored) <input type="text" name="shortname" /><br />
Database type (recommend mysql) <input type="text" name="dbtype" value="mysql" /><br />
Database hostname (or ipaddress) <input type="text" name="database" value="" /><br />
Database username (admin access required) <input type="text" name="username" /><br />
Database password <input type="password" name="password" /><br />
Database Table prefix (if required) <input type="text" name="tableprefix" />
</p>
EOTCFG;
	}


print <<< EOTLAST
</body>
</html>
EOTLAST;
	exit (0);	
	
}




?>
