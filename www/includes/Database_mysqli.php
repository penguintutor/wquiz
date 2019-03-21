<?php
/* Despite filename this is class Database
Appropriate database file is loaded based on Database.php */
/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2012

This file is part of wQuiz.

wQuiz is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

wQuiz is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wQuiz.  If not, see <http://www.gnu.org/licenses/>.
**/


/*** OOP wrapper around old mysql functions
Use this because some hosting providers do not support mysqli
Also add some additional functions to simplify database creation etc.
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
    	$this->db_conn = new mysqli ($this->db_settings['hostname'], $this->db_settings['username'], $this->db_settings['password'], $this->db_settings['database']);
    	
    	//$this->db_conn = mysql_connect ($this->db_settings['hostname'], $this->db_settings['username'], $this->db_settings['password']);
    	//if (!$this->db_conn) { $this->db_status = -1; $this->db_error='Unable to connect to database '+$this->db_conn->connect_errno." ".$this->db_conn->connect_error;}
    	//elseif (! mysql_select_db($this->db_settings['database'], $this->db_conn) ) {$this->db_status = -2; $this->db_error = 'Cannot open database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;}
        if ($this->db_conn->connect_errno) 
        {
            print "Error no :".$this->db_conn->connect_errno."\n";
            print "Error: ".$this->db_conn->connect_error."\n";
            return -1;
        }
   	    $this->db_status = 1;
    }
    
    
    // use to select a database after we've connected to the mysql server
    public function connectDb ($db_name)
    {
    	#if (mysql_select_db($db_name, $this->db_conn) )
    	$this->db_conn->select_db($db_name);
   	    if ($this->db_conn->connect_errno)
    	{
    		$this->db_status = -2; 
    		$this->db_error = 'Cannot open database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    		return -2;
    	}
    	else
    	{
    		$this->db_settings['database'] = $db_name;
   	    	$this->db_status = 1;
   	    	return 1;
   	    }
    }
    

	// creates a new database - note it doesn't select the database 
	// normally follow by connectDb    
    public function createDb ($db_name)
    {
    	global $debug;
    	
    	if ($debug) {print "Creating database:\n\nCREATE DATABASE $db_name\n\n";}
    	
    	if ($this->db_conn->query("CREATE DATABASE `$db_name`", $this->db_conn))
    	{
    		if ($debug) {print "database created\n\n";}
    		$this->db_status = 1;
    		return 1;
    	}
    	else 
    	{
    		if ($debug) {print 'Unable to create database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error."\n\n";}
    		$this->db_status = -1; 
    		$this->db_error = 'Unable to create database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    		return -1;
    	}
    }
    

    // generic query function
    // don't normally use - instead use more specific 
    // used by install eg. to create tables
    public function query ($select_string) 
    {
        if (!$results = $this->db_conn->query($select_string))
        {
    	    	return -1;
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error writing to database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    	 }	
    	 return 0;
    }         
    
    

    // this can be either update or insert - depending upon select string
    // returns array - so as consistant with other functions
    // returns null array on success
    public function updateRow ($select_string) 
    {

    	$return_array = array();
        if (!$results = $this->db_conn->query($select_string))
        {
    	    	$return_array['ERRORS'] = "Error writing to database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error writing to database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    	 }	
    	 return $return_array;
    }     
    
    
    // return autoincrement id (uses db connection)
    public function getInsertID()
    {
    	return $this->db_conn->insert_id;
    }
    
    
    // not strictly needed, but maintains consistancy with names as per QuizDB
    public function insertRow ($select_string)
    {
    	return $this->updateRow ($select_string);
    }
    
    
    public function getRow ($select_string) 
    {
    	$return_array = array();
        if (!$results = $this->db_conn->query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    	    	return $return_array;
    	 }	
    	 return $results->fetch_assoc();
    }     
    

    // returns array of hash
    public function getRowsAll ($select_string) 
    {
    	$return_array = array();
        if (!$results = $this->db_conn->query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    	    	return $return_array;
    	 }	
    	 while ($row = $results->fetch_assoc())
    	 {
    	 	 $return_array[] = $row;
    	 }
    	 return $return_array;
    }     


    
    // gets all entries and returns as a hash with key / value pairs
    public function getKeyValue ($select_string, $key_column, $value_column)
    {
    	$return_array = array();
        if (!$results = $this->db_conn->query($select_string))
    	{
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database '.$this->db_conn->connect_errno." ".$this->db_conn->connect_error;
    	    	return $return_array;
    	 }
    	 $num_rows = $results->num_rows;
    	 for ($i = 0; $i < $num_rows; $i++)
    	 {
    	     $this_row = $results->fetch_assoc();
    	     $return_array[$this_row[$key_column]] = $this_row[$value_column];
    	 	//$return_array[mysql_result($results,$i,$key_column)] = mysql_result($results,$i,"settings_value");
    	 }
    	 return ($return_array);
    }
    
    // returns array of all tables in the current database
    public function getTables ()
    {
    	$return_array = array();
    	$result = $this->db_conn->query("SHOW TABLES", $this->db_conn);
    	if ($result != false)
    	{
			while ($row = $result->fetch_row())
			{
				$return_array[] = $row[0];
	
			}
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
    
    
    public function escapeString ($input_string)
    {
        return $this->db_conn->real_escape_string($input_string);
    }
    
    
}
?>
