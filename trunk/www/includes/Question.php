<?php
/*** 
Handles the question
including formatting / checking
***/





class Question
{
	// These are all named the same as the table fields
	private $question_num = 0;
	// question is not used in the form instead we use question_num as that is position in quiz
	// question is used if we are editing the question
    private $questionid = ''; // question number - named same as mysql field name
    private $intro = '';
    private $input = '';
    private $type = '';
    private $answer = '';
    private $reason = '';
    private $image = '';
    // This is an array of quizzes that question is included in (usuing quizname rather than id)
    private $quizzes = array();
    

    // question_num is our position in quiz - irrelevant (0) if not actually doing quiz
	// normally create instance with details, but set to null in case 
	// creating a new one (eg. new question)
	// defaults are set to empty strings above    
    public function __construct ($question_num=null, $db_results=null) 
    {
    	if ($question_num != null) {$this->questionid = $question_num;}
    	// if provided with array use it to initialise variables
    	if (is_array ($db_results) && isset($db_results['questionid']))
    	{
			$this->questionid = $db_results['questionid'];	
			$this->intro = $db_results['intro'];
			$this->input = $db_results['input'];
			$this->type = $db_results['type'];
			$this->answer = $db_results['answer'];
			$this->reason = $db_results['reason'];
			$this->image = $db_results['image'];
			$this->quizzes = $db_results['quizzes'];
    	}

    }
    
    // returns an array
    // $answer is the answer provided by the customer
    // $answer = -1 means unanswered
    public function getHtmlString ($answer) 
    {
    	/* form part needs to be brought outside of the question */   	
		// Generic form statement - this is before the division as the buttons are after the question div
    	//print "<form action=\"question.php\">\n";
    	print "<div id=\"".CSS_ID_QUESTION."\">\n\t<p class=\"".CSS_CLASS_QUESTION_P."\">\n\t\t";
    	// Image is placed at the start of the text (can be moved using CSS)
    	print $this->formatImageString ();
    	print $this->intro;
    	print "</p>\n";
    	// hidden entry with this question number
    	print "<p id=\"".CSS_ID_QUESTION_INPUT."\">\n";
    	print "<input type=\"hidden\" name=\"question\" value=\"".$this->questionid."\" />\n";
    	// handle appropriate format depending upon question
    	print $this->formatQuestion($answer);  
    	print "\n</p>\n</div>\n";
    	/* move form outside of the question */
    	//print "</form>\n";
    }
    
    
    private function formatImageString ()
    {
     	return ("<img src=\"$this->image\" class=\"".CSS_CLASS_IMAGE."\" alt=\"Question Image\"/>\n");
    }
    
    //- answer is the current value - need to add this functionality
    private function formatQuestion ($answer)
    {
    	$formatted = '';
    	//- also need to add number and checkbox
    	switch ($this->type)
    	{
    		case 'radio':  	$formatted = $this->createFormRadio ($this->input);
    						break;
    		case 'checkbox':$formatted = $this->createFormCheckbox ($this->input);
    						break;
    		case 'number':
    		case 'text':   	
    		case 'TEXT':	$formatted = $this->createFormText ($this->input); // TEXT is text but case sensitive - same form formatting
    						break;
			default:	// unknown question - this is a warning level - don't break, but 
							$err =  Errors::getInstance();
    						$err->errorEvent(WARNING_QUESTION, "Warning, unknown question type for $this->questionid");
    	}
    	return $formatted;
    }
    
    
    private function createFormRadio ($answer)
    {
    	$form_string = '';
    	$options = explode (",", $this->input);
    	for ($i=0; $i<count($options); $i++)
    	{
    		$form_string .= "<input type=\"radio\" name=\"answer\" value=\"$i\" ";
    		if ($i == $answer) {$form_string.= "selected=\"selected\" ";}
    		$form_string .= "/>".$options[$i]."<br />\n";
    	}
    	return $form_string;
    }
    
    // note $labels must be ,, if empty
    private function createFormText ($answer)
    {
    	$form_string = '';
    	$labels = explode (',', $this->input);
    	// use autocomplete option instead of random string used in earlier version
    	// this is html 5 only (but works in earlier versions even though incorrect)
    	// pre-text
    	$form_string = $labels[0];
    	$form_string .= "<input type=\"text\" name=\"answer\" autocomplete=\"off\" value=\"";
    	// if not answered show default, otherwise show current
    	if ($answer != -1) {$form_string.=$answer;}
    	else {$form_string.=$labels[1];}
    	$form_string .= "\" />";
    	// post-text
    	$form_string .= $labels[2];
    	return ($form_string);
    }
    
    private function createFormCheckbox ($answer)
    {
    	$form_string = '';
    	$options = explode (",", $this->input);
    	for ($i=0; $i<count($options); $i++)
    	{
    		$form_string .= "<input type=\"checkbox\" name=\"$i\" ";
    		// if number is in the answer already
    		if (strpos ($answer, $i)) {$form_string.= "checked=\"checked\" ";}
    		$form_string .= "/>".$options[$i]."<br />\n";
    	}
    	return $form_string;
    }
    
    
    
}
?>
