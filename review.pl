#!/usr/bin/perl -w

# Allows user to review answer - see what their answer was and what it should have been
# Note we don't do security checking here - this is done by answer before it is stored into the cache

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/review.html";
my $page = "review.pl";

my $i;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbtable, $dbuser, $dbpass, $answercols, @csscategories, %cssfile, %cssindex, $dbsessiontable, $dbactivetable, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";
# Seperate _first used where we don't have anything else on the url
my $urlextra_first = "?style=$style";

my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
my $questionnum = Quizlib::Security::chk_num ($page, "question", $given_question);

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizsession', -expires=>'+4h');
if (!defined $session || $session eq "") {cache_error($page, $session);}
# Now get details about this session - which checks the session is valid as well
my @question_info = Quizlib::QuizSession::get_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum);

# If we don't have values in the question_info then session doesn't exist - so back to start - we will lose style information
# Get back : quizname, username, question, answer
# Just quick check how many values returned - if less than 4 then it's an invalid session
# This will also check for an out of range number
# If fail then $question_info will have details (doesn't handle any differently at the moment)
# 0 = sessionexpired, 1 = already finished, 2 = not an active session
if (scalar @question_info < 5) {Quizlib::Errors::cache_error($page, "$session - $question_info[0]");} 

my $status = $question_info[0];
my $quiz = $question_info[1]; 
my $name = $question_info[2];
# The actual question number in the question DB
my $quiznum = $question_info[3];
my $answer = $question_info[4];

# Check that status is 2 (already marked)
if ($status != 2) {Quizlib::Errors::sequence_error($page, $quiz, 1, "");}

# Number of questions in the quiz
my $questions_num = $numquestions{$quiz};


# Load the full question - array will have "" for any null entries so OK to print out
my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$quiznum\"");
if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $quiznum", "Array not defined");}


# Mark this answer so we know what to output
# Also convert user answers to string
my $user_out = "";
my $status_out;
if ($answer eq "-1") 
	{
	$status_out = "<font class=\"reviewnotanswered\">Not Answered</font>";
	$user_out = "No answer"; 
	}
# if radio
elsif ($question_details[5] eq "radio" || $question_details[5] eq "checkbox")
	{
	if ($answer == $question_details[6]) {$status_out = "<font class=\"reviewcorrect\">Correct</font>"; }
	else {$status_out = "<font class=\"reviewwrong\">Incorrect</font>"; }
	my @options = split /,/, $question_details[4];
	for ($i=0; $i < scalar @options; $i ++)
		{
		if ($answer =~ /$i/) {$user_out .= $options[$i]."<br>\n"; }
		}
	}
elsif ($question_details[5] eq "number")
	{
	my ($min, $max) = split /,/, $question_details[6] ;
	if ($answer >= $min && $answer <= $max) {$status_out = "<font class=\"reviewcorrect\">Correct</font>"; }
	else {$status_out = "<font class=\"reviewwrong\">Incorrect</font>"; }
	# put the before / after into the answer
	my @entries = split /,/, $question_details[4];
	if (!defined $entries[0]) {$entries[0] = ""};
	#value 1 is the default so we don't show
	if (!defined $entries[2]) {$entries[2] = ""};
	$user_out = "$entries[0] $answer $entries[2]";
	}
elsif ($question_details[5] eq "text")
	{
	if ($answer =~ /$question_details[6]/i) {$status_out = "<font class=\"reviewcorrect\">Correct</font>"; }
	else {$status_out = "<font class=\"reviewwrong\">Incorrect</font>"; }
	my @entries = split /,/, $question_details[4];
	if (!defined $entries[0]) {$entries[0] = ""};
	#value 1 is the default so we don't show
	if (!defined $entries[2]) {$entries[2] = ""};
	$user_out = "$entries[0] $answer $entries[2]";
	}
elsif ($question_details[5] eq "TEXT")
	{
	if ($answer =~ /$question_details[6]/) {$status_out = "<font class=\"reviewcorrect\">Correct</font>"; }
	else {$status_out = "<font class=\"reviewwrong\">Incorrect</font>"; }
	my @entries = split /,/, $question_details[4];
	if (!defined $entries[0]) {$entries[0] = ""};
	#value 1 is the default so we don't show
	if (!defined $entries[2]) {$entries[2] = ""};
	$user_out = "$entries[0] $answer $entries[2]";
	}
else
	{
	Quizlib::Errors::question_corrupt ($page, $dbname, $question_details[0], "invalid question type")
	}
		

# Still create form for output - but don't include activity or a submit button
# To validate as html and to handle someone pressing enter on text form we just jump to score.pl on submit
my $form_out = "\n\n<form action=\"score.pl\">\n";
$form_out .= "<input type=\"hidden\" name=\"style\" value=\"$style\" />\n";
my @entries;
# different form types
if ($question_details[5] eq "radio")
	{
	@entries = split /,/, $question_details[4];
	for ($i=0; $i < scalar @entries; $i++) 
		{
		$form_out .=	"<input type=\"radio\" name=\"answer\" value=\"$i\" /> $entries[$i]<br>\n";
		}
	$form_out .= "\n";
	}
elsif ($question_details[5] eq "checkbox")
	{
	@entries = split /,/, $question_details[4];
	for ($i=0; $i < scalar @entries; $i++) 
		{
		$form_out .=	"<input type=\"checkbox\" name=\"$i\" /> $entries[$i]<br>\n";
		}
	$form_out .= "\n";
	}
elsif ($question_details[5] eq "text" || $question_details[5] eq "number")
	{
	@entries = split /,/, $question_details[4];
	if (!defined $entries[0]) {$entries[0] = ""};
	if (!defined $entries[1]) {$entries[1] = ""};
	if (!defined $entries[2]) {$entries[2] = ""};
	$form_out .= "$entries[0]<input type=\"text\" name=\"answer\" value=\"$entries[1]\" />$entries[2]\n";
	}
$form_out .= "</form>\n";
my $question_text = "<p>".$question_details[3]."</p>".$form_out;
		
# URL strings for forward / back buttons - need to include .pl filename - as this allows us to instead give a javascript popup if not allowed (e.g. prev when question = 1)
my $urlback = "review.pl?question=".($questionnum-1).$urlextra;
if ($questionnum < 2) {$urlback = "javascript:noprevquestions();";}
my $urlnext = "review.pl?question=".($questionnum+1).$urlextra;
if ($questionnum == $numquestions{$quiz}) {$urlnext = "score.pl$urlextra_first";}
my $urlscore = "score.pl$urlextra_first";

# Set image (or not if it's blank)
my $image = "";
if ($question_details[10] ne "") {$image = "<img src=\"$question_details[10]\" alt=\"Question Image\"  class=\"questionimage\">";}

my $qref = $dbtable."_".Quizlib::Misc::to_4_digits($quiznum);

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
	s/\%\%correctanswer\%\%/$question_details[7]/g;
	s/\%\%css\%\%/$css/g;
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%givenanswer\%\%/$user_out/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%image\%\%/$image/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%numquestions\%\%/$numquestions{$quiz}/g;
	s/\%\%questiontext\%\%/$question_text/g;
	s/\%\%question_num\%\%/$questionnum/g;
	s/\%\%quizname\%\%/$quiznames{$quiz}/g;
	s/\%\%ref\%\%/$qref/g;
	s/\%\%status\%\%/$status_out/g;
	s/\%\%urlback\%\%/$urlback/g;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%urlnext\%\%/$urlnext/g;
	s/\%\%urlscore\%\%/$urlscore/g;
	s/\%\%username\%\%/$name/g;
	
	print;
	}
	
close (TEMPLATE);


