#!/usr/bin/perl -w
# Offline quiz setup cache and show questions

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/offlinequestions.html";
my $page = "offlinequestions.pl";


my $cookie1;

# Number of digits in session key
# Use a short session key and prefix with the current number from a serial file - this will guarentee unique
# Note we also use digits only - see later
my $keylen = 3;

my $questionnum;
my $i;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $offlinecountfile, $dbtable, @csscategories, %cssindex, $dbsessiontable, $dbactivetable, $offlinequiz, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# First check if offlinequiz is allowed
if (!defined($offlinequiz) || !$offlinequiz) {Quizlib::Errors::offline_not_allowed();}


# new is used to check if we need to reset the session key - can allow us to show an old session, but only from the same machine, and if not viewed any other sessions - cookie must still exist.
# If set to 1 then we make sure we are handling a new quiz
my $new = 0;
my $given_new = param ("new");
if (defined $given_new && $given_new == 1) {$new = 1;}

# Allow alternate CSS using the style param - don't use here - but maintain for future use
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);


# Load name & quiz name from form & security check
my $given_quiz = param ("quiz");
if (!defined $given_quiz || $given_quiz eq "") {Quizlib::Security::missing_parm_error ($page, "quiz");}
my $quiz = Quizlib::Security::chk_from_list ($page, "quiz", $given_quiz, @category);

# See if we have images selected
my $showimages = 0;
my $given_images = param ("images");
if (defined $given_images && $given_images eq "yes") {$showimages = 1;} 

# Is this an existing session
my $existingsession;

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizofflinesession', -expires=>'+4h');

# If not new, then we see if we have an existing session 
# (if not then we may still end up with a new session
if ($new != 1) 
	{
	# Load cookie with session number
	my $cgiQuery = CGI::new();
	my $session = $cgiQuery->cookie (-name=>'quizofflinesession', -expires=>'+4h');
	if (!defined $session || $session eq "") {$new = 1;}
	}
	


my @all_questions;
	
my (@questions, @answers);
# If new questions
if ($new)
	{

	# Create unique key - could use random number - but this ensures min digits 
	$session = Quizlib::Misc::offlineNumber($offlinecountfile) . Quizlib::Misc::gen_random_number ($keylen);
	
	# Setup which questions we are going to have (unique id)
	@all_questions = Quizlib::Qdb::db_list_options ($dbname, $dbuser, $dbpass, $page, $dbtable, "question", "where find_in_set(\"$quiz\",quiz) > 0");
	
	# Note when creating entries to cache we still setup as though this will be a online quiz, but ignore the answers section.
	# If someone tries to take the session key from offline, but use it for online, then it will be rejected because of the status
		
	# If we don't have enough questions - then error - must have at least one more
	if (scalar @all_questions <= $numquestions{$quiz}) {Quizlib::Errors::num_questions($page, $quiz, $numquestions{$quiz});}
	my $rand_num;
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
		# also add -1 to answers to mean no answer
		$answers[$i] = -1;
		# Set that question to null so that it won't be reused
		$all_questions[$rand_num] = "";
	}
	
	# Setup new session 
	Quizlib::QuizSession::newquiz ($session, $quiz, 5, "offline", $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, @questions);


	# Create cookie with the session key
	$cgiQuery = CGI::new();
	# Do not set expiry for the cookie - that way it will last until browser is closed down - so we don't need to refresh cookie
	$cookie1 = $cgiQuery->cookie (-name=>'quizofflinesession', -value=>$session);
	
	
	# Log the entry
	Quizlib::Misc::log_login ($quiz, "offline", $session);
	}
else
	{
	# Handle existing session here
	# Get details about this session - which checks the session is valid as well
	my @question_info = Quizlib::QuizSession::get_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, 1);

	if (scalar @question_info < 5) {Quizlib::Errors::cache_error($page, "$session - $question_info[0]");} 

	# Note we overwrite some of the info passed from the user with that in the DB.
	# We don't trust the user given info
	my $status = $question_info[0];
	$quiz = $question_info[1]; 
	my $name = $question_info[2];
	
	# Check that status is 5 (offline)
	if ($status != 5) {Quizlib::Errors::sequence_error($page, $quiz, 3, "");}

	($status, $quiz, $name, @questions) = Quizlib::QuizSession::get_questions ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable);
	if (!@questions || scalar @questions < $numquestions{$quiz}) {Quizlib::Errors::misc_error($page, $quiz, "insufficient answers given, expected $numquestions{$quiz}, got ".scalar @questions);}

	}

my $urlstring = "quiz=$quiz&amp;style=$style";
if ($showimages) {$urlstring.="&amp;images=yes";}


my $formattedquestions = "";
my @question_details;
my @entries; 
for ($questionnum =0 ; $questionnum < scalar @questions; $questionnum++)
	{
	$formattedquestions .= "<h2>Question Number ".($questionnum+1)."</h2>\n";
	
	# Load the question - array will have "" for any null entries so OK to print out
	@question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"".$questions[$questionnum]."\"");
	if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = ".$questions[$questionnum], "Array not defined - loading question");}

	if ($showimages && $question_details[10]) { $formattedquestions.="<img src=\"$question_details[10]\" align=\"right\" alt=\"Question Image\">\n"; }
	$formattedquestions .= "<p>$question_details[3]</p>\n";
	
	# Different types of question
	if ($question_details[5] eq "radio")
		{
		@entries = split /,/, $question_details[4];
		$formattedquestions .= "<ol type=\"a\">\n";
		for ($i = 0; $i < scalar @entries; $i++)
			{
			$formattedquestions.="<li>$entries[$i]\n";
			}
		$formattedquestions .= "</ol>\n";
		}
	elsif ($question_details[5] eq "checkbox")
		{
		@entries = split /,/, $question_details[4];
		$formattedquestions .= "<ul>\n";
		for ($i = 0; $i < scalar @entries; $i++)
			{
			$formattedquestions.="<li>$entries[$i]\n";
			}
		$formattedquestions .= "</ul>\n";
		}
	elsif ($question_details[5] eq "text" || $question_details[5] eq "number")
		{
		@entries = split /,/, $question_details[4];
		if (!defined $entries[0]) {$entries[0] = ""};
		if (!defined $entries[1]) {$entries[1] = ""}; # Note we don't support default values here - ignore this entry
		if (!defined $entries[2]) {$entries[2] = ""};
		$formattedquestions .= "<ul><li>$entries[0] ____________ $entries[2]</ul>\n"
		}
	if ($showimages) {$formattedquestions.="<br clear=\"all\">\n"; }
	}


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
if (!$existingsession) {print header(-cookie=>$cookie1);}
else {print header();}
while (<TEMPLATE>)
	{
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%questions\%\%/$formattedquestions/;
	s/\%\%quizname\%\%/$quiznames{$quiz}/;
	s/\%\%serial\%\%/$session/;
	s/\%\%style\%\%/$style/g;
	s/\%\%urlstring\%\%/$urlstring/;
	
	print;
	}
	
close (TEMPLATE);


