<?php
/*** 
Handles the question
including formatting / checking
***/





class Question
{
	// question is not used in the form instead we use question_num as that is position in quiz
	// question is used if we are editing the question
    private $questionid = ''; // question number - named same as mysql field name
    private $intro = '';
    private $input = '';
    private $type = '';
    private $answer = '';	// this is answer formatting rather than current value
    private $reason = '';
    private $reference = '';
    private $hint = '';
    private $image = '';
    private $comments = '';
    private $qfrom = '';
    private $email = '';
    private $created = '0000-00-00';
    private $reviewed = '0000-00-00';
    // This is an array of quizzes that question is included in (usuing quizname rather than id)
    private $quizzes = array();
    
    // num of chars in summary
    // moved to settings - must be specified when calling summary_length
//    private $summary_length = 45; 

	// normally create instance with details, but set to null in case 
	// creating a new one (eg. new question)
	// defaults are set to empty strings above    
    public function __construct ($db_results=null) 
    {
    	// if provided with array use it to initialise variables
    	if (is_array ($db_results) && isset($db_results['questionid']))
    	{
			$this->questionid = $db_results['questionid'];	
			$this->intro = $db_results['intro'];
			$this->input = $db_results['input'];
			$this->type = $db_results['type'];
			$this->answer = $db_results['answer'];		
			$this->reason = $db_results['reason'];
			$this->reference = $db_results['reference'];
			$this->hint = $db_results['hint'];
			$this->image = $db_results['image'];
			$this->comments = $db_results['comments'];
			$this->qfrom = $db_results['qfrom'];
			$this->email = $db_results['email'];
			$this->quizzes = $db_results['quizzes'];
			$this->created = $db_results['created'];
			$this->reviewed = $db_results['reviewed'];
    	}

    }
    
    // returns an array
    // $answer is the answer provided by the customer
    // $answer = -1 means unanswered
    public function getHtmlString ($answer) 
    {
    	print "<div id=\"".CSS_ID_QUESTION."\">\n\t<p class=\"".CSS_CLASS_QUESTION_P."\">\n\t\t";
    	// Image is placed at the start of the text (can be moved using CSS)
    	print $this->formatImageString ();
    	print $this->intro;
    	print "</p>\n";
    	// hidden entry with this question number
    	print "<p id=\"".CSS_ID_QUESTION_INPUT."\">\n";
    	// question number is added in navigation as it's navigation position - not the sql question id
    	//print "<input type=\"hidden\" name=\"question\" value=\"".$this->questionid."\" />\n";
    	// handle appropriate format depending upon question
    	print $this->formatQuestion($answer);  
    	print "\n</p>\n</div>\n";

    }


    // same as HTML String, but returns it in a format for offline viewing
    public function getOfflineHtmlString () 
    {
    	print "<div id=\"".CSS_ID_QUESTION."\">\n\t<p class=\"".CSS_CLASS_QUESTION_P."\">\n\t\t";
    	// Image is placed at the start of the text (can be moved using CSS)
    	print $this->formatImageString ();
    	print $this->intro;
    	print "</p>\n";
    	// hidden entry with this question number
    	print "<p id=\"".CSS_ID_QUESTION_INPUT."\">\n";
    	// question number is added in navigation as it's navigation position - not the sql question id
    	//print "<input type=\"hidden\" name=\"question\" value=\"".$this->questionid."\" />\n";
    	// handle appropriate format depending upon question
    	print $this->formatOfflineQuestion();  
    	print "\n</p>\n</div>\n";

    }
    
    
    
    // gives a brief summary based on the introduction text (truncated)
    // if > $summary_length chars then return trunc ...
    public function getSummary($summary_length)
    {
    	if (strlen($this->intro) > $summary_length) 
    	{
    		return (substr($this->intro, 0, $summary_length-4)." ...");
    	}
    	else {return $this->intro;}
    }


    // return type of question (eg. radio / checkbox / text)
    public function getQuestionID ()
    {
    	return $this->questionid;
    }
    
    // return type of question (eg. radio / checkbox / text)
    public function getType ()
    {
    	return $this->type;
    }
    
    
    public function getReason()
    {
    	return $this->reason;
    }
    
   
    public function getUpdated()
    {
    	return $this->updated;
    }
    
