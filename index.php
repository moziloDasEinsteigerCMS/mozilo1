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
$BASE_DIR = getcwd()."/";
$CMS_DIR_NAME = "cms";
$BASE_DIR_CMS = $BASE_DIR.$CMS_DIR_NAME."/";


#$CHARSET = 'ISO-8859-1';
$CHARSET = 'UTF-8';

    require_once($BASE_DIR_CMS."DefaultConf.php");
    require_once($BASE_DIR_CMS."SpecialChars.php");
    require_once($BASE_DIR_CMS."Properties.php");
    
    // Initial: Fehlerausgabe unterdruecken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
#    @ini_set("display_errors", 0);

    $specialchars   = new SpecialChars();
    $CMS_CONF     = new Properties($BASE_DIR_CMS."conf/main.conf",true);
    $VERSION_CONF  = new Properties($BASE_DIR_CMS."conf/version.conf",true);
    $GALLERY_CONF  = new Properties($BASE_DIR_CMS."conf/gallery.conf",true);
    $USER_SYNTAX  = new Properties($BASE_DIR_CMS."conf/syntax.conf",true);
    $URL_BASE = NULL;
    if($CMS_CONF->get("modrewrite") == "true") {
        $URL_BASE = substr(str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']),0,-(strlen("index.php")));
    }
    require_once($BASE_DIR_CMS."Language.php");
    $language       = new Language();
    require_once($BASE_DIR_CMS."Syntax.php");
    require_once($BASE_DIR_CMS."Smileys.php");
    require_once($BASE_DIR_CMS."Mail.php");
    $syntax         = new Syntax();
    $smileys        = new Smileys($BASE_DIR_CMS."smileys");
    $mailfunctions  = new Mail();

    require_once($BASE_DIR_CMS."Plugin.php");

    // Dateiendungen fuer Inhaltsseiten
    # Achtung die endungen muessen alle gleich lang sein
    $EXT_PAGE       = ".txt";
    $EXT_HIDDEN     = ".hid";
    $EXT_DRAFT      = ".tmp";
    $EXT_LINK       = ".lnk";

    $LAYOUT_DIR     = "layouts/".$CMS_CONF->get("cmslayout");
    $TEMPLATE_FILE  = $LAYOUT_DIR."/template.html";

    # wenn ein Plugin die gallerytemplate.html benutzten möchte und sie blank ist 
    if ($GALLERY_CONF->get("target") == "_blank" and getRequestParam("gal", true)) {
        $TEMPLATE_FILE  = $LAYOUT_DIR."/gallerytemplate.html";
    }

    $LAYOUT_DIR_URL = $specialchars->replaceSpecialChars($URL_BASE.$LAYOUT_DIR,true);
    $CSS_FILE       = $LAYOUT_DIR_URL."/css/style.css";
    $FAVICON_FILE   = $LAYOUT_DIR_URL."/favicon.ico";
    // Einstellungen fuer Kontaktformular
    $contactformconfig  = new Properties($BASE_DIR_CMS."formular/formular.conf",true);
    $contactformcalcs   = new Properties($BASE_DIR_CMS."formular/aufgaben.conf",true);


    $WEBSITE_NAME = $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true);
    if ($WEBSITE_NAME == "")
        $WEBSITE_NAME = "Titel der Website";

    $USE_CMS_SYNTAX = true;
    if ($CMS_CONF->get("usecmssyntax") == "false")
        $USE_CMS_SYNTAX = false;
        
    // Request-Parameter einlesen und dabei absichern
    $CAT_REQUEST_URL = $specialchars->replaceSpecialChars(getRequestParam('cat', false),false);
    $PAGE_REQUEST_URL = $specialchars->replaceSpecialChars(getRequestParam('page', false),false);
    $ACTION_REQUEST = getRequestParam('action', true);
    $QUERY_REQUEST = getRequestParam('query', true);
    $HIGHLIGHT_REQUEST = getRequestParam('highlight', false);

#    $CONTENT_DIR_REL        = "kategorien";
    $CONTENT_DIR_NAME        = "kategorien";
    $CONTENT_DIR_REL        = $BASE_DIR.$CONTENT_DIR_NAME."/";
    $CONTENT_FILES_DIR_NAME      = "dateien";
    $GALLERIES_DIR_NAME          = "galerien";
    $PLUGIN_DIR_NAME         = "plugins";
    $PLUGIN_DIR_REL         = $BASE_DIR.$PLUGIN_DIR_NAME."/";
    $HTML                   = "";

    $DEFAULT_CATEGORY = $CMS_CONF->get("defaultcat");
    // Ueberpruefen: Ist die Startkategorie vorhanden? Wenn nicht, nimm einfach die allererste als Standardkategorie
    if (!file_exists($CONTENT_DIR_REL.$DEFAULT_CATEGORY)) {
        $contentdir = opendir($CONTENT_DIR_REL);
        while ($cat = readdir($contentdir)) {
            if (isValidDirOrFile($cat)) {
                $DEFAULT_CATEGORY = $cat;
                break;
            }
        }
        closedir($contentdir);
    }
   
    $CAT_REQUEST = nameToCategory($CAT_REQUEST_URL);
    if ($CAT_REQUEST == "") {
    	$CAT_REQUEST = $DEFAULT_CATEGORY;
    }
    $PAGE_REQUEST = nameToPage($PAGE_REQUEST_URL, $CAT_REQUEST,false);

    // Dateiname der aktuellen Inhaltsseite (wird in getContent() gesetzt)
    $PAGE_FILE = "";

    // Zuerst: Uebergebene Parameter ueberpruefen
    checkParameters();
    // Dann: HTML-Template einlesen und mit Inhalt fuellen
    readTemplate();
    // Zum Schluß: Ausgabe des fertigen HTML-Dokuments
    echo $HTML;


