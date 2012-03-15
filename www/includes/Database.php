<?php
/*** This is a dummy class file to allow for alternative database formats
loads the actual class file based on the dbtype specified in the database config file
***/
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

if (isset ($app_dir)) {require_once($app_dir."/includes/".$db_file);}
else {require_once("includes/".$db_file);}


?>
