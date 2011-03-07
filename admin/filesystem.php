<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/


require_once($BASE_DIR_CMS."SpecialChars.php");

$specialchars = new SpecialChars();
require_once($BASE_DIR_CMS."Properties.php");
/* Variablen */

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Liest aus dem Language-File eine Bistimmte Variable    aus.
 --------------------------------------------------------------------------------*/
function getLanguageValue($confpara,$title = false)
{
    global $BASIC_LANGUAGE;
    global $CHARSET;
    if(isset($_REQUEST['javascript']) and $title) {
        return NULL;
    }
    $text = htmlentities($BASIC_LANGUAGE->get($confpara),ENT_COMPAT,$CHARSET);
    if(empty($text)) {
        return "FEHLER = ".$confpara;
    }
    $text = str_replace(array("&lt;","&gt;"),array("<",">"), $text);
    return $text;
}

/**--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Gibt alle enthaltenen Ordner in ein Array aus
 --------------------------------------------------------------------------------*/
function getDirs($dir,$complet = false,$exclude_link = false)
{

    $vergeben = array();
    if (is_dir($dir))
    {
        $handle = opendir($dir);
        while($file = readdir($handle))
        {
            if($exclude_link !== false and preg_match('/-_blank-|-_self-/', $file)) {
                continue;
            }
            if(isValidDirOrFile($file) && !is_file("$dir/$file"))
            {
                if($complet === false)
                    array_push($vergeben, substr($file,0,2));
                else
                    array_push($vergeben,$file);
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
    global $CONTENT_FILES_DIR_NAME;
    //$dir = stripslashes($dir);
    $files = array();
    $handle = opendir($dir);
    while($file = readdir($handle)) {
        if(isValidDirOrFile($file) && ($file != $CONTENT_FILES_DIR_NAME)) {
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
    if (is_dir($dir)){
        $handle = opendir($dir);
        while($file = readdir($handle))
        {
            if(isValidDirOrFile($file) and is_dir("$dir/$file"))
            {
                if(substr($file,0,2)==$nr)
                {
                    closedir($handle);
                    return substr($file,3);
                }
            }
        }
    }
}

/*--------------------------------------------------------------------------------
 @author: Oliver Lorenz
 Legt die Ordnerstuktur für eine neue Kategorie an
 --------------------------------------------------------------------------------*/
function createCategory($new_cat) {
    global $specialchars;
    global $ADMIN_CONF;
    global $CONTENT_DIR_NAME;
    global $CONTENT_FILES_DIR_NAME;
    # dummy fehlermeldung erzeugen
    @chmod();
    @mkdir ("../".$CONTENT_DIR_NAME."/".$new_cat);
    $line_error = __LINE__ - 1;
    $last_error['line'] = NULL;
    if(function_exists("error_get_last")) {
        $last_error = @error_get_last();
    }
    if($last_error['line'] == $line_error) {
        $error['php_error'][] = $last_error['message'];
    } elseif(!is_dir("../".$CONTENT_DIR_NAME."/".$new_cat)) {
        $error['category_error_new'][] = $new_cat;
    }
    # ist kein Link
    if(!preg_match('/-_blank-|-_self-/', $new_cat)) {
        @mkdir ("../".$CONTENT_DIR_NAME."/".$new_cat."/".$CONTENT_FILES_DIR_NAME);
        $line_error = __LINE__ - 1;
        $last_error['line'] = NULL;
        if(function_exists("error_get_last")) {
            $last_error = @error_get_last();
        }
        if($last_error['line'] == $line_error) {
            $error['php_error'][] = $last_error['message'];
        } elseif(!is_dir("../".$CONTENT_DIR_NAME."/".$new_cat."/".$CONTENT_FILES_DIR_NAME)) {
            $error['category_error_new'][] = $new_cat."/".$CONTENT_FILES_DIR_NAME;
        }
    }
    if(isset($error['php_error']) or isset($error['category_error_new'])) {
        # wenns hier schonn ne meldung gibt dann gleich Raus
        return $error;
    }
    # bis hier kein fehler dann solte das chmod auch fehlerfrei gehen
    useChmod("../".$CONTENT_DIR_NAME."/".$new_cat);
    # ist kein Link
    if(!preg_match('/-_blank-|-_self-/', $new_cat)) {
        useChmod("../".$CONTENT_DIR_NAME."/".$new_cat."/".$CONTENT_FILES_DIR_NAME);
    }
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
#function getDirContentAsArray($dir, $includefiles, $position = true) {
function getDirContentAsArray($dir, $hiddeposition = true) {
    $dircontent = array();
    if (is_dir($dir)) {
        $handle = opendir($dir);
        while($file = readdir($handle)) {
            if(isValidDirOrFile($file)) {
                if (!is_file("$dir/$file")) {
                    # wenn $hiddeposition = true keine Position
                    if($hiddeposition === true)
                        array_push($dircontent, substr($file,3));
                    else
                        array_push($dircontent, $file);
                }
            }
        }
        closedir($handle);
    }
    natcasesort($dircontent);
    return $dircontent;
}

function dirsize($dir) {
   if (!is_dir($dir) or !is_readable($dir)) return FALSE;
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
/*
// ------------------------------------------------------------------------------
// Handelt es sich um ein valides Verzeichnis / eine valide Datei?
// ------------------------------------------------------------------------------
function isValidDirOrFile($file) {
    # Alles was einen Punkt vor der Datei hat
    if(strpos($file,".") === 0) {
        return false;
    }
    # alle php Dateien
    if(substr($file,-4) == ".php") {
        return false;
    }
    # und der Rest
    if(in_array($file, array(
            "Thumbs.db", // Windows-spezifisch
            "__MACOSX", // Mac-spezifisch
            "settings" // Eclipse
            ))) {
        return false;
    }
    return true;
}
*/

// ------------------------------------------------------------------------------
// Ändert Referenzen auf eine Inhaltsseite in allen anderen Inhaltsseiten
// ------------------------------------------------------------------------------
function updateReferencesInAllContentPages($oldCategory, $oldPage, $newCategory, $newPage) {
    # Wichtig !!!!!!
    # Rename CAT: $oldPage und $newPage müssen leer sein, $oldCategory und $newCategory aber gesetzt
    # Rename PAGE: $newCategory muss leer sein, $oldCategory, $oldPage und $newPage aber gesetzt
    # Move PAGE: Alle müssen gefüllt sein
    global $CONTENT_DIR_REL;

    $error = NULL;
    // Alle Kategorien einlesen
    $contentdirhandle = opendir($CONTENT_DIR_REL);
    while($currentcategory = readdir($contentdirhandle)) {
        if(isValidDirOrFile($currentcategory)) {
            // Alle Inhaltseiten der aktuellen Kategorie einlesen 
            $cathandle = opendir($CONTENT_DIR_REL.$currentcategory);
            while($currentpage = readdir($cathandle)) {
                if(isValidDirOrFile($currentpage) && is_file($CONTENT_DIR_REL.$currentcategory."/".$currentpage)) {
                    // Datei öffnen
                    $pagehandle = @fopen($CONTENT_DIR_REL.$currentcategory."/".$currentpage, "r");
                    // Inhalt auslesen
                    $pagecontent = @fread($pagehandle, @filesize($CONTENT_DIR_REL.$currentcategory."/".$currentpage));
                    // Datei schließen
                    @fclose($pagehandle);
                    # um diese Attribute geht es
                    $allowed_attributes = array("seite","datei","bild","bildlinks","bildrechts","include");
                    # kommt eins von den Attributen im Content vor
                    # Suche nach [Attribut|
                    preg_match("/(\[".implode('=|\[',$allowed_attributes)."=).*/Umis",$pagecontent,$matches_1);
                    # Suche nach [Attribut=
                    preg_match("/(\[".implode('\||\[',$allowed_attributes)."\|).*/Umis",$pagecontent,$matches_2);
                    # nichts gefunden nächste seite
                    if(count($matches_1) == 0 and count($matches_2) == 0) continue;
                    // Referenzen im Inhalt ersetzen
                    $result = updateReferencesInText($pagecontent, $currentcategory, $currentpage, $oldCategory, $oldPage, $newCategory, $newPage, $allowed_attributes);
                    // Ersetzung nur vornehmen, wenn überhaupt Referenzen auftauchen
                    if ($result[0]) {
                        // Inhaltsseite speichern
                        $error_tmp = saveContentToPage($result[1], $CONTENT_DIR_REL.$currentcategory."/".$currentpage);
                        if(!empty($error_tmp)) {
                            if(is_array($error)) {
                                $error = array_merge_recursive($error,$error_tmp);
                            } else {
                                $error = $error_tmp;
                            }
                        }
                    }
                }
            }
            closedir($cathandle);
        }
    }
    closedir($contentdirhandle);
    return $error;
}
    
// ------------------------------------------------------------------------------
// Ändert Referenzen auf eine Inhaltsseite in einem übergebenen Text
// ------------------------------------------------------------------------------
function updateReferencesInText($currentPagesContent, $currentPagesCategory, $movedPage, $oldCategory, $oldPage, $newCategory, $newPage, $allowed_attributes) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $CHARSET;

    $pos_currentPagesCategory     = $specialchars->rebuildSpecialChars($currentPagesCategory,false,false);
    $pos_oldCategory        = $specialchars->rebuildSpecialChars($oldCategory,false,false);
    $pos_oldPage            = $specialchars->rebuildSpecialChars($oldPage,false,false);
    $pos_newCategory         = $specialchars->rebuildSpecialChars($newCategory,false,false);
    $pos_newPage             = $specialchars->rebuildSpecialChars($newPage,false,false);
    $movedPage             = $specialchars->rebuildSpecialChars($movedPage,false,false);

    $changesmade = false;

    # ein Hack weil in Inhaltsete ein ^ vor [ und ] ist im Dateinamen aber nicht
    $hack_eckigeklamern = str_replace(array("[","]"),array("&#94;[","&#94;]"),array($pos_oldCategory,$pos_oldPage,$pos_newCategory,$pos_newPage));

    $oldCategory    = $specialchars->getHtmlEntityDecode(substr($hack_eckigeklamern[0],3));
    $oldPage    = $specialchars->getHtmlEntityDecode(substr($hack_eckigeklamern[1],3,-4));
    $newCategory     = $specialchars->getHtmlEntityDecode(substr($hack_eckigeklamern[2],3));
    $newPage     = $specialchars->getHtmlEntityDecode(substr($hack_eckigeklamern[3],3,-4));

    # ein Hack weil dieses preg_match_all nicht mit ^, [ und ] im attribut umgehen kann
    $currentPagesContentmatches = str_replace(array("^[","^]"),array("&#94;&#091;","&#94;&#093;"),$currentPagesContent);
    // Nach Texten in eckigen Klammern suchen
    preg_match_all("/\[([^\[\]]+)\|([^\[\]]*)\]/Um", $currentPagesContentmatches, $matches);

#    $allowed_attributes = array("seite","kategorie","datei","bild","bildlinks","bildrechts","include");

    // Für jeden Treffer...
$debug = false; # true false
    foreach ($matches[0] as $i => $match) {
if($debug) echo "alle matches = $match -----------<br />\n";
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
if($debug) echo "match = $match -----------<br />\n";
if($debug) echo "datei = $pos_currentPagesCategory/$movedPage<br />\n";
            # weil oldPage und newPage lehr sind Kategorie rename
            if(!empty($oldCategory) and !empty($newCategory) and empty($oldPage) and empty($newPage))
            {
                # einfach alle oldCategory -> newCategory
                if(strstr($match,"|".$oldCategory.":") or strstr($match,"|".$oldCategory."]"))
                {
                    $replace_match = str_replace($oldCategory,$newCategory,$match);
if($debug) echo "cat = $match -> $replace_match<br />\n";
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
if($debug) echo "page = $match -> $replace_match<br />\n";
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
if($debug) echo "+++cat = $match -> $replace_match<br />\n";
                    }
                # weil in der zu bearbeitende Inhaltseite ein Object ist
                # das in der Kategorie liegt in die die Inhaltseite verschoben wird,
                # Kategorie entfernen
                elseif($movedPage == $pos_newPage
                and strstr($match,":")
                and $pos_currentPagesCategory == $pos_newCategory)
                {
                    $replace_match = str_replace("|$newCategory:","|",$match);
if($debug) echo "---cat = $match -> $replace_match<br />\n";
                }
                # alle andern Inhaltseiten die [attribut|oldCategory:oldPage] enthalten ändern
                elseif(strstr($match,"|$oldCategory:$oldPage]"))
                {
                    $replace_match = str_replace("$oldCategory:$oldPage","$newCategory:$newPage",$match);
if($debug) echo "cat_page = $match -> $replace_match<br />\n";
                }
            }
            # änderung nur wenn was geändert wurde
            if(!empty($replace_match) and $matches[0][$i] != $replace_match) {
                # ein Hack weil dieses preg_match_all nicht mit ^, [ und ] im attribut umgehen kann
                $matches[0][$i] = str_replace(array("&#94;&#091;","&#94;&#093;"),array("^[","^]"),$matches[0][$i]);
                $currentPagesContent = str_replace ($matches[0][$i], $replace_match, $currentPagesContent);
if($debug) echo "diff == match = ".$matches[0][$i]." | replace_match = $replace_match<br />\n";
                $changesmade = true;
            }
if($debug) echo "<br />\n";
        }    
    }
    // Konvertierten Seiteninhalt zurückgeben
    return array($changesmade, $currentPagesContent);
}

# gibt die Rechte zurück ist $dir true wird das x bit gesetzt
function getChmod($dir = false) {
    global $ADMIN_CONF;
    $mode = $ADMIN_CONF->get("chmodnewfilesatts");
    if(strlen($mode) > 0) {
        if($dir === true) {
            // X-Bit setzen, um Verzeichniszugriff zu garantieren
            if(substr($mode,0,1) >= 2 and substr($mode,0,1) <= 6) $mode = $mode + 100;
            if(substr($mode,1,1) >= 2 and substr($mode,1,1) <= 6) $mode = $mode + 10;
            if(substr($mode,2,1) >= 2 and substr($mode,2,1) <= 6) $mode = $mode + 1;
        }
        return octdec($mode);
    }
    # Der server Vergibt die Rechte
    return false;
}

# ändert die dateirechte
function changeChmod($file) {
    $error_new = NULL;
    $dir = NULL;
    if(is_dir($file)) {
        $dir = true;
    }
    # nicht zu tuhn
    if(getChmod() === false) {
        return $error_new;
    }
    @chmod($file, getChmod($dir));
    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
    $last_error['line'] = NULL;
    if(function_exists("error_get_last")) {
        $last_error = @error_get_last();
    }
    # clearstatcache() damit fileperms() sauber Arbeitet
    clearstatcache();
    if($last_error['line'] == $line_error) {
        # dummy fehlermeldung erzeugen
        @chmod();
        $error_new['php_error'] = $file." - ".$last_error['message'];
    } elseif(substr(decoct(fileperms($file)), -3) != decoct(getChmod($dir))) {
        $error_new['chmod_error'] = $file;
    }
    return $error_new;
}

# änder die dateirechte Recursiv wenn kein Parameter über geben wird das array $ordner benutzt
function useChmod($dir = false, $error = NULL) {
    global $error;
    global $CONTENT_DIR_NAME;
    global $CMS_DIR_NAME;

    if($dir === false) {
        $ordner = array("conf",
                        "../".$CMS_DIR_NAME."/conf",
                        "../".$CONTENT_DIR_NAME,"../galerien");
        foreach($ordner as $dirs) {
            $error_tmp = useChmod($dirs,$error);
            if(is_array($error_tmp)) {
                $error[key($error_tmp)] = $error_tmp[key($error_tmp)];
            }
        }
        return $error;
    } else {
        # nicht zu tuhn
        if(getChmod() === false) {
            return;
        }
        if(is_dir($dir)) {
            $error_tmp = changeChmod($dir);
            if(is_array($error_tmp)) {
                $error[key($error_tmp)][] = $error_tmp[key($error_tmp)];
            }
            $handle = opendir($dir);
            while($file = readdir($handle)) {
                if(isValidDirOrFile($file)) {
                    if(is_dir($dir.'/'.$file)) {
                        $error_tmp = useChmod($dir.'/'.$file,$error);
                        if(is_array($error_tmp)) {
                            $error[key($error_tmp)] = $error_tmp[key($error_tmp)];
                        }
                    } elseif(is_file($dir.'/'.$file)) {
                        $error_tmp = changeChmod($dir.'/'.$file);
                        if(is_array($error_tmp)) {
                            $error[key($error_tmp)][] = $error_tmp[key($error_tmp)];
                        }
                    }
                }
            }
            closedir($handle);
        } elseif(is_file($dir)) {
            $error_tmp = changeChmod($dir);
            if(is_array($error_tmp)) {
                $error[key($error_tmp)][] = $error_tmp[key($error_tmp)];
            }
        }
        return $error;
    }
}


?>
