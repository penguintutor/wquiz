#!/usr/bin/perl -w
# Displays question to user and asks for answer

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;

use strict;

my $template = "../templates/question.html";
my $page = "testquestion.pl";

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $dbtable, @csscategories, %cssfile, %cssindex, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";

## Load session & question num from form & security check
#my $given_session = param ("session");
#if (!defined $given_session || $given_session eq "") {Quizlib::Security::missing_parm_error ($page, "session");}
#my $session_key = Quizlib::Security::chk_alpnum ($page, "session", $given_session);

#my $given_question = param ("question");
#if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
#my $questionnum = Quizlib::Security::chk_num ($page, "question", $given_question);

# In test mode - question is the actual question number, not the relative number
# So use question id instead of questionnum
my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
my $questionid = Quizlib::Security::chk_num ($page, "question", $given_question);


## Extra parameter allowed - index (if 1 - then upon updating answer will return to index - if not defined or any other number doesn't)
## No need for security check as value ie either 1, or we don't care - we update the variable regardless to stop any risks.
#my $index = param ("index");
#if (defined $index && $index == 1) {$index = 1;}
#else {$index = 0;}


## Open up the cache
## 0 = info
#my $cache0 = get_cache_handle();
#my $status0 = $cache0->get("0".$session_key);
#unless ($status0 and ref $status0 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "0".$session_key); }

## 1 = questions
#my $cache1 = get_cache_handle();
#my $status1 = $cache1->get("1".$session_key);
#unless ($status1 and ref $status1 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "1".$session_key); }

## 2 = answers
#my $cache2 = get_cache_handle();
#my $status2 = $cache2->get("2".$session_key);
#unless ($status2 and ref $status2 eq "ARRAY") { Quizlib::Errors::cache_error ($page, "2".$session_key); }

#my $quiz = $status0->[0];
#my $name = $status0->[1];
my $quiz = "test";
my $name = "testing";

## Number of questions in the quiz
#my $questions_num = $numquestions{$quiz};

## Check that the user hasn't tried to give us an out of range number
#if ($questionnum < 0 || $questionnum > $questions_num) { Quizlib::Security::exit_parm_error($page, "question", $questionnum); }

## Get current question pkey from cache - question starts at 1, but cache at 0
#my $this_question = $status1->[$questionnum-1];
#my $answer = $status2->[$questionnum-1]; 

# Load the question - array will have "" for any null entries so OK to print out
my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$questionid\"");
if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $questionid", "Array not defined");}

# Create appropriate form
my $form_out = "\n\n<form action=\"testreview.pl\">\n<input type=\"hidden\" name=\"style\" value=\"$style\">\n<input type=\"hidden\" name=\"question\" value=\"$questionid\" />\n\n";
my @entries;
my $i;
# if answer is -1 for not provided we convert it to blank so that we can use it in the tests
my $answer = "";
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
	$form_out .= "$entries[0]<input type=\"text\" name=\"answer\" value=\"$entries[1]\" />$entries[2]<br />\n";
	}
$form_out .= "<input type=\"submit\" value=\"Answer\" />\n</form>\n";

my $question_text = "<p>".$question_details[3]."</p>".$form_out;

# URL strings for forward / back buttons - need to include .pl filename - as this allows us to instead give a javascript popup if not allowed (e.g. prev when question = 1)
#my $urlback = "question.pl?session=".$session_key."&amp;question=".($questionnum-1).$urlextra;
#if ($questionnum < 2) {$urlback = "javascript:noprevquestions();";}
#my $urlnext = "question.pl?session=".$session_key."&amp;question=".($questionnum+1).$urlextra;
#if ($questionnum == $numquestions{$quiz}) {$urlnext = "check.pl?session=$session_key$urlextra";}
#my $urlcheck = "check.pl?session=$session_key$urlextra";
my $urlback = "";
my $urlnext = "";
my $urlcheck = "";

my $image = "<img src=\"$question_details[10]\" alt=\"Question Image\" class=\"questionimage\">";

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
	s/\%\%username\%\%/$name/g;
	s/\%\%question_num\%\%/1/g;
	s/\%\%numquestions\%\%/1/g;
	s/\%\%questiontext\%\%/$question_text/g;
	s/\%\%image\%\%/$image/g;
	s/\%\%urlback\%\%/$urlback/g;
	s/\%\%urlnext\%\%/$urlnext/g;
	s/\%\%urlcheck\%\%/$urlcheck/g;
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

