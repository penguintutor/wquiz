// Javascript file for admin functions
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


