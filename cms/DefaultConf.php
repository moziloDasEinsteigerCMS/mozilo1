<?php

$CHARSET                = 'UTF-8';

$CONTENT_DIR_NAME       = "kategorien";
$CONTENT_FILES_DIR_NAME = "dateien";
$PLUGIN_DIR_NAME        = "plugins";
$GALLERIES_DIR_NAME     = "galerien";
$PREVIEW_DIR_NAME       = "vorschau";
// Dateiendungen fuer Inhaltsseiten
# Achtung die endungen muessen alle gleich lang sein
$EXT_PAGE       = ".txt";
$EXT_HIDDEN     = ".hid";
$EXT_DRAFT      = ".tmp";
$EXT_LINK       = ".lnk";

define("CHARSET",$CHARSET);
define("CONTENT_DIR_NAME",$CONTENT_DIR_NAME);
define("CONTENT_FILES_DIR_NAME",$CONTENT_FILES_DIR_NAME);
define("PLUGIN_DIR_NAME",$PLUGIN_DIR_NAME);
define("GALLERIES_DIR_NAME",$GALLERIES_DIR_NAME);
define("PREVIEW_DIR_NAME",$PREVIEW_DIR_NAME);
define("EXT_PAGE",$EXT_PAGE);
define("EXT_HIDDEN",$EXT_HIDDEN);
define("EXT_DRAFT",$EXT_DRAFT);
define("EXT_LINK",$EXT_LINK);

function cleanREQUEST($post_return) {
    foreach($post_return as $key => $value) {
        if(is_array($post_return[$key])) {
            $post_return[$key] = cleanREQUEST($post_return[$key]);
        } else {
            // Nullbytes abfangen!
            if (strpos("tmp".$value, "\x00") > 0 or strpos("tmp".$key, "\x00") > 0) {
                die();
            }
            # auf manchen Systemen mus ein stripslashes() gemacht werden
            if(strpos("tmp".$value,'\\') > 0
                and  addslashes(stripslashes($value)) == $value) {
                $value = stripslashes($value);
            }
            # auf manchen Systemen mus ein stripslashes() gemacht werden
            if(strpos("tmp".$key,'\\') > 0
                and  addslashes(stripslashes($key)) == $key) {
                $value = stripslashes($key);
            }
            $post_return[$key] = $value;
        }
    }
    return $post_return;
}

# Alle Platzhalter
function makePlatzhalter($all = false) {
    # Alle Platzhalter für die Selctbox im Editor als array
    $platzhalter = array(
                        '{BASE_URL}',
                        '{CATEGORY_NAME}',
                        '{CATEGORY}',
                        '{CATEGORY_URL}',
                        '{PAGE_NAME}',
                        '{PAGE_FILE}',
                        '{PAGE_URL}',
                        '{PAGE}',
                        '{SEARCH}',
                        '{SITEMAPLINK}',
                        '{CMSINFO}',
                        '{TABLEOFCONTENTS}'
    );
    # Die Rstlichen Platzhalter
    $platzhalter_rest = array(
                        '{CSS_FILE}',
                        '{CHARSET}',
                        '{FAVICON_FILE}',
                        '{LAYOUT_DIR}',
                        '{WEBSITE_TITLE}',
                        '{WEBSITE_KEYWORDS}',
                        '{WEBSITE_DESCRIPTION}',
                        '{WEBSITE_NAME}',
                        '{MAINMENU}',
                        '{DETAILMENU}',
                        '{MEMORYUSAGE}',
                        '{EXECUTETIME}'
    );

    if($all) {
        $platzhalter = array_merge($platzhalter,$platzhalter_rest);
    }
    return $platzhalter;
}

