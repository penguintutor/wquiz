#!/usr/bin/perl -w
# offline quiz - ask which quiz

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;

use strict;

my $template = "templates/offline.html";
my $page = "offline.pl";

our (%quiznames, @category, %numquestions, @csscategories, %cssindex, $offlinequiz, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# First check if offlinequiz is allowed
if (!defined($offlinequiz) || !$offlinequiz) {Quizlib::Errors::offline_not_allowed();}

# Allow alternate CSS using the style param - don't use here - but maintain for future use
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

# Create an option menu for choice of quiz's
my $i;
my $optionmenu = ""; 
for ($i=0; $i < scalar @category; $i++)
	{
	# Check that category & numquestions also exist else error
	if (!defined $quiznames{$category[$i]} || !defined $numquestions{$category[$i]}) {Quizlib::Errors::config_error($page, "\%quiznames, or \%numquestions does not exist for $category[$i]");}
	
	$optionmenu .= "<option value=\"$category[$i]\">$quiznames{$category[$i]}</option>";
	}
	
# Get headers and footers from external file
my $headertext = "";
if (defined $headerfile && $headerfile ne "")
{
	$headertext = Quizlib::Misc::readhtml($headerfile);
}
my $footertext = "";
if (defined $footerfile && $footerfile ne "")
{
	$footertext = Quizlib::Misc::readhtml($footerfile);
}


open(TEMPLATE, $template) or Quizlib::Errors::fileopen_error($page, "$template", "read"); 
print header();
while (<TEMPLATE>)
	{
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%quiznames\%\%/$optionmenu/;
	s/\%\%style\%\%/$style/g;
	
	print;
	}
	
close (TEMPLATE);
