<?php

/*
 *
 * $Revision: 198 $
 * $LastChangedDate: 2009-05-13 20:09:42 +0200 (Mi, 13 Mai 2009) $
 * $Author: arvid $
 *
 */


// DEEEEEEEEEEEBUG ;)
// Ausgabe aller übergebenen Werte zu Testzwecken

/*
 echo "<h2>POST</h2>";
 foreach ($_POST as $a => $b)
 echo $a." -> ".$b."<br />";
 echo "<h2>GET</h2>";
 foreach ($_GET as $a => $b)
 echo $a." -> ".$b."<br />";
 echo "<h2>REQUEST</h2>";
 foreach ($_REQUEST as $a => $b)
 echo $a." -> ".$b."<br />";
 */

$ADMIN_TITLE = "moziloAdmin";

// Login überprüfen
 session_start();
 if (!$_SESSION['login_okay']) {
    header("location:login.php?logout=true");
    die("");
 }
 
 // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
 @ini_set("display_errors", 0);

 // Session Fixation durch Vergabe einer neuen Session-ID beim ersten Login verhindern
 if (!isset($_SESSION['PHPSESSID'])) {
 session_regenerate_id(true);
 $_SESSION['PHPSESSID'] = true;
 }

 require_once("filesystem.php");
 require_once("string.php");
 require_once("../Smileys.php");
 require_once("../SpecialChars.php");
 require_once("../Mail.php");

 $ADMIN_CONF    = new Properties("conf/basic.conf");
 $CMS_CONF    = new Properties("../conf/main.conf");
 $DOWNLOAD_COUNTS = new Properties("../conf/downloads.conf");
 $LOGINCONF = new Properties("conf/logindata.conf");
 $MAILFUNCTIONS = new Mail(true);
 $USER_SYNTAX_FILE = "../conf/syntax.conf";
 $USER_SYNTAX = new Properties($USER_SYNTAX_FILE);
 $CONTACT_CONF = new Properties("../formular/formular.conf");

 // Abwärtskompatibilität: Downloadcounter initalisieren
 if ($DOWNLOAD_COUNTS->get("_downloadcounterstarttime") == "")
 $DOWNLOAD_COUNTS->set("_downloadcounterstarttime", time());

// Pfade
$CONTENT_DIR_NAME        = "kategorien";
$CONTENT_DIR_REL        = "../".$CONTENT_DIR_NAME;
$GALLERIES_DIR_NAME    = "galerien";
$GALLERIES_DIR_REL    = "../".$GALLERIES_DIR_NAME;
$PREVIEW_DIR_NAME        = "vorschau";

// RegEx für erlaubte Zeichen in Inhaltsseiten, Kategorien, Dateien und Galerien 
$specialchars = new SpecialChars();
$ALLOWED_SPECIALCHARS_REGEX = $specialchars->getSpecialCharsRegex();

// Dateiendungen für Inhaltsseiten 
$EXT_PAGE     = ".txt";
$EXT_HIDDEN     = ".hid";
$EXT_DRAFT     = ".tmp";


// Aktion abhängig vom action-Parameter 
if (isset($_REQUEST['action']))
$action = $_REQUEST['action'];
else
$action = "";

$functionreturn = array();

// Kategorien
if ($action=="displaysysinfo")
$functionreturn = sysInfo();
elseif ($action=="category")
$functionreturn = category();
elseif ($action=="newcategory")
$functionreturn = newCategory();
elseif ($action=="editcategory")
$functionreturn = editCategory();
elseif ($action=="deletecategory")
$functionreturn = deleteCategory();
// Inhaltsseiten
elseif ($action=="site")
$functionreturn = site();
elseif ($action=="newsite")
$functionreturn = newSite();
elseif ($action=="editsite")
$functionreturn = editSite();
elseif ($action=="deletesite")
$functionreturn = deleteSite();
elseif ($action=="copymovesite")
$functionreturn = copymoveSite();
// Dateien
elseif ($action=="file")
$functionreturn = files();
elseif ($action=="newfile")
$functionreturn = newFile();
elseif ($action=="aboutfile")
$functionreturn = aboutFile();
elseif ($action=="deletefile")
$functionreturn = deleteFile();
// Galerien
elseif ($action=="gallery")
$functionreturn = gallery();
elseif ($action=="newgallery")
$functionreturn = newGallery();
elseif ($action=="editgallery")
$functionreturn = editGallery();
elseif ($action=="deletegallery")
$functionreturn = deleteGallery();
// Einstellungen
elseif ($action=="config")
$functionreturn = config();
elseif ($action=="displaycmsconfig")
$functionreturn = configCmsDisplay();
elseif ($action=="displayadminconfig")
$functionreturn = configAdminDisplay();
elseif ($action=="loginadminconfig")
$functionreturn = configAdminLogin();
// Beim ersten Login: Wichtigste Initialeinstellungen anzeigen
elseif ($action=="initialsetup")
$functionreturn = initialSetup();
// Bei unbekanntem oder leerem action-Parameter: Startseite
else
$functionreturn = home();

$pagetitle = $functionreturn[0];
$pagecontent = $functionreturn[1];


// Aufbau der gesamten Seite 
$html = "<!doctype html public \"-//W3C//DTD HTML 4.01 Transitional//EN\">";
$html .= "<html>";
$html .= "<head>";
$html .= "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=iso-8859-1\">";
$html .= "<script src=\"buttons.js\" type=\"text/javascript\"></script>";
$html .= "<script src=\"multifileupload.js\" type=\"text/javascript\"></script>";
$html .= "<title>$ADMIN_TITLE - $pagetitle</title>";
$html .= "<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\">";
$html .= "<link rel=\"stylesheet\" href=\"js_color_picker_v2/js_color_picker_v2.css\" media=\"screen\" type=\"text/css\">";
$html .= "<script type=\"text/javascript\" src=\"js_color_picker_v2/color_functions.js\"></script>";
$html .= "<script type=\"text/javascript\" src=\"js_color_picker_v2/js_color_picker_v2.js\"></script>";
$html .= "</head>";
$html .= "<body onload=\"htmlOverlopen(document.documentElement,0)\">";
$html .= "<script src=\"crossTooltips.js\" type=\"text/javascript\"></script>";
$html .= "<div id=\"mozilo_Logo\"></div>";
$html .= "<div id=\"main_div\">";
// Titelleiste
$html .= "<div id=\"design_Title\">";
$html .= "<a href=\"login.php?logout=true\" accesskey=\"x\"></a>";
$html .= "<div id=\"design_Titletext\">$ADMIN_TITLE - $pagetitle</div>";
$html .= "<a href=\"login.php?logout=true\" accesskey=\"".createNormalTooltip("button_home_logout", "button_home_logout_tooltip", 150)."\"><span id=\"design_Logout\"></span></a>";
$html .= "</div>";
// Titelleiste Ende
$html .= "<div id=\"navi_left\">";

/* Menü */

// Menüpunkt "Home"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=home\" accesskey=\"".createNormalTooltip("button_home", "button_home_tooltip", 150)."\"><span id=\"navi_btn_home\">".getLanguageValue("button_home")."</span></a> ";
//Menüpunkt "Kategorien"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=category\" accesskey=\"".createNormalTooltip("button_category", "button_category_tooltip", 150)."\"><span id=\"navi_btn_category\">".getLanguageValue("button_category")."</span></a> ";
// Menüpunkt "Seiten"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=site\" accesskey=\"".createNormalTooltip("button_site", "button_site_tooltip", 150)."\"><span id=\"navi_btn_site\">".getLanguageValue("button_site")."</span></a> ";
// Menüpunkt "Dateien"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=file\" accesskey=\"".createNormalTooltip("button_data", "button_data_tooltip", 150)."\"><span id=\"navi_btn_upload\">".getLanguageValue("button_data")."</span></a> ";
// Menüpunkt "Galerie"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=gallery\" accesskey=\"".createNormalTooltip("button_gallery", "button_gallery_tooltip", 150)."\"><span id=\"navi_btn_gallery\">".getLanguageValue("button_gallery")."</span></a> ";
// Menüpunkt "Konfiguration"
$html .= "<a class=\"leftmenu\" href=\"index.php?action=config\" accesskey=\"".createNormalTooltip("button_config", "button_config_tooltip", 150)."\"><span id=\"navi_btn_help\">".getLanguageValue("button_config")."</span></a> ";

/* Unterkategorien */
/* Home */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=displaysysinfo\" accesskey=\"".createNormalTooltip("button_home_sysinfo", "button_home_sysinfo_tooltip", 150)."\"><span id=\"home_sysinfo\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"login.php?logout=true\" accesskey=\"".createNormalTooltip("button_home_logout", "button_home_logout_tooltip", 150)."\"><span id=\"home_logout\"></span></a>";

/* Categories */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=newcategory\" accesskey=\"".createNormalTooltip("button_category_new", "", 150)."\"><span id=\"kategorie_new\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=editcategory\" accesskey=\"".createNormalTooltip("button_category_edit", "", 150)."\"><span id=\"kategorie_edit\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletecategory\" accesskey=\"".createNormalTooltip("button_category_delete", "", 150)."\"><span id=\"kategorie_delete\"> </span></a>";

/* Sites */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=newsite\" accesskey=\"".createNormalTooltip("button_site_new", "", 150)."\"><span id=\"site_new\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=editsite\" accesskey=\"".createNormalTooltip("button_site_edit", "", 150)."\"><span id=\"site_edit\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletesite\" accesskey=\"".createNormalTooltip("button_site_delete", "", 150)."\"><span id=\"site_delete\"> </span></a>";

/* Files */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=newfile\" accesskey=\"".createNormalTooltip("button_data_new", "", 150)."\"><span id=\"upload_new\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=aboutfile\" accesskey=\"".createNormalTooltip("button_data_info", "", 150)."\"><span id=\"upload_info\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletefile\" accesskey=\"".createNormalTooltip("button_data_delete", "", 150)."\"><span id=\"upload_delete\"> </span></a>";

/* Galleries */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=newgallery\" accesskey=\"".createNormalTooltip("button_gallery_new", "", 150)."\"><span id=\"gallery_new\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=editgallery\" accesskey=\"".createNormalTooltip("button_gallery_edit", "", 150)."\"><span id=\"gallery_edit\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletegallery\" accesskey=\"".createNormalTooltip("button_gallery_delete", "", 150)."\"><span id=\"gallery_delete\"> </span></a>";

/* Config */
$html .= "<a class=\"leftmenu\" href=\"index.php?action=displaycmsconfig\" accesskey=\"".createNormalTooltip("button_config_cms", "", 150)."\"><span id=\"config_cms\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=displayadminconfig\" accesskey=\"".createNormalTooltip("button_config_admin", "", 150)."\"><span id=\"config_admin\"> </span></a>";
$html .= "<a class=\"leftmenu\" href=\"index.php?action=loginadminconfig\" accesskey=\"".createNormalTooltip("button_config_pw", "", 150)."\"><span id=\"config_login\"> </span></a>";

$html .= "</div>";

/* Seiteninhalt */
$html .= "<div id=\"div_content\">";

// Warnung, wenn noch das Initialpaßwort verwendet wird (nicht zeigen, wenn gerade die Login-Config oder das initiale Setup angezeigt werden)
if (($LOGINCONF->get("initialpw") == "true") && ($action <> "loginadminconfig") && ($action <> "initialsetup")) {
    $html .= returnMessage(false, getLanguageValue("warning_initial_pw"));
}

// Warnung, wenn seit dem letzten Login Logins fehlgeschlagen sind
if ($LOGINCONF->get("falselogincount") > 0) {
    $html .= returnMessage(false, getLanguageValue("warning_false_logins")." ".$LOGINCONF->get("falselogincount"));
    // Gesamt-Counter für falsche Logins zurücksetzen
    $LOGINCONF->set("falselogincount", 0);
}

// Warnung, wenn die letzte Backupwarnung mehr als $intervallsetting Tage her ist
$intervallsetting = $ADMIN_CONF->get("backupmsgintervall");
if (($intervallsetting != "") && preg_match("/^[0-9]+$/", $intervallsetting) && ($intervallsetting > 0)) {
    $intervallinseconds = 60 * 60 * 24 * $intervallsetting;
    $lastbackup = getLastBackup();
    // initial: nur setzen
    if ($lastbackup == "") {
        setLastBackup();
    }
    // wenn schon gesetzt: prüfen und ggfs. warnen
    else {
        $nextbackup = $lastbackup + $intervallinseconds;
        if($nextbackup <= time())    {
            $html .= returnMessage(false, getLanguageValue("reminder_backup"));
            setLastBackup();
        }
    }
}
$html .= $pagecontent;
$html .= "</div>";


$html .= "</div>";
$html .= "</body>";
$html .= "</html>";



/* Ausgabe der kompletten Seite */
echo $html;


/*     ------------------------------
 Zusätzliche Funktionen
 ------------------------------ */

function home() {
    global $LOGINCONF;

    $pagecontent = "<h2>".getLanguageValue("button_home")."</h2>";

    // Initialeinstellungen noch nicht gemacht? > Anzeigen!
    if ($LOGINCONF->get("initialsetup") == "true") {
        header("location:index.php?action=initialsetup");
    }
    elseif (isset($_REQUEST['initialsetupdone']) && ($_REQUEST['initialsetupdone'] == "true")) {
        $pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
    }
    $pagecontent .= "<p>";
    $pagecontent .= getLanguageValue("welcome_text");
    $pagecontent .= "</p>";
    return array(getLanguageValue("button_home"), $pagecontent);
}

function initialSetup() {
    global $ADMIN_CONF;
    global $CMS_CONF;
    global $LOGINCONF;
    global $MAILFUNCTIONS;

    // Nur einmal anzeigen!
    $LOGINCONF->set("initialsetup", "false");

    $pagecontent = "<h2>".getLanguageValue("button_initialsetup")."</h2>";

    if (isset($_REQUEST['apply']) && ($_REQUEST['apply'] == "true")) {
        if (
        isValidRequestParameter("websitetitle", 2)
        /*
         && isValidRequestParameter("cmslang", 2)
         && isValidRequestParameter("adminlang", 2)
         */
        && isValidRequestParameter("adminmail", 3)
        && isValidRequestParameter("loginname", 5)
        && isValidRequestParameter("loginpw", 4)
        && ($_REQUEST['loginpwrepeat'] == $_REQUEST['loginpw'])
        ) {
            $CMS_CONF->set("websitetitle", htmlentities(stripslashes($_REQUEST['websitetitle'])));
            /*
             $CMS_CONF->set("cmslanguage", $_REQUEST['cmslang']);
             $ADMIN_CONF->set("language", $_REQUEST['adminlang']);
             */
            $ADMIN_CONF->set("adminmail", $_REQUEST['adminmail']);
            $LOGINCONF->set("name", $_REQUEST['loginname']);
                
            require_once("Crypt.php");
            $pwcrypt = new Crypt();
            $LOGINCONF->set("pw", $pwcrypt->encrypt($_REQUEST['loginpw']));
            $LOGINCONF->set("initialpw", "false");

            // zur Startseite
            header("location:index.php?initialsetupdone=true");
        }
        else {
            $pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
        }
    }

    $pagecontent .= "<p>";
    $pagecontent .= getLanguageValue("initialsetup_text");
    $pagecontent .= "</p>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"initialsetup\"><input type=\"hidden\" name=\"apply\" value=\"true\">";

    $pagecontent .= "<table class=\"data\">";
    // Zeile "WEBSITE-TITEL"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitetitle_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"websitetitle\" value=\"".$CMS_CONF->get("websitetitle")."\" /></td>";
    $pagecontent .= "</tr>";
    /*
     // Zeile "SPRACHAUSWAHL CMS"
     $pagecontent .= "<tr>";
     $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("cmslanguage_text")."</td>";
     $pagecontent .= "<td class=\"config_row2\"><select name=\"cmslang\" class=\"maxwidth\">";
     if ($handle = opendir('../sprachen')){
        while ($file = readdir($handle)) {
        $selected = "";
        if (isValidDirOrFile($file)) {
        if (substr($file,0,strlen($file)-strlen(".conf")) == $CMS_CONF->get("cmslanguage"))
        $selected = " selected";
        $pagecontent .= "<option".$selected." value=\"".substr($file,0,strlen($file)-strlen(".conf"))."\">";
        // Übersetzer aus der aktuellen Sprachdatei holen
        $languagefile = new Properties("../sprachen/$file");
        $pagecontent .= substr($file,0,strlen($file)-strlen(".conf"))." (".getLanguageValue("translator_text")." ".$languagefile->get("_translator_0").")";
        $pagecontent .= "</option>";
        }
        }
        closedir($handle);
        }
        $pagecontent .= "</select></td></tr>";
        // Zeile "SPRACHAUSWAHL ADMIN"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("selectLanguage_text")."</td><td class=\"config_row2\"><select name=\"adminlang\" class=\"maxwidth\">";
        if ($handle = opendir('conf')){
        while ($file = readdir($handle)) {
        $selected = "";
        if (isValidDirOrFile($file)) {
        if(substr($file,0,9) == "language_") {
        if (substr($file,9,4) == $ADMIN_CONF->get("language"))
        $selected = " selected";
        $pagecontent .= "<option".$selected." value=\"".substr($file,9,4)."\">";
        $currentlanguage = new Properties("conf/$file");
        $pagecontent .= substr($file,9,4)." (".getLanguageValue("translator_text")." ".$currentlanguage->get("_translator").")";
        $pagecontent .= "</option>";
        }
        }
        }
        closedir($handle);
        }
        $pagecontent .= "</select></td></tr>";
        */
    if($MAILFUNCTIONS->isMailAvailable())
    {
        // Zeile "ADMIN-MAIL"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("initialsetup_adminmail_text")."</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= "<input type=\"text\" class=\"text1\" name=\"adminmail\" value=\"".$ADMIN_CONF->get("adminmail")."\" /> ";
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";

        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\" colspan=\"2\">".getLanguageValue("initialsetup_login_text");
        $pagecontent .= "<br />";
        $pagecontent .= getLanguageValue("config_adminlogin_rules_text");
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
    }

    // Zeile "LOGIN-NAME"
    $pagecontent .= "<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newname_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"loginname\" value=\"".$LOGINCONF->get("name")."\" /></td>"
    ."</tr>"
    // Zeile "PASSWORT"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newpw_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"loginpw\" /></td>"
    ."</tr>"
    // Zeile "PASSWORT - WIEDERHOLUNG"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newpwrepeat_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"loginpwrepeat\" /></td>"
    ."</tr>";


    // Zeile "ÜBERNEHMEN"
    $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>";
    $pagecontent .= "</table>";
    $pagecontent .= "</form>";

    return array(getLanguageValue("button_initialsetup"), $pagecontent);
}

