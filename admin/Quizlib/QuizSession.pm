# QuizSession.pm - deals with creating and checking session for quiz
# Note this is specifically for the quiz - not the same as used for the admin

package Quizlib::QuizSession;
use DBI;
use Quizlib::Errors;
use Time::Local;

use strict;

my $verbose = 1;


# first array field returns positive on success, 0 on expired an -1 on invalid 
# we don't do cleanup using check_login, instead rely on existing clearup from logout or quiz
sub get_question 
{
# Checks the session exists, and that it hasn't expired
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum) = @_;

# Get overview information
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query = $dbh->prepare("select quizname, status, name from $dbsessiontable where session_id =\"$session\""); 
$query -> execute or Quizlib::Errors::db_error("get_question" ,$dbname, "select quizname, status, name from $dbsessiontable where session_id =\"$session\"", $dbh->errstr);
my ($quizname, $status, $name) = $query->fetchrow_array();

$query->finish;

# If we don't get status then session has gone (possibly expired and deleted)
if (!defined $status || $status eq "") {$dbh->disconnect; return (0);}

# status = 2 is for already marked - so suspect trying to go back and change their answer, rather than using review
#if ($status == 2) {return (1);}
#elsif ($status != 1) {return (2);}
#- Note status has been removed and returned for checking in the appropriate section

# If we reach here it appears to be a valid session - now check for the question number
$query = $dbh->prepare("select question, answer from $dbactivetable where session_id=\"$session\" and qnum=\"$questionnum\"");
$query -> execute or Quizlib::Errors::db_error("get_question $quizname" ,$dbname, "select question, answer from $dbactivetable where session_id=\"$session\" and qnum=\"$questionnum\"", $dbh->errstr);
my ($question, $answer) = $query->fetchrow_array();
$query->finish;
$dbh->disconnect;

if (!defined $question || !defined $answer) {Quizlib::Errors::misc_error ("get_question", $quizname, "No question details qnum = $questionnum");}

return ($status, $quizname, $name, $question, $answer);
}



# returns 1 on success, 0 on expired an -1 on invalid (ie not a admin session)
# we don't do cleanup using check_login, instead rely on existing clearup from logout or quiz
sub get_answers 
{
# Checks the session exists, and that it hasn't expired
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable) = @_;

# Get overview information
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query = $dbh->prepare("select quizname, status, name from $dbsessiontable where session_id =\"$session\""); 
$query -> execute or Quizlib::Errors::db_error("get_question" ,$dbname, "select quizname, status, name from $dbsessiontable where session_id =\"$session\"", $dbh->errstr);
my ($quizname, $status, $name) = $query->fetchrow_array();

$query->finish;

# If we don't get status then session has gone (possibly expired and deleted)
if (!defined $status || $status eq "") {$dbh->disconnect; return (0);}

# status = 2 is for already marked - so suspect trying to go back and change their answer, rather than using review
#if ($status == 2) {return (1);}
#elsif ($status != 1) {return (2);}
# Instead return as first entry in the array

# If we reach here it appears to be a valid session - now read in the answers (and qnum, so we can check no problems)
$query = $dbh->prepare("select qnum, answer from $dbactivetable where session_id=\"$session\" order by qnum");
$query -> execute or Quizlib::Errors::db_error("get_answers $quizname" ,$dbname, "select qnum, answer from $dbactivetable where session_id=\"$session\" order by qnum", $dbh->errstr);

my @all_answers;
my ($question, $answer);
my $i = 0;
while (($question, $answer) = $query->fetchrow_array())
{
	# Check we have an answer (which ensures we also have a question)
	if (!defined $answer) {Quizlib::Errors::misc_error("get_answers", $quizname, "question missing - expecting ".($i+1).", no answer supplied");}
	# Check that qnum matches number we expect
	if ($question != ($i+1)) {Quizlib::Errors::misc_error("get_answers", $quizname, "question missing - expecting ".($i+1).", got $question");}
	$all_answers[$i] = $answer;
	$i++;
}

$query->finish;
$dbh->disconnect;

return ($status, $quizname, $name, @all_answers);
}


# returns postitive number on success - relates to status, 0 on expired an -1 on invalid 
sub get_questions {
# Checks the session exists, and that it hasn't expired
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable) = @_;

# Get overview information
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query = $dbh->prepare("select quizname, status, name from $dbsessiontable where session_id =\"$session\""); 
$query -> execute or Quizlib::Errors::db_error("get_question" ,$dbname, "select quizname, status, name from $dbsessiontable where session_id =\"$session\"", $dbh->errstr);
my ($quizname, $status, $name) = $query->fetchrow_array();

$query->finish;

# If we don't get status then session has gone (possibly expired and deleted)
if (!defined $status || $status eq "") {$dbh->disconnect; return (0);}