    // return a string listing quizzes
    public function getQuizzes ()
    {
    	if (count($this->quizzes) < 1) {return "";}
    	$return_string = $this->quizzes[0];
    	for ($i = 1; $i < count($this->quizzes); $i++)
    	{
    		$return_string .= ",".$this->quizzes[$i];
    	}
    	return $return_string;
    }
    
    // return the quizzes as an array
    public function getQuizArray ()
    {
    	return $this->quizzes;
    }
    
    // returns true if this question is part of this quiz
    public function isInQuiz ($check_quiz)
    {
    	if (in_array($check_quiz, $this->quizzes)) {return true;}
    	else {return false;} 
    }

    
    public function getAnswer()
    {
    	return $this->answer;
    }

    public function getInput()
    {
    	return $this->input;
    }        
    
    public function getCreated()
    {
    	return $this->created;
    }
    
    public function getReviewed()
    {
    	return $this->reviewed;
    }

    public function getReference()
    {
    	return $this->reference;
    }
    
    public function getIntro()
    {
    	return $this->intro;
    }

    public function getHint()
    {
    	return $this->hint;
    }

    public function getImage()
    {
    	return $this->image;
    }

    public function getComments()
    {
    	return $this->comments;
    }

    public function getQfrom()
    {
    	return $this->qfrom;
    }
    
    public function getEmail()
    {
    	return $this->email;
    }
    
    
    // validates the type against the type in the post
    // note that the type in the post will be text even for number etc.
    public function validateType ($post_type)
    {
    	// simplist - checkbox / radio / text will all match
    	if ($post_type == $this->type) {return true;}
    	// if type is number / TEXT 
    	if ($post_type == 'text' && ($this->type == 'number' || $this->type == 'TEXT')) {return true;}
    	// if not returned then invalid type
    	return false;
    }
    
