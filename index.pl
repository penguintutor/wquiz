#!/usr/bin/perl -w
# Request personal details and select which quiz

use CGI qw(:standard);
use Quizlib::Misc;
use Quizlib::Security;
use Quizlib::Errors;

use strict;

my $template = "templates/index.html";
my $page = "index.pl";

our (%quiznames, @category, %numquestions, @csscategories, %cssfile, %cssextra, %cssindex, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $cssextraentry = $cssextra{$style};
my $urlextra = "&amp;style=$style";

# Create an option menu for choice of quiz's
my $i;
my $optionmenu = ""; 
for ($i=0; $i < scalar @category; $i++)
	{
	# Check that category & numquestions also exist else error
	if (!defined $quiznames{$category[$i]} || !defined $numquestions{$category[$i]}) {Quizlib::Errors::config_error($page, "\%quiznames, or \%numquestions does not exist for $category[$i]");}
	# note a space is added to ensure that a small letter (e.g. 1) isn't at the edge of the menu
	$optionmenu .= "<option value=\"$category[$i]\">$quiznames{$category[$i]}&nbsp;</option>";
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
	s/\%\%css\%\%/$css/g;
	s/\%\%cssextra\%\%/$cssextraentry/g;
	s/\%\%cssurl\%\%/$style/g;
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%quiznames\%\%/$optionmenu/;
	s/\%\%urlextra\%\%/$urlextra/g;
	
	
	
	print;
	}
	
close (TEMPLATE);
