<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/




/*
echo "<pre style=\"position:fixed;background-color:#000;color:#0f0;padding:5px;font-family:monospace;border:2px solid #777;\">";
print_r($_REQUEST);
echo "</pre>";
*/

    require_once("Language.php");
    require_once("Properties.php");
    require_once("SpecialChars.php");
    require_once("Syntax.php");
    require_once("Smileys.php");
    require_once("Mail.php");
    
    // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
    @ini_set("display_errors", 0);

    $language = new Language();
    $mainconfig = new Properties("conf/main.conf");
    $adminconfig = new Properties("admin/conf/basic.conf");
    $specialchars = new SpecialChars();
    $syntax = new Syntax();
    $smileys = new Smileys("smileys");
    $mailfunctions = new Mail(false);

    // Dateiendungen für Inhaltsseiten
    $EXT_PAGE     = ".txt";
    $EXT_HIDDEN = ".hid";
    $EXT_DRAFT     = ".tmp";

    // Config-Parameter auslesen
    $LAYOUT_DIR                 = $specialchars->replaceSpecialChars($mainconfig->get("cmslayout"),true);
    $TEMPLATE_FILE            = "layouts/$LAYOUT_DIR/template.html";
    $CSS_FILE                        = "layouts/$LAYOUT_DIR/css/style.css";
    $FAVICON_FILE                = "layouts/$LAYOUT_DIR/favicon.ico";
    // Template für Kontaktformular
    $contactformconfig = new Properties("formular/formular.conf");

    $WEBSITE_NAME            = $mainconfig->get("websitetitle");
    if ($WEBSITE_NAME == "")
        $WEBSITE_NAME = "Titel der Website";

    $DEFAULT_CATEGORY        = $mainconfig->get("defaultcat");
    if ($DEFAULT_CATEGORY == "")
        $DEFAULT_CATEGORY = "10_Willkommen";

    $DEFAULT_PAGE                = $mainconfig->get("defaultpage");
    if ($DEFAULT_PAGE == "")
        $DEFAULT_PAGE = "10_Willkommen";

    $USE_CMS_SYNTAX            = true;
    if ($mainconfig->get("usecmssyntax") == "false")
        $USE_CMS_SYNTAX = false;
        
    // Request-Parameter einlesen und dabei absichern
    $CAT_REQUEST = $specialchars->replaceSpecialChars(getRequestParam('cat', true),false);
    $PAGE_REQUEST = $specialchars->replaceSpecialChars(getRequestParam('page', true),false);
    $ACTION_REQUEST = getRequestParam('action', true);
    $QUERY_REQUEST = getRequestParam('query', true);
    $HIGHLIGHT_REQUEST = getRequestParam('highlight', false);

    $CONTENT_DIR_REL        = "kategorien";
    $CONTENT_DIR_ABS         = getcwd() . "/$CONTENT_DIR_REL";
    $CONTENT_FILES_DIR    = "dateien";
    $GALLERIES_DIR            = "galerien";
    $CONTENT                         = "";
    $HTML                                = "";

    // Überprüfen: Ist die Startkategorie vorhanden? Wenn nicht, nimm einfach die allererste als Standardkategorie
    if (!file_exists("$CONTENT_DIR_REL/$DEFAULT_CATEGORY")) {
        $contentdir = opendir($CONTENT_DIR_REL);
        while ($cat = readdir($contentdir)) {
            if (isValidDirOrFile($cat)) {
                $DEFAULT_CATEGORY = $cat;
                break;
            }
        }
        closedir($contentdir);
    }

    // Dateiname der aktuellen Inhaltsseite (wird in getContent() gesetzt)
    $PAGE_FILE = "";

    // Zuerst: Übergebene Parameter überprüfen
    checkParameters();
    // Dann: HTML-Template einlesen und mit Inhalt füllen
    readTemplate();
    // Zum Schluß: Ausgabe des fertigen HTML-Dokuments
    echo $HTML;


