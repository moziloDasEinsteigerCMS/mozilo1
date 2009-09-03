<?php
	require_once("Properties.php");
	$DOWNLOADS = new Properties("conf/downloads.conf");

	$CAT 	= $_REQUEST['cat'];
	$FILE = $_REQUEST['file'];
	$PATH = "kategorien/$CAT/dateien/$FILE";

	// Abbruch bei fehlerhaften Parametern
	if (($CAT == "") || ($FILE == "") || (!file_exists($PATH)))
		die("Invalid Parameters given. Stop hackin', kid.");
		
	// Alles okay, Downloadzähler inkrementieren und Datei ausliefern
	else {
		$DOWNLOADS->set($CAT.":".$FILE, $DOWNLOADS->get($CAT.":".$FILE) + 1);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".$FILE."\"");
		readfile($PATH);
	}
?>