// ------------------------------------------------------------------------------
// Parameter auf Korrektheit pruefen
// ------------------------------------------------------------------------------
    function checkParameters() {
        global $CONTENT_DIR_REL;
        global $DEFAULT_CATEGORY;
        global $ACTION_REQUEST;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $CMS_CONF;

        // Ueberpruefung der gegebenen Parameter
        if (
                // Wenn keine Kategorie uebergeben wurde...
                ($CAT_REQUEST == "")
                // ...oder eine nicht existente Kategorie...
                || (!file_exists($CONTENT_DIR_REL.$CAT_REQUEST))
                // ...oder eine Kategorie ohne Contentseiten...
                || (getDirContentAsArray($CONTENT_DIR_REL.$CAT_REQUEST, true, true) == "")
            )
            // ...dann verwende die Standardkategorie
            $CAT_REQUEST = $DEFAULT_CATEGORY;


        // Kategorie-Verzeichnis einlesen
        $pagesarray = getDirContentAsArray($CONTENT_DIR_REL.$CAT_REQUEST, true, $CMS_CONF->get("showhiddenpagesasdefaultpage") == "true");

        // Wenn Contentseite nicht explizit angefordert wurde oder nicht vorhanden ist...
        if (
            ($PAGE_REQUEST == "")
            || (!file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_PAGE) && !file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_HIDDEN) && !file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_DRAFT))
            ) {
            //...erste Contentseite der Kategorie setzen
            $PAGE_REQUEST = substr($pagesarray[0], 0, strlen($pagesarray[0]) - 4);
        }

        // Wenn ein Action-Parameter uebergeben wurde: keine aktiven Kat./Inhaltts. anzeigen
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
        global $LAYOUT_DIR_URL;
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
        global $BASE_DIR_CMS;

    if (!$file = @fopen($TEMPLATE_FILE, "r"))
        die($language->getLanguageValue1("message_template_error_1", $TEMPLATE_FILE));
    $template = fread($file, filesize($TEMPLATE_FILE));
    fclose($file);
        // Platzhalter des Templates mit Inhalt fuellen
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
    // Inhalte aus Inhaltsseiten durch Passwort schuetzen
    else { 
        // zunaechst Passwort als gesetzt und nicht eingegeben annehmen
        $passwordok = false;
        if (file_exists($BASE_DIR_CMS."conf/passwords.conf")) {
            $passwords = new Properties($BASE_DIR_CMS."conf/passwords.conf", true); // alle Passwörter laden
            if ($passwords->keyExists($CAT_REQUEST.'/'.$PAGE_REQUEST)) { // nach Passwort fuer diese Seite suchen
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
    $HTML = preg_replace('/{CONTENT}/', $pagecontent, $HTML);
    // Benutzer-Variablen ersetzen
    $HTML = replacePluginVariables($HTML);

    $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $HTML);
    $HTML = preg_replace('/{CHARSET}/', $CHARSET, $HTML);
    $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    $HTML = preg_replace('/{LAYOUT_DIR}/', $LAYOUT_DIR_URL, $HTML);

    // Platzhalter ersetzen
    $HTML = replacePlaceholders($HTML, $cattitle, $pagetitle);
    if(strpos($HTML,'{WEBSITE_TITLE}') !== false)
        $HTML = preg_replace('/{WEBSITE_TITLE}/', getWebsiteTitle($WEBSITE_NAME, $cattitle, $pagetitle), $HTML);

    // Meta-Tag "keywords"
    $HTML = preg_replace('/{WEBSITE_KEYWORDS}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitekeywords"),false,true), $HTML);
    // Meta-Tag "description"
    $HTML = preg_replace('/{WEBSITE_DESCRIPTION}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitedescription"),false,true), $HTML);

    if(strpos($HTML,'{MAINMENU}') !== false)
        $HTML = preg_replace('/{MAINMENU}/', getMainMenu(), $HTML);

    if(strpos($HTML,'{DETAILMENU}') !== false) {
        // Detailmenue (nicht zeigen, wenn Submenues aktiviert sind)
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

    // Letzte Aenderung (obsolet seit 1.12 - nur aus Gründen der Abwärtskompatibilität noch dabei) 
    if(strpos($HTML,'{LASTCHANGE}') !== false) {
        $HTML = preg_replace('/{LASTCHANGE}/', $language->getLanguageValue0("message_lastchange_0")." ".$lastchangeinfo[1]." (".$lastchangeinfo[2].")", $HTML); 
    }
    
    // Sitemap-Link
    $HTML = preg_replace('/{SITEMAPLINK}/', "<a href=\"".$URL_BASE."index.php?action=sitemap\" id=\"sitemaplink\"".getTitleAttribute($language->getLanguageValue0("tooltip_showsitemap_0")).">".$language->getLanguageValue0("message_sitemap_0")."</a>", $HTML);
    
    // CMS-Info-Link
    if(strpos($HTML,'{CMSINFO}') !== false)
        $HTML = preg_replace('/{CMSINFO}/', getCmsInfo(), $HTML);
      
    // Kontaktformular
    if(strpos($HTML,'{CONTACT}') !== false)
        $HTML = preg_replace('/{CONTACT}/', buildContactForm(), $HTML);

    // Kontaktformular
    if(strpos($HTML,'{TABLEOFCONTENTS}') !== false)
        $HTML = preg_replace('/{TABLEOFCONTENTS}/', $syntax->getToC($pagecontent), $HTML);

    # Titel der Galerie wird bei blank benutzt
    if(strpos($HTML,'{CURRENTGALLERY}') !== false) {
        if(getRequestParam('gal', false)) {
            $HTML = preg_replace('/{CURRENTGALLERY}/', $specialchars->rebuildSpecialChars(getRequestParam('gal', true),false,true), $HTML);
        }
    }

    }
    
// ------------------------------------------------------------------------------
// Formular zur Passworteingabe anzeigen
// ------------------------------------------------------------------------------
    function getPasswordForm() {
        global $language;
        global $CMS_CONF;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $URL_BASE;

        $url = "index.php?cat=".substr($CAT_REQUEST,3)."&amp;page=".substr($PAGE_REQUEST,3);
        if($CMS_CONF->get("modrewrite") == "true") {
            $url = $URL_BASE.substr($CAT_REQUEST,3)."/".substr($PAGE_REQUEST,3).".html";
        }
        // TODO: sollte auch wahlweise ueber ein Template gehen
        return '<form action="'.$url.'" method="post" class="contentpassword">
        '.$language->getLanguageValue0("passwordform_pagepasswordplease_0").' 
        <input type="password" name="password" class="contentpassword_input" />
        <input type="submit" value="'.$language->getLanguageValue0("passwordform_send_0").'" class="contentpassword_button" />
        </form>';
    }

// ------------------------------------------------------------------------------
// Zu einem Kategorienamen passendes Kategorieverzeichnis suchen und zurueckgeben
// Alle Kuehe => 00_Alle-nbsp-K-uuml-he
// ------------------------------------------------------------------------------
    function nameToCategory($catname) {
        global $CONTENT_DIR_REL;
        // Content-Verzeichnis einlesen
        $dircontent = getDirContentAsArray($CONTENT_DIR_REL, false, false);
        // alle vorhandenen Kategorien durchgehen...
        foreach ($dircontent as $currentelement) {
            // ...und wenn eine auf den Namen paßt...
            if (substr($currentelement, 3, strlen($currentelement)-3) == $catname) {
                // ...den vollen Kategorienamen zurueckgeben
                return $currentelement;
            # bei alten links ist die Position noch dabei
            } elseif($currentelement == $catname) {
                return $currentelement;
            }
        }
        // Wenn kein Verzeichnis paßt: Leerstring zurueckgeben
        return "";
    }


// ------------------------------------------------------------------------------
// Zu einer Inhaltsseite passende Datei suchen und zurueckgeben
// Muellers Kuh => 00_M-uuml-llers-nbsp-Kuh.txt
// ------------------------------------------------------------------------------
    function nameToPage($pagename, $currentcat, $ext = true) {
        global $CONTENT_DIR_REL;
        global $EXT_PAGE;

        // Kategorie-Verzeichnis einlesen
        $dircontent = getDirContentAsArray($CONTENT_DIR_REL.$currentcat, true, true);
        // alle vorhandenen Inhaltsdateien durchgehen...
        foreach ($dircontent as $currentelement) {
            // ...und wenn eine auf den Namen paßt...
            if (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($EXT_PAGE)) == $pagename) {
                // ...den vollen Seitennamen zurueckgeben mit extension
                if($ext) {
                    return $currentelement;
                } else {
                // ...den vollen Seitennamen zurueckgeben ohne extension
                    return substr($currentelement, 0, strlen($currentelement) - strlen($EXT_PAGE));
                }
            # bei alten links ist die Positon noch im Namen
            } elseif (substr($currentelement, 0, strlen($currentelement) - strlen($EXT_PAGE)) == $pagename) {
                // ...den vollen Seitennamen zurueckgeben mit extension
                if($ext) {
                    return $currentelement;
                } else {
                // ...den vollen Seitennamen zurueckgeben ohne extension
                    return substr($currentelement, 0, strlen($currentelement) - strlen($EXT_PAGE));
                }
            }
        }
        // Wenn keine Datei paßt: Leerstring zurueckgeben
        return "";
    }


// ------------------------------------------------------------------------------
// Kategorienamen aus komplettem Verzeichnisnamen einer Kategorie zurueckgeben
// 00_Alle-nbsp-K-uuml-he => Alle Kuehe
// ------------------------------------------------------------------------------
    function catToName($cat, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Seitennamen aus komplettem Dateinamen einer Inhaltsseite zurueckgeben
// 00_M-uuml-llers-nbsp-Kuh.txt => Muellers Kuh
// ------------------------------------------------------------------------------
    function pageToName($page, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($page, 3, strlen($page) - 7), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getContent() {
        global $CONTENT_DIR_REL;
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
                (file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_DRAFT))
            ) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_HIDDEN;
            return array (
                                        implode("", file($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_DRAFT)),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_DRAFT, true)
                                        );
        }
        // normale Inhaltsseite
        elseif (file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_PAGE)) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_PAGE;
            return array (
                                        implode("", file($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_PAGE)),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_PAGE, true)
                                        );
        }
        // Versteckte Inhaltsseite
        elseif (file_exists($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_HIDDEN)) {
            $PAGE_FILE = $PAGE_REQUEST.$EXT_HIDDEN;
            return array (
                                        implode("", file($CONTENT_DIR_REL.$CAT_REQUEST."/".$PAGE_REQUEST.$EXT_HIDDEN)),
                                        catToName($CAT_REQUEST, true),
                                        pageToName($PAGE_REQUEST.$EXT_HIDDEN, true)
                                        );
        }
        else
            return array("","","");
    }


