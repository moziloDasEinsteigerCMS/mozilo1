<?php

/*
*
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

session_start();

/*
echo "<pre style=\"position:fixed;background-color:#000;color:#0f0;padding:5px;font-family:monospace;border:2px solid #777;\">";
print_r($_REQUEST);
echo "</pre>";
*/

    require_once("SpecialChars.php");
    require_once("Properties.php");
    
    // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
#    @ini_set("display_errors", 0);

    $specialchars   = new SpecialChars();
    $CMS_CONF     = new Properties("conf/main.conf");
    $VERSION_CONF  = new Properties("conf/version.conf");
    $GALLERY_CONF  = new Properties("conf/gallery.conf");
    $USER_SYNTAX  = new Properties("conf/syntax.conf");
    require_once("Language.php");
    $language       = new Language();
    require_once("Syntax.php");
    require_once("Smileys.php");
    require_once("Mail.php");
    $syntax         = new Syntax();
    $smileys        = new Smileys("smileys");
    $mailfunctions  = new Mail();

    require_once("Plugin.php");


#$CHARSET = 'ISO-8859-1';
$CHARSET = 'UTF-8';

    $URL_BASE = NULL;
    if($CMS_CONF->get("modrewrite") == "true") {
        $URL_BASE = substr(str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']),0,-(strlen("index.php")));
    }

    // Dateiendungen für Inhaltsseiten
    $EXT_PAGE       = ".txt";
    $EXT_HIDDEN     = ".hid";
    $EXT_DRAFT      = ".tmp";
    $EXT_LINK       = ".lnk";

    // Config-Parameter auslesen
    $LAYOUT_DIR_PHP     = "layouts/".$specialchars->replaceSpecialChars($CMS_CONF->get("cmslayout"),true);
    $TEMPLATE_FILE  = $LAYOUT_DIR_PHP."/template.html";
    if ($GALLERY_CONF->get("target") == "_blank" and isset($_GET["gal"])) {
        $TEMPLATE_FILE  = $LAYOUT_DIR_PHP."/gallerytemplate.html";
    }

    $LAYOUT_DIR     = $URL_BASE.$LAYOUT_DIR_PHP;
    $CSS_FILE       = $LAYOUT_DIR."/css/style.css";
    $FAVICON_FILE   = $LAYOUT_DIR."/favicon.ico";
    // Einstellungen für Kontaktformular
    $contactformconfig  = new Properties("formular/formular.conf");
    $contactformcalcs   = new Properties("formular/aufgaben.conf");


    $WEBSITE_NAME = $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true);
    if ($WEBSITE_NAME == "")
        $WEBSITE_NAME = "Titel der Website";

    $USE_CMS_SYNTAX = true;
    if ($CMS_CONF->get("usecmssyntax") == "false")
        $USE_CMS_SYNTAX = false;
        
    // Request-Parameter einlesen und dabei absichern
    $CAT_REQUEST = $specialchars->replaceSpecialChars(getRequestParam('cat', true),false);
    $PAGE_REQUEST = $specialchars->replaceSpecialChars(getRequestParam('page', true),false);
    $ACTION_REQUEST = getRequestParam('action', true);
    $QUERY_REQUEST = getRequestParam('query', true);
    $HIGHLIGHT_REQUEST = getRequestParam('highlight', false);

    $CONTENT_DIR_REL        = "kategorien";
    $CONTENT_DIR_ABS        = getcwd() . "/$CONTENT_DIR_REL";
    $CONTENT_FILES_DIR      = "dateien";
    $GALLERIES_DIR          = "galerien";
    $PLUGIN_DIR             = "plugins";
    $HTML                   = "";

    $DEFAULT_CATEGORY = $CMS_CONF->get("defaultcat");
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
        global $DEFAULT_CATEGORY;
        global $ACTION_REQUEST;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $CMS_CONF;

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
        $pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", true, $CMS_CONF->get("showhiddenpagesasdefaultpage") == "true");

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
        global $PAGE_REQUEST;
        global $language;
        global $syntax;
        global $CMS_CONF;
        global $smileys;
        global $specialchars;
        global $URL_BASE;
        global $CHARSET;
        global $GALLERY_CONF;

    if (!$file = @fopen($specialchars->rebuildSpecialChars($TEMPLATE_FILE, false, false), "r"))
        die($language->getLanguageValue1("message_template_error_1", $specialchars->rebuildSpecialChars($TEMPLATE_FILE, false, false)));
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
    // Inhalte aus Inhaltsseiten durch Passwort schützen
    else { 
        // zunächst Passwort als gesetzt und nicht eingegeben annehmen
        $passwordok = false;
        if (file_exists("conf/passwords.conf")) {
            $passwords = new Properties("conf/passwords.conf"); // alle Passwörter laden
            if ($passwords->keyExists($CAT_REQUEST.'/'.$PAGE_REQUEST)) { // nach Passwort für diese Seite suchen
                $cattitle    = catToName($CAT_REQUEST, true);
                $pagetitle   = $language->getLanguageValue0("passwordform_title_0");
                if (!isset($_POST) || ($_POST == array())) // sofern kein Passwort eingegeben, nach einem Fragen
                    $pagecontent = getPasswordForm();
                else {
                    if (md5(getRequestParam("password", false)) == $passwords->get($CAT_REQUEST.'/'.$PAGE_REQUEST))
                    // richtiges Passwort eingegeben
                        $passwordok = true;
                    else
                    // falsches Passwort eingegeben - Zugriff verweigern
                        $pagecontent = $language->getLanguageValue0("passwordform_message_passwordwrong_0");
                }
            }
            else
            // diese Seite hat ein Passwort - lasse Zugriff zu
                $passwordok = true;
        }
        else
        // keine Seite hat ein Passwort - lasse Zugriff zu
            $passwordok = true;
        if ($passwordok) {
            if ($USE_CMS_SYNTAX) {
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
        }
    }  
    // Smileys ersetzen
    if ($CMS_CONF->get("replaceemoticons") == "true") {
        $pagecontent = $smileys->replaceEmoticons($pagecontent);
    }

    // Gesuchte Phrasen hervorheben
    if ($HIGHLIGHT_REQUEST <> "") {
        $pagecontent = highlight($pagecontent, $HIGHLIGHT_REQUEST);
    }

    $HTML = $template;
    if(strpos($HTML,'{CSS_FILE}') !== false)
        $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $HTML);
    if(strpos($HTML,'{CHARSET}') !== false)
        $HTML = preg_replace('/{CHARSET}/', $CHARSET, $HTML);
    if(strpos($HTML,'{FAVICON_FILE}') !== false)
        $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    if(strpos($HTML,'{LAYOUT_DIR}') !== false)
        $HTML = preg_replace('/{LAYOUT_DIR}/', $LAYOUT_DIR, $HTML);

    // Platzhalter ersetzen
    $HTML = replacePlaceholders($HTML, $cattitle, $pagetitle);
    if(strpos($HTML,'{WEBSITE_TITLE}') !== false)
        $HTML = preg_replace('/{WEBSITE_TITLE}/', getWebsiteTitle($WEBSITE_NAME, $cattitle, $pagetitle), $HTML);

    // Meta-Tag "keywords"
    if(strpos($HTML,'{WEBSITE_KEYWORDS}') !== false)
        $HTML = preg_replace('/{WEBSITE_KEYWORDS}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitekeywords"),false,true), $HTML);
    // Meta-Tag "description"
    if(strpos($HTML,'{WEBSITE_DESCRIPTION}') !== false)
        $HTML = preg_replace('/{WEBSITE_DESCRIPTION}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitedescription"),false,true), $HTML);

    if(strpos($HTML,'{CONTENT}') !== false)
        $HTML = preg_replace('/{CONTENT}/', $pagecontent, $HTML);
    if(strpos($HTML,'{MAINMENU}') !== false)
        $HTML = preg_replace('/{MAINMENU}/', getMainMenu(), $HTML);

    if(strpos($HTML,'{DETAILMENU}') !== false) {
        // Detailmenü (nicht zeigen, wenn Submenüs aktiviert sind)
        if ($CMS_CONF->get("usesubmenu") > 0) {
            $HTML = preg_replace('/{DETAILMENU}/', "", $HTML);
        }
        else {
            $HTML = preg_replace('/{DETAILMENU}/', getDetailMenu($CAT_REQUEST), $HTML);
        }
    }
    // Suchformular
    if(strpos($HTML,'{SEARCH}') !== false)
        $HTML = preg_replace('/{SEARCH}/', getSearchForm(), $HTML);

    // Letzte Änderung
    $lastchangeinfo = getLastChangedContentPageAndDate();
    // - Name der zuletzt geänderten Inhaltsseite
    // - kompletter Link auf diese Inhaltsseite  
    // - formatiertes Datum der letzten Änderung
    if(strpos($HTML,'{LASTCHANGEDTEXT}') !== false)
        $HTML = preg_replace('/{LASTCHANGEDTEXT}/', $language->getLanguageValue0("message_lastchange_0"), $HTML);
    if(strpos($HTML,'{LASTCHANGEDPAGE}') !== false)
        $HTML = preg_replace('/{LASTCHANGEDPAGE}/', $lastchangeinfo[0], $HTML);
    if(strpos($HTML,'{LASTCHANGEDPAGELINK}') !== false)
        $HTML = preg_replace('/{LASTCHANGEDPAGELINK}/', $lastchangeinfo[1], $HTML);
    if(strpos($HTML,'{LASTCHANGEDATE}') !== false)
        $HTML = preg_replace('/{LASTCHANGEDATE}/', $lastchangeinfo[2], $HTML);
    // Platzhalter {LASTCHANGE} ist obsolet seit 1.12! Wird nur aus Gründen der Abwärtskompatibilität noch ersetzt 
    if(strpos($HTML,'{LASTCHANGE}') !== false)
        $HTML = preg_replace('/{LASTCHANGE}/', $language->getLanguageValue0("message_lastchange_0")." ".$lastchangeinfo[1]." (".$lastchangeinfo[2].")", $HTML); 
    
    // Sitemap-Link
    if(strpos($HTML,'{SITEMAPLINK}') !== false)
        $HTML = preg_replace('/{SITEMAPLINK}/', "<a href=\"index.php?action=sitemap\" id=\"sitemaplink\"".getTitleAttribute($language->getLanguageValue0("tooltip_showsitemap_0")).">".$language->getLanguageValue0("message_sitemap_0")."</a>", $HTML);
    
    // CMS-Info-Link
    if(strpos($HTML,'{CMSINFO}') !== false)
        $HTML = preg_replace('/{CMSINFO}/', getCmsInfo(), $HTML);
      
    // Kontaktformular
    if(strpos($HTML,'{CONTACT}') !== false)
        $HTML = preg_replace('/{CONTACT}/', buildContactForm(), $HTML);

    // Kontaktformular
    if(strpos($HTML,'{TABLEOFCONTENTS}') !== false)
        $HTML = preg_replace('/{TABLEOFCONTENTS}/', $syntax->getToC($pagecontent), $HTML);

    if ($GALLERY_CONF->get("target") == "_blank" and isset($_GET["gal"])) {
        require_once("gallery.php");
        if (isset($_GET["gal"])) {
            $gal_request = $specialchars->replaceSpecialChars(html_entity_decode($_GET["gal"], ENT_COMPAT, $CHARSET),false);
            $HTML = $gallery->renderGallery($HTML,$gal_request);
        }
    }

    // Benutzer-Variablen ersetzen
    $HTML = replacePluginVariables($HTML);
    
    }
    
// ------------------------------------------------------------------------------
// Formular zur Passworteingabe anzeigen
// ------------------------------------------------------------------------------
    function getPasswordForm() {
        global $language;
        // TODO: sollte auch wahlweise über ein Template gehen
        return '<form action="index.php?'.$_SERVER['QUERY_STRING'].'" method="post">
        '.$language->getLanguageValue0("passwordform_pagepasswordplease_0").' 
        <input type="password" name="password" />
        <input type="submit" value="'.$language->getLanguageValue0("passwordform_send_0").'" />
        </form>';
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
            return array("","","");
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
        global $EXT_LINK;

#echo "$dir<br>\n";
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
                        || (substr($file, strlen($file)-4, strlen($file)) == $EXT_LINK)
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
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $specialchars;
        global $CMS_CONF;
        global $language;
        global $syntax;
        global $URL_BASE;
        global $EXT_LINK;

        $mainmenu = "<ul class=\"mainmenu\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false, false);
        // Jedes Element des Arrays ans Menü anhängen
        foreach ($categoriesarray as $currentcategory) {
            # Mod Rewrite
            $url = "index.php?cat=".$currentcategory;
            if($CMS_CONF->get("modrewrite") == "true") {
                $url = $URL_BASE.$currentcategory.".html";
            }
            if(substr($currentcategory,-(strlen($EXT_LINK))) == $EXT_LINK) {
               $mainmenu .= '<li class="mainmenu">'.menuLink($currentcategory,"menu")."</li>";
            }
            // Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
            elseif (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true, false) == "") {
                $mainmenu .= "";
            }
            // Aktuelle Kategorie als aktiven Menüpunkt anzeigen...
            elseif ($currentcategory == $CAT_REQUEST) {
                $mainmenu .= "<li class=\"mainmenu\">".
                    "<a href=\"".$url."\" class=\"menuactive\"".
                    $syntax->getTitleAttribute($language->getLanguageValue1("tooltip_link_category_1", catToName($currentcategory, false))).
                    ">".catToName($currentcategory, false)."</a>";
                if ($CMS_CONF->get("usesubmenu") > 0) {
                    $mainmenu .= getDetailMenu($currentcategory);
                }
                $mainmenu .= "</li>";
            }
            // ...alle anderen als normalen Menüpunkt.
            else {
                $mainmenu .= "<li class=\"mainmenu\">".
                    "<a href=\"".$url."\" class=\"menu\"".
                     $syntax->getTitleAttribute($language->getLanguageValue1("tooltip_link_category_1", catToName($currentcategory, false))).
                     ">".catToName($currentcategory, false)."</a>";
                if ($CMS_CONF->get("usesubmenu") == 2) {
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
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_DRAFT;
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $syntax;
        global $URL_BASE;
        global $EXT_LINK;
        global $CHARSET;

        if ($CMS_CONF->get("usesubmenu") > 0)
            $cssprefix = "submenu";
        else
            $cssprefix = "detailmenu";

        $detailmenu = "<ul class=\"detailmenu\">";
        // Sitemap
        if (($ACTION_REQUEST == "sitemap") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?action=sitemap\" class=\"".$cssprefix."active\">".$language->getLanguageValue0("message_sitemap_0")."</a></li>";
        // Suchergebnis
        elseif (($ACTION_REQUEST == "search") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?action=search&amp;query=".$specialchars->replaceSpecialChars($QUERY_REQUEST, true)."\" class=\"".$cssprefix."active\">".$language->getLanguageValue1("message_searchresult_1", html_entity_decode($QUERY_REQUEST,ENT_COMPAT,$CHARSET))."</a></li>";
        // Entwurfsansicht
        elseif (($ACTION_REQUEST == "draft") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"index.php?cat=$cat&amp;page=$PAGE_REQUEST&amp;action=draft\" class=\"".$cssprefix."active\">".pageToName($PAGE_REQUEST.$EXT_DRAFT, false)." (".$language->getLanguageValue0("message_draft_0").")</a></li>";
        // "ganz normales" Detailmenü einer Kategorie
        else {
            // Content-Verzeichnis der aktuellen Kategorie einlesen
            $contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$cat", true, false);

            // Kategorie, die nur versteckte Seiten enthält: kein Detailmenü zeigen
            if ($contentarray == "") {
                return "";
            }

            // Jedes Element des Arrays ans Menü anhängen
            foreach ($contentarray as $currentcontent) {
                // Inhaltsseite nicht anzeigen, wenn sie genauso heißt wie die Kategorie
                if ($CMS_CONF->get("hidecatnamedpages") == "true") {
                    if(substr($currentcontent, 3, strlen($currentcontent) - 7) == substr($CAT_REQUEST, 3, strlen($CAT_REQUEST) - 3) and substr($currentcontent,-(strlen($EXT_LINK))) != $EXT_LINK) {
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
                # Mod Rewrite
                $url = "index.php?cat=".$cat."&amp;page=".substr($currentcontent, 0, strlen($currentcontent) - 4);
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.$cat."/".substr($currentcontent, 0, strlen($currentcontent) - 4).".html";
                }
                // Aktuelle Inhaltsseite als aktiven Menüpunkt anzeigen...
                if (
                    ($CAT_REQUEST == $cat) // aktive Kategorie
                    && (substr($currentcontent, 0, strlen($currentcontent) - 4) == $PAGE_REQUEST) // aktive Seite
                    && (substr($currentcontent, -(strlen($EXT_LINK))) != $EXT_LINK) // aktive Seite
                ) {
                    $detailmenu .= "<li class=\"detailmenu\"><a href=\"".$url.
                                                    "\" class=\"".$cssprefix."active\"".
                                                    $syntax->getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($cat, false))).
                                                    ">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
                }
                // ...alle anderen als normalen Menüpunkt.
                else {
                    if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                        $detailmenu .= '<li class="detailmenu">'.menuLink($currentcontent,$cssprefix)."</li>";
                    } else {
                        $detailmenu .= "<li class=\"detailmenu\"><a href=\"".$url.
                                                    "\" class=\"".$cssprefix."\"".
                                                    $syntax->getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($cat, false))).
                                                    ">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
            }
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
        global $CMS_CONF;
        global $specialchars;
        global $CHARSET;
        global $LAYOUT_DIR;

        $form = "<form accept-charset=\"$CHARSET\" method=\"get\" action=\"index.php.html\" class=\"searchform\"><fieldset id=\"searchfieldset\">"
        ."<input type=\"hidden\" name=\"action\" value=\"search\" />"
        ."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" />"
        ."<input type=\"image\" name=\"action\" value=\"search\" src=\"".$LAYOUT_DIR."/grafiken/searchicon.gif\" alt=\"".$language->getLanguageValue0("message_search_0")."\" class=\"searchbutton\"".getTitleAttribute($language->getLanguageValue0("message_search_0"))." />"
        ."</fieldset></form>";
        return $form;
    }


// ------------------------------------------------------------------------------
// Rückgabe eines Array, bestehend aus:
// - Name der zuletzt geänderten Inhaltsseite
// - kompletter Link auf diese Inhaltsseite  
// - formatiertes Datum der letzten Änderung
// ------------------------------------------------------------------------------
    function getLastChangedContentPageAndDate(){
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
/*                        $url = "index.php?cat=$currentcategory&amp;page=".substr($matchingpage, 0, strlen($matchingpage) - 4);
                        if($CMS_CONF->get("modrewrite") == "true") {
                            $url = $URL_BASE.$currentcategory."/".substr($matchingpage, 0, strlen($matchingpage) - 4).".html";
                        }*/
        
        $lastchangedpage = $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true);
        $linktolastchangedpage = "<a href=\"index.php?cat=".$latestchanged['cat']."&amp;page=".substr($latestchanged['file'], 0, strlen($latestchanged['file'])-4)."\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true), $specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true, true)))." id=\"lastchangelink\">".$lastchangedpage."</a>";
        $lastchangedate = @strftime($language->getLanguageValue0("_dateformat_0"), date($latestchanged['time']));

        return array($lastchangedpage, $linktolastchangedpage,$lastchangedate);
    }


// ------------------------------------------------------------------------------
// Einlesen eines Kategorie-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
    function getLastChangeOfCat($dir){
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $CMS_CONF;

        $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinlastchanged") == "true");
        
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
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $EXT_LINK;
        global $URL_BASE;
        
        $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinsitemap") == "true");
        
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
                # ist ein link
                if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                    continue;
                }
                $url = "index.php?cat=$currentcategory&amp;page=".substr($currentcontent, 0, strlen($currentcontent) - 4);
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.$currentcategory."/".substr($currentcontent, 0, strlen($currentcontent) - 4).".html";
                }
                $sitemap .= "<li><a href=\"$url\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($currentcategory, false))).">".
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
        global $USE_CMS_SYNTAX;
        global $QUERY_REQUEST;
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $URL_BASE;
        global $EXT_LINK;

        $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinsearch") == "true");
        $matchesoverall = 0;
        $searchresults = "";

        // Überhaupt erst etwas machen, wenn die Suche nicht leer ist
        if (trim($QUERY_REQUEST) != "") {
            // Damit die Links in der Ergbnisliste korrekt sind: Suchanfrage bereinigen
            $queryarray = explode(" ", preg_replace('/"/', "", $QUERY_REQUEST));
            $searchresults .= "<h1>".$language->getLanguageValue1("message_searchresult_1", (trim($specialchars->rebuildSpecialChars($QUERY_REQUEST,true,true))))."</h1>"
            ."<div class=\"searchresults\">";

            // Kategorien-Verzeichnis einlesen
            $categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false, false);

            // Alle Kategorien durchsuchen
            foreach ($categoriesarray as $currentcategory) {

                // Wenn die Kategorie keine Contentseiten hat, direkt zur nächsten springen
                $contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true, $showhiddenpages);
                if ($contentarray == "") {
                    continue;
                }

                $matchingpages = array();
                $i = 0;

                // Alle Inhaltsseiten durchsuchen
                foreach ($contentarray as $currentcontent) {
                    # wenns ein link ist
                    if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                        continue;
                    }
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
                        $url = "index.php?cat=$currentcategory&amp;page=".substr($matchingpage, 0, strlen($matchingpage) - 4);
                        if($CMS_CONF->get("modrewrite") == "true") {
                            $url = $URL_BASE.$currentcategory."/".substr($matchingpage, 0, strlen($matchingpage) - 4).".html";
                        }
                        $pagename = pageToName($matchingpage, false);
                        $filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$matchingpage;
                        $searchresults .= "<li>".
                            "<a href=\"".$url.
                            "?highlight=".rawurlencode($highlightparameter)."\"".
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
        global $CHARSET;
        
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
                    $includedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($matches[1][$i],ENT_COMPAT,$CHARSET),false), $cat);
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
                    $includedpagescat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,$CHARSET),false));
                    $includedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1],ENT_COMPAT,$CHARSET),false), $includedpagescat);
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
            || ((filesize($filepath) > 0) && (substr_count(strtolower($content), strtolower(html_entity_decode($query,ENT_COMPAT,$CHARSET))) > 0))
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
        global $CMS_CONF;
        global $specialchars;

        $title = $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarformat"),true,true);
        $sep = $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarseparator"),true,true);
        $title = preg_replace('/{WEBSITE}/', $websitetitle, $title);
        if ($cattitle == "") {
            $title = preg_replace('/{CATEGORY}/', "", $title);
        }
        else {
            $title = preg_replace('/{CATEGORY}/', $cattitle, $title);
        }
        $title = preg_replace('/{PAGE}/', $pagetitle, $title);
        $title = preg_replace('/{SEP}/', $sep, $title);
        return $title;
    }