function sysInfo() {
    global $CMS_CONF;

    $safemode = getLanguageValue("no");
    if (ini_get('safe_mode')) {
        $safemode = getLanguageValue("yes");
    }

    $gdlibinstalled = getLanguageValue("no");
    if (extension_loaded("gd"))
    $gdlibinstalled = getLanguageValue("yes");

    $cmssize = convertFileSizeUnit(dirsize(getcwd()."/.."));

    $pagecontent = "<h2>".getLanguageValue("button_home_sysinfo")."</h2>"
    // CMS-INFOS
    ."<h3>".getLanguageValue("cmsinfo")."</h3>"
    ."<table class=\"data\">"
    // Zeile "CMS-VERSION"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("cmsversion_text")."</td>"
    ."<td class=\"config_row2\">".$CMS_CONF->get("cmsversion")."</td>"
    ."</tr>"
    // Zeile "Gesamtgröße des CMS"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("cmssize_text")."</td>"
    ."<td class=\"config_row2\">".$cmssize."</td>"
    ."</tr>"
    ."</table>"
    // SERVER-INFOS
    ."<h3>".getLanguageValue("serverinfo")."</h3>"
    ."<table class=\"data\">"
    // Zeile "Installationspfad"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("installpath_text")."</td>"
    ."<td class=\"config_row2\">".dirname(getcwd()."..")."</td>"
    ."</tr>"
    // Zeile "PHP-Version"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("phpversion_text")."</td>"
    ."<td class=\"config_row2\">".phpversion()."</td>"
    ."</tr>"
    // Zeile "Safe Mode"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("safemodeactive_text")."</td>"
    ."<td class=\"config_row2\">".$safemode."</td>"
    ."</tr>"
    // Zeile "GDlib installiert"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("gdlibinstalled_text")."</td>"
    ."<td class=\"config_row2\">".$gdlibinstalled."</td>"
    ."</tr>"

    ."</table>";
    return array(getLanguageValue("button_home_sysinfo"), $pagecontent);
}

function category() {
    $pagecontent = "<h2>".getLanguageValue("button_category")."</h2>";
    $pagecontent .= "<p>".getLanguageValue("category_text")."</p>";
    $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
    $pagecontent .= "<ul>";
    $pagecontent .= "<li><a href=\"index.php?action=newcategory\">".getLanguageValue("button_category_new")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=editcategory\">".getLanguageValue("button_category_edit")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=deletecategory\">".getLanguageValue("button_category_delete")."</a></li>";
    $pagecontent .= "</ul>";
    return array(getLanguageValue("button_category"), $pagecontent);
}

function newCategory() {
    global $action;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;

    $pagecontent = "";

    $title = getLanguageValue("button_category_new");
    $message1 = "";
    $message2 = "";
    $message3 = "";
    $message4 = "";
    $nameconflict = false;

    if (isset($_REQUEST["name"])) {
        $name = stripslashes($_REQUEST["name"]);
        // Existiert eine Kategorie mit gleichem Namen?
        $nameconflict = false;
        foreach (getDirContentAsArray($CONTENT_DIR_REL, false) as $test) {
            if(substr($test,3) == $name) {
                $nameconflict = true;
                break;
            }
        }
    }
    if(isset($_REQUEST["position"])) {
        if(strlen($name) == 0) {
            $message3 = getLanguageValue("category_empty");
        }
        elseif(strlen($_REQUEST["position"])>2 or $nameconflict) {
            $message1 = htmlentities($name).": ".getLanguageValue("category_exist");
        }
        elseif(!(preg_match($ALLOWED_SPECIALCHARS_REGEX, $name))) {
            $message4 = htmlentities($name).": ".getLanguageValue("category_name_wrong");
            $nameconflict = true;
        }
        elseif(strlen($specialchars->replaceSpecialChars($name))>64) {
            $message4 = htmlentities($name).": ".getLanguageValue("name_too_long");
            $nameconflict = true;
        }
        if(strlen($_REQUEST["position"])<3 && strlen($name) != 0 && !$nameconflict) {
            createCategory();
            $message2 = htmlentities($name).": ".getLanguageValue("category_created_ok");
        }
    }

    $pagecontent = "<h2>".getLanguageValue("button_category_new")."</h2>";
    if( $message1!="") {
        $pagecontent .= returnMessage(false, $message1);
    }
    if( $message2!="") {
        $pagecontent .= returnMessage(true, $message2);
    }
    if( $message3!="") {
        $pagecontent .= returnMessage(false, $message3);
    }
    if( $message4!="") {
        $pagecontent .= returnMessage(false, $message4);
    }


    $pagecontent .= "<p>";
    $pagecontent .= getLanguageValue("category_new_text");
    $pagecontent .= "</p>";
    $pagecontent .= "<h3>".getLanguageValue("button_category_new")."</h3>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"".$action."\"><table class=\"data\">";
    // Zeile "NEUER NAME"
    $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("choose_category_name")."</td>";
    if(!(isset($_REQUEST["name"])))    {
        $pagecontent .= "<td class=\"config_row2\"><input class=\"text1\" name=\"name\" /></td>";
    }    else {
        if($message1 != "" && $message3 != "" && $message4 != "") {
            $fill = $name;
        }
        else {
            $fill = "";
        }
        $pagecontent .= "<td class=\"config_row2\"><input class=\"text1\" name=\"name\" value=\"".$fill."\" /></td>";
    }
    $pagecontent .= "</tr>";
    // Zeile "NEUE POSITION"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("category_numbers", "category_number_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("choose_category_position")."</td>";
    $pagecontent .= "<td class=\"config_row2\">".show_dirs("$CONTENT_DIR_REL/", "")."</td>";
    $pagecontent .= "</tr>";
    // Zeile "SUBMIT"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
    $pagecontent .= "<td class=\"config_row1\"><input type=\"submit\" class=\"submit\"></td>";
    $pagecontent .= "</tr>";
    $pagecontent .= "</table>";
    $pagecontent .= "</form>";
    return array(getLanguageValue("button_category_new"), $pagecontent);
}
/*
function editCategory() {
    global $action;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;

    $pagecontent = "<h2>".getLanguageValue("button_category_edit")."</h2>";
    $goto = "";
    $done = false;
    if (isset($_REQUEST["newname"]))
    $newname = stripslashes($_REQUEST["newname"]);

    if(isset($_REQUEST["submit"])) {
        // Position frei
        if (strlen($_REQUEST["position"])<3) {
            if (preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname)) {
                @rename("$CONTENT_DIR_REL/".$_REQUEST["cat"],"$CONTENT_DIR_REL/".$_REQUEST["position"]."_".$specialchars->replaceSpecialChars($newname));
                renameCategoryInDownloadStats($_REQUEST["cat"], $_REQUEST["position"]."_".$specialchars->replaceSpecialChars($newname));
                updateReferencesInAllContentPages($_REQUEST["cat"], "", substr($_REQUEST["position"], 0, 2)."_".$specialchars->replaceSpecialChars($newname), "");
                $pagecontent .= returnMessage(true, htmlentities($newname).": ".getLanguageValue("category_edited"));
                $done = true;
            }
            else
            $pagecontent .= returnMessage(false, htmlentities($newname).": ".getLanguageValue("invalid_values"));
        }
        // Position belegt, aber mit der gleichen Kategorie >> UMBENENNEN
        elseif (substr($_REQUEST["position"],0,2)."_".$specialchars->replaceSpecialChars(substr($_REQUEST["position"],3)) == $_REQUEST["cat"]) {
            if (preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname)) {
                if (@rename("$CONTENT_DIR_REL/".$_REQUEST["cat"], "$CONTENT_DIR_REL/".substr($_REQUEST["position"],0,2)."_".$specialchars->replaceSpecialChars($newname))) {
                    // Referenzen auf die umbenannte Kategorie in der Download-Statistik ändern
                    renameCategoryInDownloadStats($_REQUEST["cat"], substr($_REQUEST["position"],0,2)."_".$specialchars->replaceSpecialChars($newname));
                    // Referenzen auf die umbenannte Kategorie in allen Inhaltsseiten ändern
                    updateReferencesInAllContentPages($_REQUEST["cat"], "", substr($_REQUEST["position"], 0, 2)."_".$specialchars->replaceSpecialChars($newname), "");
                    $pagecontent .= returnMessage(true, htmlentities($newname).": ".getLanguageValue("category_edited"));
                    $_REQUEST["cat"] = substr($_REQUEST["position"],0,2)."_".$specialchars->replaceSpecialChars($newname);
                    $done = true;
                }
            }
            else
            $pagecontent .= returnMessage(false, htmlentities($_REQUEST["cat"]).": ".getLanguageValue("invalid_values"));
        }
        // Position mit anderer Kategorie belegt
        else
        {
            $pagecontent .= returnMessage(false, htmlentities($_REQUEST["cat"]).": ".getLanguageValue("position_in_use"));
            $goto = "->";
        }
    }

    if((!isset($_REQUEST["cat"]) && $goto != "->") || $done)
    {
        // 1. Seite
        $pagecontent .= "<p>";
        $pagecontent .= getLanguageValue("category_edit_text");
        $pagecontent .= "</p>";
        $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"".$action."\">";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">";
        $pagecontent .= getLanguageValue("choose_category");
        $pagecontent .= "</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= getCatsAsSelect("");
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\"><input value=\"".getLanguageValue("choose_category_button")."\" type=\"submit\" class=\"submit\" /></td>";
        $pagecontent .= "</tr>";

        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }
    elseif(isset($_REQUEST["cat"]) || $goto == "->")
    {
        $pagecontent .= "<p>";
        $pagecontent .= getLanguageValue("category_choosed");
        $pagecontent .= "<b> ".$specialchars->rebuildSpecialChars(substr($_REQUEST["cat"],3), true)."</b>";
        $pagecontent .= "</p>";

        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\">";
        $pagecontent .= "<input type=\"hidden\" name=\"action\" value=\"".$action."\">";
        $pagecontent .= "<input type=\"hidden\" name=\"cat\" value=\"".$_REQUEST["cat"]."\">";
        $pagecontent .= "<table class=\"data\">";
        // Zeile "NAME ÄNDERN"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_category_name")."</td>";
        $pagecontent .= "<td class=\"config_row2\"><input class=\"Text1\" value=\"".$specialchars->rebuildSpecialChars( substr($_REQUEST["cat"],3), true )."\" type=\"text\" name=\"newname\"></td>";
        $pagecontent .= "</tr>";
        // Zeile "POSITION ÄNDERN"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_category_position")."</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= show_dirs("$CONTENT_DIR_REL", $_REQUEST["cat"]);
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= "<input value=\"".getLanguageValue("button_save")."\" type=\"Submit\" name=\"submit\" class=\"submit\" />";
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }
    return array(getLanguageValue("button_category_edit"), $pagecontent);
}*/

function editCategory() {
    global $action;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $EXT_LINK;

    $pagecontent = "<h2>".getLanguageValue("button_category_edit")."</h2>";
    $done = false;

    if (!empty($_REQUEST["newname"])) {
		$nameconflict = false;
		$newname = $specialchars->replaceSpecialChars(stripslashes($_REQUEST["newname"]));
		// Exestiert Kategorie mit gleichen namen ohne Position dann fehler
		$doppelt = 0;
		foreach (getDirContentAsArray($CONTENT_DIR_REL, false) as $test) {
			if(substr($test,3) == $newname) $doppelt++;
			if($doppelt > 0) {
				$nameconflict = true;
				break;
			}
		}
		if($nameconflict
				and $newname == substr($_REQUEST["cat"],3)
				and substr($_REQUEST["position"],0,2) != substr($_REQUEST["cat"],0,2)) {
			$nameconflict = false;
		}
		if($nameconflict) {
			$newname = "";
			$pagecontent .= returnMessage(false, htmlentities(substr($_REQUEST["position"],0,2)."_".$_REQUEST["newname"]).": ".getLanguageValue("category_exist"));
		}
	}

	if(isset($_REQUEST["submit"]) and !empty($newname))
	{
		// Position frei oder Position belegt, aber mit der gleichen Kategorie >> UMBENENNEN
		if((substr($_REQUEST["position"],0,2) == substr($_REQUEST["cat"],0,2)) or strlen($_REQUEST["position"]) < 3)
		{
			if(preg_match($ALLOWED_SPECIALCHARS_REGEX, stripslashes($_REQUEST["newname"])))
			{
				if(@rename("$CONTENT_DIR_REL/".$_REQUEST["cat"], "$CONTENT_DIR_REL/".substr($_REQUEST["position"],0,2)."_".$newname))
				{
					// Referenzen auf die umbenannte Kategorie in der Download-Statistik ändern
					renameCategoryInDownloadStats($_REQUEST["cat"], substr($_REQUEST["position"],0,2)."_".$newname);
					// Referenzen auf die umbenannte Kategorie in allen Inhaltsseiten ändern
					updateReferencesInAllContentPages($_REQUEST["cat"], "", substr($_REQUEST["position"], 0, 2)."_".$newname, "");
					$pagecontent .= returnMessage(true, htmlentities($_REQUEST["newname"]).": ".getLanguageValue("category_edited"));
					$_REQUEST["cat"] = substr($_REQUEST["position"],0,2)."_".$newname;
					$done = true;
				}
			}
			else
				$pagecontent .= returnMessage(false, htmlentities($_REQUEST["cat"]).": ".getLanguageValue("invalid_values"));
		}
		// Position mit anderer Kategorie belegt
        else
        {
            $pagecontent .= returnMessage(false, htmlentities($_REQUEST["cat"]).": ".getLanguageValue("position_in_use"));
        }
    }

    if(!isset($_REQUEST["submit"]) or $done)
    {
        // 1. Seite
        $pagecontent .= "<p>";
        $pagecontent .= getLanguageValue("category_edit_text");
        $pagecontent .= "</p>";
        $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"".$action."\">";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">";
        $pagecontent .= getLanguageValue("choose_category");
        $pagecontent .= "</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= getCatsAsSelect("");
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\"><input name=\"submit\" value=\"".getLanguageValue("choose_category_button")."\" type=\"submit\" class=\"submit\" /></td>";
        $pagecontent .= "</tr>";

        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }
    elseif(!empty($_REQUEST["cat"]) and !($done))
    {
        $pagecontent .= "<p>";
        $pagecontent .= getLanguageValue("category_choosed");
        $pagecontent .= "<b> ".$specialchars->rebuildSpecialChars(substr($_REQUEST["cat"],3), true)."</b>";
        $pagecontent .= "</p>";

        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\">";
        $pagecontent .= "<input type=\"hidden\" name=\"action\" value=\"".$action."\">";
        $pagecontent .= "<input type=\"hidden\" name=\"cat\" value=\"".$_REQUEST["cat"]."\">";
        $pagecontent .= "<table class=\"data\">";
        // Zeile "NAME ÄNDERN"
        $tmpname = substr($_REQUEST["cat"],3);
        $current_language = "current_category_name";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue($current_language)."</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= "<input class=\"Text1\" value=\"".$specialchars->rebuildSpecialChars( $tmpname, true )."\" type=\"text\" name=\"newname\"></td>";
        $pagecontent .= "</tr>";


        // Zeile "POSITION ÄNDERN"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_category_position")."</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= show_dirs("$CONTENT_DIR_REL", $_REQUEST["cat"]);
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= "<input value=\"".getLanguageValue("button_save")."\" type=\"Submit\" name=\"submit\" class=\"submit\" />";
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }
    return array(getLanguageValue("button_category_edit"), $pagecontent);
}

function deleteCategory() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    if (isset($_REQUEST['cat']))
    $cat = stripslashes($_REQUEST['cat']);

    $pagecontent = "<h2>".getLanguageValue("button_category_delete")."</h2>";
    // Löschen der Kategorie nach Auswertung der übergebenen Parameter
    if (isset($cat) && file_exists("$CONTENT_DIR_REL/".$cat)) {
        if (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == "true")) {
            if (deleteDir("$CONTENT_DIR_REL/".$cat)) {
                deleteCategoryFromDownloadStats($cat);    // Alle Dateien der gelöschten Kategorie aus Downloadstatistik entfernen
                $pagecontent .= returnMessage(true, $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), true).": ".getLanguageValue("category_deleted"));
            }
            else
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), true).": ".getLanguageValue("category_delete_error"));
        }
        else
        $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), true).": ".getLanguageValue("category_delete_confirm")." <a href=\"index.php?action=deletecategory&amp;cat=".$cat."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletecategory\">".getLanguageValue("no")."</a>");
    }

    $pagecontent .= "<p>".getLanguageValue("category_delete_text")."</p>";
    $dirs = getDirs("$CONTENT_DIR_REL");
    $pagecontent .= "<table class=\"data\">";
    foreach ($dirs as $file) {
        $file = $file."_".specialNrDir("$CONTENT_DIR_REL", $file);
        if (isValidDirOrFile($file)
        && ($pageshandle = opendir("$CONTENT_DIR_REL/".$file))
        && ($fileshandle = opendir("$CONTENT_DIR_REL/".$file."/dateien"))
        ) {
            // Anzahl Inhaltsseiten auslesen
            $pagescount = 0;
            while (($currentpage = readdir($pageshandle))) {
                if (is_file("$CONTENT_DIR_REL/".$file."/".$currentpage))
                $pagescount++;
            }
            // Anzahl Dateien auslesen
            $filecount = 0;
            while (($filesdir = readdir($fileshandle))) {
                if (isValidDirOrFile($filesdir))
                $filecount++;
            }
            if ($pagescount == 1)
            $pagestext = getLanguageValue("single_page");
            else
            $pagestext = getLanguageValue("many_pages");
            if ($filecount == 1)
            $filestext = getLanguageValue("single_file");
            else
            $filestext = getLanguageValue("many_files");
            $pagecontent .= "<tr><td class=\"config_row1\"><h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</h3> ($pagescount $pagestext, $filecount $filestext)</td>";
            $pagecontent .= "<td class=\"config_row2 righttext\"><a href=\"index.php?action=deletecategory&amp;cat=$file\" class=\"imagelink\">".getActionIcon("delete", getLanguageValue("button_delete"))."</a></td></tr>";
        }
        closedir($pageshandle);
        closedir($fileshandle);
    }
    $pagecontent .= "</table>";
    return array(getLanguageValue("button_category_delete"), $pagecontent);
}

function site() {
    $pagecontent = "<h2>".getLanguageValue("button_site")."</h2>";
    $pagecontent .= "<p>".getLanguageValue("site_text")."</p>";
    $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
    $pagecontent .= "<ul>";
    $pagecontent .= "<li><a href=\"index.php?action=newsite\">".getLanguageValue("button_site_new")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=editsite\">".getLanguageValue("button_site_edit")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=deletesite\">".getLanguageValue("button_site_delete")."</a></li>";
    $pagecontent .= "</ul>";
    return array(getLanguageValue("button_site"), $pagecontent);
}

