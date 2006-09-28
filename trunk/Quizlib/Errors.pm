# Some generic error message handling

package Quizlib::Errors;

use CGI qw(:standard);
use Net::SMTP;

use strict;

our $cfgfile = "../quiz.cfg";

my $verbose = 1;



# Note: missing__error & invalid_ are in Quizlib::Security not Errors

our @severity_word = ("", "Critical Error", "Error", "Error", "Warning", "Warning", "Debug");



# Display error and exits - for insufficient questions
sub num_questions
{
my ($page, $quizname, $required) = @_;

check_ignore_error();

# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An internal error has occured.<br>\n";
print "Insufficient questions for that category<p>\n";
print "The administrator has been notified of this error<p>\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "Insufficient Questions for quiz $quizname, num required $required", 1, 0);
}






sub cache_error
{
my ($page, $session) = @_;

check_ignore_error();
	
# Error message
print header();
print start_html("Session Timed out");

print "<h1>Timeout</h1>\n";
print "Session timeout.<br>\n";
print "Whilst no time limit is given, the session may timeout if left for a long period of time\n";
print "<p>&nbsp;</p>\n";
print "<p><a href=\"index.pl\">Restart Quiz</a></p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "cache error ".$session, 4, 0);
}

sub offline_counter_error
{
my ($filename) = @_;

check_ignore_error();
	
# Error message
print header();
print start_html("Internal Error");

print "<h1>Internal Error</h1>\n";
print "Sorry, but an internal error has occured<br>\n";
print "The webmaster has been notified of this error.\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ("Offline", "counter_error ".$filename, 4, 0);
}


# Display error and exits - for a corrupt config file
sub config_error
{
my ($page, $error) = @_;
	
check_ignore_error();
	
# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An internal error has occured.<br>\n";
print "quiz.cfg cannot be loaded, or is corrupt<p>\n";
print "Sorry for any inconvenience<p>\n";
print "<p>&nbsp;</p>\n";
print "<a href=\"index.pl\">Return to start page</a></p>";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "config error".$error, 1, 0);
}


# If we don't allow offline quiz
sub offline_not_allowed
{
#my ($page, $quizname, $required) = @_;

# don't ignore error as robots should not try when disabled
#check_ignore_error();

# Error message
print header();
print start_html("Error - Offline Mode Not Supported");

print "<h1>Offline Mode Not Supported</h1>\n";
print "The Offline mode is not supported for this quiz.<br>\n";
print "<p>&nbsp;</p>\n";
print "<p><a href=\"index.pl\">Return to the index page</a></p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ("Offline", "Offline mode not supported", 4, 0);
}

# Display error and exits
sub fileopen_error
{
my ($page, $file, $mode) = @_;

check_ignore_error();

# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An internal error has occured.<br>\n";
print "$file cannot be opened for $mode<p>\n";
print "Called within the $page page<p>\n";
print "Sorry for any inconvenience<p>\n";
print "The Administrator has been notified of this error<p>\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

error_email ($page, "File Open Error $file $mode session terminated", 1, 0);
}



# Problems communicating with DB
sub db_error
{
my ($page, $db, $select, $error) = @_;

check_ignore_error();

# Note select could be command if not a select
	
# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An internal error has occured.<br>\n";
print "Problem communicating with the DB.\n";
print "Sorry for any inconvenience<p>\n";
print "The administrator has been notified of this error<p>\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

my $error_msg = "$page: DB error :: $select :: $error";

# Error message: page, message, severity, die?
error_email ($page, $error_msg, 1, 0);
}



sub question_corrupt
{
my ($page, $quizname, $question, $comments) = @_;

check_ignore_error();

# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An internal error has occured.<br>\n";
print "Question Corrupt<p>\n";
print "Sorry for any inconvenience<p>\n";
print "The administrator has been notified of this error<p>\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "Question Syntax Error for quiz $quizname, question $question, $comments", 1, 0);
}



sub misc_error
{
my ($page, $quizname, $comments) = @_;

check_ignore_error();

# Error message
print header();
print start_html("Error");

print "<h1>Internal Error</h1>\n";
print "An unknown internal error has occured.<br>\n";
print "Sorry for any inconvenience<p>\n";
print "The administrator has been notified of this error<p>\n";
print "<p>&nbsp;</p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "Question Misc Error for quiz $quizname, $comments", 1, 0);
}


