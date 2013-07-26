<?php
session_start();
$start_time = get_executTime(false);
/* 
echo "<pre style=\"position:fixed;background-color:#000;color:#0f0;padding:5px;font-family:monospace;border:2px solid #777;\">";
print_r($_REQUEST);
echo "</pre>"; 
*/

# bei winsystemen gibts nicht immer $_SERVER["SCRIPT_FILENAME"]
if(isset($_SERVER["SCRIPT_FILENAME"]))
    $BASE_DIR = $_SERVER["SCRIPT_FILENAME"];
else
    $BASE_DIR = __FILE__;
# fals da bei winsystemen \\ drin sind in \ wandeln
$BASE_DIR = str_replace("\\\\", "\\",$BASE_DIR);
# zum schluss noch den teil denn wir nicht brauchen abschneiden
$BASE_DIR = substr($BASE_DIR,0,-(strlen("index.php")));
define("BASE_DIR",$BASE_DIR);

$CMS_DIR_NAME = "cms";
define("CMS_DIR_NAME",$CMS_DIR_NAME);
$BASE_DIR_CMS = BASE_DIR.$CMS_DIR_NAME."/";
define("BASE_DIR_CMS",$BASE_DIR_CMS);
$tmp_getDirContentAsArray = NULL;

if(is_file(BASE_DIR_CMS."DefaultConf.php")) {
    require_once(BASE_DIR_CMS."DefaultConf.php");
} else {
    die("Fatal Error ".BASE_DIR_CMS."DefaultConf.php Datei existiert nicht");
}

# Um Cross-Site Scripting-Schwachstellen zu verhindern
$_SERVER["PHP_SELF"] = htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, CHARSET);
$_SERVER["REQUEST_URI"] = htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, CHARSET);
if(isset($_SERVER["SCRIPT_URL"]))
    $_SERVER["SCRIPT_URL"] = htmlspecialchars($_SERVER["SCRIPT_URL"], ENT_QUOTES, CHARSET);
if(isset($_SERVER["SCRIPT_URI"]))
    $_SERVER["SCRIPT_URI"] = htmlspecialchars($_SERVER["SCRIPT_URI"], ENT_QUOTES, CHARSET);

