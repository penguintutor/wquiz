# Security checks for valid entries in values (prevents buffer overflow type attacks / invalid URL attacks).

package Quizlib::Security;

use CGI qw(:standard);
use Quizlib::Errors;

use strict;

my $verbose = 1;


sub text_2_html
{
my ($page, $parm, $value) = @_;

# First a quick test - if we only have letters digits or spaces then just return
# otherwise we perform a lot of unneccessary checks
if ($value =~ /^[\w ]*$/) {return $value;}

$value =~ s/\&/\&amp;/g;
$value =~ s/\'/\&apos;/g;
$value =~ s/\"/\&quot;/g;
$value =~ s/\,/\&#184;/g;

# Accented Characters etc.
$value =~ s/�/\&Yuml;/g;
$value =~ s/�/\&Agrave;/g;
$value =~ s/�/\&Aacute;/g;
$value =~ s/�/\&Acirc;/g;
$value =~ s/�/\&Atilde;/g;
$value =~ s/�/\&Auml;/g;
$value =~ s/�/\&Aring;/g;
$value =~ s/�/\&AElig;/g;
$value =~ s/�/\&Ccedil;/g;
$value =~ s/�/\&Egrave;/g;
$value =~ s/�/\&Eacute;/g;
$value =~ s/�/\&Ecirc;/g;
$value =~ s/�/\&Euml;/g;
$value =~ s/�/\&Igrave;/g;
$value =~ s/�/\&Iacute;/g;
$value =~ s/�/\&Icirc;/g;
$value =~ s/�/\&Iuml;/g;
$value =~ s/�/\&Ograve;/g;
$value =~ s/�/\&Oacute;/g;
$value =~ s/�/\&Ocirc;/g;
$value =~ s/�/\&Otilde;/g;
$value =~ s/�/\&Ouml;/g;
$value =~ s/�/\&Ugrave;/g;
$value =~ s/�/\&Uacute;/g;
$value =~ s/�/\&Ucirc;/g;
$value =~ s/�/\&Uuml;/g;
$value =~ s/�/\&Yacute;/g;
$value =~ s/�/\&szlig;/g;
$value =~ s/�/\&agrave;/g;
$value =~ s/�/\&aacute;/g;
$value =~ s/�/\&acirc;/g;
$value =~ s/�/\&atilde;/g;
$value =~ s/�/\&auml;/g;
$value =~ s/�/\&aring;/g;
$value =~ s/�/\aelig;/g;
$value =~ s/�/\ccedil;/g;
$value =~ s/�/\&egrave;/g;
$value =~ s/�/\&eacute;/g;
$value =~ s/�/\&ecirc;/g;
$value =~ s/�/\&euml;/g;
$value =~ s/�/\&igrave;/g;
$value =~ s/�/\&iacute;/g;
$value =~ s/�/\&icirc;/g;
$value =~ s/�/\&iuml;/g;
$value =~ s/�/\&ntilde;/g;
$value =~ s/�/\&ograve;/g;
$value =~ s/�/\&oacute;/g;
$value =~ s/�/\&ocirc;/g;
$value =~ s/�/\&ouml;/g;
$value =~ s/�/\&ugrave;/g;
$value =~ s/�/\&uacute;/g;
$value =~ s/�/\&ucirc;/g;
$value =~ s/�/\&uuml;/g;
$value =~ s/�/\&yacute;/g;
$value =~ s/�/\&yuml;/g;


#- other special chars

return $value;
}

# Allow html character encoding (not hyperlinks)
sub chk_string_html
{
my ($page, $parm, $value) = @_;
# Simple check only allow those listed in []
if ($value =~ /^[,\w !%\+\(\)\.\-_\;\@\&\#]*$/) { return $value;}
else {exit_parm_error ($page, $parm, $value);}
}



sub chk_string
{
my ($page, $parm, $value) = @_;
# Simple check only allow those listed in []
if ($value =~ /^[,\w !% \+\(\)\.\-_]*$/) { return $value;}
else {exit_parm_error ($page, $parm, $value);}
}

sub chk_alpnum
{
my ($page, $parm, $value) = @_;
if ($value =~ /\w*/) { return $value;}
else {exit_parm_error ($page, $parm, $value);}
}

sub chk_num
{
my ($page, $parm, $value) = @_;

# Simple check that this is a valid number
# Add 1 then subtract it - potentially could break, but at least it should fail gracefully
my $temp = $value;
$value = $value + 1;
$value = $value - 1;

if ($value == $temp) {return $value;}

else {exit_parm_error ($page, $parm, $value);}
}



sub chk_num_range
{
my ($page, $parm, $value, $min, $max) = @_;

# Simple check that this is a valid number
# Add 1 then subtract it - potentially could break, but at least it should fail gracefully
my $temp = $value;
$value = $value + 1;
$value = $value - 1;
if ($value == $temp)
	{
	# Now checked it's a number - check it's within range
	if ($value >= $min && $value <= $max) {return $value;};
	}

else {exit_parm_error ($page, $parm, $value);}
}


sub chk_date_iso
{
my ($page, $parm, $value) = @_;

if ($value =~ /^\d{4}-\d{2}-\d{2}$/) {return $value;}
else {exit_parm_error ($page, $parm, $value);}
}


# Only allow those that match entries on this list
sub chk_from_list
{
my ($page, $parm, $value, @options) = @_;
my $i;
my $option;
foreach $option (@options)
 {
 if ($value =~ /^$option$/) {return $option;}
 }

exit_parm_error ($page, $parm, $value);
}


# Error Messages
############

# Display error and exits
sub exit_parm_error
{
my ($page, $parm, $value) = @_;

Quizlib::Errors::check_ignore_error();

# Error message
print header();
print start_html("Error");
 
print "<h1>Error</h1>\n";
print "An invalid parameter was sent to the server<br>\n";
print "$parm = $value is not a valid entry<p>\n";
print "Caught by the security checking process<p>\n";
print "This has been reported to the system administrator<p>\n";
print "<a href=\"index.pl\">Return to the Quiz Index Page.</a></p>";
print "\n";
 
# End HTTP
print end_html();

Quizlib::Errors::error_email ($page, "Invalid Parm Error parm=$parm, value=$value", 3, 0);
}


# Display error and exits
sub missing_parm_error
{
my ($page, $parm) = @_;
 
Quizlib::Errors::check_ignore_error();

 # Error message
print header();
print start_html("Error");

print "<h1>Error</h1>\n";
print "A parameter was missing in the request to the server<br>\n";
print "$parm is a mandatory parameter<p>\n";
print "Caught by the security checking process<p>\n";
print "This has been reported to the system administrator<p>\n";
print "<a href=\"index.pl\">Return to the quiz index page</a></p>";
print "\n";

# End HTTP
print end_html();

Quizlib::Errors::error_email ($page, "Missing Parm Error parm=$parm", 3, 0);
}

# Internal subs
############

# Internal sub to convert a single digit to 2 (prefixed with a zero)
sub _to_2_digits
{
my ($num) = @_;
if ($num < 10) {$num = "0".$num;}
return $num;
}



