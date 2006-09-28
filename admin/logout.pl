#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Misc;
use Quizlib::AdminSession;
use Quizlib::QuizSession;

use strict;

# Set some variables to ensure we don't get warnings.
our ($adminuser, $adminpass, $dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $dbsettingsnumtable, $sessionexpiry);
our $keylen = 20;
do "../quiz.cfg" or die "Error loading quiz.cfg";


# First make sure we have the cookie - if not assume already logged out
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizadminsession', -expires=>'+4h');
if (!defined $session || $session eq "") {redirect ("index.pl?status=4");}
# If logout not successful then we expire cookie and redirect (to prevent this being misused for DOS)
if (Quizlib::AdminSession::logout ($session, $dbname, $dbuser, $dbpass, $dbsessiontable) != 0) 
{
	# Still expire the cookie by setting to -1day
	$cgiQuery = CGI::new();
	my $cookie1 = $cgiQuery->cookie (-name=>'quizadminsession', -value=>"", -expires=>'-1d');
	redirect ("index.pl?status=4");
}


## Perform housekeeping cleanup
# If expiry not set, use default of 4 hours
my $sessionsbeforecleanup = 0;
if (!defined $sessionexpiry || $sessionexpiry < 60) {$sessionexpiry=14400;}
Quizlib::QuizSession::remove_expired($dbname, $dbuser, $dbpass, $dbsessiontable, $dbactivetable, $dbsettingsnumtable, $sessionexpiry, $sessionsbeforecleanup);



# Expire the cookie by setting to -1day
$cgiQuery = CGI::new();
my $cookie1 = $cgiQuery->cookie (-name=>'quizadminsession', -value=>"", -expires=>'-1d');
# Redirect with the expired cookie
print redirect(-location => 'index.pl?status=4', -cookie => [$cookie1], -method => 'get');
