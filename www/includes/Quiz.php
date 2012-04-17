<?php
/*** 
Handles the overall quiz setup for a single quiz
used by Quizzes class for menu / admin / overview, but can be called 
directly by individual php files where in active quiz
***/

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

class Quiz
{
	// Hash array
	// These are all named the same as the table fields - rather than normal coding convension
	// quizid, quizname, title, numquestions, numquestionsoffline, quizintro, enableonline, enableoffline
	private $quiz_info;

	// question_num is our position in quiz - irrelevant (0) if not actually doing quiz
	// normally create instance with details, but set to null in case 
	// creating a new one (eg. new question)
	// defaults are set to empty strings above    
    public function __construct ($quiz_array) 
    {
    	// if provided with array use it to initialise array
    	if (is_array ($quiz_array)) {$this->quiz_info = $quiz_array;}
    }

	// generic function - normally use the specific function    
    public function getValue ($field) 
    {
    	return $this->quiz_info[$field];
    }
    
    public function getQuizname ()
    {
    	return $this->quiz_info['quizname'];
    }
    
    public function getPriority ()
    {
    	return $this->quiz_info['priority'];
    }
    
    public function getTitle ()
    {
    	return $this->quiz_info['title'];
    }

    public function getIntro ()
    {
    	return $this->quiz_info['quizintro'];
    }

	// quiztype 
	public function getNumQuestions ($quiztype = 'online')
	{
		if ($quiztype == 'online') {return $this->quiz_info['numquestions'];}
		else {return $this->quiz_info['numquestionsoffline'];}
	}
    
    // returns if this is enabled for online / offline
    // parameter required is "online", "offline", "any" or "all"
    // all will return even if disabled for both, either only if one is enabled
    public function isEnabled ($use)
    {
    	// by definition first will always be true - but allows consistant use of function
    	if ($use == "all") {return true;}
    	else if ($use == "offline") {return $this->quiz_info['enableoffline'];}
    	else if ($use == "online") {return $this->quiz_info['enableonline'];}
    	// next line returns the result of the test condition (ie true / false)
    	else if ($use == "any") {return ($this->quiz_info['enableonline'] || $this->quiz_info['enableoffline']);}  
    	// if not a supported field then it's disabled
    	else {return false;}
    }

    // compare function - used by usort
    // objects are never equal as quizname must be unique
    static function cmpObj ($a, $b)
    {
    	// if equal priority order by quizname
    	if ($a->quiz_info['priority'] != $b->quiz_info['priority']) 
    	{
    		// sort highest number first so less than
    		return ($a->quiz_info['priority'] < $b->quiz_info['priority']) ? +1 : -1;
    	}
    	return ($a->quiz_info['quizname'] > $b->quiz_info['quizname']) ? +1 : -1;
    }
    
    
}
?>