function newSite() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $EXT_PAGE;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;

    $pagecontent = "";
    if (isset($_POST['page'])) {
        $page = stripslashes($_POST['page']);
    }
    if (isset($_POST['cat'])) {
        $cat = stripslashes($_POST['cat']);
    }
    $nameconflict = false;
    if (isset($_POST['name'])) {
        $name = stripslashes($_POST['name']);
        foreach (getFiles("$CONTENT_DIR_REL/$cat", false) as $test) {
            if(substr($test,3,-4) == $name) {
                $nameconflict = true;
                break;
            }
        }
    }

    // Wenn nach dem Editieren "Speichern" gedrückt wurde
    if (isset($_POST['save'])) {
        // Entwurf speichern
        if (isset($_POST['saveas']) && ($_POST['saveas'] == "draft")) {
            saveContentToPage($_POST['pagecontent'],"$CONTENT_DIR_REL/".$cat."/".substr($page, 0, strlen($page)-4).$EXT_DRAFT);
        }
        // versteckte Seite speichern
        elseif (isset($_POST['saveas']) && ($_POST['saveas'] == "hidden")) {
            saveContentToPage($_POST['pagecontent'],"$CONTENT_DIR_REL/".$cat."/".substr($page, 0, strlen($page)-4).$EXT_HIDDEN);
        }
        // als normale Seite speichern
        else {
            saveContentToPage($_POST['pagecontent'],"$CONTENT_DIR_REL/".$cat."/".substr($page, 0, strlen($page)-4).$EXT_PAGE);
        }
        $pagecontent = returnMessage(true, $specialchars->rebuildSpecialChars(substr($page, 3,strlen($page)-7), true).": ".getLanguageValue("changes_applied"));
    }

    // Wenn nach dem Editieren "Abbrechen" gedrückt wurde
    elseif (isset($_POST['cancel'])) {
        unset($_POST['cancel']);
        $functionreturn = newSite();
        return array($functionreturn[0], $functionreturn[1]);
    }

    // Wenn die Kategorie schon gewählt wurde oder ein Fehler aufgetreten ist
    if ( isset($cat) ||
    (
    isset($_POST['position']) && isset($name)
    && (strlen($name) == 0)
    && (!preg_match($ALLOWED_SPECIALCHARS_REGEX, $name))
    && (strlen($_POST['position'])>2)
    && $nameconflict
    )
    ) {
        $pagecontent .= "<h2>".getLanguageValue("button_site_new")."</h2>";
        $pagecontent .= "<h3>".getLanguageValue("chosen_category")." ".$specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)-3), true)."</h3>";
        if (isset($_POST['position']) && isset($name)) {
            if (strlen($name) == 0)
            $pagecontent .= returnMessage(false, getLanguageValue("page_empty"));
            elseif (!preg_match($ALLOWED_SPECIALCHARS_REGEX, $name))
            $pagecontent .= returnMessage(false, htmlentities($name).": ".getLanguageValue("invalid_values"));
            elseif (strlen($_POST["position"])>2 or $nameconflict)
            $pagecontent .= returnMessage(false, htmlentities($name).": ".getLanguageValue("page_exist"));
        }
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"newsite\"><input type=\"hidden\" name=\"cat\" value=\"".$cat."\">";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_page_name")."</td>";
        $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" name=\"name\"></td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("page_numbers", "page_number_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("choose_page_position")."</td>";
        $pagecontent .= "<td class=\"config_row2\">".show_files("$CONTENT_DIR_REL/".$cat, "x", false)."</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("button_newpage_create")."\" /></td></tr>";
        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }
    else {
        // Zuerst: Kategorie wählen
        $pagecontent = "<h2>".getLanguageValue("button_site_new")."</h2>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"newsite\">";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_category_for_page")."</td>";
        $pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect("")."</td></tr>";
        $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_category_button")."\" /></td></tr>";
        $pagecontent .= "</table>";
        $pagecontent .= "</form>";
    }

    // Wenn Name und Position der Seite schon gewählt wurde und korrekt sind
    if (
    isset($_POST['position'])
    && isset($name)
    && strlen($name) > 0
    && preg_match($ALLOWED_SPECIALCHARS_REGEX, $name)
    && (strlen($_POST['position'])<=2)
    && !$nameconflict
    ) {
        $pagecontent = "<h2>".getLanguageValue("button_site_new")."</h2>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"name=\"form\" method=\"post\" action=\"index.php\">";
        $pagecontent .= showEditPageForm($cat, $_POST['position']."_".$specialchars->replaceSpecialChars($name).$EXT_PAGE, "newsite", "");
        $pagecontent .= "</form>";
    }
    return array(getLanguageValue("button_site_new"), $pagecontent);
}

function editSite() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $EXT_PAGE;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;

    $pagecontent = "<h2>".getLanguageValue("button_site_edit")."</h2>";

    if (isset($_POST['page']))
    $page = stripcslashes($_POST['page']);
    if (isset($_POST['cat']))
    $cat = stripcslashes($_POST['cat']);
    // Wenn nach dem Editieren "Speichern" gedrückt wurde
    if (isset($_POST['save']) || isset($_POST['savetemp'])) {
        $pagenamewithoutextension = substr($page, 0, strlen($page)-4);
        // Entwurf speichern
        if (isset($_POST['saveas']) && ($_POST['saveas'] == "draft")) {
            $newpagename = $pagenamewithoutextension.$EXT_DRAFT;
        }
        // als versteckte Seite speichern
        elseif (isset($_POST['saveas']) && ($_POST['saveas'] == "hidden")) {
            $newpagename = $pagenamewithoutextension.$EXT_HIDDEN;
            // wenn Inhaltsseite oder Entwurf von dieser Seite existiert: vorher löschen
            if (file_exists("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_PAGE))
            @unlink("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_PAGE);
            if (file_exists("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_DRAFT))
            @unlink("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_DRAFT);
        }
        // als normale Inhaltsseite speichern
        else {
            $newpagename = $pagenamewithoutextension.$EXT_PAGE;
            // wenn versteckte Seite oder Entwurf von dieser Seite existiert: vorher löschen
            if (file_exists("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_HIDDEN))
            @unlink("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_HIDDEN);
            if (file_exists("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_DRAFT))
            @unlink("$CONTENT_DIR_REL/".$cat."/".$pagenamewithoutextension.$EXT_DRAFT);
        }
        saveContentToPage($_POST['pagecontent'],"$CONTENT_DIR_REL/".$cat."/".$newpagename);
        $pagecontent .= returnMessage(true, $specialchars->rebuildSpecialChars(substr($newpagename,3,strlen($newpagename)-7),true).": ".getLanguageValue("changes_applied"));
        // Beim Zwischenspeichern: Zurückkehren zur Editieransicht
        if (isset($_POST['savetemp'])) {
            $_REQUEST["cat"] = $cat;
            $_REQUEST["file"] = $newpagename;
        }
    }

    // Wenn nach dem Editieren "Abbrechen" gedrückt wurde
    elseif (isset($_POST['cancel']))
    {
        unset($_REQUEST["cat"]);
        unset($_REQUEST["file"]);
    }
    // Editieransicht der Inhaltsseite
    if (isset($_REQUEST['file']) && isset($_REQUEST['cat'])) {
        $file = stripslashes($_REQUEST['file']);
        $cat = stripslashes($_REQUEST['cat']);
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"name=\"form\" method=\"post\" action=\"index.php\">";
        $status = "";
        if (substr($file, strlen($file)-4, strlen($file)) == $EXT_DRAFT) {
            $status = " (".getLanguageValue("draft").")";
        }
        else if (substr($file, strlen($file)-4, strlen($file)) == $EXT_HIDDEN) {
            $status = " (".getLanguageValue("hiddenpage").")";
        }
        $pagecontent .= "<h3>".getLanguageValue("chosen_page")." ".$specialchars->rebuildSpecialChars(substr($file,3,strlen($file)-7), true).$status."</h3>";
        $pagecontent .= showEditPageForm($cat, $file, "editsite", "");
        $pagecontent .= "</form>";
    }

    else {
        $dirs = getDirs("$CONTENT_DIR_REL");
        sort($dirs);
        $pagecontent .= "<p>".getLanguageValue("page_edit_text")."</p>";
        foreach ($dirs as $file) {
            $file = $file."_".specialNrDir("$CONTENT_DIR_REL", $file);
            if (isValidDirOrFile($file) && ($file <> ".svn") && ($file <> ".s_n") && ($subhandle = opendir("$CONTENT_DIR_REL/".$file))) {
                $pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</h3>";
                $hasdata = false;
                $pagecontent .= "<table class=\"data\">";
                $catcontent = array();
                while (($subfile = readdir($subhandle))) {
                    if (is_file("$CONTENT_DIR_REL/".$file."/".$subfile)) {
                        array_push($catcontent, $subfile);
                    }
                }
                closedir($subhandle);
                sort($catcontent);
                foreach ($catcontent as $subfile) {
                    $status = "";
                    $draftaction = "";
                    if (substr($subfile, strlen($subfile)-4, strlen($subfile)) == $EXT_DRAFT) {
                        $status = " (".getLanguageValue("draft").")";
                        $draftaction = "&amp;action=draft";
                    }
                    elseif (substr($subfile, strlen($subfile)-4, strlen($subfile)) == $EXT_HIDDEN) {
                        $status = " (".getLanguageValue("hiddenpage").")";
                    }
                    $pagecontent .= "<tr><td class=\"config_row1\">".$specialchars->rebuildSpecialChars(substr($subfile, 3, strlen($subfile)-7), true)."$status</td><td class=\"config_row2 righttext\">";
                    $pagecontent .= "<a href=\"../index.php?cat=".$file."&amp;page=".substr($subfile, 0, strlen($subfile)-4)."$draftaction\" class=\"imagelink\" target=\"_blank\">".getActionIcon("view", getLanguageValue("button_preview"))."</a>";
                    $pagecontent .= "&nbsp;<a href=\"index.php?action=editsite&amp;cat=".$file."&amp;file=".$subfile."\" class=\"imagelink\">".getActionIcon("editpage", getLanguageValue("button_edit"))."</a>";
                    $pagecontent .= "&nbsp;<a href=\"index.php?action=copymovesite&amp;cat=".$file."&amp;file=".$subfile."\" class=\"imagelink\">".getActionIcon("copypage", getLanguageValue("button_copymove"))."</a>";
                    $pagecontent .= "</td></tr>";
                    $hasdata = true;
                }
                if (!$hasdata)
                $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("page_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
                $pagecontent .= "</table>";
            }
        }
    }
    return array(getLanguageValue("button_site_edit"), $pagecontent);
}

function copymoveSite() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $EXT_PAGE;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;

    // Initialvariablen und Konstanten
    define("CAT_PARAM","cat");
    define("PAGE_PARAM","file");
    define("CURRENT_CAT","currcat");
    define("ACTION_PARAM","action");
    define("ACTION_VALUE","copymovesite");
    define("STEP","step");
    define("NEW_PAGE_NAME","newname");

    $step = 0;
    
    // Wenn die Werte übergeben wurden, dann ab damit in die Hiddenfields. Sonst
    // wieder aus den POST-Daten auslesen
    $catvalue = "";
    $pagevalue = "";
    $curcat = "";
    
    if(isset($_POST[CAT_PARAM]) && isset($_POST[PAGE_PARAM]))
    {
        
        // Methodik mit POST
        $catvalue = $_POST[CAT_PARAM];
        $pagevalue = $_POST[PAGE_PARAM];
    }
    elseif (isset($_GET[CAT_PARAM]) && isset($_GET[PAGE_PARAM]))
    {
        // Methodik mit GET
        $catvalue = $_GET[CAT_PARAM];
        $pagevalue = $_GET[PAGE_PARAM];
    }

    if(isset($_POST[CURRENT_CAT])) $curcat = $_POST[CURRENT_CAT];
    // Form und Hidden fields zammenbasteln
    $pagecontent = "<form accept-charset=\"ISO-8859-1\" name=\"form\" method=\"post\" action=\"index.php?" . ACTION_PARAM . "=" . ACTION_VALUE . "\">";
    $pagecontent .= "<input type='hidden' id='" . CAT_PARAM . "' name='" . CAT_PARAM . "' value='" . $catvalue . "' />";
    $pagecontent .= "<input type='hidden' id='" . PAGE_PARAM . "' name='" . PAGE_PARAM . "' value='" . $pagevalue . "' />";
    $pagecontent .= "<input type='hidden' name='" . CURRENT_CAT . "' value='".$curcat."' />";
    $pagecontent .= "<input type='hidden' id='" . STEP . "' name='" . STEP . "' value='";
    
    // Step mitzählen
    if(isset($_POST[STEP]))
    {
        $step = (int) $_POST[STEP] + 1;
    }
    $pagecontent .= $step;
    $pagecontent .= "' />";

    // Speichern  --------------------------------------------------------------
    if(isset($_POST["type"]) && isset($_POST[NEW_PAGE_NAME]) && $step > 1)
    {
        $checkdir = $specialchars->replaceSpecialChars("$CONTENT_DIR_REL/".$_POST[CURRENT_CAT]);
        $sourcefile = $specialchars->replaceSpecialChars("$CONTENT_DIR_REL/".$_POST[CAT_PARAM]."/".$_POST[PAGE_PARAM],true);
        $destinationfile = $specialchars->replaceSpecialChars($checkdir."/".substr($_POST["position"],0,2)."_".$_POST[NEW_PAGE_NAME]).substr($sourcefile,strlen($sourcefile) - 4,4);
        $messagefile = $specialchars->rebuildSpecialChars($_POST[NEW_PAGE_NAME], true);

        $nameconflict = true;
        $doppelt = 0;
        foreach (getFiles("$CONTENT_DIR_REL/".$_POST[CURRENT_CAT], false) as $test) {
            if(substr($test,3,-4) == $_POST[NEW_PAGE_NAME]) $doppelt++;
            if($doppelt > 0) {
                $nameconflict = false;
                break;
            }
        }
	if(!$nameconflict
			and substr($_POST[PAGE_PARAM],3,-4) == $_POST[NEW_PAGE_NAME]
			and substr($_POST[PAGE_PARAM],0,2) != substr($_POST["position"],0,2))
		$nameconflict = true;
        if($_POST["type"] == "1"
        and $nameconflict
        and preg_match($ALLOWED_SPECIALCHARS_REGEX, $_POST[NEW_PAGE_NAME])
        and $_POST[CAT_PARAM] == $_POST[CURRENT_CAT]
        and (specialNrFile("$CONTENT_DIR_REL/".$_POST[CURRENT_CAT], substr($_POST["position"],0,2)) == substr($_POST[PAGE_PARAM],3)
            or strlen($_POST["position"]) <= 2))
        {
            if(@rename($sourcefile,$destinationfile))
            {
                updateReferencesInAllContentPages($_POST[CAT_PARAM], $pagevalue, "", substr($_POST["position"],0,2) . "_" . $specialchars->replaceSpecialChars($_POST[NEW_PAGE_NAME]).substr($sourcefile,strlen($sourcefile) - 4,4));
                $pagecontent = returnMessage(true, htmlentities($messagefile).": ".getLanguageValue("copymove_type_move_success"));
            }
            else
            {
                $pagecontent = returnMessage(false, htmlentities($messagefile).": ".getLanguageValue("page_exist"));
            }
        }
        elseif(strlen($_POST["position"]) <= 2 and $nameconflict and preg_match($ALLOWED_SPECIALCHARS_REGEX, $_POST[NEW_PAGE_NAME]))
        {
            if(@copy($sourcefile, $destinationfile))
            {
                $unlink = "nein";
                if($_POST["type"] == "1")
                {
                    // Wenn verschieben dann alte datei löschen
                    if(@unlink($sourcefile))
                    {
                        updateReferencesInAllContentPages($catvalue, $pagevalue, $specialchars->replaceSpecialChars($_POST[CURRENT_CAT]), $_POST["position"] . "_" . $specialchars->replaceSpecialChars($_POST[NEW_PAGE_NAME]).substr($sourcefile,strlen($sourcefile) - 4,4));
                        $pagecontent = returnMessage(true, htmlentities($messagefile).": ".getLanguageValue("copymove_type_move_success"));
                        $unlink = "ja";
                    }
                }
                if($unlink == "nein") {
                    $pagecontent = returnMessage(true, htmlentities($messagefile).": ".getLanguageValue("copymove_type_copy_success"));
                }
            }
            else
            {
                $pagecontent = returnMessage(false, htmlentities($messagefile).": ".getLanguageValue("page_exist"));
            }
            if(!preg_match($ALLOWED_SPECIALCHARS_REGEX, $_POST[NEW_PAGE_NAME])) {
                $pagecontent = returnMessage(false, $specialchars->rebuildSpecialChars($_POST[NEW_PAGE_NAME], true, true).": ".getLanguageValue("invalid_values")); 
            }
        }
        else
        {
        $pagecontent = returnMessage(false, htmlentities($messagefile).": ".getLanguageValue("page_exist"));
        }
        unset($_REQUEST["cat"]);
        unset($_REQUEST["file"]);
        $functionreturn = editSite();
        return array($functionreturn[0], $pagecontent.$functionreturn[1]);
    }

    
    // STEP 0 ------------------------------------------------------------------
    if($step == 0)
    {
        $pagecontent .= "<div>";
        $pagecontent .= "<h3>".getLanguageValue("button_copymove_category_headline")."</h3>";
        $pagecontent .= "<table style='width: 100%;'><tr><td class='config_row1'>".getLanguageValue("button_copymove_choose_category")."</td><td class='config_row2'>";
        $pagecontent .= str_replace('<select name="cat">', '<select name="'.CURRENT_CAT.'">', getCatsAsSelect($catvalue));

    
        $pagecontent .= "</select></td></tr></table></div>";
        $pagecontent .= "<table style='width: 100%;'>"
                 .  "<tr>"
                 .  "<td class='config_row1'></td>"
                 .  "<td class='config_row2'>"
                 .  "<input type=\"submit\" class=\"submit\" value=\"" . getLanguageValue("choose_category_button") . "\" />"
                 .  "</td>"
                 .    "</tr>"
                 .    "</table>";
    }
    elseif($step == 1)
    {
    // STEP 1 ------------------------------------------------------------------
        $pagecontent .= "<div>";
        $pagecontent .= "<h2>".getLanguageValue("button_copymove_edit")."</h2>";
        $checked0 = "checked";
        $checked1 = "";
        $pagecontent .= "<table style='width: 100%;'>"
                     .  "<tr>"
                     .  "<td class='config_row1'>".getLanguageValue("button_copymove_choose_copymove")."</td>"
                     .  "<td class='config_row2'>"
                     .  "<input type=\"radio\" name=\"type\" value=\"0\" $checked0 />".getLanguageValue("copymove_type_copy")."<br />"
                     .  "<input type=\"radio\" name=\"type\" value=\"1\" $checked1 />".getLanguageValue("copymove_type_move")."<br />"
                     .  "</td>"
                     .  "</tr>"
                     .  "<tr>"
                     .  "<td class='config_row1'>".getLanguageValue("button_copymove_choose_pos")."</td>"
                     .  "<td class='config_row2'>";
        if(!isset($_POST[CURRENT_CAT])) $_POST[CURRENT_CAT] = "";
        $pagecontent .= show_files("$CONTENT_DIR_REL/". $_POST[CURRENT_CAT], substr($pagevalue,3,strlen($pagevalue)-7) , false);
        $pagecontent .= "</td>"
                     .  "</tr>";
        $pagecontent .=  "<tr>"
                     .  "<td class='config_row1'>".getLanguageValue("button_copymove_new_name")."</td>"
                     .  "<td class='config_row2'>";
        $pagecontent .= "<input type='text' id='" . NEW_PAGE_NAME . "' name='" . NEW_PAGE_NAME . "' value='" . $specialchars->rebuildSpecialChars(substr($pagevalue,3,strlen($pagevalue)-7), true) . "' />"
                 .  "</td>"
                 .  "</tr>"
                 .  "</table>";

        $pagecontent .= "</div>";
        $pagecontent .= "<table style='width: 100%;'>"
                 .  "<tr>"
                 .  "<td class='config_row1'></td>"
                 .  "<td class='config_row2'>"
                 .  "<input type=\"submit\" class=\"submit\" value=\"" . getLanguageValue("finish") . "\" />"
                 .  "</td>"
                 .    "</tr>"
                 .    "</table>";
    }    
    $pagecontent .= "</form>";

    return array(getLanguageValue("button_site_edit"), $pagecontent);
}


function deleteSite() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;

    if (isset($_REQUEST['cat']))
    $cat = stripslashes($_REQUEST['cat']);
    if (isset($_REQUEST['file']))
    $file = stripslashes($_REQUEST['file']);
    $pagecontent = "<h2>".getLanguageValue("button_site_delete")."</h2>";
    // Löschen der Inhaltsseite nach Auswertung der übergebenen Parameter
    if (isset($cat) && isset($file) && file_exists("$CONTENT_DIR_REL/".$cat) && file_exists("$CONTENT_DIR_REL/".$cat."/".$file)) {
        if (substr($file, strlen($file)-4, strlen($file)) == $EXT_DRAFT)
        $status = " (".getLanguageValue("draft").")";
        elseif (substr($file, strlen($file)-4, strlen($file)) == $EXT_HIDDEN)
        $status = " (".getLanguageValue("hiddenpage").")";
        else
        $status = "";
        // Löschnachfrage bestätigt?
        if (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == "true")) {
            if (@unlink("$CONTENT_DIR_REL/".$cat."/".$file))
            // Löschen erfolgreich
            $pagecontent .= returnMessage(true, $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-7), true)."$status: ".getLanguageValue("page_deleted"));
            else
            // Löschen fehlgeschlagen
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-7), true)."$status: ".getLanguageValue("page_delete_error"));
        }
        // Nachfrage: Wirklich löschen?
        else {
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-7), true)."$status: ".getLanguageValue("page_delete_confirm")." <a href=\"index.php?action=deletesite&amp;cat=".$cat."&amp;file=".$file."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletesite\">".getLanguageValue("no")."</a>");
        }
    }
    $pagecontent .= "<p>".getLanguageValue("page_delete_text")."</p>";
    $dirs = getDirs("$CONTENT_DIR_REL");
    foreach ($dirs as $file) {
        $file = $file."_".specialNrDir("$CONTENT_DIR_REL", $file);
        if (isValidDirOrFile($file) && ($subhandle = opendir("$CONTENT_DIR_REL/".$file))) {
            $pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</h3>";
            $hasdata = false;
            $pagecontent .= "<table class=\"data\">";

            $catcontent = array();
            while (($subfile = readdir($subhandle))) {
                if (is_file("$CONTENT_DIR_REL/".$file."/".$subfile)) {
                    array_push($catcontent, $subfile);
                }
            }
            closedir($subhandle);
            sort($catcontent);
            foreach ($catcontent as $subfile) {
                $status ="";
                $draftaction = "";
                if (substr($subfile, strlen($subfile)-4, strlen($subfile)) == $EXT_DRAFT) {
                    $status = " (".getLanguageValue("draft").")";
                    $draftaction = "&amp;action=draft";
                }
                elseif (substr($subfile, strlen($subfile)-4, strlen($subfile)) == $EXT_HIDDEN) {
                    $status = " (".getLanguageValue("hiddenpage").")";
                }
                $pagecontent .= "<tr><td class=\"config_row1\">".$specialchars->rebuildSpecialChars(substr($subfile, 3, strlen($subfile)-7), true)."$status</td><td class=\"config_row2 righttext\">";
                $pagecontent .= "<a href=\"../index.php?cat=".$file."&amp;page=".substr($subfile, 0, strlen($subfile)-4)."$draftaction\" class=\"imagelink\" target=\"_blank\">".getActionIcon("view", getLanguageValue("button_preview"))."</a>";
                $pagecontent .= "&nbsp;<a href=\"index.php?action=deletesite&amp;cat=".$file."&amp;file=".$subfile."\" class=\"imagelink\">".getActionIcon("delete", getLanguageValue("button_delete"))."</a>";
                $pagecontent .= "</td></tr>";
                $hasdata = true;
            }
            if (!$hasdata)
            $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("page_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
            $pagecontent .= "</table>";
        }
    }
    return array(getLanguageValue("button_site_delete"), $pagecontent);
}

function gallery() {
    $pagecontent = "<h2>".getLanguageValue("button_gallery")."</h2>";
    $pagecontent .= "<p>".getLanguageValue("gallery_text")."</p>";
    $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
    $pagecontent .= "<ul>";
    $pagecontent .= "<li><a href=\"index.php?action=newgallery\">".getLanguageValue("button_gallery_new")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=editgallery\">".getLanguageValue("button_gallery_edit")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=deletegallery\">".getLanguageValue("button_gallery_delete")."</a></li>";
    $pagecontent .= "</ul>";
    return array(getLanguageValue("button_gallery"), $pagecontent);
}

function newGallery() {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $ADMIN_CONF;

    $pagecontent = "<h2>".getLanguageValue("button_gallery_new")."</h2>";

    if (isset($_POST['galleryname']))
    $galleryname = stripslashes($_POST['galleryname']);

    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        if (isset($galleryname) && preg_match($ALLOWED_SPECIALCHARS_REGEX, $galleryname)) {
            $dirname = $specialchars->replaceSpecialChars($galleryname);
            // Galerieverzeichnis schon vorhanden? Wenn nicht: anlegen!
            if (!file_exists("$GALLERIES_DIR_REL/".$dirname)) {
                if (@mkdir($GALLERIES_DIR_REL."/".$dirname, 0777) && @mkdir($GALLERIES_DIR_REL."/".$dirname."/".$PREVIEW_DIR_NAME, 0777)) {
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
                    $filename = "$GALLERIES_DIR_REL/".$dirname."/texte.conf";
                    $fp = fopen ($filename, "w");
                    // chmod, wenn so eingestellt
                    if ($ADMIN_CONF->get("chmodnewfiles") == "true")
                    chmod ($filename, octdec($ADMIN_CONF->get("chmodnewfilesatts")));
                    fclose($fp);
                    $pagecontent .= returnMessage(true, htmlentities($galleryname).": ".getLanguageValue("gallery_create_success"));
                }
                else
                $pagecontent .= returnMessage(false, htmlentities($galleryname).": ".getLanguageValue("gallery_create_error"));
            }
            else {
                $pagecontent .= returnMessage(false, htmlentities($galleryname).": ".getLanguageValue("gallery_exists_error"));
            }
        }
        else
        $pagecontent .= returnMessage(false, htmlentities($galleryname).": ".getLanguageValue("invalid_values"));
    }
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"newgallery\" />";
    $pagecontent .= "<table class=\"data\">";
    // Zeile "NAME DER GALERIE"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_choose_name_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"galleryname\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "GALERIE ANLEGEN"
    $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("button_gallery_new")."\" /></td></tr>";
    $pagecontent .= "</table></form>";

    return array(getLanguageValue("button_gallery_new"), $pagecontent);
}

