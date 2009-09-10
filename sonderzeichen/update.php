<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/


/*
######
        
    Update-Script für moziloCMS

    Bringt die Namen von Kategorie- und Galerieverzeichnissen sowie von Dateien und 
    Einträgen auf den Stand der mozilo-Sonderzeichenkodierung ab moziloCMS 1.10.

    VOR moziloCMS 1.12:     M-uuml~llers-nbsp~Kuh (Bindestrich Entity Tilde)
    AB moziloCMS 1.12:         M%FCllers%20Kuh (URL-Encoding)

    mozilo 2009
    www.mozilo.de
        
######
*/
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>moziloCMS-Sonderzeichen-Updatescript / moziloCMS special character update script</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>

<body>

<?php

    // erweiterte Ausgaben?
    $VERBOSE = false;
    if (isset($_GET['verbose']) && (($_GET['verbose'] == "1") || ($_GET['verbose'] == "true"))) {
        $VERBOSE = true;
    }

    include '../Properties.php';
    include '../SpecialChars.php';
    $mainconf = new Properties("../conf/main.conf");
    $versionconf = new Properties("../conf/version.conf");
    $specialchars = new SpecialChars();

    echo "<h2>Kategorien, Inhaltsseiten / Categories, content pages:</h2>";
    $categoriesOkay = renameAllFilesInDir("../kategorien", -1); 
    if ($categoriesOkay)
        echo "<b>OK :)</b><br /><br />";
    else
        echo "<br /><b>Dateirechte prüfen! / Check file rights!</b><br /><br />";
        
    echo "<h2>Galerien / Galleries:</h2>";
    $galleriesOkay = renameAllFilesInDir("../galerien", 0);
    if ($galleriesOkay)
        echo "<b>OK :)</b><br /><br />";
    else
        echo "<br /><b>Dateirechte prüfen! / Check file rights!</b><br /><br />";
        
    echo "<h2>Einstellungen / Settings:</h2>";
    if ($VERBOSE) {
        echo "mainconf->defaultcat = ".$mainconf->get("defaultcat")."<br />";
    }
    $propertiesOkay = $mainconf->set("defaultcat", updateEntities($mainconf->get("defaultcat")));
    if ($VERBOSE) {
        echo "mainconf->defaultcat = ".$mainconf->get("defaultcat")."<hr />";
    }
    if ($propertiesOkay)
        echo "<b>OK :)</b><br /><br />";
    else
        echo "<br /><b>Dateirechte für conf/main.conf prüfen! / Check file rights for conf/main.conf!</b><br /><br />";

    echo "<h2>Layouts / Layouts:</h2>";
    $layoutsOkay = $mainconf->set("cmslayout", updateEntities($mainconf->get("cmslayout"))) && renameAllFilesInDir("../layouts", 0);
    if ($layoutsOkay)
        echo "<b>OK :)</b><br /><br />";
    else
        echo "<br /><b>Dateirechte prüfen! / Check file rights!</b><br /><br />";
        
    echo "<hr />";
    echo "<h2>Ergebnis / Result:</h2>";
    if ($categoriesOkay && $galleriesOkay && $propertiesOkay && $layoutsOkay)
        echo "Ohne Fehler abgeschlossen - viel Spaß mit moziloCMS ".$versionconf->get("cmsversion")." :)<br />"
            ."Finished without errors - have fun with moziloCMS ".$versionconf->get("cmsversion")." :)";
    else
        echo "Es sind Fehler aufgetreten. Bitte Dateirechte überprüfen. Wenn nichts hilft, hilft das <a href=\"http://forum.mozilo.de\" target=\"_blank\">mozilo-Supportforum</a>.<br />"
            ."Errors occured. Please check file rights. If nothing helps, the <a href=\"http://forum.mozilo.de\" target=\"_blank\">mozilo support board</a> helps.";
        
        
        
        
            
    // - benennt alle Dateien und Verzeichnisse um, die in $dir liegen
    // - geht dabei $recursivedepth Verzeichnisebenen nach unten (negative Zahl für unendliche Verzeichnistiefe)
    // - gibt true zurück, wenn alle Dateien erfolgreich umbenannt werden konnten; sonst false
    function renameAllFilesInDir($dir, $recursivedepth)    {
        global $VERBOSE;
        
        // Erfolgsflag initialisieren
        $success = true;
        $handle = opendir($dir);
        while ($currentelement = readdir($handle)) {
            // "." und ".." auslassen
            if (($currentelement == ".") || ($currentelement == "..")) continue;
            // Unterverzeichnis? Rekursiv aufrufen
            if (is_dir($dir."/".$currentelement) && ($recursivedepth != 0))
                $success = renameAllFilesInDir($dir."/".$currentelement, $recursivedepth-1);
            if ($VERBOSE) {
                echo $dir."/".$currentelement ."<br />". $dir."/".updateEntities($currentelement)."<hr />";
            }
            // umbenennen -> false zurückgeben, wenn dabei was schiefgeht
            if (!@rename($dir."/".$currentelement, $dir."/".updateEntities($currentelement))) {
                $success = false;
                echo "Fehler bei / Error at: ".$dir."/".$currentelement."<br />";
            }
        }
        closedir($handle);
        return $success;
    }
     
        
    // Ersetzt im übergebenen Text alte mozilo-Entities durch neue
    function updateEntities($text) {
        global $specialchars;
        
        // alte moziloCMS-Entities zurückwandeln
        $text = rebuildSpecialCharsFrom111($text, false);
        return $specialchars->replaceSpecialChars($text, false);
    }
    

    // 1.11er-Entities wieder zu normalen Sonderzeichen machen
	function rebuildSpecialCharsFrom111($text, $rebuildnbsp) {
		// Leerzeichen
		if ($rebuildnbsp)
			$text = preg_replace("/-nbsp~/", "&nbsp;", $text);
		else
			$text = preg_replace("/-nbsp~/", " ", $text);
		// @, ?
		$text = preg_replace("/-at~/", "@", $text);
		$text = preg_replace("/-ques~/", "?", $text);
		// Alle mozilo-Entities in HTML-Entities umwandeln!
		$text = preg_replace("/-([^-~]+)~/U", "&$1;", $text);
		return html_entity_decode($text);
	}
	
?>
</body>
</html>