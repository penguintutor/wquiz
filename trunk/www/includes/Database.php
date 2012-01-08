<?php
/*** This is a dummy class file to allow for alternative database formats
loads the actual class file based on the dbtype specified in the database config file
***/

/* Only mysql is officially supported (default if no other specified -
alternatives can be added by creating appropriate class file */

// No error checking 
// A missing or corrupt Database_*.php file will break

if (!isset ($dbsettings['dbtype']) || $dbsettings['dbtype'] == '')
{
	$db_file = "Database_mysql.php";
}
else
{
	$db_file = "Database_".$dbsettings['dbtype'].".php";
}

require_once("includes/".$db_file);


?>
