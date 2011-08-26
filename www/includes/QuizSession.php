<?php
/*** 
Handles the PHP session - uses serialised arrays stored in the session
***/
// Note all interaction with this must be before we sent the HTML header
// handles deserialising arrays


// css tags that the customer should use in css file
// Whenever an entry is added - add this to overview.txt
define ("SESSION_STATUS_BEGIN", 0); 	// Not used means not yet initialised
define ("SESSION_STATUS_ACTIVE", 1);	// active in quiz (answering questions)
define ("SESSION_STATUS_COMPLETE", 2);	// answers marked - prevent changes - review mode
define ("SESSION_STATUS_OFFLINE", 3);	// offline mode
define ("SESSION_STATUS_ADMIN", 4);		// admin login


include ($include_dir."PHPSession.php");


class QuizSession extends PHPSession
{
	
	// parent constructor is called automatically - will create the sesion
	/*// establish session
    public function __construct () 
    {
    	parent::__construct(); 
    }*/ 
    
    
    // returns the session information as a hash array (quizname, status etc.)
    // does not return entries that are stored as a serialised array - they need to be requested seperately 
    public function getSessionInfo () 
    {
    	// first check status - if not set then return empty array
    	$session_info = array();
    	$status = $this->getValue('status');
    	
    	if (!isset($status) || !is_int ($status)) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(INFO_SESSION, "No session found"); 
    		return ($session_info);
    	}
    	$session_info['status'] = $status;
    	$session_info['quizname'] = $this->getValue('quizname');
    	
    	return ($session_info);
    }
    
    // status - track where we are
    public function setStatus ($new_status)
    {
		$this->setValue('status', $new_status);
    }
    
    
    // returns array from serialized values
    public function getQuestions () 
    {
    	return (unserialize ($this->getValue('question'))); 
    }
    

    // returns array from serialized values
    public function getAnswers ()
    {
    	return (unserialize ($this->getValue('answer')));
    }
    
    
    // serialise then store in the array
    public function setQuestions ($question_array)
    {
		$this->setValue('question', serialize($question_array));
    }

    // serialise then store in the array
    public function setAnswers ($answer_array)
    {
		$this->setValue('answer', serialize($answer_array));
    }
    
    
}
?>
