<?php
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

// Enable debugging
//error_reporting(E_ALL);
//ini_set('display_errors', true);

//$debug = false;

// adminsetup is within the admin directory - this will load the main setup.php as well 
require_once ("adminsetup.php");

// message is used to provide feedback to the user
//eg. if we get here from an expired session
$message = '';


// Authentication class required for admin functions
require_once($include_dir."SimpleAuth.php");
// add this here as not required for some pages (which use Quiz.php instead)
require_once ($include_dir."Quizzes.php");

// must add this before we require the menu 
$admin_menu = 'home';

/*** Authentication ***/
if (isset($debug) && $debug) {print "\nAuthentication\n";}
// user must be logged in for any admin functions
// this needs to be before we output anything as it uses sessions (cookies)
$auth = new SimpleAuth ($settings->getSetting('admin_login_username'), $settings->getSetting('admin_login_password'), $settings->getSetting('admin_login_expirytime'));
// if not logged in redirect to login page
$status = $auth->checkLogin();
if ($status != 1) 
	{
	// no from as use default which goes back to this page
	header("Location: ".ADMIN_LOGIN_FILE."?status=$status");
	// header will redirect to a new page so we just close this script
	exit (0);	//Important to stop script here
	}
// If we reach here then login was successful
$sessionUsername = $auth->getUser();




// header template
$templates->includeTemplate('header', 'admin');

// Show menu
require_once ($include_dir."adminmenu.php");

$quizzes = ADMIN_QUIZZES_FILE;
$questions = ADMIN_LIST_FILE;
$settings_file = ADMIN_EDIT_SETTINGS_FILE;
$pwchange_file = ADMIN_USER_FILE;
$main_css = CSS_ID_ADMIN_MAIN;

print <<< EOT
<div id="$main_css">
<h1>Administration</h1>
<h2>Tasks</h2>
<ul>
<li><a href="$quizzes">Quizzes</a></li>
<li><a href="$questions">Questions</a></li>
</ul>
<ul>
<li><a href="$settings_file">Settings</a></li>
<li><a href="$pwchange_file">Change password</a></li>
</ul>
</div>
EOT;



// footer template
$templates->includeTemplate('footer', 'admin');



?>
