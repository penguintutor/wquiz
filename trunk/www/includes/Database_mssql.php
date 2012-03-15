<?php
/* Despite filename this is class Database
Appropriate database file is loaded based on Database.php */
// Unsupported file for ms sql database alternative
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

/*** WARNING - this has not been tested - use at own risk ***/



class Database
{
    private $db_conn;
    private $db_settings;
    // status 0 unconnected, 1 connected, -1 error
    private $db_status = 0;
    // store error message
    private $db_error = '';
    
    
    public function __construct ($db_settings) 
    {
    	$this->db_settings = $db_settings;
    	// connect within the constructor
    	$this->db_conn = mssql_connect ($this->db_settings['hostname'], $this->db_settings['username'], $this->db_settings['password']);
    	// no mssql_error option
    	if (!$this->db_conn) { $this->db_status = -1; $this->db_error='Unable to connect to database '; }
    	elseif (! mssql_select_db($this->db_settings['database'], $this->db_conn) ) {$this->db_status = -1; $this->db_error = 'Cannot open database ';}
   	    else {$this->db_status = 1;}
    }
    

    // this can be either update or insert - depending upon select string
    // returns array - so as consistant with other functions
    // returns null array on success
    public function updateRow ($select_string) 
    {
    	
    	//debug
    	//print "Updating $select_string\n";
    	
    	$return_array = array();
        if (!$results = mssql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error writing to database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error writing to database ';
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
        if (!$results = mssql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database ';
    	    	return $return_array;
    	 }	
    	 return mssql_fetch_assoc($results);
    }     
    

    // returns array of hash
    public function getRowsAll ($select_string) 
    {
    	$return_array = array();
        if (!$results = mssql_query ($select_string))
        {
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database ';
    	    	return $return_array;
    	 }	
    	 while ($row = mssql_fetch_assoc($results))
    	 {
    	 	 $return_array[] = $row;
    	 }
    	 return $return_array;
    }     


    
    // gets all entries and returns as a hash with key / value pairs
    public function getKeyValue ($select_string, $key_column, $value_column)
    {
    	$return_array = array();
        if (!$results = mssql_query ($select_string))
    	{
    	    	$return_array['ERRORS'] = "Error reading from database";
    	    	$this->db_status = -1;
    	    	$this->db_error = 'Error reading from database ';
    	    	return $return_array;
    	 }
    	 $num_rows = mssql_num_rows($results);
    	 for ($i = 0; $i < $num_rows; $i++)
    	 {
    	 	$return_array[mssql_result($results,$i,$key_column)] = mssql_result($results,$i,"settings_value");
    	 }
    	 return ($return_array);
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