function editGallery() {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $ADMIN_CONF;

    if (isset($_REQUEST['gal']))
    $gal = stripslashes($_REQUEST['gal']);

    if (isset($gal) && file_exists("$GALLERIES_DIR_REL/".$gal))
    $mygallery = $gal;

    $pagecontent = "<h2>".getLanguageValue("button_gallery_edit")."</h2>";
    // Zuerst: Galerie wählen
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"editgallery\">";
    $pagecontent .= "<table class=\"data\">";
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_gal_for_editgallery")."</td>";
    $pagecontent .= "<td class=\"config_row2\">".getGalleriesAsSelect("")."</td></tr>";
    $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_gallery_button")."\" /></td></tr>";
    $pagecontent .= "</table>";
    $pagecontent .= "</form>";

    // Wenn die Galerie schon gewählt wurde
    if (isset($mygallery) && file_exists("$GALLERIES_DIR_REL/".$mygallery)) {
        $galleryconf = new Properties("$GALLERIES_DIR_REL/".$mygallery."/texte.conf");
        $msg = "";
        $pagecontent = "<h2>".getLanguageValue("button_gallery_edit")."</h2>";
        // Galeriebild hochladen
        if (isset($_FILES['uploadfile']) and !$_FILES['uploadfile']['error']) {
            $gallerydir = "$GALLERIES_DIR_REL/".$mygallery;
            if (!fileHasExtension($_FILES['uploadfile']['name'], array("jpg", "jpeg", "jpe", "gif", "png", "svg")))
            $pagecontent .= returnMessage(false, htmlentities($_FILES['uploadfile']['name']).": ".getLanguageValue("gallery_uploadfile_wrongtype"));
            elseif (file_exists($gallerydir."/".$_FILES['uploadfile']['name']))
            $pagecontent .= returnMessage(false, htmlentities($_FILES['uploadfile']['name']).": ".getLanguageValue("gallery_uploadfile_exists"));
            elseif (!preg_match($specialchars->getFileCharsRegex(), $_FILES['uploadfile']['name'])) {
                $pagecontent .= returnMessage(false, htmlentities($_FILES['uploadfile']['name']).": ".getLanguageValue("invalid_values"));
            }
            else {
                // Bild und Kommentar speichern
                move_uploaded_file($_FILES['uploadfile']['tmp_name'], $gallerydir."/".$_FILES['uploadfile']['name']);
                // chmod, wenn so eingestellt
                if ($ADMIN_CONF->get("chmodnewfiles") == "true")
                chmod ($gallerydir."/".$_FILES['uploadfile']['name'], octdec($ADMIN_CONF->get("chmodnewfilesatts")));
                $galleryconf = new Properties($gallerydir."/texte.conf");
                $galleryconf->set($_FILES['uploadfile']['name'], stripslashes($_POST['comment']));
                // Vorschaubild erstellen (nur, wenn GDlib installiert ist)
                if (extension_loaded("gd")) {
                    require_once("../Thumbnail.php");
                    $tn = new Thumbnail();
                    $tn->createThumb($_FILES['uploadfile']['name'], $gallerydir."/", $gallerydir."/$PREVIEW_DIR_NAME/");
                    // chmod, wenn so eingestellt
                    if ($ADMIN_CONF->get("chmodnewfiles") == "true")
                    chmod ($gallerydir."/$PREVIEW_DIR_NAME/".$_FILES['uploadfile']['name'], octdec($ADMIN_CONF->get("chmodnewfilesatts")));
                }
                $pagecontent .= returnMessage(true, htmlentities($_FILES['uploadfile']['name']).": ".getLanguageValue("gallery_upload_success"));
            }
        }
        // Wenn "Speichern" bei "Galerie umbenennen" gedrückt wurde
        elseif (isset($_REQUEST['save_galname'])) {
            if (isset($_REQUEST["newname"]))
            $newname = stripslashes($_REQUEST["newname"]);
            // Fehlermeldung, wenn bereits Galerie mit gewünschtem Namen existiert
            if (file_exists("$GALLERIES_DIR_REL/".$specialchars->replaceSpecialChars($newname)))
            $pagecontent .= returnMessage(false, htmlentities($newname).": ".getLanguageValue("gallery_exists_error"));
            // Fehlermeldung, wenn kein Name angegeben oder nicht erlaubte Zeichen enthalten
            elseif (($newname == "") || (!preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname)))
            $pagecontent .= returnMessage(false, htmlentities($newname).": ".getLanguageValue("invalid_values"));
            // sonst: Galerieverzeichnis umbenennen
            else {
                if (@rename("$GALLERIES_DIR_REL/".$gal, "$GALLERIES_DIR_REL/".$specialchars->replaceSpecialChars($newname))) {
                    $pagecontent .= returnMessage(true, htmlentities($newname).": ".getLanguageValue("gallery_edited"));
                    $mygallery = $specialchars->replaceSpecialChars($newname);
                }
            }
        }
        // Wenn "Speichern" bei einem Galeriebild gedrückt wurde
        elseif (isset($_REQUEST['save'])) {
            $galleryconf->set($_REQUEST['image'], stripslashes($_REQUEST['comment']));
            $pagecontent .= returnMessage(true, htmlentities($_REQUEST['image']).": ".getLanguageValue("changes_applied"));
        }
        // Wenn "Löschen" bei einem Galeriebild gedrückt wurde
        elseif (isset($_REQUEST['delete'])) {
            // nach Bestätigung: löschen
            if (isset($_REQUEST['confirm'])) {
                $galleryconf->delete($_REQUEST['image']);
                if (
                @unlink("$GALLERIES_DIR_REL/".$mygallery."/".$_REQUEST['image'])
                && (!file_exists("$GALLERIES_DIR_REL/".$mygallery."/$PREVIEW_DIR_NAME/".$_REQUEST['image']) || @unlink("$GALLERIES_DIR_REL/".$mygallery."/$PREVIEW_DIR_NAME/".$_REQUEST['image']))
                )
                $pagecontent .= returnMessage(true, htmlentities($_REQUEST['image']).": ".getLanguageValue("gallery_image_deleted"));
                else
                $pagecontent .= returnMessage(false, htmlentities($_REQUEST['image']).": ".getLanguageValue("data_file_delete_error"));
            }
            // Löschbestätigung erfragen
            else
            $pagecontent .= returnMessage(false, htmlentities($_REQUEST['image']).": ".getLanguageValue("gallery_confirm_delete")." <a href=\"index.php?action=editgallery&amp;delete=true&amp;gal=".$mygallery."&amp;image=".$_REQUEST['image']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=editgallery&amp;gal=".$mygallery."\">".getLanguageValue("no")."</a>");
        }
        $pagecontent .= "<h3>".getLanguageValue("chosen_gallery")." ".$specialchars->rebuildSpecialChars($mygallery, true)."</h3>";
        $pagecontent .= "<p>".getLanguageValue("gallery_edit_text")."</p>";

        // Zeile "GALERIE UMBENENNEN"
        $pagecontent .= "<h3>".getLanguageValue("gallery_rename")."</h3>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\">";
        $pagecontent .= "<input type=\"hidden\" name=\"action\" value=\"editgallery\">";
        $pagecontent .= "<input type=\"hidden\" name=\"gal\" value=\"".$mygallery."\" />";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_gallery_name")."</td>";
        $pagecontent .= "<td class=\"config_row2\"><input class=\"Text1\" value=\"".$specialchars->rebuildSpecialChars($mygallery, true)."\" type=\"text\" name=\"newname\"></td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
        $pagecontent .= "<td class=\"config_row2\">";
        $pagecontent .= "<input value=\"".getLanguageValue("button_save")."\" type=\"submit\" name=\"save_galname\" class=\"submit\" />";
        $pagecontent .= "</tr>";
        $pagecontent .= "</table></form>";

        // Zeile "BILDDATEI WÄHLEN"
        $pagecontent .= "<h3>".getLanguageValue("gallery_upload")."</h3>";
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"editgallery\" /><input type=\"hidden\" name=\"gal\" value=\"".$mygallery."\" />";
        $pagecontent .= "<table class=\"data\">";
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_choose_file_text")."</td>";
        $pagecontent .= "<td class=\"config_row2\"><input type=\"file\" name=\"uploadfile\" /></td>";
        $pagecontent .= "</tr>";
        // Zeile "KOMMENTAR"
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_add_comment_text")."</td>";
        $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"comment\" /></td>";
        $pagecontent .= "</tr>";
        // Zeile "UPLOADEN"
        $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("button_gallery_upload")."\" /></td></tr>";
        $pagecontent .= "</table></form>";

        $pagecontent .= "<h3>".getLanguageValue("gallery_overview")."</h3>";
        // alle Bilder der Galerie auflisten
        $handle = opendir("$GALLERIES_DIR_REL/".$mygallery);
        $counter = 0;
        $gallerypics = array();
        while (($file = readdir($handle))) {
            if (is_file("$GALLERIES_DIR_REL/".$mygallery."/".$file) && ($file <> "texte.conf")) {
                array_push($gallerypics, $file);
            }
        }
        closedir($handle);
        sort($gallerypics);
        foreach ($gallerypics as $file) {
            $counter++;
            $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php#lastsavedimage\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"editgallery\"><input type=\"hidden\" name=\"gal\" value=\"".$mygallery."\"><input type=\"hidden\" name=\"image\" value=\"".$file."\">";
            $pagecontent .= "<table class=\"data\">";
            $pagecontent .= "<tr>";
            // Anker setzen, zu dem nach dem Speichern gesprungen wird
            if (isset($_REQUEST['save']) && isset($_REQUEST['image']) && ($_REQUEST['image'] == $file))
            $lastsavedanchor = " id=\"lastsavedimage\"";
            else
            $lastsavedanchor = "";
            // Vorschaubild anzeigen, wenn vorhanden; sonst Originalbild
            if (file_exists("$GALLERIES_DIR_REL/".$mygallery."/$PREVIEW_DIR_NAME/".$file))
            $pagecontent .= "<td class=\"config_row1\"".$lastsavedanchor."><img src=\"$GALLERIES_DIR_REL/".$mygallery."/$PREVIEW_DIR_NAME/".$file."\" alt=\"$file\" style=\"width:100px;\" /><br />".$file."</td>";
            else
            $pagecontent .= "<td class=\"config_row1\"".$lastsavedanchor."><img src=\"$GALLERIES_DIR_REL/".$mygallery."/".$file."\" alt=\"$file\" style=\"width:100px;\" /><br />".$file."</td>";
            $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"comment\" value=\"".htmlentities($galleryconf->get($file))."\" /><br /><input type=\"submit\" name=\"save\" value=\"".getLanguageValue("button_save")."\" class=\"submit\" /> <input type=\"submit\" name=\"delete\" value=\"".getLanguageValue("button_delete")."\" class=\"submit\" /></td>";
            $pagecontent .= "</tr>";
            $pagecontent .= "</table>";
            $pagecontent .= "</form>";
        }
        if ($counter == 0)
        {
            $pagecontent .= "<table class=\"data\">";
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_no_data")."</td>";
            $pagecontent .= "<td class=\"config_row2\"></td>";
            $pagecontent .= "</tr>";
            $pagecontent .= "</table>";
        }
    }
    return array(getLanguageValue("button_gallery_edit"), $pagecontent);
}

