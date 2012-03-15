// Javascript file for wquiz functions
/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2012

This file is part of wQuiz.

wQuiz is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

wQuiz is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wQuiz.  If not, see <http://www.gnu.org/licenses/>.
**/


// On Initial Offline page (after selecting appropriate quiz) create popup windows or revert to static if file
// type = questions / answers
function offlinePrint (type_page, printfile)
{
	var popUp = window.open(printfile+"?type=popup", 'offline_'+type_page, 'menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no,scrollbars=yes');	
	var start_element = document.getElementById(start_element_id);
	// make sure that the window has been opened - if not go back to non-javascript version with message
	if (popUp == null || typeof(popUp)=='undefined')
	{
		// note that the CSS_ID_FORM and CSS_ID_OFFLINE_START are hardcoded as it's in php settings rather than javascript
		var static_html_msg = '<p>Pop-ups need to be enabled for the automated print buttons. Either enable pop-ups and reload this page or click on the button below</p>\n';
		//static_html += '<form id="wquiz-form" method="post" action="'+printfile+'">\n<input type="hidden" name="type" name="basic" />\n<input type="submit" value="Offline questions" name="start" />\n</form>\n';	
		
		//var start_element = document.getElementById("$start_element_id");
		start_element.innerHTML = static_html_msg+static_html;
		
	}
	// otherwise we start print
	else
	{
		popUp.print();
	}
}


// On Initial Offline page (after selecting appropriate quiz) - enable appropriate active menus
function offlineEnableActive ()
{
	var start_element = document.getElementById(start_element_id);
	start_element.innerHTML = active_html;
}



// Onload event handler 
// look for variable being set rather than looking at document name
//window.addEventListener("load", eventWindowLoaded, false);
function eventWindowLoaded ()
{
	if (typeof (page) != 'undefined' && page == "offline" && page_status == "printquiz")
	{
		offlineEnableActive();
	}
}




if (window.addEventListener)
{
	window.addEventListener('load', eventWindowLoaded, false);
}
else if (window.attachEvent)
{
	window.attachEvent('onload', eventWindowLoaded);
}
else 
{
	window.onload = chain(window.onload, eventWindowLoaded);
}