// ------------------------------------------------------------------------------
// Parameter auf Korrektheit prüfen
// ------------------------------------------------------------------------------
    function checkParameters() {
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $DEFAULT_CATEGORY;
        global $ACTION_REQUEST;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;

        // Überprüfung der gegebenen Parameter
        if (
                // Wenn keine Kategorie übergeben wurde...
                ($CAT_REQUEST == "")
                // ...oder eine nicht existente Kategorie...
                || (!file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST"))
                // ...oder eine Kategorie ohne Contentseiten...
                || (getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST", true, true) == "")
            )
            // ...dann verwende die Standardkategorie
            $CAT_REQUEST = $DEFAULT_CATEGORY;


        // Kategorie-Verzeichnis einlesen
        $pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", true, true);

        // Wenn Contentseite nicht explizit angefordert wurde oder nicht vorhanden ist...
        if (
            ($PAGE_REQUEST == "")
            || (!file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_PAGE") && !file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_HIDDEN") && !file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_DRAFT"))
            ) {
            //...erste Contentseite der Kategorie setzen
            $PAGE_REQUEST = substr($pagesarray[0], 0, strlen($pagesarray[0]) - 4);
        }

        // Wenn ein Action-Parameter übergeben wurde: keine aktiven Kat./Inhaltts. anzeigen
        if (($ACTION_REQUEST == "sitemap") || ($ACTION_REQUEST == "search")) {
            $CAT_REQUEST = "";
            $PAGE_REQUEST = "";
        }
    }


// ------------------------------------------------------------------------------
// HTML-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
    function readTemplate() {
        global $CSS_FILE;
        global $HTML;
        global $FAVICON_FILE;
        global $LAYOUT_DIR;
        global $TEMPLATE_FILE;
        global $USE_CMS_SYNTAX;
        global $WEBSITE_NAME;
        global $ACTION_REQUEST;
        global $HIGHLIGHT_REQUEST;
        global $CAT_REQUEST;
        global $language;
        global $syntax;
        global $mainconfig;
        global $smileys;
        global $specialchars;

    if (!$file = @fopen($specialchars->rebuildSpecialChars($TEMPLATE_FILE, false, false), "r"))
        die($language->getLanguageValue1("message_template_error_1", $TEMPLATE_FILE));
    $template = fread($file, filesize($specialchars->rebuildSpecialChars($TEMPLATE_FILE, false, false)));
    fclose($file);
        // Platzhalter des Templates mit Inhalt füllen
        $pagecontentarray = array();
       // getSiteMap, getSearchResult und getContent liefern jeweils ein Array:
       // [0] = Inhalt
       // [1] = Name der Kategorie (leer bei getSiteMap, getSearchResult)
       // [2] = Name des Inhalts
    $pagecontent = "";
       $cattitle = "";
       $pagetitle = "";
     
    if ($ACTION_REQUEST == "sitemap") {
        $pagecontentarray = getSiteMap();
        $pagecontent    = $pagecontentarray[0];
        $cattitle         = $pagecontentarray[1];
        $pagetitle         = $pagecontentarray[2];
    }
    elseif ($ACTION_REQUEST == "search") {
        $pagecontentarray = getSearchResult();
        $pagecontent    = $pagecontentarray[0];
        $cattitle         = $pagecontentarray[1];
        $pagetitle         = $pagecontentarray[2];
    }
    elseif ($USE_CMS_SYNTAX) {
        $pagecontentarray = getContent();
        $pagecontent    = $syntax->convertContent($pagecontentarray[0], $CAT_REQUEST, true);
        $cattitle         = $pagecontentarray[1];
          $pagetitle         = $pagecontentarray[2];
      }
    else {
        $pagecontentarray = getContent();
        $pagecontent    = $pagecontentarray[0];
        $cattitle         = $pagecontentarray[1];
          $pagetitle         = $pagecontentarray[2];
      }
      
      // Smileys ersetzen
         if ($mainconfig->get("replaceemoticons") == "true")
          $pagecontent = $smileys->replaceEmoticons($pagecontent);

        // Gesuchte Phrasen hervorheben
        if ($HIGHLIGHT_REQUEST <> "") {
            $pagecontent = highlight($pagecontent, $HIGHLIGHT_REQUEST);
        }

    $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $template);
    $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    $HTML = preg_replace('/{LAYOUT_DIR}/', $LAYOUT_DIR, $HTML);

    // Platzhalter ersetzen
    $HTML = replacePlaceholders($HTML, $cattitle, $pagetitle);
    $HTML = preg_replace('/{WEBSITE_TITLE}/', getWebsiteTitle($WEBSITE_NAME, $cattitle, $pagetitle), $HTML);

    // Meta-Tag "keywords"
       $HTML = preg_replace('/{WEBSITE_KEYWORDS}/', $mainconfig->get("websitekeywords"), $HTML);
    // Meta-Tag "description"
       $HTML = preg_replace('/{WEBSITE_DESCRIPTION}/', $mainconfig->get("websitedescription"), $HTML);

        $HTML = preg_replace('/{CONTENT}/', $pagecontent, $HTML);
    $HTML = preg_replace('/{MAINMENU}/', getMainMenu(), $HTML);

        // Detailmenü nicht zeigen, wenn Submenüs aktiviert sind
        if ($mainconfig->get("usesubmenu") > 0) {
            $HTML = preg_replace('/{DETAILMENU}/', "", $HTML);
        }
        else {
        $HTML = preg_replace('/{DETAILMENU}/', getDetailMenu($CAT_REQUEST), $HTML);
    }
    $HTML = preg_replace('/{SEARCH}/', getSearchForm(), $HTML);
    $HTML = preg_replace('/{LASTCHANGE}/', getLastChangedContentPage(), $HTML);
    $HTML = preg_replace('/{SITEMAPLINK}/', "<a href=\"index.php?action=sitemap\" id=\"sitemaplink\"".getTitleAttribute($language->getLanguageValue0("tooltip_showsitemap_0")).">".$language->getLanguageValue0("message_sitemap_0")."</a>", $HTML);
    $HTML = preg_replace('/{CMSINFO}/', getCmsInfo(), $HTML);
      
         // Kontaktformular
    $HTML = preg_replace('/{CONTACT}/', buildContactForm(), $HTML);
    }


// ------------------------------------------------------------------------------
// Zu einem Kategorienamen passendes Kategorieverzeichnis suchen und zurückgeben
// Alle Kühe => 00_Alle-nbsp-K-uuml-he
// ------------------------------------------------------------------------------
    function nameToCategory($catname) {
        global $CONTENT_DIR_ABS;
        // Content-Verzeichnis einlesen
        $dircontent = getDirContentAsArray("$CONTENT_DIR_ABS", false, false);
        // alle vorhandenen Kategorien durchgehen...
        foreach ($dircontent as $currentelement) {
            // ...und wenn eine auf den Namen paßt...
            if (substr($currentelement, 3, strlen($currentelement)-3) == $catname) {
                // ...den vollen Kategorienamen zurückgeben
                return $currentelement;
            }
        }
        // Wenn kein Verzeichnis paßt: Leerstring zurückgeben
        return "";
    }


// ------------------------------------------------------------------------------
// Zu einer Inhaltsseite passende Datei suchen und zurückgeben
// Müllers Kuh => 00_M-uuml-llers-nbsp-Kuh.txt
// ------------------------------------------------------------------------------
    function nameToPage($pagename, $currentcat) {
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;

        // Kategorie-Verzeichnis einlesen
        $dircontent = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcat", true, true);
        // alle vorhandenen Inhaltsdateien durchgehen...
        foreach ($dircontent as $currentelement) {
            // ...und wenn eine auf den Namen paßt...
            if (
                (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($EXT_PAGE)) == $pagename)
                || (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($EXT_HIDDEN)) == $pagename)
                || (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($EXT_DRAFT)) == $pagename)
                ) {
                // ...den vollen Seitennamen zurückgeben
                return $currentelement;
            }
        }
        // Wenn keine Datei paßt: Leerstring zurückgeben
        return "";
    }


