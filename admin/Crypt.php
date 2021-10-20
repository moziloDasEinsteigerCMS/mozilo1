<?php

/* 
* 
* $Revision: 42 $
* $LastChangedDate: 2009-09-04 18:36:58 +0200 (Fr, 04. Sep 2009) $
* $Author: arvid $
*
*/


class Crypt {

	function encrypt($s)
	{
		return md5($s);
	}
}
?>