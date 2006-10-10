#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::AdminSession;

use strict;

my $template = "templates/add.html";
my $page = "admin/add.pl";

# Default Values - can be overridden in quiz.cfg
our $adminsessiontimeout = 240;

our ($dbname, $dbuser, $dbpass, $dbtable, %quiznames, @category, @allowedcategory, %numquestions, @csscategories, %cssfile, %cssextra, %cssindex, $dbsessiontable);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

# First make sure we have the cookie - otherwise go to login page
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizadminsession', -expires=>'+4h');
if (!defined $session || $session eq "") {redirect ("index.pl?status=2");}
# Now check that logged in user is valid
if (!Quizlib::AdminSession::check_login ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $adminsessiontimeout)) {redirect ("index.pl?status=3"); exit ;}



my $editinfo = "<form action=\"save.pl\" method=\"post\">\n\n";

# MySQL Fields
#0 question 
#1 quiz
#2 section
#3 intro
#4 input
#5 type
#6 answer
#7 reason
#8 reference
#9 hint
#10 image
#11 comments
#12 qfrom
#13 email
#14 created
#15 reviewed


$editinfo .= "<h3>New Question</h3>\n<input type=\"hidden\" name=\"question\" value=\"0\" />\n";
# Quizlist
$editinfo .= "Quiz Categories:\n<ul>\n";
my $quizcategory;
# Note a comma is added to the end of each option so that we can use a single field
my $i;
for ($i =0; $i < scalar @allowedcategory; $i++)
	{
	$editinfo.= "<li><input type=\"checkbox\" name=\"quiz_$i\" value=\"$allowedcategory[$i]\" />$allowedcategory[$i]</li>\n";
	}

$editinfo .= "</ul>\n";
# Section
$editinfo .= "Section (e.g. chapter / subcategory): <input type=\"text\" name=\"section\" value=\"\" /><br />\n";
# Question text
$editinfo .= "Intro:<br /><textarea name=\"intro\" cols=\"40\" rows=\"10\"></textarea><br />\n";
# Input
$editinfo .= "Input (pre,actual,post), or (comma seperated radio options): <br /><textarea name=\"input\" cols=\"40\" rows=\"10\"></textarea><br />\n";
# Type
$editinfo .= "Question Type: <select name=\"type\">\n";
$editinfo .= "<option value=\"notselected\">Please Select</option>\n";
$editinfo .= "<option value=\"text\">text</option>\n";
$editinfo .= "<option value=\"TEXT\">TEXT</option>\n";
$editinfo .= "<option value=\"number\">number</option>\n";
$editinfo .= "<option value=\"radio\">radio</option>\n";
$editinfo .= "<option value=\"checkbox\">checkbox</option>\n";
$editinfo .= "</select><br />\n";
# Answer - free text - may want to interpret different number formats, e.g. 3 instead of 3,3 and 3-4 instead of 3,4 for a number.
$editinfo .= "Answer (radio = number from 0; number = min,max; text = perl regexp no /; checkbox=digits of answer starting 0): <br /><textarea name=\"answer\" cols=\"40\" rows=\"10\"></textarea><br />\n";
# Reason
$editinfo .= "Reason (use &lt;b&gt; around the actual answer):<br />\n<textarea name=\"reason\" cols=\"40\" rows=\"5\"></textarea><br />\n";
# Reference
$editinfo .= "Reference: <input type=\"text\" name=\"reference\" value=\"\" /><br />\n";
# Hint
$editinfo .= "Hint: <input type=\"text\" name=\"hint\" value=\"\" /><br />\n";
# Image
$editinfo .= "Image (URL): <input type=\"text\" name=\"image\" value=\"\" /><br />\n";
# Comment 
$editinfo .= "Comment (not shown to the user):<br />\n<textarea name=\"comment\" cols=\"40\" rows=\"5\"></textarea><br />\n";
# Contributer
$editinfo .= "Contributor: <input type=\"text\" name=\"qfrom\" value=\"\" /><br />\n";
# Email
$editinfo .= "Contributor Email: <input type=\"text\" name=\"email\" value=\"\" /><br />\n";

# Created - handled automatically
# Updated - handled automatically


# Finally the submit button
$editinfo .= "<input type=\"submit\" value=\"Save\" />\n\n</form>";




open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/%%addinfo%%/$editinfo/;
	print;
	}
	
close (TEMPLATE);