// ------------------------------------------------------------------------------
// Auslesen des Content-Verzeichnisses unter Beruecksichtigung
// des auszuschließenden File-Verzeichnisses, Rueckgabe als Array
// ------------------------------------------------------------------------------
    function getDirContentAsArray($dir, $iscatdir, $showhidden) {
        global $CONTENT_FILES_DIR_NAME;
        global $EXT_DRAFT;
        global $EXT_HIDDEN;
        global $EXT_PAGE;
        global $EXT_LINK;

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
                    // ...und nicht $CONTENT_FILES_DIR_NAME
                    && (($file <> $CONTENT_FILES_DIR_NAME) || (!$iscatdir))
                    // nicht "." und ".."
                    && isValidDirOrFile($file)
                    ) {
            $files[$i] = $file;
            $i++;
            }
        }
        closedir($currentdir);
        // Rueckgabe des sortierten Arrays
        if ($files <> "")
            sort($files);
        return $files;
    }


// ------------------------------------------------------------------------------
// Aufbau des Hauptmenues, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getMainMenu() {
        global $CONTENT_DIR_REL;
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
        $categoriesarray = getDirContentAsArray($CONTENT_DIR_REL, false, false);
        // Jedes Element des Arrays ans Menue anhaengen
        foreach ($categoriesarray as $currentcategory) {
            # Mod Rewrite
            $url = "index.php?cat=".substr($currentcategory,3);
            if($CMS_CONF->get("modrewrite") == "true") {
                $url = $URL_BASE.substr($currentcategory,3).".html";
            }
            if(substr($currentcategory,-(strlen($EXT_LINK))) == $EXT_LINK) {
               $mainmenu .= '<li class="mainmenu">'.menuLink($currentcategory,"menu")."</li>";
            }
            // Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
            elseif (getDirContentAsArray($CONTENT_DIR_REL.$currentcategory, true, false) == "") {
                $mainmenu .= "";
            }
            // Aktuelle Kategorie als aktiven Menuepunkt anzeigen...
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
            // ...alle anderen als normalen Menuepunkt.
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
        // Rueckgabe des Menues
        return $mainmenu . "</ul>";
    }


