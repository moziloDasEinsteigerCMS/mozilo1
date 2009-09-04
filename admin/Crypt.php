<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
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