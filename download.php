<?php

/* 
* 
* $Revision: 19 $
* $LastChangedDate: 2008-03-12 18:06:54 +0100 (Mi, 12 Mrz 2008) $
* $Author: arvid $
*
*/

require_once("Properties.php");
	$DOWNLOADS = new Properties("conf/downloads.conf");

	$CAT 	= preg_replace('/(\/|\\\)/', "", $_REQUEST['cat']);
	$FILE = preg_replace('/(\/|\\\)/', "", $_REQUEST['file']);
	$PATH = "kategorien/$CAT/dateien/$FILE";

	// Abbruch bei fehlerhaften Parametern
	if (($CAT == "") || ($FILE == "") || (!file_exists($PATH)))
		die("Invalid Parameters given.");
		
	// Alles okay, Downloadzähler inkrementieren und Datei ausliefern
	else {
		$DOWNLOADS->set($CAT.":".$FILE, $DOWNLOADS->get($CAT.":".$FILE) + 1);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".$FILE."\"");
		readfile($PATH);
	}
?>