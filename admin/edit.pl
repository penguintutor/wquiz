#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::AdminSession;

use strict;

my $template = "templates/edit.html";
my $page = "admin/edit.pl";

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



# Load parameter question=
my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
my $questionnum = Quizlib::Security::chk_alpnum ($page, "question", $given_question);


my $editinfo = "<form action=\"save.pl\" method=\"post\">\n\n";

# Load question
my $question_details = Quizlib::Qdb::db_get_entry_hashref ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$questionnum\"");
if (! $question_details) {Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $questionnum", "Array not defined");}

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


$editinfo .= "<h3>Question Number: ".$question_details->{'question'}."</h3>\n<input type=\"hidden\" name=\"question\" value=\"".$question_details->{'question'}."\">\n";
# Quizlist
#$editinfo .= "Quiz (comma seperated): <input type=\"text\" name=\"quiz\" value=\"$question_details{"quiz"}\"><br />\n";
$editinfo .= "Quiz Categories:\n<ul>\n";
my $quizcategory;
# Note a comma is added to the end of each option so that we can use a single field
my $i;
for ($i =0; $i < scalar @allowedcategory; $i++)
{
   if ($question_details->{'quiz'} =~ /^$allowedcategory[$i],/ || $question_details->{'quiz'} =~ /^$allowedcategory[$i]$/ || $question_details->{'quiz'} =~ /,$allowedcategory[$i]$/ || $question_details->{'quiz'} =~ /,$allowedcategory[$i],/)
   	{
	$editinfo.= "<li><input type=\"checkbox\" name=\"quiz_$i\" value=\"$allowedcategory[$i]\" checked=\"checked\">$allowedcategory[$i]</li>\n";
	}
   else
	{
	$editinfo.= "<li><input type=\"checkbox\" name=\"quiz_$i\" value=\"$allowedcategory[$i]\">$allowedcategory[$i]</li>\n";
	}
	
}
$editinfo .= "</ul>\n";
# Section
$editinfo .= "Section (e.g. chapter / subcategory): <input type=\"text\" name=\"section\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'section'})."\"><br />\n";
# Question text
$editinfo .= "Intro:<br /><textarea name=\"intro\" cols=\"40\" rows=\"10\">".Quizlib::Misc::format_edit_html($question_details->{'intro'})."</textarea><br />\n";
# Input
$editinfo .= "Input (pre,actual,post), or (comma seperated radio options): <br /><textarea name=\"input\" cols=\"40\" rows=\"10\">".Quizlib::Misc::format_edit_html($question_details->{'input'})."</textarea><br />\n";
# Type
$editinfo .= "Question Type: <select name=\"type\">\n";
if ($question_details->{'type'} eq "text") {$editinfo .= "<option value=\"text\" selected=\"selected\">text</option>\n";} else {$editinfo .= "<option value=\"text\">text</option>\n";}
if ($question_details->{'type'} eq "TEXT") {$editinfo .= "<option value=\"TEXT\" selected=\"selected\">TEXT</option>\n";} else {$editinfo .= "<option value=\"TEXT\">TEXT</option>\n";}
if ($question_details->{'type'} eq "number") {$editinfo .= "<option value=\"number\" selected=\"selected\">number</option>\n";} else {$editinfo .= "<option value=\"number\">number</option>\n";}
if ($question_details->{'type'} eq "radio") {$editinfo .= "<option value=\"radio\" selected=\"selected\">radio</option>\n";} else {$editinfo .= "<option value=\"radio\">radio</option>\n";}
if ($question_details->{'type'} eq "checkbox") {$editinfo .= "<option value=\"checkbox\" selected=\"selected\">checkbox</option>\n";} else {$editinfo .= "<option value=\"checkbox\">checkbox</option>\n";}
$editinfo .= "</select><br />\n";
# Answer - free text - may want to interpret different number formats, e.g. 3 instead of 3,3 and 3-4 instead of 3,4 for a number.
$editinfo .= "Answer (radio = number from 0; number = min,max; text = perl regexp no /; checkbox=digits of answer starting 0): <br /><textarea name=\"answer\" cols=\"40\" rows=\"10\">".Quizlib::Misc::format_edit_html($question_details->{'answer'})."</textarea><br />\n";
# Reason
$editinfo .= "Reason (use &lt;b&gt; around the actual answer):<br />\n<textarea name=\"reason\" cols=\"40\" rows=\"5\">".Quizlib::Misc::format_edit_html($question_details->{'reason'})."</textarea><br />\n";
# Reference
$editinfo .= "Reference: <input type=\"text\" name=\"reference\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'reference'})."\"><br />\n";
# Hint
$editinfo .= "Hint: <input type=\"text\" name=\"hint\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'hint'})."\"><br />\n";
# Image
$editinfo .= "Image (URL): <input type=\"text\" name=\"image\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'image'})."\"><br />\n";
# Comment 
$editinfo .= "Comment (not shown to the user):<br />\n<textarea name=\"comment\" cols=\"40\" rows=\"5\">".Quizlib::Misc::format_edit_html($question_details->{'comments'})."</textarea><br />\n";
# Contributer
$editinfo .= "Contributor: <input type=\"text\" name=\"qfrom\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'qfrom'})."\"><br />\n";
# Email
$editinfo .= "Contributor Email: <input type=\"text\" name=\"email\" value=\"".Quizlib::Misc::format_edit_html($question_details->{'email'})."\"><br />\n";
# Created - not changed, we need to pass through to retain
$editinfo .= "<input type=\"hidden\" name=\"created\" value=\"".$question_details->{'created'}."\"><br />\n";
# Updated - handled automatically


# Finally the submit button
$editinfo .= "<input type=\"submit\" value=\"Save\" />\n\n</form>";




open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/%%editinfo%%/$editinfo/;
	print;
	}
	
close (TEMPLATE);



