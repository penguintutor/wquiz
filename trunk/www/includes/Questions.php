<?php
/*** 
For dealing with multiple questions
*/
///****** Not currently used **** Do not use ********//
/*
// Is this used?
***/





class Questions
{
	// These are all named the same as the table fields
	private $question_objects;
    

    public function __construct () 
    {

    }
    
    // returns an array
    // $answer is the answer provided by the customer
    // $answer = -1 means unanswered
    public function count () 
    {
    	return (count($this->question_objects));
    }
    
    
    public function addQuestion ($question) 
    {
    	$this->question_objects[] = $question;
    }
    
    
    
}
?>