    // check that the answer is valid - not that it is correct!
    // ie. for a number - must be a number, radio must be a valid character
    public function validateAnswer ($answer)
    {
    	//print ("Answer is $answer \nType is ".$this->type." \n");
    	if ($this->type == 'number' && is_numeric($answer))
    	{
    		return true;
    	}
    	else if ($this->type == 'radio' && is_numeric($answer)) 
    	{
    		$options = explode (",", $this->input);
    		if ($answer >=0 && $answer < count($options)) {return true;}
    		else {return false;}
    	}
    	else if ($this->type == 'text' || $this->type == 'TEXT')
    	{
    		// we don't do any further checking - we use mysql escape to save and use regexp to check valid answer
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    	
    }
    
    
    private function formatImageString ()
    {
    	// If image is blank then we don't return anything - if rather use a dummy image then that should be added to each question.
    	if ($this->image == '') {return "";}
    	else
    	{
    		return ("<img src=\"$this->image\" class=\"".CSS_CLASS_IMAGE."\" alt=\"Question Image\"/>\n");
    	}
    }
    
    // answer is the current value
    private function formatQuestion ($answer)
    {
    	$formatted = '';
    	switch ($this->type)
    	{
    		case 'radio':  	$formatted = $this->createFormRadio ($answer);
    						break;
    		case 'checkbox':$formatted = $this->createFormCheckbox ($answer);
    						break;
    		case 'number':
    		case 'text':   	
    		case 'TEXT':	$formatted = $this->createFormText ($answer); // TEXT is text but case sensitive - same form formatting
    						break;
			default:	// unknown question - this is a warning level - don't break, but 
							$err =  Errors::getInstance();
    						$err->errorEvent(WARNING_QUESTION, "Warning, unknown question type for $this->questionid");
    	}
    	return $formatted;
    }


    private function formatOfflineQuestion ()
    {
    	$formatted = '';
    	switch ($this->type)
    	{
    		case 'radio':  	
    		case 'checkbox':
    			$formatted .= "<ul>\n";
    			$options = explode (",", $this->input);
    			for ($i=0; $i<count($options); $i++)
    			{
    				$formatted .= "<li>$i</li>\n";
    			}
    			$formatted .= "</ul>\n";
    			break;
    		case 'number':
    		case 'text':   	
    		case 'TEXT':	
    			$formatted .= "<ul><li>";
    			$options = explode (",", $this->input);
    			// if blank replace with number of spaces
    			// normally css should set it to underline or similar
    			if ($options[1] == '') {$options[1] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";}
				$formatted .=  $options[0]." <span class=\"".CSS_CLASS_OFFLINE_QUESTION_ANSWER."\">".$options[1]."</span> ".$options[2]."</li></ul>\n"; 			
    			break;
			default:	// unknown question - this is a warning level - don't break, but 
							$err =  Errors::getInstance();
    						$err->errorEvent(WARNING_QUESTION, "Warning, unknown question type for $this->questionid");
    	}
    	return $formatted;
    }

    
    
    private function createFormRadio ($answer)
    {
    	//print "Form button answer is $answer \n";
    	$form_string = "<input type=\"hidden\" name=\"type\" value=\"radio\">\n";
    	$options = explode (",", $this->input);
    	for ($i=0; $i<count($options); $i++)
    	{
    		$form_string .= "<input type=\"radio\" name=\"answer\" value=\"$i\" ";
    		if ($i == $answer) {$form_string.= "checked=\"checked\" ";}
    		$form_string .= "/>".$options[$i]."<br />\n";
    	}
    	return $form_string;
    }
    
    // note $labels must be ,, if empty
    private function createFormText ($answer)
    {
    	$form_string = "<input type=\"hidden\" name=\"type\" value=\"text\">\n";
    	$labels = explode (',', $this->input);
    	// use autocomplete option instead of random string used in earlier version
    	// this is html 5 only (but works in earlier versions even though incorrect)
    	// pre-text
    	$form_string .= $labels[0];
    	$form_string .= "<input type=\"text\" name=\"answer\" autocomplete=\"off\" value=\"";
    	// if not answered show default, otherwise show current
    	if ($answer != -1) {$form_string.= $answer;}
    	else {$form_string .= $labels[1];}
    	$form_string .= "\" />";
    	// post-text
    	$form_string .= $labels[2];
    	return ($form_string);
    }
    
    private function createFormCheckbox ($answer)
    {
    	//print "Answer is $answer<br />\n";
    	// if answer is -1 set to '' so does not match
    	if ($answer == -1) {$answer = '';}
    	$form_string = "<input type=\"hidden\" name=\"type\" value=\"checkbox\">\n";
    	$options = explode (",", $this->input);
    	for ($i=0; $i<count($options); $i++)
    	{
    		$form_string .= "<input type=\"checkbox\" name=\"answer-$i\" ";
    		// make int into a string so that it can be used in strpos search
    		$i_string = ''.$i;
    		// if number is in the answer already
    		// use === type comparison as 0 is the first character
    		if (strpos ($answer, $i_string)!==false) {$form_string.= "checked=\"checked\" ";}
    		$form_string .= "/>".$options[$i]."<br />\n";
    	}
    	return $form_string;
    }

    
    // checks to see if an answer is correct or incorrect
    // returns true (correct) or false
    function markAnswer ($answer)
    {
    	// radio / checkbox - answer must be same as 
    	if ($this->type == 'radio' || $this->type == 'checkbox')
    	{
    		if ($answer == $this->answer) {return true;}
    		else {return false;}
    	}
    	elseif ($this->type == 'number')
    	{
    		// split answer into min max
    		$min_max = explode (',', $this->answer);
    		if ($answer >= $min_max[0] && $answer <= $min_max[1]) {return true;}
    		else {return false;}
    	}
    	elseif ($this->type == 'text')
    	{
    		if (preg_match('/^'.$this->answer.'$/i', $answer)) {return true;}
    		else {return false;}
    	}
    	// as text, but without ignore case
    	elseif ($this->type == 'TEXT')
    	{
    		if (preg_match('/^'.$this->answer.'$/', $answer)) {return true;}
    		else {return false;}
    	}
    	// invalid type
    	else 
    	{
    		// error in question configuration
    		$err =  Errors::getInstance();
    		$err->errorEvent(WARNING_QUESTION, "Warning, unknown question type for $this->questionid");
    		return false;
    	} 
    	
    }
    
    
}
?>
