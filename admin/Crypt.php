<?php

/* 
* 
* $Revision: 19 $
* $LastChangedDate: 2008-03-12 18:06:54 +0100 (Mi, 12 Mrz 2008) $
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