$_GET = cleanREQUEST($_GET);
$_REQUEST = cleanREQUEST($_REQUEST);
$_POST = cleanREQUEST($_POST);

    # ab php > 5.2.0 hat preg_* ein default pcre.backtrack_limit von 100000 zeichen
    # deshalb der versuch mit ini_set
    @ini_set('pcre.backtrack_limit', 1000000);

    require_once(BASE_DIR_CMS."SpecialChars.php");
    require_once(BASE_DIR_CMS."Properties.php");
    
    // Initial: Fehlerausgabe unterdruecken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
    @ini_set("display_errors", 0);

    $specialchars   = new SpecialChars();
    $CMS_CONF     = new Properties(BASE_DIR_CMS."conf/main.conf",true);
    $VERSION_CONF  = new Properties(BASE_DIR_CMS."conf/version.conf",true);
    $GALLERY_CONF  = new Properties(BASE_DIR_CMS."conf/gallery.conf",true);
    $USER_SYNTAX  = new Properties(BASE_DIR_CMS."conf/syntax.conf",true);
    $URL_BASE = substr($_SERVER['PHP_SELF'],0,strpos($_SERVER['PHP_SELF'],"index.php"));
    $CONTENT_DIR_REL        = BASE_DIR.CONTENT_DIR_NAME."/";
    $PLUGIN_DIR_REL         = BASE_DIR.PLUGIN_DIR_NAME."/";
    define("URL_BASE",$URL_BASE);
    define("CONTENT_DIR_REL",$CONTENT_DIR_REL);
    define("PLUGIN_DIR_REL",$PLUGIN_DIR_REL);

    require_once(BASE_DIR_CMS.'idna_convert.class.php');
    $Punycode = new idna_convert();

    require_once(BASE_DIR_CMS."Language.php");
    $language       = new Language();

    $activ_plugins = array();
    $deactiv_plugins = array();
    # Vorhandene Plugins finden und in array $activ_plugins und $deactiv_plugins einsetzen
    # wird für Search und Pluginplatzhaltern verwendet
    list($activ_plugins,$deactiv_plugins) = findPlugins();
    require_once(BASE_DIR_CMS."Syntax.php");
    require_once(BASE_DIR_CMS."Smileys.php");
    $syntax         = new Syntax();
    $smileys        = new Smileys(BASE_DIR_CMS."smileys");

    require_once(BASE_DIR_CMS."Plugin.php");

    $LAYOUT_DIR     = "layouts/".$CMS_CONF->get("cmslayout");
    $TEMPLATE_FILE  = $LAYOUT_DIR."/template.html";

    # wenn ein Plugin die gallerytemplate.html benutzten möchte
    # reicht es wenn in der URL galtemplate=??? enthalten ist ??? können Galerien sein
    if (getRequestParam("galtemplate", false)) {
        $TEMPLATE_FILE  = $LAYOUT_DIR."/gallerytemplate.html";
    }

    $LAYOUT_DIR_URL = $specialchars->replaceSpecialChars(URL_BASE.$LAYOUT_DIR,true);
    $CSS_FILE       = $LAYOUT_DIR_URL."/css/style.css";
    $FAVICON_FILE   = $LAYOUT_DIR_URL."/favicon.ico";

    $WEBSITE_NAME = $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true);
    if ($WEBSITE_NAME == "")
        $WEBSITE_NAME = "Titel der Website";

    $USE_CMS_SYNTAX = true;
    if ($CMS_CONF->get("usecmssyntax") == "false")
        $USE_CMS_SYNTAX = false;

    // Request-Parameter einlesen und dabei absichern
    $CAT_REQUEST_URL = $specialchars->replaceSpecialChars(getRequestParam('cat', false),false);
    $PAGE_REQUEST_URL = $specialchars->replaceSpecialChars(getRequestParam('page', false),false);
    $ACTION_REQUEST = getRequestParam('action', false);
    $QUERY_REQUEST = stripcslashes(getRequestParam('query', false));
    $HIGHLIGHT_REQUEST = getRequestParam('highlight', false);

    $HTML                   = "";

    require_once(BASE_DIR_CMS."CatPageClass.php");
    $CatPage         = new CatPageClass();

    # $CAT_REQUEST und $PAGE_REQUEST setzen und mit nichts füllen
    # wird von checkParameters() gefühlt
    $CAT_REQUEST = "";
    $PAGE_REQUEST = "";

    // Dateiname der aktuellen Inhaltsseite (wird in getContent() gesetzt)
    $PAGE_FILE = "";

    // Zuerst: Uebergebene Parameter ueberpruefen
    checkParameters();
    define("CAT_REQUEST",$CAT_REQUEST);
    define("PAGE_REQUEST",$PAGE_REQUEST);

    // Dann: HTML-Template einlesen und mit Inhalt fuellen
    readTemplate();
    # manche Provider sind auf iso eingestelt
    header('content-type: text/html; charset='.CHARSET.'');

    if(strpos($HTML,"<!--{MEMORYUSAGE}-->") > 1)
        $HTML = str_replace("<!--{MEMORYUSAGE}-->",get_memory(),$HTML);

    if(strpos($HTML,"<!--{EXECUTETIME}-->") > 1)
        $HTML = str_replace("<!--{EXECUTETIME}-->",get_executTime($start_time),$HTML);
    // Zum Schluß: Ausgabe des fertigen HTML-Dokuments
    echo $HTML;

    function get_executTime($start_time) {
        if(!function_exists('gettimeofday'))
            return NULL;
        list($usec, $sec) = explode(" ", microtime());
        if($start_time === false) {
            return ((float)$usec + (float)$sec);
        }
        return "Seite in ".sprintf("%.4f", (((float)$usec + (float)$sec) - $start_time))." Sek. erstelt";
    }

    function get_memory() {
        $size = memory_get_usage();
        if(function_exists('memory_get_peak_usage'))
            $size = memory_get_peak_usage();
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i].' Memory Benutzt';
    }