function deleteGallery() {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;

    if (isset($_REQUEST['gal']))
    $gal = stripslashes($_REQUEST['gal']);
    else
    $gal = "";

    // Zuerst: Kategorie wählen
    $pagecontent = "<h2>".getLanguageValue("button_gallery_delete")."</h2>";
    // Wenn die Kategorie schon gewählt wurde
    if (($gal != "") && file_exists("$GALLERIES_DIR_REL/".$gal)) {
        $mygallery = "$GALLERIES_DIR_REL/".$gal;
        if (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == "true")) {
            $success = true;
            $couldnotrmdir = false;
            // Vorschauverzeichnis leeren
            $handle = opendir($mygallery."/$PREVIEW_DIR_NAME");
            while ($file = readdir($handle)) {
                if (is_file($mygallery."/$PREVIEW_DIR_NAME/".$file)) {
                    if (!@unlink($mygallery."/$PREVIEW_DIR_NAME/".$file)) {
                        $success = false;
                    }
                }
            }
            closedir($handle);
            if (!@rmdir($mygallery."/$PREVIEW_DIR_NAME"))
            $couldnotrmdir = true;
            // Galerieverzeichnis leeren
            $handle = opendir($mygallery);
            while ($file = readdir($handle)) {
                if (is_file($mygallery."/".$file)) {
                    if (!@unlink($mygallery."/".$file)) {
                        $success = false;
                    }
                }
            }
            closedir($handle);
            if (!@rmdir($mygallery))
            $couldnotrmdir = true;
            if ($success && !$couldnotrmdir)
            $pagecontent .= returnMessage(true, $specialchars->rebuildSpecialChars($gal, true).": ".getLanguageValue("gallery_delete_success"));
            elseif ($success && $couldnotrmdir)
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars($gal, true).": ".getLanguageValue("gallery_delete_success")." ".getLanguageValue("gallery_delete_no_rmdir"));
            else
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars($gal, true).": ".getLanguageValue("gallery_delete_error"));
        }
        else {
            $pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars($gal, true).": ".getLanguageValue("gallery_confirm_deleteall")." <a href=\"index.php?action=deletegallery&amp;delete=true&amp;gal=".$gal."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletegallery\">".getLanguageValue("no")."</a>");
        }
    }
    $pagecontent .= "<p>".getLanguageValue("gallery_delete_text")."</p>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"deletegallery\">";
    $pagecontent .= "<table class=\"data\">";
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_gal_for_delete")."</td>";
    $pagecontent .= "<td class=\"config_row2\">".getGalleriesAsSelect($gal)."</td></tr>";
    $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_gallery_button")."\" /></td></tr>";
    $pagecontent .= "</table>";
    $pagecontent .= "</form>";
    return array(getLanguageValue("button_gallery_delete"), $pagecontent);
}

function files() {
    $pagecontent = "<h2>".getLanguageValue("button_data")."</h2>";
    $pagecontent .= "<p>".getLanguageValue("data_text")."</p>";
    $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
    $pagecontent .= "<ul>";
    $pagecontent .= "<li><a href=\"index.php?action=newfile\">".getLanguageValue("button_data_new")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=aboutfile\">".getLanguageValue("button_data_info")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=deletefile\">".getLanguageValue("button_data_delete")."</a></li>";
    $pagecontent .= "</ul>";
    return array(getLanguageValue("button_data"), $pagecontent);
}

function newFile() {
    global $ADMIN_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $CMS_CONF;
    $pagecontent = "<h2>".getLanguageValue("button_data_new")."</h2>";
    if (isset($_POST['cat'])) {
        $cat = stripslashes($_POST['cat']);
    }
    else {
        $cat = "";
    }

    // maximale Anzahl Dateien (Standardwert nehmen, wenn ungültiger Wert konfiguriert wurde)
    $maxnumberoffiles = $ADMIN_CONF->get("maxnumberofuploadfiles");
    if (!is_numeric($maxnumberoffiles) || ($maxnumberoffiles < 1)) {
        $maxnumberoffiles = 5;
    }

    // Überschreiben erzwungen?
    $forceoverwrite = "";
    if (isset($_POST['overwrite'])) {
        $forceoverwrite = $_POST['overwrite'];
    }

    // Kein JS aktiviert? Dann nur simpler Single-File-Upload
    if (isset($_FILES['uploadfile']) and !$_FILES['uploadfile']['error']) {
        $pagecontent .= uploadFile($_FILES['uploadfile'], $cat, $forceoverwrite);
    }
    // ansonsten ist JS aktiviert: Multi-Upload
    else {
        for ($uploads = 0; $uploads < $maxnumberoffiles; $uploads++) {
            if (isset($_FILES['file_'.$uploads])) {
                $pagecontent .= uploadFile($_FILES['file_'.$uploads], $cat, $forceoverwrite);
            }
        }
    }
    $pagecontent .= "<p>".getLanguageValue("data_new_text")."</p>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"newfile\" />";
    $pagecontent .= "<table><tr>";
    // Kategorie auswählen
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("data_choose_category_text")."</td>"
    ."<td class=\"config_row2\">".getCatsAsSelect($specialchars->replaceSpecialChars($cat))."</td></tr>";
    // Dateien auswählen
    $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_choose_file_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"file\" id=\"uploadfileinput\" name=\"uploadfile\" />"
    ."<div id=\"files_list\"></div>"
    ."<script>\n"
    ."<!-- Create an instance of the multiSelector class, pass it the output target and the max number of files -->\n"
    ."var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), ".$maxnumberoffiles.", '".getLanguageValue("data_delete_file_text")."' );\n"
    ."<!-- Pass in the file element -->\n"
    ."multi_selector.addElement( document.getElementById( 'uploadfileinput' ) );\n"
    ."</script>"
    ."</td></tr>";
    // Checkbox "Überschreiben"
    $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_overwrite_text")."</td>"
    ."<td class=\"config_row2\">"
    .buildCheckBox("overwrite", ($ADMIN_CONF->get("overwriteuploadfiles") == "true"))
    .getLanguageValue("data_overwrite_text2")."</td></tr>";
    // Button
    $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("button_data_new")."\" /></td></tr>";
    $pagecontent .= "</table></form>";
    return array(getLanguageValue("button_data_new"), $pagecontent);
}

function aboutFile() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $DOWNLOAD_COUNTS;
    $pagecontent = "<h2>".getLanguageValue("button_data_info")."</h2>"
    ."<p>".getLanguageValue("data_info_text")." ".strftime(getLanguageValue("_dateformat"), $DOWNLOAD_COUNTS->get("_downloadcounterstarttime"))."</p>";
    $dirs = getDirs("$CONTENT_DIR_REL");
    foreach ($dirs as $file) {
        $file = $file."_".specialNrDir("$CONTENT_DIR_REL", $file);
        if (isValidDirOrFile($file) && ($subhandle = opendir("$CONTENT_DIR_REL/".$file."/dateien"))) {
            $pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</h3>";
            $hasdata = false;
            $pagecontent .= "<table class=\"data\">";
            $mysubfiles = array();
            while (($subfile = readdir($subhandle))) {
                array_push($mysubfiles, $subfile);
            }
            closedir($subhandle);
            sort($mysubfiles);
            foreach ($mysubfiles as $subfile) {
                if (isValidDirOrFile($subfile)) {
                    $downloads = $DOWNLOAD_COUNTS->get($file.":".$subfile);
                    $countword = getLanguageValue("data_downloads"); // Plural
                    if ($downloads == "1")
                    $countword = getLanguageValue("data_download"); // Singular
                    if ($downloads == "")
                    $downloads = "0";
                    // Downloads pro Tag berechnen
                    $uploadtime = filemtime("$CONTENT_DIR_REL/$file/dateien/$subfile");
                    $counterstart = $DOWNLOAD_COUNTS->get("_downloadcounterstarttime");
                    // Berechnungsgrundlage für "Downloads pro Tag":
                    // Entweder Upload-Zeitpunkt oder Beginn der Statistik - genommen wird der spätere Zeitpunkt
                    if ($uploadtime > $counterstart)
                    $starttime = $uploadtime;
                    else
                    $starttime = $counterstart;
                    $dayscounted = ceil((time() - $starttime) / (60*60*24));
                    if ($dayscounted == 0)
                    $downloadsperday = 0;
                    else
                    $downloadsperday = round(($downloads/$dayscounted), 2);
                    if ($downloads > 0)
                    $downloadsperdaytext = "<br />(".$downloadsperday." ".getLanguageValue("data_downloadsperday").")";
                    else
                    $downloadsperdaytext = "";
                    // Dateigröße
                    $filesize = filesize("$CONTENT_DIR_REL/$file/dateien/$subfile");
                    $pagecontent .= "<tr><td class=\"config_row0\">$subfile</td>"
                    ."<td class=\"config_row1\">".convertFileSizeUnit($filesize)."</td>"
                    ."<td class=\"config_row1\">".strftime(getLanguageValue("_dateformat"), $uploadtime)."</td>"
                    ."<td class=\"config_row2\">".$downloads." ".$countword.$downloadsperdaytext."</td></tr>";
                    $hasdata = true;
                }
            }
            if (!$hasdata)
            $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
            $pagecontent .= "</table>";
        }
    }
    return array(getLanguageValue("button_data_info"), $pagecontent);
}

function deleteFile() {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $DOWNLOAD_COUNTS;

    if (isset($_REQUEST['cat']))
    $cat = stripslashes($_REQUEST['cat']);
    if (isset($_REQUEST['file']))
    $file = stripslashes($_REQUEST['file']);


    $pagecontent = "<h2>".getLanguageValue("button_data_delete")."</h2>";
    // Löschen der Dateien nach Auswertung der übergebenen Parameter
    if (isset($cat) && isset($file) && file_exists("$CONTENT_DIR_REL/".$cat) && file_exists("$CONTENT_DIR_REL/".$cat."/dateien/".$file)) {
        if (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == "true")) {
            if (@unlink("$CONTENT_DIR_REL/".$cat."/dateien/".$file)) {
                // Datei und dazugehörigen Downloadcounter löschen
                $pagecontent .= returnMessage(true, htmlentities($file).": ".getLanguageValue("data_file_deleted"));
                $DOWNLOAD_COUNTS->delete($cat.":".$file);
            }
            else
            $pagecontent .= returnMessage(false, htmlentities($file).": ".getLanguageValue("data_file_delete_error"));
        }
        else
        $pagecontent .= returnMessage(false, htmlentities($file).": ".getLanguageValue("data_file_delete_confirm")." <a href=\"index.php?action=deletefile&amp;cat=".$cat."&amp;file=".$file."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletefile\">".getLanguageValue("no")."</a>");
    }
    $pagecontent .= "<p>".getLanguageValue("data_delete_text")."</p>";
    $dirs = getDirs("$CONTENT_DIR_REL");
    foreach ($dirs as $file) {
        $file = $file."_".specialNrDir("$CONTENT_DIR_REL", $file);
        if (isValidDirOrFile($file) && ($subhandle = opendir("$CONTENT_DIR_REL/".$file."/dateien"))) {
            $pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</h3>";
            $hasdata = false;
            $pagecontent .= "<table class=\"data\">";
            $mysubfiles = array();
            while (($subfile = readdir($subhandle))) {
                array_push($mysubfiles, $subfile);
            }
            closedir($subhandle);
            sort($mysubfiles);
            foreach ($mysubfiles as $subfile) {
                if (isValidDirOrFile($subfile)) {
                    $pagecontent .= "<tr><td class=\"config_row1\">$subfile</td>"
                    ."<td class=\"config_row2 righttext\">"
                    ."<a href=\"$CONTENT_DIR_REL/".$file."/dateien/".$subfile."\" target=\"_blank\" class=\"imagelink\">".getActionIcon("download", getLanguageValue("data_download"))."</a>"
                    ."&nbsp;<a href=\"index.php?action=deletefile&amp;cat=".$file."&amp;file=".$subfile."\" class=\"imagelink\">".getActionIcon("delete", getLanguageValue("button_delete"))."</a>"
                    ."</td></tr>";
                    $hasdata = true;
                }
            }
            if (!$hasdata) {
                $pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
            }
            $pagecontent .= "</table>";
        }
    }
    return array(getLanguageValue("button_data_delete"), $pagecontent);
}

function config() {
    $pagecontent = "<h2>".getLanguageValue("button_config")."</h2>";
    $pagecontent .= "<p>".getLanguageValue("config_text")."</p>";
    $pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
    $pagecontent .= "<ul>";
    $pagecontent .= "<li><a href=\"index.php?action=displaycmsconfig\">".getLanguageValue("button_config_displaycms")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=displayadminconfig\">".getLanguageValue("button_config_displayadmin")."</a></li>";
    $pagecontent .= "<li><a href=\"index.php?action=loginadminconfig\">".getLanguageValue("button_config_loginadmin")."</a></li>";
    $pagecontent .= "</ul>";
    return array(getLanguageValue("button_config"), $pagecontent);
}

