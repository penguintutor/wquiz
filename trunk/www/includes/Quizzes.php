<?php
/*** 
Handles the overall quiz setups for multiple quizzes
eg. Menu page for quizzes available
***/

require_once ($include_dir."Quiz.php");



class Quizzes
{
	// These are all named the same as the table fields
	// defined as static as when we export the object we need
	// to be able to use it outside of this class without risk of garbage collection
	private static $quiz_objects;
    

	// empty constructor - we add objects afterwards
    public function __construct () 
    {


    }
    
    
    // sort based on priority, secondary quizname
    private function cmpObj($a, $b)
    {
    	if ($a->priority == $b->priority) 
    	{
    		return ($a->quizname < $b->quizname) ? -1 : 1;
    	}
    	return ($a->priority < $b->priority) ? -1 : 1;
    }
    
    
    
    public function count ()
    {
    	return (count($this->quiz_objects));	
    }
    

    public function addQuiz ($quiz) 
    {
    	$this->quiz_objects[] = $quiz;
    }
    
    // use to order objects - if required (eg menu)
    private function _sort()
    {
    	usort ($this->quiz_objects, array("Quiz", "cmpObj"));
    }
    
	// returns a quiz object so that it can be accessed directly through Quiz class
	public function getQuiz ($quizname)
	{
    	// run through all questions and look for quizname matching
    	foreach ($this->quiz_objects as $this_object)
    	{
    		if ($this_object->getQuizname() == $quizname) {return $this_object;}
    	}
	}
	
	// Returns has array
	// key = quizname (db unique entry)
	// value = title
	public function getQuizNameArray()
	{
		
	}


   
    public function validateQuizname ($quizname)
    {
    	// run through all questions and look for quizname matching
    	foreach ($this->quiz_objects as $this_object)
    	{
    		if ($this_object->getQuizname() == $quizname) {return true;}
    	}
    	// reach here then we haven't found the entry
    	return false;
    }

    // returns an option list
    // parameter required is "online", "offline", "any" or "all"
    // note that all includes any that are disabled for both online and offline
	public function htmlSelect ($use)
	{
		$return_string = '<select id="'.CSS_ID_OPTION_QUIZ.'" name="quizname">\n';
		// sort first
		$this->_sort();
		// includes formatting of table input fields - but no other html
		foreach ($this->quiz_objects as $this_object)
		{
			if (!$this_object->isEnabled($use)) {continue;}
			$return_string.="\t<option value=\"".$this_object->getQuizname()."\">".$this_object->getTitle()."</option>\n";
		}
		$return_string .= "</select>\n";
		return ($return_string);
	}
    
    
}
?>

