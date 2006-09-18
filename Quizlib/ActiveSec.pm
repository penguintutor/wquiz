# ActiveSec.pm 
# Pro-Active security features, eg. anti-DOS support

package Quizlib::ActiveSec;

use CGI qw(:standard);
use Fcntl qw(:DEFAULT :flock);
use Quizlib::Errors;

use strict;

my $verbose = 1;

# If the following time values change then the housekeeping job will need to be update
# Max period of time (secs) to count over 180secs = 3mins 300sec = 5 mins
our $countperiod = 180;
# Max number of logins in that time
our $maxlogins = 20;
# Time before allow logins again (secs) 600 = 10 mins
our $blocktime = 600;

# Add entry to cache with timestamp and ipaddress
#Quizlib::ActiveSec::add_sec_cache($seccache, $ipaddr); - no values returned 
sub add_sec_cache
{
	my ($seccache, $ipaddr) = @_;

	# Note we don't error if logging fails, it would be a bit ironic to break
	# the quiz by a non-working anti-DOS measure
	if (open (CACHEFILE, ">>$seccache"))
	{
		# Lock file so we don't get two people writing at same time - needed more if the cleanup is running
		flock (CACHEFILE, LOCK_EX) or do {close (CACHEFILE); return;}; # Just return if this fails, as the cleanup script may be running and taking too long
		print CACHEFILE time." $ipaddr\n";
		close (CACHEFILE);
	}
}

# If don't pass tests in check_qal then close
#Quizlib::ActiveSec::check_qal($denyfile, $seccache, $seclogfile, $ipaddr);
# First checks if not allowed because of deny file, then checks the cache file to see if it
# needs adding to the denyfile
sub check_qal
{
	my ($denyfile, $seccache, $seclogfile, $ipaddr) = @_;
	
	#open (DEBUG, ">>/var/quiz/test.debug");
	
	
	my $currenttime = time; 
	my $numlogins = 0;
	my ($denytime, $null, $cachetime);

	# First add the current entry to the cache
	add_sec_cache($seccache, $ipaddr);
	
	# Check see if denyfile contains this address
	if (open (DENYIN, $denyfile))
	{
		while (<DENYIN>)
		{
			if (/ $ipaddr$/)
			{
				#print DEBUG "In Outfile";
				($denytime, $null) = split / /, $_;
				if (($denytime+$blocktime) > $currenttime) 
				{
					#print DEBUG "- Denied - $denytime, $blocktime = ".($denytime+$blocktime)." < $currenttime\n";
					close (DENYIN); 
					sec_fail ("Too many attempted logins, possible DOS attack", $ipaddr, $seclogfile);
				}
			#print DEBUG "Not Denied\n";
			}
		}
		close (DENYIN);
	}
		
	# Not in denyfile so try cache and see how many times they've logged in
	open (CACHEIN, $seccache) || return;
	while (<CACHEIN>)
	{
		if (/ $ipaddr$/)
		{
			($cachetime, $null) = split / /, $_;
			#print DEBUG "$ipaddr - $cachetime - $currenttime - $numlogins\n";
			if (($cachetime + $countperiod) > $currenttime) {$numlogins++;}
		}
	}
	close (CACHEIN);
	
	if ($numlogins > $maxlogins) 
	{
		if (open (DENYOUT, ">>$denyfile"))
		{
			# Lock file so we don't get two people writing at same time - needed more if the cleanup is running
			flock(DENYOUT, LOCK_EX) or do {close (CACHEFILE); return;}; # Just return if this fails, as the cleanup script may be running and taking too long
			print DENYOUT "$currenttime $ipaddr\n";
			close (DENYOUT);
		}
	sec_fail ("Too many attempted logins, possible DOS attack", $ipaddr, $seclogfile);
	}
# If we reach here then no security concerns we can exit
}


# Sec_fail which should then call Quizlib::Errors::Logerrors("error message"); to log in normal error log and close
# This sub outputs an error message, logs in errorlog and security log and then exits 
sub sec_fail
{
my ($errormsg, $ipaddr, $seclogfile) = @_;

# Error message
print header();
print start_html("Security Error");

print "<h1>A Security Violation has Occurred</h1>\n";
print "A potential security violoation has occured.<br>\n";
print "$errormsg<p>\n";
print "The administrator has been notified of this event<p>\n";
print "If the alert states this is a suspected DOS attack, then please wait 10 minutes before trying again.<br />\n";
print 'If you believe that this alert is an error please email <a href="mailto:quizsecurity@watkissonline.co.uk">quizsecurity@watkissonline.co.uk</a>';
print "<p>&nbsp;</p>\n";
print "<a href=\"index.pl\">Return to the Quiz Index page.</a></p>";
print "\n";

# End HTTP
print end_html();

if (open (SECLOG, ">>$seclogfile"))
	{
	print SECLOG time." $ipaddr $errormsg\n";
	close (SECLOG);
	}


# Use standard logging and email subs
Quizlib::Errors::log_errors("$errormsg from $ipaddr");	
Quizlib::Errors::error_email ("ActiveSec", "Security Violation - $errormsg, from $ipaddr", 1, 0);
}