function configCMSDisplay() {
    global $CMS_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $USER_SYNTAX_FILE;
    global $CONTACT_CONF;

    $pagecontent = "<h2>".getLanguageValue("button_config_displaycms")."</h2>";
    // Änderungen speichern
    $changesmade = false;
    if (isset($_REQUEST['apply']) && ($_REQUEST['apply'] == "true")) {
        $changesapplied = false;
        if (
        isValidRequestParameter("gmw", 1)
        && isValidRequestParameter("gmh", 1)
        && isValidRequestParameter("title", 2)
        && isValidRequestParameter("description", 6) // darf leer sein
        && isValidRequestParameter("keywords", 6) // darf leer sein
        && isValidRequestParameter("layout", 2)
        && isValidRequestParameter("gthumbs", 2)
        && isValidRequestParameter("gppr", 1)
        && isValidRequestParameter("dcat", 2)
        && isValidRequestParameter("syntaxslinks", 2)
        && isValidRequestParameter("lang", 2)
        && isValidRequestParameter("titlebarformat", 2)
        ) {
            $CMS_CONF->set("websitetitle", htmlentities(stripslashes($_REQUEST['title'])));
            $CMS_CONF->set("websitedescription", htmlentities(stripslashes($_REQUEST['description'])));
            $CMS_CONF->set("websitekeywords", htmlentities(stripslashes($_REQUEST['keywords'])));
            $CMS_CONF->set("galleryusethumbs", $_REQUEST['gthumbs']);
            $CMS_CONF->set("gallerypicsperrow", $_REQUEST['gppr']);
            $CMS_CONF->set("gallerymaxwidth", $_REQUEST['gmw']);
            $CMS_CONF->set("gallerymaxheight", $_REQUEST['gmh']);
            $CMS_CONF->set("defaultcat", $specialchars->replaceSpecialChars($_REQUEST['dcat']));
            $CMS_CONF->set("shortenlinks", $_REQUEST['syntaxslinks']);
            $CMS_CONF->set("cmslanguage", $_REQUEST['lang']);
            $CMS_CONF->set("titlebarformat", $_REQUEST['titlebarformat']);
            $titlesep = $_REQUEST['titlesep'];
            $titlesep = preg_replace('/\s/', "&nbsp;", htmlspecialchars($titlesep));
            $CMS_CONF->set("titlebarseparator", $titlesep);
            $CMS_CONF->set("usesubmenu", $_REQUEST['usesubmenu']);

            if (checkBoxIsChecked('usesyntax'))
            $CMS_CONF->set("usecmssyntax", "true");
            else
            $CMS_CONF->set("usecmssyntax", "false");

            if (checkBoxIsChecked('replaceemoticons'))
            $CMS_CONF->set("replaceemoticons", "true");
            else
            $CMS_CONF->set("replaceemoticons", "false");
                
            /*            if (checkBoxIsChecked('targetblank_link'))
                $CMS_CONF->set("targetblank_link", "true");
                else
                $CMS_CONF->set("targetblank_link", "false");

                if (checkBoxIsChecked('targetblank_gallery'))
                $CMS_CONF->set("targetblank_gallery", "true");
                else
                $CMS_CONF->set("targetblank_gallery", "false");

                if (checkBoxIsChecked('targetblank_download'))
                $CMS_CONF->set("targetblank_download", "true");
                else
                $CMS_CONF->set("targetblank_download", "false");
                */

            $contactoptions = array("name", "mail", "website", "message");
            foreach ($contactoptions as $contactoption) {
                if (checkBoxIsChecked('show_'.$contactoption)) {
                    $show = "true";
                } else {
                $show = "false";
                }
                if (checkBoxIsChecked('mandatory_'.$contactoption)) {
                    $mandatory = "true";
                } else {
                    $mandatory = "false";
                }
                $CONTACT_CONF->set($contactoption, $show.",".$mandatory);
            }

                
            // Speichern der benutzerdefinierten Syntaxelemente -> ERWEITERN UM PRÜFUNG!
            $handle = @fopen($USER_SYNTAX_FILE, "w");
            fputs($handle, stripcslashes($_REQUEST['usersyntax']));
            fclose($handle);


            // Layout und layoutabhängige Einstellungen setzen
            setLayoutAndDependentSettings($_REQUEST['layout']);

            $pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
        }
        else
        $pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
    }
    $pagecontent .= "<p>".getLanguageValue("config_cmsdisplay_text")."</p>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"displaycmsconfig\"><input type=\"hidden\" name=\"apply\" value=\"true\">";

    // ALLGEMEINE EINSTELLUNGEN
    $pagecontent .= "<h3>".getLanguageValue("config_cmsglobal_headline")."</h3>";
    $pagecontent .= "<table class=\"data\">";
    // Zeile "WEBSITE-TITEL"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitetitle_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"title\" value=\"".$CMS_CONF->get("websitetitle")."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "WEBSITE-TITELLEISTE"
    $titlebarsep = $CMS_CONF->get("titlebarseparator");
    $txt_websitetitle = getLanguageValue("websitetitle");
    $txt_category = getLanguageValue("category");
    $txt_page = getLanguageValue("page");
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitetitlebar_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><select name=\"titlebarformat\" class=\"maxwidth\">";
    $titlebarformats = array(
            "{WEBSITE}{SEP}{CATEGORY}{SEP}{PAGE}",
            "{WEBSITE}{SEP}{CATEGORY}",
            "{WEBSITE}{SEP}{PAGE}",
            "{CATEGORY}{SEP}{PAGE}{SEP}{WEBSITE}",
            "{CATEGORY}{SEP}{WEBSITE}",
            "{PAGE}{SEP}{WEBSITE}",
            "{WEBSITE}",
            "{CATEGORY}{SEP}{PAGE}",
            "{PAGE}"
            );
            $selected = "";
            foreach ($titlebarformats as $titlebarformat) {
                if ($titlebarformat == $CMS_CONF->get("titlebarformat"))
                $selected = "selected ";
                $text = preg_replace('/{WEBSITE}/', $txt_websitetitle, $titlebarformat);
                $text = preg_replace('/{CATEGORY}/', $txt_category, $text);
                $text = preg_replace('/{PAGE}/', $txt_page, $text);
                $text = preg_replace('/{SEP}/', $titlebarsep, $text);
                $pagecontent .= "<option ".$selected."value=\"".$titlebarformat."\">".$text."</option>";
                $selected = "";
            }
            $pagecontent .= "</select></td>";
            $pagecontent .= "</tr>";
            // Zeile "TITEL-TRENNER"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitetitleseparator_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"titlesep\" value=\"".$CMS_CONF->get("titlebarseparator")."\" /></td>";
            $pagecontent .= "</tr>";
            // Zeile "WEBSITE-BESCHREIBUNG"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitedescription_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"description\" value=\"".$CMS_CONF->get("websitedescription")."\" /></td>";
            $pagecontent .= "</tr>";
            // Zeile "WEBSITE-KEYWORDS"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitekeywords_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"keywords\" value=\"".$CMS_CONF->get("websitekeywords")."\" /></td>";
            $pagecontent .= "</tr>";
            // Zeile "SPRACHAUSWAHL"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("cmslanguage_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\"><select name=\"lang\" class=\"maxwidth\">";
            if ($handle = opendir('../sprachen')){
                while ($file = readdir($handle)) {
                    $selected = "";
                    if (isValidDirOrFile($file)) {
                        if (substr($file,0,strlen($file)-strlen(".conf")) == $CMS_CONF->get("cmslanguage"))
                        $selected = " selected";
                        $pagecontent .= "<option".$selected." value=\"".substr($file,0,strlen($file)-strlen(".conf"))."\">";
                        // Übersetzer aus der aktuellen Sprachdatei holen
                        $languagefile = new Properties("../sprachen/$file");
                        $pagecontent .= substr($file,0,strlen($file)-strlen(".conf"))." (".getLanguageValue("translator_text")." ".$languagefile->get("_translator_0").")";
                        $pagecontent .= "</option>";
                    }
                }
                closedir($handle);
            }
            $pagecontent .= "</select></td></tr>";
            // Zeile "LAYOUTAUSWAHL"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("cmslayout_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\"><select name=\"layout\" class=\"maxwidth\">";
            if ($handle = opendir('../layouts')){
                $layoutdirsarray = array();
                while ($file = readdir($handle)) {
                    array_push($layoutdirsarray, $file);
                }
                closedir($handle);
                natcasesort($layoutdirsarray);
                foreach ($layoutdirsarray as $file) {
                    $selected = "";
                    if (isValidDirOrFile($file)) {
                        if ($file == $CMS_CONF->get("cmslayout"))
                        $selected = " selected";
                        $pagecontent .= "<option".$selected." value=\"".$file."\">";
                        // Übersetzer aus der aktuellen Sprachdatei holen
                        $pagecontent .= $specialchars->rebuildSpecialChars($file, true);
                        $pagecontent .= "</option>";
                    }
                }
            }
            $pagecontent .= "</select></td></tr>";
            // Zeile "STANDARD-KATEGORIE"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("defaultcat_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\">";
            $dirs = array();
            $dirs = getDirs("$CONTENT_DIR_REL");
            $pagecontent .= "<select name=\"dcat\" class=\"maxwidth\">";
            foreach ($dirs as $element) {
                $myfiles = getFiles("$CONTENT_DIR_REL/".$element."_".specialNrDir("$CONTENT_DIR_REL", $element), "");
                if (count($myfiles) == 0)
                continue;
                $selected = "";
                if ($element."_".$specialchars->rebuildSpecialChars(specialNrDir("$CONTENT_DIR_REL", $element), true) == $CMS_CONF->get("defaultcat"))
                $selected = "selected ";
                $pagecontent .= "<option ".$selected."value=\"".$element."_".$specialchars->rebuildSpecialChars(specialNrDir("$CONTENT_DIR_REL", $element), true)."\">".$specialchars->rebuildSpecialChars(specialNrDir("$CONTENT_DIR_REL", $element), true)."</option>";
            }
            $pagecontent .= "</select></td>";
            $pagecontent .= "</tr>";
            // Zeile "NUTZE SUBMENÜ"
            $checked0 = "";
            $checked1 = "";
            $checked2 = "";
            if ($CMS_CONF->get("usesubmenu") == "2")
            $checked2 = " checked=\"checked\"";
            elseif ($CMS_CONF->get("usesubmenu") == "1")
            $checked1 = " checked=\"checked\"";
            else
            $checked0 = " checked=\"checked\"";
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("usesubmenu_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\">";
            $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"0\"$checked0 />".getLanguageValue("usesubmenu_text2")."<br />";
            $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"1\"$checked1 />".getLanguageValue("usesubmenu_text3")."<br />";
            $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"2\"$checked2 />".getLanguageValue("usesubmenu_text4")."<br />";
            $pagecontent .= "</td>";
            $pagecontent .= "</tr>";
            $pagecontent .= "</table>";

            // SYNTAX-EINSTELLUNGEN
            $pagecontent .= "<h3>".getLanguageValue("config_cmssyntax_headline")."</h3>";
            $pagecontent .= "<table class=\"data\">";
            // Zeile "NUTZE CMS-SYNTAX"
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("usesyntax_text")."</td>";
            $pagecontent .= "<td class=\"config_row2\">"
            .buildCheckBox("usesyntax", ($CMS_CONF->get("usecmssyntax") == "true"))
            .getLanguageValue("usesyntax_text2");
            // Wenn die CMS-Syntax deaktiviert ist: Die anderen Werte per Hidden-Inputs durchreichen
            if ($CMS_CONF->get("usecmssyntax") != "true") {
                // Links kürzen (Wert "0" extra setzen, damit der Parameter nicht leer durchgereicht wird)
                $shortenthem = $CMS_CONF->get("shortenlinks");
                if ($shortenthem == "")
                $shortenthem = "0";
                $pagecontent .= "<input type=\"hidden\" name=\"syntaxslinks\" value=\"".$shortenthem."\" />";
                // Benutzerdefinierte Syntaxelemente
                $usersyntaxdefs = "";
                if (file_exists($USER_SYNTAX_FILE)) {
                    $handle = @fopen($USER_SYNTAX_FILE, "r");
                    $usersyntaxdefs = @fread($handle, @filesize($USER_SYNTAX_FILE));
                    @fclose($handle);
                }
                $pagecontent .= "<input type=\"hidden\" name=\"usersyntax\" value=\"".htmlentities($usersyntaxdefs)."\" />";
                // Ersetze Emoticons
                if ($CMS_CONF->get("replaceemoticons") == "true")
                $replacethem = "on";
                else
                $replacethem = "off";
                $pagecontent .= "<input type=\"hidden\" name=\"replaceemoticons\" value=\"".$CMS_CONF->get("shortenlinks")."\" />";
            }
            $pagecontent .= "</td></tr>";
            // Die folgenden Einstellungen werden nur angezeigt, wenn die CMS-Syntax aktiv ist
            if ($CMS_CONF->get("usecmssyntax") == "true") {
                // Zeile "LINKS KÜRZEN"
                $checked0 = "";
                $checked1 = "";
                $checked2 = "";
                if ($CMS_CONF->get("shortenlinks") == "2")
                $checked2 = " checked=\"checked\"";
                elseif ($CMS_CONF->get("shortenlinks") == "1")
                $checked1 = " checked=\"checked\"";
                else
                $checked0 = " checked=\"checked\"";
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("syntaxshortenlinks_text")."</td>";
                $pagecontent .= "<td class=\"config_row2\">";
                $pagecontent .= "<input type=\"radio\" name=\"syntaxslinks\" value=\"0\"$checked0 />http://www.domain.com<br />";
                $pagecontent .= "<input type=\"radio\" name=\"syntaxslinks\" value=\"1\"$checked1 />www.domain.com<br />";
                $pagecontent .= "<input type=\"radio\" name=\"syntaxslinks\" value=\"2\"$checked2 />domain.com<br />";
                $pagecontent .= "</td>";
                $pagecontent .= "</tr>";
                // Zeile "LINKS IN NEUEM FENSTER ÖFFNEN"
                /*                $targetblank_link_checked = "";
                $targetblank_gallery_checked = "";
                $targetblank_download_checked = "";
                // externe Links
                if ($CMS_CONF->get("targetblank_link") == "true")
                $targetblank_link_checked .= " checked=checked";
                // Galerie-Links
                if ($CMS_CONF->get("targetblank_gallery") == "true")
                $targetblank_gallery_checked .= " checked=checked";
                // Download-Links
                if ($CMS_CONF->get("targetblank_download") == "true")
                $targetblank_download_checked .= " checked=checked";
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("opentargetblank_text")."</td>";
                $pagecontent .= "<td class=\"config_row2\">"
                ."<input type=\"checkbox\"".$targetblank_link_checked." name=\"targetblank_link\" />".getLanguageValue("targetblank_link_text");
                $pagecontent .= "<br /><input type=\"checkbox\"".$targetblank_gallery_checked." name=\"targetblank_gallery\" />".getLanguageValue("targetblank_gallery_text");
                $pagecontent .= "<br /><input type=\"checkbox\"".$targetblank_download_checked." name=\"targetblank_download\" />".getLanguageValue("targetblank_download_text");
                */
                // Zeile "BENUTZERDEFINIERTE SYNTAX-ELEMENTE"
                $usersyntaxdefs = "";
                if (file_exists($USER_SYNTAX_FILE)) {
                    $handle = @fopen($USER_SYNTAX_FILE, "r");
                    $usersyntaxdefs = @fread($handle, @filesize($USER_SYNTAX_FILE));
                    @fclose($handle);
                }
                $pagecontent .= "<tr><td class=\"config_row1\" colspan=\"2\">".getLanguageValue("usersyntax_text")."<br />";
                $pagecontent .= "<textarea class=\"usersyntaxarea\" name=\"usersyntax\">".htmlentities($usersyntaxdefs)."</textarea></td></tr>";
                // Zeile "ERSETZE EMOTICONS"
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("replaceemoticons_text")."</td>";
                $pagecontent .= "<td class=\"config_row2\">"
                .buildCheckBox("replaceemoticons", ($CMS_CONF->get("replaceemoticons") == "true"))
                .getLanguageValue("replaceemoticons_text2")."</td>";
                $pagecontent .= "</tr>";
            }

            $pagecontent .= "</table>";

            // GALERIE-EINSTELLUNGEN
            $pagecontent .= "<h3>".getLanguageValue("config_cmsgallery_headline")."</h3>";
            $pagecontent .= "<table class=\"data\">";
            // Zeile "GALERIE IM EINZEL- ODER ÜBERSICHT-MODUS" (nur, wenn GDlib installiert ist)
            if (extension_loaded("gd")) {
                $checked1 = "";
                $checked2 = "";
                if ($CMS_CONF->get("galleryusethumbs") == "true")
                $checked1 = "checked=\"checked\" ";
                else
                $checked2 = "checked=\"checked\" ";
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("galleryusethumbs_text")."</td>";
                $pagecontent .= "<td class=\"config_row2\"><input type=\"radio\" name=\"gthumbs\" value=\"true\"$checked1 />".getLanguageValue("galleryusethumbs_yes")."<br /><input type=\"radio\" name=\"gthumbs\" value=\"false\"$checked2 />".getLanguageValue("galleryusethumbs_no")."</td>";
                $pagecontent .= "</tr>";
            }

            if (extension_loaded("gd") && ($CMS_CONF->get("galleryusethumbs") == "true")) {
                // "ANZAHL VORSCHAUBILDER IN EINER ZEILE"
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallerypicsperrow_text");
                $pagecontent .= "<input type=\"hidden\" name=\"gmw\" value=\"".$CMS_CONF->get("gallerymaxwidth")."\">";
                $pagecontent .= "<input type=\"hidden\" name=\"gmh\" value=\"".$CMS_CONF->get("gallerymaxheight")."\">";
                $pagecontent .= "</td>";
                $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"gppr\" value=\"".$CMS_CONF->get("gallerypicsperrow")."\" /></td>";
                $pagecontent .= "</tr>";
            }

            // wenn GDlib nicht installiert ist oder Benutzer Einzelmodus gewählt hat
            if (!extension_loaded("gd") || ($CMS_CONF->get("galleryusethumbs") != "true")) {
                // Zeile "MAXIMALE BILDBREIE GALERIE"
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallerymaxwidth_text") . getLanguageValue("gallerymaxheight_text");
                $pagecontent .= "<input type=\"hidden\" name=\"gppr\" value=\"".$CMS_CONF->get("gallerypicsperrow")."\">";
                if (!extension_loaded("gd"))
                $pagecontent .= "<input type=\"hidden\" name=\"gthumbs\" value=\"false\">";
                $pagecontent .= "</td>";
                $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text3\" name=\"gmw\" value=\"".$CMS_CONF->get("gallerymaxwidth")."\" />&nbsp;x&nbsp;<input type=\"text\" class=\"text3\" name=\"gmh\" value=\"".$CMS_CONF->get("gallerymaxheight")."\" />&nbsp;" . getLanguageValue("pixels") . "</td>";
                $pagecontent .= "</tr>";
                // Zeile "MAXIMALE BILDHÖHE GALERIE"
                $pagecontent .= "<tr>";
                $pagecontent .= "<td class=\"config_row1\"></td>";
                $pagecontent .= "<td class=\"config_row2\"></td>";
                $pagecontent .= "</tr>";
            }
            $pagecontent .= "</table>";
                
                

            // KONTAKTFORMULAR-EINSTELLUNGEN
            $pagecontent .= "<h3>".getLanguageValue("config_contactform_headline")."</h3>";
            $pagecontent .= "<table class=\"data\">";
            // Zeile "ANGEZEIGTE FELDER / PFLICHTFELDER"
            $config_name = explode(",", ($CONTACT_CONF->get("name")));
            $config_mail = explode(",", ($CONTACT_CONF->get("mail")));
            $config_website = explode(",", ($CONTACT_CONF->get("website")));
            $config_message = explode(",", ($CONTACT_CONF->get("message")));
            $pagecontent .= "<tr>";
            $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("contactformfields_text")."</td>"
            ."<td class=\"config_row2\">"
            ."<table>"
            ."<tr><td>Name</td><td>".buildCheckBox("show_name", ($config_name[0] == "true"))." ".getLanguageValue("contactform_show_text")."</td><td>".buildCheckBox("mandatory_name", ($config_name[1] == "true"))." ".getLanguageValue("contactform_mandatory_text")."</td></tr>"
            ."<tr><td>Mail</td><td>".buildCheckBox("show_mail", ($config_mail[0] == "true"))." ".getLanguageValue("contactform_show_text")."</td><td>".buildCheckBox("mandatory_mail", ($config_mail[1] == "true"))." ".getLanguageValue("contactform_mandatory_text")."</td></tr>"
            ."<tr><td>Website</td><td>".buildCheckBox("show_website", ($config_website[0] == "true"))." ".getLanguageValue("contactform_show_text")."</td><td>".buildCheckBox("mandatory_website", ($config_website[1] == "true"))." ".getLanguageValue("contactform_mandatory_text")."</td></tr>"
            ."<tr><td>Nachricht</td><td>".buildCheckBox("show_message", ($config_message[0] == "true"))." ".getLanguageValue("contactform_show_text")."</td><td>".buildCheckBox("mandatory_message", ($config_message[1] == "true"))." ".getLanguageValue("contactform_mandatory_text")."</td></tr>"
            ."</table></td>";
            $pagecontent .= "</tr>";
                
            // Zeile "ÜBERNEHMEN"
            $pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>";
            $pagecontent .= "</table>";
                
            $pagecontent .= "</form>";
            return array(getLanguageValue("button_config_displaycms"), $pagecontent);
}

function configAdminDisplay() {
    global $ADMIN_CONF;
    global $CMS_CONF;
    global $MAILFUNCTIONS;

    // Änderungen gespeichert
    $changesmade = false;

    $pagecontent = "<h2>".getLanguageValue("button_config_displayadmin")."</h2>";

    // Testmail schicken
    if (isset($_REQUEST['test'])) {
        if ($ADMIN_CONF->get("adminmail") != "") {
            $MAILFUNCTIONS->sendMailToAdmin(getLanguageValue("mailtest_mailsubject"), getLanguageValue("mailtest_mailcontent"));
            $pagecontent .= returnMessage(true, getLanguageValue("testmail_sent"));
        }
        else {
            $pagecontent .= returnMessage(false, getLanguageValue("testmail_noadress"));
        }
    }

    // Auswertung des Formulars
    if (isset($_REQUEST['apply'])) {
        if (checkBoxIsChecked('tooltip')) {
            $ADMIN_CONF->set("showTooltips", "true");
            $changesmade = true;
        }
        else
        $ADMIN_CONF->set("showTooltips", "false");
        if (isset($_REQUEST['lang'])) {
            $ADMIN_CONF->set("language", $_REQUEST['lang']);
            $changesmade = true;
        }
        if (isValidRequestParameter("noupload",6)) {
            $ADMIN_CONF->set("noupload", $_REQUEST['noupload']);
            $changesmade = true;
        }
        if (isValidRequestParameter("textareaheight",1)) {
            $height = $_REQUEST['textareaheight'];
            if ($height < 50)
            $height = 50;
            elseif ($height > 1000)
            $height = 1000;
            $ADMIN_CONF->set("textareaheight", $height);
            $changesmade = true;
        }
        if (isValidRequestParameter("backupmsgintervall",1)) {
            $ADMIN_CONF->set("backupmsgintervall", $_REQUEST['backupmsgintervall']);
            $changesmade = true;
        }
        if (checkBoxIsChecked('overwrite')) {
            $ADMIN_CONF->set("overwriteuploadfiles", "true");
            $changesmade = true;
        }
        else {
            $ADMIN_CONF->set("overwriteuploadfiles", "false");
        }
	if (checkBoxIsChecked('chmodnewfiles') and preg_match("/^[0-7]{3}$/", $_REQUEST['chmodnewfilesatts'])) {
                $ADMIN_CONF->set("chmodnewfiles", "true");
                $ADMIN_CONF->set("chmodnewfilesatts", $_REQUEST['chmodnewfilesatts']);
                $changesmade = true;
	} else {
                $ADMIN_CONF->set("chmodnewfiles", "false");
                $ADMIN_CONF->set("chmodnewfilesatts", "");
                $changesmade = true;
            }


        if (checkBoxIsChecked('sendadminmail') and isValidRequestParameter("adminmail",3)) {
            $ADMIN_CONF->set("sendadminmail", "true");
            $ADMIN_CONF->set("adminmail", $_REQUEST['adminmail']);
            $changesmade = true;
        } else {
            $ADMIN_CONF->set("sendadminmail", "false");
            $ADMIN_CONF->set("adminmail", "");
            $changesmade = true;
        }

        if (isValidRequestParameter("maxnumberofuploadfiles",1)) {
            $maxnumberofuploadfiles = $_REQUEST['maxnumberofuploadfiles'];
            if ($maxnumberofuploadfiles < 1) {
                $maxnumberofuploadfiles = 5;
            }
            $ADMIN_CONF->set("maxnumberofuploadfiles", $maxnumberofuploadfiles);
            $changesmade = true;
        }

        // maximale Größer der Uploadimages
	if(checkBoxIsChecked('resizeimages') and isValidRequestParameter("imagewidth",1) and isValidRequestParameter("imageheight",1)) {
                $ADMIN_CONF->set("maximageheight", $_REQUEST['imageheight']);
                $ADMIN_CONF->set("maximagewidth", $_REQUEST['imagewidth']);
		$ADMIN_CONF->set("resizeimages", "true");
                $changesmade = true;
	} elseif(checkBoxIsChecked('resizeimages') and isValidRequestParameter("imagewidth",1) and empty($_REQUEST['imageheight'])) {
		$ADMIN_CONF->set("maximagewidth", $_REQUEST['imagewidth']);
		$ADMIN_CONF->set("maximageheight", floor((($_REQUEST['imagewidth'] / 4) * 3)));
		$ADMIN_CONF->set("resizeimages", "true");
                $changesmade = true;
	} elseif(checkBoxIsChecked('resizeimages') and isValidRequestParameter("imageheight",1) and empty($_REQUEST['imagewidth'])) {
                $ADMIN_CONF->set("maximageheight", $_REQUEST['imageheight']);
                $ADMIN_CONF->set("maximagewidth", floor((($_REQUEST['imageheight'] / 3) * 4)));
		$ADMIN_CONF->set("resizeimages", "true");
		$changesmade = true;
	} elseif(checkBoxIsChecked('resizeimages') and empty($_REQUEST['imageheight']) and empty($_REQUEST['imagewidth'])) {
		$ADMIN_CONF->set("maximageheight", 600);
		$ADMIN_CONF->set("maximagewidth", 800);

            $ADMIN_CONF->set("resizeimages", "true");
            $changesmade = true;
	} else {
		$ADMIN_CONF->set("maximageheight", "");
		$ADMIN_CONF->set("maximagewidth", "");
            $ADMIN_CONF->set("resizeimages", "false");
            $changesmade = true;
        }
    }
	if(isset($_REQUEST['apply']) and (!isValidRequestParameter("noupload",2)
		or !isValidRequestParameter("textareaheight",1)
		or !isValidRequestParameter("backupmsgintervall",1)
		or (empty($_REQUEST['chmodnewfilesatts']) and checkBoxIsChecked('chmodnewfiles'))
		or (!empty($_REQUEST['chmodnewfilesatts']) and checkBoxIsChecked('chmodnewfiles') and !preg_match("/^[0-7]{3}$/", $_REQUEST['chmodnewfilesatts']))
		or (empty($_REQUEST['adminmail']) and checkBoxIsChecked('sendadminmail'))
		or (!empty($_REQUEST['adminmail']) and checkBoxIsChecked('sendadminmail') and !isValidRequestParameter("adminmail",3))
		or !isValidRequestParameter("maxnumberofuploadfiles",1)
		or (!empty($_REQUEST['imagewidth']) and checkBoxIsChecked('resizeimages') and !isValidRequestParameter("imagewidth",1))
		or (!empty($_REQUEST['imageheight']) and checkBoxIsChecked('resizeimages') and !isValidRequestParameter("imageheight",1)))) {
 			$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
			$changesmade = false;
    }

    if ($changesmade) {
        $pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
    }
    $pagecontent .= "<p>".getLanguageValue("config_admindisplay_text")."</p>";
    $pagecontent .= "<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"displayadminconfig\"><input type=\"hidden\" name=\"apply_\" value=\"true\">";
    $pagecontent .= "<table class=\"data\">";
    // Zeile "ZEIGE TOOLTIPS"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("showTooltips_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\">"
    .buildCheckBox("tooltip", (showTooltips()=="true"))
    .getLanguageValue("showTooltips_text2")."</td>";
    $pagecontent .= "</tr>";
    // Zeile "SPRACHAUSWAHL"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("languagechoose", "language_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("selectLanguage_text")."</td><td class=\"config_row2\"><select name=\"lang\" class=\"maxwidth\">";
    if ($handle = opendir('conf')){
        while ($file = readdir($handle)) {
            $selected = "";
            if (isValidDirOrFile($file)) {
                if(substr($file,0,9) == "language_") {
                    if (substr($file,9,4) == $ADMIN_CONF->get("language"))
                    $selected = " selected";
                    $pagecontent .= "<option".$selected." value=\"".substr($file,9,4)."\">";
                    $currentlanguage = new Properties("conf/$file");
                    $pagecontent .= substr($file,9,4)." (".getLanguageValue("translator_text")." ".$currentlanguage->get("_translator").")";
                    $pagecontent .= "</option>";
                }
            }
        }
        closedir($handle);
    }
    $pagecontent .= "</select></td></tr>";
    // Zeile "ADMIN-MAIL"
    if($MAILFUNCTIONS->isMailAvailable())
    {
        $pagecontent .= "<tr>";
        $pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("sendadminmail_tooltiptitle", "sendadminmail_tooltiptext", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("sendadminmail_text")."</td>";
        $pagecontent .= "<td class=\"config_row2\">"
        .buildCheckBox("sendadminmail", ($ADMIN_CONF->get("sendadminmail") == "true"))
        .getLanguageValue("sendadminmail_text2")."<br />";
        $pagecontent .= "<input type=\"text\" class=\"text1\" name=\"adminmail\" value=\"".$ADMIN_CONF->get("adminmail")."\" /> ";
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
    }
    // Zeile "HÖHE DES TEXTFELDES"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("textareaheight_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"textareaheight\" value=\"".$ADMIN_CONF->get("textareaheight")."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "BACKUP-ERINNERUNG"
    $backupmsgintervall = $ADMIN_CONF->get("backupmsgintervall");
    if ($backupmsgintervall == "") {
        $backupmsgintervall = 0;
    }
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("reminder_backup_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"backupmsgintervall\" value=\"".$backupmsgintervall."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "SETZE DATEIRECHTE FÜR NEUE DATEIEN"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("chmodnewfiles_tooltiptitle", "chmodnewfiles_tooltiptext", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("chmodnewfiles_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\">"
    .buildCheckBox("chmodnewfiles", ($ADMIN_CONF->get("chmodnewfiles") == "true"))
    .getLanguageValue("chmodnewfiles_text2")."<br />";
    $pagecontent .= "<input type=\"text\" class=\"text1\" name=\"chmodnewfilesatts\" value=\"".$ADMIN_CONF->get("chmodnewfilesatts")."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "MAXIMALE DATEIANZAHL BEIM UPLOAD"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("maxnumberofuploadfiles_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"maxnumberofuploadfiles\" value=\"".$ADMIN_CONF->get("maxnumberofuploadfiles")."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "UPLOAD-FILTER"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("uploadfilter_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"noupload\" value=\"".$ADMIN_CONF->get("noupload")."\" /></td>";
    $pagecontent .= "</tr>";
    // Zeile "VORHANDENE DATEIEN BEIM UPLOAD ÜBERSCHREIBEN"
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("uploaddefaultoverwrite_text")."</td>";
    $pagecontent .= "<td class=\"config_row2\">"
    .buildCheckBox("overwrite", ($ADMIN_CONF->get("overwriteuploadfiles") == "true"))
    .getLanguageValue("uploaddefaultoverwrite_text2")."</td>";
    $pagecontent .= "</tr></table>";

    // BILD-EINSTELLUNGEN
    if (extension_loaded("gd"))
    {
        $pagecontent .= "<table class=\"data\"><tr>";
        $pagecontent .= "<td class=\"config_row1\">".getLanguageValue("imagesmax_text");
        $pagecontent .= "</td>";

        $pagecontent .= "<td class=\"config_row2\">" . buildCheckBox("resizeimages", $ADMIN_CONF->get("resizeimages") == "true") . getLanguageValue("resize_image") . "<input type=\"text\" class=\"text3\" name=\"imagewidth\" value=\"".$ADMIN_CONF->get("maximagewidth")."\" />&nbsp;x&nbsp;<input type=\"text\" class=\"text3\" name=\"imageheight\" value=\"".$ADMIN_CONF->get("maximageheight")."\" />&nbsp;" . getLanguageValue("pixels") . "</td>";
        $pagecontent .= "</tr>";
        $pagecontent .= "</table>";
    }

    // Zeile "ÜBERNEHMEN"
    $pagecontent .= "<table class=\"data\"><tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" name=\"apply\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr></table>";
    $pagecontent .= "</form>";
    // Mail-Test-Button
    if ($ADMIN_CONF->get("adminmail") != "" && $MAILFUNCTIONS->isMailAvailable()) {
        $pagecontent .= "<form accept-charset=\"ISO-8859-1\"style=\"display:inline;\" action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"displayadminconfig\">";
        $pagecontent .= "<table><tr><td></td><td><input type=\"submit\" class=\"submit\" name=\"test\" value=\"".getLanguageValue("sendadminmail_testbutton")."\"/></td></table>";
        $pagecontent .= "</form>";
    }

    //$pagecontent .= "</td></tr></table>";
    return array(getLanguageValue("button_config"), $pagecontent);
}

function configAdminLogin() {
    $pagecontent = "<h2>".getLanguageValue("button_config_loginadmin")."</h2>";
    $adminconf = new Properties("conf/logindata.conf");
    $erroroccured = false;

    if (isset($_POST['oldname']))
    $oldname = stripslashes($_POST['oldname']);
    else
    $oldname = "";

    if (isset($_POST['newname']))
    $newname = stripslashes($_POST['newname']);
    else
    $newname = "";

    require_once("Crypt.php");
    $pwcrypt = new Crypt();
    // Übergebene Werte prüfen
    if (isset($_POST['apply']) && ($_POST['apply'] == "true")) {
        // Alle Felder übergeben...
        if(!$erroroccured)
        if (isset($_POST['oldname']) && isset($_POST['oldpw']) && isset($_POST['newname']) && isset($_POST['newpw']) && isset($_POST['newpwrepeat']))
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_missingvalues"));
        }

        // ...und keines leer?
        if(!$erroroccured)
        if (($_POST['oldname'] <> "" ) && ($_POST['oldpw'] <> "" ) && ($_POST['newname'] <> "" ) && ($_POST['newpw'] <> "" ) && ($_POST['newpwrepeat'] <> "" ))
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_missingvalues"));
        }

        // Alte Zugangsdaten korrekt?
        if(!$erroroccured)
        if (($_POST['oldname'] == $adminconf->get("name")) && ($pwcrypt->encrypt($_POST['oldpw']) == $adminconf->get("pw")))
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_wronglogindata"));
        }

        // Neuer Name wenigstens 5 Zeichen lang?
        if(!$erroroccured)
        if (strlen($_POST['newname']) >= 5)
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_tooshortname"));
        }

        // Neues Paßwort zweimal exakt gleich eingegeben?
        if(!$erroroccured)
        if ($_POST['newpw'] == $_POST['newpwrepeat'])
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_newpwmismatch"));
        }

        // Neues Paßwort wenigstens sechs Zeichen lang und mindestens aus kleinen und großen Buchstaben sowie Zahlen bestehend?
        if(!$erroroccured)
        if ((strlen($_POST['newpw']) >= 6) && preg_match("/[0-9]/", $_POST['newpw']) && preg_match("/[a-z]/", $_POST['newpw']) && preg_match("/[A-Z]/", $_POST['newpw']))
        $erroroccured = false;
        else {
            $erroroccured = true;
            $pagecontent .= returnMessage(false, getLanguageValue("config_admin_newpwerror"));
        }

        if (!$erroroccured){
            $adminconf->set("name", $_POST['newname']);
            $adminconf->set("pw", $pwcrypt->encrypt($_POST['newpw']));
            $adminconf->set("initialpw", "false");
            $pagecontent .= returnMessage(true, getLanguageValue("config_userdata_changed"));
        }
    }
    $pagecontent .= "<p>"
    .getLanguageValue("config_adminlogin_text")
    ."<br />"
    ."<br />"
    .getLanguageValue("config_adminlogin_rules_text")
    ."</p>"
    ."<form accept-charset=\"ISO-8859-1\"action=\"index.php\" method=\"post\"><input type=\"hidden\" name=\"apply\" value=\"true\">"
    ."<table class=\"data\">"
    // Zeile "ALTER NAME"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_oldname_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"oldname\" value=\"".$oldname."\" /></td>"
    ."</tr>"
    // Zeile "ALTES PASSWORT"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_oldpw_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"oldpw\" /></td>"
    ."</tr>"
    // Zeile "NEUER NAME"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newname_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"newname\" value=\"".$newname."\" /></td>"
    ."</tr>"
    // Zeile "NEUES PASSWORT"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newpw_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"newpw\" /></td>"
    ."</tr>"
    // Zeile "NEUES PASSWORT - WIEDERHOLUNG"
    ."<tr>"
    ."<td class=\"config_row1\">".getLanguageValue("config_newpwrepeat_text")."</td>"
    ."<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"newpwrepeat\" /></td>"
    ."</tr>"
    // Zeile "ÜBERNEHMEN"
    ."<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"hidden\" name=\"action\" value=\"loginadminconfig\" /><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>"

    ."</table>"
    ."</form>";
    return array(getLanguageValue("button_config_loginadmin"), $pagecontent);
}

