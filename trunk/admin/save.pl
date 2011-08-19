#!/usr/bin/perl -w
# Save updated / new entry.
# If this is updated, then we submit all fields regardless, rather than having to query DB again to check what has changed

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::AdminSession;

use strict;

my $template = "templates/save.html";
my $page = "admin/save.pl";

# These are the type of questions allowed
my @allowed_types = ("text", "TEXT", "number", "radio", "checkbox");

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



# Details of if successful or not
my $successinfo = "";
# Saved details (list of details saved)
my $saveddetails = "";


# After verifying the details they go into an array - with details as below:
my @dbfields;

# MySQL fields
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



# [0] Question (number) if 0, then we have a new question (save, rather than update)
my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
$dbfields[0] = Quizlib::Security::chk_alpnum ($page, "question", $given_question);
if ($dbfields[0] == 0) {$saveddetails .= "<h3>New Question</h3>\n\n";}
else {$saveddetails .= "<h3>Question Number $dbfields[0]</h3>\n\n";}



# [1] quiz (category)
# We have a field for each allowed category, which must match existing
# so no need for extra security checking, but means no alerting
my $allcategories = "";
my $i;
my $thiscategory;
for ($i =0; $i < scalar @allowedcategory; $i++)
	{
	$thiscategory = param ("quiz_".$i);
	#Security checking is done here, only use param if it is same - then we take allowedcataegory anyway 
	if (defined $thiscategory && $thiscategory eq $allowedcategory[$i])
		{
		$allcategories .= $allowedcategory[$i].",";
		}
	}
# Strip off last ,
$allcategories =~ s/,$//;

$dbfields[1] = $allcategories;
$saveddetails .= "<b>Quiz Categories:</b> $dbfields[1]<br />\n";


# [2] Section (e.g. subcategory)
my $given_section = param ("section");
# We do allow a null entry, but this is interpretted as ""
if (!defined $given_section) {$given_section = "";}
$dbfields[2] = Quizlib::Security::chk_alpnum ($page, "section", $given_section);
$saveddetails .= "<b>Section:</b> $dbfields[2]<br />\n";

# [3] intro (Question text)
my $given_intro = param ("intro");
# We do allow a null entry, but this is interpretted as ""
# Only reason for having null entry would be a picture quiz etc.
if (!defined $given_intro) {$given_intro = "";}
$dbfields[3] = Quizlib::Security::chk_alpnum ($page, "intro", $given_intro);
# Remove ' characters which will break the sql save
$dbfields[3] =~ s/'/&apos;/g;
$saveddetails .= "<b>Intro:</b> $dbfields[3]<br />\n";


# [4] input (options / pre or post message)
my $given_input = param ("input");
# We do allow a null entry, but this is interpretted as ""
if (!defined $given_input) {$given_input = "";}
# Remove any \n chars
$given_input =~ s/\n//g;
# convert any risky characters into html format
# Leave formatting as is
$dbfields[4] = Quizlib::Security::chk_alpnum ($page, "input", $given_input);
$dbfields[4] =~ s/'/&apos;/g;
$saveddetails .= "<b>Input:</b> $dbfields[4]<br />\n";

# [5] type (e.g. radio / number / text)
my $given_type = param ("type");
# We do not allow a null entry
if (!defined $given_type || $given_type eq "") {Quizlib::Security::missing_parm_error ($page, "type");}
#- add checking for notselected here, and give a more user friendly error
$dbfields[5] = Quizlib::Security::chk_from_list ($page, "type", $given_type, @allowed_types);
$dbfields[5] =~ s/'/&apos;/g;
$saveddetails .= "<b>Type:</b> $dbfields[5]<br />\n";

# [6] answer (what answer should actually be)
my $given_answer = param ("answer");
# We do not allow a null entry
if (!defined $given_answer || $given_answer eq "") {Quizlib::Security::missing_parm_error ($page, "type");}
$dbfields[6] = Quizlib::Security::chk_alpnum ($page, "answer", $given_answer);
$dbfields[6] =~ s/'/&apos;/g;
# Can check question formats are inline with the question type
# If number - need number,number if only one number then use same for both
if ($dbfields[5] eq "number")
{
	# split numbers into $1, $2
	$dbfields[6] =~ /(\d+),?(\d*)/;
	# Error and exit if we don't have a number
	if (!defined $1 || $1 eq "") {Quizlib::Errors::admin_error ($page, "Number selected, but no number given");}
	# If we have $1, but no $2 then make $2 = $1
	if (!defined $2 || $2 eq "") {$dbfields[6] = "$1,$1";}
	# We still re-do if we had two numbers as this will strip any non digit characters
	else {$dbfields[6] = "$1,$2";}
}
$saveddetails .= "<b>Answer:</b> $dbfields[6]<br />\n";