// ------------------------------------------------------------------------------
// Aufbau des Detailmenues, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getDetailMenu($cat){
        global $ACTION_REQUEST;
        global $QUERY_REQUEST;
        global $CONTENT_DIR_REL;
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

        # Mod Rewrite
        $url_draft = "index.php?cat=".substr($cat,3)."&amp;page=".substr($PAGE_REQUEST, 3)."&amp;";
        $modrewrite_dumy = NULL;
        if($CMS_CONF->get("modrewrite") == "true") {
            $url_draft = $URL_BASE.substr($cat,3)."/".substr($PAGE_REQUEST, 3).".html?";
            $modrewrite_dumy = ".html";
        }
        $detailmenu = "<ul class=\"detailmenu\">";
        // Sitemap
        if (($ACTION_REQUEST == "sitemap") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"".$URL_BASE."index.php".$modrewrite_dumy."?action=sitemap\" class=\"".$cssprefix."active\">".$language->getLanguageValue0("message_sitemap_0")."</a></li>";
        // Suchergebnis
        elseif (($ACTION_REQUEST == "search") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"".$URL_BASE."index.php".$modrewrite_dumy."?action=search&amp;query=".$specialchars->replaceSpecialChars($QUERY_REQUEST, true)."\" class=\"".$cssprefix."active\">".$language->getLanguageValue1("message_searchresult_1", $specialchars->getHtmlEntityDecode($QUERY_REQUEST))."</a></li>";
        // Entwurfsansicht
        elseif (($ACTION_REQUEST == "draft") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= "<li class=\"detailmenu\"><a href=\"".$url_draft."action=draft\" class=\"".$cssprefix."active\">".pageToName($PAGE_REQUEST.$EXT_DRAFT, false)." (".$language->getLanguageValue0("message_draft_0").")</a></li>";
        // "ganz normales" Detailmenue einer Kategorie
        else {
            // Content-Verzeichnis der aktuellen Kategorie einlesen
            $contentarray = getDirContentAsArray($CONTENT_DIR_REL.$cat, true, false);

            // Kategorie, die nur versteckte Seiten enthaelt: kein Detailmenue zeigen
            if ($contentarray == "") {
                return "";
            }

            // Jedes Element des Arrays ans Menue anhaengen
            foreach ($contentarray as $currentcontent) {
                // Inhaltsseite nicht anzeigen, wenn sie genauso heißt wie die Kategorie
                if ($CMS_CONF->get("hidecatnamedpages") == "true") {
                    if(substr($currentcontent, 3, strlen($currentcontent) - 7) == substr($CAT_REQUEST, 3, strlen($CAT_REQUEST) - 3) and substr($currentcontent,-(strlen($EXT_LINK))) != $EXT_LINK) {
                        // Wenn es in der Kategorie nur diese eine (dank hidecatnamedpages eh nicht angezeigte) Seite gibt,
                        // dann gib als Detailmenue gleich einen Leerstring zurueck
                        if (count($contentarray) == 1) {
                            return "";
                        } 
                        // ...ansonsten auf zur naechsten Inhaltsseite!
                        else {
                            continue;
                        }
                    }
                }
                # Mod Rewrite
                $url = "index.php?cat=".substr($cat,3)."&amp;page=".substr($currentcontent, 3, strlen($currentcontent) - 7);
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.substr($cat,3)."/".substr($currentcontent, 3, strlen($currentcontent) - 7).".html";
                }
                // Aktuelle Inhaltsseite als aktiven Menuepunkt anzeigen...
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
                // ...alle anderen als normalen Menuepunkt.
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
        // Rueckgabe des Menues
        return $detailmenu . "</ul>";
    }


// ------------------------------------------------------------------------------
// Rueckgabe des Suchfeldes
// ------------------------------------------------------------------------------
    function getSearchForm(){
        global $language;
        global $CMS_CONF;
        global $specialchars;
        global $CHARSET;
        global $LAYOUT_DIR_URL;

        $modrewrite_dumy = NULL;
        if($CMS_CONF->get("modrewrite") == "true") {
            $modrewrite_dumy = ".html";
        }
        $form = "<form accept-charset=\"$CHARSET\" method=\"get\" action=\"index.php$modrewrite_dumy\" class=\"searchform\"><fieldset id=\"searchfieldset\">"
        ."<input type=\"hidden\" name=\"action\" value=\"search\" />"
        ."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" />"
        ."<input type=\"image\" name=\"action\" value=\"search\" src=\"".$LAYOUT_DIR_URL."/grafiken/searchicon.gif\" alt=\"".$language->getLanguageValue0("message_search_0")."\" class=\"searchbutton\"".getTitleAttribute($language->getLanguageValue0("message_search_0"))." />"
        ."</fieldset></form>";
        return $form;
    }


// ------------------------------------------------------------------------------
// Rueckgabe eines Array, bestehend aus:
// - Name der zuletzt geaenderten Inhaltsseite
// - kompletter Link auf diese Inhaltsseite  
// - formatiertes Datum der letzten Aenderung
// ------------------------------------------------------------------------------
    function getLastChangedContentPageAndDate(){
        global $CONTENT_DIR_REL;
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $URL_BASE;

        $latestchanged = array("cat" => "catname", "file" => "filename", "time" => 0);
        $currentdir = opendir($CONTENT_DIR_REL);
        while ($file = readdir($currentdir)) {
            if (isValidDirOrFile($file)) {
                $latestofdir = getLastChangeOfCat($CONTENT_DIR_REL.$file);
                if ($latestofdir['time'] > $latestchanged['time']) {
                    $latestchanged['cat'] = $file;
                    $latestchanged['file'] = $latestofdir['file'];
                    $latestchanged['time'] = $latestofdir['time'];
                }
            }
        }
        closedir($currentdir);
        
        $lastchangedpage = $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true);
        # Mod Rewrite
        $url = "index.php?cat=".substr($latestchanged['cat'],3)."&amp;page=".substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7);
        if($CMS_CONF->get("modrewrite") == "true") {
            $url = $URL_BASE.substr($latestchanged['cat'],3)."/".substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7).".html";
        }
        $linktolastchangedpage = "<a href=\"".$url."\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true), $specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true, true)))." id=\"lastchangelink\">".$lastchangedpage."</a>";


        $lastchangedate = @strftime($language->getLanguageValue0("_dateformat_0"), date($latestchanged['time']));

        return array($lastchangedpage, $linktolastchangedpage,$lastchangedate);
    }


// ------------------------------------------------------------------------------
// Einlesen eines Kategorie-Verzeichnisses, Rueckgabe der zuletzt geaenderten Datei
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
        global $CONTENT_DIR_REL;
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $EXT_LINK;
        global $URL_BASE;
        
        $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinsitemap") == "true");
        
        $sitemap = "<h1>".$language->getLanguageValue0("message_sitemap_0")."</h1>"
        ."<div class=\"sitemap\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = getDirContentAsArray($CONTENT_DIR_REL, false, false);
        // Jedes Element des Arrays an die Sitemap anhaengen
        foreach ($categoriesarray as $currentcategory) {
            // Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
            $contentarray = getDirContentAsArray($CONTENT_DIR_REL.$currentcategory, true, $showhiddenpages);
            if ($contentarray == "")
                continue;

            $sitemap .= "<h2>".catToName($currentcategory, false)."</h2><ul>";
            // Alle Inhaltsseiten der aktuellen Kategorie auflisten...
            // Jedes Element des Arrays an die Sitemap anhaengen
            foreach ($contentarray as $currentcontent) {
                # ist ein link
                if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                    continue;
                }
                $url = "index.php?cat=".substr($currentcategory,3)."&amp;page=".substr($currentcontent, 3, strlen($currentcontent) - 7);
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.substr($currentcategory,3)."/".substr($currentcontent, 3, strlen($currentcontent) - 7).".html";
                }
                $sitemap .= "<li><a href=\"$url\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($currentcategory, false))).">".
                                                    pageToName($currentcontent, false).
                                                    "</a></li>";
            }
            $sitemap .= "</ul>";
        }
        $sitemap .= "</div>";
        // Rueckgabe der Sitemap
        return array($sitemap, $language->getLanguageValue0("message_sitemap_0"), $language->getLanguageValue0("message_sitemap_0"));
    }