// ------------------------------------------------------------------------------
// Anzeige der Informationen zum System
// ------------------------------------------------------------------------------
    function getCmsInfo() {
        global $CMS_CONF;
        global $language;
        global $VERSION_CONF;
        return "<a href=\"http://cms.mozilo.de/\" target=\"_blank\" id=\"cmsinfolink\"".getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1", "http://cms.mozilo.de")).">moziloCMS ".$VERSION_CONF->get("cmsversion")."</a>";
    }


// ------------------------------------------------------------------------------
// Platzhalter im übergebenen String ersetzen
// ------------------------------------------------------------------------------
    function replacePlaceholders($content, $cattitle, $pagetitle) {
        global $CMS_CONF;
        global $specialchars;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $PAGE_FILE;
        global $EXT_PAGE;
        global $LAYOUT_DIR;

        // Titel der Website
        $content = preg_replace('/{WEBSITE_NAME}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true), $content);
        // Layout-Verzeichnis
        $content = preg_replace('/{LAYOUT_DIR}/', $LAYOUT_DIR, $content);

        if ($CAT_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{CATEGORY}/', $CAT_REQUEST, $content);
            // "sauberer" Name der aktuellen Kategorie ("Müllers Kuh")
            $content = preg_replace('/{CATEGORY_NAME}/', catToName($CAT_REQUEST, true), $content);
        }
        // Suche, Sitemap
        else {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{CATEGORY}/', $cattitle, $content);
            // "sauberer" Name der aktuellen Kategorie ("Müllers Kuh")
            $content = preg_replace('/{CATEGORY_NAME}/', $cattitle, $content);
        }

        if ($PAGE_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Inhaltsseite ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{PAGE}/', $PAGE_REQUEST, $content);
            // Dateiname der aktuellen Inhaltsseite ("10_M%FCllers%20Kuh.txt")
            $content = preg_replace('/{PAGE_FILE}/', $PAGE_FILE, $content);
            // "sauberer" Name der aktuellen Inhaltsseite ("Müllers Kuh")
            $content = preg_replace('/{PAGE_NAME}/', pageToName($PAGE_FILE, true), $content);
            
            $neighbourPages = getNeighbourPages($PAGE_REQUEST);
            // "unbehandelter" Name der vorigen Inhaltsseite ("00_Der%20M%FCller")
            $content = preg_replace('/{PREVIOUS_PAGE}/', substr($neighbourPages[0], 0, strlen($neighbourPages[0]) - 4), $content);
            // Dateiname der vorigen Inhaltsseite ("00_Der%20M%FCller.txt")
            $content = preg_replace('/{PREVIOUS_PAGE_FILE}/', $neighbourPages[0], $content);
            // "sauberer" Name der vorigen Inhaltsseite ("Der Müller")
            $content = preg_replace('/{PREVIOUS_PAGE_NAME}/', pageToName($neighbourPages[0], true), $content);
            // "unbehandelter" Name der nächsten Inhaltsseite ("20_M%FCllers%20M%FChle")
            $content = preg_replace('/{NEXT_PAGE}/', substr($neighbourPages[1], 0, strlen($neighbourPages[1]) - 4), $content);
            // Dateiname der nächsten Inhaltsseite ("20_M%FCllers%20M%FChle.txt")
            $content = preg_replace('/{NEXT_PAGE_FILE}/', $neighbourPages[1], $content);
            // "sauberer" Name der nächsten Inhaltsseite ("Müllers Mühle")
            $content = preg_replace('/{NEXT_PAGE_NAME}/', pageToName($neighbourPages[1], true), $content);
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

// ------------------------------------------------------------------------------
// Gibt das Kontaktformular zurück
// ------------------------------------------------------------------------------
    function buildContactForm() {
        global $contactformconfig;
        global $language;
        global $mailfunctions;
        global $CMS_CONF;
        global $WEBSITE_NAME;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $CHARSET;
        
        // existiert eine Mailadresse? Wenn nicht: Das Kontaktformular gar nicht anzeigen!
        if (strlen($contactformconfig->get("formularmail")) < 1) {
            return "<span class=\"deadlink\"".getTitleAttribute($language->getLanguageValue0("tooltip_no_mail_error_0")).">{CONTACT}</span>";
        }
        
        // Sollen die Spamschutz-Aufgaben verwendet werden?
        $usespamprotection = $contactformconfig->get("contactformusespamprotection") == "true";

        $config_name = explode(",", ($contactformconfig->get("name")));
        $config_mail = explode(",", ($contactformconfig->get("mail")));
        $config_website = explode(",", ($contactformconfig->get("website")));
        $config_message = explode(",", ($contactformconfig->get("message")));
        
        $errormessage = "";
        $form = "";
        
        if (isset($_SESSION['contactform_name'])) {
            $name       = getRequestParam($_SESSION['contactform_name'], false);
            $mail       = getRequestParam($_SESSION['contactform_mail'], false);
            $website    = getRequestParam($_SESSION['contactform_website'], false);
            $message    = getRequestParam($_SESSION['contactform_message'], false);
            $calcresult = getRequestParam($_SESSION['contactform_calculation'], false);
        }
        else {
            $name       = "";
            $mail       = "";
            $website    = "";
            $message    = "";
            $calcresult = "";
        }
        // Das Formular wurde abgesendet
        if (getRequestParam('submit', false) <> "") { 

            // Bot-Schutz: Wurde das Formular innerhalb von x Sekunden abgeschickt?
            $sendtime = $contactformconfig->get("contactformwaittime");
            if (($sendtime == "") || !preg_match("/^[\d+]+$/", $sendtime)) {
                $sendtime = 15;
            }
            if (time() - $_SESSION['contactform_loadtime'] < $sendtime) {
                $errormessage = $language->getLanguageValue1("contactform_senttoofast_1", $sendtime);
            }
            if ($usespamprotection) {
                // Nochmal Spamschutz: Ergebnis der Spamschutz-Aufgabe auswerten
                if (strtolower($calcresult) != strtolower($_SESSION['calculation_result'])) {
                    $errormessage = $language->getLanguageValue0("contactform_wrongresult_0");
                }
            }
            // Es ist ein Fehler aufgetreten!
            if ($errormessage == "") {
                // Eines der Pflichtfelder leer?
                if (($config_name[2] == "true") && ($name == "")) {
                    $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_name_0");
                }
                else if (($config_mail[2] == "true") && ($mail == "")) {
                    $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_mail_0");
                }
                else if (($config_website[2] == "true") && ($website == "")) {
                    $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_website_0");
                }
                else if (($config_message[2] == "true") && ($message == "")) {
                    $errormessage = $language->getLanguageValue0("contactform_fieldnotset_0")." ".$language->getLanguageValue0("contactform_message_0");
                }
            }
            // Es ist ein Fehler aufgetreten!
            if ($errormessage <> "") {
                $form .= "<span id=\"contact_errormessage\">".$errormessage."</span>";
            }
            else {
                $mailcontent = "";
                if ($config_name[1] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_name_0").":\t".$name."\r\n";
                }
                if ($config_mail[1] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_mail_0").":\t".$mail."\r\n";
                }
                if ($config_website[1] == "true") {
                    $mailcontent .= $language->getLanguageValue0("contactform_website_0").":\t".$website."\r\n";
                }
                if ($config_message[1] == "true") {
                    $mailcontent .= "\r\n".$language->getLanguageValue0("contactform_message_0").":\r\n".$message."\r\n";
                }
                $mailsubject = $language->getLanguageValue1("contactform_mailsubject_1", html_entity_decode($WEBSITE_NAME,ENT_COMPAT,$CHARSET));
                // Wenn Mail-Adresse gesetzt ist: erhält der Absender eine copy
                if ($mail <> "") {
                    $mailfunctions->sendMail($mailsubject, $mailcontent, $contactformconfig->get("formularmail"), $mail);
                }
                // Mail Senden an eingestelte emailadresse
                $mailfunctions->sendMail($mailsubject, $mailcontent, $contactformconfig->get("formularmail"), $contactformconfig->get("formularmail"));
                $form .= "<span id=\"contact_successmessage\">".$language->getLanguageValue0("contactform_confirmation_0")."</span>";
                
                // Felder leeren
                $name = "";
                $mail = "";
                $website = "";
                $message = "";
            }
        }

        // Wenn das Formular nicht abgesendet wurde: die Feldnamen neu bestimmen
        else {
            renameContactInputs();
        }
        
        // aktuelle Zeit merken
        $_SESSION['contactform_loadtime'] = time();
        $action_para = "index.php";
        if($CMS_CONF->get("modrewrite") == "true") {
            $action_para = $PAGE_REQUEST.".html";
        }
        $form .= "<form accept-charset=\"$CHARSET\" method=\"post\" action=\"$action_para\" name=\"contact_form\" id=\"contact_form\">"
        ."<input type=\"hidden\" name=\"cat\" value=\"".$CAT_REQUEST."\" />"
        ."<input type=\"hidden\" name=\"page\" value=\"".$PAGE_REQUEST."\" />"
        ."<table id=\"contact_table\" summary=\"contact form table\">";
        if ($config_name[1] == "true") {
            // Bezeichner aus formular.conf nutzen, wenn gesetzt
            if ($config_name[0] != "") {
                $form .= "<tr><td style=\"padding-right:10px;\">".$config_name[0];
            } else {
                $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_name_0");
            }
            if ($config_name[2] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_name\" name=\"".$_SESSION['contactform_name']."\" value=\"".$name."\" /></td></tr>";
        }
        if ($config_mail[1] == "true") {
            // Bezeichner aus formular.conf nutzen, wenn gesetzt
            if ($config_mail[0] != "") {
                $form .= "<tr><td style=\"padding-right:10px;\">".$config_mail[0];
            } else {
                $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_mail_0");
            }
            if ($config_mail[2] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_mail\" name=\"".$_SESSION['contactform_mail']."\" value=\"".$mail."\" /></td></tr>";
        }
        if ($config_website[1] == "true") {
            // Bezeichner aus formular.conf nutzen, wenn gesetzt
            if ($config_website[0] != "") {
                $form .= "<tr><td style=\"padding-right:10px;\">".$config_website[0];
            } else {
                $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_website_0");
            }
            if ($config_website[2] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><input type=\"text\" id=\"contact_website\" name=\"".$_SESSION['contactform_website']."\" value=\"".$website."\" /></td></tr>";
        }
        if ($config_message[1] == "true") {
            // Bezeichner aus formular.conf nutzen, wenn gesetzt
            if ($config_message[0] != "") {
                $form .= "<tr><td style=\"padding-right:10px;\">".$config_message[0];
            } else {
                $form .= "<tr><td style=\"padding-right:10px;\">".$language->getLanguageValue0("contactform_message_0");
            }
            if ($config_message[2] == "true") {
                $form .= "*";
            }
            $form .= "</td><td><textarea rows=\"10\" cols=\"50\" id=\"contact_message\" name=\"".$_SESSION['contactform_message']."\">".$message."</textarea></td></tr>";
        }
        if ($usespamprotection) {
            // Spamschutz-Aufgabe
            $calculation_data = getRandomCalculationData();
            $_SESSION['calculation_result'] = $calculation_data[1];
            $form .= "<tr><td colspan=\"2\">".$language->getLanguageValue0("contactform_spamprotection_text_0")."</td></tr>"
                ."<tr><td style=\"padding-right:10px;\">".$calculation_data[0]."</td>"
                ."<td><input type=\"text\" id=\"contact_calculation\" name=\"".$_SESSION['contactform_calculation']."\" value=\"\" /></td></tr>";
            
            $form .= "<tr><td style=\"padding-right:10px;\">&nbsp;</td><td>".$language->getLanguageValue0("contactform_mandatory_fields_0")."</td></tr>"
            ."<tr><td style=\"padding-right:10px;\">&nbsp;</td><td><input type=\"submit\" class=\"submit\" id=\"contact_submit\" name=\"submit\" value=\"".$language->getLanguageValue0("contactform_submit_0")."\" /></td></tr>";
        }
        $form .= "</table>"
        ."</form>";
        
        return $form;
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Sichert einen Input-Wert
// ------------------------------------------------------------------------------
    function cleanInput($input) {
        global $CHARSET;
        if (function_exists("mb_convert_encoding")) {
            $input = @mb_convert_encoding($input, $CHARSET);
        }
        return stripslashes($input);    
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Prüft einen Requestparameter
// ------------------------------------------------------------------------------
    function getRequestParam($param, $clean) {
        global $URL_BASE;
        global $CMS_CONF;

        if(($CMS_CONF->get("modrewrite") == "true") and ($param == "cat" or $param == "page")) {
            $request = NULL;
            if($param == "cat") {
                $url_get = str_replace($URL_BASE,"",$_SERVER['REQUEST_URI']);
                $url_para = explode("/",$url_get);
                if(count($url_para) > 1) {
                    $request = $url_para[0];
                } else {
                    $request = substr($url_get,0,-5);
                }
            } elseif($param == "page") {
                $url_get = str_replace($URL_BASE,"",$_SERVER['REQUEST_URI']);
                $url_get = str_replace("?".$_SERVER['QUERY_STRING'],"",$url_get);
                $url_para = explode("/",$url_get);
                if(count($url_para) > 1) {
                    $request = substr($url_para[1],0,-5);
                } else {
                    $request = NULL;
                }
            }
            return $request;
        }
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
        global $CMS_CONF;
        if ($CMS_CONF->get("showsyntaxtooltips") == "true") {
            return " title=\"".$value."\"";
        }
        return "";
    }


// ------------------------------------------------------------------------------
// Rückgabe der Dateinamen der vorigen und nächsten Seite
// ------------------------------------------------------------------------------
    function getNeighbourPages($page) {
        global $CONTENT_DIR_ABS;
        global $CAT_REQUEST;
        global $CMS_CONF;
        
        // leer initialisieren
        $neighbourPages = array("", "");
        // aktuelle Kategorie einlesen
        $pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", true, $CMS_CONF->get("showhiddenpagesincmsvariables") == "true");
        // Schleife über alle Seiten
        for ($i = 0; $i < count($pagesarray); $i++) {
            if ($page == substr($pagesarray[$i], 0, strlen($pagesarray[$i]) - 4)) {
                // vorige Seite (nur setzen, wenn aktuelle nicht die erste ist)
                if ($i > 0) {
                    $neighbourPages[0] = $pagesarray[$i-1];
                }
                // nächste Seite (nur setzen, wenn aktuelle nicht die letzte ist)
                if($i < count($pagesarray)-1) {
                    $neighbourPages[1] = $pagesarray[$i+1];
                }
                // Schleife kann abgebrochen werden
                break;
            }
        }

        return $neighbourPages;
    }


// ------------------------------------------------------------------------------
// Hilfsfunktion: Zufällige Spamschutz-Rechenaufgabe und deren Ergebnis zurückgeben
// ------------------------------------------------------------------------------
    function getRandomCalculationData() {
        global $contactformcalcs;
        $confarray = $contactformcalcs->toArray();
        unset($confarray['readonly']);
        $tmp = array_keys($confarray);
        $randnum = rand(0, count($confarray)-1);
        return array($tmp[$randnum],$confarray[$tmp[$randnum]]);
    }

// ------------------------------------------------------------------------------
// zeigt Galerie anstelle eines Seiteninhalts an
// ------------------------------------------------------------------------------    
    function getEmbeddedGallery() { 
        global $language;
        
        include("gallery.php");
        $gallery->setLinkPrefix("index.php?action=gallery&amp;");        
        return array($gallery->renderGallery(),
                     $language->getLanguageValue1("message_gallery_1", $gallery->getGalleryName()),
                     $gallery->getCurrentIndex());
    }
// ------------------------------------------------------------------------------
// Hilfsfunktion: Bestimmt die Inputnamen neu
// ------------------------------------------------------------------------------    
    function renameContactInputs() {
        $_SESSION['contactform_name'] = time()-rand(30, 40);
        $_SESSION['contactform_mail'] = time()-rand(10, 20);
        $_SESSION['contactform_website'] = time()-rand(0, 10);
        $_SESSION['contactform_message'] = time()-rand(40, 50);
        $_SESSION['contactform_calculation'] = time()-rand(50, 60);
    }
    

// ------------------------------------------------------------------------------
// Hilfsfunktion: Plugin-Variablen ersetzen
// ------------------------------------------------------------------------------    
    function replacePluginVariables($content) {
        global $PLUGIN_DIR;
        global $syntax;
        global $language;
        
        $availableplugins = array();
        
        // alle Plugins einlesen
        $dircontent = getDirContentAsArray(getcwd()."/$PLUGIN_DIR", false, false);
        foreach ($dircontent as $currentelement) {
            if (file_exists(getcwd()."/$PLUGIN_DIR/".$currentelement."/index.php")) {
                array_push($availableplugins, $currentelement);
            }
        }

        // Alle Variablen aus dem Inhalt heraussuchen
        preg_match_all("/\{(.+)\}/Umsi", $content, $matches);
        // Für jeden Treffer...
        $i = 0;
        foreach ($matches[0] as $match) {
            // ...erstmal schauen, ob ein Wert dabeisteht, z.B. {VARIABLE|wert}
            $valuearray = explode("|", $matches[1][$i]);
            if (sizeof($valuearray) > 1) {
                $currentvariable = $valuearray[0];
                $currentvalue = $valuearray[1];
            }
            // Sonst den Wert leer vorbelegen
            else {
                $currentvariable = $matches[1][$i];
                $currentvalue = "";
            }
            
            // ...überprüfen, ob es eine zugehörige Plugin-PHP-Datei gibt
            if (in_array($currentvariable, $availableplugins)) {
                $replacement = "";
                // Plugin-Code includieren
                require_once(getcwd()."/$PLUGIN_DIR/".$currentvariable."/index.php");
                // Enthält der Code eine Klasse mit dem Namen des Plugins?
                if (class_exists($currentvariable)) {
                    // Objekt instanziieren und Inhalt holen!
                    $currentpluginobject = new $currentvariable();
                    $replacement = $currentpluginobject->getPluginContent($currentvalue);
                }
                else {
                    $replacement = $syntax->createDeadlink($matches[0][$i], $language->getLanguageValue1("plugin_error_1", $currentvariable));
                }
                // Variable durch Plugin-Inhalt (oder Fehlermeldung) ersetzen
                $content = preg_replace('/{'.preg_quote($matches[1][$i], '/').'}/Um', $replacement, $content);
            }
            $i++;
        }
        return $content;
    }

    function menuLink($link,$css) {
        global $EXT_LINK;
        global $specialchars;
        global $syntax;
        global $language;

        if(!empty($css)) {
             $css = ' class="'.$css.'"';
        }
        $target = "_blank";
        if(strstr($link,"-_blank-")) {
            $tmp_link = explode("-_blank-",$link);
            if(substr($tmp_link[1], 0, 13) != "http%3A%2F%2F") {
                $tmp_link[1] = "http%3A%2F%2F".$tmp_link[1];
            }
        }
        if(strstr($link,"-_self-")) {
            $tmp_link = explode("-_self-",$link);
            $target = "_self";
        }
        $titel = $syntax->getTitleAttribute($language->getLanguageValue1("tooltip_link_link",$specialchars->rebuildSpecialChars($tmp_link[1], true, true)));
        return '<a href="'.$specialchars->rebuildSpecialChars(substr($tmp_link[1],0,-(strlen($EXT_LINK))), true, true).'"'.$css.' target="'.$target.'"'.$titel.'>'.$specialchars->rebuildSpecialChars(substr($tmp_link[0],3), true, true).'</a> ';
    }
    
?>