# status = 2 is for already marked - so suspect trying to go back and change their answer, rather than using review
#if ($status == 2) {return (1);}
#elsif ($status != 1) {return (2);}
# Return as first entry in the array

# If we reach here it appears to be a valid session - now read in the answers (and qnum, so we can check no problems)
$query = $dbh->prepare("select qnum, question from $dbactivetable where session_id=\"$session\" order by qnum");
$query -> execute or Quizlib::Errors::db_error("get_questions $quizname" ,$dbname, "select qnum, question from $dbactivetable where session_id=\"$session\" order by qnum", $dbh->errstr);

my @all_questions;
my ($question, $this_question);
my $i = 0;
while (($question, $this_question) = $query->fetchrow_array())
{
	# Check we have an answer (which ensures we also have a question)
	if (!defined $this_question) {Quizlib::Errors::misc_error("get_questions", $quizname, "question missing - expecting ".($i+1).", no question supplied");}
	# Check that qnum matches number we expect
	if ($question != ($i+1)) {Quizlib::Errors::misc_error("get_answers", $quizname, "question missing - expecting ".($i+1).", got $question");}
	$all_questions[$i] = $this_question;
	$i++;
}

$query->finish;
$dbh->disconnect;

return ($status, $quizname, $name, @all_questions);

}





# note $status - should be 1 for online quiz (ie. ready to use)
# or 5 for offline quiz
sub newquiz
{
my ($session, $quizname, $status, $username, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, @questions) = @_;	

# First create a session entry (does not hold the questions)
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($quizname, $dbname, "Connect", "- Failed");
# Get current time (gmtime) in iso yyyy-mm-dd hh:mm:ss
my @timearray = gmtime (time);
my $currenttime = ($timearray[5]+1900)."-".($timearray[4]+1)."-".$timearray[3]." ".$timearray[2].":".$timearray[1].":".$timearray[0]; 
# Note 1 means the quiz is ready to use
my $query = $dbh->prepare("insert into $dbsessiontable values (\"$session\", \"$currenttime\", \"$quizname\", \"$status\", \"$username\")"); 
$query -> execute or Quizlib::Errors::db_error($quizname ,$dbname, "insert into $dbsessiontable values (\"$session\", \"$currenttime\", \"$quizname\", \"$status\", \"$username\")", $dbh->errstr);
$query->finish;

# Now setup a table entry for each question
# Start DB entries from 1 as that is what number will be passed on the url
my $i;
for ($i=0; $i < scalar @questions; $i++)
{
	# All answers should set to -1, to signify not answered
	$query = $dbh->prepare("insert into $dbactivetable values (\"$session\", \"".($i+1)."\", \"$questions[$i]\", \"-1\")");
	$query -> execute or Quizlib::Errors::db_error($quizname ,$dbname, "insert into $dbactivetable values (\"$session\", \"".($i+1)."\", \"$questions[$i]\", \"-1\")", $dbh->errstr);
	$query->finish;
}

$dbh->disconnect;
}



# Change the status of the session (e.g. to set quiz to marked = 2)
# activetable not needed but kept to keep formatting the same
sub set_status {
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $status) = @_;

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");

my $query = $dbh->prepare("update $dbsessiontable set status=\"$status\" where session_id=\"$session\"");
$query -> execute or Quizlib::Errors::db_error("QuizSession.pm" ,$dbname, "update $dbsessiontable set status=\"$status\" where session_id=\"$session\"", $dbh->errstr);
$query->finish;

$dbh->disconnect;

}






# shouldn't fail unless major problem with DB access, so doesn't 
# return an error, instead just call the Error script
sub save_question 
{
# Session table is not needed, but keeps the format similar to get_question
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $questionnum, $answer) = @_;

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");

my $query = $dbh->prepare("update $dbactivetable set answer=\"$answer\" where session_id=\"$session\" and qnum=\"$questionnum\"");
$query -> execute or Quizlib::Errors::db_error("QuizSession.pm" ,$dbname, "update $dbactivetable set answer=\"$answer\" where session_id=\"$session\" and qnum=\"$questionnum\"", $dbh->errstr);
$query->finish;

$dbh->disconnect;

}



