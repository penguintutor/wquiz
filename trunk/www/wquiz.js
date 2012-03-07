// Javascript file for wquiz functions


// type = questions / answers
function offlinePrint (type, printfile)
{
	var popUp = window.open(printfile+"?type=popup", 'offline '+type, 'menubar=no,height=600,width=800,resizable=yes,toobar=no,status=no');	
	// make sure that the window has been opened - if not go back to non-javascript version with message
	if (popUp == null || typeof(popUp)=='undefined')
	{
		// note that the CSS_ID_FORM and CSS_ID_OFFLINE_START are hardcoded as it's in php settings rather than javascript
		var static_html = '<p>Pop-ups need to be enabled for the automated print buttons. Either allow pop-ups or click on the button below</p>\n';
		static_html += '<form id="wquiz-form" method="post" action="'+printfile+'">\n<input type="hidden" name="type" name="basic" />\n<input type="submit" value="Offline questions" name="start" />\n</form>\n';	
		
		//var start_element = document.getElementById("$start_element_id");
		start_element.innerHTML = static_html;
		
	}
	// otherwise we start print
	else
	{
		popUp.print();
	}
}


//window.onload = setup;