// ------------------------------------------------------------------------------
// Anzeige der Suchergebnisse
// ------------------------------------------------------------------------------
    function getSearchResult() {
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

        // Ueberhaupt erst etwas machen, wenn die Suche nicht leer ist
        if (trim($QUERY_REQUEST) != "") {
            // Damit die Links in der Ergbnisliste korrekt sind: Suchanfrage bereinigen
            $queryarray = explode(" ", preg_replace('/"/', "", $QUERY_REQUEST));
            $searchresults .= "<h1>".$language->getLanguageValue1("message_searchresult_1", (trim($specialchars->rebuildSpecialChars($QUERY_REQUEST,true,true))))."</h1>"
            ."<div class=\"searchresults\">";

            // Kategorien-Verzeichnis einlesen
            $categoriesarray = getDirContentAsArray($CONTENT_DIR_REL, false, false);

            // Alle Kategorien durchsuchen
            foreach ($categoriesarray as $currentcategory) {

                // Wenn die Kategorie keine Contentseiten hat, direkt zur naechsten springen
                $contentarray = getDirContentAsArray($CONTENT_DIR_REL.$currentcategory, true, $showhiddenpages);
                if ($contentarray == "") {
                    continue;
                }

                $matchingpages = array();

                // Alle Inhaltsseiten durchsuchen
                foreach ($contentarray as $currentcontent) {
                    # wenns ein link ist
                    if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                        continue;
                    }
                    // Treffer in der aktuellen Seite?
                    if (pageContainsWord($currentcategory, $currentcontent, $queryarray, true)) {
                        // wenn noch nicht im Treffer-Array: hinzufuegen
                        if (!in_array($currentcontent, $matchingpages))
                            $matchingpages[] = $currentcontent;
                    }
                }

                // die gesammelten Seiten ausgeben
                if (count($matchingpages) > 0) {
                    $highlightparameter = implode(",", $queryarray);
                    $categoryname = catToName($currentcategory, false);
                    $searchresults .= "<h2>$categoryname</h2><ul>";
                    foreach ($matchingpages as $matchingpage) {
                        $url = "index.php?cat=".substr($currentcategory,3)."&amp;page=".substr($matchingpage, 3, strlen($matchingpage) - 7)."&amp;";
                        if($CMS_CONF->get("modrewrite") == "true") {
                            $url = $URL_BASE.substr($currentcategory,3)."/".substr($matchingpage, 3, strlen($matchingpage) - 7).".html?";
                        }
                        $pagename = pageToName($matchingpage, false);
                        $filepath = $CONTENT_DIR_REL.$currentcategory."/".$matchingpage;
                        $searchresults .= "<li>".
                            "<a href=\"".$url.
                            "highlight=".$specialchars->replaceSpecialChars($highlightparameter,false)."\"".
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
        // Rueckgabe des Menues
        return array($searchresults, $language->getLanguageValue0("message_search_0"), $language->getLanguageValue1("message_searchresult_1", (trim($QUERY_REQUEST))));
    }


// ------------------------------------------------------------------------------
// Inhaltsseite durchsuchen
// ------------------------------------------------------------------------------
    function pageContainsWord($cat, $page, $queryarray, $firstrecursion) {
        global $CONTENT_DIR_REL;
        global $specialchars;
        global $CHARSET;
        
        $filepath = $CONTENT_DIR_REL.$cat."/".$page;
        $ismatch = false;
        $content = "";
        
        // Dateiinhalt auslesen, wenn vorhanden...
        if (filesize($filepath) > 0) {
            $handle = fopen($filepath, "r");
            $content = fread($handle, filesize($filepath));
            fclose($handle);
            // Zuerst: includierte Seiten herausfinden!
            preg_match_all("/\[include\|([^\[\]]*)\]/Um", $content, $matches);
            // Fuer jeden Treffer...
            foreach ($matches[1] as $i => $match) {
                // ...Auswertung und Verarbeitung der Informationen
                $valuearray = explode(":", $matches[1][$i]);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $includedpage = nameToPage($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($matches[1][$i]),false), $cat);
                    // verhindern, daß in der includierten Seite includierte Seiten auch noch durchsucht werden
                    if ($firstrecursion) {
                        // includierte Seite durchsuchen!
                        if (pageContainsWord($cat, $includedpage, $queryarray,false)) {
                            return true;
                        }
                    }
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $includedpagescat = nameToCategory($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($valuearray[0]),false));
                    $includedpage = nameToPage($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($valuearray[1]),false), $includedpagescat);
                    // verhindern, daß in der includierten Seite includierte Seiten auch noch durchsucht werden
                    if ($firstrecursion) {
                        // includierte Seite durchsuchen!
                        if (pageContainsWord($includedpagescat, $includedpage, $queryarray, false)) {
                            return true;
                        }
                    }
                }
            }

            // alle horizontalen Linien ("[----]") von der Suche ausschließen
            $content = preg_replace("/\[----\]/U", " ", $content);
            # alle geschuetzten [] entfernen
            $content = str_replace(array("^[","^]")," ",$content);
            # alle html tags entfernen
            $content = strip_tags($content);
            $notexit = 0;
            # tmp damit wenn als erstes im string [ ist keine 0 zurueck kommt
             while((strpos("tmp".$content,'[') > 0)) {
                $start = strrpos($content,'[');
                $lengt = strpos($content,']',$start) - $start + 1; # 1 weil ] brauceh wir auch
                $syntax = substr($content,$start,$lengt);
                if(strpos(substr($syntax,1,-1),'[') == 0 and strpos($syntax,'=') == 0) {
                    $match = substr($syntax,strpos($syntax,'|') + 1,-1);
                    $content = str_replace($syntax,$match,$content);
                } 
                if(strpos(substr($syntax,1,-1),'[') == 0 and strpos($syntax,'=') > 0) {
                    $match = substr($syntax,strpos($syntax,'=') + 1,strpos($syntax,'|') - strpos($syntax,'=') - 1);
                    $content = str_replace($syntax,$match,$content);
                }
                $notexit++;
                if($notexit > 500) break;
            }
            // Auch Emoticons in Doppelpunkten (z.B. ":lach:") sollen nicht beruecksichtigt werden
            $content = preg_replace("/:[^\s]+:/U", " ", $content);
        }
        # nach alle Suchbegrieffe suchen
        foreach($queryarray as $query) {
            if ($query == "")
                continue;
            // Wenn...
            if (
                // ...der aktuelle Suchbegriff im Seitennamen...
                (substr_count(strtolower(pageToName($page, false)), strtolower($query)) > 0)
                // ...oder im eigentlichen Seiteninhalt vorkommt (ueberprueft werden nur Seiten, die nicht leer sind), ...
                || ((filesize($filepath) > 0) && (substr_count(strtolower($content), strtolower($specialchars->getHtmlEntityDecode($query))) > 0))
                ) {
                // ...dann setze das Treffer-Flag
                $ismatch = true;
                # und abbrechen da einer von den suchbegrieffen gefunden
                break;
            }
        }
        // Ergebnis zurueckgeben
        return $ismatch;
    }

