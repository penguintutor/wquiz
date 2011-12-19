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
    
    
    private $quiz_elements = array 
    (
    	'quizname',
    	'title',
    	'numquestions',
    	'numquestionsoffline',
    	'quizintro',
    	'priority',
    	'enableonline',
    	'enableoffline'
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
    	global $debug;
    	
    	$settings = array();
    	$select_string = "select settings_key,settings_value from ".$this->table_prefix.$this->quiz_tables['settings'];
    	$settings = $this->db_object->getKeyValue ($select_string, "settings_key", "settings_value");
    	return $settings;
    }
    
    
    // returns true on success 
    // due to error level chosen will exit application on error (but could change to warning)
    public function updateSetting ($key, $value)
    {
    	global $debug;
    	
    	$sql = "update ".$this->table_prefix.$this->quiz_tables['settings']." set settings_value='$value' where settings_key='$key'";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
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
    	global $debug;
    	
    	$sql = "insert into ".$this->table_prefix.$this->quiz_tables['settings']." (settings_value, settings_key) value('$value', '$key')";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
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
    	
    	// Note a left outer join is needed instead of just join as the right hand table may then be null and we still match on the questions table
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid,intro,input,type,answer,reason, reference, hint, image, comments, qfrom, email, created, reviewed, quizname from ".$this->table_prefix.$this->quiz_tables['questions']." LEFT OUTER JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid where ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=$question_num";
   	
    	
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
    	// Initial sql without where - add where part later if required
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid, section, intro, input, type, answer, reason, reference, hint, image, comments, qfrom, email, created, reviewed, quizname FROM ". $this->table_prefix.$this->quiz_tables['questions']. " LEFT OUTER JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	   	
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


    // returns all rel entries
    public function getRelAll ()
    {
    	return ($this->db_object->getRowsAll ("Select * from ".$this->table_prefix.$this->quiz_tables['rel']));
    }    
    
    
    // returns an array
    public function getQuiz ($quizname) 
    {
    	global $debug;
    	// join used to get the quiznames from the relationship table
    	// may end up with multiple results with quizname being the unique part of each entry
    	$quizn_result = array();
    	
    	// Note a left outer join is needed instead of just join as the right hand table may then be null and we still match on the questions table
    	$sql = "Select * from ".$this->table_prefix.$this->quiz_tables['quizzes']." where quizname=\"$quizname\"";
   	
    	
    	if ($debug) {print "Loading quiz $quizname: \n SQL is:\n $sql \n\n";}
    	
    	// get all results into a temp array then we can combine to a single array with the quizname entries joined
    	$result = $this->db_object->getRow ($sql);
    	
    	
    	// check for errors
    	if (isset ($result['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	return ($result);
    }


    // create new quiz
    public function addQuiz ($post_details) 
    {
    	global $debug;

    	// create two strings - one with field names - second with values
    	$fields = '';
    	$values = '';
    	$comma = '';
    	foreach ($this->quiz_elements as $this_element)
    	{
    		$fields .= $comma.$this_element;
    		// if value is not set then we set to a default
    		if (isset ($post_details[$this_element])) {$values .= $comma."\"".mysql_real_escape_string($post_details[$this_element])."\"";}
    		else {$values .= $comma."\"\"";}
    		$comma = ',';
    	}
    	
    	$sql = "INSERT INTO ".$this->table_prefix.$this->quiz_tables['quizzes']."($fields) VALUES ($values)";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	return true;
    }

    
    // change existing quiz
    public function updateQuiz ($post_details) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$fields = '';
    	$comma = '';
    	foreach ($this->quiz_elements as $this_element)
    	{
    		// if section not set then we ignore (not same as ='' which we will update with)
    		if (!isset($post_details[$this_element])) {continue;}
    		$fields .= $comma.$this_element."=\"".mysql_real_escape_string($post_details[$this_element])."\"";
    		$comma = ',';
    	}
    	
    	$sql = "UPDATE ".$this->table_prefix.$this->quiz_tables['quizzes']." SET $fields WHERE quizname=\"".$post_details['quizname']."\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	} 	
    	return true;
    }

    
    // does not del rel entries - they need to be done seperately (pref first)
    public function delQuiz ($quizname) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$sql = "DELETE FROM ".$this->table_prefix.$this->quiz_tables['quizzes']." WHERE quizname=\"$quizname\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }       
    
    
    // create new question
    public function addQuestion ($post_details) 
    {
    	global $debug;
    	
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
    		if (isset ($post_details[$this_element])) {$values .= $comma."\"".mysql_real_escape_string($post_details[$this_element])."\"";}
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
    	global $debug;
    	
    	$question_result = array();
    	
    	// create two strings - one with field names - second with values
    	$fields = '';
    	$comma = '';
    	foreach ($this->question_elements as $this_element)
    	{
    		// if section not set then we ignore (not same as ='' which we will update with)
    		if (!isset($post_details[$this_element])) {continue;}
    		$fields .= $comma.$this_element."=\"".mysql_real_escape_string($post_details[$this_element])."\"";
    		$comma = ',';
    	}
    	
    	$sql = "UPDATE ".$this->table_prefix.$this->quiz_tables['questions']." SET $fields WHERE questionid=".$post_details['questionid'];
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	} 	
    	return true;
    }

    
    
    // does not del rel entries - they need to be done seperately (pref first)
    public function delQuestion ($questionid) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$sql = "DELETE FROM ".$this->table_prefix.$this->quiz_tables['questions']." WHERE questionid=\"$questionid\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }    
    
    
    // Check if a question exists or not
    // returns true if exists - false if not
    public function checkQuestionID($questionid)
    {
    	global $debug;
    	
    	
    }
    

    // returns list of question ids associated with particular quiz (or all if no quiz specified)
    public function getQuestionIds ($quiz="") 
    {
    	global $debug;
    	
    	// if $quiz not specified get all questions in db (even those with no quiz)
    	// Initial sql without where - add where part later if required
    	$sql = "SELECT ".$this->table_prefix.$this->quiz_tables['questions'].".questionid, quizname FROM ". $this->table_prefix.$this->quiz_tables['questions']. " LEFT OUTER JOIN ".$this->table_prefix.$this->quiz_tables['rel']." on ".$this->table_prefix.$this->quiz_tables['questions'].".questionid=".$this->table_prefix.$this->quiz_tables['rel'].".questionid";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
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



    /** question_rel table updates **/
    // We have add and del 
    // there is no need to update / save as only 2 fields - del and then add if appropriate
    
    // Adds an entry to the question_rel table
    public function addQuestionQuiz ($quizname, $questionid) 
    {
    	global $debug;
    	
    	$question_result = array();
    	
    	// create two strings - one with field names - second with values
    	$sql = "INSERT INTO ".$this->table_prefix.$this->quiz_tables['rel']." SET quizname=\"$quizname\",questionid=\"$questionid\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }    
    


    // Deletes an entry to the question_rel table
    public function delQuestionQuiz ($quizname, $questionid) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$sql = "DELETE FROM ".$this->table_prefix.$this->quiz_tables['rel']." WHERE quizname=\"$quizname\" AND questionid=\"$questionid\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }    
    

    // Deletes entries to the question_rel table - all which match quizname
    public function delQuestionQuizQuizname ($quizname) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$sql = "DELETE FROM ".$this->table_prefix.$this->quiz_tables['rel']." WHERE quizname=\"$quizname\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }    


    // Deletes entries to the question_rel table - all which match questionid
    public function delQuestionQuizQuestionid ($questionid) 
    {
    	global $debug;
    	
    	// create two strings - one with field names - second with values
    	$sql = "DELETE FROM ".$this->table_prefix.$this->quiz_tables['rel']." WHERE questionid=\"$questionid\"";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }
    
    
    // check to see if question exists (if yes return true)
    public function checkQuestion ($question_num) 
    {
    	global $debug;
    	    	
    	// Note a left outer join is needed instead of just join as the right hand table may then be null and we still match on the questions table
    	// some more fields are included in case we want to make a more advanced check
    	$sql = "SELECT questionid,intro,type from ".$this->table_prefix.$this->quiz_tables['questions']." where questionid=\"$question_num\"";
   	
    	
    	if ($debug) {print "Checking question $question_num: \n SQL is:\n $sql \n\n";}
    	
    	// get all results into a temp array then we can combine to a single array with the quizname entries joined
    	$temp_array = $this->db_object->getRowsAll ($sql);
    	
    	// if we have an entry then it does exist
    	if (count($temp_array) > 0) {return true;}
    	else {return false;}
    }
    
    public function checkQuiz ($quizname) 
    {
    	global $debug;
    	    	
    	// Note a left outer join is needed instead of just join as the right hand table may then be null and we still match on the questions table
    	// some more fields are included in case we want to make a more advanced check
    	$sql = "SELECT quizname,title from ".$this->table_prefix.$this->quiz_tables['quizzes']." where quizname=\"$quizname\"";
   	
    	
    	if ($debug) {print "Checking quiz $quizname: \n SQL is:\n $sql \n\n";}
    	
    	// get all results into a temp array then we can combine to a single array with the quizname entries joined
    	$temp_array = $this->db_object->getRowsAll ($sql);
    	
    	// if we have an entry then it does exist
    	if (count($temp_array) > 0) {return true;}
    	else {return false;}
    }
    
}
?>