// ------------------------------------------------------------------------------
// Kategorienamen aus komplettem Verzeichnisnamen einer Kategorie zurückgeben
// 00_Alle-nbsp-K-uuml-he => Alle Kühe
// ------------------------------------------------------------------------------
    function catToName($cat, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Seitennamen aus komplettem Dateinamen einer Inhaltsseite zurückgeben
// 00_M-uuml-llers-nbsp-Kuh.txt => Müllers Kuh
// ------------------------------------------------------------------------------
    function pageToName($page, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($page, 3, strlen($page) - 7), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rückgabe als String
// ------------------------------------------------------------------------------
    function getContent() {
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $EXT_DRAFT;
        global $PAGE_FILE;
        global $ACTION_REQUEST;
        global $specialchars;

        // Entwurf
        if (
                ($ACTION_REQUEST == "draft") &&
                (file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_DRAFT"))
            ) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_HIDDEN;
            return array (
                                        implode("", file("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_DRAFT")),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_DRAFT, true)
                                        );
        }
        // normale Inhaltsseite
        elseif (file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_PAGE")) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_PAGE;
            return array (
                                        implode("", file("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_PAGE")),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_PAGE, true)
                                        );
        }
        // Versteckte Inhaltsseite
        elseif (file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_HIDDEN")) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_HIDDEN;
            return array (
                                        implode("", file("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$EXT_HIDDEN")),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_HIDDEN, true)
                                        );
        }
        else
            return "";
    }



// ------------------------------------------------------------------------------
// Auslesen des Content-Verzeichnisses unter Berücksichtigung
// des auszuschließenden File-Verzeichnisses, Rückgabe als Array
// ------------------------------------------------------------------------------
    function getDirContentAsArray($dir, $iscatdir, $showhidden) {
        global $CONTENT_FILES_DIR;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;

        $currentdir = opendir($dir);
        $i=0;
        $files = "";
        // Einlesen des gesamten Content-Verzeichnisses außer dem
        // auszuschließenden Verzeichnis und den Elementen . und ..
        while ($file = readdir($currentdir)) {
            if (
                    // wenn Kategorieverzeichnis: Alle Dateien auslesen, die auf $EXT_PAGE oder $EXT_HIDDEN enden...
                    (
                        (!$iscatdir)
                        || (substr($file, strlen($file)-4, strlen($file)) == $EXT_PAGE)
                        || ($showhidden && (substr($file, strlen($file)-4, strlen($file)) == $EXT_HIDDEN))
                    )
                    // ...und nicht $CONTENT_FILES_DIR
                    && (($file <> $CONTENT_FILES_DIR) || (!$iscatdir))
                    // nicht "." und ".."
                    && isValidDirOrFile($file)
                    ) {
            $files[$i] = $file;
            $i++;
            }
        }
        closedir($currentdir);
        // Rückgabe des sortierten Arrays
        if ($files <> "")
            sort($files);
        return $files;
    }


// ------------------------------------------------------------------------------
// Aufbau des Hauptmenüs, Rückgabe als String
// ------------------------------------------------------------------------------
    function getMainMenu() {
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $specialchars;
        global $mainconfig;

        $mainmenu = "<ul class=\"mainmenu\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false, false);
        // numerische Accesskeys für angezeigte Menüpunkte
        $currentaccesskey = 0;
        // Jedes Element des Arrays ans Menü anhängen
        foreach ($categoriesarray as $currentcategory) {
            // Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
            if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true, false) == "") {
                $mainmenu .= "";
            }
            // Aktuelle Kategorie als aktiven Menüpunkt anzeigen...
            elseif ($currentcategory == $CAT_REQUEST) {
                $currentaccesskey++;
                //$mainmenu .= "<li class=\"mainmenu\"><a href=\"index.php?cat=$currentcategory\" class=\"menuactive\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a></li>";
                $mainmenu .= "<li class=\"mainmenu\"><a href=\"index.php?cat=".$currentcategory."\" class=\"menuactive\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a>";
                if ($mainconfig->get("usesubmenu") > 0) {
                    // $mainmenu .= "<li class=\"mainmenu_submenu\">".getDetailMenu($currentcategory)."</li>";
                    $mainmenu .= getDetailMenu($currentcategory);
                }
                $mainmenu .= "</li>";
            }
            // ...alle anderen als normalen Menüpunkt.
            else {
                $currentaccesskey++;
                //$mainmenu .= "<li class=\"mainmenu\"><a href=\"index.php?cat=$currentcategory\" class=\"menu\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a></li>";
                $mainmenu .= "<li class=\"mainmenu\"><a href=\"index.php?cat=".$currentcategory."\" class=\"menu\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a>";
                if ($mainconfig->get("usesubmenu") == 2) {
                    //$mainmenu .= "<li class=\"mainmenu_submenu\">".getDetailMenu($currentcategory)."</li>";
                    $mainmenu .= getDetailMenu($currentcategory);
                }
                $mainmenu .= "</li>";
            }
        }
        // Rückgabe des Menüs
        return $mainmenu . "</ul>";
    }


