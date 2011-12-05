// Javascript file for admin functions

//Answer hint
var $answer_hints = array(
	'radio'=>'Radio element number counting from 0', 
	'number'=>'Number min,max',
	'text' =>'Text Perl regexp without /;',
	'TEXT' =>'TEXT Perl regexp without /;',
	'checkbox' =>'Checkbox digits counting from 0'
);


// Handle type selector - with hint text
var type_element = document.getElementByID("type");
if (type_element != null)
{
	type_element.onchange = changeTextHint();	
}

function changeTextHint ()
{
	var hint_element = document.getElementbyID("wquiz-edit-hint-answer");
	hint_element.text = $answer_hints[type_element.value];
}

