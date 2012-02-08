<?php
/* Despite filename this is class Database
Appropriate database file is loaded based on Database.php */


/*** OOP wrapper around old mysql functions
Use this because some hosting providers do not support mysqli
In future this will be replaced with mysqli functions
***/


class Database
{
    private $db_conn;
    private $db_settings;
    // status 0 unconnected, 1 connected, -1 error, -2 = connected to mysql but database not selected (-2 useful during install to allow us to create a new db)
    private $db_status = 0;
    // store error message
    private $db_error = '';
    
    
    public function __construct ($db_settings) 
    {
    	$this->db_settings = $db_settings;
    	// connect within the constructor
    	$this->db_conn = mysql_connect ($this->db_settings['hostname'], $this->db_settings['username'], $this->db_settings['password']);
    	if (!$this->db_conn) { $this->db_status = -1; $this->db_error='Unable to connect to database '+mysql_error();}
    	elseif (! mysql_select_db($this->db_settings['database'], $this->db_conn) ) {$this->db_status = -2; $this->db_error = 'Cannot open database '.mysql_error();}
   	    else {$this->db_status = 1;}
    }
    
    
    // use to select a database after we've connected to the mysql server
    public function connectDb ($db_name)
    {
    	if (mysql_select_db($db_name, $this->db_conn) )
    	{
    		$this->db_settings['database'] = $db_name;
   	    	$this->db_status = 1;
   	    	return 1;
   	    }
   	    else 
    	{
    		$this->db_status = -2; 
    		$this->db_error = 'Cannot open database '.mysql_error();
    		return -2;
    	}
    }
    

	// creates a new database - note it doesn't select the database 
	// normally follow by connectDb    
    public function createDb ($db_name)
    {
    	if (mysql_query("CREATE DATABASE $db_name", $this->db_conn))
    	{
    		$this->db_status = 1;
    		return 1;
    	}
    	else 
    	{
    		$this->db_status = -1; 
    		$this->db_error = 'Unable to create database '.mysql_error();
    		return -1;
    	}
    }
    

    // generic query function
    // don't normally use - instead use more specific 
    // used by install eg. to create tables
    public function query ($select_string) 
    {
        if (!$results = mysql_query ($select_string))
        {
    	    	return -1;
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error writing to database '.mysql_error();
    	 }	
    	 return 0;
    }         
    
    

    // this can be either update or insert - depending upon select string
    // returns array - so as consistant with other functions
    // returns null array on success
    public function updateRow ($select_string) 
    {
    	
    	//debug
    	//print "Updating $select_string\n";
    	
    	$return_array = array();
        if (!$results = mysql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error writing to database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error writing to database '.mysql_error();
    	 }	
    	 return $return_array;
    }     
    
    // not strictly needed, but maintains consistancy with names as per QuizDB
    public function insertRow ($select_string)
    {
    	return $this->updateRow ($select_string);
    }
    
    
    public function getRow ($select_string) 
    {
    	$return_array = array();
        if (!$results = mysql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.mysql_error();
    	    	return $return_array;
    	 }	
    	 return mysql_fetch_assoc($results);
    }     
    

    // returns array of hash
    public function getRowsAll ($select_string) 
    {
    	$return_array = array();
        if (!$results = mysql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.mysql_error();
    	    	return $return_array;
    	 }	
    	 while ($row = mysql_fetch_assoc($results))
    	 {
    	 	 $return_array[] = $row;
    	 }
    	 return $return_array;
    }     


    
    // gets all entries and returns as a hash with key / value pairs
    public function getKeyValue ($select_string, $key_column, $value_column)
    {
    	$return_array = array();
        if (!$results = mysql_query ($select_string))
    	{
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.mysql_error();
    	    	return $return_array;
    	 }
    	 $num_rows = mysql_num_rows($results);
    	 for ($i = 0; $i < $num_rows; $i++)
    	 {
    	 	$return_array[mysql_result($results,$i,$key_column)] = mysql_result($results,$i,"settings_value");
    	 }
    	 return ($return_array);
    }
    
    // returns array of all tables in the current database
    public function getTables ()
    {
    	$return_array = array();
    	$result = mysql_query("SHOW TABLES");
    	while ($row = mysql_fetch_row($result))
		{
			$return_array[] = $row[0];

		}
		return $return_array;
    }
    
    // returns table prefix - used by external classes to construct sql statements with the correct table name
    public function getTablePrefix ()
    {
    	return $this->db_settings['tableprefix'];
    }
    
    // note based on last task - does not check with database 
    public function getStatus ()
    {
    	return $this->db_status;
    }
    
    // get last error message
    public function getError ()
    {
    	return $this->db_error;
    }
    
    
    
}
?>