// ------------------------------------------------------------------------------
// Aufbau des Detailmenüs, Rückgabe als String
// ------------------------------------------------------------------------------
    function getDetailMenu($cat){
        global $ACTION_REQUEST;
        global $QUERY_REQUEST;
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_DRAFT;
        global $language;
        global $specialchars;
        global $mainconfig;

        if ($mainconfig->get("usesubmenu") > 0)
            $cssprefix = "submenu";
        else
            $cssprefix = "detailmenu";

        $detailmenu = "<ul class=\"detailmenu\">";
        // Sitemap
        if (($ACTION_REQUEST == "sitemap") && ($mainconfig->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?action=sitemap\" class=\"".$cssprefix."active\">".$language->getLanguageValue0("message_sitemap_0")."</a></li>";
        // Suchergebnis
        elseif (($ACTION_REQUEST == "search") && ($mainconfig->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?action=search&amp;query=".$QUERY_REQUEST."\" class=\"".$cssprefix."active\">".$language->getLanguageValue1("message_searchresult_1", html_entity_decode($QUERY_REQUEST,ENT_COMPAT,'ISO-8859-1'))."</a></li>";
        // Entwurfsansicht
        elseif (($ACTION_REQUEST == "draft") && ($mainconfig->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?cat=$cat&amp;page=$PAGE_REQUEST&amp;action=draft\" class=\"".$cssprefix."active\">".pageToName($PAGE_REQUEST.$EXT_DRAFT, false)." (".$language->getLanguageValue0("message_draft_0").")</a></li>";
        // "ganz normales" Detailmenü einer Kategorie
        else {
            // Content-Verzeichnis der aktuellen Kategorie einlesen
            $contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$cat", true, false);

            // Kategorie, die nur versteckte Seiten enthält: kein Detailmenü zeigen
            if ($contentarray == "") {
                return "";
            }

            // alphanumerische Accesskeys (über numerischen ASCII-Code) für angezeigte Menüpunkte
            $currentaccesskey = 0;
            // Jedes Element des Arrays ans Menü anhängen
            foreach ($contentarray as $currentcontent) {
                $currentaccesskey++;
                // Inhaltsseite nicht anzeigen, wenn sie genauso heißt wie die Kategorie
                if ($mainconfig->get("hidecatnamedpages") == "true") {
                    if(substr($currentcontent, 3, strlen($currentcontent) - 7) == substr($CAT_REQUEST, 3, strlen($CAT_REQUEST) - 3)) {
                        // Wenn es in der Kategorie nur diese eine (dank hidecatnamedpages eh nicht angezeigte) Seite gibt,
                        // dann gib als Detailmenü gleich einen Leerstring zurück
                        if (count($contentarray) == 1) {
                            return "";
                        } 
                        // ...ansonsten auf zur nächsten Inhaltsseite!
                        else {
                            continue;
                        }
                    }
                }
                // Aktuelle Inhaltsseite als aktiven Menüpunkt anzeigen...
                if (substr($currentcontent, 0, strlen($currentcontent) - 4) == $PAGE_REQUEST) {
                    $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?cat=".$cat."&amp;page=".
                                                    substr($currentcontent, 0, strlen($currentcontent) - 4).
                                                    "\" class=\"".$cssprefix."active\" accesskey=\"".chr($currentaccesskey+96)."\">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
                }
                // ...alle anderen als normalen Menüpunkt.
                else {
                    $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?cat=".$cat."&amp;page=".
                                                    substr($currentcontent, 0, strlen($currentcontent) - 4).
                                                    "\" class=\"".$cssprefix."\" accesskey=\"".chr($currentaccesskey+96)."\">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
                }
            }
        }
        // Rückgabe des Menüs
        return $detailmenu . "</ul>";
    }


// ------------------------------------------------------------------------------
// Rückgabe des Suchfeldes
// ------------------------------------------------------------------------------
    function getSearchForm(){
        global $language;
        global $mainconfig;
        global $specialchars;

        $form = "<form accept-charset=\"ISO-8859-1\" method=\"get\" action=\"index.php\" name=\"search\" class=\"searchform\">"
        ."<input type=\"hidden\" name=\"action\" value=\"search\" />"
        ."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" accesskey=\"s\" />"
        ."<input type=\"image\" name=\"action\" value=\"search\" src=\"layouts/".$specialchars->replaceSpecialChars($mainconfig->get("cmslayout"), true)."/grafiken/searchicon.gif\" alt=\"".$language->getLanguageValue0("message_search_0")."\" class=\"searchbutton\"".getTitleAttribute($language->getLanguageValue0("message_search_0"))." />"
        ."</form>";
        return $form;
    }


// ------------------------------------------------------------------------------
// Einlesen des Inhalts-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
    function getLastChangedContentPage(){
        global $CONTENT_DIR_REL;
        global $language;
        global $specialchars;

        $latestchanged = array("cat" => "catname", "file" => "filename", "time" => 0);
        $currentdir = opendir($CONTENT_DIR_REL);
        while ($file = readdir($currentdir)) {
            if (isValidDirOrFile($file)) {
                $latestofdir = getLastChangeOfCat($CONTENT_DIR_REL."/".$file);
                if ($latestofdir['time'] > $latestchanged['time']) {
                    $latestchanged['cat'] = $file;
                    $latestchanged['file'] = $latestofdir['file'];
                    $latestchanged['time'] = $latestofdir['time'];
                }
        }
        }
        closedir($currentdir);
        return $language->getLanguageValue0("message_lastchange_0")." <a href=\"index.php?cat=".$latestchanged['cat']."&amp;page=".substr($latestchanged['file'], 0, strlen($latestchanged['file'])-4)."\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true), $specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true, true)))." id=\"lastchangelink\">".$specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true)."</a> (".strftime($language->getLanguageValue0("_dateformat_0"), date($latestchanged['time'])).")";
    }



// ------------------------------------------------------------------------------
// Einlesen eines Kategorie-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
    function getLastChangeOfCat($dir){
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $mainconfig;

        $showhiddenpages = ($mainconfig->get("showhiddenpagesinlastchanged") == "true");
        
        $latestchanged = array("file" => "filename", "time" => 0);
        $currentdir = opendir($dir);
        while ($file = readdir($currentdir)) {
            if (is_file($dir."/".$file)) {
                // normale Inhaltsseiten
                if ( 
                    (substr($file, strlen($file)-4, 4) == $EXT_PAGE)
                    // oder, wenn konfiguriert, auch versteckte
                    || ($showhiddenpages && substr($file, strlen($file)-4, 4) == $EXT_HIDDEN)
                    )
                    {
                    if (filemtime($dir."/".$file) > $latestchanged['time']) {
                        $latestchanged['file'] = $file;
                        $latestchanged['time'] = filemtime($dir."/".$file);
                    }
                }
        }
        }
        closedir($currentdir);
        return $latestchanged;
    }



// ------------------------------------------------------------------------------
// Erzeugung einer Sitemap
// ------------------------------------------------------------------------------
    function getSiteMap() {
        global $CONTENT_DIR_ABS;
        global $CONTENT_FILES_DIR;
        global $language;
        global $specialchars;
        global $mainconfig;
        
        $showhiddenpages = ($mainconfig->get("showhiddenpagesinsitemap") == "true");
        
        $sitemap = "<h1>".$language->getLanguageValue0("message_sitemap_0")."</h1>"
        ."<div class=\"sitemap\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false, false);
        // Jedes Element des Arrays an die Sitemap anhängen
        foreach ($categoriesarray as $currentcategory) {
            // Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
            $contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true, $showhiddenpages);
            if ($contentarray == "")
                continue;

            $sitemap .= "<h2>".catToName($currentcategory, false)."</h2><ul>";
            // Alle Inhaltsseiten der aktuellen Kategorie auflisten...
            // Jedes Element des Arrays an die Sitemap anhängen
            foreach ($contentarray as $currentcontent) {
                $sitemap .= "<li><a href=\"index.php?cat=$currentcategory&amp;page=".
                                                    substr($currentcontent, 0, strlen($currentcontent) - 4).
                                                    "\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($currentcategory, false))).">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
            }
            $sitemap .= "</ul>";
        }
        $sitemap .= "</div>";
        // Rückgabe der Sitemap
        return array($sitemap, $language->getLanguageValue0("message_sitemap_0"), $language->getLanguageValue0("message_sitemap_0"));
    }


// ------------------------------------------------------------------------------
// Anzeige der Suchergebnisse
// ------------------------------------------------------------------------------
    function getSearchResult() {
        global $CONTENT_DIR_ABS;
        global $CONTENT_DIR_REL;
        global $CONTENT_FILES_DIR;
        global $USE_CMS_SYNTAX;
        global $QUERY_REQUEST;
        global $language;
        global $specialchars;
        global $mainconfig;
        
        $showhiddenpages = ($mainconfig->get("showhiddenpagesinsearch") == "true");
        $matchesoverall = 0;
        $searchresults = "";

        // Überhaupt erst etwas machen, wenn die Suche nicht leer ist
        if (trim($QUERY_REQUEST) != "") {
            // Damit die Links in der Ergbnisliste korrekt sind: Suchanfrage bereinigen
            $queryarray = explode(" ", preg_replace('/"/', "", $QUERY_REQUEST));
            $searchresults .= "<h1>".$language->getLanguageValue1("message_searchresult_1", (trim($specialchars->rebuildSpecialChars($QUERY_REQUEST,true,true))))."</h1>"
            ."<div class=\"sitemap\">";

            // Kategorien-Verzeichnis einlesen
            $categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false, false);

            // Alle Kategorien durchsuchen
            foreach ($categoriesarray as $currentcategory) {

                // Wenn die Kategorie keine Contentseiten hat, direkt zur nächsten springen
                $contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true, $showhiddenpages);
                if ($contentarray == "")
                    continue;

                $matchingpages = array();
                $i = 0;

                // Alle Inhaltsseiten durchsuchen
                foreach ($contentarray as $currentcontent) {
                    // Jedes Suchwort
                    foreach($queryarray as $query) {
                        if ($query == "")
                            continue;
                        // Treffer in der aktuellen Seite?
                        if (pageContainsWord($currentcategory, $currentcontent, $query, true)) {
                            // wenn noch nicht im Treffer-Array: hinzufügen
                            if (!in_array($currentcontent, $matchingpages))
                                $matchingpages[$i] = $currentcontent;
                            $i++;
                        }
                    }
                }

                // die gesammelten Seiten ausgeben
                if (count($matchingpages) > 0) {
                    $highlightparameter = implode(",", $queryarray);
                    $categoryname = catToName($currentcategory, false);
                    $searchresults .= "<h2>$categoryname</h2><ul>";
                    foreach ($matchingpages as $matchingpage) {
                        $pagename = pageToName($matchingpage, false);
                        $filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$matchingpage;
                        $searchresults .= "<li>".
                            "<a href=\"index.php?cat=$currentcategory&amp;page=".
                            substr($matchingpage, 0, strlen($matchingpage) - 4).
                            "&amp;highlight=".rawurlencode($highlightparameter)."\"".
                            getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $pagename, $categoryname)).">".
                            highlight($pagename,$highlightparameter).
                            "</a>".
                            "</li>";
                    }
                    $searchresults .= "</ul>";
                    $matchesoverall += count($matchingpages);
                }
            }
            $searchresults .= "</div>";
        }
        // Keine Inhalte gefunden?
        if ($matchesoverall == 0)
            $searchresults .= $language->getLanguageValue0("message_nodatafound_0", trim($QUERY_REQUEST));
        // Rückgabe des Menüs
        return array($searchresults, $language->getLanguageValue0("message_search_0"), $language->getLanguageValue1("message_searchresult_1", (trim($QUERY_REQUEST))));
    }


