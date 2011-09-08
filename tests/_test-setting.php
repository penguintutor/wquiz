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
$test_value = 'test_text2 $ ; this funny % char 2089#';
$test_key = 'test_key';

if ($debug) {print "Getting settings\n";}

$settings = Settings::getInstance();

if ($debug) {print "Setting value\n";}

$settings->setSetting($test_key, $test_value);

if ($debug) {print "Reading value back\n";}

$updated_value = $settings->getSetting($test_key);

if ($debug) {print "Reloading values\n";}

// reload array
$settings->reloadSettings();

if ($debug) {print "Reading reloaded value\n";}

$check_value = $settings->getSetting($test_key);

if ($debug) {print "Testing result\n";}

if ($test_value == $updated_value && $test_value == $check_value) {$test_result = true;}
else {$test_result = false;}

if (!$test_result) 
{
	print ("*** Fail ***\n");
}
else 
{
	print ("*** Pass ***\n");
}
	
print ("test value:\n");
print_r ($test_value);

print ("\nset value:\n");
print_r ($updated_value);

print ("\nreloaded value:\n");
print_r ($check_value);

	

?>