// Anzeige der Editieransicht
function showEditPageForm($cat, $page, $action, $tempfile)    {
    global $ADMIN_CONF;
    global $CMS_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;
    global $EXT_PAGE;

    $content = "";

    // wenn das Tempfile gesetzt ist: Tempfile statt originaler Datei verwenden
#    if ($tempfile == "")
    $file = "$CONTENT_DIR_REL/".$cat."/".$page;
//     else
//     $file = "$CONTENT_DIR_REL/".$cat."/".$tempfile;

    if (file_exists($file)) {
        // Inhaltsseite schon vorhanden: Inhalt ins Textfeld holen
        $handle=fopen($file, "r");
        if (filesize($file) > 0)
        $pagecontent = htmlentities(fread($handle, filesize($file)));
        else
        $pagecontent = "";
        fclose($handle);
        // Tempfile hat seinen Zweck erfüllt und kann gelöscht werden
/*        if ($tempfile != "") {
            @unlink("$CONTENT_DIR_REL/".$cat."/".$tempfile);
            // ab hier gilt wieder die Datei der Inhaltsseite
            $file = "$CONTENT_DIR_REL/".$cat."/".$page;
        }*/
    }
    else
    // Inhaltsseite noch nicht vorhanden: Titel als Überschrift ins Textfeld einfügen
    $pagecontent = "[ueber1|".$specialchars->rebuildSpecialChars(substr($page, 3,strlen($page)-7), false)."]";

    // Anzeige der Formatsymbolleiste, wenn die CMS-Syntax aktiviert ist
    if ($CMS_CONF->get("usecmssyntax") == "true") {
        $content .= returnFormatToolbar($cat);
    }

    // Seiteninhalt
    $height = $ADMIN_CONF->get("textareaheight");
    if ($height == "") {
        $height = 350;
        $ADMIN_CONF->set("textareaheight", $height);
    }
    $content .= "<textarea cols=\"96\" rows=\"24\" style=\"height:$height;\" name=\"pagecontent\">".$pagecontent."</textarea><br />"
    ."<input type=\"hidden\" name=\"page\" value=\"$page\" />"
    ."<input type=\"hidden\" name=\"action\" value=\"$action\" />"
    ."<input type=\"hidden\" name=\"cat\" value=\"$cat\" />"
    ."<input type=\"submit\" name=\"cancel\" value=\"".getLanguageValue("button_cancel")."\" accesskey=\"a\" /> ";
    // Zwischenspeichern-Button nicht beim Neuanlegen einer Inhaltsseite anzeigen
    if (file_exists($file))
    $content .= "<input type=\"submit\" name=\"savetemp\" value=\"".getLanguageValue("button_savetemp")."\" accesskey=\"w\" /> ";
    $content .= "<input type=\"submit\" name=\"save\" value=\"".getLanguageValue("button_save")."\" accesskey=\"s\" /> ";
    $checked = "";
    // Auswahl "Speicher-Art"
    $extension = substr($page, strlen($page)-4, 4);
    $checkednormal = "";
    $checkedhidden = "";
    $checkeddraft = "";
    if ($extension == $EXT_PAGE) {
        $checkednormal = " checked=\"checked\"";
    }
    if ($extension == $EXT_HIDDEN) {
        $checkedhidden = " checked=\"checked\"";
    }
    if ($extension == $EXT_DRAFT) {
        $checkeddraft = " checked=\"checked\"";
    }
    $content .= "<input type=\"radio\" name=\"saveas\" value=\"normal\"$checkednormal accesskey=\"n\" /> ".getLanguageValue("saveasnormal_radiobutton")
    ." <input type=\"radio\" name=\"saveas\" value=\"hidden\"$checkedhidden accesskey=\"v\" /> ".getLanguageValue("saveashidden_radiobutton")
    ." <input type=\"radio\" name=\"saveas\" value=\"draft\"$checkeddraft accesskey=\"e\" /> ".getLanguageValue("saveasdraft_radiobutton");
    return $content;
}

function saveContentToPage($content, $page) {
    global $specialchars;
    global $ADMIN_CONF;
    $handle=fopen($specialchars->replaceSpecialChars($page), "w");
    if (get_magic_quotes_gpc()) {
        // fputs($handle, trim(stripslashes($content)));
        fputs($handle, stripslashes($content)); // nicht trimmen, damit führende und folgende Leerzeichen/-zeilen nicht verloren gehen
    }
    else {
        // fputs($handle, trim($content));
        fputs($handle, $content); // nicht trimmen, damit führende und folgende Leerzeichen/-zeilen nicht verloren gehen
    }
    fclose($handle);
    // chmod, wenn so eingestellt
    if ($ADMIN_CONF->get("chmodnewfiles") == "true") {
        chmod ($page, octdec($ADMIN_CONF->get("chmodnewfilesatts")));
    }

}

// Lösche ein Verzeichnis rekursiv
function deleteDir($path) {
    $success = true;
    // Existenz prüfen
    if (!file_exists($path))
    return false;
    $handle = opendir($path);
    while ($currentelement = readdir($handle)) {
        if (!isValidDirOrFile($currentelement))
        continue;
        // Verzeichnis: Rekursiver Funktionsaufruf
        if (is_dir($path."/".$currentelement))
        $success = deleteDir($path."/".$currentelement);
        // Datei: löschen
        else
        $success = @unlink($path."/".$currentelement);
    }
    closedir($handle);
    // Verzeichnis löschen
    $success = @rmdir($path);
    return $success;
}

// Überprüfe, ob die gegebene Datei eine der übergebenen Endungen ha
function fileHasExtension($filename, $extensions) {
    foreach ($extensions as $ext) {
        if (strtolower(substr($filename, strlen($filename)-(strlen($ext)+1), strlen($ext)+1)) == ".".strtolower($ext))
        return true;
    }
    return false;
}

// Gib Erfolgs- oder Fehlermeldung zurück
function returnMessage($success, $message) {
    if ($success == true)
    return "<span class=\"erfolg\">".$message."</span>";
    else
    return "<span class=\"fehler\">".$message."</span>";
}

// Smiley-Liste
function returnSmileyBar() {
    $smileys = new Smileys("../smileys");
    $content = "";
    foreach($smileys->getSmileysArray() as $icon => $emoticon)
    $content .= "<img class=\"jss\" title=\":$icon:\" alt=\"$emoticon\" src=\"../smileys/$icon.gif\" onClick=\"insert(' :$icon: ', '', false)\" />";
    return $content;
}

// Selectbox mit allen benutzerdefinierten Syntaxelementen
function returnUserSyntaxSelectbox() {
    global $USER_SYNTAX;
    $usersyntaxarray = $USER_SYNTAX->toArray();
    ksort($usersyntaxarray);

    $content = "<select class=\"usersyntaxselectbox\" name=\"usersyntax\" onchange=\"insertTagAndResetSelectbox(this);\">"
    ."<option value=\"\">".getLanguageValue("usersyntax")."</option>";
    foreach ($usersyntaxarray as $key => $value) {
        $content .= "<option value=\"".$key."\">[".$key."|...]</option>";
    }
    $content .= "</select>";
    return $content;
}


