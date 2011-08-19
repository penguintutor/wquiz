#!/usr/bin/perl -w

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::AdminSession;

use strict;

my $template = "templates/adminindex.html";
my $page = "admin/adminindex.pl";

# Default Values - can be overridden in quiz.cfg
our $adminsessiontimeout = 240;
our (%quiznames, @category, %numquestions, @csscategories, %cssfile, %cssextra, %cssindex, $dbname, $dbuser, $dbpass, $dbsessiontable);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "../quiz.cfg" or die "Error loading quiz.cfg";

# Verify login
# First make sure we have the cookie - otherwise go to login page
my $cgiQuery = CGI::new();
my $session = $cgiQuery->cookie (-name=>'quizadminsession', -expires=>'+4h');
if (!defined $session || $session eq "") {redirect ("index.pl?status=2"); exit 0;}
# Now check that logged in user is valid
if (!Quizlib::AdminSession::check_login ($session, $dbname, $dbuser, $dbpass, $dbsessiontable, $adminsessiontimeout)) {redirect ("index.pl?status=3"); exit(0);}


open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	# Enter variables into template
	print;
	}
	
close (TEMPLATE);


