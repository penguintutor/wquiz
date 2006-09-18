# Provides functions to access DB

package Quizlib::Qdb;

use CGI;
use DBI;
use Quizlib::Errors;
use Quizlib::Misc;

use strict;

my $verbose = 1;

# Insert values into table using ("entry1", "entry2" ...)
sub db_insert
{
my ($dbname, $dbuser, $dbpass, $page, $table, @values) = @_;

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

my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($page,$dbname, "Connect", "- Failed");
# Convert \ to \\ ready for DB update
$select =~ s/\\/\\\\/g;
my $query = $dbh->prepare($select);
$query -> execute or Quizlib::Errors::db_error($page,$dbname, $select,$dbh->errstr);
$query->finish;
$dbh->disconnect;
}


sub db_delete_row
{
my ($dbname, $dbuser, $dbpass, $page, $table, $question) = @_;

my $select = "delete from $table where question = \"$question\"";
my $dbh = DBI->connect($dbname,$dbuser,$dbpass) or die "error connecting to DB";
my $query = $dbh->prepare($select);
$query -> execute or die "Error updating DB: $select, Page $page";
$query->finish;
$dbh->disconnect;
}


#Gets list of options from a table (e.g. quiznum) - last field optional allows order by or where = to be added
sub db_list_options
{
#my ($dbname, $dbuser, $dbpass, $user, $page, $table, $field, $extras) = @_;
my ($dbname, $dbuser, $dbpass, $page, $table, $field, $extras) = @_;
# Connect to mysql
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($page,$dbname, "Connect", "- Failed");
#my $apache_user = $dbh->quote($user);
my $query;
if (!defined $extras)
	{
	$query = $dbh->prepare("SELECT $field FROM $table");
	$query -> execute or Quizlib::Errors::db_error($page,$dbname, "SELECT $field FROM $table",$dbh->errstr);
	}
else
	{
	$query = $dbh->prepare("SELECT $field FROM $table $extras");
	$query -> execute or Quizlib::Errors::db_error($page,$dbname, "SELECT $field FROM $table $extras",$dbh->errstr);
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


#Gets list of options from a table (e.g. roles) returns as a hash where first field is the key and second is the value
sub db_list_hash
{
my ($dbname, $dbuser, $dbpass, $page, $table, $field, $field2) = @_;
# Connect to mysql
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($page,$dbname, "Connect", "- Failed");

my $query = $dbh->prepare("SELECT $field, $field2 FROM $table");
$query -> execute or Quizlib::Errors::db_error($page,$dbname, "SELECT $field FROM $table",$dbh->errstr);


my %entries;
my @this_entry;
while (@this_entry = $query->fetchrow_array)
	{
	$entries{$this_entry[0]} = $this_entry[1];
	}

$query->finish;
$dbh->disconnect;
return %entries;
}

#Gets list of options from a table (e.g. roles) returns as a hash where first field is the key and second is the value
sub db_get_entry_hashref
{
my ($dbname, $dbuser, $dbpass, $page, $table, $where) = @_;
# Connect to mysql
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($page,$dbname, "Connect", "- Failed");

my $query = $dbh->prepare("SELECT * FROM $table $where");
$query -> execute or Quizlib::Errors::db_error($page,$dbname, "SELECT * $table $where",$dbh->errstr);



return $query->fetchrow_hashref;
$dbh->disconnect;
}


#Gets all details for a specific entry - returns as array - it is down to calling program to ensure that only one entry will be selected (preferably use the primary key)
sub db_get_entry
{
my ($dbname, $dbuser, $dbpass, $page, $table, $where) = @_;
# Connect to mysql
my $dbh = DBI->connect("$dbname",$dbuser,$dbpass) or Quizlib::Errors::db_error($page,$dbname, "Connect", "- Failed");
my $query;
$query = $dbh->prepare("SELECT * FROM $table $where");
$query -> execute or Quizlib::Errors::db_error($page,$dbname, "SELECT * FROM $table $where",$dbh->errstr);

my @this_entry = $query->fetchrow_array();
$query->finish;
$dbh->disconnect;
return @this_entry;
}



