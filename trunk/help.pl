#!/usr/bin/perl -w
# Shows help screen

use CGI qw(:standard);
use Quizlib::Security;
use Quizlib::Errors;
use Quizlib::Misc;
use Quizlib::Qdb;

use strict;

my $template = "templates/help.html";
my $page = "help.pl";

my $i;


# Just load version & style from quiz.cfg
our ($version, @csscategories, %cssfile, %cssindex, $headerfile, $footerfile);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "quiz.cfg" or die "Error loading quiz.cfg";

# Allow alternate CSS using the style param
my $given_style = param ("style");
if (!defined $given_style || $given_style eq "") {$given_style=$csscategories[0];}
my $style = Quizlib::Security::chk_from_list ($page, "style", $given_style, @csscategories);

my $css = $cssfile{$style};
my $urlextra = "&amp;style=$style";

# sections is list of sections titles are the summary titles for the menu and text holds the entries
our (@sections, %titles, %text);
# This is the one thing we don't error handle properly - if this fails then we can't find who to email etc.
do "help.cfg" or die "Error loading help.cfg";

# Load name & quiz name from form & security check
my $given_section = param ("section");
if (!defined $given_section || $given_section eq "") {$given_section="default";}
my $section = Quizlib::Security::chk_from_list ($page, "section", $given_section, @sections);

# Generate Menu
my $menu_out = " : ";
for ($i = 0; $i < scalar @sections; $i++)
	{
	# If this then don't include url & use different stylesheet
	if ($section eq $sections[$i]) {$menu_out .= "<font class=\"current\">".$titles{$section}."</font> : ";}
	else {$menu_out .= "<a href=\"help.pl?section=$sections[$i]&amp;style=$style\"><font class=\"other\">".$titles{$sections[$i]}."</font></a> : ";}
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
	s/\%\%footer\%\%/$footertext/g;
	s/\%\%header\%\%/$headertext/g;
	s/\%\%helptext\%\%/$text{$section}/g;
	s/\%\%index\%\%/$cssindex{$style}/g;
	s/\%\%menu\%\%/$menu_out/g;
	s/\%\%version\%\%/$version/g;
	
	print;
	}
	
close (TEMPLATE);