// ------------------------------------------------------------------------------
// Parameter auf Korrektheit pruefen
// ------------------------------------------------------------------------------
    function checkParameters() {
        global $ACTION_REQUEST;
        global $CMS_CONF;
        global $PAGE_REQUEST_URL;
        global $CAT_REQUEST_URL;
        global $CatPage;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;

        // Wenn ein Action-Parameter uebergeben wurde: keine aktiven Kat./Inhaltts. anzeigen
        # $CAT_REQUEST und $PAGE_REQUEST bleiben lehr
        if (($ACTION_REQUEST == "sitemap") || ($ACTION_REQUEST == "search")) {
            return;
        }


        # übergebene cat und page gibts
        if($CatPage->exists_CatPage($CAT_REQUEST_URL,false)
            and $CatPage->exists_CatPage($CAT_REQUEST_URL,$PAGE_REQUEST_URL)
            ) {
            $CAT_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST_URL,false);
            $PAGE_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST_URL,$PAGE_REQUEST_URL);
            return;
        # übergebene cat gibts aber page nicht cat hat aber pages
        } elseif($CatPage->exists_CatPage($CAT_REQUEST_URL,false)
            and $CatPage->get_FirstPageOfCat($CAT_REQUEST_URL)
            ) {
            $CAT_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST_URL,false);
            # erste page nehmen
            $PAGE_REQUEST = $CatPage->get_FirstPageOfCat($CAT_REQUEST);
            $PAGE_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST,$PAGE_REQUEST);
            return;
        }

        # so wir sind bishierher gekommen dann probieren wirs mit defaultcat
        # oder mit erster cat die page hat
        $DEFAULT_CATEGORY = $CAT_REQUEST_URL;
        # $CAT_REQUEST_URL ist lehr
        # oder $CAT_REQUEST_URL gibts nicht als cat
        # oder $CAT_REQUEST_URL hat keine pages
        # dann defaultcat aus conf holen
        if(empty($CAT_REQUEST_URL)
            or !$CatPage->exists_CatPage($CAT_REQUEST_URL,false)
            or !$CatPage->get_FirstPageOfCat($CAT_REQUEST_URL)
            ) {
            $DEFAULT_CATEGORY = $CMS_CONF->get("defaultcat");
        }
        # prüfen ob die $DEFAULT_CATEGORY existiert
        if($CatPage->exists_CatPage($DEFAULT_CATEGORY,false)) {
            # die erste page holen
            # und setze $CAT_REQUEST und $PAGE_REQUEST
            $CAT_REQUEST = $CatPage->get_CatPageWithPos($DEFAULT_CATEGORY,false);
            $PAGE_REQUEST = $CatPage->get_FirstPageOfCat($CAT_REQUEST);
            if($CatPage->exists_CatPage($CAT_REQUEST,$PAGE_REQUEST))
                $PAGE_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST,$PAGE_REQUEST);
            return;
        # defaultcat gibts nicht hol die erste cat die auch pages hat und setze sie
        } else {
            list($CAT_REQUEST,$PAGE_REQUEST) = $CatPage->get_FirstCatPage();
            if($CatPage->exists_CatPage($CAT_REQUEST,false))
                $CAT_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST,false);
            if($CatPage->exists_CatPage($CAT_REQUEST,$PAGE_REQUEST))
                $PAGE_REQUEST = $CatPage->get_CatPageWithPos($CAT_REQUEST,$PAGE_REQUEST);
            # $CAT_REQUEST und $PAGE_REQUEST sind gesetz
            return;
        }
    }


