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
$value =~ s/Ÿ/\&Yuml;/g;
$value =~ s/À/\&Agrave;/g;
$value =~ s/Á/\&Aacute;/g;
$value =~ s/Â/\&Acirc;/g;
$value =~ s/Ã/\&Atilde;/g;
$value =~ s/Ä/\&Auml;/g;
$value =~ s/Å/\&Aring;/g;
$value =~ s/Æ/\&AElig;/g;
$value =~ s/Ç/\&Ccedil;/g;
$value =~ s/È/\&Egrave;/g;
$value =~ s/É/\&Eacute;/g;
$value =~ s/Ê/\&Ecirc;/g;
$value =~ s/Ë/\&Euml;/g;
$value =~ s/Ì/\&Igrave;/g;
$value =~ s/Í/\&Iacute;/g;
$value =~ s/Î/\&Icirc;/g;
$value =~ s/Ï/\&Iuml;/g;
$value =~ s/Ò/\&Ograve;/g;
$value =~ s/Ó/\&Oacute;/g;
$value =~ s/Ô/\&Ocirc;/g;
$value =~ s/Õ/\&Otilde;/g;
$value =~ s/Ö/\&Ouml;/g;
$value =~ s/Ù/\&Ugrave;/g;
$value =~ s/Ú/\&Uacute;/g;
$value =~ s/Û/\&Ucirc;/g;
$value =~ s/Ü/\&Uuml;/g;
$value =~ s/Ý/\&Yacute;/g;
$value =~ s/ß/\&szlig;/g;
$value =~ s/à/\&agrave;/g;
$value =~ s/á/\&aacute;/g;
$value =~ s/â/\&acirc;/g;
$value =~ s/ã/\&atilde;/g;
$value =~ s/ä/\&auml;/g;
$value =~ s/å/\&aring;/g;
$value =~ s/æ/\aelig;/g;
$value =~ s/ç/\ccedil;/g;
$value =~ s/è/\&egrave;/g;
$value =~ s/é/\&eacute;/g;
$value =~ s/ê/\&ecirc;/g;
$value =~ s/ë/\&euml;/g;
$value =~ s/ì/\&igrave;/g;
$value =~ s/í/\&iacute;/g;
$value =~ s/î/\&icirc;/g;
$value =~ s/ï/\&iuml;/g;
$value =~ s/ñ/\&ntilde;/g;
$value =~ s/ò/\&ograve;/g;
$value =~ s/ó/\&oacute;/g;
$value =~ s/ô/\&ocirc;/g;
$value =~ s/õ/\&ouml;/g;
$value =~ s/ù/\&ugrave;/g;
$value =~ s/ú/\&uacute;/g;
$value =~ s/û/\&ucirc;/g;
$value =~ s/ü/\&uuml;/g;
$value =~ s/ü/\&yacute;/g;
$value =~ s/ÿ/\&yuml;/g;


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



