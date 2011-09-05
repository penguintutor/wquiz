<?php
/* Provides navigation buttons used in question.php */


// show the navigation buttons (form) 
// current is page we are currently on 
// min is first question (normally 1)
// max is last question (number of questions)
// Does not add a help button - add this seperately 
function showNavigation ($current, $min, $max)
{
	// note that the value entries need to be static as these are used 
	// in the check during question.php
	print "<input type=\"submit\" name=\"nav\" value=\"|<<\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\"<<\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\">>\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\">>|\">\n";
	print "<input type=\"submit\" name=\"nav\" value=\"Finish\">\n";
}


?>
