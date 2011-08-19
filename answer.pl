#!/usr/bin/perl -w
# Updates cache with answer then redirects

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;
use Quizlib::QuizSession;

use strict;

my $template = "templates/answer.html";
my $page = "answer.pl";

my $i;

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbuser, $dbpass, $dbtable, @csscategories, %cssfile, $dbsessiontable, $dbactivetable);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

# Load question num from form & security check
my $given_question = param ("question");
if (!defined $given_question || $given_question eq "") {Quizlib::Security::missing_parm_error ($page, "question");}
my $questionnum = Quizlib::Security::chk_num ($page, "question", $given_question);

# Load cookie with session number
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizsession', -expires=>'+4h');
if (!defined $session || $session eq "") {cache_error($page, $session);}
# Now get summary details about this question - also check the session is valid
# Don't make any updates at this stage
my @question_info = Quizlib::QuizSession::get_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum);
# We will load details about the specific question later
# $shortsession - contains a 6 digit number (first digits of session), used to prevent browser autoform feature
my $shortsession = substr $session, 1, 6;

# Extra parameter allowed - index (if 1 - then upon updating answer will return to index - if not defined or any other number doesn't)
# No need for security check as value ie either 1, or we don't care - we update the variable regardless to stop any risks.
my $index = param ("index");
if (defined $index && $index == 1) {$index = 1;}
else {$index = 0;}

# We leave loading the actual answer until we know what we should be looking for (e.g. text / number / checkbox / radio)
# text / number (answer=string); checkbox (1=on, 3=on etc.); radio (answer=2)

my $status = $question_info[0];
my $quiz = $question_info[1]; 

# Check that status is 1 (not marked)
if ($status != 1) {Quizlib::Errors::sequence_error($page, $quiz, 2, "");}

# Number of questions in the quiz
my $questions_num = $numquestions{$quiz};
# Check that the user hasn't tried to give us an out of range number
if ($questionnum < 0 || $questionnum > $questions_num) { Quizlib::Security::exit_parm_error($page, "question", $questionnum); }


# Get current question number (as stored in db) from cache
my $this_question = $question_info[3];

# Load the question - array will have "" for any null entries so OK to print out
my @question_details = Quizlib::Qdb::db_get_entry ($dbname, $dbuser, $dbpass, $page, $dbtable, "where question = \"$this_question\"");
if (! @question_details) { Quizlib::Errors::db_error($page, $dbname, "select * from questions where question = $this_question", "Array not defined");}

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
	$given_answer = param ("answer$shortsession");
	if (!defined $given_answer || $given_answer eq "") { $answer = -1; }
	$answer = Quizlib::Security::chk_alpnum ($page, "text entry", $given_answer);
	# If includes a \ then replace with \\ (so it doesn't break the sql)
	$answer =~ s/\\/\\\\/g;
	# Trim any \n etc.
	chomp $answer;
	# Trim any \s at the end
	$answer =~ s/\s*$//;
	}
# For number take first set of digits
elsif ($question_details[5] eq "number")
	{
	$given_answer = param ("answer$shortsession");
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

# Now we can save the answers back into the DB - don't check for failure as QuizSession handles that and won't return
Quizlib::QuizSession::save_question ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum, $answer);


# now just redirect - depending upon value of index if we return to check or question
if ($index == 1 || $questionnum >= $questions_num) {print redirect ("check.pl?style=$style");}
else { print redirect("question.pl?question=".($questionnum+1)."&style=$style");}