# Call if the page has been called out of sequence
# e.g. someone is trying to change questions after marking
# or trying to review before marking
# Task is what is trying to do - 
# * 1 if trying to look at review when not marked, 
# * 2 if trying to change questions after marked
# * 3 trying to use online quiz offline
sub sequence_error
{
my ($page, $quizname, $task, $comments) = @_;

check_ignore_error();

# Error message
print header();
print start_html("Error");

print "<h1>Out of Sequence</h1>\n";

if ($task == 1) {print "<p>You cannot see the answers until they have been marked.</p>\n";}
elsif ($task == 2) {print "<p>You cannot change the answers now they have been marked.</p>\n";}
elsif ($task == 3) {print "<p>You cannot use an online quiz session on the online quiz.</p>\n";}
else {print "<p>The requested task is out of sequence.</p>\n";}	
print "<p>&nbsp;</p>\n";
print "<p><a href=\"index.pl\">Return to the start of the quiz</a></p>\n";
print "\n";

# End HTTP
print end_html();

# Error message: page, message, severity, die?
error_email ($page, "Question Out of Sequence for quiz $quizname - $task, $comments", 4, 0);
}


sub send_email
{
my ($severity, $msg) = @_;

our ($smtpserver, $hellostring, $fromemail, $adminemail, $emailerror);
do $cfgfile or return;

# Is the error severe enough
if ($emailerror < $severity) {return;}
# Get users IP address
my $ipaddr = $ENV{'REMOTE_ADDR'};


my $from = $fromemail;
my $to = $adminemail;

# put header straight into body
my @email;
push @email, "From: ".$from."\n";
push @email, "To: ".$to."\n";
push @email, "Subject: Quiz Error - $severity_word[$severity]\n";
push @email, "\nError from Quiz - $severity_word[$severity]\n$msg\n\nFrom user at: $ipaddr";

my $smtp;
defined ($smtp = Net::SMTP->new($smtpserver, Hello => $hellostring)) or return;
$smtp->mail($from);
$smtp->to($to);
$smtp->data();
$smtp->datasend(@email);
$smtp->dataend();
$smtp->quit();
}


# Checks to see if the error message should be supressed.
# If it should be suppressed we issue a new error message and exit, else we return
sub check_ignore_error
{
	our @ignoreboterrors;
	do $cfgfile or return;

	my $ipaddr = $ENV{'REMOTE_ADDR'};
	my $ignore;
	foreach $ignore (@ignoreboterrors)
	{
		if ($ipaddr eq $ignore)
		{
			print header();
			print start_html("Robot Redirect Page");
			print <<ERROR;
			<h1>Quiz / Exam Error Page</h1>
			An Error has occured, most likely this is a web seearch engine 
			robot that has tried to access a dynamic page.<p>
			More information about this error is available from <a href="http://www.penguintutor.com">Penguin Tutor - Linux website and home of the quiz perl code</a>, <a href="http://www.watkissonline.co.uk">WatkissOnline.co.uk - Stewart Watkiss's pages, perl developer of this program</a>
ERROR
			print end_html();
			exit;
		}
	}
}



# Administrator error (e.g. invalid input)
sub admin_error
{
my ($page, $errormsg) = @_;

check_ignore_error();
	
# Error message
print header();
print start_html("Input Error");

print "<h1>Input Error</h1>\n";
print "<p>$errormsg</p>";
print "<p>Please go back and correct the error</p>";
print "\n";

# End HTTP
print end_html();

# Set very low priority, don't normally email this error
error_email ($page, "admin error ".$errormsg, 5, 0);
}



# Adds the errors to the log file
sub log_errors
{
my ($errormsg) = @_;

# If cannot open config file - then it won't be opened by the error so ignore
our $errorlogfile;
do $cfgfile or return;


# Get date in a user format
my $time = localtime (time);
# Get users IP address
my $ipaddr = $ENV{'REMOTE_ADDR'};

open (LOGFILE, ">>$errorlogfile") or return;
print LOGFILE "$time : $ipaddr : $errormsg\n";
close LOGFILE;
}


# Sends an email message
# Error message: page, message, severity, die?
# severity (1 to 5), 1 = critical, 3 = major, 4 = warnings, 5 = debug
# Return 1 - warning carry on running, 0 - End program unable to continue

# Email rules (0 to 5), 0 = never email, 1 = critical only, 3 = major, 4 = warnings, 5 = debug
sub error_email
{
my ($page, $message, $severity, $return) = @_;

my $full_msg = "$page : $severity_word[$severity] : $message";

log_errors ($full_msg);
send_email ($severity, $full_msg);

# Die if we don't return - these are put in syslog as well as the error log
if ($return != 1) {die "Quiz $full_msg";}
# We return 0 in case any of the calling code want to see if we've been called
return 0;
}