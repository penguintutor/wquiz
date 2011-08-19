#!/usr/bin/perl -w
# Displays question to user and asks for answer

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/question.html";
my $page = "question.pl";

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $dbtable, @csscategories, %cssfile, %cssindex, $dbsessiontable, $dbactivetable, $headerfile, $footerfile);
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

# Load question num from form & security check
my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
my $questionnum = Quizlib::Security::chk_num ($page, "question", $given_question);

# Extra parameter allowed - index (if 1 - then upon updating answer will return to index - if not defined or any other number doesn't)
# No need for security check as value ie either 1, or we don't care - we update the variable regardless to stop any risks.
my $index = param ("index");
if (defined $index && $index == 1) {$index = 1;}
else {$index = 0;}

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizsession', -expires=>'+4h');
if (!defined $session || $session eq "") {Quizlib::Errors::cache_error($page, $session);}
# Now get details about this session - which checks the session is valid as well
my @question_info = Quizlib::QuizSession::get_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum);
# $shortsession - contains a 6 digit number (first digits of session), used to prevent browser autoform feature
my $shortsession = substr $session, 1, 6;


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

# Check that status is 1 (not marked)
if ($status != 1) {Quizlib::Errors::sequence_error($page, $quiz, 2, "");}


# Number of questions in the quiz
my $questions_num = $numquestions{$quiz};


# Load the full question - array will have "" for any null entries so OK to print out
my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$quiznum\"");
if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $quiznum", "Array not defined");}

# Create appropriate form
my $form_out = "\n\n<form action=\"answer.pl\">\n<input type=\"hidden\" name=\"style\" value=\"$style\">\n<input type=\"hidden\" name=\"question\" value=\"$questionnum\" />\n<input type=\"hidden\" name=\"index\" value=\"$index\" />\n";
my @entries;
my $i;
# if answer is -1 for not provided we convert it to blank so that we can use it in the tests
if ($answer eq "-1") {$answer = "";}
# different form types
if ($question_details[5] eq "radio")
	{
	@entries = split /,/, $question_details[4];
	for ($i=0; $i < scalar @entries; $i++) 
		{
		if ($answer =~ /$i/)
			{
			$form_out .=	"<input type=\"radio\" name=\"answer\" value=\"$i\" checked /> $entries[$i]<br>\n";
			}
		else
			{
			$form_out .=	"<input type=\"radio\" name=\"answer\" value=\"$i\" /> $entries[$i]<br>\n";
			}
		}
	$form_out .= "\n";
	}
elsif ($question_details[5] eq "checkbox")
	{
	@entries = split /,/, $question_details[4];
	for ($i=0; $i < scalar @entries; $i++) 
		{
		if ($answer =~ /$i/)
			{
			$form_out .=	"<input type=\"checkbox\" name=\"$i\" checked /> $entries[$i]<br>\n";
			}
		else
			{
			$form_out .=	"<input type=\"checkbox\" name=\"$i\" /> $entries[$i]<br>\n";
			}
		}
	$form_out .= "\n";
	}
elsif ($question_details[5] eq "text" || $question_details[5] eq "TEXT" || $question_details[5] eq "number")
	{
	@entries = split /,/, $question_details[4];
	if (!defined $entries[0]) {$entries[0] = ""};
	if (!defined $entries[1]) {$entries[1] = ""};
	if (!defined $entries[2]) {$entries[2] = ""};
	if ($answer ne "") {$entries[1] = $answer;}
	$form_out .= "$entries[0]<input type=\"text\" name=\"answer$shortsession\" value=\"$entries[1]\" />$entries[2]<br />\n";
	}
$form_out .= "<input id=\"answerbutton\" type=\"submit\" value=\"Answer\" />\n</form>\n";

my $question_text = "<p>".$question_details[3]."</p>".$form_out;

# URL strings for forward / back buttons - need to include .pl filename - as this allows us to instead give a javascript popup if not allowed (e.g. prev when question = 1)
my $urlback = "question.pl?question=".($questionnum-1).$urlextra;
if ($questionnum < 2) {$urlback = "javascript:noprevquestions();";}
my $urlnext = "question.pl?question=".($questionnum+1).$urlextra;
if ($questionnum == $numquestions{$quiz}) {$urlnext = "check.pl$urlextra_first";}
my $urlcheck = "check.pl$urlextra_first";

my $image = "";
if ($question_details[10] ne "") {$image = "<img src=\"$question_details[10]\" alt=\"Question Image\" class=\"questionimage\">";}

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
	s/\%\%css\%\%/$css/g;
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%image\%\%/$image/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%numquestions\%\%/$numquestions{$quiz}/g;
	s/\%\%questiontext\%\%/$question_text/g;
	s/\%\%question_num\%\%/$questionnum/g;
	s/\%\%quizname\%\%/$quiznames{$quiz}/g;
	s/\%\%urlback\%\%/$urlback/g;
	s/\%\%urlcheck\%\%/$urlcheck/g;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%urlnext\%\%/$urlnext/g;
	s/\%\%username\%\%/$name/g;
	
	print;
	}
	
close (TEMPLATE);


