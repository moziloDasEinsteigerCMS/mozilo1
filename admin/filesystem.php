<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
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
	return htmlentities($BASIC_LANGUAGE->get($confpara),ENT_COMPAT,'ISO-8859-1');
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
			$content .= addFrontZero($pos)." ".$specialchars->rebuildSpecialChars(specialNrDir($maindir, addFrontZero($pos)), true, true);
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
				(specialNrFile($dir, addFrontZero($pos)) == $currentfile.$EXT_PAGE) 
				|| (specialNrFile($dir, addFrontZero($pos)) == $currentfile.$EXT_HIDDEN) 
				|| (specialNrFile($dir, addFrontZero($pos)) == $currentfile.$EXT_DRAFT)
				) {
				$selected = "selected=\"selected\" ";
			}
			else {
				$selected = " ";
			}
			$content .= "<option ".$selected."style=\"color:lightgrey;\">";
			$fullname = $specialchars->rebuildSpecialChars(specialNrFile($dir, addFrontZero($pos)), true, true);
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
			if(isValidDirOrFile($file) && !is_file("$dir/$file"))
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
			if(isValidDirOrFile($file) && !is_file("$dir/$file"))
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
			if(isValidDirOrFile($file) && is_file("$dir/$file")) {
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
	global $ADMIN_CONF;
	
	$betterString = $specialchars->replaceSpecialChars($_REQUEST["name"],false);
	mkdir ("../kategorien/".$_REQUEST["position"]."_".$betterString, 0777);
	mkdir ("../kategorien/".$_REQUEST["position"]."_".$betterString."/dateien", 0777);
	// chmod, wenn so eingestellt
	if ($ADMIN_CONF->get("chmodnewfiles") == "true") {
		$mode = $ADMIN_CONF->get("chmodnewfilesatts");
        // X-Bit setzen, um Verzeichniszugriff zu garantieren
		if(substr($mode,0,1) >=2 and substr($mode,0,1) <= 6) $mode = $mode + 100;
		if(substr($mode,1,1) >=2 and substr($mode,1,1) <= 6) $mode = $mode + 10;
		if(substr($mode,2,1) >=2 and substr($mode,2,1) <= 6) $mode = $mode + 1;
		chmod ("../kategorien/".$_REQUEST["position"]."_".$betterString, octdec($mode));
		chmod ("../kategorien/".$_REQUEST["position"]."_".$betterString."/dateien", octdec($mode));
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
		$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true, true)."</option>";
		else
		$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true, true)."</option>";
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
		$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true, true)."</option>";
		else
		$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true, true)."</option>";
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
				if (($includefiles == true) || !is_file("$dir/$file"))
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
		# Wichtig !!!!!!
		# Rename CAT: $oldPage und $newPage müssen leer sein, $oldCategory und $newCategory aber gesetzt
		# Rename PAGE: $newCategory muss leer sein, $oldCategory, $oldPage und $newPage aber gesetzt
		# Move PAGE: Alle müssen gefüllt sein
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
						$result = updateReferencesInText($pagecontent, $currentcategory, $currentpage, $oldCategory, $oldPage, $newCategory, $newPage);
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
	}
		
	// ------------------------------------------------------------------------------
	// Ändert Referenzen auf eine Inhaltsseite in einem übergebenen Text
	// ------------------------------------------------------------------------------
	function updateReferencesInText($currentPagesContent, $currentPagesCategory, $movedPage, $oldCategory, $oldPage, $newCategory, $newPage) {
		global $specialchars;
		global $CONTENT_DIR_REL;

		$pos_currentPagesCategory 	= $specialchars->rebuildSpecialChars($currentPagesCategory,false,false);
		$pos_oldCategory		= $specialchars->rebuildSpecialChars($oldCategory,false,false);
		$pos_oldPage			= $specialchars->rebuildSpecialChars($oldPage,false,false);
		$pos_newCategory 		= $specialchars->rebuildSpecialChars($newCategory,false,false);
		$pos_newPage 			= $specialchars->rebuildSpecialChars($newPage,false,false);
		$movedPage 			= $specialchars->rebuildSpecialChars($movedPage,false,false);

		$changesmade = false;

		# ein Hack weil in Inhaltsete ein ^ vor [ und ] ist im Dateinamen aber nicht
		$hack_eckigeklamern = str_replace(array("[","]"),array("&#94;[","&#94;]"),array($pos_oldCategory,$pos_oldPage,$pos_newCategory,$pos_newPage));

		$oldCategory	= html_entity_decode(substr($hack_eckigeklamern[0],3),ENT_COMPAT,'ISO-8859-1');
		$oldPage	= html_entity_decode(substr($hack_eckigeklamern[1],3,-4),ENT_COMPAT,'ISO-8859-1');
		$newCategory 	= html_entity_decode(substr($hack_eckigeklamern[2],3),ENT_COMPAT,'ISO-8859-1');
		$newPage 	= html_entity_decode(substr($hack_eckigeklamern[3],3,-4),ENT_COMPAT,'ISO-8859-1');

		# ein Hack weil dieses preg_match_all nicht mit ^, [ und ] im attribut umgehen kann
		$currentPagesContentmatches = str_replace(array("^[","^]"),array("&#94;&#091;","&#94;&#093;"),$currentPagesContent);
		// Nach Texten in eckigen Klammern suchen
		preg_match_all("/\[([^\[\]]+)\|([^\[\]]*)\]/Um", $currentPagesContentmatches, $matches);
		$i = 0;
	
		$allowed_attributes = array("seite","kategorie","datei","bild","bildlinks","bildrechts","include");
	
		// Für jeden Treffer...
		foreach ($matches[0] as $match) {
			# ein Hack weil dieses preg_match_all nicht mit ^, [ und ] im attribut umgehen kann
			$match = str_replace(array("&#94;&#091;","&#94;&#093;"),array("^[","^]"),$match);
			// ...Auswertung und Verarbeitung der Informationen
			$attribute = $matches[1][$i];
			$replace_match = "";
			if(strstr($attribute,"=")) {
				$allowed_test = substr($attribute,0,strpos($attribute,"="));

			} else {
				$allowed_test = $attribute;
			}
			if(in_array($allowed_test,$allowed_attributes))
			{
$debug = false;
if($debug) echo "match = $match -----------<br>\n";
if($debug) echo "datei = $pos_currentPagesCategory/$movedPage<br>\n";
				# weil oldPage und newPage lehr sind Kategorie rename
				if(!empty($oldCategory) and !empty($newCategory) and empty($oldPage) and empty($newPage))
				{
					# einfach alle oldCategory -> newCategory
					if(strstr($match,"|".$oldCategory.":") or strstr($match,"|".$oldCategory."]"))
					{
						$replace_match = str_replace($oldCategory,$newCategory,$match);
if($debug) echo "cat = $match -> $replace_match<br>\n";
					}
				}
				# weil newCategory lehr Inhaltseite rename
				if(!empty($oldCategory) and empty($newCategory) and !empty($oldPage) and !empty($newPage))
				{
					# ist [attribut|oldCategory:oldPage] dann oldPage -> newPage
					# oder ist [attribut|oldPage] und die untersuchende datei in oldCategory dann oldPage -> newPage
					if((strstr($match,"|$oldCategory:$oldPage]") or (strstr($match,"|$oldPage]")
					and $pos_oldCategory == $pos_currentPagesCategory )))
					{
						$replace_match = str_replace($oldPage,$newPage,$match);
if($debug) echo "page = $match -> $replace_match<br>\n";
					}
				}
				# alles voll dann move Inhaltseite in andere Kategorie
				if(!empty($oldCategory) and !empty($newCategory) and !empty($oldPage) and !empty($newPage))
				{
					# weil in der zu bearbeitende Inhaltseite ein Object ist
					# das in alten Kategorie liegt neue Kategorie einfügen
					if($movedPage == $pos_newPage
					and !strstr($match,":")
					and $oldCategory != $newCategory)
					{
						$replace_match = str_replace("|","|$oldCategory:",$match);
if($debug) echo "+++cat = $match -> $replace_match<br>\n";
						}
					# weil in der zu bearbeitende Inhaltseite ein Object ist
					# das in der Kategorie liegt in die die Inhaltseite verschoben wird,
					# Kategorie entfernen
					elseif($movedPage == $pos_newPage
					and strstr($match,":")
					and $pos_currentPagesCategory == $pos_newCategory)
					{
						$replace_match = str_replace("|$newCategory:","|",$match);
if($debug) echo "---cat = $match -> $replace_match<br>\n";
					}
					# alle andern Inhaltseiten die [attribut|oldCategory:oldPage] enthalten ändern
					elseif(strstr($match,"|$oldCategory:$oldPage]"))
					{
						$replace_match = str_replace("$oldCategory:$oldPage","$newCategory:$newPage",$match);
if($debug) echo "cat_page = $match -> $replace_match<br>\n";
					}
				}
				# änderung nur wenn was geändert wurde
				if(!empty($replace_match) and $matches[0][$i] != $replace_match) {
					# ein Hack weil dieses preg_match_all nicht mit ^, [ und ] im attribut umgehen kann
					$matches[0][$i] = str_replace(array("&#94;&#091;","&#94;&#093;"),array("^[","^]"),$matches[0][$i]);
					$currentPagesContent = str_replace ($matches[0][$i], $replace_match, $currentPagesContent);
if($debug) echo "diff == match = ".$matches[0][$i]." | replace_match = $replace_match<br>\n";
					$changesmade = true;
				}
if($debug) echo "<br>\n";
			}	
		$i++;
		}
	// Konvertierten Seiteninhalt zurückgeben
	return array($changesmade, $currentPagesContent);
	}
		

?>
