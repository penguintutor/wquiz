#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::AdminSession;


use strict;

my $template = "templates/listquestions.html";
my $page = "admin/listquestions.pl";


# Default Values - can be overridden in quiz.cfg
our $adminsessiontimeout = 240;
# Num chars to show from the question
my $questionsumlength = 50;

our ($dbname, $dbuser, $dbpass, $dbtable, %quiznames, @category, %numquestions, @csscategories, %cssfile, %cssextra, %cssindex, $dbsessiontable);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

# First make sure we have the cookie - otherwise go to login page
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizadminsession', -expires=>'+4h');
if (!defined $session || $session eq "") {redirect ("index.pl?status=2");}
# Now check that logged in user is valid
if (!Quizlib::AdminSession::check_login ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $adminsessiontimeout)) {redirect ("index.pl?status=3"); exit ;}


my $questionlist = "";
my ($thisquestion, $questionsummary, @question_details, $quizlist, $questiontype, $created, $reviewed);
# Iterate over every question
foreach $thisquestion (Quizlib::Qdb::db_list_options ($dbname, $dbuser, $dbpass, $page, $dbtable, "question"))
	{
	# Load actual question
	@question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$thisquestion\"");
	if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $thisquestion", "Array not defined");}
	
	#Extract information - don't need to do this for all values, but it makes it easier to read / modify
	$quizlist = $question_details[1];
	$questionsummary = summarise($question_details[3]);
	$questiontype = $question_details[5];
	$created = $question_details[14];
	$reviewed = $question_details[15];
	
	
	# print summary with links
	$questionlist .= "<tr><td><a href=\"testquestion.pl?question=$thisquestion\">$thisquestion</a></td><td><a href=\"edit.pl?question=$thisquestion\">$questionsummary</a></td><td>$questiontype</td><td>$quizlist</td><td>$created</td><td>$reviewed</td></tr>\n";
	
	}





open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/%%questionlist%%/$questionlist/;
	print;
	}
	
close (TEMPLATE);


# Shortens question to only a summary
sub summarise
{
my ($fulltext) = @_;

# Keep full text as we may need it later
my $summarytext = $fulltext;
# Remove any newlines etc. - looking for <br or <p
if ($summarytext =~ /\<br/) 
	{
	$summarytext = substr $summarytext, 0, (index $summarytext, '<br');
	}
if ($summarytext =~ /\<p/) 
	{
	$summarytext = substr $summarytext, 0, (index $summarytext, '<p');
	}

# Strip any html - simple approach
$summarytext =~ s/<.+?>//g;

# If we have a 0 length (ie. only html tags before the first <p> or <br> - then start again - but this time ignore the new lines / paragraph)
if (length $summarytext < 2) 
	{
	$summarytext = $fulltext;
	$summarytext =~ s/<.+?>//g;
	}

# Now shorten to max 
if (length $summarytext > $questionsumlength) {$summarytext = (substr $summarytext, 0, ($questionsumlength-4))." ...";}

return $summarytext;
}
