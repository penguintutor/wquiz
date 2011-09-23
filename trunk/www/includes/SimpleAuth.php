<?php
// basic authentication - single user / password (passed in constructor - from settings)
// note session_start needs to be called before this is included

class SimpleAuth
{

    
    public function SimpleAuth ($adminuser, $adminpass, $expirytime)
    {
    	@session_start();
    	$this->adminuser = $adminuser;
    	$this->adminpass = $adminpass;
    	$this->expirytime = $expirytime;

    }
    
    
    // check if logged in already
    public function checkLogin() 
    {
    	    // see if we have a session
    	    if (!isset($_SESSION['user'])) {return -1;}
    	    $user = $_SESSION['user'];
    	    $time = $_SESSION['timestamp'];
    	    // check user matches adminuser
    	    if (!isset($user) || $user == '' || !isset($time) || $time == '') {return -1;}
    	    if ($time + $this->expirytime < time()) {return -2;}
    	    if ($user != $this->adminuser) {return -3;}
    	    // authentication successful - extend time
    	    $_SESSION['timestamp'] = time();
    	    return 1;
    }
    

    public function logout() 
    {
    	    // see if we have a session - if not we are not logged in
    	    if (!isset($_SESSION['user'])) {return 1;}
    	    // set session info to blank to fail session
    	    $_SESSION['user'] = '';
    	    $_SESSION['timestamp'] = '';
    	    return 1;
    }

    
    // get username
    public function getUser()
    {
    	return $_SESSION['user'];
    }
    
    
    // check for invalid characters in values - check post info
    // includes checks for username, password and location
    // returns status code 1 for success 0 for unknown type -1 for invalid string, -2 for type not recongised (shouldn't see this) 
    //public function securityCheck ($type, $input, $array)
    public function securityCheck ()
    {
		$args = func_get_args();
		$type = $args[0];
		$input = $args[1];
		
		// username only allow alphanumeric _- (ie. \w)
		// first character must be alphanumeric (not _)
		// 5 to 20 characters long
		if ($type == 'username') 
		{
				if (preg_match("/^[a-zA-Z0-9]\w{4,20}$/", $input)) {return 1;}
				else {return -1;}
		}
		// password similar, but also allow certain special characters
		// first character must be alphanumeric (not even _)
		// note no brackets / quotes / (semi-)colons
		elseif ($type == 'password')
		{
				if (preg_match("/^[a-zA-Z0-9][\w%!�$%^&*#?~]{5,20}$/", $input)) {return 1;}
				else {return -1;}
		}
		// location is a special one check usual \w - look for valid key in $url 
		elseif ($type == 'location')
		{
				// get the array 
				$array = $args[2];
				// first check alphanumeric
				if (preg_match("/^\w{1,20}$/", $input))
				{
						if (array_key_exists($input, $array)) {return 1;}
						else {return -1;}
				}
				else {return -1;}
		}
		// Reach here and it was not a valid type 
		return -2;
    }
    
    
    public function loginNow($username, $password)
    {
    	// check login
    	if (($username == $this->adminuser) && (md5($password) == $this->adminpass)) 
    	{
    		$_SESSION['user'] = $username;
    		$_SESSION['timestamp'] = time();
   		return 1;
    	}
    	else {return -1;}
    }

    // $loginurl is the page to send the login form to $gotourl to redirect to page if successful
    public function loginForm($loginurl, $gotourl)
    {
    	print "<form name=\"login\" action=\"$loginurl\" method=\"post\">\n";
    	print "<input type=\"hidden\" name=\"location\" value=\"$gotourl\" />\n";
	    print "Username: <input type=\"text\" name=\"username\" /><br />\n";
    	print "Password: <input type=\"password\" name=\"password\" /><br />\n";
    	print "<input type=\"submit\" value=\"login\" />\n";
    	print "</form>\n";
    }
    
    
} // end class


?>
