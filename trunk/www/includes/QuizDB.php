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
    
    // list of elements in question table - use to compile sql req
    private $question_elements = array 
    (
    	'questionid',
    	'section',			// not currently used
    	'intro',
    	'input',
    	'type',
    	'answer',
    	'reason',
    	'reference',
    	'hint',
    	'image',
    	'comments',
    	'qfrom',
    	'email',
    	'created',
    	'reviewed'
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
    
    
    // returns true on success 
    // due to error level chosen will exit application on error (but could change to warning)
    public function updateSetting ($key, $value)
    {
    	$sql = "update ".$this->table_prefix.$this->quiz_tables['settings']." set settings_value='$value' where settings_key='$key'";
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }
    
    // returns true on success 
    // due to error level chosen will exit application on error (but could change to warning)
    public function insertSetting ($key, $value)
    {
    	$sql = "insert into ".$this->table_prefix.$this->quiz_tables['settings']." (settings_value, settings_key) value('$value', '$key')";
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    	
    }


    
    // returns an array
    public function getQuestion ($question_num) 
    {
    	global $debug;
    	// join used to get the quiznames from the relationship table
    	// may end up with multiple results with quizname being the unique part of each entry
    	$question_result = array();
    	
    	//--- fails on the join if the question is not assigned to any quizzes!!!
    	
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid,intro,input,type,answer,reason, reference, hint, image, comments, qfrom, email, created, reviewed, quizname from ".$this->table_prefix.$this->quiz_tables['questions']." JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid where ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=$question_num";
    	
    	if ($debug) {print "Loading question $question_num: \n SQL is:\n $sql \n\n";}
    	
    	// get all results into a temp array then we can combine to a single array with the quizname entries joined
    	$temp_array = $this->db_object->getRowsAll ($sql);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	
    	
    	// copy first entry into place except for quizname
    	foreach ($temp_array[0] as $key => $value)
    	{
    		// skip keyname
    		if ($key != "quizname") 
    		{
    			$question_result[$key] = $value;
    		}
    	}
    	
    	$question_result['quizzes'] = array();
    	
    	// now iterate over the entire array (of hash arrays) adding question_result to an array
    	foreach ($temp_array as $this_array)
    	{
    		$question_result['quizzes'][] = $this_array['quizname'];
    	}
    	
    	return ($question_result);
    }


    // returns array of arrays - all questions in Quiz (category)
	// no category returns all
    public function getQuestionQuiz ($quiz="") 
    {
    	global $debug;
    	
    	// if $quiz not specified get all questions in db (even those with no quiz)
    	//--- note this is not working where question is not assigned to a quiz (as we fail on the join)
    	// Initial sql without where - add where part later if required
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid, section, intro, input, type, answer, reason, reference, hint, image, comments, qfrom, email, created, reviewed, quizname FROM ". $this->table_prefix.$this->quiz_tables['questions']. " JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid";
    	
    	   	
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
    		//-here userfriendly handling?
    		//print "An error has occurred";
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);

    	}
    	
    	// New array that we return from the function
    	$return_array = array();

    	//print "DEBUG\n";
    	//print_r ($temp_array);
    	
    	// iterate over all arrays - set so we only have one question
    	foreach ($temp_array as $this_array)
    	{
    		// pull this out of array as we will use a few times and makes easier to read
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
    
    
    // create new question
    public function addQuestion ($post_details) 
    {
    	global $debug;
    	// join used to get the quiznames from the relationship table
    	// may end up with multiple results with quizname being the unique part of each entry
    	$question_result = array();
    	
    	// set questionid to 0 for add (auto-increment)
    	$post_details['questionid'] = '';
    	
    	// create two strings - one with field names - second with values
    	$fields = '';
    	$values = '';
    	$comma = '';
    	foreach ($this->question_elements as $this_element)
    	{
    		$fields .= $comma.$this_element;
    		// if value is not set then we set to a default
    		if (isset ($post_details[$this_element])) {$values .= $comma."\"".$post_details[$this_element]."\"";}
    		else {$values .= $comma."\"\"";}
    		$comma = ',';
    	}
    	
    	$sql = "INSERT INTO ".$this->table_prefix.$this->quiz_tables['questions']."($fields) VALUES ($values)";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	// return autoincremented questionid
    	return mysql_insert_id();
    }
    

    // change existing question
    public function updateQuestion ($post_details) 
    {
    	/*// join used to get the quiznames from the relationship table
    	// may end up with multiple results with quizname being the unique part of each entry
    	$question_result = array();
    	
    	// set questionid to 0 for add (auto-increment)
    	$post_details['questionid'] = '';
    	
    	// create two strings - one with field names - second with values
    	$fields = '';
    	$values = '';
    	foreach ($question_elements as $this_element)
    	{
    		$comma = '';
    		$fields .= $comma.$this_element;
    		// if value is not set then we set to a default
    		if (isset $post_details[$this_element]) {$values .= $comma.$post_details[$this_element];}
    		else {$values .= $comma.'';}
    		$comma = ',';
    	}
    	
    	$sql = "INSERT INTO ".$this->table_prefix.$this->quiz_tables['questions']."($fields) VALUES ($values)";
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	*/
    	return true;
    }


    // returns list of question ids associated with particular quiz (or all if no quiz specified)
    public function getQuestionIds ($quiz="") 
    {
    	// if $quiz not specified get all questions in db (even those with no quiz)
    	// Initial sql without where - add where part later if required
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid, quizname FROM ". $this->table_prefix.$this->quiz_tables['questions']. " JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid";
    	
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
    		//-here userfriendly handling?
    		//print "An error has occurred";
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);

    	}
    	
    	// New array that we return from the function - std 1 dimension
    	$return_array = array();

    	//print "DEBUG\n";
    	//print_r ($temp_array);
    	
    	// iterate over all arrays - set so we only have one question
    	foreach ($temp_array as $this_array)
    	{
    		// pull this out of array as we will use a few times and makes easier to read
    		$this_question_id = $this_array['questionid'];
    		// does this question already exist - if so ignore
    		if (!in_array ($this_question_id, $return_array))
    		{
    			$return_array[] = $this_question_id;
    		}
    	}
    	return $return_array;
    }
    
    
}
?>
