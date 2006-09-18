#!/usr/bin/perl -w

# Score answers
# Note we don't do security checking here - this is done by answer before it is stored into the cache

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/score.html";
my $page = "score.pl";

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbtable, $dbuser, $dbpass, $answercols, @csscategories, %cssfile, %cssindex, $dbsessiontable, $dbactivetable, $headerfile, $footerfile, $dbsettingsnumtable, $sessionsbeforecleanup, $sessionexpiry);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";
my $urlstring = "&amp;style=$style";


# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizsession', -expires=>'+4h');
if (!defined $session || $session eq "") {Quizlib::Errors::cache_error($page, "No Session Info");}
# Now get summary details about this question - also check the session is valid
# And get a list of all answers - we then compare with the questions individually using Qdb
my ($status, $quiz, $name, @all_answers, @all_questions);
($status, $quiz, $name, @all_answers) = Quizlib::QuizSession::get_answers ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable);
# We load the same $quiz and $name, because the get_questions and get_answers both return a similar format
($status, $quiz, $name, @all_questions) = Quizlib::QuizSession::get_questions ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable);

# Do not check status, as we need to review answers even after it has been marked
# Rely on answer.pl, review.pl and question.pl to ensure that it can't be changed
# Check that status is 1 (not marked)
#if ($status != 1) {Quizlib::Errors::sequence_error($page, $quiz, 2, "");}


# If not marked then we should run remove_expired to cleanup old sessions
if ($status == 1) 
{
	# If not defined $sessionsbeforecleanup, set to default of 100
	if (!defined $sessionsbeforecleanup || $sessionsbeforecleanup < 1) {$sessionsbeforecleanup = 100;}
	# If expiry not set, use default of 4 hours
	if (!defined $sessionexpiry || $sessionexpiry < 60) {$sessionexpiry=14400;}
	Quizlib::QuizSession::remove_expired($dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $dbsettingsnumtable, $sessionexpiry, $sessionsbeforecleanup);
}


# Check that @all_answers contains the correct number of answers
# !@all_answers (like !defined @all_answers - which is depreciated)
if (!@all_questions || scalar @all_questions < $numquestions{$quiz}) {Quizlib::Errors::misc_error($page, $quiz, "insufficient answers given, expected $numquestions{$quiz}, got ".scalar @all_questions);}
if (!@all_answers || scalar @all_answers < $numquestions{$quiz}) {Quizlib::Errors::misc_error($page, $quiz, "insufficient answers given, expected $numquestions{$quiz}, got ".scalar @all_answers);}



# From all values of answer - create a table of results - at same time total up correct answers
my @column;
my $colnum = 0;
my $i;
my $numtd = 0;
my $num_correct = 0;
my $this_answer; #(if this answer is right or not) 0 = no answer -1 = wrong -2 = right

for ($i = 0; $i < $numquestions{$quiz}; $i++)
	{
	# Load from DB even if not answered as shouldn't be many of these
	my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$all_questions[$i]\"");
	if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from $dbtable where question = $all_questions[$i]", "Array not defined");}
	# if not answered - wrong
	if ($all_answers[$i] eq -1) { $this_answer = 0; }
	# if radio
	elsif ($question_details[5] eq "radio" || $question_details[5] eq "checkbox")
		{
		if ($all_answers[$i] == $question_details[6]) 
			{
			$this_answer = 1;
			$num_correct ++;
			}
		else {$this_answer = -1;}
		}
	elsif ($question_details[5] eq "number")
		{
		my ($min, $max) = split /,/, $question_details[6];
		if ($all_answers[$i] >= $min && $all_answers[$i] <= $max) 
			{
			$this_answer = 1;
			$num_correct ++;
			}
		else {$this_answer = -1;}
		}
	elsif ($question_details[5] eq "text")
		{
		if ($all_answers[$i] =~ /$question_details[6]/i)
			{
			$this_answer = 1;
			$num_correct ++;
			}
		else {$this_answer = -1;}
		}
	else
		{
		Quizlib::Errors::question_corrupt ($page, $dbname, $question_details[0], "invalid question type")
		}
		
	# Now we have marked question - update table
	if ($this_answer == 0)
		{
		$column[$colnum] .= "<a href=\"review.pl?question=".($i+1)."&amp;index=1&amp;style=$style\"><font class=\"number\">".($i+1).". </font> <font class=\"wrongnotanswered\">Not Answered</font></a><br />\n";
		}
	elsif ($this_answer == -1)
		{
		$column[$colnum] .= "<a href=\"review.pl?question=".($i+1)."&amp;index=1&amp;style=$style\"><font class=\"number\">".($i+1).". </font> <font class=\"wrong\">Incorrect</font></a><br />\n";
		}
	else
		{
		$column[$colnum] .= "<a href=\"review.pl?question=".($i+1)."&amp;index=1&amp;style=$style\"><font class=\"number\">".($i+1).". </font> <font class=\"correct\">Correct</font></a><br />\n";
		}
	$colnum ++;
	if ($colnum >= $answercols) {$colnum = 0;}
	}
my $view_out = "";
for ($i=0; $i < scalar @column; $i++)
	{
	$view_out .= "<span class=\"checkcolumn\">".$column[$i]."</span>\n\n";
	}

#$view_out .= "<br class=\"clearboth\" />\n\n";
$view_out .= "\n\n";

my $percent = int ($num_correct / $numquestions{$quiz} * 100);

# Set the status to marked
Quizlib::QuizSession::set_status($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, 2);


# Log score
Quizlib::Misc::log_score ($quiz, $session, $num_correct, $numquestions{$quiz});

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
	s/\%\%quizname\%\%/$quiznames{$quiz}/g;
	s/\%\%username\%\%/$name/g;
	s/\%\%score\%\%/$num_correct/g;
	s/\%\%numquestions\%\%/$numquestions{$quiz}/g;
	s/\%\%percentage\%\%/$percent/g;
	s/\%\%answers\%\%/$view_out/g;
	s/\%\%css\%\%/$css/g;
	s/\%\%urlstring\%\%/$urlstring/g;
	s/\%\%urlextra\%\%/$urlextra/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%footer\%\%/$footertext/g;
	print;
	}
	
close (TEMPLATE);


