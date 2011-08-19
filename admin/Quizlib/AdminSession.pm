# AdminSession.pm - deals with creating and checking session for admin
# Note this is specifically for the admin function - not the same as used for the quiz

package Quizlib::AdminSession;
use DBI;
use Quizlib::Errors;
use Time::Local;

use strict;

my $verbose = 1;


# returns 1 on success, 0 on expired an -1 on invalid (ie not a admin session)
# we don't do cleanup using check_login, instead rely on existing clearup from logout or quiz
sub check_login {
# Checks the session exists, and that it hasn't expired
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $adminsessiontimeout) = @_;

if (!defined $session || $session eq "") {return 0;}

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query = $dbh->prepare("select startsession, status from $dbsessiontable where session_id =\"$session\""); 
$query -> execute or Quizlib::Errors::db_error("Admin" ,$dbname, "select startsession, status from $dbsessiontable where session_id =\"$session\"", $dbh->errstr);
my ($startsession, $status) = $query->fetchrow_array();


$query->finish;

# If we don't get status then session has gone (possibly expired and deleted)
if (!defined $status || $status eq "") {$dbh->disconnect; return 0;}
# If not an admin session - return error
if ($status != 10) {return -1;}
# If expire
# first check we have a valid date / time - otherwise test expireed
# Shouldn't get this, but it adds an extra check
if (!defined $startsession || $startsession eq "" || $startsession eq "0000-00-00 00:00:00") {$dbh->disconnect; return 0;} 
# Convert the time to UNIX like time stamp (so we can compare them as numbers)
#timegm uses sec, min, hr, mday, mon, year - SQL returns yyyy-mm-dd hh:mm:ss
#mon -1 as c struct used in the timegm / gmtime functions goes from 0-11
my $startsessionunix = timegm(substr ($startsession, 17,2), substr ($startsession, 14, 2), substr ($startsession, 11, 2), substr ($startsession, 8, 2), substr ($startsession, 5, 2), substr ($startsession, 0, 4));
# Take current time (unix format) subtract expiry period
my $expirytime = time - ($adminsessiontimeout * 60);
# If expired
if ($startsessionunix < $expirytime) {$dbh->disconnect; return 0;}

# If we successfully authenticated - update the starttime with the current time to extend login
# Get current time (gmtime) in iso yyyy-mm-dd hh:mm:ss
my @timearray = gmtime (time);
my $currenttime = ($timearray[5]+1900)."-".($timearray[4]+1)."-".$timearray[3]." ".$timearray[2].":".$timearray[1].":".$timearray[0]; 

my $update = $dbh->prepare("update $dbsessiontable set startsession=\"$currenttime\" where session_id=\"$session\""); 

$update -> execute or Quizlib::Errors::db_error("Admin" ,$dbname, "update $dbsessiontable set startsession=\"$currenttime\" where session_id=\"$session\"", $dbh->errstr);
$update->finish;

$dbh->disconnect;

}


sub setsession
{
my ($session, $quizname, $username, $dbname, $dbuser, $dbpass, $dbsessiontable) = @_;	

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");

# Get current time (gmtime) in iso yyyy-mm-dd hh:mm:ss
my @timearray = gmtime (time);
my $currenttime = ($timearray[5]+1900)."-".($timearray[4]+1)."-".$timearray[3]." ".$timearray[2].":".$timearray[1].":".$timearray[0]; 

# Note 10 means this is an admin session
my $query = $dbh->prepare("insert into $dbsessiontable values (\"$session\", \"$currenttime\", \"$quizname\", \"10\", \"$username\")"); 

$query -> execute or Quizlib::Errors::db_error("Admin" ,$dbname, "insert into $dbsessiontable values (\"$session\", \"$currenttime\", \"$quizname\", \"10\", \"$username\")", $dbh->errstr);
$query->finish;
$dbh->disconnect;
}


# Just logout, delete sql entry
sub logout {
my ($session, $dbname, $dbuser, $dbpass, $dbsessiontable) = @_;

if (!defined $session || $session eq "") {return -1;}

# First check a session exists (otherwise this could be used as DOS by running the housekeeping)
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error("Admin", $dbname, "Connect", "- Failed");
my $query = $dbh->prepare("select startsession, status from $dbsessiontable where session_id =\"$session\""); 
$query -> execute or Quizlib::Errors::db_error("Admin" ,$dbname, "select startsession, status from $dbsessiontable where session_id =\"$session\"", $dbh->errstr);
my ($startsession, $status) = $query->fetchrow_array();
$query->finish;
# If we don't get status then session has gone (possibly expired and deleted)
if (!defined $status || $status eq "") {$dbh->disconnect; return -1;}


$query = $dbh->prepare("delete from $dbsessiontable where session_id =\"$session\" limit 1"); 
$query -> execute or Quizlib::Errors::db_error("Admin" ,$dbname, "delete from $dbsessiontable where session_id =\"$session\" limit 1", $dbh->errstr);
$query->finish;

$dbh->disconnect;
}

