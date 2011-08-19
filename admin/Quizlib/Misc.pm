
package Quizlib::Misc;

use Quizlib::Errors;

use strict;

our $verbose = 1;



sub readhtml 
{
my ($filename) = @_;
my $outtext = "";
if (!open(INFILE, $filename)) {return "File missing $filename";} 
while (<INFILE>)
	{
	$outtext .= $_;
	}
close (INFILE);
return $outtext;
}

# This is done to allow use in admin where the lack of a template file
# should not cause an error
sub readhtml_noerror 
{
my ($filename) = @_;
my $outtext = "";
if (open(INFILE, $filename))
{
	while (<INFILE>)
		{
		$outtext .= $_;
		}
close (INFILE);
}
return $outtext;
}




# Returns next number in the offline file
#-- Not currently implemented returning 001
sub offlineNumber
{
my ($filename) = @_;

open (COUNTERFILE, "+< $filename") or Quizlib::Errors::offline_counter_error($filename);
flock(COUNTERFILE,2) or Quizlib::Errors::offline_counter_error("$filename,lockerror");
my $count = readline (COUNTERFILE);
$count ++;
seek COUNTERFILE, 0, 0 or Quizlib::Errors::offline_counter_error("$filename,rewinderror");
print COUNTERFILE $count;
close COUNTERFILE;
return $count;
}

sub gen_random_key
{
my ($numchars) = @_;

my ($i, $y);
my $sessionkey = "";

my @validchars = ("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z" );

for ($i = 0; $i < $numchars; $i++)
	{
	$y = int(rand(scalar @validchars));
	$sessionkey .= $validchars[$y];
	}

return $sessionkey;
}


sub gen_random_number
{
my ($numchars) = @_;

my ($i, $y);
my $sessionkey = "";

my @validchars = ("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");

for ($i = 0; $i < $numchars; $i++)
	{
	$y = int(rand(scalar @validchars));
	$sessionkey .= $validchars[$y];
	}

return $sessionkey;
}

sub log_login
{
my ($quizname, $username, $session) = @_;

# If cannot open config file - then it won't be opened by the error so ignore
our $loginlogfile;
do "quiz.cfg" or return;

# Get date in a user format
my $time = localtime (time);
# Get users IP address
my $ipaddr = $ENV{'REMOTE_ADDR'};

my $success = 1;
open (LOGFILE, ">>$loginlogfile") or $success = Quizlib::Errors::error_email ("Login log", "Error writing to log file", 4, 1);
if ($success == 0) {return;}
print LOGFILE "$time : $quizname : $ipaddr : $username : $session\n";
close LOGFILE;
}

sub log_score
{
my ($quizname, $session, $score, $numquestions) = @_;

# If cannot open config file - then it won't be opened by the error so ignore
our $scorelogfile;
do "quiz.cfg" or return;

# Get date in a user format
my $time = localtime (time);
# Get users IP address
my $ipaddr = $ENV{'REMOTE_ADDR'};

my $success = 1;
open (LOGFILE, ">>$scorelogfile") or $success = Quizlib::Errors::error_email ("Score log", "Error writing to log file", 4, 1);
if ($success == 0) {return;}
print LOGFILE "$time : $quizname : $ipaddr : $session : $score : $numquestions\n";
close LOGFILE;
}

# Converts & to %26 etc.
# This is specifically to allow us to edit and resave data
# e.g. designed for handling &amp; stored in db, will ignore commas (used for other purposes)
sub format_edit_html
{
my ($convtext) = @_;
$convtext =~ s/&/&amp;/g;
return $convtext;
}

# Converts a number to 2 digits (used for day and month numbers)
sub to_2_digits
{
my ($date) = @_;

if ($date < 10)
        {
        $date = "0".$date;
        }
return $date;
}

# converts a number to 4 digits
sub to_4_digits
{
my ($date) = @_;

if ($date < 10) {$date = "000".$date;}
elsif ($date < 100) {$date = "00".$date;}
elsif ($date < 1000) {$date = "0".$date;}
return $date;
}