# $conf_datei = voller pfad und conf Dateiname oder nur Array Name
function makeDefaultConf($conf_datei) {
    $basic = array(
                    'text' => array(
                        'adminmail' => '',
                        'language' => 'deDE',
                        'noupload' => 'php,php3,php4,php5'),
                    'digit' => array(
                        'backupmsgintervall' => '30',
                        'chmodnewfilesatts' => '',
                        'lastbackup' => time(),
                        'maximageheight' => '',
                        'maximagewidth' => '',
                        'maxnumberofuploadfiles' => '5',
                        'textareaheight' => '270'),
                    'checkbox' => array(
                        'overwriteuploadfiles' => 'false',
                        'sendadminmail' => 'false',
                        'showTooltips' => 'true',
                        'usebigactionicons' => 'false',
                        'showexpert' => 'false'),
                    # das sind die Expert Parameter von basic
                    'expert' => array(
                        'noupload',
                        'backupmsgintervall',
                        'lastbackup',
                        'maxnumberofuploadfiles',
                        'showTooltips',
                        'textareaheight',
                        'usebigactionicons',
                        'overwriteuploadfiles')
                    );

    $main = array(
                    'text' => array(
                        'shortenlinks' => '0',
                        'titlebarseparator' => '%20%3A%3A%20',
                        'usesubmenu' => '1',
                        'websitedescription' => '',
                        'websitekeywords' => '',
                        'websitetitle' => 'moziloCMS%20-%20Das%20CMS%20f%C3%BCr%20Einsteiger'),
                    'select' => array(
                        'cmslanguage' => 'deDE',
                        'cmslayout' => 'moziloCMS',
                        'defaultcat' => '10_Willkommen',
                        'titlebarformat' => '%7BWEBSITE%7D'),
                    'checkbox' => array(
                        'hidecatnamedpages' => 'false',
                        'modrewrite' => 'false',
                        'replaceemoticons' => 'true',
                        'showhiddenpagesasdefaultpage' => ' false',
                        'showhiddenpagesincmsvariables' => ' false',
                        'showhiddenpagesinlastchanged' => 'false',
                        'showhiddenpagesinsearch' => 'false',
                        'showhiddenpagesinsitemap' => 'false',
                        'showsyntaxtooltips' => 'true',
                        'targetblank_download' => 'true',
                        'targetblank_link' => 'true',
                        'usecmssyntax' => 'true',
                        'usecmseditarea' => 'true'),
                    # das sind die Expert Parameter von main
                    'expert' => array(
                        'hidecatnamedpages',
                        'modrewrite',
                        'showhiddenpagesasdefaultpage',
                        'showhiddenpagesincmsvariables',
                        'showhiddenpagesinlastchanged',
                        'showhiddenpagesinsearch',
                        'showhiddenpagesinsitemap',
                        'targetblank_download',
                        'targetblank_link',
                        'showsyntaxtooltips',
                        'replaceemoticons',
                        'shortenlinks',
                        'usecmssyntax',
                        'usesubmenu',
                        'usecmseditarea')
                    );

    $syntax = array('wikipedia' => '[link={DESCRIPTION}|http://de.wikipedia.org/wiki/{VALUE}]');
/*
    $formular = array('formularmail' => '',
                        'contactformusespamprotection' => 'true',
                        'contactformwaittime' => '15',
                        'mail' => ',true,true',
                        'message' => ',true,true',
                        'name' => ',true,true',
                        'website' => ',true,false');
*/
    $logindata = array('falselogincount' => '0',
                        'falselogincounttemp' => '0',
                        'initialpw' => 'true',
                        'initialsetup' => 'true',
                        'loginlockstarttime' => '',
                        'name' => 'admin',
                        'pw' => '19ad89bc3e3c9d7ef68b89523eff1987');

    $downloads = array('_downloadcounterstarttime' => time());

    $version = array('cmsversion' => '1.12.php7',
                        'cmsname' => 'Amalia',
                        'revision' => '958');

    $gallery = array('digit' => array(
                        'maxheight' => '',
                        'maxwidth' => '',
                        'maxthumbheight' => '100',
                        'maxthumbwidth' => '100'),
##                  'checkbox' => array(
##                      'createthumbs' => 'true'),
                    'expert' => array(
##                      'createthumbs',
                        'maxthumbheight',
                        'maxthumbwidth')
                    );

    $plugin = array('active' => 'true');

    $passwords = array('# Kategorie/Inhaltsseite' => 'password');

    # ist eine *.conf datei angegeben wird das jeweilige array ohne expert und nur der inhalt der subarrays zurückgegeben
    if(strpos($conf_datei,".conf") > 0) {
        $name = substr(basename($conf_datei),0,-(strlen(".conf")));
        # beim erzeugen duerfen sub arrays nicht mit rein
        foreach($$name as $key => $value) {
            if($key == "expert") continue;
            if(is_array($value)) {
                foreach($value as $key => $value) {
                    $return_array[$key] = $value;
                }
            } else {
                $return_array = $$name;
                break;
            }
        }
        return $return_array;
    # ist es keine *.conf einfach das ganze array zurück
    } else {
        return $$conf_datei;
    }
}

// ------------------------------------------------------------------------------
// Handelt es sich um ein valides Verzeichnis / eine valide Datei?
// ------------------------------------------------------------------------------
function isValidDirOrFile($file) {
    # Alles, was einen Punkt vor der Datei hat
    if(strpos($file,".") === 0) {
        return false;
    }
    # alle PHP-Dateien
    if(substr($file,-4) == ".php") {
        return false;
    }
    # ...und der Rest
    if(in_array($file, array(
            "Thumbs.db", // Windows-spezifisch
            "__MACOSX", // Mac-spezifisch
            "settings" // Eclipse
            ))) {
        return false;
    }
    return true;
}

# $filetype = "dir" nur ordner
# $filetype = "file" nur dateien
# $filetype = array(".txt",".hid",...) nur die mit dieser ext
#               Achtung Punkt nicht vergessen Gross/Kleinschreibung ist egal
# $filetype = false alle dateien
# $sort_type = "sort" (Default) oder "natcasesort" oder "none"
function getDirAsArray($dir,$filetype = false,$sort_type = "sort") {
    $dateien = array();
    if(is_dir($dir) and false !== ($currentdir = opendir($dir))) {
        while(false !== ($file = readdir($currentdir))) {
            # keine gültige datei gleich zur nächsten datei
            if(!isValidDirOrFile($file))
                continue;
            # nur mit ext
            if(is_array($filetype)) {
                # alle ext im array in kleinschreibung wandeln
                $filetype = array_map('strtolower', $filetype);
                $ext = strtolower(substr($file,strrpos($file,".")));
                if(in_array($ext,$filetype)) {
                    $dateien[] = $file;
                }
				 # nur dir oder file
			// AZI 2017-09-17: Auch Symlinks müssen beachtet werden
            //} elseif(filetype($dir."/".$file) == $filetype) {
			} elseif(filetype($dir."/".$file) == $filetype || filetype($dir."/".$file) == 'link') {
			// /AZI
                $dateien[] = $file;
            # alle
            } elseif(!$filetype) {
                $dateien[] = $file;
            }
        }
        closedir($currentdir);
        if($sort_type == "sort")
            sort($dateien);
        elseif($sort_type == "natcasesort")
            natcasesort($dateien);
    }
    return $dateien;
}

?>