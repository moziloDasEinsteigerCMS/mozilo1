<?php

/* 
* 
* $Revision: 115 $
* $LastChangedDate: 2009-01-27 21:14:39 +0100 (Di, 27 Jan 2009) $
* $Author: arvid $
*
*/


class Crypt {

	// constructor
	function Crypt() {
	}
	
	function encrypt($s)
	{
		return md5($s);
	}
}
?>