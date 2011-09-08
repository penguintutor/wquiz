<?php
// test script - inserts a serialized setting and then reloads it to see if it is the same
// outputs as raw text - view as html source

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', true);

$debug = false;


if ($debug) {print "Loading setup\n";}

require_once("../includes/setup.php");


// input array - this is what we will insert into settings
$test_array = array ('first'=>'|<<', 'previous'=>'<<', 'next'=>'>>', 'last'=>'>>|', 'review'=>'End');
$test_key = 'buttons_navigation';

if ($debug) {print "Getting settings\n";}

$settings = Settings::getInstance();

if ($debug) {print "Setting value\n";}

$settings->setSetting($test_key, serialize($test_array));

if ($debug) {print "Reading value back\n";}

$updated_array = unserialize ($settings->getSetting($test_key));

if ($debug) {print "Reloading values\n";}

// reload array
$settings->reloadSettings();

if ($debug) {print "Reading reloaded value\n";}

$check_array = unserialize ($settings->getSetting($test_key));

if ($debug) {print "Testing result\n";}

$test_result = array_diff_assoc($test_array, $updated_array, $check_array);

if ($test_result != null) 
{
	print ("*** Fail ***\n");
}
else 
{
	print ("*** Pass ***\n");
}
	
print ("test array:\n");
print_r ($test_array);

print ("\nset array:\n");
print_r ($updated_array);

print ("\nreloaded array:\n");
print_r ($check_array);

if ($test_result != null) 
{
	print ("\n\nDiff\n");
	print_r ($test_result);
}
	

?>
