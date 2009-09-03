<?php

/* 
* 
* $Revision: 163 $
* $LastChangedDate: 2009-01-30 17:38:26 +0100 (Fr, 30 Jan 2009) $
* $Author: oliver $
*
*/



require_once("../Properties.php");
require_once("../SpecialChars.php");

$specialchars = new SpecialChars();
/* Variablen */

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt zurueck welche Sprache aktuell verwendet wird.
 --------------------------------------------------------------------------------*/
function getLanguage()
{
	$BASIC_CONFIG 		= new Properties("conf/basic.conf");
	return $BASIC_CONFIG->get("language");
}


/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt zurueck wann der letzte Backup gemacht wurde...
 --------------------------------------------------------------------------------*/
function getLastBackup()
{
	$BASIC_CONFIG 		= new Properties("conf/basic.conf");
	return $BASIC_CONFIG->get("lastbackup");
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Schreibt die aktuelle Uhrzeit in lastbackup
 --------------------------------------------------------------------------------*/
function setLastBackup()
{
	$BASIC_CONFIG 		= new Properties("conf/basic.conf");
	return $BASIC_CONFIG->set("lastbackup",time());
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Liest aus dem Language-File eine Bistimmte Variable	aus.
 --------------------------------------------------------------------------------*/
function getLanguageValue($confpara)
{
	$BASIC_LANGUAGE 	= new Properties("conf/language_".getLanguage().".conf");
	return htmlentities($BASIC_LANGUAGE->get($confpara));
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz

 Fragt die Konfiguration ab, ob die Tooltips	angezeigt werden sollen oder nicht.
 --------------------------------------------------------------------------------*/
function showTooltips()
{
	$BASIC_CONFIG 		= new Properties("conf/basic.conf");
	return $BASIC_CONFIG->get("showTooltips");
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt eine Dropdown-Liste mit den allen möglichen und belegten Positionen zurueck 
 --------------------------------------------------------------------------------*/
function show_dirs($maindir, $selecteddir)
{
	$content = "<select name=\"position\" size=1>";
	global $specialchars;
	$vergeben = getDirs($maindir);

	for($pos = 0; $pos < 100; $pos++ )
	{
		if(!in_array($pos,$vergeben))
		{
			$content .= "<option>";
			$content .= addFrontZero($pos);
			$content .= "</option>";
		}
		else
		{
			$selected = "";
			if (addFrontZero($pos)."_".specialNrDir($maindir, addFrontZero($pos)) == $selecteddir)
			$selected = "selected=\"selected\" ";
			$content .= "<option style=\"color:lightgrey;\"$selected>";
			$content .= addFrontZero($pos)." ".$specialchars->rebuildSpecialChars(specialNrDir($maindir, addFrontZero($pos)), true);
			$content .= "</option>";
		}
	  
	}
	$content .= "</select>";
	 
	return $content;
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt eine Dropdown-Liste mit den allen möglichen und belegten Positionen zurueck 
 --------------------------------------------------------------------------------*/
function show_files($dir, $currentfile, $includedrafts)
{
	global $specialchars;
	global $EXT_PAGE;
	global $EXT_HIDDEN;
	global $EXT_DRAFT;

	$content = "<select name=\"position\" class=\"select1\" size=1>";
	if ($includedrafts) {
		$vergeben = getFiles($dir, "");
	}
	else {
		$vergeben = getFiles($dir, ".tmp");
	}
	sort($vergeben);
	
	for($pos = 0; $pos < 100; $pos++ ) {
		// Position ist frei: Nummer anzeigen
		if(!in_array($pos,$vergeben)) {
			$content .= "<option>";
			$content .= addFrontZero($pos);
			$content .= "</option>";
		}
		// Position ist belegt: Seintennamen anzeigen
		else {
			if (
				(specialNrDir($dir, addFrontZero($pos)) == $currentfile.$EXT_PAGE) 
				|| (specialNrDir($dir, addFrontZero($pos)) == $currentfile.$EXT_HIDDEN) 
				|| (specialNrDir($dir, addFrontZero($pos)) == $currentfile.$EXT_DRAFT)
				) {
				$selected = "selected=\"selected\" ";
			}
			else {
				$selected = " ";
			}
			$content .= "<option ".$selected."style=\"color:lightgrey;\">";
			$fullname = $specialchars->rebuildSpecialChars(specialNrDir($dir, addFrontZero($pos)), true);
			$content .= addFrontZero($pos)." ".substr($fullname, 0, strlen($fullname)-strlen(".txt"));
			$content .= "</option>";
		}
	}
	$content .= "</select>";

	return $content;
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt alle enthaltenen Ordner in ein Array aus
 --------------------------------------------------------------------------------*/
function getDirs($dir)
{
	global $specialchars;
	$vergeben = array();
	if (is_dir($dir))
	{
		$handle = opendir($dir);
		while($file = readdir($handle))
		{
			if(isValidDirOrFile($file) && !is_file($file))
			{
				array_push($vergeben, substr($file,0,2));
			}
		}
		closedir($handle);
	}
	sort($vergeben);
	return $vergeben;
}

/**--------------------------------------------------------------------------------
 @author: Arvid Zimmermann
 Gibt alle enthaltenen Dateien in ein Array aus
 --------------------------------------------------------------------------------*/
function getFiles($dir, $excludeextension)
{
	$dir = stripslashes($dir);
	$files = array();
	$handle = opendir($dir);
	while($file = readdir($handle)) {
		if(isValidDirOrFile($file) && ($file != "dateien")) {
			// auszuschließende Extensions nicht berücksichtigen
			if ($excludeextension != "") {
				if (substr($file, strlen($file)-4, strlen($file)) != "$excludeextension")
				array_push($files, $file);
			}
			else
			array_push($files, $file);
		}
	}
	closedir($handle);
	return $files;
}

/*--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Sucht nach einem Ordner der mit einer Bestimmten Nummern-Praefix beginnt
 --------------------------------------------------------------------------------*/
function specialNrDir($dir, $nr)
{
	$dir = stripslashes($dir);
	if (!is_file($dir)){
		$handle = opendir($dir);
		$vergeben = array();
		while($file = readdir($handle))
		{
			if(isValidDirOrFile($file) && !is_file($file))
			{
				if(substr($file,0,2)==$nr)
				{
					return substr($file,3);
				}
			}
		}
		closedir($handle);
	}
}

/*--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Sucht nach einer Datei, die mit einer Bestimmten Nummern-Praefix beginnt
 --------------------------------------------------------------------------------*/
function specialNrFile($dir, $nr) {
	$dir = stripslashes($dir);
	if (!is_file($dir)){
		$handle = opendir($dir);
		while($file = readdir($handle)) {
			if(isValidDirOrFile($file) && is_file($file)) {
				if(substr($file,0,2)==$nr) {
					return substr($file,3);
				}
			}
		}
		closedir($handle);
	}
}
 
/*--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Legt die Ordnerstuktur für eine neue Kategorie an
 --------------------------------------------------------------------------------*/
function createCategory()
{
	global $specialchars;
	global $CMS_CONF;
	$betterString = $specialchars->replaceSpecialChars($_REQUEST["name"]);
	mkdir ("../kategorien/".$_REQUEST["position"]."_".$betterString, 0777);
	mkdir ("../kategorien/".$_REQUEST["position"]."_".$betterString."/dateien", 0777);
	// chmod, wenn so eingestellt
	if ($CMS_CONF->get("chmodnewfiles") == "true") {
		chmod ("../kategorien/".$_REQUEST["position"]."_".$betterString, octdec($CMS_CONF->get("chmodnewfilesatts")));
		chmod ("../kategorien/".$_REQUEST["position"]."_".$betterString."/dateien", octdec($CMS_CONF->get("chmodnewfilesatts")));
	}

}

function getFreeDirs($dir)
{
	$dirarray = array();
	global $specialchars;
	$vergeben = getDirs($dir);

	for($pos = 0; $pos < 100; $pos++ )
	{
		if(!in_array($pos,$vergeben))
		{
			array_push($dirarray, addFrontZero($pos));
		}
	}
	return $dirarray;
}

function getCatsAsSelect($selectedcat) {
	global $specialchars;
	$dirs = array();
	$handle = opendir('../kategorien');
	while (($file = readdir($handle))) {
		if (isValidDirOrFile($file))
		array_push($dirs, $file);
	}
	closedir($handle);
	sort($dirs);
	$select = "<select name=\"cat\">";
	foreach ($dirs as $file) {
		if (($selectedcat <> "") && ($file == $selectedcat))
		$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</option>";
		else
		$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</option>";
	}
	$select .= "</select>";
	return $select;
}

function getGalleriesAsSelect($selectedgallery) {
	global $specialchars;
	$dirs = array();
	$handle = opendir('../galerien');
	while (($file = readdir($handle))) {
		if (isValidDirOrFile($file))
		array_push($dirs, $file);
	}
	closedir($handle);
	sort($dirs);
	$select = "<select name=\"gal\">";
	foreach ($dirs as $file) {
		if (($selectedgallery <> "") && ($file == $selectedgallery))
		$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true)."</option>";
		else
		$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true)."</option>";
	}
	$select .= "</select>";
	return $select;
}

// gibt Verzeichnisinhalte als Array zurück (ignoriert dabei Dateien, wenn $includefiles == true)
function getDirContentAsArray($dir, $includefiles) {
	$dircontent = array();
	if (is_dir($dir)) {
		$handle = opendir($dir);
		while($file = readdir($handle)) {
			if(isValidDirOrFile($file)) {
				// wenn $includefiles true ist, werden auch Dateien ins Array gesteckt; sonst nur Verzeichnisse
				if (($includefiles == true) || !is_file($file))
				array_push($dircontent, $file);
			}
		}
		closedir($handle);
	}
	natcasesort($dircontent);
	return $dircontent;
}

function dirsize($dir) {
   if (!is_dir($dir)) return FALSE;
   $size = 0;
   $dh = opendir($dir);
   while(($entry = readdir($dh)) !== false) {
      if(!isValidDirOrFile($entry)) 
         continue;
      if(is_dir( $dir . "/" . $entry))
         $size += dirsize($dir . "/" . $entry);
      else
         $size += filesize($dir . "/" . $entry);
   }
   closedir($dh);
   return $size;
}

function convertFileSizeUnit($filesize){
	if ($filesize < 1024)
		return $filesize . "&nbsp;B";
	elseif ($filesize < 1048576)
		return round(($filesize/1024) , 2) . "&nbsp;KB";
	else
		return round(($filesize/1024/1024) , 2) . "&nbsp;MB";
}

// ------------------------------------------------------------------------------
// Handelt es sich um ein valides Verzeichnis / eine valide Datei?
// ------------------------------------------------------------------------------
	function isValidDirOrFile($file) {
		return (!in_array($file, array(
				".", // aktuelles Verzeichnis 
				"..", // Parent-Verzeichnis
				"Thumbs.db", // Windows-spezifisch
				".DS_Store", // Mac-spezifisch
				"__MACOSX", // Mac-spezifisch
				".svn",	// SVN
				".cache", // Eclipse
				"settings" // Eclipse 
				)));
	}


// ------------------------------------------------------------------------------
// Ändert Referenzen auf eine Inhaltsseite in allen anderen Inhaltsseiten
// ------------------------------------------------------------------------------
	function updateReferencesInAllContentPages($oldCategory, $oldPage, $newCategory, $newPage) {
		global $CONTENT_DIR_REL;
		
		// Alle Kategorien einlesen
		$contentdirhandle = opendir($CONTENT_DIR_REL);
		while($currentcategory = readdir($contentdirhandle)) {
			if(isValidDirOrFile($currentcategory)) {
				// Alle Inhaltseiten der aktuellen Kategorie einlesen 
				$cathandle = opendir($CONTENT_DIR_REL."/".$currentcategory);
				while($currentpage = readdir($cathandle)) {
					if(isValidDirOrFile($currentpage) && is_file($CONTENT_DIR_REL."/".$currentcategory."/".$currentpage)) {
						// Datei öffnen
						$pagehandle = @fopen($CONTENT_DIR_REL."/".$currentcategory."/".$currentpage, "r");
						// Inhalt auslesen
						$pagecontent = @fread($pagehandle, @filesize($CONTENT_DIR_REL."/".$currentcategory."/".$currentpage));
						// Datei schließen
						@fclose($pagehandle);
						// Referenzen im Inhalt ersetzen
						$result = updateReferencesInText($pagecontent, $currentcategory, $oldCategory, $oldPage, $newCategory, $newPage);
						// Ersetzung nur vornehmen, wenn überhaupt Referenzen auftauchen
						if ($result[0]) {
						// Inhaltsseite speichern
							saveContentToPage($result[1], $CONTENT_DIR_REL."/".$currentcategory."/".$currentpage);
						}
					}
				}
				closedir($cathandle);
			}
		}
		closedir($contentdirhandle);

		
		//echo "updateReferences($oldCategory, $oldPage, $newCategory, $newPage)";
	}
		
// ------------------------------------------------------------------------------
// Ändert Referenzen auf eine Inhaltsseite in einem übergebenen Text
// ------------------------------------------------------------------------------
	function updateReferencesInText($currentPagesContent, $currentPagesCategory, $oldCategoryOfMovedPage, $oldNameOfMovedPage, $newCategoryOfMovedPage, $newNameOfMovedPage) {
		global $specialchars;
		
		$currentPagesCategory 	= $specialchars->rebuildSpecialChars($currentPagesCategory,true);
		$oldCategoryOfMovedPage = $specialchars->rebuildSpecialChars($oldCategoryOfMovedPage,true);
		$newCategoryOfMovedPage = $specialchars->rebuildSpecialChars($newCategoryOfMovedPage,true);
		$newNameOfMovedPage 	= $specialchars->rebuildSpecialChars($newNameOfMovedPage,true);
		$oldNameOfMovedPage 	= $specialchars->rebuildSpecialChars($oldNameOfMovedPage,true);
		
		// Fileextension wegschneiden
		$currentPagesCategory 	= substr($currentPagesCategory,3,strlen($currentPagesCategory));
		$oldCategoryOfMovedPage = substr($oldCategoryOfMovedPage,3,strlen($oldCategoryOfMovedPage));
		$newCategoryOfMovedPage = substr($newCategoryOfMovedPage,3,strlen($newCategoryOfMovedPage));
		$newNameOfMovedPage 	= substr($newNameOfMovedPage,3,strlen($newNameOfMovedPage)-7);
		$oldNameOfMovedPage 	= substr($oldNameOfMovedPage,3,strlen($oldNameOfMovedPage)-7);
		
		// Flag: muß wirklich was ersetzt werden?
		$changesmade = false;

		// Nach Texten in eckigen Klammern suchen
		preg_match_all("/\[([^\[\]]+)\|([^\[\]]*)\]/Um", $currentPagesContent, $matches);
		$i = 0;
		// Für jeden Treffer...
		foreach ($matches[0] as $match) {
			// ...Auswertung und Verarbeitung der Informationen
			$attribute = $matches[1][$i];
			$value = $matches[2][$i];
			
			// Link auf Inhaltsseite in aktueller oder anderer Kategorie (überprüfen, ob Inhaltsseite existiert)
			if ($attribute == "seite" || substr($attribute,0,6) == "seite=") {
				
				$seite = "";
				$kategorie = "";
				

				$valuearray = explode(":", $value);
				if (count($valuearray) == 2) 
				{
					$kategorie = $valuearray[0];
					$seite = $valuearray[1];
				}
				else
				{
					$seite = $valuearray[0];
				}
				
				if($newCategoryOfMovedPage != $oldCategoryOfMovedPage)
				{
					$kategorie = $newCategoryOfMovedPage;
					$changesmade = true;
				}
				
				if($newNameOfMovedPage != $oldNameOfMovedPage)
				{
					$seite = $newNameOfMovedPage;
					$changesmade = true;
				}
				
				if ($changesmade && $seite == $oldNameOfMovedPage) {
					// Inhaltsseite liegt in "meiner" Kategorie
					if ($kategorie == "") {
						$currentPagesContent = str_replace ("$match", "[".$attribute."|".html_entity_decode($seite)."]", $currentPagesContent);
					}
					// Inhaltsseite ist in anderer Kategorie
					else {
						$currentPagesContent = str_replace ("$match", "[".$attribute."|".html_entity_decode($kategorie).":".html_entity_decode($seite)."]", $currentPagesContent);
					}
				}
			}
			$i++;
		}
		
		// Konvertierten Seiteninhalt zurückgeben
    return array($changesmade, $currentPagesContent);
	}

?>
