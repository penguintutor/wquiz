#!/usr/bin/perl -w

use CGI qw(:standard);
use DBI;

use strict;

# Set some variables to ensure we don't get warnings.
our ($dbname, $dbuser, $dbpass, $dbtable, $dbsessiontable, $dbactivetable, $dbsettingsnumtable);
do "../quiz.cfg" or do 
{
	print header();
	print start_html("Unable to install - no config found");
	print << "HTML1";
<h1>Unable to install - no config found</h1>
<p>Unable to open ../quiz.cfg - please read the install file for details of 
how to configure the configuration file before running this script</p>
HTML1
	print end_html();
	exit (0);
};


# These are the SQL commands if you need to create the database tables manually
my $dbtablesql = "create table $dbtable (question int primary key auto_increment, quiz varchar(254) NOT NULL, section varchar(254) NOT NULL, intro TEXT NOT NULL, input TEXT NOT NULL, type varchar(10) NOT NULL, answer varchar(100) NOT NULL, reason TEXT NOT NULL, reference varchar(100) NOT NULL, hint varchar(254) NOT NULL, image varchar(200) NOT NULL, comments varchar(200) NOT NULL, qfrom varchar(50) NOT NULL, email varchar(50) NOT NULL, created date NOT NULL, reviewed date NOT NULL)";
my $dbsessiontablesql = "create table $dbsessiontable (session_id varchar(254) primary key, startsession datetime NOT NULL, quizname varchar(254) not null, status int not null, name varchar(254) not null)";
my $dbactivetablesql = "create table $dbactivetable (session_id varchar(254), qnum int not null, question int not null, answer varchar(254) not null, PRIMARY KEY (session_id , qnum))";
my $dbsettingsnumtablesql = "create table $dbsettingsnumtable (settingkey char(25) primary key, settingvalue int not null)";

my $dbalreadyexist = 0;

my ($dbi, $dbtype, $thisdb, $dbserver) = split /:/, $dbname, 4;

# Some checks
if ($dbi ne "DBI" || $dbtype ne "mysql")
{
		print header();
	print start_html("Not a valid DB type");
	print << "HTML2";
<h1>Not a valid DB type</h1>
<p>This install script will only work with mysql using the following format.
Please correct and rerun this script, or create the database manually.</p>
<p>The format should normally be: our $dbname:mysql:<i>quiz</i>:localhost</p>
HTML2
	print end_html();
	exit (0);
}

# Create the DB 
my $drh = DBI->install_driver('mysql');
## If this fail then DB already exists (no error)
my $rc = $drh->func("createdb", $thisdb, $dbserver, $dbuser, $dbpass, 'admin');

# Connect to new DB - with admin
my $dbh = DBI->connect($dbname,$dbuser,$dbpass) or do 
{
	print header();
	print start_html("Unable to connect to database");
	print << "HTML3";
<h1>Unable to connect to database</h1>
<p>Unable to connect, or create database $dbname</p>
<p>You will need to create the database manually</p>
HTML3
	print end_html();
	exit (0);
};

# Create tables
my $query = $dbh->prepare($dbtablesql);
$query -> execute or do 
{
	my $error = $dbh->errstr();
	print header();
	print start_html("Unable to create table");
	print << "HTML4";
<h1>Unable to create table</h1>
<p>Unable to create table $dbtable</p>
<p>Error: $error <br />SQL: $dbtablesql</p>
<p>If the tables already exist then there is no need to run this script</p>
<p>You will need to create the database manually</p>
HTML4
	print end_html();
	exit (0);
};
$query->finish;

$query = $dbh->prepare($dbsessiontablesql);
$query -> execute or do 
{
	my $error = $dbh->errstr();
	print header();
	print start_html("Unable to create table");
	print << "HTML5";
<h1>Unable to create table</h1>
<p>Unable to create table $dbsessiontable</p>  
<p>Error: $error <br />SQL: $dbsessiontablesql</p>
<p>If the tables already exist then there is no need to run this script</p>
<p>You will need to create the database manually</p>
HTML5
	print end_html();
	exit (0);
};
$query->finish;


$query = $dbh->prepare($dbactivetablesql);
$query -> execute or do 
{
	my $error = $dbh->errstr();
	print header();
	print start_html("Unable to create table");
	print << "HTML5";
<h1>Unable to create table</h1>
<p>Unable to create table $dbactivetable</p>
<p>Error: $error <br />SQL: $dbactivetablesql</p>
<p>If the tables already exist then there is no need to run this script</p>
<p>You will need to create the database manually</p>
HTML5
	print end_html();
	exit (0);
};
$query->finish;

$query = $dbh->prepare($dbsettingsnumtablesql);
$query -> execute or do
{
	my $error = $dbh->errstr();
	print header();
	print start_html("Unable to create table");
	print << "HTML5";
<h1>Unable to create table</h1>
<p>Unable to create table $dbsettingsnumtable</p>
<p>Error: $error <br />SQL: $dbsettingsnumtablesql</p>
<p>If the tables already exist then there is no need to run this script</p>
<p>You will need to create the database manually</p>
HTML5
	print end_html();
	exit (0);
};
$query->finish;


# DB Setup Complete
$dbh->disconnect;


	print header();
	print start_html("Database Created");
	print << "HTML5";
<h1>Database Created</h1>
<p>You should now be able to add questions from the <a href="index.pl">Administration Index Page</a>.</p>
HTML5

# Set return code
exit 1;