// ------------------------------------------------------------------------------
// Inhaltsseite durchsuchen
// ------------------------------------------------------------------------------
    function pageContainsWord($cat, $page, $query, $firstrecursion) {
        global $CONTENT_DIR_REL;
        global $specialchars;
        
        $filepath = $CONTENT_DIR_REL."/".$cat."/".$page;
        $ismatch = false;
        $content = "";
        
        // Dateiinhalt auslesen, wenn vorhanden...
        if (filesize($filepath) > 0) {
            $handle = fopen($filepath, "r");
            $content = fread($handle, filesize($filepath));
            fclose($handle);
            // Zuerst: includierte Seiten herausfinden!
            preg_match_all("/\[include\|([^\[\]]*)\]/Um", $content, $matches);
            $i = 0;
            // Für jeden Treffer...
            foreach ($matches[1] as $match) {
                // ...Auswertung und Verarbeitung der Informationen
                $valuearray = explode(":", $matches[1][$i]);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $includedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($matches[1][$i],ENT_COMPAT,'ISO-8859-1'),false), $cat);
                    // verhindern, daß in der includierten Seite includierte Seiten auch noch durchsucht werden
                    if ($firstrecursion) {
                        // includierte Seite durchsuchen!
                        if (pageContainsWord($cat, $includedpage, $query,false)) {
                            return true;
                        }
                    }
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $includedpagescat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1'),false));
                    $includedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1],ENT_COMPAT,'ISO-8859-1'),false), $includedpagescat);
                    // verhindern, daß in der includierten Seite includierte Seiten auch noch durchsucht werden
                    if ($firstrecursion) {
                        // includierte Seite durchsuchen!
                        if (pageContainsWord($includedpagescat, $includedpage, $query, false)) {
                            return true;
                        }
                    }
                }
                $i++;
            }

            // ...und alle Syntax-Tags entfernen. Gesucht werden soll nur im reinen Text
            $content = preg_replace("/\[[^\[\]]+\|([^\[\]]*)\]/U", "$1", $content);
            // Auch Emoticons in Doppelpunkten (z.B. ":lach:") sollen nicht berücksichtigt werden
            $content = preg_replace("/:[^\s]+:/U", "", $content);
            // Zum Schluß noch die horizontalen Linien ("[----]") von der Suche ausschließen
            $content = preg_replace("/\[----\]/U", "", $content);
        }
        if ($query == "")
            continue;
        // Wenn...
        if (
            // ...der aktuelle Suchbegriff im Seitennamen...
            (substr_count(strtolower(pageToName($page, false)), strtolower($query)) > 0)
            // ...oder im eigentlichen Seiteninhalt vorkommt (überprüft werden nur Seiten, die nicht leer sind), ...
            || ((filesize($filepath) > 0) && (substr_count(strtolower($content), strtolower(html_entity_decode($query,ENT_COMPAT,'ISO-8859-1'))) > 0))
            ) {
            // ...dann setze das Treffer-Flag
            $ismatch = true;
        }