# [7] reason (free text)
my $given_reason = param ("reason");
# We do allow a null entry, but this is interpretted as ""
# Can still have "correct / incorrect" without explanation, although shouldn't do this
if (!defined $given_reason) {$given_reason = "";}
$dbfields[7] = Quizlib::Security::chk_alpnum ($page, "reason", $given_reason);
$dbfields[7] =~ s/'/&apos;/g;
$saveddetails .= "<b>Reason:</b> $dbfields[7]<br />\n";

# [8] reference
# Only visible in the admin at the moment, but be aware this may be in the public view as well
my $given_reference = param ("reference");
# We do allow a null entry, but this is interpretted as ""
if (!defined $given_reference) {$given_reference = "";}
$dbfields[8] = Quizlib::Security::chk_alpnum ($page, "reference", $given_reference);
$dbfields[8] =~ s/'/&apos;/g;
$saveddetails .= "<b>Reference:</b> $dbfields[8]<br />\n";

# [9] hint
# Not used at the moment - for future use
my $given_hint = param ("hint");
# We do allow a null entry, but this is interpretted as ""
if (!defined $given_hint) {$given_hint = "";}
$dbfields[9] = Quizlib::Security::chk_alpnum ($page, "hint", $given_hint);
$dbfields[9] =~ s/'/&apos;/g;
$saveddetails .= "<b>Hint:</b> $dbfields[9]<br />\n";

# [10] image
my $given_image = param ("image");
# We do allow a null entry
if (!defined $given_image) {$given_image = "";}
$dbfields[10] = Quizlib::Security::chk_alpnum ($page, "image", $given_image);
$dbfields[10] =~ s/'/&apos;/g;
#-- Could check that this is a url here
$saveddetails .= "<b>Image:</b> $dbfields[10]<br />\n";

# [11] comments
# For the administrator only
my $given_comments = param ("comment");
# We do allow a null entry
if (!defined $given_comments) {$given_comments = "";}
$dbfields[11] = Quizlib::Security::chk_alpnum ($page, "comments", $given_comments);
$dbfields[11] =~ s/'/&apos;/g;
$saveddetails .= "<b>Comments:</b> $dbfields[11]<br />\n";

# [12] contributer (qfrom)
# For the administrator only - at the moment, may be made public in future versions
my $given_contributer = param ("qfrom");
# We do allow a null entry
if (!defined $given_contributer) {$given_contributer = "";}
$dbfields[12] = Quizlib::Security::chk_alpnum ($page, "qfrom", $given_contributer);
$dbfields[12] =~ s/'/&apos;/g;
$saveddetails .= "<b>Contributor:</b> $dbfields[12]<br />\n";

# [13] email (email address of the contributer)
# For the administrator only - should not show, but could provide an option to contact the contributer as long as this could be enabled / disabled by the user
my $given_email = param ("email");
# We do allow a null entry
if (!defined $given_email) {$given_email = "";}
$dbfields[13] = Quizlib::Security::chk_alpnum ($page, "email", $given_email);
$dbfields[13] =~ s/'/&apos;/g;
#-- Could check that this is an email address here
$saveddetails .= "<b>Contributor Email:</b> $dbfields[13]<br />\n";

# Get the current date ready for updating created and/or update time
# We use localtime, so it's the date of the server
my @tempdate = localtime(time);
my $today = (1900+$tempdate[5])."-".Quizlib::Misc::to_2_digits($tempdate[4]+1)."-".Quizlib::Misc::to_2_digits($tempdate[3]);

# [14] created
# [15] Updated
#  If created on this save
if ($dbfields[0] == 0)
	{
	$dbfields[14] = $today;
	}
# Update
else
	{
	my $given_created = param("created");
	# Must be defined for an update 
	if (!defined $given_created || $given_created eq "") {Quizlib::Security::missing_parm_error ($page, "created");}
	$dbfields[14] = Quizlib::Security::chk_date_iso($page, "created", $given_created);
	$saveddetails .= "<b>Created Date</b> $dbfields[14]<br />\n";
	}
# Updated is set to today whether it's an add or an update
$dbfields[15] = $today;
$saveddetails .= "<b>Updated Date:</b> $dbfields[15]<br />\n";


# Finish formatting ready for sql update / add
# If add
if ($dbfields[0] == 0) 
	{
	$dbfields[0] = "";

	Quizlib::Qdb::db_insert ($dbname, $dbuser, $dbpass, $page, $dbtable, @dbfields);
	
	# If fail won't return so we know this worked
	$successinfo .= "<h2>New Question Successfully Added</h2>\n";
	}
# If update
else
	{
	# Delete and readd using new values
	Quizlib::Qdb::db_delete_row ($dbname, $dbuser, $dbpass, $page, $dbtable, $dbfields[0]);
	
	
	Quizlib::Qdb::db_insert ($dbname, $dbuser, $dbpass, $page, $dbtable, @dbfields);
	$successinfo .= "<h2>Question Successfully Updated</h2>\n";
	}

$successinfo .= $saveddetails;


open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/%%saveinfo%%/$successinfo/;
	print;
	}
	
close (TEMPLATE);



