#!/usr/bin/perl -w
# Setup cache files, and show welcome info / no. questions

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::ActiveSec;
use Quizlib::QuizSession;

use strict;

my $template = "templates/details.html";
my $page = "details.pl";

# Number of digits in session key
my $keylen = 20;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbtable, $dbuser, $dbpass, @csscategories, %cssfile, %cssindex, $seccache, $seclogfile, $denyfile, $dbsessiontable, $dbactivetable, $headerfile, $footerfile, $activesecstatus);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";


# First check that user isn't disallowed
my $ipaddr = $ENV{'REMOTE_ADDR'};
# will not return if fails check
if ($activesecstatus) {Quizlib::ActiveSec::check_qal($denyfile, $seccache, $seclogfile, $ipaddr);}
# If we reach here we've passed the active security checking


# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";

# Load name & quiz name from form & security check
my $given_name = param ("name");
if (!defined $given_name || $given_name eq "") {$given_name="Anonymous";}
$given_name = Quizlib::Security::text_2_html ($page, "name", $given_name);
my $name = Quizlib::Security::chk_string_html ($page, "name", $given_name);

my $given_quiz = param ("quiz");
if (!defined $given_quiz || $given_quiz eq "") 
	{
	# If we did not get name and quiz then could be from bookmark so return to index.pl
	if	($given_name eq "Anonymous") {print redirect ("index.pl"); exit 0;}
	Quizlib::Security::missing_parm_error ($page, "quiz");
	}
my $quiz = Quizlib::Security::chk_from_list ($page, "quiz", $given_quiz, @category);

# Information about the quiz
my $info = "";
if (defined $quizintro{$quiz}) {$info = $quizintro{$quiz};}

# Create unique key 
my $session_key = Quizlib::Misc::gen_random_key ($keylen);

# Setup which questions we are going to have (unique id)
my @all_questions = Quizlib::Qdb::db_list_options ($dbname, $dbuser, $dbpass, $page, $dbtable, "question", "where find_in_set(\"$quiz\",quiz) > 0");

# If we don't have enough questions - then error - must have at least one more
if (scalar @all_questions <= $numquestions{$quiz}) {Quizlib::Errors::num_questions($page, $quiz, $numquestions{$quiz});}
my ($i, $rand_num);
my @questions;
# populate the entries
for ($i =0; $i < $numquestions{$quiz}; $i++)
	{
	$rand_num = int (rand (scalar @all_questions - 1));
	# We have a loop here to allow us to increment if we get a null entry (ie. already used)
	while ($all_questions[$rand_num] eq "") 
		{
		$rand_num ++;
		if ($rand_num >= scalar @all_questions) {$rand_num = 0;}
		}
	# Add question name to the array
	$questions[$i] = $all_questions[$rand_num];
	
	# Set that question to null so that it won't be reused
	$all_questions[$rand_num] = "";
	}


# Setup new session (1 for online quiz)
Quizlib::QuizSession::newquiz ($session_key, $quiz, 1, $name, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, @questions);


# Create cookie with the session key
my $cgiQuery = CGI::new();
# Do not set expiry for the cookie - that way it will last until browser is closed down - so we don't need to refresh cookie
my $cookie1 = $cgiQuery->cookie (-name=>'quizsession', -value=>$session_key);



my $urlstring = "question=1&amp;style=$style";

# Log the entry
Quizlib::Misc::log_login ($quiz, $name, $session_key);

# Get headers and footers from external file
my $headertext = "";
if (defined $headerfile && $headerfile ne "")
{
	$headertext = Quizlib::Misc::readhtml($headerfile);
}
my $footertext = "";
if (defined $footerfile && $footerfile ne "")
{
	$footertext = Quizlib::Misc::readhtml($footerfile);
}

open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header(-cookie=>$cookie1);
while (<TEMPLATE>)
	{
	s/\%\%css\%\%/$css/g;
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%info\%\%/$info/;
	s/\%\%numquestions\%\%/$numquestions{$quiz}/;
	s/\%\%quizname\%\%/$quiznames{$quiz}/;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%urlstring\%\%/$urlstring/;
	s/\%\%username\%\%/$name/;
	
	print;
	}
	
close (TEMPLATE);