// ------------------------------------------------------------------------------
// HTML-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
    function readTemplate() {
        global $HTML;
        global $TEMPLATE_FILE;
        global $USE_CMS_SYNTAX;
        global $ACTION_REQUEST;
        global $HIGHLIGHT_REQUEST;
        global $language;
        global $syntax;
        global $CMS_CONF;
        global $smileys;
        global $passwordok;

    if (!$file = @fopen($TEMPLATE_FILE, "r"))
        die($language->getLanguageValue1("message_template_error_1", $TEMPLATE_FILE));
    $template = fread($file, filesize($TEMPLATE_FILE));
    fclose($file);

    $pagecontent = "";

    # ist nur true wenn Inhaltseite eingelesen wird
    $is_Page = false;
    if ($ACTION_REQUEST == "sitemap") {
        $pagecontent = getSiteMap();
    }
    elseif ($ACTION_REQUEST == "search") {
        require_once(BASE_DIR_CMS."Search.php");
        global $QUERY_REQUEST;
        $pagecontent = searchInPages();
    }
    // Inhalte aus Inhaltsseiten durch Passwort schuetzen
    else {
        // zunaechst Passwort als gesetzt und nicht eingegeben annehmen
        $passwordok = false;
        if (file_exists(BASE_DIR_CMS."conf/passwords.conf")) {
            $passwords = new Properties(BASE_DIR_CMS."conf/passwords.conf", true); // alle Passwörter laden
            if ($passwords->keyExists(CAT_REQUEST.'/'.PAGE_REQUEST)) { // nach Passwort fuer diese Seite suchen
                if (!isset($_POST) || ($_POST == array())) // sofern kein Passwort eingegeben, nach einem Fragen
                    $pagecontent = getPasswordForm();
                else {
                    if (md5(getRequestParam("password", false)) == $passwords->get(CAT_REQUEST.'/'.PAGE_REQUEST))
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
            # Inhaltseite wird eingelesen und $USE_CMS_SYNTAX wird benutzt
            if($USE_CMS_SYNTAX)
                $is_Page = true;
            $pagecontent = getContent();
        }
    }

    # wenn im Template keine Inhaltseite benutzt wird
    if(!strstr($template,"{CONTENT}"))
        $is_Page = false;

    $HTML = str_replace('{CONTENT}','---content~~~'.$pagecontent.'~~~content---',$template);
    $HTML = $syntax->convertContent($HTML, CAT_REQUEST, $is_Page);
    unset($pagecontent);

    // Smileys ersetzen
    if ($CMS_CONF->get("replaceemoticons") == "true") {
        $HTML = $smileys->replaceEmoticons($HTML);
    }

    // Gesuchte Phrasen hervorheben
    if ($HIGHLIGHT_REQUEST <> "") {
        require_once(BASE_DIR_CMS."Search.php");
        # wir suchen nur im content teil
        list($content_first,$content,$content_last) = $syntax->splitContent($HTML);
        $content = highlightSearch($content, $HIGHLIGHT_REQUEST);
        $HTML = $content_first.$content.$content_last;
        unset($content_first,$content,$content_last);
    }

#    $HTML = str_replace(array('&#123;','&#125;','&#91;','&#93;'),array('{','}','[',']'),$HTML);
    $HTML = str_replace(array('---content~~~','~~~content---'),"",$HTML);
    }

// ------------------------------------------------------------------------------
// Formular zur Passworteingabe anzeigen
// ------------------------------------------------------------------------------
    function getPasswordForm() {
        global $language;
        global $CMS_CONF;

        $url = "index.php?cat=".substr(CAT_REQUEST,3)."&amp;page=".substr(PAGE_REQUEST,3);
        if($CMS_CONF->get("modrewrite") == "true") {
            $url = URL_BASE.substr(CAT_REQUEST,3)."/".substr(PAGE_REQUEST,3).".html";
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

    # ACHTUNG nicht mehr benutzen siehe CatPage.php
#!!!!!!!!!! löschen?
    function nameToCategory($catname) {
        global $CatPage;
        if($CatPage->exists_CatPage($catname,false))
            return $CatPage->get_CatPageWithPos($catname,false);
        // Wenn kein Verzeichnis paßt: Leerstring zurueckgeben
        return "";
    }


// ------------------------------------------------------------------------------
// Kategorienamen aus komplettem Verzeichnisnamen einer Kategorie zurueckgeben
// 00_Alle-nbsp-K-uuml-he => Alle Kuehe
// ------------------------------------------------------------------------------

    # ACHTUNG nicht mehr benutzen siehe CatPage.php
#!!!!!!!!!! löschen?
    function catToName($cat, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Seitennamen aus komplettem Dateinamen einer Inhaltsseite zurueckgeben
// 00_M-uuml-llers-nbsp-Kuh.txt => Muellers Kuh
// ------------------------------------------------------------------------------

    # ACHTUNG nicht mehr benutzen siehe CatPage.php
#!!!!!!!!!! löschen?
   function pageToName($page, $rebuildnbsp) {
        global $specialchars;
        return $specialchars->rebuildSpecialChars(substr($page, 3, strlen($page) - 7), $rebuildnbsp, true);
    }


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getContent() {
        global $PAGE_FILE;
        global $ACTION_REQUEST;
        global $specialchars;
        global $CatPage;

        // Entwurf
        if (
                $ACTION_REQUEST == "draft"
                and $CatPage->get_Type(CAT_REQUEST,PAGE_REQUEST) == EXT_DRAFT
                and $CatPage->exists_CatPage(CAT_REQUEST,PAGE_REQUEST)
            ) {
            $PAGE_FILE = PAGE_REQUEST.EXT_DRAFT;
            return $CatPage->get_PageContent(CAT_REQUEST,PAGE_REQUEST);
        }
        // normale Inhaltsseite
        elseif ($CatPage->get_Type(CAT_REQUEST,PAGE_REQUEST) == EXT_PAGE
                and $CatPage->exists_CatPage(CAT_REQUEST,PAGE_REQUEST)) {
            $PAGE_FILE = PAGE_REQUEST.EXT_PAGE;
            return $CatPage->get_PageContent(CAT_REQUEST,PAGE_REQUEST);
        }
        // Versteckte Inhaltsseite
        elseif ($CatPage->get_Type(CAT_REQUEST,PAGE_REQUEST) == EXT_HIDDEN
                and $CatPage->exists_CatPage(CAT_REQUEST,PAGE_REQUEST)) {
            $PAGE_FILE = PAGE_REQUEST.EXT_HIDDEN;
            return $CatPage->get_PageContent(CAT_REQUEST,PAGE_REQUEST);
        }
        return "";
    }


// ------------------------------------------------------------------------------
// Auslesen des Content-Verzeichnisses unter Beruecksichtigung
// des auszuschließenden File-Verzeichnisses, Rueckgabe als Array
// ------------------------------------------------------------------------------

    # ACHTUNG nicht mehr benutzen siehe CatPage.php
    # oder getDirAsArray()
#!!!!!!!!!! löschen?
    function getDirContentAsArray($dir, $iscatdir, $showhidden, $showdraft = false) {
        global $tmp_getDirContentAsArray;

        $files_read = array();
        if(!isset($tmp_getDirContentAsArray[$dir])) {
            $currentdir = opendir($dir);
            while (false !== ($file = readdir($currentdir))) {
                if (
                    // ...und nicht $CONTENT_FILES_DIR_NAME
                    (($file <> CONTENT_FILES_DIR_NAME) || (!$iscatdir))
                    // nicht "." und ".."
                    && isValidDirOrFile($file)
                    ) {
                $files_read[] = $file;
                }
            }
            closedir($currentdir);
            $tmp_getDirContentAsArray[$dir] = $files_read;
        } else {
            $files_read = $tmp_getDirContentAsArray[$dir];
        }
        $files = array();
        // Einlesen des gesamten Content-Verzeichnisses außer dem
        // auszuschließenden Verzeichnis und den Elementen . und ..
        foreach ($files_read as $file) {
            if (
                // wenn Kategorieverzeichnis: Alle Dateien auslesen, die auf EXT_PAGE oder EXT_HIDDEN enden...
                (!$iscatdir)
                || (substr($file, strlen($file)-4, strlen($file)) == EXT_PAGE)
                || (substr($file, strlen($file)-4, strlen($file)) == EXT_LINK)
                || ($showhidden && (substr($file, strlen($file)-4, strlen($file)) == EXT_HIDDEN))
                || ($showdraft && (substr($file, strlen($file)-4, strlen($file)) == EXT_DRAFT))
                ) {
            $files[] = $file;
            }
        }
        sort($files);
        return $files;
    }


// ------------------------------------------------------------------------------
// Aufbau des Hauptmenues, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getMainMenu() {
        global $CMS_CONF;
        global $CatPage;

        $mainmenu = "<ul class=\"mainmenu\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = $CatPage->get_CatArray();
        // Jedes Element des Arrays ans Menue anhaengen
        foreach ($categoriesarray as $currentcategory) {
            $mainmenu .= '<li class="mainmenu">'
                .$CatPage->create_AutoLinkTag($currentcategory,false,"menu");
            if($CatPage->is_Activ($currentcategory,false)
                    and $CMS_CONF->get("usesubmenu") > 0) {
                $mainmenu .= getDetailMenu($currentcategory);
            } elseif(!$CatPage->is_Activ($currentcategory,false)
                    and $CMS_CONF->get("usesubmenu") == 2) {
                $mainmenu .= getDetailMenu($currentcategory);
            }
            $mainmenu .= "</li>";

        }
        // Rueckgabe des Menues
        return $mainmenu . "</ul>";
    }


// ------------------------------------------------------------------------------
// Aufbau des Detailmenues, Rueckgabe als String
// ------------------------------------------------------------------------------
    function getDetailMenu($cat) {
        global $ACTION_REQUEST;
        global $QUERY_REQUEST;
        global $language;
        global $specialchars;
        global $CMS_CONF;
        global $CatPage;

        if ($CMS_CONF->get("usesubmenu") > 0)
            $cssprefix = "submenu";
        else
            $cssprefix = "detailmenu";

        $detailmenu = "<ul class=\"detailmenu\">";
        // Sitemap
        if (($ACTION_REQUEST == "sitemap") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= '<li class="detailmenu">'
                .$CatPage->create_LinkTag($CatPage->get_Href(false,false,"action=sitemap")
                    ,$language->getLanguageValue0("message_sitemap_0")
                    ,$cssprefix."active"
                    ,false)
                .'</li>';
        // Suchergebnis
        elseif (($ACTION_REQUEST == "search") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= '<li class="detailmenu">'
                .$CatPage->create_LinkTag($CatPage->get_Href(false,false,"action=search&amp;query=".$specialchars->replaceSpecialChars($QUERY_REQUEST, false))
                    ,$language->getLanguageValue1("message_searchresult_1", $specialchars->getHtmlEntityDecode($QUERY_REQUEST))
                    ,$cssprefix."active"
                    ,false)
                .'</li>';
        // Entwurfsansicht
        elseif (($ACTION_REQUEST == "draft") && ($CMS_CONF->get("usesubmenu") == 0))
            $detailmenu .= '<li class="detailmenu">'
                .$CatPage->create_LinkTag($CatPage->get_Href($cat,PAGE_REQUEST,"action=draft")
                    ,$CatPage->get_HrefText($cat,PAGE_REQUEST)." (".$language->getLanguageValue0("message_draft_0").")"
                    ,$cssprefix."active"
                    ,false)
                .'</li>';
        // "ganz normales" Detailmenue einer Kategorie
        else {
            // Content-Verzeichnis der aktuellen Kategorie einlesen
            $contentarray = $CatPage->get_PageArray($cat);
            # wenn keine Inhaltseiten lehr zurück
            if(count($contentarray) == 0)
                return NULL;
            // Jedes Element des Arrays ans Menue anhaengen
            foreach ($contentarray as $currentcontent) {
                $detailmenu .= '<li class="detailmenu">'
                    .$CatPage->create_AutoLinkTag($cat,$currentcontent,$cssprefix)
                    ."</li>";
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
        global $LAYOUT_DIR_URL;

        $modrewrite_dumy = NULL;
        if($CMS_CONF->get("modrewrite") == "true") {
            $modrewrite_dumy = ".html";
        }
        $form = "<form accept-charset=\"".CHARSET."\" method=\"get\" action=\"index.php$modrewrite_dumy\" class=\"searchform\"><fieldset id=\"searchfieldset\">"
        ."<input type=\"hidden\" name=\"action\" value=\"search\" />"
        ."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" />"
        ."<input type=\"image\" name=\"action\" value=\"search\" src=\"".$LAYOUT_DIR_URL."/grafiken/searchicon.gif\" alt=\"".$language->getLanguageValue0("message_search_0")."\" class=\"searchbutton\"".getTitleAttribute($language->getLanguageValue0("message_search_0"))." />"
        ."</fieldset></form>";
        return $form;
    }


// ------------------------------------------------------------------------------
// Erzeugung einer Sitemap
// ------------------------------------------------------------------------------
    function getSiteMap() {
        global $language;
        global $CMS_CONF;
        global $CatPage;

        $include_pages = array(EXT_PAGE);
        if($CMS_CONF->get("showhiddenpagesinsitemap") == "true") {
            $include_pages = array(EXT_PAGE,EXT_HIDDEN);
        }

        $sitemap = "<h1>".$language->getLanguageValue0("message_sitemap_0")."</h1>"
        ."<div class=\"sitemap\">";
        // Kategorien-Verzeichnis einlesen
        $categoriesarray = $CatPage->get_CatArray(false, false, $include_pages);
        // Jedes Element des Arrays an die Sitemap anhaengen
        foreach ($categoriesarray as $currentcategory) {
            $sitemap .= "<h2>".$CatPage->get_HrefText($currentcategory,false)."</h2><ul>";
            // Inhaltsseiten-Verzeichnis einlesen
            $contentarray = $CatPage->get_PageArray($currentcategory,$include_pages,true);
            // Alle Inhaltsseiten der aktuellen Kategorie auflisten...
            // Jedes Element des Arrays an die Sitemap anhaengen
            foreach ($contentarray as $currentcontent) {
                $url = $CatPage->get_Href($currentcategory,$currentcontent);
                $urltext = $CatPage->get_HrefText($currentcategory,$currentcontent);
                $titel = $language->getLanguageValue2("tooltip_link_page_2", $CatPage->get_HrefText($currentcategory,$currentcontent), $CatPage->get_HrefText($currentcategory,false));

                $sitemap .= "<li>".$CatPage->create_LinkTag($url,$urltext,false,$titel)."</li>";
            }
            $sitemap .= "</ul>";
        }
        $sitemap .= "</div>";
        // Rueckgabe der Sitemap
        return $sitemap;
    }

// ------------------------------------------------------------------------------
// E-Mail-Adressen verschleiern
// ------------------------------------------------------------------------------
// Dank fuer spam-me-not.php an Rolf Offermanns!
// Spam-me-not in JavaScript: http://www.zapyon.de
# Achtung muss url encoded sein
    function obfuscateAdress($originalString, $mode) {
        // $mode == 1            dezimales ASCII
        // $mode == 2            hexadezimales ASCII
        // $mode == 3            zufaellig gemischt
        $encodedString = "";
        $nowCodeString = "";

        $originalLength = strlen($originalString);
        $encodeMode = $mode;

        for ( $i = 0; $i < $originalLength; $i++) {
            if($originalString[$i] == "%") {
                $encodedString .= $originalString[$i].$originalString[$i+1].$originalString[$i+2];
                $i = $i + 2;
                continue;
            }
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
// Rueckgabe des Website-Titels
// ------------------------------------------------------------------------------

    function getWebsiteTitle() {
        global $CMS_CONF;
        global $specialchars;
        global $ACTION_REQUEST;

        $websitetitle = $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true);
        if ($ACTION_REQUEST == "sitemap") {
            global $language;
            $cat    = $language->getLanguageValue0("message_sitemap_0");
            $page   = $language->getLanguageValue0("message_sitemap_0");
        } elseif ($ACTION_REQUEST == "search") {
            global $QUERY_REQUEST;
            global $language;
            $cat    = $language->getLanguageValue0("message_search_0");
            $page   = $language->getLanguageValue1("message_searchresult_1", (trim($QUERY_REQUEST)));
        } else {
            global $CatPage;
            $cat = $CatPage->get_HrefText(CAT_REQUEST,false);
            $page = $CatPage->get_HrefText(CAT_REQUEST,PAGE_REQUEST);
        }
        global $passwordok;
        if($passwordok === false) {
            global $language;
            $page   = $language->getLanguageValue0("passwordform_title_0");
        }
        $title = $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarformat"),true,true);
        if($CMS_CONF->get("hidecatnamedpages") == "true"
                and $cat == $page
                and strstr($title,'{CATEGORY}') and strstr($title,'{PAGE}')) {
            $title = str_replace(array('{SEP}{PAGE}','{PAGE}{SEP}'),'',$title);

        }
        $sep = $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarseparator"),true,true);
        $title = str_replace(array('{WEBSITE}','{CATEGORY}','{PAGE}','{SEP}'),
                            array($websitetitle,$cat,$page,$sep), $title);
        return $title;
    }

// ------------------------------------------------------------------------------
// Anzeige der Informationen zum System
// ------------------------------------------------------------------------------
    function getCmsInfo() {
        global $CMS_CONF;
        global $language;
        global $VERSION_CONF;
        return "<a href=\"http://mozilo.de/\" target=\"_blank\" id=\"cmsinfolink\"".getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1", "http://mozilo.de")).">moziloCMS ".$VERSION_CONF->get("cmsversion")."</a>";
    }


// ------------------------------------------------------------------------------
// Hilfsfunktion: Sichert einen Input-Wert
// ------------------------------------------------------------------------------
    function cleanInput($input) {
        if (function_exists("mb_convert_encoding")) {
            $input = @mb_convert_encoding($input, CHARSET);
        }
        return $input;
    }

// ------------------------------------------------------------------------------    
// Alte Url wandeln
// ------------------------------------------------------------------------------
    function rebuildOldSpecialChars($oldurl) {
        global $specialchars;
        global $CMS_CONF;

        # wenn die numeriung im cat page ist weg damit
        if(preg_match("/\d\d_/", substr($oldurl,0,3)))
            $oldurl = substr($oldurl,3);
        # wenn keine alte -????~ sachen im cat page sind gleich raus hier
        if(!preg_match("/-\D+~/", $oldurl))
            return rawurldecode($oldurl);
        // Leerzeichen
        $oldurl = str_replace("-nbsp~", " ", $oldurl);
        // @, ?
        $oldurl = str_replace("-at~", "@", $oldurl);
        $oldurl = str_replace("-ques~", "?", $oldurl);
        // Alle mozilo-Entities in HTML-Entities umwandeln!
        $oldurl = preg_replace("/-([^-~]+)~/U", "&$1;", $oldurl);
        $oldurl = rawurldecode($specialchars->getHtmlEntityDecode($oldurl));
        return $oldurl;
    }
// ------------------------------------------------------------------------------
// Hilfsfunktion: Prueft einen Requestparameter
// ------------------------------------------------------------------------------
    function getRequestParam($param, $clean) {
        global $CMS_CONF;

        # wenn in der url z.B. cat[]=Kategorie übergeben wurde
        if(isset($_REQUEST[$param]) and is_array($_REQUEST[$param]))
            return NULL;

        # auf Alte Url testen und gewandelt zurück geben
        if((isset($_REQUEST[$param])) and ($param == "cat" or $param == "page"))
            $_REQUEST[$param] = rebuildOldSpecialChars($_REQUEST[$param]);

        if(($CMS_CONF->get("modrewrite") == "true") and ($param == "cat" or $param == "page")) {
            $request = NULL;
            # ein hack für alte links
            if (isset($_REQUEST[$param])) {
                return $_REQUEST[$param];
            }

            # ein tmp dafor weil wenn URL_BASE = / ist werden alle / ersetzt durch nichts
            $url_get = str_replace("tmp".URL_BASE,"","tmp".$_SERVER['REQUEST_URI']);
            $url_get = str_replace("&amp;","&",$url_get);
            $QUERY_STRING = str_replace("&amp;","&",$_SERVER['QUERY_STRING']);
            $url_get = str_replace("?".$QUERY_STRING,"",$url_get);
            if($param == "cat") {
                $url_para = explode("/",$url_get);
                if(count($url_para) > 1) {
                    $request = $url_para[0];
                } else {
#echo $url_get."<br />\n";
#                    if($url_get != "index.php")
                    $request = substr($url_get,0,-5);
                }
            } elseif($param == "page") {
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


    function findPlugins() {
        $activ_plugins = array();
        $deactiv_plugins = array();
        // alle Plugins einlesen
        $dircontent = getDirAsArray(PLUGIN_DIR_REL,"dir");
        foreach ($dircontent as $currentelement) {
            # nach schauen ob das Plugin active ist
            if(file_exists(PLUGIN_DIR_REL.$currentelement."/plugin.conf")
                and file_exists(PLUGIN_DIR_REL.$currentelement."/index.php")) {
                $conf_plugin = new Properties(PLUGIN_DIR_REL.$currentelement."/plugin.conf",true);
                if($conf_plugin->get("active") == "false") {
                    # array fuehlen mit deactivierte Plugin Platzhalter
                    $deactiv_plugins[] = $currentelement;
                } elseif($conf_plugin->get("active") == "true") {
                    $activ_plugins[] = $currentelement;
                }
                unset($conf_plugin);
            }
        }
        return array($activ_plugins,$deactiv_plugins);
    }

#!!!!!! gibts nur noch in Plugins ist durch CatPage abgelöst
    function menuLink($link,$css) {
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

        $tmp_link[1] = substr($tmp_link[1],0,-(strlen(EXT_LINK)));
        $titel = $syntax->getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1",$specialchars->rebuildSpecialChars($tmp_link[1], true, true)));
        return '<a href="'.$specialchars->rebuildSpecialChars($tmp_link[1], true, true).'"'.$css.' target="'.$target.'"'.$titel.'>'.$specialchars->rebuildSpecialChars(substr($tmp_link[0],3), true, true).'</a> ';
    }


?>