function returnFormatToolbar($currentcat) {
    global $CMS_CONF;
    global $USER_SYNTAX;

    $content = "<div style=\"padding:0px 0px;\">"
    // Information zeigen, wenn JavaScript nicht aktiviert
    ."<noscript><span class=\"fehler\">".getLanguageValue("toolbar_nojs_text")."</span></noscript>"
    ."<table>"
    ."<tr>"
    // Überschrift Syntaxelemente
    ."<td style=\"padding-right:10px;\">"
    .getLanguageValue("toolbar_syntaxelements")
    ."</td>"
    // Überschrift Textformatierung
    ."<td style=\"padding-left:22px;\">"
    .getLanguageValue("toolbar_textformatting")
    ."</td>"
    // Überschrift Farben
    ."<td style=\"padding-left:22px;\">"
    .getLanguageValue("toolbar_textcoloring")
    ."</td>"
    ."</tr>"
    ."<tr>"
    // Syntaxelemente
    ."<td style=\"padding-right:0px;\">"
    .returnFormatToolbarIcon("link")
    .returnFormatToolbarIcon("mail")
    .returnFormatToolbarIcon("seite")
    .returnFormatToolbarIcon("kategorie")
    .returnFormatToolbarIcon("datei")
    .returnFormatToolbarIcon("galerie")
    .returnFormatToolbarIcon("bild")
    .returnFormatToolbarIcon("bildlinks")
    .returnFormatToolbarIcon("bildrechts")
    .returnFormatToolbarIcon("ueber1")
    .returnFormatToolbarIcon("ueber2")
    .returnFormatToolbarIcon("ueber3")
    .returnFormatToolbarIcon("liste")
    .returnFormatToolbarIcon("numliste")
    ."<img class=\"js\" alt=\"Tabelle\" title=\"[tabelle| ... ] - ".getLanguageValue("toolbar_desc_tabelle")."\" src=\"gfx/jsToolbar/tabelle.png\" onClick=\"insert('[tabelle|\\n<< ', ' |  >>\\n<  |  >\\n]', true)\">"
    ."<img class=\"js\" alt=\"Horizontale Linie\" title=\"[----] - ".getLanguageValue("toolbar_desc_linie")."\" src=\"gfx/jsToolbar/linie.png\" onClick=\"insert('[----]', '', false)\">"
    .returnFormatToolbarIcon("html")
    .returnFormatToolbarIcon("include")
    ."</td>"
    // Textformatierung
    ."<td style=\"padding-left:22px;\">"
    .returnFormatToolbarIcon("links")
    .returnFormatToolbarIcon("zentriert")
    .returnFormatToolbarIcon("block")
    .returnFormatToolbarIcon("rechts")
    .returnFormatToolbarIcon("fett")
    .returnFormatToolbarIcon("kursiv")
    .returnFormatToolbarIcon("unter")
    .returnFormatToolbarIcon("durch")
    ."</td>"
    // Farben
    ."<td style=\"padding-left:22px;\">"
    ."<table><tr><td>"
    ."<img class=\"js\" style=\"background-color:#AA0000\" alt=\"Farbe\" id=\"farbicon\" title=\"[farbe=RRGGBB| ... ] - ".getLanguageValue("toolbar_desc_farbe")."\" src=\"gfx/jsToolbar/farbe.png\" onClick=\"insert('[farbe=' + document.getElementById('farbcode').value + '|', ']', true)\">"
    ."</td><td>"
    ."<div class=\"colordiv\">"
    ."<input type=\"text\" readonly=\"readonly\" maxlength=\"6\" value=\"AA0000\" class=\"colorinput\" id=\"farbcode\" size=\"0\">"
    ."<img class=\"colorimage\" src=\"js_color_picker_v2/images/select_arrow.gif\" onmouseover=\"this.src='js_color_picker_v2/images/select_arrow_over.gif'\" onmouseout=\"this.src='js_color_picker_v2/images/select_arrow.gif'\" onclick=\"showColorPicker(this,document.getElementById('farbcode'))\" alt=\"...\" title=\"Farbauswahl\" />"
    ."</div>"
    ."</td></tr></table>"
    ."</td>"
    ."</tr>"
    ."</table>"
    ."<table>"
    ."<tr>";

    // Benutzerdefinierte Syntaxelemente vorbereiten
    $usersyntaxarray = $USER_SYNTAX->toArray();

    // Überschrift Inhalte
    $content .=    "<td>"
    .getLanguageValue("toolbar_contents")
    ."</td>";
    // Überschrift Benutzerdefinierte Syntaxelemente
    if (count($usersyntaxarray) > 0) {
        $content .=    "<td style=\"padding-left:22px;\">"
        .getLanguageValue("usersyntax")
        ."</td>";
    }
    $content .= "</tr>"
    ."<tr>"
    // Inhalte
    ."<td>"
    .returnOverviewSelectbox(1, $currentcat)
    ."&nbsp;"
    .returnOverviewSelectbox(2, $currentcat)
    ."&nbsp;"
    .returnOverviewSelectbox(3, $currentcat)
    ."</td>";
    // Benutzerdefinierte Syntaxelemente
    if (count($usersyntaxarray) > 0) {
        $content .=    "<td style=\"padding-left:22px;\">"
        .returnUserSyntaxSelectbox()
        ."</td>";
    }
    $content .=     "</tr>"
    ."</table>";

    // Smileys
    if ($CMS_CONF->get("replaceemoticons") == "true") {
        $content .= "<table><tr><td colspan=\"2\">".returnSmileyBar()."</td></tr></table>";
    }

    $content .= "</div>";
    return $content;
}

// Rückgabe eines Standard-Formatsymbolleisten-Icons
function returnFormatToolbarIcon($tag) {
    return "<img class=\"js\" alt=\"$tag\" title=\"[$tag| ... ] - ".getLanguageValue("toolbar_desc_".$tag)."\" src=\"gfx/jsToolbar/".$tag.".png\" onClick=\"insert('[".$tag."|', ']', true)\">";
}


// Rückgabe einer Selectbox mit Elementen, die per Klick in die Inhaltsseite übernommen werden können
// $type: 1=Kategorien 2=Inhaltsseiten 3=Dateien 4=Galerien
function returnOverviewSelectbox($type, $currentcat) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $GALLERIES_DIR_REL;
    global $EXT_PAGE;
    global $EXT_HIDDEN;

    $elements = array();
    $selectname = "";
    $spacer = "&nbsp;&bull;&nbsp;";

    switch ($type) {

        // Inhaltsseiten und Kategorien
        case 1:
            $categories = getDirContentAsArray($CONTENT_DIR_REL, false);
            foreach ($categories as $catdir) {
                if (isValidDirOrFile($catdir)) {
                    $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), false);
                    array_push($elements, array($cleancatname, $cleancatname));
                    $handle = opendir("$CONTENT_DIR_REL/$catdir");
                    while (($file = readdir($handle))) {
                        if (isValidDirOrFile($file) && is_file("$CONTENT_DIR_REL/$catdir/$file") && ((substr($file, strlen($file)-4, 4) == $EXT_PAGE) || (substr($file, strlen($file)-4, 4) == $EXT_HIDDEN))) {
                            $cleanpagename = $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file) - 3 - strlen($EXT_PAGE)), false);
                            $completepagename = $cleanpagename;
                            if (substr($file, strlen($file)-4, 4) == $EXT_HIDDEN)
                            $completepagename = $cleanpagename." (".getLanguageValue("hiddenpage").")";
                            if ($catdir == $currentcat)
                            array_push($elements, array($spacer.$completepagename, $cleanpagename));
                            else
                            array_push($elements, array($spacer.$completepagename, $cleancatname.":".$cleanpagename));
                        }
                    }
                    closedir($handle);
                }
            }
            $selectname = "pages";
            break;

            // Dateien
        case 2:
            // alle Kategorien durchgehen
            $categories = getDirContentAsArray($CONTENT_DIR_REL, false);
            foreach ($categories as $catdir) {
                if (isValidDirOrFile($catdir)) {
                    $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), false);
                    array_push($elements, array($cleancatname, ":".$cleancatname));
                    $handle = opendir("$CONTENT_DIR_REL/$catdir/dateien");
                    $currentcat_filearray = array();
                    while (($file = readdir($handle))) {
                        if (isValidDirOrFile($file) && is_file("$CONTENT_DIR_REL/$catdir/dateien/$file")) {
                            array_push($currentcat_filearray, $file);
                        }
                    }
                    natcasesort($currentcat_filearray);
                    foreach ($currentcat_filearray as $current_file) {
                        if ($catdir == $currentcat)
                        array_push($elements, array($spacer.$current_file, $current_file));
                        else
                        array_push($elements, array($spacer.$current_file, $cleancatname.":".$current_file));
                    }
                    closedir($handle);
                }
            }
            $selectname = "files";
            break;

            // Galerien
        case 3:
            $galleries = getDirContentAsArray($GALLERIES_DIR_REL, false);
            foreach ($galleries as $currentgallery) {
                array_push($elements, array($specialchars->rebuildSpecialChars($currentgallery, false), $specialchars->rebuildSpecialChars($currentgallery, false)));
            }
            $selectname = "gals";
            break;

        default:
            return "WRONG PARAMETER!";
    }

    // Selectbox zusammenbauen
    $select = "<select name=\"$selectname\" class=\"overviewselect\" onchange=\"insertAndResetSelectbox(this);\">";
    // Titel der Selectbox
    switch ($type) {
        // Inhaltsseiten und Kategorien
        case 1:
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("button_category")." / ".getLanguageValue("button_site").":</option>";
            break;
            // Dateien
        case 2:
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("button_data").":</option>";
            break;
            // Galerien
        case 3:
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("button_gallery").":</option>";
            break;
    }
    // Elemente der Selectbox
    foreach ($elements as $element) {
        if (substr($element[1], 0, 1) == ":") {
            $select .= "<option class=\"noaction\" value=\"\">".$element[0]."</option>";
        }
        else {
            $select .= "<option class=\"hasaction\" value=\"".$element[1]."\">".$element[0]."</option>";
        }
    }
    $select .= "</select>";
    return $select;
}


// alle Dateien einer Kategorie aus der Download-Statistik löschen
function deleteCategoryFromDownloadStats($catname) {
    global $DOWNLOAD_COUNTS;
    // Download-Statistik als Array holen
    $downloadsarray = $DOWNLOAD_COUNTS->toArray();
    foreach($downloadsarray as $key => $value) {
        // Keys mit zu löschendem Kategorienamen: aus dem Array nehmen
        $data = explode(":", $key);
        if ($data[0] == $catname) {
            unset($downloadsarray[$key]);
        }
    }
    // bearbeitetes Array wieder zurück in die Download-Statistik schreiben
    $DOWNLOAD_COUNTS->setFromArray($downloadsarray);
}


// eine Kategorie in der Download-Statistik umbenennen
function renameCategoryInDownloadStats($oldcatname, $newcatname) {
    global $DOWNLOAD_COUNTS;
    // Download-Statistik als Array holen
    $downloadsarray = $DOWNLOAD_COUNTS->toArray();
    foreach($downloadsarray as $key => $value) {
        // Keys mit zu änderndem Kategorienamen: im Array ändern
        $keyparts = explode(":", $key);
        if ($keyparts[0] == $oldcatname) {
            $downloadsarray[$newcatname.":".$keyparts[1]] = $value;                // Element mit neuem Key ans Array hängen
            unset($downloadsarray[$key]);                                                                    // Element mit altem Key aus Array löschen
        }
    }
    // bearbeitetes Array wieder zurück in die Download-Statistik schreiben
    $DOWNLOAD_COUNTS->setFromArray($downloadsarray);
}

// Überschreibt die layoutabhängigen CMS-Einstellungen usesubmenu und gallerypicsperrow
function setLayoutAndDependentSettings($layoutfolder) {
    global $CMS_CONF;

    // nur, wenn sich das Layout ändert
    if ($layoutfolder != $CMS_CONF->get("cmslayout")) {
        $settingsfile = "../layouts/$layoutfolder/layoutsettings.conf";
        if (file_exists($settingsfile)) {
            // Einstellungen aus Layout-Settings laden und in den CMS-Einstellungen überschreiben
            $layoutsettings = new Properties($settingsfile);
            $CMS_CONF->set("usesubmenu", $layoutsettings->get("usesubmenu"));
            $CMS_CONF->set("gallerypicsperrow", $layoutsettings->get("gallerypicsperrow"));
        }
    }
    $CMS_CONF->set("cmslayout", $layoutfolder);
}

// Hochgeladene Datei überprüfen und speichern
function uploadFile($uploadfile, $cat, $forceoverwrite){
    global $ADMIN_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;

    if (isset($uploadfile) and !$uploadfile['error']) {
        // nicht erlaubte Endung
        if (fileHasExtension($uploadfile['name'], explode(",", $ADMIN_CONF->get("noupload")))) {
            return returnMessage(false, htmlentities($uploadfile['name']).": ".getLanguageValue("data_uploadfile_wrongext"));
        }
        // ungültige Zeichen im Dateinamen
        elseif(!preg_match($specialchars->getFileCharsRegex(), $uploadfile['name'])) {
            return returnMessage(false, htmlentities($uploadfile['name']).": ".getLanguageValue("invalid_values"));
        }
        // Datei vorhanden und "Überschreiben"-Checkbox nicht aktiviert
        elseif (file_exists("$CONTENT_DIR_REL/".$specialchars->replaceSpecialChars($cat)."/dateien/".$uploadfile['name']) && ($forceoverwrite != "on")) {
            return returnMessage(false, htmlentities($uploadfile['name']).": ".getLanguageValue("data_uploadfile_exists"));
        }
        // alles okay, hochladen!
        else {
            $savepath = "$CONTENT_DIR_REL/".$cat."/dateien/".$uploadfile['name'];
            move_uploaded_file($uploadfile['tmp_name'], $savepath);
            // chmod, wenn so eingestellt
            if ($ADMIN_CONF->get("chmodnewfiles") == "true") {
                chmod ($savepath, octdec($ADMIN_CONF->get("chmodnewfilesatts")));
            }
                
            if($ADMIN_CONF->get("resizeimages") == "true") {
                // Bilddaten feststellen
                $size = getimagesize($savepath);
                $width = $size[0];
                $height = $size[1];

                $MAX_IMG_WIDTH = $ADMIN_CONF->get("maximagewidth");
                $MAX_IMG_HEIGHT = $ADMIN_CONF->get("maximageheight");

                // Breite skalieren
                if ($width > $MAX_IMG_WIDTH) {
                    $width=$MAX_IMG_WIDTH;
                    $height=round(($MAX_IMG_WIDTH*$size[1])/$size[0]);
                }
                // Höhe skalieren
                if ($height > $MAX_IMG_HEIGHT){
                    $height=$MAX_IMG_HEIGHT;
                    $width=round(($MAX_IMG_HEIGHT*$size[0])/$size[1]);
                }
                
                // Mimetype herausfinden
                $image_typ = strtolower(str_replace('image/','',$size['mime']));
                if($image_typ == "gif" or $image_typ == "png" or $image_typ == "jpeg") {
                    $image_erzeugen = "imagecreatefrom$image_typ";
                    $originalpic = $image_erzeugen($savepath);
                    // es ist ein ein Palette-Image
                    if(!imageistruecolor($originalpic)) { 
                        $transparentcolor = imagecolortransparent($originalpic);
                        $resizedpic = imagecreate($width,$height);
                        imagepalettecopy($resizedpic,$originalpic);
                        if($transparentcolor >= 0) {
                            imagefill($resizedpic,0,0,$transparentcolor);
                            imagecolortransparent($resizedpic,$transparentcolor);
                        }
                    }
                    // es ist ein TrueColor-Image 
                    else {
                        $trans = "nein";
                        // PNG: Pixelweise auf Transparenz prüfen
                        if($image_typ == "png") {
                            $step_h = round($size[1] * 0.005); # kleiner Hack, daß nicht ganz so
                            $step_w = round($size[0] * 0.005); # viele Pixel untersucht werden müssen
                            for ($h = 0; $h < $size[1]; $h = $h + $step_h) {
                                if($trans == "ja") break;
                                for ($w = 0; $w < $size[0]; $w = $w + $step_w) {
                                    $alpha = imagecolorsforindex($originalpic,imagecolorat($originalpic,$w,$h));
                                    if($alpha['alpha'] > 0) { 
                                        $trans = "ja"; 
                                        break; 
                                    }
                                }
                            }
                        }
                        $resizedpic = imagecreatetruecolor($width,$height);
                        // es ist ein TrueColor-Image mit Tranparenz
                        if($trans == "ja") { 
                            imagealphablending($resizedpic, false);
                            $transparentcolor = imagecolorallocatealpha($resizedpic,0,0,0,127);
                            imagefill($resizedpic,0,0,$transparentcolor);
                            imagesavealpha($resizedpic,true);
                        }
                    }
                    // Verkleinertes Bild erzeugen und abspeichern
                    imagecopyresized($resizedpic, $originalpic, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                    $image_erzeugen = "image$image_typ";
                    $image_erzeugen($resizedpic, $savepath);
                    // Aufräumen
                    imagedestroy($originalpic);
                    imagedestroy($resizedpic);
                }
            }
                
            return returnMessage(true, htmlentities($uploadfile['name']).": ".getLanguageValue("data_upload_success"));
        }
    }
}

// Überprüft den REQUEST-Parameter mit dem übergebenen Index auf Validität.
// $type hat folgende Werte:
// 1: nur Ziffern (wenigstens eine)
// 2: beliebige Zeichen (wenigstens eins)
// 3: Mail-Adresse
// 4: Paßwort
// 5: Benutzername
// 6: beliebige Zeichen (darf leer sein)
function isValidRequestParameter($index, $type) {
    if (!isset($_REQUEST[$index])) {
        return false;
    }

    $value = $_REQUEST[$index];
    switch ($type) {

        case 1:
            return preg_match("/^[0-9]+$/", $value);
            break;

        case 2:
            return ($value <> "");
            break;

        case 3:
            return preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/", $value);
            break;
                
        case 4:
            return (
            (strlen($value) >= 6)
            && preg_match("/[0-9]/", $value)
            && preg_match("/[a-z]/", $value)
            && preg_match("/[A-Z]/", $value)
            );

        case 5:
            return (strlen($value) >= 5);

        case 6:
            return true;

                
        default:
            return false;
    }
}

// Gibt eine Checkbox mit dem übergebenen Namen zurück. Der Parameter checked bestimmt, ob die Checkbox angehakt ist.
function buildCheckBox($name, $checked) {
    $checkbox = "<input type=\"checkbox\" ";
    if ($checked) {
        $checkbox .= "checked=checked";
    }
    $checkbox .= " name=\"".$name."\">";
    return $checkbox;
}

// gibt zurück, ob eine Checkbox angehakt ist
function checkBoxIsChecked($checkboxrequest) {
    return (isset($_REQUEST[$checkboxrequest]) && ($_REQUEST[$checkboxrequest] == "on"));
}

// gibt das img-Tag für ein Action-Icon zurück (abhängig von der entsprechenden Einstellung)
function getActionIcon($iconname, $titletext) {
    global $ADMIN_CONF;
    // Große Icons anzeigen?
    if ($ADMIN_CONF->get("usebigactionicons") == "true") {
        return "<img src=\"gfx/actionsbig/".$iconname.".png\" alt=\"".$titletext."\" title=\"".$titletext."\" />";
    }
    // sonst normale Icons anzeigen
    else {
        return "<img src=\"gfx/actions/".$iconname.".png\" alt=\"".$titletext."\" title=\"".$titletext."\" />";
    }
    
}

?>