#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Misc;
use Quizlib::AdminSession;

use strict;

my $template = "templates/index.html";
my $page = "admin/index.pl";

# Set some variables to ensure we don't get warnings.
our ($adminuser, $adminpass, $dbname, $dbuser, $dbpass, $dbsessiontable);
our $keylen = 20;
do "../quiz.cfg" or die "Error loading quiz.cfg";

# See if we have values already included
my $given_name = param("user");
my $given_password = param("password");

my $message = "";

# If reason is anything other than 0 then it means we've been redirected either from here or another page
# 1 = failed login, 2 = from another page, 3 = invalid session, 4 = logout  
my $reason = param("status");
if (!defined $reason || $reason eq "") {$reason = 0;}
$reason = Quizlib::Security::chk_num_range ($page, "reason", $reason, 0, 2);


# Test if this is an actual login - or if we need to show login page
if ($reason == 0 && defined $given_name && $given_name ne "" && defined $given_password && $given_password ne "")
	{
	# Security Check variables
	my $checked_name = Quizlib::Security::chk_string ($page, "user", $given_name);
	my $checked_password = Quizlib::Security::chk_string ($page, "password", $given_password);

	# See if name & password correct
	if ($checked_name eq $adminuser && $checked_password eq $adminpass)
		{
		#Login successful
		
		# Setup a sessonkey to maintain authorised login
		my $session_key = Quizlib::Misc::gen_random_key ($keylen);
		
		# # Setup a memory cache with details
		# my $cache = get_cache_handle();
		# $cache->set("0".$session_key, "1");
		Quizlib::AdminSession::setsession($session_key, "Admin", "Admin", $dbname, $dbuser, $dbpass, $dbsessiontable);

		# Create Cookie to maintain session
		my $cgiQuery = CGI::new();
		# Do not set expiry for the cookie - that way it will last until browser is closed down - so we don't need to refresh cookie
		my $cookie1 = $cgiQuery->cookie (-name=>'quizadminsession', -value=>$session_key);

		# If we want to log successful logins add here (not currently implemented)
		# Now redirect to the adminindex.pl page
		# Redirect will send the header information including the cookie
		print redirect(-location => "adminindex.pl", -cookie => [$cookie1], -method => 'get');
		exit(0);
		}
	else
		{
		# If we want to log failures add here (not currently implemented)
		print redirect("index.pl?status=1"); 
		exit(0);
		}

	}		
	
if ($reason == 1) {$message = "<h3>Failed Login Please Contact the System Adminstrator</h3>";}
if ($reason == 2) {$message = "<h3>Session not logged in<br />Please Login.</h3>";}
if ($reason == 4) {$message = "<h3>User Logged Out</h3>";}

# If we get here then we are not logged (as we would have redirected) in so show login page
my $form = << "FORM";

<h2>Login</h2>

$message

<p>
Please login:
</p>
<form method="post" action="index.pl">
<pre>
Username: <input type="text" name="user" />
Password: <input type="password" name="password" /> <input type="submit" value="Login" />
</pre>
</form>


FORM



open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	# Enter variables into template
	s/%%login%%/$form/;
	print;
	}
	
close (TEMPLATE);




