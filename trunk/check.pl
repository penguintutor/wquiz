#!/usr/bin/perl -w
# Allows user to check their answers, and shows any unanswered q's

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/check.html";
my $page = "check.pl";

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $answercols, @csscategories, %cssfile, %cssindex, $dbsessiontable, $dbactivetable, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizsession', -expires=>'+4h');
if (!defined $session || $session eq "") {cache_error($page, $session);}
# Now get summary details about this question - also check the session is valid
# And get a list of all answers
my ($status, $quiz, $name, @all_answers) = Quizlib::QuizSession::get_answers ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable);

# Check that @all_answers contains the correct number of answers
# !@all_answers (like !defined @all_answers - which is depreciated)
if (!@all_answers || scalar @all_answers < $numquestions{$quiz}) {Quizlib::Errors::misc_error($page, $quiz, "insufficient answers given, expected $numquestions{$quiz}, got ".scalar @all_answers);}

# Check that status is 1 (not marked)
if ($status != 1) {Quizlib::Errors::sequence_error($page, $quiz, 2, "");}

# From all values of answer - create a table of whether answered or not
# Put the results into column entries - then use css to lay these out
my @column;
my $colnum = 0;
my $i;
my $numtd = 0;
my $num_not_answered = 0;
for ($i = 0; $i < $numquestions{$quiz}; $i++)
	{
	
	# If not answered
	if ($all_answers[$i] eq -1)
		{
		$column[$colnum] .= "<a href=\"question.pl?question=".($i+1)."&amp;index=1&amp;style=$style\"><font class=\"number\">".($i+1).". </font> <font class=\"notanswered\">Not Answered</font></a><br />\n";
		$num_not_answered ++;
		}
	else
		{
		$column[$colnum] .= "<a href=\"question.pl?question=".($i+1)."&amp;index=1&amp;style=$style\"><font class=\"number\">".($i+1).". </font> <font class=\"answered\">Answered</font></a><br />\n";
		}
	$colnum ++;
	if ($colnum >= $answercols) {$colnum = 0;}
	}
my $view_out = "";
for ($i=0; $i < scalar @column; $i++)
	{
	$view_out .= "<span class=\"checkcolumn\">".$column[$i]."</span>\n\n";
	}

$view_out .= "<br class=\"clearboth\" />\n\n";

if ($num_not_answered > 0) 
	{
	$view_out .= "<span class=\"notanswered\">Not all questions answered: Only ".($numquestions{$quiz}-$num_not_answered)." out of $numquestions{$quiz} have been answered</span>\n";
	}

	my $urlstring;
	if ($urlextra eq "") {$urlstring = "";}
	else {$urlstring = "?".$urlextra;}

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
	s/\%\%checks\%\%/$view_out/g;
	s/\%\%css\%\%/$css/g;
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%quizname\%\%/$quiznames{$quiz}/g;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%urlstring\%\%/$urlstring/g;
	s/\%\%username\%\%/$name/g;
	
	print;
	}
	
close (TEMPLATE);


