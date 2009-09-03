<?php

/* 
* 
* $Revision: 72 $
* $LastChangedDate: 2009-01-05 17:11:58 +0100 (Mo, 05 Jan 2009) $
* $Author: arvid $
*
*/


/*
######
		
	Update-Script für moziloCMS

	Bringt die Namen von Kategorie- und Galerieverzeichnissen sowie von Dateien und 
	Einträgen auf den Stand der mozilo-Sonderzeichenkodierung ab moziloCMS 1.10.

	VOR moziloCMS 1.10: 	M-uuml-llers-nbsp-Kuh (Bindestrich Entity Bindestrich)
	AB moziloCMS 1.10: 		M-uuml~llers-nbsp~Kuh (Bindestrich Entity Tilde)

	mozilo 2008
	www.mozilo.de
		
######
*/

	include '../Properties.php';

	echo "<h2>Kategorien, Inhaltsseiten / Categories, content pages:</h2>";
	$categoriesOkay = renameAllFilesInDir("../kategorien", 1); 
	if ($categoriesOkay)
		echo "<b>OK :)</b><br><br>";
	else
		echo "<br><b>Dateirechte prüfen! / Check file rights!</b><br><br>";
		
	echo "<h2>Galerien / Galleries:</h2>";
	$galleriesOkay = renameAllFilesInDir("../galerien", 0);
	if ($galleriesOkay)
		echo "<b>OK :)</b><br><br>";
	else
		echo "<br><b>Dateirechte prüfen! / Check file rights!</b><br><br>";
		
	echo "<h2>Einstellungen / Settings:</h2>";
	$mainconf = new Properties("../conf/main.conf");
	$propertiesOkay = $mainconf->set("defaultcat", updateEntities($mainconf->get("defaultcat")));
	if ($propertiesOkay)
		echo "<b>OK :)</b><br><br>";
	else
		echo "<br><b>Dateirechte für conf/main.conf prüfen! / Check file rights for conf/main.conf!</b><br><br>";
		
	echo "<hr>";
	echo "<h2>Ergebnis / Result:</h2>";
	if ($categoriesOkay && $galleriesOkay && $propertiesOkay)
		echo "Ohne Fehler abgeschlossen - viel Spaß mit moziloCMS 1.10 :)<br>"
			."Finished without errors - have fun with moziloCMS 1.10 :)";
	else
		echo "Es sind Fehler aufgetreten. Bitte Dateirechte überprüfen. Wenn nichts hilft, hilft das <a href=\"http://www.mozilo.de/forum\" target=\"_blank\">mozilo-Supportforum</a>.<br>"
			."Errors occured. Please check file rights. If nothing helps, the <a href=\"http://www.mozilo.de/forum\" target=\"_blank\">mozilo support forum</a> helps.";
		
		
		
		
			
	// - benennt alle Dateien und Verzeichnisse um, die in $dir liegen
	// - geht dabei $recursivedepth Verzeichnisebenen nach unten (negative Zahl für unendliche Verzeichnistiefe)
	// - gibt true zurück, wenn alle Dateien erfolgreich umbenannt werden konnten; sonst false
	function renameAllFilesInDir($dir, $recursivedepth)	{
		// Erfolgsflag initialisieren
		$success = true;
		$handle = opendir($dir);
		while ($currentelement = readdir($handle)) {
			// "." und ".." auslassen
			if (($currentelement == ".") || ($currentelement == "..")) continue;
			// Unterverzeichnis? Rekursiv aufrufen
			if (is_dir($dir."/".$currentelement) && ($recursivedepth != 0))
				$success = renameAllFilesInDir($dir."/".$currentelement, $recursivedepth-1);
			// umbenennen -> false zurückgeben, wenn dabei was schiefgeht
			if (!@rename($dir."/".$currentelement, $dir."/".updateEntities($currentelement))) {
				$success = false;
				echo "Fehler bei / Error at: ".$dir."/".$currentelement."<br>";
			}
		}
		closedir($handle);
		return $success;
	}
	 
		
	// Ersetzt im übergebenen Text alte mozilo-Entities durch neue
	function updateEntities($text) {
		$text = preg_replace("/-auml-/", 	"-auml~", 	$text);
		$text = preg_replace("/-ouml-/", 	"-ouml~", 	$text);
		$text = preg_replace("/-uuml-/", 	"-uuml~", 	$text);
		$text = preg_replace("/-Auml-/", 	"-Auml~", 	$text);
		$text = preg_replace("/-Ouml-/", 	"-Ouml", 		$text);
		$text = preg_replace("/-Uuml-/", 	"-Uuml~", 	$text);
		$text = preg_replace("/-szlig-/",	"-szlig~", 	$text);
		$text = preg_replace("/-ques-/",	"-ques~", 	$text);
		$text = preg_replace("/-amp-/",		"-amp~", 		$text);
		$text = preg_replace("/-euro-/",	"-euro~",		$text);
		$text = preg_replace("/-lt-/", 		"-lt~", 		$text);
		$text = preg_replace("/-gt-/", 		"-gt~", 		$text);
		$text = preg_replace("/-at-/", 		"-at~", 		$text);
		$text = preg_replace("/-nbsp-/", 	"-nbsp~", 	$text);
		return $text;	
	}
?>