/*        echo "pageContainsWord($cat, $page, $query, $firstrecursion)";
        if ($ismatch)
            echo "<b> -> TREFFER!</b>";
        echo "<br>";
*/        
        // Ergebnis zurückgeben
        return $ismatch;
    }

// ------------------------------------------------------------------------------
// E-Mail-Adressen verschleiern
// ------------------------------------------------------------------------------
// Dank für spam-me-not.php an Rolf Offermanns!
// Spam-me-not in JavaScript: http://www.zapyon.de
    function obfuscateAdress($originalString, $mode) {
        // $mode == 1            dezimales ASCII
        // $mode == 2            hexadezimales ASCII
        // $mode == 3            zufällig gemischt
        $encodedString = "";
        $nowCodeString = "";
        $randomNumber = -1;

        $originalLength = strlen($originalString);
        $encodeMode = $mode;

        for ( $i = 0; $i < $originalLength; $i++) {
            if ($mode == 3) $encodeMode = rand(1,2);
            switch ($encodeMode) {
                case 1: // Decimal code
                    $nowCodeString = "&#" . ord($originalString[$i]) . ";";
                    break;
                case 2: // Hexadecimal code
                    $nowCodeString = "&#x" . dechex(ord($originalString[$i])) . ";";
                    break;
                default:
                    return "ERROR: wrong encoding mode.";
            }
            $encodedString .= $nowCodeString;
        }
        return $encodedString;
    }



