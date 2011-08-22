<?php
/*** 
Encapsulate requests for access to the database
This way calling code does not need to worry about building sql statements etc.
***/


class QuizDB
{
	// stores Database class instance
    private $qdb_object;
    // store table prefix - rather than get from Database Class each time
    private $table_prefix;
    // the table entries
    // note no entry for settings_num, which has been dropped in this version
    private $quiz_tables = array 
    ( 
    	'quizzes' => 'quiz_quiz',
    	'questions' => 'quiz_questions',
    	'rel' => 'quiz_questionrel',
    	//'active' => 'quiz_active',
    	//'session' => 'quiz_session',
    	'settings' => 'quiz_settings'
    );
    
    /* needs to be passed the database object for class Database */
    public function __construct ($db_object) 
    {
    	$this->db_object = $db_object;
    	$this->table_prefix = $this->db_object->getTablePrefix();
    }
    
    // Creates the select string and then calls Database
    // returns hash array of key value pairs
    public function getSettingsAll ()
    {
    	$settings = array();
    	$select_string = "select settings_key,settings_value from ".$this->table_prefix.$this->quiz_tables['settings'];
    	$settings = $this->db_object->getKeyValue ($select_string, "settings_key", "settings_value");
    	return $settings;
    }
    
    // returns an array
    public function getQuestion ($question_num) 
    {
    	
    	$question_result = $this->db_object->getRow ("Select question,intro,input,type,answer,reason,image from ".$this->table_prefix.$this->quiz_tables['questions']." where question=$question_num");
    	
    	return ($question_result);
    }


    // returns array of arrays - all questions in Quiz (category)
	// no category returns all
    public function getQuestionQuiz ($quiz="") 
    {
    	// if $quiz not specified get all questions in db (even those with no quiz)
    	// Initial sql without where - add where part later if required
    	$sql = "SELECT questionid, section, intro, input, type, answer, reason, reference, hint, image, comments, qfrom, email, created, reviewed, quizname FROM ". $this->table_prefix.$this->quiz_tables['questions']. " JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid";
    	
    	// if we limit to a quiz then handle here
    	if ($quiz!="")
    	{
    		// add a where clause - no security checking here - it needs to be done at a higher level
    		$sql .= " WHERE ".$this->table_prefix.$this->quiz_tables['rel'].".quizname=\"$quiz\"";
    	}
    	
    	// Get all the rows into a temp array - we then reformat appropriately (eg. move quizname into array instead of individual rows
    	
    	$temp_array = $this->db_object->getRowsAll ($sql);
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']); 
    	}
    	
    	// New array that we return from the function
    	$return_array = array();

    	print "DEBUG\n";
    	print_r ($temp_array);
    	
    	// iterate over all arrays - set so we only have one question
    	foreach ($temp_array as $this_array)
    	{
    		// pull this out of array as we will use a few tiems and makes easier to read
    		$this_question_id = $this_array['questionid'];
    		// does this question already exist - if so just add this quiz to it's quiz array
    		if (isset($return_array[$this_question_id]))
    		{
    			$return_array[$this_question_id]['quizzes'][] = $this_array['quizname'];
    		}
    		// This is new so create a new array entry
    		else
    		{
    			foreach ($this_array as $key => $value)
    			{
    				// handle quizname seperately (create array with this value as an entry
    				if ($key == "quizname") {$return_array[$this_question_id]['quizzes']= array($value);}
    				else {$return_array[$this_question_id][$key]=$value;}
    			}
    		}
    	}
    	return $return_array;
    }
    
    // get all quizzes
    //returns array of hash arrays
    public function getQuizzesAll ()
    {
    	return ($this->db_object->getRowsAll ("Select * from ".$this->table_prefix.$this->quiz_tables['quizzes']));
    }
    
    
}
?>