# Remove expired entries
# This is called once per quiz, but does not run every time, instead it only checks 
# every $sessionsbeforecleanup times.
# It is not used to cleanup the current session (although it may if the session has been around a really long time)
# It is designed to remove other sessions that have now expired
sub remove_expired 
{
my ($dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $dbsettingstable, $sessionexpiry, $sessionsbeforecleanup) = @_;

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query;
my $currentvalue = 0;

# If sessionsbeforecleanup is 0 then we run the remove_expired regardless (admin logout)
if ($sessionsbeforecleanup != 0)
{
	# Read in sessionendcount
	$query = $dbh->prepare("select settingvalue from $dbsettingstable where settingkey=\"sessionendcount\""); 
	$query -> execute or Quizlib::Errors::db_error("remove_expired" ,$dbname, "select settingvalue from $dbsettingstable where settingkey=\"sessionendcount\"", $dbh->errstr);
	$currentvalue = $query->fetchrow_array();
	$query->finish;
}
# If we don't have a currentvalue set then we create with 0
if (!defined $currentvalue) 
	{
		$currentvalue = 0;
		$query = $dbh->prepare("insert into $dbsettingstable values (\"sessionendcount\", \"$currentvalue\")");
		# If unsuccessful then we will log, but continue 
		$query -> execute or Quizlib::error_email ("QuizSession.pm", "Set sessionscount", 5, 1);
		$query->finish;
		$dbh->disconnect;
	}
# If not time to run then we increment save and exit
elsif ($sessionsbeforecleanup != 0 && $currentvalue <= $sessionsbeforecleanup)
	{
	$currentvalue ++;
	$query = $dbh->prepare("update $dbsettingstable set settingvalue=\"$currentvalue\" where settingkey=\"sessionendcount\"");
	# If unsuccessful then we will log, but continue 
	$query -> execute or Quizlib::error_email ("QuizSession.pm", "Increment sessionscount", 5, 1);
	$query->finish;
	$dbh->disconnect;
	return;
	}

# Lock the table - or if we can't get a lock assume already running and return
$dbh->do("lock tables $dbsettingstable write");

# Read in the value again - to make sure a run hasn't started since we locked the table
# We always run for a sessionsbeforecleanup = 0 (don't have many admin logouts)
if ($sessionsbeforecleanup != 0)
{
	$query = $dbh->prepare("select settingvalue from $dbsettingstable where settingkey=\"sessionendcount\""); 
	$query -> execute or Quizlib::Errors::db_error("remove_expired" ,$dbname, "select settingvalue from $dbsettingstable where settingkey=\"sessionendcount\"", $dbh->errstr);
	$currentvalue = $query->fetchrow_array();
	$query->finish;
}

# If it's been updated since we first looked, then it will have been run by another process
if ($sessionsbeforecleanup != 0 && $currentvalue <= $sessionsbeforecleanup) {$dbh->do("unlock tables"); return;}

# We have successfully locked the table, so write back a 0
$query = $dbh->prepare("update $dbsettingstable set settingvalue=\"0\" where settingkey=\"sessionendcount\"");
$query -> execute or Quizlib::Errors::db_error("remove_expired" ,$dbname, "update $dbsettingstable set settingvalue=\"0\" where settingkey=\"sessionendcount\"", $dbh->errstr);
$query->finish;
# Unlock the table
$dbh->do("unlock tables");

# Now we should be the only thread attempting to perform a cleanup so we are free to procede
# admin/logout.pl will have bypassed the checks, but the chance of happening at same time as a run is negligable
# If it did then it would only give an error to an administrator, not impact the users of the system
$query = $dbh->prepare("SELECT session_id, startsession FROM $dbsessiontable");
$query -> execute or Quizlib::Errors::db_error("QuizSession.pm" ,$dbname, "SELECT session_id, startsession FROM $dbsessiontable",$dbh->errstr);
my @expiredsessions;
my @this_entry;
my ($this_date, $this_time);
my (@this_date_elements, @this_time_elements);
my @debugentries;
my $currenttime = time;
while (@this_entry = $query->fetchrow_array())
	{
	($this_date, $this_time) = split / /, $this_entry[1];
	@this_date_elements = split /-/, $this_date;
	@this_time_elements = split /:/, $this_time;
	$this_time = timegm($this_time_elements[2], $this_time_elements[1], $this_time_elements[0], $this_date_elements[2], $this_date_elements[1]-1, $this_date_elements[0]);
	#push @debugentries, "\n## This $this_time :: ".($currenttime - $sessionexpiry)." :: $currenttime :: $sessionexpiry ##";
	if ($this_time < ($currenttime - $sessionexpiry)) {push @expiredsessions, $this_entry[0];}
	}
$query->finish;
# Delete the expired sessions
my $i;
for ($i=0; $i < scalar @expiredsessions; $i++)
	{
	# First delete from active session db
	$query = $dbh->prepare("delete from $dbactivetable where session_id=\"$expiredsessions[$i]\"");
	# Log / (maybe email) without breaking the session
	$query->execute or Quizlib::error_email ("QuizSession.pm", "Delete from dbactivetable ".$dbh->errstr, 5, 1);
	$query->finish;
	$query = $dbh->prepare("delete from $dbsessiontable where session_id=\"$expiredsessions[$i]\"");
	# No checking - could add a log/email only option, but don't break the user session
	$query->execute or Quizlib::error_email ("QuizSession.pm", "Delete from dbsessiontable".$dbh->errstr, 5, 1);
	$query->finish;
	}
}