// ------------------------------------------------------------------------------
// Phrasen in Inhalt hervorheben
// ------------------------------------------------------------------------------
    function highlight($content, $phrasestring) {
        // Zu highlightende Begriffe kommen kommasepariert ("begriff1,begriff2")-> in Array wandeln
        $phrasestring = rawurldecode($phrasestring);
        $phrasearray = explode(",", $phrasestring);
        // jeden Begriff highlighten
        foreach($phrasearray as $phrase) {
            // Regex-Zeichen im zu highlightenden Text escapen (.\+*?[^]$(){}=!<>|:)
            $phrase = preg_quote($phrase);
            // Slashes im zu highlightenden Text escapen
            $phrase = preg_replace("/\//", "\\\\/", $phrase);
            $phrase = htmlentities($phrase);
            //$content = preg_replace("/((<[^>]*|{CONTACT})|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $content);
            $content = preg_replace("/((<[^>]*|{CONTACT})|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $content); 
        }
        //
        return $content;
    }



// ------------------------------------------------------------------------------
// Rückgabe des Website-Titels
// ------------------------------------------------------------------------------
    function getWebsiteTitle($websitetitle, $cattitle, $pagetitle) {
        global $mainconfig;

        $title = $mainconfig->get("titlebarformat");
        $sep = $mainconfig->get("titlebarseparator");

    $title = preg_replace('/{WEBSITE}/', $websitetitle, $title);
        if ($cattitle == "")
            $title = preg_replace('/{CATEGORY}/', "", $title);
        else
            $title = preg_replace('/{CATEGORY}/', $cattitle, $title);
    $title = preg_replace('/{PAGE}/', $pagetitle, $title);
    $title = preg_replace('/{SEP}/', $sep, $title);
    return $title;
    }



// ------------------------------------------------------------------------------
// Überprüfung auf
// ------------------------------------------------------------------------------
    function hasValidContentExtension($filename) {
    }



// ------------------------------------------------------------------------------
// Anzeige der Informationen zum System
// ------------------------------------------------------------------------------
    function getCmsInfo() {
        global $mainconfig;
        global $language;
        return "<a href=\"http://cms.mozilo.de/\" target=\"_blank\" id=\"cmsinfolink\"".getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1", "http://cms.mozilo.de")).">moziloCMS ".$mainconfig->get("cmsversion")."</a>";
    }


// ------------------------------------------------------------------------------
// Platzhalter im übergebenen String ersetzen
// ------------------------------------------------------------------------------
    function replacePlaceholders($content, $cattitle, $pagetitle) {
        global $mainconfig;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $PAGE_FILE;
        global $EXT_PAGE;

        // Titel der Website
        $content = preg_replace('/{WEBSITE_NAME}/', $mainconfig->get("websitetitle"), $content);

        if ($CAT_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M-uuml-llers-nbsp-Kuh")
            $content = preg_replace('/{CATEGORY}/', $CAT_REQUEST, $content);
            // "sauberer" Name der aktuellen Kategorie ("Müllers Kuh")
            $content = preg_replace('/{CATEGORY_NAME}/', catToName($CAT_REQUEST, true), $content);
        }
        // Suche, Sitemap
        else {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M-uuml-llers-nbsp-Kuh")
            $content = preg_replace('/{CATEGORY}/', $cattitle, $content);
            // "sauberer" Name der aktuellen Kategorie ("Müllers Kuh")
            $content = preg_replace('/{CATEGORY_NAME}/', $cattitle, $content);
        }

        if ($PAGE_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh")
            $content = preg_replace('/{PAGE}/', $PAGE_REQUEST, $content);
            // Dateiname der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh.txt")
            $content = preg_replace('/{PAGE_FILE}/', $PAGE_FILE, $content);
            // "sauberer" Name der aktuellen Inhaltsseite ("Müllers Kuh")
            $content = preg_replace('/{PAGE_NAME}/', pageToName($PAGE_FILE, true), $content);
        }
        // Suche, Sitemap
        else {
            // "unbehandelter" Name der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh")
            $content = preg_replace('/{PAGE}/', $pagetitle, $content);
            // Dateiname der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh.txt")
            $content = preg_replace('/{PAGE_FILE}/', $pagetitle, $content);
            // "sauberer" Name der aktuellen Inhaltsseite ("Müllers Kuh")
            $content = preg_replace('/{PAGE_NAME}/', $pagetitle, $content);
        }
        // ...und zurückgeben
        return $content;
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
                ".svn",    // SVN
                ".cache", // Eclipse
                "settings" // Eclipse 
                )));
    }

