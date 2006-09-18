#!/usr/bin/perl -w
# Performs housekeeping for the quiz
# To run on a regular basis - depends upon how busy site is
# Keeping the file short will minimise time that it takes to check for DOS attacks
# But it can lock users out whilst running. - try daily / twice daily
# e.g. in crontab
#10 3,15 * * * /var/www/cgi-bin/quiz/admin/housekeep.pl 

use Fcntl qw(:DEFAULT :flock);
use strict;

# These entries must be the same (or higher) than  values in activesec
our $countperiod = 300;
# Time before allow logins again (secs) 600 = 10 mins
our $blocktime = 600;

# These are the entries we use from the cfg file
our ($seccache, $denyfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

my $currenttime = time;

# Clear denyfile first as it's quicker and less likely to be being updated
clearfile ($denyfile, $currenttime - $blocktime);

# Now do seccache file 
clearfile ($seccache, $currenttime - $countperiod);


## Add here any other operations needed to clearup values









# Clears expired entries from a file (either cache or denyfile)
# Only locks the file when saving, this may mean that some entries are lost if written between reading and writing
# but has little impact on the activesecurity files.
# It however minimises the impact of this locking files for a long period of time
# Only works for files where time is in numerical order (not an issue for this)
# File must be timestamp(since epoch) space followed by any other info
# expiretime needs to be current time-countperiod(orblocktime)
sub clearfile
{
	my ($filename, $expiretime) = @_;

	# Save the entries we are keeping into an array, saves us having to save to a
	# temporary file, but could impact machine if the files were really large and didn't have
	# much memory on the machine - (in that case either not running often enough, or machine is not capaable of handling the quiz anyway)
	
	my ($thistimeentry, $details);
	my $timereached = 0;
	my @keepentries;
	# Check see if denyfile contains this address
	# file may not exist in which case return as it means we have no entries
	if (!open (INFILE, $filename)) {return;}
	
	while (<INFILE>)
	{
		if ($timereached == 1) {push @keepentries, $_;}
		else
		{
			($thistimeentry, $details) = split / /, $_;
			if ($thistimeentry > $expiretime) 
			{
				$timereached = 1;	#Set time reached so we just write rest to keepentries
				push @keepentries, "$thistimeentry $details";
			}
		}
	}
	close (INFILE);
	

	# We always write back to the file, otherwise if we have 0 entries then we won't clear them
	my $i;
	if (!open (OUTFILE, ">$filename")) {die "Error updating $filename";}
	flock (OUTFILE, LOCK_EX) or die "Unable to get lock on $filename";
	
	for ($i=0; $i <scalar @keepentries; $i++)
	{
		print OUTFILE "$keepentries[$i]\n";
	}
	close (OUTFILE);
	
}
