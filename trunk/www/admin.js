// Javascript file for admin functions



//Answer hint
// javascript does not support associative arrays, but this object works in a similar way
var answer_hints = {
	'radio':'Radio element number counting from 0', 
	'number':'Number min,max',
	'text':'Text Perl regexp without /;',
	'TEXT':'TEXT Perl regexp without /;',
	'checkbox':'Checkbox digits counting from 0'
};


function changeTextHint()
{
	var type_element = document.getElementById("type");
	var selected_index = type_element.selectedIndex;
	document.getElementById('wquiz-edit-hint-answer').innerHTML = answer_hints[type_element.options[selected_index].value];
}



function setupAdmin()
{
	// if we have a type option we display hints (eg. edit)
	var type_element = document.getElementById("type");
	if (type_element != null)
	{
		// set initial text
		changeTextHint();
		// register listener for changes
		type_element.addEventListener("change", changeTextHint);
	}
	
}

window.onload = setupAdmin;