// ------------------------------------------------------------------------------
// Gibt das Kontaktformular zurück
// ------------------------------------------------------------------------------
    function buildContactForm() {
        global $contactformconfig;
        global $language;
        global $mailfunctions;
        global $WEBSITE_NAME;
        global $adminconfig;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        
        // Ist Mailversand überhaupt aktiviert? Wenn nicht: Das Kontaktformular gar nicht anzeigen!
        if ($adminconfig->get("sendadminmail") != "true") {
            return "<span class=\"deadlink\"".getTitleAttribute($language->getLanguageValue0("tooltip_no_mail_error_0")).">{CONTACT}</span>";
        }

        $config_name = explode(",", ($contactformconfig->get("name")));
        $config_mail = explode(",", ($contactformconfig->get("mail")));
        $config_website = explode(",", ($contactformconfig->get("website")));
        $config_message = explode(",", ($contactformconfig->get("message")));
        
        $errormessage = "";
        $form = "";
        
        $name = getRequestParam('name', false);
        $mail = getRequestParam('mail', false);
        $website = getRequestParam('website', false);
        $message = getRequestParam('message', false);

        // Das Formular wurde abgesendet
        if (getRequestParam('submit', false) <> "") { 

            // Bot-Schutz: Wurde das Formular innerhalb von 5 Sekunden abgeschickt?
            if (time() - getRequestParam('loadtime', false) < 5) {
                die ("Netter Versuch.");
            }
            
            // Eines der Pflichtfelder leer?
            if (($config_name[1] == "true") && ($name == "")) {
                $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_name_0");
            }
            else if (($config_mail[1] == "true") && ($mail == "")) {
                $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_mail_0");
            }
            else if (($config_website[1] == "true") && ($website == "")) {
                $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_website_0");
            }
            else if (($config_message[1] == "true") && ($message == "")) {
                $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_message_0");
            }
            // Es ist ein Fehler aufgetreten!
            if ($errormessage <> "") {
                $form .= "<span id=\"contact_errormessage\">".$errormessage."</span>";
            }
            else {
                $mailcontent = "";
                if ($config_name[0] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_name_0").":\t".$name."\r\n";
                }
                if ($config_mail[0] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_mail_0").":\t".$mail."\r\n";
                }
                if ($config_website[0] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_website_0").":\t".$website."\r\n";
                }
                if ($config_message[0] == "true") {
                    $mailcontent .= "\r\n".$language->getLanguageValue0("contactform_message_0").":\r\n".$message."\r\n";
                }
                $mailsubject = $language->getLanguageValue1("contactform_mailsubject_1", html_entity_decode($WEBSITE_NAME,ENT_COMPAT,'ISO-8859-1'));
                // Wenn Mail-Adresse gesetzt ist: Als Absender für die Mail nutzen
                if ($mail <> "") {
                    $mailfunctions->sendMailToAdminWithFrom($mailsubject, $mailcontent, $mail);
                }
                // ansonsten Mail ohne festgelegten Absender losschicken
                else {
                    $mailfunctions->sendMailToAdmin($mailsubject, $mailcontent);
                }
                $form .= "<span id=\"contact_successmessage\">".$language->getLanguageValue0("contactform_confirmation_0")."</span>";
                
                // Felder leeren
                $name = "";
                $mail = "";
                $website = "";
                $message = "";
            }
        }

        $form .= "<form accept-charset=\"ISO-8859-1\" method=\"post\" action=\"index.php\" name=\"contact_form\" id=\"contact_form\">"
        ."<input type=\"hidden\" name=\"cat\" value=\"".$CAT_REQUEST."\" />"
        ."<input type=\"hidden\" name=\"page\" value=\"".$PAGE_REQUEST."\" />"
        ."<input type=\"hidden\" name=\"loadtime\" value=\"".time()."\" />"
        ."<table id=\"contact_table\" summary=\"contact form table\">";
        if ($config_name[0] == "true") {
            $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_name_0");
            if ($config_name[1] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_name\" name=\"name\" value=\"".$name."\" /></td></tr>";
        }
        if ($config_mail[0] == "true") {
            $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_mail_0");
            if ($config_mail[1] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_mail\" name=\"mail\" value=\"".$mail."\" /></td></tr>";
        }
        if ($config_website[0] == "true") {
            $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_website_0");
            if ($config_website[1] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_website\" name=\"website\" value=\"".$website."\" /></td></tr>";
        }
        if ($config_message[0] == "true") {
            $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_message_0");
            if ($config_message[1] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><textarea rows=\"10\" cols=\"50\" id=\"contact_message\" name=\"message\">".$message."</textarea></td></tr>";
        }
        $form .= "<tr><td style=\"padding-right:10px;\">&nbsp;</td><td>".$language->getLanguageValue0("contactform_mandatory_fields_0")."</td></tr>"
        ."<tr><td style=\"padding-right:10px;\">&nbsp;</td><td><input type=\"submit\" class=\"submit\" id=\"contact_submit\" name=\"submit\" value=\"".$language->getLanguageValue0("contactform_submit_0")."\" /></td></tr>"
        ."</table>"
        ."</form>";
        
        return $form;
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Sichert einen Input-Wert
// ------------------------------------------------------------------------------
    function cleanInput($input) {
        if (function_exists("mb_convert_encoding")) {
            $input = @mb_convert_encoding($input, "ISO-8859-1");
        }
        return stripslashes($input);    
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Prüft einen Requestparameter
// ------------------------------------------------------------------------------
    function getRequestParam($param, $clean) {
        if (isset($_REQUEST[$param])) {
          // Nullbytes abfangen!
            if (strpos($_REQUEST[$param], "\x00") > 0) {
              die();
          }
            if ($clean) {
                return cleanInput(rawurldecode($_REQUEST[$param]));
            }
            else {
                return rawurldecode($_REQUEST[$param]);
            }
        }
        // Parameter ist nicht im Request vorhanden
        else {
            return "";
        }
    }
    

// ------------------------------------------------------------------------------
// Hilfsfunktion: "title"-Attribut zusammenbauen (oder nicht, wenn nicht konfiguriert)
// ------------------------------------------------------------------------------
    function getTitleAttribute($value) {
        global $mainconfig;
        if ($mainconfig->get("showsyntaxtooltips") == "true") {
            return " title=\"".$value."\"";
        }
        return "";
    }

?>