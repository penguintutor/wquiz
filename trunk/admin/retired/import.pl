#!/usr/bin/perl -w

# Updated Version 0.3.0 - devel
# Imports version 2.0 to 2.8 exported files into version 0.3 DB format, use for migration purposes, not suitable for backup and restore

use DBI;
use strict;

my $infile = "quiz.txt";


my @entries = ("Title", "Quiz", "Section", "Intro", "Input", "Type", "Answer", "Reason", "Reference", "Hint", "Image", "Comments", "From", "Email"); 

our (%quiznames, @category, %numquestions, %quizintro, $dbname, $dbtable, $dbuser, $dbpass);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";



open (INFILE, $infile) or die "error reading file $infile";

# Still get list of all questions so we know if need to update or add
my @all_questions = db_list_options ($dbname, $dbuser, $dbpass, $dbtable, "question");

my $next_title = "";
my $this_title = "";
my @this_entry;

# an integer of position in array
my $this_field;
my $this_line;
my $temp;

while (<INFILE>)
	{
	chomp;
	$this_line = $_;
	# Ignore blank lines
	if ($this_line =~ /^\s*$/) {next;}
	# next field - so update DB with current & prepare for next
	elsif ($this_line =~ /^\[(\w+)\]/ && $this_title ne "")
		{
		$next_title = $1;
		# Update DB with this entry
		# see if entry exists
		if (chk_in_array ($this_title, @all_questions))
			{
			db_delete ($dbname, $dbuser, $dbpass, $dbtable, $this_title); 
			}
		
		# Set title to 0 so increment
		$this_entry[0]  = "";
		$this_entry[14] = "0000-00-00";
		$this_entry[15] = "0000-00-00";
		# Do insert
		db_insert ($dbname, $dbuser, $dbpass, $dbtable, @this_entry);
					
		$this_title = $next_title;
		# Set all fields to default
		@this_entry = ("$this_title", "", "", "", "", "", "", "", "", "", "", "", "", "", "0000-00-00", "0000-00-00");
		}
	elsif ($this_line =~ /^\[(\w+)\]/)
		{
		$this_title = $1;
		# Set all fields to default
		@this_entry = ("$this_title", "", "", "", "", "", "", "", "", "", "", "", "", "", "0000-00-00", "0000-00-00");
		}
	elsif ($this_line =~ /^:(\w+)\s*$/)
		{
		$temp = $1;
		$this_field = pos_in_array($temp, @entries);
		if ($this_field == 0) {die "Title as an entry - not allowed";}
		}
	elsif ($this_line =~ /^:/)
		{
		die "syntax error - (not a valid option) : $_"; 
		}
	else 
		{
		# If we already have a value then add a <cr>
		if ($this_entry[$this_field] ne "") {$this_entry[$this_field] = $this_entry[$this_field]."\n";}
		$this_entry[$this_field] = $this_entry[$this_field].$this_line;
		}
	}
	
if (chk_in_array ($this_title, @all_questions))
	{
	db_delete ($dbname, $dbuser, $dbpass, $dbtable, $this_title); 
	}
db_insert ($dbname, $dbuser, $dbpass, $dbtable, @this_entry);


# This not very efficient, but then this is a batch job anyway - returns 1 if entry exists in array, or 0 if not
sub chk_in_array
{
my ($entry, @array) = @_;

my $i;
for ($i=0; $i < scalar @array; $i++)
	{
	if ($entry eq $array[$i]) {return 1;}
	}
return 0;
}


# This not very efficient, but then this is a batch job anyway - returns number of pos / else die - should have already checked (using above if appropriate)
sub pos_in_array
{
my ($entry, @array) = @_;

my $i;
for ($i=0; $i < scalar @array; $i++)
	{
	if ($entry eq $array[$i]) {return $i;}
	}
die "unable to find $entry in array";
}

sub db_get_entry
{
my ($dbname, $dbuser, $dbpass, $table, $where) = @_;
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or die "db connect failed";
my $query;
$query = $dbh->prepare("SELECT * FROM $table $where");
$query -> execute or die "select failed";

my @this_entry = $query->fetchrow_array();
$query->finish;
$dbh->disconnect;
return @this_entry;
}


sub db_list_options
{
my ($dbname, $dbuser, $dbpass, $table, $field, $extras) = @_;
my $dbh = DBI->connect($dbname,$dbuser,$dbpass) or die "db connect failed";
my $query;
if (!defined $extras)
	{
	$query = $dbh->prepare("SELECT $field FROM $table");
	$query -> execute or die "select failed";
	}
else
	{
	$query = $dbh->prepare("SELECT $field FROM $table $extras");
	$query -> execute or die "select failed";
	}

my @entries;
my @this_entry;
while (@this_entry = $query->fetchrow_array())
	{
	push @entries, $this_entry[0];
	}

$query->finish;
$dbh->disconnect;
return @entries;
}


sub db_insert
{
my ($dbname, $dbuser, $dbpass, $table, @values) = @_;

my $select = "insert into $table values (";
my $i;
my $not_first =0;
for ($i=0; $i < scalar @values; $i++)
	{
	if ($not_first) {$select = $select.",";} # Only put a comma on not first entry
	else {$not_first = 1;}
	$select = $select."\'$values[$i]\'";
	}
$select = $select.")";

my $dbh = DBI->connect($dbname,$dbuser,$dbpass) or die "error connecting to DB";
#my $apache_user = $dbh->quote($user);
my $query = $dbh->prepare($select);
$query -> execute or die "Error updating DB: $select";
$query->finish;
$dbh->disconnect;
}


sub db_delete
{
my ($dbname, $dbuser, $dbpass, $table, $title) = @_;

my $select = "delete from $table where question = \"$title\"";
my $dbh = DBI->connect($dbname,$dbuser,$dbpass) or die "error connecting to DB";
my $query = $dbh->prepare($select);
$query -> execute or die "Error updating DB: $select";
$query->finish;
$dbh->disconnect;
}