// ------------------------------------------------------------------------------
// E-Mail-Adressen verschleiern
// ------------------------------------------------------------------------------
// Dank fuer spam-me-not.php an Rolf Offermanns!
// Spam-me-not in JavaScript: http://www.zapyon.de
    function obfuscateAdress($originalString, $mode) {
        // $mode == 1            dezimales ASCII
        // $mode == 2            hexadezimales ASCII
        // $mode == 3            zufaellig gemischt
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
#        $phrasestring = rawurldecode($phrasestring);
        $phrasearray = explode(",", $phrasestring);
        // jeden Begriff highlighten
        foreach($phrasearray as $phrase) {
            // Regex-Zeichen im zu highlightenden Text escapen (.\+*?[^]$(){}=!<>|:)
            $phrase = preg_quote($phrase);
            // Slashes im zu highlightenden Text escapen
            $phrase = preg_replace("/\//", "\\\\/", $phrase);
#            $phrase = htmlentities($phrase);
            //$content = preg_replace("/((<[^>]*|{CONTACT})|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $content);
            $content = preg_replace("/((<[^>]*|{CONTACT})|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $content); 
        }
        //
        return $content;
    }



// ------------------------------------------------------------------------------
// Rueckgabe des Website-Titels
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
// Platzhalter im uebergebenen String ersetzen
// ------------------------------------------------------------------------------
    function replacePlaceholders($content, $cattitle, $pagetitle) {
        global $CMS_CONF;
        global $specialchars;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;
        global $PAGE_FILE;
        global $EXT_PAGE;
        global $LAYOUT_DIR_URL;

        // Titel der Website
        $content = preg_replace('/{WEBSITE_NAME}/', $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true), $content);
        // Layout-Verzeichnis
        $content = preg_replace('/{LAYOUT_DIR}/', $LAYOUT_DIR_URL, $content);

        if ($CAT_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{CATEGORY}/', $CAT_REQUEST, $content);
            // "sauberer" Name der aktuellen Kategorie ("Muellers Kuh")
            if(strpos("tmp".$content,'{CATEGORY_NAME}') !== false)
                $content = preg_replace('/{CATEGORY_NAME}/', catToName($CAT_REQUEST, true), $content);
        }
        // Suche, Sitemap
        else {
            // "unbehandelter" Name der aktuellen Kategorie ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{CATEGORY}/', $cattitle, $content);
            // "sauberer" Name der aktuellen Kategorie ("Muellers Kuh")
            $content = preg_replace('/{CATEGORY_NAME}/', $cattitle, $content);
        }

        if ($PAGE_REQUEST != "") {
            // "unbehandelter" Name der aktuellen Inhaltsseite ("10_M%FCllers%20Kuh")
            $content = preg_replace('/{PAGE}/', $PAGE_REQUEST, $content);
            // Dateiname der aktuellen Inhaltsseite ("10_M%FCllers%20Kuh.txt")
            $content = preg_replace('/{PAGE_FILE}/', $PAGE_FILE, $content);
            // "sauberer" Name der aktuellen Inhaltsseite ("Muellers Kuh")
            if(strpos("tmp".$content,'{PAGE_NAME}') !== false)
                $content = preg_replace('/{PAGE_NAME}/', pageToName($PAGE_FILE, true), $content);
            
            if(strpos("tmp".$content,'{PREVIOUS_') !== false or strpos("tmp".$content,'{NEXT_') !== false) {
                $neighbourPages = getNeighbourPages($PAGE_REQUEST);
                // "unbehandelter" Name der vorigen Inhaltsseite ("00_Der%20M%FCller")
                $content = preg_replace('/{PREVIOUS_PAGE}/', substr($neighbourPages[0], 0, strlen($neighbourPages[0]) - 4), $content);
                // Dateiname der vorigen Inhaltsseite ("00_Der%20M%FCller.txt")
                $content = preg_replace('/{PREVIOUS_PAGE_FILE}/', $neighbourPages[0], $content);
                // "sauberer" Name der vorigen Inhaltsseite ("Der Mueller")
                if(strpos("tmp".$content,'{PREVIOUS_PAGE_NAME}') !== false)
                    $content = preg_replace('/{PREVIOUS_PAGE_NAME}/', pageToName($neighbourPages[0], true), $content);
                // "unbehandelter" Name der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle")
                $content = preg_replace('/{NEXT_PAGE}/', substr($neighbourPages[1], 0, strlen($neighbourPages[1]) - 4), $content);
                // Dateiname der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle.txt")
                $content = preg_replace('/{NEXT_PAGE_FILE}/', $neighbourPages[1], $content);
                // "sauberer" Name der naechsten Inhaltsseite ("Muellers Muehle")
                if(strpos("tmp".$content,'{NEXT_PAGE_NAME}') !== false)
                    $content = preg_replace('/{NEXT_PAGE_NAME}/', pageToName($neighbourPages[1], true), $content);
            }
        }
        // Suche, Sitemap
        else {
            // "unbehandelter" Name der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh")
            $content = preg_replace('/{PAGE}/', $pagetitle, $content);
            // Dateiname der aktuellen Inhaltsseite ("10_M-uuml-llers-nbsp-Kuh.txt")
            $content = preg_replace('/{PAGE_FILE}/', $pagetitle, $content);
            // "sauberer" Name der aktuellen Inhaltsseite ("Muellers Kuh")
            $content = preg_replace('/{PAGE_NAME}/', $pagetitle, $content);
        }
        // ...und zurueckgeben
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
// Gibt das Kontaktformular zurueck
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
        global $specialchars;
        
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
                $mailsubject = $language->getLanguageValue1("contactform_mailsubject_1", $specialchars->getHtmlEntityDecode($WEBSITE_NAME));
                // Wenn Mail-Adresse gesetzt ist: erhaelt der Absender eine copy
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
            $action_para = substr($PAGE_REQUEST,3).".html";
        }
        $form .= "<form accept-charset=\"$CHARSET\" method=\"post\" action=\"$action_para\" name=\"contact_form\" id=\"contact_form\">"
        ."<input type=\"hidden\" name=\"cat\" value=\"".substr($CAT_REQUEST,3)."\" />"
        ."<input type=\"hidden\" name=\"page\" value=\"".substr($PAGE_REQUEST,3)."\" />"
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
                ."<tr><td style=\"padding-right:10px;\">".$calculation_data[0]."*</td>"
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
// Hilfsfunktion: Prueft einen Requestparameter
// ------------------------------------------------------------------------------
    function getRequestParam($param, $clean) {
        global $URL_BASE;
        global $CMS_CONF;
        // Nullbytes abfangen!
        if (strpos($_SERVER['REQUEST_URI'], "\x00") > 0) {
            die();
        }

        if(($CMS_CONF->get("modrewrite") == "true") and ($param == "cat" or $param == "page")) {
            $request = NULL;
            # ein hack für alte links
            if (isset($_REQUEST[$param])) {
                return rawurldecode($_REQUEST[$param]);
            }
            if($param == "cat") {
                # ein tmp dafor weil wenn $URL_BASE = / ist werden alle / ersetzt durch nichts
                $url_get = str_replace("tmp".$URL_BASE,"","tmp".$_SERVER['REQUEST_URI']);
                $url_get = str_replace("?".$_SERVER['QUERY_STRING'],"",$url_get);
                $url_para = explode("/",$url_get);
                if(count($url_para) > 1) {
                    $request = $url_para[0];
                } else {
                    $request = substr($url_get,0,-5);
                }
            } elseif($param == "page") {
                # ein tmp dafor weil wenn $URL_BASE = / ist werden alle / ersetzt durch nichts
                $url_get = str_replace("tmp".$URL_BASE,"","tmp".$_SERVER['REQUEST_URI']);
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
// Rueckgabe der Dateinamen der vorigen und naechsten Seite
// ------------------------------------------------------------------------------
    function getNeighbourPages($page) {
        global $CONTENT_DIR_REL;
        global $CAT_REQUEST;
        global $CMS_CONF;
        global $EXT_LINK;
        
        // leer initialisieren
        $neighbourPages = array("", "");
        // aktuelle Kategorie einlesen
        $pagesarray = getDirContentAsArray($CONTENT_DIR_REL.$CAT_REQUEST, true, $CMS_CONF->get("showhiddenpagesincmsvariables") == "true");
        // Schleife ueber alle Seiten
        for ($i = 0; $i < count($pagesarray); $i++) {
            if(substr($pagesarray[$i], -(strlen($EXT_LINK))) == $EXT_LINK)
                continue;
            if ($page == substr($pagesarray[$i], 0, strlen($pagesarray[$i]) - 4)) {
                // vorige Seite (nur setzen, wenn aktuelle nicht die erste ist)
                if ($i > 0) {
                    $neighbourPages[0] = $pagesarray[$i-1];
                }
                // naechste Seite (nur setzen, wenn aktuelle nicht die letzte ist)
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
// Hilfsfunktion: Zufaellige Spamschutz-Rechenaufgabe und deren Ergebnis zurueckgeben
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
// Hilfsfunktion: Bestimmt die Inputnamen neu
// ------------------------------------------------------------------------------    
    function renameContactInputs() {
        $_SESSION['contactform_name'] = time()-rand(30, 40);
        $_SESSION['contactform_mail'] = time()-rand(10, 20);
        $_SESSION['contactform_website'] = time()-rand(0, 10);
        $_SESSION['contactform_message'] = time()-rand(40, 50);
        $_SESSION['contactform_calculation'] = time()-rand(50, 60);
    }

    # die geschweiften PluginPlatzhalter klammern ersetzen
    function replacePluginsCurly($matches,$content,$availableplugins) {
        foreach($matches[0] as $pos => $inhalt) {
            $plugin = explode("|", $matches[1][$pos]);
            $currentplugin = $matches[1][$pos];
            if (sizeof($plugin) > 1) {
                $currentplugin = $plugin[0];
            }
            # Platzhalter werden alle mit ersetzt ~platz-, -platzend~
            if(!in_array($currentplugin, $availableplugins)) {
                $string_search = $matches[0][$pos];
                $string_new = str_replace(array('{','}'),array('~platz-','-platzend~'),$matches[0][$pos]);
            # alle {PluginPlatzhalter} nicht Verschachtelt
            } elseif(strrpos($matches[0][$pos],'{',1) == 0) {
                $string_search = $matches[0][$pos];
                $string_new = str_replace(array('{','}'),array('~start-','-end~'),$string_search);
            # alle Verschachtelte {PluginPlatzhalter|variable{PluginPlatzhalter|variable}}
            } elseif(strrpos($matches[0][$pos],'{',1) > 0) {
                $string_search = substr($matches[0][$pos],strrpos($matches[0][$pos],'{',1));
                $string_new = str_replace(array('{','}'),array('~start_in-','-end_in~'),$string_search);
            }
            # die geschweiften PluginPlatzhalter klammern ersetzen
            $content = str_replace($string_search,$string_new,$content);
        }
        # noch mal suchen
        preg_match_all("/\{(.+)\}/Umsi", $content, $matches);
        # solange suchen bis keine mehr vorhanden
        if(count($matches[0]) > 0) {
            $content = replacePluginsCurly($matches,$content,$availableplugins);
        }
        return $content;
    }
// ------------------------------------------------------------------------------
// Hilfsfunktion: Plugin-Variablen ersetzen
// ------------------------------------------------------------------------------    
    function replacePluginVariables($content) {
        global $PLUGIN_DIR_REL;
        global $syntax;
        global $language;
        global $URL_BASE;
        global $PLUGIN_DIR_NAME;

        $availableplugins = array();
        $deactiv_plugins = array();
        // alle Plugins einlesen
        $dircontent = getDirContentAsArray($PLUGIN_DIR_REL, false, false);
        # Plugin Galerie gipts nicht manuel hinzufuegen
/*        if(!is_dir(getcwd()."/$PLUGIN_DIR/Galerie")) {
            $availableplugins[] = "Galerie";
        }*/
        foreach ($dircontent as $currentelement) {
            # alle Plugins suchen
            if (file_exists($PLUGIN_DIR_REL.$currentelement."/index.php")) {
                $availableplugins[] = $currentelement;
            }
            # wens die gallery.php ist gibts keine plugin.conf
/*            if($currentelement == "Galerie" and !is_dir(getcwd()."/$PLUGIN_DIR/".$currentelement)) {
                continue;
            }*/
            # nach schauen ob das Plugin active ist
            if(file_exists($PLUGIN_DIR_REL.$currentelement."/plugin.conf")) {
                $conf_plugin = new Properties($PLUGIN_DIR_REL.$currentelement."/plugin.conf",true);
                if($conf_plugin->get("active") == "false") {
                    # array fuehlen mit deactivierte Plugin Platzhalter
                    $deactiv_plugins[] = $currentelement;
                    unset($conf_plugin);
                }
            }
        }
        // Alle Variablen aus dem Inhalt heraussuchen
        preg_match_all("/\{(.+)\}/Umsi", $content, $matches);
        # Alle Platzhalter die keine Plugins sind ersetze {, } mit ~platz-, -platzend~
        # und jetzt noch die Verschachtelten und die {, } ersetzen mit ~start-, -end~,
        # inerhalb eines Plugins und ~start_in-, -end_in~
        $content = replacePluginsCurly($matches,$content,$availableplugins);

        $notexit = 0;
        // Fuer jeden Treffer...
        while ((strpos($content,'~start-') > 0)
                or (strpos($content,'~start_in-') > 0)
            ) {
            # alle PluginPlatzhalter die in einem Plugin sind zuerst ersetzen
            if(strpos($content,'~start_in-') > 0) {
                $match_start = strrpos($content,'~start_in-');
                $match_len = strpos($content,'-end_in~',$match_start) - $match_start + 8;
                $match = substr($content,$match_start,$match_len);
                $match_plugin = substr($match,10,strlen($match) - 18);
            # dann alle anderen
            } elseif(strpos($content,'~start-') > 0) {
                $match_start = strpos($content,'~start-');
                $match_len = strpos($content,'-end~',$match_start) - $match_start + 5;
                $match = substr($content,$match_start,$match_len);
                # bei PluginPlatzhalter die neben einander in einem Plugin stehen
                if(strpos($match,'~start-',7) > 0) {
                    $match = substr($match,strrpos($match,'~start-'));
                }
                $match_plugin = substr($match,7,strlen($match) - 12);
            }
            // ...erstmal schauen, ob ein Wert dabeisteht, z.B. {VARIABLE|wert}
#            $valuearray = explode("|", $match_plugin);
            if(substr($match_plugin,0,strpos($match_plugin,'|'))) {

                $currentvariable = substr($match_plugin,0,strpos($match_plugin,'|'));
                $currentvalue = substr($match_plugin,strpos($match_plugin,'|') + 1);
/*
            if (sizeof($valuearray) > 1) {
                $currentvariable = $valuearray[0];
                $currentvalue = $valuearray[1];*/
            // Sonst den Wert leer vorbelegen
            } else {
                $currentvariable = $match_plugin;
                $currentvalue = "";
            }
            // ...ueberpruefen, ob es eine zugehörige Plugin-PHP-Datei gibt
            if (in_array($currentvariable, $availableplugins)) {
                $replacement = "";
                # Plugin Galerie gibts nicht dann gallery.php benutzen
/*                if($currentvariable == "Galerie" and !is_dir(getcwd()."/$PLUGIN_DIR/".$currentvariable)) {
                    # Plugin-Code includieren aus der gallery.php
                    require_once(getcwd()."/gallery.php");
                } else {*/
                if (file_exists($PLUGIN_DIR_REL.$currentvariable."/index.php")) {
                    // Plugin-Code includieren
                    require_once($PLUGIN_DIR_REL.$currentvariable."/index.php");
                }
                // Enthaelt der Code eine Klasse mit dem Namen des Plugins?
                if (class_exists($currentvariable)) {
                    if(!in_array($currentvariable, $deactiv_plugins)) {
                        // Objekt instanziieren und Inhalt holen!
                        $currentpluginobject = new $currentvariable();
                        $replacement = $currentpluginobject->getPluginContent($currentvalue);
                    }
                } else {
                    $replacement = $syntax->createDeadlink($match, $language->getLanguageValue1("plugin_error_1", $currentvariable));
                }
                // Variable durch Plugin-Inhalt (oder Fehlermeldung) ersetzen
#                $content = preg_replace('/'.preg_quote($match, '/').'/Um', $replacement, $content);
                $content = str_replace($match,$replacement,$content);
                if (!in_array($currentvariable, $deactiv_plugins)
                    and file_exists($PLUGIN_DIR_REL.$currentvariable."/plugin.css")
                    and strpos($content,$URL_BASE.$PLUGIN_DIR_NAME.'/'.$currentvariable.'/plugin.css') < 1) {
                    $css = '<style type="text/css"> @import "'.$URL_BASE.$PLUGIN_DIR_NAME.'/'.$currentvariable.'/plugin.css"; </style></head>';
                    $content = str_replace(array("</head>","</HEAD>"),$css,$content);
                }
            }
            $notexit++;
            # nach spaetestens 500 durchlaeufe die while schleife verlassen nicht das das
            # zur endlosschleife wird
            if($notexit > 500) break;
        }
        # Platzhalter wieder herstellen
        $content = str_replace(array('~platz-','-platzend~'),array('{','}'),$content);
        # fals doch noch was uebrig geblieben sein solte
        $content = str_replace(array('~start_in-','-end_in~','~start-','-end~'),array('{','}','{','}'),$content);
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
        }
        if(strstr($link,"-_self-")) {
            $tmp_link = explode("-_self-",$link);
            $target = "_self";
        }
        if(substr($tmp_link[1], 0, 13) != "http%3A%2F%2F") {
            $tmp_link[1] = "http%3A%2F%2F".$tmp_link[1];
        }
        $titel = $syntax->getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1",$specialchars->rebuildSpecialChars($tmp_link[1], true, true)));
        return '<a href="'.$specialchars->rebuildSpecialChars(substr($tmp_link[1],0,-(strlen($EXT_LINK))), true, true).'"'.$css.' target="'.$target.'"'.$titel.'>'.$specialchars->rebuildSpecialChars(substr($tmp_link[0],3), true, true).'</a> ';
    }
    
?>