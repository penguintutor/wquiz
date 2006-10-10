#!/usr/bin/perl -w
# Show answers to quiz

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/offlineanswers.html";
my $page = "offlineanswers.pl";

# Number of digits in session key
# Use a short session key and prefix with the current number from a serial file - this was guarentee unique
# Note we also use digits only - see later
my $keylen = 3;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $dbtable, $offlinecountfile, @csscategories, %cssindex, $offlinequiz, $dbsessiontable, $dbactivetable, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# First check if offlinequiz is allowed
if (!defined($offlinequiz) || !$offlinequiz) {Quizlib::Errors::offline_not_allowed();}


# Allow alternate CSS using the style param - don't use here - but maintain for future use
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);


# Load name & quiz name from form & security check
my $given_quiz = param ("quiz");
if (!defined $given_quiz || $given_quiz eq "") {Quizlib::Security::missing_parm_error ($page, "quiz");}
my $quiz = Quizlib::Security::chk_from_list ($page, "quiz", $given_quiz, @category);


# See if we have images selected - we don't display in answers, but need to remember to pass back on url if reqd
my $showimages = 0;
my $given_images = param ("images");
if (defined $given_images && $given_images eq "yes") {$showimages = 1;} 

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizofflinesession', -expires=>'+4h');
if (!defined $session || $session eq "") {cache_error($page, $session);}
# Now get details about this session - which checks the session is valid as well
my @question_info = Quizlib::QuizSession::get_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, 1);

# If we don't have values in the question_info then session doesn't exist - so back to start - we will lose style information
# Get back : quizname, username, question, answer
# Just quick check how many values returned - if less than 4 then it's an invalid session
# This will also check for an out of range number
# If fail then $question_info will have details (doesn't handle any differently at the moment)
# 5 = offline
if (scalar @question_info < 5) {Quizlib::Errors::cache_error($page, "$session - $question_info[0]");} 

# Note we overwrite some of the info passed from the user with that in the DB.
# We don't trust the user given info
my $status = $question_info[0];
$quiz = $question_info[1]; 
my $name = $question_info[2];
# The actual question number in the question DB
#my $quiznum = $question_info[3];
#my $answer = $question_info[4];


# Check that status is 5 (offline)
if ($status != 5) {Quizlib::Errors::sequence_error($page, $quiz, 3, "");}

# Used for back to questions button
my $urlstring = "quiz=$quiz&style=$style";
if ($showimages) {$urlstring.="&images=yes";}

# Log answers read
Quizlib::Misc::log_score ($quiz, $session, "offline", $numquestions{$quiz});

my @questions;
($status, $quiz, $name, @questions) = Quizlib::QuizSession::get_questions ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable);
if (!@questions || scalar @questions < $numquestions{$quiz}) {Quizlib::Errors::misc_error($page, $quiz, "insufficient answers given, expected $numquestions{$quiz}, got ".scalar @questions);}


my $questionnum;
my @question_details;
my $formattedanswers = "";
# Get and display answers
for ($questionnum = 0; $questionnum < scalar @questions; $questionnum++)
	{
	@question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$questions[$questionnum]\"");
	if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $questions[$questionnum]", "Array not defined reading question");}
	
	$formattedanswers .= "<h2>Question Number ".($questionnum+1)."</h2>\n";

	#- Could reprint the question here if we wanted - for now we'll just output the answer
	# As answer should be included in the answer comments, just need to display those
	
	$formattedanswers .= "<p>$question_details[7]</p>";
	
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
print header();
while (<TEMPLATE>)
	{
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%questions\%\%/$formattedanswers/;
	s/\%\%quizname\%\%/$quiznames{$quiz}/;
	s/\%\%serial\%\%/$session/;
	s/\%\%style\%\%/$style/g;
	s/\%\%urlstring\%\%/$urlstring/;
	
	print;
	}
	
close (TEMPLATE);

