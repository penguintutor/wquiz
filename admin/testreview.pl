#!/usr/bin/perl -w

# Allows user to review answer - see what their answer was and what it should have been
# Note we don't do security checking here - this is done by answer before it is stored into the cache

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;

use strict;

my $template = "../templates/review.html";
my $page = "review.pl";

my $i;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbtable, $dbuser, $dbpass, $answercols, @csscategories, %cssfile, %cssindex, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";

# Load session & question num from form & security check
#my $given_session = param ("session");
#if (!defined $given_session || $given_session eq "") {Quizlib::Security::missing_parm_error ($page, "session");}
#my $session_key = Quizlib::Security::chk_alpnum ($page, "session", $given_session);

my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
#my $questionnum = Quizlib::Security::chk_num ($page, "question", $given_question);
my $questionid = Quizlib::Security::chk_num ($page, "question", $given_question);

my $quiz = "test"; 
my $name = "testing"; 

# Get current question pkey from cache - question starts at 1, but cache at 0
#my $this_question = $status1->[$questionnum-1];
#my $answer = $status2->[$questionnum-1]; 

# Load the question - array will have "" for any null entries so OK to print out
my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$questionid\"");
if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from questions where question = $questionid", "Array not defined");}


# Based upon type of question load appropriate parm
my $answer = "";
my $given_answer;
if ($question_details[5] eq "radio")
	{
	# Don't compare against list of possible - if it's not in range 0-9 reject, if it is in that range, but not a valid answer then will mark as wrong.
	$given_answer = param ("answer");
	# If no answer given - leave as not answered and go to next
	if (!defined $given_answer || $given_answer eq "") {$answer = -1;}
	else
		{
		$answer = Quizlib::Security::chk_num_range ($page, "radio value", $given_answer, 0, 9);
		}
	}
elsif ($question_details[5] eq "checkbox")
	{
	# check all 10 possibles
	for ($i =0; $i < 10; $i++)
		{
		# Don't security check as we only look for one possible (on), all others are ignored
		$given_answer = param ("$i");
		if (!defined $given_answer || $given_answer eq "") {next;}
		elsif ($given_answer eq "on") {$answer .= $i;}
		}
	}
# For text (allow aphanum)
elsif ($question_details[5] eq "text" || $question_details[5] eq "TEXT")
	{
	$given_answer = param ("answer");
	if (!defined $given_answer || $given_answer eq "") { $answer = -1; }
	$answer = Quizlib::Security::chk_alpnum ($page, "text entry", $given_answer);
	}
# For number take first set of digits
elsif ($question_details[5] eq "number")
	{
	$given_answer = param ("answer");
	if (!defined $given_answer || $given_answer eq "") { $answer = -1; }
	# First check it's alpha num to stop anyone putting anything nasty in
	$answer = Quizlib::Security::chk_alpnum ($page, "text entry", $given_answer);
	$answer =~ /(\d+)/;
	# If no digits found then return not answered (same as if they hadn't tried)
	if (!defined $1) {$answer = -1;}
	else {$answer = $1;}
	}

# Final check to put to not answered if appropriate
if (!defined $answer || $answer eq "") {$answer = -1;}

# 0 = info
#my $cache0 = get_cache_handle();
#my $status0 = $cache0->get("0".$session_key);
#unless ($status0 and ref $status0 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "0".$session_key); }

# 1 = questions
#my $cache1 = get_cache_handle();
#my $status1 = $cache1->get("1".$session_key);
#unless ($status1 and ref $status1 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "1".$session_key); }

# 2 = answers
#my $cache2 = get_cache_handle();
#my $status2 = $cache2->get("2".$session_key);
#unless ($status2 and ref $status2 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "2".$session_key); }


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
	chomp $answer;
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
	chomp $answer;
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
$form_out .= "<input type=\"hidden\" name=\"style\" value=\"$style\">\n";
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
#my $urlback = "review.pl?session=".$session_key."&amp;question=".($questionnum-1).$urlextra;
#if ($questionnum < 2) {$urlback = "javascript:noprevquestions();";}
#my $urlnext = "review.pl?session=".$session_key."&amp;question=".($questionnum+1).$urlextra;
#if ($questionnum == $numquestions{$quiz}) {$urlnext = "score.pl?session=$session_key$urlextra";}
#my $urlscore = "score.pl?session=$session_key$urlextra";
my $urlback = "";
my $urlnext = "";
my $urlscore = "";

my $image = "<img src=\"$question_details[10]\" alt=\"Question Image\"  class=\"questionimage\">";

my $qref = $dbtable."_".Quizlib::Misc::to_4_digits($questionid);

# Get headers and footers from external file
my $headertext = "";
if (defined $headerfile && $headerfile ne "")
{
	$headertext = Quizlib::Misc::readhtml_noerror($headerfile);
}
my $footertext = "";
if (defined $footerfile && $footerfile ne "")
{
	$footertext = Quizlib::Misc::readhtml_noerror($footerfile);
}

open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/\%\%quizname\%\%/testquiz/g;
	s/\%\%username\%\%/test/g;
	s/\%\%numquestions\%\%/1/g;
	s/\%\%question_num\%\%/1/g;
	s/\%\%questiontext\%\%/$question_text/g;
	s/\%\%image\%\%/$image/g;
	s/\%\%status\%\%/$status_out/g;
	s/\%\%givenanswer\%\%/$user_out/g;
	s/\%\%correctanswer\%\%/$question_details[7]/g;
	s/\%\%urlback\%\%/$urlback/g;
	s/\%\%urlnext\%\%/$urlnext/g;
	s/\%\%urlscore\%\%/$urlscore/g;
	s/\%\%ref\%\%/$qref/g;
	s/\%\%css\%\%/$css/g;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%footer\%\%/$footertext/g;
	print;
	}
	
close (TEMPLATE);


sub get_cache_handle
{
require Cache::FileCache;
Cache::FileCache->new
	({
    namespace => 'quiz_session_1',
    username => 'nobody',
    default_expires_in => '4 hours',
    auto_purge_interval => '6 hours',
    });
}

