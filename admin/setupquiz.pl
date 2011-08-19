#!/usr/bin/perl -w

# Setup a new quiz. Run this command from the command line after installation.
# This should be run as root, and will ask for the mysql administrative password
# There is no validation of data - we expect administrators to follow instructions

# The quiz supports MySQL only. To support another DB then it would need to be installed
# manually - see Documentation for more details

use ExtUtils::Installed;
use strict;

my $version = "0.3.0";

my @prereqs = ('Cache::Cache', 'DBI', 'CGI');

# Check we have pre-req's 
print "Quiz Setup Program\n\n";


# Get user and group for file permissions
our $uid = getpwnam "apache";
our $gid = getgrnam "apache";


## Find what directory installed in, so that we can calculate paths for cfg files
my $i;
my $prevdircount = 0;
my $directory = "";
my $dirpart2 = "";

my $pwd = `pwd`;
chomp $pwd;
chomp $0;

my @program = split /\//, $0;
my @currdir = split /\//, $pwd;

# Work out part 2 - taken from path
# Check see if we start with any directories in program and if we have .. count how many - must be at beginning
for ($i=0; $i <(scalar @program)-1; $i++)
{
	# if we have .. where we haven't created some part of the directory
	# if we've already created some $dirpart2 then we have .. in the middle of a string and don't like 
	if ($program[$i] eq "..")
	{
		if ($dirpart2 eq "") {$prevdircount++;}
		else {die "\n\nError - illegal use of .. on commandline: $0\n";}
	}
	elsif ($program[$i] eq ".") {next;}
	else {$dirpart2 .= "/$program[$i]";}
}
# Was command run as absolute (in which case don't need to look at pwd)
if ($0 =~ /^\//) {$directory = $dirpart2;}
else
{
	# Work through currdir - but stop early if we have $prevdircount
	for ($i=0; $i < (scalar @currdir)-$prevdircount; $i++)
	{
		$directory .= "/$currdir[$i]";
	}
	$directory .= $dirpart2;
}

# Now strip of admin directory (where the script is) and ensure it ends without /
$directory =~ s/\/admin\/{0,1}$//;
# Strip of double initial /
$directory =~ s/^\/\//\//;


print "\nChecking for pre-requisite perl modules\n";
my $inst    = ExtUtils::Installed->new();
my @modules = $inst->modules();
my $modules_needed = 0; #incremented if we need to install modules - whether or not we attempt an install
my ($inst_status, $thismodule, $instmodule, $useranswer);

foreach $thismodule (@prereqs)
{
	$inst_status = 0;
	foreach $instmodule (@modules)
	{
		if ($thismodule eq $instmodule) {$inst_status = 1; last;} 
	}
	if ($inst_status == 1)
	{
		print "Module $thismodule installed\n";
	}
	else
	{
		$modules_needed ++;
		print "\n\nModule $thismodule NOT installed\n";
		print "Do you want to attempt an install from CPAN?[yes] ";
		$useranswer = readline STDIN;
		chomp $useranswer;
		if ($useranswer eq "" || $useranswer =~ /^y/i) {system ("cpan -i $thismodule");}
		else {print "Skipping Module $thismodule\n\n";}
	}
}

print "\n\nModule check complete.";
if ($modules_needed >0) 
{
	print "\nIf there are any unresolved errors cancel this and install the modules manually.\n";
	print "Do you want to continue with the install?[yes] ";
	$useranswer = readline STDIN;
	chomp $useranswer;
	if ($useranswer ne "" && $useranswer !~ /^y/i) {print "Install Aborted\n"; exit(0);}
}

# Check for quiz.cfg - if it exists then check it hasn't already been installed
if (open (TEMPFILE, "../quiz.cfg")) 
{
	print "\n** quiz.cfg file already exists. \nIf an earlier install failed then it should be safe to continue\n**\nIf you want to install multiple copies of the quiz\nread the documentation carefully.\nContinuing to install will remove the current configuration\nare you sure you want to continue?[no] ";
	close (TEMPFILE);
	$useranswer = readline STDIN;
	chomp $useranswer;
	if ($useranswer eq "" || $useranswer =~ /^n/i) {print "\n\nInstall Aborted\n"; exit(0);}
}

print "Please provide MySQL Database details\n** Warning passwords will be shown on the commandline **\nYou should ensure that nobody else can see your screen.\n\n";

print "\nShort Name for the Quiz (no spaces or special characters, unique for each quiz): ";
my $quizname = readline STDIN;
chomp $quizname;
# Use quizname for the DB tablename
my $dbtable = $quizname;
print "Database Server [localhost]: ";
my $dbserver = readline STDIN;
chomp $dbserver;
if ($dbserver eq "") {$dbserver = "localhost";}
print "Database Name [quiz]: ";
my $dbname = readline STDIN;
chomp $dbname;
if ($dbname eq "") {$dbname = "quiz";}

print "MySQL New User (will be created) - for quiz only [$quizname]: ";
my $dbuser = readline STDIN;
chomp $dbuser;
if ($dbuser eq "") {$dbuser = $quizname;}
# Generate password - use for DB and store in quiz.cfg user doesn't need to know what it is
my $dbpass = gen_password(15);
print "Secure password automatically generated for DB access\n";

print "Username to administer quiz with [$quizname]: ";
my $adminuser = readline STDIN;
chomp $adminuser;
if ($adminuser eq "") {$adminuser = $quizname;}
print "Password to administer quiz with : ";
my $adminpass = readline STDIN;
chomp $adminpass;
if ($adminpass eq "") {print "\nWarning no password supplied, please update quiz.cfg after install\n\n";}


## All files to be owned by apache:apache with permissions 644 (directory 755)
# Change of tact - we create a directory and give all files default name
# Still coded in config file individually - so can still change after if prefer
## Log files
print "\nPlease enter directory for temporary quiz files - this should be unique for each quiz\nDirectory seperators must be / even on windows machines.\n\n";
print "\nLog Files [/var/quiz/$quizname/]:";
#print "\nLogin Logfile [/var/quiz/login.log]:";
#my $loginlogfile = readline STDIN;
#chomp $loginlogfile;
#if ($loginlogfile eq "") {$loginlogfile = "/var/quiz/login.log";}
my $vardirectory = readline STDIN;
chomp $vardirectory;
if ($vardirectory eq "") {$vardirectory = "/var/quiz/$quizname/";}
# If doesn't end with a / - add it
elsif ($vardirectory !~ /\/$/) {$vardirectory .= "/";}

my $loginlogfile = $vardirectory."login.log";
createfile ($loginlogfile);

#print "\nScore Logfile [/var/quiz/score.log]:";
#my $scorelogfile = readline STDIN;
#chomp $scorelogfile;
#if ($scorelogfile eq "") {$scorelogfile = "/var/quiz/score.log";}
my $scorelogfile = $vardirectory."score.log";
createfile ($scorelogfile);

#print "\nError Logfile [/var/quiz/error.log]:";
#my $errorlogfile = readline STDIN;
#chomp $errorlogfile;
#if ($errorlogfile eq "") {$errorlogfile = "/var/quiz/error.log";}
my $errorlogfile = $vardirectory."error.log";
createfile ($errorlogfile);

# print "\nOffline\n-------\n";
# print "\nOffline Count file [/var/quiz/offline.qcf]:";
# my $offlinecountfile = readline STDIN;
# chomp $offlinecountfile;
# if ($offlinecountfile eq "") {$offlinecountfile = "/var/quiz/offline.qcf";}
my $offlinecountfile = $vardirectory."offline.qcf";
createfile ($offlinecountfile);
# Need to set value for this file
open (WRITEFILE, ">$offlinecountfile") or die "unable to update $offlinecountfile";
print WRITEFILE "0";
close WRITEFILE;

#print "\nSecurity Files\n--------------\n";
#print "\nSecurity Cache file [/var/quiz/activesec.cache]:";
#my $activesecfile = readline STDIN;
#chomp $activesecfile;
#if ($activesecfile eq "") {$activesecfile = "/var/quiz/activesec.cache";}
my $activesecfile = $vardirectory."activesec.cache";
createfile ($activesecfile);

#print "\nSecurity Log File [/var/quiz/activesec.log]:";
#my $activeseclogfile = readline STDIN;
#chomp $activeseclogfile;
#if ($activeseclogfile eq "") {$activeseclogfile = "/var/quiz/activesec.log";}
my $activeseclogfile = $vardirectory."activesec.log";
createfile ($activeseclogfile);

#print "\nSecurity Deny File [/var/quiz/block.qal]:";
#my $denyfile = readline STDIN;
#chomp $denyfile;
#if ($denyfile eq "") {$denyfile = "/var/quiz/deny.qal";}
my $denyfile = $vardirectory."deny.qal";
createfile ($denyfile);


# Create a quiz.cfg file
open (CFGFILE, ">$directory/quiz.cfg") or die "\n\nUnable to create $directory/quiz.cfg \nInstall aborted\n";

print CFGFILE << "CONFIG";
# Configuration file for Quiz
# Created and updated using the setup / web scripts
# Read the manual before updating manually as a syntax error will stop the quiz from working

our \$version = \"$version\";

#############################
#Generic Information applies to all quizes
# DBSetup
our \$dbname=\"DBI:mysql:$dbname:$dbserver\";
our \$dbuser=\"$dbuser\";
our \$dbpass=\"$dbpass\";
our \$dbtable=\"$dbtable\";

# User and password for administrator to update quiz through web interface 
our \$adminuser=\"$adminuser\";
our \$adminpass=\"$adminpass\";

#Support for multiple style sheets - this feature may not be required depending upon your website
#First is default
our \@csscategories = (\"default\", \"alt\");
our \%cssfile = (
	\"default\" => \"\",  
	\"alt\" => \"\"
	);
# cssextra - allows insertion into html - e.g. allowing user to change css
our \%cssextra = (
	\"default\" => \"\",
	\"alt\" => \"\"
	);
# Allows for alternate links back to main page
our \%cssindex = (
	\"default\" => \"\",
	\"alt\" => \"\"
	);


## Adminsistrator details - these are used by the automation
# Set to a user to receive emails
our \$adminemail=\'root\@localhost\';
# Do we email error messages (0 to 5), 0 = never email, 1 = critical only, 3 = major, 4 = warnings, 5 = debug
# 4 should be the normal level
our \$emailerror=4;

# Number of cols in check/results table
our \$answercols = 3;

# Log files
our \$loginlogfile = \"$loginlogfile\";
our \$scorelogfile = \"$scorelogfile\";
our \$errorlogfile = \"$errorlogfile\";

# Count number of times used (part of unique session ID creation for offline only)
our \$offlinecountfile = \"$offlinecountfile\";

# Added in Version 0.2.4 for Anti-DOS
\$seccache = \'$activesecfile\';
\$seclogfile = \'$activeseclogfile\';
\$denyfile = \'$denyfile\';

# Exclude bots from the error messages
# Any listed will get a generic error message (instead of actual error)
# and will be provided with a link to the home page
# Errors will not send emails and will not be logged
# Use IP addresses (NOT hostnames / bot names)
our \@ignoreboterrors = (\'127.0.0.1\', \'192.168.1.0\');

################################
## Items specific to each quiz - must populate all fields and keep in line with category
# If not in category then quiz will not be used (can use to temporarily disable a quiz)
# The reason for having a seperate category (not just use keys) is so that we can have sort them in the order we want
# Allowed category, must have all from \@category, but can also have additional entries for quizes that are disabled or for future use.
our \@category=(\"all\");
our \@allowedcategory=(\"all\");
#Title of quiz
our \%quiznames = (\"all\" => \"All Categories\");
our \%numquestions = (\"all\" => 10);
our \%quizintro = (\"all\" => \"A selection of questions picked from all the different quizzes. This may include some questions from a quiz that has not yet been fully implemented.\");



CONFIG
close CFGFILE;

# Set permissions for quiz.cfg - apache:apache 640
if (defined $uid && defined $gid && $uid > 0 && $gid > 0)
	{
	chown $uid, $gid, "$directory/quiz.cfg";
	chmod 0640, "$directory/quiz.cfg";
	}
else
	{
	print "Please set the permission of $directory/quiz.cfg to match your webserver user/group names\n";
	}




# This needs to be run twoards the end, as otherwise if any steps fail earlier we may end up with multiple entries in the quizhousekeep.pl
# Ensure housekeep.pl has the correct permissions - it will run as root so ensure that only root can read/write
chown 0, 0, "$directory/admin/quizhousekeep.pl" or die "\n\nUnable to set owner for $directory/admin/quizhousekeep.pl\nInstall aborted\n";
chmod 0700, "$directory/admin/quizhousekeep.pl" or die "\n\nUnable to set permission for $directory/admin/quizhousekeep.pl\nInstall aborted\n";

# Create cron job in /etc/cron.daily
open (CRONFILE, ">>/etc/cron.daily/quizhousekeep") or die "\n\nUnable to create cron task\nInstall aborted\n";
print CRONFILE "$directory/admin/quizhousekeep.pl\n";
close CRONFILE;

chown 0, 0, "/etc/cron.daily/quizhousekeep" or die "\n\nUnable to set owner for $directory/admin/quizhousekeep.pl\nInstall aborted\n";
chmod 0700, "/etc/cron.daily/quizhousekeep" or die "\n\nUnable to set permission for $directory/admin/quizhousekeep.pl\nInstall aborted\n";


## Setup Part 2 (DB Setup

# By putting require rather than use - it will work as long as the install worked above
require DBI;

my $dblongname="mysql:$dbname:$dbserver";

print "\n\nExisting MySQL Username (with administrative privileges) [root]: ";
my $rootdbuser = readline STDIN;
chomp $rootdbuser;
if ($rootdbuser eq "") {$rootdbuser = "root";}
print "MySQL Password for $rootdbuser: ";
my $rootdbpass = readline STDIN;
chomp $rootdbpass;

# Now have the information required to create the DB / Table
print "\nCreating DB / Table:\nDB: $dblongname\nTable: $dbtable\n";

my $dbalreadyexist = 0;

# Create the DB 
print "\nCreating DB $dbname\@$dbserver";
my $drh = DBI->install_driver('mysql');
my $rc = $drh->func("createdb", $dbname, $dbserver, $rootdbuser, $rootdbpass, 'admin');
if ($rc != 1) {print "\nUnable to create DB, testing if it already exists"; $dbalreadyexist = 1;}

# Connect to new DB - with admin
my $dbh = DBI->connect("DBI:".$dblongname,$rootdbuser,$rootdbpass) or die "\nUnable to connect to DB using DBI:$dbname\nInstallation Aborted\n";

if ($dbalreadyexist) {print "\n\nDB already exists, most likely another quiz has already created the DB. \nAttempting to create table, if table already exists then this will fail, \notherwise you can safely ignore the above error message\n";}
else {print "\nDB created";}

# Grant permissions
# We actually grant permissions to entire DB - manually configure more specific if required
# Only grant permissions to localhost - if DB is installed on a seperate DB server, then needs to be setup manually
my $select = "grant usage on $dbname.* to $dbuser\@localhost identified by \'$dbpass\'";
my $query = $dbh->prepare($select);
$query -> execute or die "Error configuring Database - Grant 1\n$select\n";
$query->finish;
$select = "grant select,insert,update,delete,create,drop on $dbname.* to $dbuser\@localhost";
$query = $dbh->prepare($select);
$query -> execute or die "Error configuring Database - Grant 2\n$select\n";
$query->finish;

# Create table
$select = "create table $dbtable (question int primary key auto_increment, quiz varchar(254) NOT NULL, section varchar(254) NOT NULL, intro TEXT NOT NULL, input TEXT NOT NULL, type char(10) NOT NULL, answer char(100) NOT NULL, reason TEXT NOT NULL, reference varchar(100) NOT NULL, hint varchar(254) NOT NULL, image char(200) NOT NULL, comments varchar(200) NOT NULL, qfrom char(50) NOT NULL, email char(50) NOT NULL, created date NOT NULL, reviewed date NOT NULL);";
$query = $dbh->prepare($select);
$query -> execute or die "Error configuring Database - Create Table\n";
$query->finish;
print "\nTable Created";

print "\nDatabase Successfully Updated\n";
# DB Setup Complete
$dbh->disconnect;


# Set return code
exit 1;



## End part 2


print "\nInstallation complete - now add some questions and update the templates\n";


#####################################
# Subs

sub createfile 
{
my ($filename) =@_;
my ($i);

# Check directory already exists
my @dir = split /\//, $filename;
my $directory = "";


# Check directory each level at a time to make sure it exists
# As if it doesn't we need to create each one a step at a time
for ($i =0; $i < (scalar @dir) -1; $i++)
	{
	# Check each directory
	$directory .= "/$dir[$i]";
	# Strip of double initial /
	$directory =~ s/^\/\//\//;

	# Check the directory exists
	if (opendir (TEMPDIR, $directory)) {closedir TEMPDIR;}
	else 
		{
		mkdir $directory or die "Unable to create $directory";
		# Set permission - don't use within mkdir as that uses umask
		# Assume apache:apache for the user/group -if not needs to be done manually
		if (defined $uid && defined $gid && $uid > 0 && $gid > 0)
			{
			chown $uid, $gid, $directory;
			chmod 0755;
			}
		else
			{
			print "Please set the permission of $directory to match your webserver user/group names\n";
			}
		}
	}
# We've setup any dirs above, now create file
if (open (CHKFILE, $filename))
	{
	print "File already exists $filename\n";
	print "Update quiz.cfg after install to use a different file\n";
	close (CHKFILE);
	}
open (NEWFILE, ">$filename") or die "Unable to create file $filename";
close NEWFILE;
# Make sure that we actually have a user and gid for apache before running chown / chmod
if (defined $uid && defined $gid && $uid > 0 && $gid > 0)
	{
	chown $uid, $gid, $filename;
	chmod 0644, $filename;
	}
else
	{
	print "Please set the permission of $filename to match your webserver user/group names\n";
	}
}





# Generate a random password of alphanumeric (numchars) long
sub gen_password
{
my ($numchars) = @_;

my ($i, $y);
my $key = "";

my @validchars = ("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z" );

for ($i = 0; $i < $numchars; $i++)
	{
	$y = int(rand 60);
	$key .= $validchars[$y];
	}

return $key;
}
