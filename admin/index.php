<?php

/*
 *
 * $Revision$
 * $LastChangedDate$
 * $Author$
 *
 */


$ADMIN_TITLE = "moziloAdmin";

#$CHARSET = 'ISO-8859-1';
$CHARSET = 'UTF-8';

session_start();
 
$debug = "ja"; # ja oder nein
 // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
if($debug != "ja")
    @ini_set("display_errors", 0);

 // ISO 8859-1 erzwingen - experimentell!
 // @ini_set("default_charset", $CHARSET);

 // Session Fixation durch Vergabe einer neuen Session-ID beim ersten Login verhindern
 if (!isset($_SESSION['PHPSESSID'])) {
 session_regenerate_id(true);
 $_SESSION['PHPSESSID'] = true;
 }

# das nach POST!!!!!!!!!!!!!!!!!!!!!!!!!!!!!1
$phpsessid_tmp = $_REQUEST['PHPSESSID'];
#unset($_REQUEST);
#$_REQUEST['PHPSESSID'] = $phpsessid_tmp;
if($debug == "ja") { #top:850px; relative absolute position:relative;
    ob_start();
#echo getcwd()." = getcwd<br>\n";
#echo $_SERVER['REQUEST_URI']." = REQUEST_URI<br>\n";
#echo __FILE__." = file<br>\n";
#    echo "<div style=\"overflow:auto;width:920px;height:400px;margin:0;margin-top:20px;padding:0;\"><pre style=\"background-color:#000;color:#0f0;padding:5px;margin:0;font-family:monospace;border:2px solid #777;\">";
    echo "SESSION -------------------\n";
    print_r($_SESSION);
    echo "POST -------------------\n";
    print_r($_POST);
    echo "FILES -------------------\n";
    print_r($_FILES);
/**/    echo "REQUEST -------------------\n";
    print_r($_REQUEST);
#    echo "GET -------------------\n";
#    print_r($_GET);
#    echo "SERVER -------------------\n";
#    print_r($_SERVER);
#    echo "</pre></div>";
    $debug_txt = ob_get_contents();
    ob_end_clean();
}

require_once("filesystem.php");
require_once("string.php");
require_once("../Smileys.php");
require_once("../Mail.php");
require_once("categories_array.php");

# Fatal Errors sofort beenden
if(!is_dir("conf")) {
    die("Fatal Error admin/conf Verzeichnis existiert nicht");
}
if(!is_dir("../conf")) {
    die("Fatal Error conf Verzeichnis existiert nicht");
}
/*
properties['readonly'] = nur lesen
properties['error'] = kann nicht lesen oder schreiben, Sperdatei anlegen oder Sperdatei kann nicht gelöscht werden
*/

$ADMIN_CONF    = new Properties("conf/basic.conf",true);
if(!isset($ADMIN_CONF->properties['readonly'])) {
    die($ADMIN_CONF->properties['error']);
}
$BASIC_LANGUAGE = new Properties("sprachen/language_".$ADMIN_CONF->get("language").".conf",true);
if(!isset($BASIC_LANGUAGE->properties['readonly'])) {
    die($BASIC_LANGUAGE->properties['error']);
}
# Errors nicht ganz so tragisch
if(!is_dir("../kategorien")) {
    die(getLanguageValue("error_dir")." kategorien/");
}
if(!is_dir("../layouts")) {
    die(getLanguageValue("error_dir")." layouts/");
}
if(!is_dir("../sprachen")) {
    die(getLanguageValue("error_dir")." sprachen/");
}
if(!is_dir("../formular")) {
    die(getLanguageValue("error_dir")." formular/");
}
if(!is_dir("../galerien")) {
    die(getLanguageValue("error_dir")." galerien/");
}

$CMS_CONF    = new Properties("../conf/main.conf",true);
if(!isset($CMS_CONF->properties['readonly'])) {
    die($CMS_CONF->properties['error']);
}

$VERSION_CONF    = new Properties("../conf/version.conf",true);
if(!isset($VERSION_CONF->properties['readonly'])) {
    die($VERSION_CONF->properties['error']);
}

$DOWNLOAD_COUNTS = new Properties("../conf/downloads.conf",true);
if(!isset($DOWNLOAD_COUNTS->properties['readonly'])) {
    die($DOWNLOAD_COUNTS->properties['error']);
}

$LOGINCONF = new Properties("conf/logindata.conf",true);
# die muss schreiben geöfnet werden können
if(isset($LOGINCONF->properties['error'])) {
    die($LOGINCONF->properties['error']);
}
// Login ueberpruefen
if (!isset($_SESSION['login_okay']) or !$_SESSION['login_okay']) {
    header("location:login.php?logout=true");
    die("");
}


$MAILFUNCTIONS = new Mail();

$USER_SYNTAX_FILE = "../conf/syntax.conf";
$USER_SYNTAX = new Properties($USER_SYNTAX_FILE,true);
if($CMS_CONF->properties['usecmssyntax'] == "true" and !isset($USER_SYNTAX->properties['readonly'])) {
    die($USER_SYNTAX->properties['error']);
}

$CONTACT_CONF = new Properties("../formular/formular.conf",true);
if(!isset($CONTACT_CONF->properties['readonly'])) {
    die($CONTACT_CONF->properties['error']);
}

if(!is_file("../formular/aufgaben.conf")) {
    $AUFGABEN_CONF = new Properties("../formular/aufgaben.conf",true);
    if(!isset($AUFGABEN_CONF->properties['readonly'])) {
        die($AUFGABEN_CONF->properties['error']);
    }
    unset($AUFGABEN_CONF);
}

if(!is_file("../conf/passwords.conf")) {
    $PASSWORDS_CONF = new Properties("../conf/passwords.conf",true);
    if(!isset($PASSWORDS_CONF->properties['readonly'])) {
        die($PASSWORDS_CONF->properties['error']);
    }
    unset($PASSWORDS_CONF);
}
// AbwÃ¤rtskompatibilitÃ¤t: Downloadcounter initalisieren
if ($DOWNLOAD_COUNTS->get("_downloadcounterstarttime") == "" and !isset($DOWNLOAD_COUNTS->properties['error']))
    $DOWNLOAD_COUNTS->set("_downloadcounterstarttime", time());

// Pfade
$CONTENT_DIR_NAME        = "kategorien";
$CONTENT_DIR_REL        = "../".$CONTENT_DIR_NAME;
$GALLERIES_DIR_NAME    = "galerien";
$GALLERIES_DIR_REL    = "../".$GALLERIES_DIR_NAME;
$PREVIEW_DIR_NAME        = "vorschau";

// RegEx fuer erlaubte Zeichen in Inhaltsseiten, Kategorien, Dateien und Galerien
# $specialchars wurde schonn in filesystem.php erstelt!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
#$specialchars = new SpecialChars();
$ALLOWED_SPECIALCHARS_REGEX = $specialchars->getSpecialCharsRegex();

// Dateiendungen fuer Inhaltsseiten 
$EXT_PAGE     = ".txt";
$EXT_HIDDEN     = ".hid";
$EXT_DRAFT     = ".tmp";
$EXT_LINK     = ".lnk";

$post = NULL;
$array_tabs = array("home","category","page","files","gallery","config","admin","plugins");
$action = 'home';
foreach($array_tabs as $pos => $tab) {
    if(isset($_POST['action_'.$pos])) {
        $action = $tab;
        $post['makepara'] = "yes";
        break;
    }
    if(isset($_POST['action_activ'])) {
    $action_html = $specialchars->rebuildSpecialChars($_POST['action_activ'], false, true);
        if($action_html == getLanguageValue($tab."_button")) {
            $action = $tab;
            break;
        }
    }
}

# action_data parameter die mit cat page zu tun haben
if(isset($_POST['action_data'])) {
    # ist das Array für Kategorien oder Inhaltseiten nicht erzeugen
    $post['makepara'] = "no";
    if(isset($_POST['checkpara']) and $_POST['checkpara'] == "yes") {
        $post['categories'] = checkPostCatPageReturnVariable("$CONTENT_DIR_REL/");
    }
    # aus checkPostCatPageReturnVariable error_messages umsetzen
    if(isset($post['categories']['error_messages'])) {
        $post['error_messages'] = $post['categories']['error_messages'];
        unset($post['categories']['error_messages']);
    }
    # aus checkPostCatPageReturnVariable move_copy umsetzen
    if(isset($post['categories']['move_copy'])) {
        $post['move_copy'] = $post['categories']['move_copy'];
        unset($post['categories']['move_copy']);
    }
    # action_data standartkomform aufbauen
    $post['action_data'] = $_POST['action_data'];
    $action_key = key($post['action_data']);
    if(is_array($post['action_data'][$action_key])) {
        $cat = key($post['action_data'][$action_key]);
        if(is_array($post['action_data'][$action_key][$cat])) {
            $page = key($post['action_data'][$action_key][$cat]);
            $post['action_data'][$action_key][$cat] = $page;
        }
    }
}

if($action == 'config' or $action == 'admin' ) {
    if(is_array($_POST) and count($_POST) > 0) {
        foreach($_POST as $key => $inhalt) {
            $post[$key] = getRequestParam($key, true);
        }
    }
}


if($LOGINCONF->get("initialsetup") == "true") {
    $action = "admin";
}

# Backup erinerung bestätigen
$error_backup = NULL;
if(getRequestParam("lastbackup_yes", true)) {
    $post['makepara'] = "yes";
    if($specialchars->rebuildSpecialChars(getRequestParam("lastbackup_yes", true), false, true) == getLanguageValue("yes")) {
        if(!isset($ADMIN_CONF->properties['error'])) {
            $ADMIN_CONF->set("lastbackup",time());
        } else {
            $error_backup = returnMessage(false, $ADMIN_CONF->properties['error']);
        }
    }
}

# Function aufrufen
$functionreturn = array();
$functionreturn = $action($post);

$pagetitle = $functionreturn[0];
$pagecontent = $functionreturn[1];

// Aufbau der gesamten Seite 
$html = '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
$html .= "<html>\n";
$html .= "<head>";
$html .= '<meta http-equiv="Content-Type" content="text/html;charset='.$CHARSET.'">';
$html .= '<script type="text/javascript" src="buttons.js"></script>';
$html .= '<script type="text/javascript" src="multifileupload.js"></script>';
$html .= "<title>$ADMIN_TITLE - $pagetitle</title>";
$html .= '<link type="text/css" rel="stylesheet" href="adminstyle_neu.css">';
$html .= '<link type="text/css" rel="stylesheet" href="editsite.css">';
$html .= '<link type="text/css" rel="stylesheet" media="screen" href="js_color_picker_v2/js_color_picker_v2.css">';
$html .= '<script type="text/javascript" src="js_color_picker_v2/color_functions.js"></script>';
$html .= '<script type="text/javascript" src="js_color_picker_v2/js_color_picker_v2.js"></script>';
$html .= "</head>";
$html .= "<body>";
$html .= '<script type="text/javascript" src="wz_tooltip.js"></script>';

$icon_size = "24x24";


if($ADMIN_CONF->get("showTooltips") == "true") {
    $tooltip_button_home_logout = createTooltipWZ("button_home_logout","",",WIDTH,200,CLICKCLOSE,true");
} else {
    $tooltip_button_home_logout = NULL;
}

$html .= '<table width="90%" cellspacing="0" border="0" cellpadding="0" id="table_admin">';
$html .= '<tr><td width="100%" id="td_title">';
$html .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" id="table_titel">';
$html .= '<tr><td id="td_table_titel_text">'.$ADMIN_TITLE.' - '.$pagetitle.'</td>';
$html .= '<td width="24" height="24" id="td_table_titel_logout"><form class="form" accept-charset="'.$CHARSET.'" action="login.php" method="post"><input id="design_logout" class="input_button" type="image" name="logout" value="'.getLanguageValue("button_home_logout").'&nbsp;"  src="gfx/icons/'.$icon_size.'/logout.png" title="'.getLanguageValue("button_home_logout").'"'.$tooltip_button_home_logout.'></form></td></tr>';
$html .= '</table>';

$html .= "</td></tr>";
$html .= '<tr><td width="100%" id="td_tabs">';

$html .= '<table width="100%" id="table_buttons" cellspacing="0" cellpadding="0" border="0">';
$html .= '<tr>';

# Menue Tabs erzeugen
foreach($array_tabs as $position => $language) {
    if($ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip = createTooltipWZ($language."_button",$language."_text",",WIDTH,400");
    } else {
        $tooltip = NULL;
    }

    # IE button bug deshalb name=action_1 ...
    if($action == $language) $activ = "_activ"; else $activ = NULL;
    $html .= '<td width="2%" class="td_button'.$activ.'" nowrap>';
    $html .= '<form class="form" accept-charset="'.$CHARSET.'" action="index.php" method="post">';
    $html .= '<script type="text/javascript">document.write(\'<input type="hidden" name="javascript" value="ja">\');</script>';
    $html .= '<button class="button_tab'.$activ.'" type="submit" name="action_'.$position.'" value="'.getLanguageValue($language."_button").'" title="'.getLanguageValue($language."_button").'"'.$tooltip.' accesskey="s">';
    $html .= '<table width="50%" class="button_tab_inhalt'.$activ.'" cellspacing="0" cellpadding="0" border="0"><tr><td width="2%" class="button_tab_icon"><img src="gfx/icons/'.$icon_size.'/'.$language.'.png" alt="" width="24" height="24" hspace="0" vspace="0" border="0"></td><td width="98%" class="button_tab_text">'.getLanguageValue($language."_button").'</td></tr></table>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</td>';

}

$html .= '<td class="rest_td_tabs">&nbsp;</td>';
$html .= '</tr></table>';

$html .= "</td></tr>";
$html .= '<tr><td width="100%" height="450" valign="top" id="td_content">';

$enctype = NULL;
if($action == 'files' or $action == 'gallery' or $action == 'plugins') {
    $enctype = ' enctype="multipart/form-data"';
}
$html .= '<form name="form" class="form" accept-charset="'.$CHARSET.'" action="index.php" method="post"'.$enctype.'>';

$html .= '<script type="text/javascript">document.write(\'<input type="hidden" name="javascript" value="ja">\');</script>';


if($LOGINCONF->get("initialpw") == "true") {
    $html .= returnMessage(false, getLanguageValue("initialpw"));
}

// Warnung, wenn seit dem letzten Login Logins fehlgeschlagen sind
if ($LOGINCONF->get("falselogincount") > 0) {
    $html .= returnMessage(false, getLanguageValue("warning_false_logins")." ".$LOGINCONF->get("falselogincount"));
    // Gesamt-Counter fuer falsche Logins zuruecksetzen
    if(!isset($LOGINCONF->properties['error'])) {
        $LOGINCONF->set("falselogincount", 0);
    }
}

$html .= $error_backup;
// Warnung, wenn die letzte Backupwarnung mehr als $intervallsetting Tage her ist
$intervallsetting = $ADMIN_CONF->get("backupmsgintervall");
if (($intervallsetting != "") && preg_match("/^[0-9]+$/", $intervallsetting) && ($intervallsetting > 0)) {
    $intervallinseconds = 60 * 60 * 24 * $intervallsetting;
    $lastbackup = $ADMIN_CONF->get("lastbackup");
    // initial: nur setzen 
    if ($lastbackup == "") {
        if(!isset($ADMIN_CONF->properties['error'])) {
            $ADMIN_CONF->set("lastbackup",time());
        } else {
            $html .= returnMessage(false, $ADMIN_CONF->properties['error']);
        }
    }
    // wenn schon gesetzt: pruefen und ggfs. warnen
    else {
        $nextbackup = $lastbackup + $intervallinseconds;
        if($nextbackup <= time())    {
            $html .= returnMessage(false, getLanguageValue("admin_messages_backup").'<br>Bitte bestätigen <input type="submit" name="lastbackup_yes" value="'.getLanguageValue("yes").'">');
        }
    }
}
$html .= $pagecontent;

$html .= '</form>';

$html .= "</td></tr>";

if($debug == "ja") {
    ob_start();
    echo "<div style=\"overflow:auto;width:920px;height:400px;margin:0;margin-top:20px;padding:0;\"><pre style=\"background-color:#000;color:#0f0;padding:5px;margin:0;font-family:monospace;border:2px solid #777;\">";
if(isset($debug_test))  print_r($debug_test);
    echo $debug_txt;
    echo "post ------------------------\n";
if(isset($post)) print_r($post);
    echo "</pre></div>";
    $debug_txt = ob_get_contents();
    ob_end_clean();
}

if($debug == "ja") $html .= "<tr><td>".$debug_txt."</td></tr>";

$html .= '<tr><td width="100%"><img src="gfx/clear.gif" alt=" " width="930" height="1" hspace="0" vspace="0" align="left" border="0"></td></tr></table>';
$html .= "</body></html>";



// Ausgabe als ISO 8859-1 deklarieren
header('content-type: text/html; charset='.$CHARSET.'');
/* Ausgabe der kompletten Seite */
echo $html;

/*     ------------------------------
 ZusÃ¤tzliche Funktionen
 ------------------------------ */

function home($post) {
    global $CMS_CONF;
    global $VERSION_CONF;
    global $ADMIN_CONF;
    global $MAILFUNCTIONS;
    
    $pagecontent = NULL;

    if(isset($_GET['link']) and $_GET['link'] == 1) {
        $post['error_messages']['home_error_mod_rewrite'][] = NULL;
    } elseif(isset($_GET['link']) and $_GET['link'] == 2) {
        $post['messages']['home_messages_mod_rewrite'][] = NULL;
    }
    $safemode = getLanguageValue("no");
    if(ini_get('safe_mode')) {
        $post['error_messages']['home_error_safe_mode'][] = NULL;
        $safemode = '<span style="color:#ff0000;font-weight:bold;">'.getLanguageValue("yes").'</span>';
    }

    $gdlibinstalled = getLanguageValue("yes");
    if(!extension_loaded("gd")) {
        $post['error_messages']['home_error_gd'][] = NULL;
        $gdlibinstalled = '<span style="color:#ff0000;font-weight:bold;">'.getLanguageValue("no").'</span>';
    }

# das fehlt noch !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
# if ($ADMIN_CONF->get("adminmail") != "" && $MAILFUNCTIONS->isMailAvailable()) {
    $test_mail_adress = NULL;
    if($ADMIN_CONF->get("adminmail") != "") {
        $test_mail_adress = $ADMIN_CONF->get("adminmail");
    }
    if(getRequestParam('test_mail_adresse', true) != "") {
        $test_mail_adress = getRequestParam('test_mail_adresse', true);
    }
    // Testmail schicken
    if (getRequestParam('test_mail', true)) {
        if (getRequestParam('test_mail_adresse', true) and getRequestParam('test_mail_adresse', true) != "") {
            $MAILFUNCTIONS->sendTestMail(getLanguageValue("mailtest_mailsubject"), getLanguageValue("mailtest_mailcontent"),getRequestParam('test_mail_adresse', true));
            $post['messages']['home_messages_test_mail'][] = getRequestParam('test_mail_adresse', true);
        }
        else {
            $post['error_messages']['home_error_test_mail'][] = NULL;
        }
    }


    $pagecontent .= categoriesMessages($post);

    $pagecontent .= '<span class="titel">'.getLanguageValue("home_button").'</span>';
    $pagecontent .= "<p>";
    $pagecontent .= getLanguageValue("home_text_welcome");
    $pagecontent .= "</p>";

    $path = dirname(dirname(__FILE__))."/";
#    $cmssize = convertFileSizeUnit(dirsize(getcwd()."/.."));
    $cmssize = convertFileSizeUnit(dirsize($path));
    $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">'
    // CMS-INFOS
    ."<tr>"
    .'<td width="100%" class="td_cms_titel" colspan="2"><b>'.getLanguageValue("cmsinfo").'</b></td>'
    ."</tr>"
    // Zeile "CMS-VERSION"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("cmsversion_text")."</td>"
    .'<td width="50%" class="td_cms_left">'.$VERSION_CONF->get("cmsversion").' ("'.$VERSION_CONF->get("cmsname").'")</td>'
    ."</tr>"
    // Zeile "Gesamtgröße des CMS"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("cmssize_text")."</td>"
    .'<td width="50%" class="td_cms_left">'.$cmssize."</td>"
    ."</tr>"
    // SERVER-INFOS
    ."<tr>"
    .'<td width="100%" class="td_cms_titel" colspan="2"><b>'.getLanguageValue("serverinfo").'</b></td>'
    ."</tr>"
    // Zeile "Installationspfad"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("installpath_text")."</td>"
#    .'<td class="config_row2">".dirname(getcwd()."..")."</td>"
    .'<td width="50%" class="td_cms_left">'.$path."</td>"
    ."</tr>"
    // Zeile "PHP-Version"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("phpversion_text")."</td>"
    .'<td width="50%" class="td_cms_left">'.phpversion()."</td>"
    ."</tr>"
    // Zeile "Safe Mode"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("home_text_safemode")."</td>"
    .'<td width="50%" class="td_cms_left">'.$safemode."</td>"
    ."</tr>"
    // Zeile "GDlib installiert"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("home_text_gd")."</td>"
    .'<td width="50%" class="td_cms_left">'.$gdlibinstalled."</td>"
    ."</tr>"
    // test
    ."<tr>"
    .'<td width="100%" class="td_cms_titel" colspan="2"><b>'.getLanguageValue("home_titel_mod_rewrite").'</b></td>'
    ."</tr>"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("home_text_mod_rewrite")."</td>"
    .'<td width="50%" class="td_cms_left"><a href="rewrite2.htm" style="color:#ff0000;font-weight:bold;">'.getLanguageValue("home_button_mod_rewrite").'</a></td>'
    ."</tr>"
    ."<tr>"
    .'<td width="100%" class="td_cms_titel" colspan="2"><b>'.getLanguageValue("home_titel_test_mail").'</b></td>'
    ."</tr>"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("home_text_test_mail")."</td>"
    .'<td width="50%" class="td_cms_left"><input type="submit" class="input_submit" name="test_mail" value="'.getLanguageValue("home_input_test_mail").'">&nbsp;&nbsp;&nbsp;<input type="text" class="input_mail" name="test_mail_adresse" value="'.$test_mail_adress.'"></td>'
    ."</tr>"


    ."</table>";
    return array(getLanguageValue("home_button"), $pagecontent);
}

function category($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_LINK;
    global $ADMIN_CONF;
    $icon_size = "24x24";
    $max_cat_page = 100;
    $max_strlen = 255;


    if(isset($post['action_data']) and !isset($post['error_messages'])) {
        $action_data_key = key($post['action_data']);
        if($action_data_key == "editcategory") {
            $post = editCategory($post);
        } elseif($action_data_key == "deletecategory") {
            $post = deleteCategory($post);
        }
    }

    if(isset($post['makepara']) and $post['makepara'] == "yes") {
        $post['categories'] = makePostCatPageReturnVariable($CONTENT_DIR_REL);
        if(isset($post['categories']['error_messages'])) {
            $post['error_messages'] = $post['categories']['error_messages'];
            unset($post['categories']['error_messages']);
        }
    }

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("category_button").'">';
    $pagecontent .= '<input type="hidden" name="checkpara" value="yes">';

    $pagecontent .= categoriesMessages($post);

    if(isset($post['ask'])) {
        $pagecontent .= returnMessage(true, $post['ask']);
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_category_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","category_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_category_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;page=30_Kategorien" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }

    $pagecontent .= '<span class="titel">'.getLanguageValue("category_button").'</span>';
    $pagecontent .= $tooltip_category_help;
    $pagecontent .= "<p>".getLanguageValue("category_text")."</p>";

    # Die getLanguageValue() und createTooltipWZ() erzeugen
    $array_getLanguageValue = array("pages","files","url","position","name","new_name",
        "url_adress","url_new_adress","url_adress_description","contents","category_button_change",
        "category_button_edit","category_button_delete");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }

    $array_getTooltipValue = array("help_new_url","help_target_blank","help_target_self","help_target","help_url",
        "category_help_new_position","category_help_new_name","category_help_position","category_help_delete",
        "category_help_name","category_help_edit");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        if($pos == $max_cat_page) {
            continue;
        }
        if(isset($post['displays']['cat']['error_html']['display'][$pos])) {
            $post['categories']['cat']['error_html']['display'][$pos] = $post['displays']['cat']['error_html']['display'][$pos];
        }
        if(!isset($display_new_cat)) {
            $pagecontent .= '<tr><td width="100%" class="td_toggle">';
            $pagecontent .= '<input type="submit" name="action_data[editcategory]" value="'.$text_category_button_change.'" class="input_submit">';
            $pagecontent .= '</td></tr>';
            # Neue Kategorie nicht Anzeigen wenn es schonn 100 Kategorien gibt
            if(count($post['categories']['cat']['position']) < $max_cat_page + 1) {
                $pagecontent .= '<tr><td width="100%" class="td_toggle_new">';
                $pagecontent .= '<table width="100%" class="table_new" border="0" cellspacing="0" cellpadding="0">';
                $pagecontent .= '<tr><td width="6%" class="td_left_title"><b>'.$text_position.'</b></td><td width="30%" class="td_left_title"><b>'.$text_name.'</b></td><td width="30%" class="td_left_title"><b>'.$text_url_adress.'</b> '.$text_url_adress_description.'</td><td width="6%" class="td_center_title">&nbsp;</td><td width="6%" class="td_center_title">blank</td><td width="6%" class="td_center_title">self</td><td>&nbsp;</td></tr>';
                $pagecontent .= '<tr>';
                $pagecontent .= '<td width="6%" class="td_left_title"><input type="hidden" name="categories[cat][position]['.$max_cat_page.']" value="'.$post['categories']['cat']['position'][$max_cat_page].'">';
                $pagecontent .= '<input '.$post['categories']['cat']['error_html']['new_position'][$max_cat_page].'class="input_text" type="text" name="categories[cat][new_position]['.$max_cat_page.']" value="'.$post['categories']['cat']['new_position'][$max_cat_page].'" size="2" maxlength="2"'.$tooltip_category_help_new_position.'></td>';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$max_cat_page].' class="input_text" name="categories[cat][new_name]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$max_cat_page], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_category_help_new_name.'></td>';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_url'][$max_cat_page].' class="input_text" name="categories[cat][new_url]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_url'][$max_cat_page], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_new_url.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"'.$tooltip_help_target.'><b>Target:</b></td><td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$max_cat_page.']" value="-_blank-"'.$post['categories']['cat']['checked_blank'][$max_cat_page].$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$max_cat_page.']" value="-_self-"'.$post['categories']['cat']['checked_selv'][$max_cat_page].$tooltip_help_target_self.'></td>';
                $pagecontent .= '<td>&nbsp;';
                $pagecontent .= '</td></tr>';
                $pagecontent .= '</table>';
                $pagecontent .= '</td></tr>';
            }
            $display_new_cat = true;
        }

        if (!isset($post['categories']['cat']['url'][$pos])) {
            $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
            // Anzahl Inhaltsseiten auslesen
            $pagescount = 0;
            if($pageshandle = opendir("$CONTENT_DIR_REL/".$file)) {
                while (($currentpage = readdir($pageshandle))) {
                    if(is_file("$CONTENT_DIR_REL/".$file."/".$currentpage))
                        $pagescount++;
                }
                closedir($pageshandle);
            }
            // Anzahl Dateien auslesen
            $filecount = 0;
            if($fileshandle = opendir("$CONTENT_DIR_REL/".$file."/dateien")) {
                 while (($filesdir = readdir($fileshandle))) {
                    if(isValidDirOrFile($filesdir))
                        $filecount++;
                }
                closedir($fileshandle);
            }
            $text = '('.$pagescount.' '.$text_pages.', '.$filecount.' '.$text_files.')';
        } else {
            $text = '('.$text_url.' target = '.substr($post['categories']['cat']['target'][$pos],2,-1).')';
        }

        $pagecontent .= '<tr><td width="100%" class="td_toggle">';

        $pagecontent .= '<table width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
       if(!isset($display_new_cat_name)) {
            $pagecontent .= '<tr><td width="6%" class="td_left_title"><b>'.$text_position.'</b></td><td class="td_left_title"><b>'.$text_name.'</b></td><td width="30%" class="td_left_title"><b>'.$text_contents.'</b></td><td width="15%" class="td_icons">&nbsp;</td></tr>';
            $display_new_cat_name = true;
        }
        $pagecontent .= '<tr><td width="6%" class="td_left_title"><input type="hidden" name="categories[cat][position]['.$pos.']" value="'.$post['categories']['cat']['position'][$pos].'">';
        $pagecontent .= '<input '.$post['categories']['cat']['error_html']['new_position'][$pos].'class="input_text" type="text" name="categories[cat][new_position]['.$pos.']" value="'.$post['categories']['cat']['new_position'][$pos].'" size="2" maxlength="2"'.$tooltip_category_help_position.'>';
        $pagecontent .= '</td><td class="td_left_title"><span '.$post['categories']['cat']['error_html']['name'][$pos].'class="text_cat_page">'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'</span><input type="hidden" name="categories[cat][name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'"></td><td width="30%" class="td_left_title"><span class="text_info">'.$text.'</span></td><td width="15%" class="td_icons">';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$pos.'_linkBild"'.$tooltip_category_help_edit.'></span>';
        }
        $del_dir = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if(isset($post['categories']['cat']['url'][$pos])) {
            $del_dir = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos].$post['categories']['cat']['target'][$pos].$post['categories']['cat']['url'][$pos].$EXT_LINK;
        }
        $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletecategory]['.$del_dir.']" value="'.$text_category_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$text_category_button_delete.'"'.$tooltip_category_help_delete.'>';
        $pagecontent .= '</td></tr></table></td></tr>';
        $pagecontent .= '<tr>';
        $pagecontent .= '<td width="100%" '.$post['categories']['cat']['error_html']['display'][$pos].'id="toggle_'.$pos.'" align="right" class="td_togglen_padding_bottom">';
        if(isset($post['categories']['cat']['url'][$pos])) {
            $pagecontent .= '<table width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
            $pagecontent .= '<tr><td width="30%" class="td_left_title" valign="bottom" nowrap><b>'.$text_new_name.'</b></td>';
            $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_adress.'</b></td>';
            $pagecontent .= '<td width="30%" class="td_left_title">';
            $pagecontent .= '<input type="text" class="input_readonly" name="categories[cat][url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['url'][$pos], true, true).'" maxlength="'.$max_strlen.'" readonly>';
            $pagecontent .= '</td><td>&nbsp;</td><td width="6%" valign="bottom" class="td_center_title">blank</td><td width="6%" align="center" valign="bottom" class="td_center_title">self</td><td>&nbsp;</td>';
            $pagecontent .= '</tr><tr>';
            $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$pos].' class="input_text" name="categories[cat][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$pos], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_category_help_name.'></td>';
            $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_new_adress.'</b></td>';
            $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_url'][$pos].' class="input_text" name="categories[cat][new_url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_url'][$pos], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_url.'></td>';
            $pagecontent .= '<td width="6%" class="td_center_title"><b'.$tooltip_help_target.'>Target:</b></td><td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$pos.']" value="-_blank-"'.$post['categories']['cat']['checked_blank'][$pos].$tooltip_help_target_blank.'></td>';
            $pagecontent .= '<td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$pos.']" value="-_self-"'.$post['categories']['cat']['checked_selv'][$pos].$tooltip_help_target_self.'><input type="hidden" name="categories[cat][target]['.$pos.']" value="'.$post['categories']['cat']['target'][$pos].'"></td>';
            $pagecontent .= '<td>&nbsp;</td></tr></table>';
        } else
            $pagecontent .= '<table width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0"><tr><td colspan="2" class="td_left_title" align="left" valign="bottom" ><b>'.$text_new_name.'</b></td></tr><tr><td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$pos].'class="input_text" name="categories[cat][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$pos], true, true).'"'.$tooltip_category_help_name.'></td><td>&nbsp;</td></tr></table>';

        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_category_button_edit.'\');</script>';
        }
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= '<tr><td width="100%" class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[editcategory]" value="'.$text_category_button_change.'" class="input_submit">';
    $pagecontent .= '</td></tr></table>';
    return array(getLanguageValue("category_button"), $pagecontent);
}



function editCategory($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $EXT_LINK;
    global $CMS_CONF;
    $max_cat_page = 100;

    $new_move_cat_page = false;
    if(!empty($post['categories']['cat']['new_position'][$max_cat_page])) {
        $new_move_cat_page[] = $post['categories']['cat']['new_position'][$max_cat_page];
    }

    $array_return = position_move($post['categories']['cat']['position'],$post['categories']['cat']['new_position'],$new_move_cat_page);

    # Rename Vorbereiten
    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        # $max_cat_page = new_cat
        if($pos == $max_cat_page) {
            continue;
        }
        if(isset($array_return['move'][sprintf("%1d",$position)])) {
            $post['categories']['cat']['new_position'][$pos] = $array_return['move'][sprintf("%1d",$position)];
        }
        # wenn new_name, new_target, new_url
        if(!empty($post['categories']['cat']['new_name'][$pos]))
            $newname[$pos] = $post['categories']['cat']['new_position'][$pos]."_".$post['categories']['cat']['new_name'][$pos];
        else
            $newname[$pos] = $post['categories']['cat']['new_position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if(isset($post['categories']['cat']['url'][$pos])) {
            $orgname[$pos] = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos].$post['categories']['cat']['target'][$pos].$post['categories']['cat']['url'][$pos].$EXT_LINK;
            if(!empty($post['categories']['cat']['new_target'][$pos]))
                $newname[$pos] = $newname[$pos].$post['categories']['cat']['new_target'][$pos];
            else
                $newname[$pos] = $newname[$pos].$post['categories']['cat']['target'][$pos];
            if(!empty($post['categories']['cat']['new_url'][$pos]))
                $newname[$pos] = $newname[$pos].$post['categories']['cat']['new_url'][$pos].$EXT_LINK;
            else
                $newname[$pos] = $newname[$pos].$post['categories']['cat']['url'][$pos].$EXT_LINK;
        } else {
            $orgname[$pos] = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        }
        if($newname[$pos] == $orgname[$pos]) {
            unset($newname[$pos],$orgname[$pos]);
        }
    }

    # Rename $orgname[$pos] -> $newname[$pos]
    if(isset($newname)) {
        $mesages = NULL;
        $error_mesages = NULL;
        foreach ($newname as $pos => $tmp) {
            @rename("$CONTENT_DIR_REL/".$orgname[$pos], "$CONTENT_DIR_REL/".$newname[$pos]);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
                $post['display']['cat']['error_html']['display'][$pos] = 'style="display:block;" ';
            } elseif(!is_dir("$CONTENT_DIR_REL/".$newname[$pos])) {
                $post['error_messages']['category_error_rename'][] = $orgname[$pos];
                $post['displays']['cat']['error_html']['display'][$pos] = 'style="display:block;" ';
            } else {
                if(!isset($post['categories']['cat']['url'][$pos])) {
                    // Referenzen auf die umbenannte Kategorie in der Download-Statistik Ändern
                    renameCategoryInDownloadStats($orgname[$pos],$newname[$pos]);
                    // Referenzen auf die umbenannte Kategorie in allen Inhaltsseiten Ändern
                    $error['updateReferences'] = updateReferencesInAllContentPages($orgname[$pos],"",$newname[$pos],"");
                    if(is_array($error['updateReferences'])) {
                        $post['makepara'] = "yes";
                        if(isset($post['error_messages']) and is_array($post['error_messages'])) {
                            $post['error_messages'] = array_merge_recursive($post['error_messages'],$error);
                        } else {
                            $post['error_messages'] = $error;
                        }
                    }
                    if($CMS_CONF->get('defaultcat') == $orgname[$pos]) {
                        $CMS_CONF->set('defaultcat',$newname[$pos]);
                        $post['messages']['category_message_defaultcat'][] = $newname[$pos];
                    }
                }
                $post['messages']['category_message_rename'][] = $orgname[$pos]." <b>></b> ".$newname[$pos];
                $post['makepara'] = "yes";
            }
        }
    }
    # Neue Kategorie erstellen
    if(!empty($post['categories']['cat']['new_position'][$max_cat_page])) {
        $new_position = $post['categories']['cat']['new_position'][$max_cat_page];
        # Aufbau: Orig Position mit null => Neue (von dieser function) oder Orig Position mit null
        if(isset($array_return['new_move_cat_page_newposition']) and is_array($array_return['new_move_cat_page_newposition'])) {
            if(isset($array_return['new_move_cat_page_newposition'][$post['categories']['cat']['new_position'][$max_cat_page]])) {
                $new_position = $array_return['new_move_cat_page_newposition'][$post['categories']['cat']['new_position'][$max_cat_page]];
            }
        }
        $new_cat = $new_position."_".$post['categories']['cat']['new_name'][$max_cat_page];

        if(!empty($post['categories']['cat']['new_url'][$max_cat_page])) {
            $new_cat .= $post['categories']['cat']['new_target'][$max_cat_page].$post['categories']['cat']['new_url'][$max_cat_page].$EXT_LINK;
        }
        $post['error_messages'] = createCategory($new_cat);
        if(isset($post['error_messages'])) {
            $post['makepara'] = "no"; # kein makePostCatPageReturnVariable()
        } else {
            if(empty($post['categories']['cat']['new_url'][$max_cat_page])) {
                $post['messages']['category_message_new'][] = "$new_cat";
            } else {
                $post['messages']['message_link'][] = "$new_cat";
            }
            $post['makepara'] = "yes"; # kein makePostCatPageReturnVariable()
        }
    }
    return $post;
}

function deleteCategory($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $CMS_CONF;

    $icon_size = '24x24';
    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $del_cat = key($post['action_data']['deletecategory']);
        $post['ask'] = getLanguageValue("category_ask_delete").'<br><span style="font-weight:normal;">-&gt;&nbsp;&nbsp;'.messagesOutLen($specialchars->rebuildSpecialChars($del_cat, true, true)).'</span>&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="true" alt="'.getLanguageValue("yes").'" src="gfx/icons/'.$icon_size.'/accept.png" title="'.getLanguageValue("yes").'" style="vertical-align:middle;">&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="false" alt="'.getLanguageValue("no").'" src="gfx/icons/'.$icon_size.'/cancel.png" title="'.getLanguageValue("no").'" style="vertical-align:middle;"><input type="hidden" name="action_data[deletecategory]" value="'.$post['action_data']['deletecategory'].'"><input type="hidden" name="del_cat" value="'.$del_cat.'">';
        $post['makepara'] = "yes";
        return $post;
    }
    # Kategorie Löschen    
    if(isset($_POST['confirm']) and $_POST['confirm'] == "true" and isset($_POST['del_cat']) and !empty($_POST['del_cat'])) {
        $del_cat = "$CONTENT_DIR_REL/".$_POST['del_cat'];
        $post['error_messages'] = deleteDir($del_cat);
        if(!file_exists($_POST['del_cat'])) {
            // Alle Dateien der gelöschten Kategorie aus Downloadstatistik entfernen
            deleteCategoryFromDownloadStats($_POST['del_cat']);
            $post['messages']['category_message_delete'][] = $_POST['del_cat'];
            $post['makepara'] = "yes";
            if($CMS_CONF->get('defaultcat') == $_POST['del_cat']) {
                $tmp_array = getDirs($CONTENT_DIR_REL,true,true);
                $CMS_CONF->set('defaultcat',$tmp_array[0]);
                $post['messages']['category_message_defaultcat'][] = $tmp_array[0];
            }
            return $post;
        } else {
            if(!isset($post['error_messages'])) {
                $post['error_messages']['category_error_delete'][] = $_POST['del_cat'];
            }
            $post['makepara'] = "yes";
            return $post;
        }
    }
    return $post;
}

function page($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_PAGE;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;
    global $EXT_LINK;
    global $ADMIN_CONF;

    $icon_size = "24x24";
    $max_cat_page = 100;
    $max_strlen = 255;
    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("page_button").'">';




    if(isset($post['action_data']) and !isset($post['error_messages'])) {
        if(key($post['action_data']) == "editsite") {
            $post = editSite($post);
            if($post['editsite'] == 'yes') {
                $pagecontent .= categoriesMessages($post);
                $cat = key($post['action_data']['editsite']);
                $page = $post['action_data']['editsite'][$cat];
                $pagecontent .= '<span class="titel">'.getLanguageValue("page_edit").' -&gt; </span>'.$specialchars->rebuildSpecialChars(substr($cat,3), true, true).'/'.$specialchars->rebuildSpecialChars(substr($page,3,-(strlen($EXT_PAGE))), true, true);
                $pagecontent .= $post['content'];
                $pagecontent .= '<input type="hidden" name="checkpara" value="no">';
                return array(getLanguageValue("page_button"), $pagecontent);
            }
        }
        if(key($post['action_data']) == "deletesite") {
            $post = deleteSite($post);
        }
        if(key($post['action_data']) == "copymovesite") {
             $post = copymoveSite($post);
        }
    }
# das noch anpassen!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $pagecontent .= '<input type="hidden" name="checkpara" value="yes">';

# brauch ich das yes?????????????????????????????????????????????????????
    if(isset($post['makepara']) and $post['makepara'] == "yes") {
        $post['categories'] = makePostCatPageReturnVariable($CONTENT_DIR_REL,true);
        if(isset($post['categories']['error_messages'])) {
            $post['error_messages'] = $post['categories']['error_messages'];
            unset($post['categories']['error_messages']);
        }
    }

    if(isset($post['ask'])) {
        $pagecontent .= returnMessage(true, $post['ask']);
    }

    $pagecontent .= categoriesMessages($post);

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }
    $pagecontent .= '<span class="titel">'.getLanguageValue("page_button").'</span>';
    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_page_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","page_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_page_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;page=40_Inhaltsseiten" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }
    $pagecontent .= $tooltip_page_help;
    $pagecontent .= "<p>".getLanguageValue("page_text")."</p>";

    $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" cellpadding="0">';# border="0"
    $pagecontent .= '<tr><td width="100%" class="td_toggle"><input type="submit" name="action_data[copymovesite]" value="'.getLanguageValue("page_button_change").'" class="input_submit"></td></tr>';


    # Die getLanguageValue() und createTooltipWZ() erzeugen
    $array_getLanguageValue = array("pages","position","name","new_name","url_adress","url_new_adress","url_adress_description","page_button_edit","page_move","page_status","page_saveasdraft","page_saveasnormal","page_saveashidden","page_copy","page_button_edit","page_button_delete");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }

    $array_getTooltipValue = array("help_new_url","help_target_blank","help_target_self","help_target","help_url",
        "page_help_edit","page_help_position","page_help_new_position","page_help_name","page_help_new_name",
        "page_help_move","page_help_editieren","page_help_delete","page_help_copy");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    foreach ($post['categories'] as $cat => $tmp) {
        unset($display_new_cat,$display_new_cat_name);
        if(isset($post['display'][$cat]['error_html']['display_cat'])) {
            $post['categories'][$cat]['error_html']['display_cat'] = $post['display'][$cat]['error_html']['display_cat'];
        }

        $pagecontent .= '<tr><td width="100%" class="td_toggle">';
        $pagecontent .= '<table width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
        $pagecontent .= '<tr><td class="td_left_title">';
        $pagecontent .= '<span '.$post['categories'][$cat]['error_html']['cat_name'].'class="text_cat_page">'.$specialchars->rebuildSpecialChars(substr($cat,3), true, true).'</span><input type="hidden" name="categories['.$cat.']" value="'.$cat.'">';
        $pagescount = 0;
        if($pageshandle = opendir("$CONTENT_DIR_REL/$cat/")) {
            while (($currentpage = readdir($pageshandle))) {
                if(is_file("$CONTENT_DIR_REL/".$cat."/".$currentpage) and ctype_digit(substr($currentpage,0,2)))
                    $pagescount++;
            }
            closedir($pageshandle);
        }
        $text = '('.$pagescount.' '.$text_pages.')'; 
        $pagecontent .= '<td width="20%" class="td_left_title"><span class="text_info">'.$text.'</span></td>';
        $pagecontent .= '<td width="15%" class="td_icons">&nbsp;';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.substr($cat,0,2).'_linkBild"'.$tooltip_page_help_edit.'></span>';
        }
        $pagecontent .= '</td></tr>';
        $pagecontent .= '</table>';
        $pagecontent .= '</td></tr>';
        $pagecontent .= '<tr><td width="100%" '.$post['categories'][$cat]['error_html']['display_cat'].'class="td_togglen" align="right" id="toggle_'.substr($cat,0,2).'">';# align="right"
        if(getRequestParam('javascript', true)) {

            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.substr($cat,0,2).'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_page_button_edit.'\');</script>';
        }
        $pagecontent .= '<table width="98%" cellspacing="0" cellpadding="0" border="0"><tr><td width="100%">';

        $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" cellpadding="0" border="0">';
        foreach ($post['categories'][$cat]['position'] as $pos => $tmp) {
            if(isset($post['display'][$cat]['error_html']['display'][$pos])) {
                $post['categories'][$cat]['error_html']['display'][$pos] = $post['display'][$cat]['error_html']['display'][$pos];
            }
            # Neue Inhaltseite oder Link
            if(!isset($display_new_cat)) {
                $pagecontent .= '<tr><td width="100%" class="td_toggle_new">';
                $pagecontent .= '<table width="100%" class="table_data" border="0" cellspacing="0" cellpadding="0">';
                $pagecontent .= '<tr><td width="6%" class="td_left_title"><b>'.$text_position.'</b></td><td width="30%" class="td_left_title"><b>'.$text_name.'</b></td><td width="30%" class="td_left_title"><b>'.$text_url_adress.'</b> '.$text_url_adress_description.'</td><td width="6%" class="td_center_title">&nbsp;</td><td width="6%" class="td_center_title">blank</td><td width="6%" class="td_center_title">self</td><td>&nbsp;</td></tr>';
                $pagecontent .= '<tr>';
                $pagecontent .= '<td width="6%" class="td_left_title"><input type="hidden" name="categories['.$cat.'][position]['.$max_cat_page.']" value="'.$post['categories'][$cat]['position'][$max_cat_page].'">';
                $pagecontent .= '<input '.$post['categories'][$cat]['error_html']['new_position'][$max_cat_page].'class="input_text" type="text" name="categories['.$cat.'][new_position]['.$max_cat_page.']" value="'.$post['categories'][$cat]['new_position'][$max_cat_page].'" size="2" maxlength="2"'.$tooltip_page_help_new_position.'></td>';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$max_cat_page].' class="input_text" name="categories['.$cat.'][new_name]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$max_cat_page],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_new_name.'></td>';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_url'][$max_cat_page].' class="input_text" name="categories['.$cat.'][new_url]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_url'][$max_cat_page],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_new_url.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"'.$tooltip_help_target.'><b>Target:</b></td><td width="6%" class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$max_cat_page.']" value="-_blank-"'.$post['categories'][$cat]['checked_blank'][$max_cat_page].''.$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$max_cat_page.']" value="-_self-"'.$post['categories'][$cat]['checked_selv'][$max_cat_page].''.$tooltip_help_target_self.'></td>';
                $pagecontent .= '<td nowrap>&nbsp;';
                $pagecontent .= '</td>';
                $pagecontent .= '</tr></table>';

                $pagecontent .= '</td></tr>';
                $display_new_cat = true;
            }
        if($pos == $max_cat_page) continue;
            # Select Box erstellen 
            $select_box = '<select name="categories['.$cat.'][new_cat]['.$pos.']" size="1" class="input_select"'.$tooltip_page_help_move.'>'."\n";
            $select_option = NULL;
            foreach ($post['categories'] as $option_cat => $tmp) {
                $selected = NULL;
                if(isset($post['categories'][$cat]['new_cat'][$pos]) and $post['categories'][$cat]['new_cat'][$pos] == $option_cat) {
                    $selected = ' selected="selected"';
                } elseif($option_cat == $cat) {
                    $selected = ' selected="selected"';
                }
                if($option_cat == $cat) {
                    $first_option = '<option value="'.$option_cat.'"'.$selected.'>&nbsp;</option>';
                    continue;
                }
                $select_option .= '<option value="'.$option_cat.'"'.$selected.'>'.$specialchars->rebuildSpecialChars(substr($option_cat,3),true,true)."</option>\n";
            }
            $select_box .= $first_option.$select_option.'</select>'."\n";

            $pagecontent .= '<tr><td width="100%" class="td_toggle_padding">';

            $pagecontent .= '<table width="100%" class="table_data" border="0" cellspacing="0" cellpadding="0">';
            if(!isset($display_new_cat_name)) {
                $pagecontent .= '<tr><td width="6%" class="td_left_title"><b>'.$text_position.'</b></td><td class="td_left_title"><b>'.$text_name.'</b></td><td width="12%" class="td_left_title"><b>'.$text_page_status.'</b></td><td width="17%" class="td_left_title"><b>'.$text_page_move.'</b></td><td width="12%" class="td_left_title">&nbsp;</td><td width="15%" class="td_icons">&nbsp;</td></tr>';
                $display_new_cat_name = true;
            }
            $pagecontent .= '<tr><td width="6%" class="td_left_title"><input '.$post['categories'][$cat]['error_html']['new_position'][$pos].'class="input_text" type="text" name="categories['.$cat.'][new_position]['.$pos.']" value="'.$post['categories'][$cat]['new_position'][$pos].'" size="2" maxlength="2"'.$tooltip_page_help_position.'><input type="hidden" name="categories['.$cat.'][position]['.$pos.']" value="'.$post['categories'][$cat]['position'][$pos].'"></td>';
            $pagecontent .= '<td class="td_left_title">';
            if($post['categories'][$cat]['ext'][$pos] == $EXT_PAGE) {
                $text = "(".$text_page_saveasnormal.")";
            } elseif($post['categories'][$cat]['ext'][$pos] == $EXT_DRAFT) {
                $text = "(".$text_page_saveasdraft.")";
            } elseif($post['categories'][$cat]['ext'][$pos] == $EXT_HIDDEN) {
                $text = "(".$text_page_saveashidden.")";
            } elseif(isset($post['categories'][$cat]['url'][$pos])) {
                $text = "(Target: ".substr($post['categories'][$cat]['target'][$pos],2,-1).")";
            } else {
                $text = NULL;
            }
            $pagecontent .= '<span '.$post['categories'][$cat]['error_html']['name'][$pos].'class="text_cat_page">'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['name'][$pos],true,true).'</span>';

            $pagecontent .= '<input type="hidden" name="categories['.$cat.'][name]['.$pos.']" value="'.$post['categories'][$cat]['name'][$pos].'"><input type="hidden" name="categories['.$cat.'][ext]['.$pos.']" value="'.substr($post['categories'][$cat]['ext'][$pos],-(strlen($EXT_PAGE))).'"></td>';
            $pagecontent .= '<td width="12%" class="td_left_title" nowrap><span class="text_info">'.$text.'</span></td>';
            $pagecontent .= '<td width="17%" class="td_left_title" nowrap>'.$select_box.'</td>';
            $pagecontent .= '<td width="12%" class="td_left_title" nowrap><b>'.$text_page_copy.'</b>&nbsp;<input class="input_check" type="checkbox" name="categories['.$cat.'][copy]['.$pos.']" value="yes"'.$post['categories'][$cat]['copy'][$pos].''.$tooltip_page_help_copy.'></td>';

            $pagecontent .= '<td width="15%" class="td_icons" nowrap>';
            if(getRequestParam('javascript', true)) {
                $pagecontent .= '<span id="toggle_'.substr($cat,0,2).'_'.$pos.'_linkBild"'.$tooltip_page_help_edit.'></span>';
            }
            if(!isset($post['categories'][$cat]['url'][$pos])) {
                $pagecontent .= '<input type="image" class="input_img_button" name="action_data[editsite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_edit.'"  src="gfx/icons/'.$icon_size.'/page-edit.png" title="'.$text_page_button_edit.'"'.$tooltip_page_help_editieren.'>';
                $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletesite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$text_page_button_delete.'"'.$tooltip_page_help_delete.'>';
            } else {
                $pagecontent .= '<input type="image" class="input_nobefor_img_button_last" name="action_data[deletesite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['target'][$pos].$post['categories'][$cat]['url'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$text_page_button_delete.'"'.$tooltip_page_help_delete.'>';
            }
            $pagecontent .= '</td></tr></table>';
            $pagecontent .= '</td></tr>';
            $pagecontent .= '<tr>';
            $pagecontent .= '<td width="100%" class="td_togglen_padding_bottom" align="right" '.$post['categories'][$cat]['error_html']['display'][$pos].' id="toggle_'.substr($cat,0,2).'_'.$pos.'">';

            if(isset($post['categories'][$cat]['url'][$pos])) {
                $pagecontent .= '<table width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data"><tr><td width="30%" valign="bottom" nowrap class="td_left_title"><b>'.$text_new_name.'</b></td>';
                $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_adress.'</b></td>';
                $pagecontent .= '<td width="30%" class="td_left_title">';
                $pagecontent .= '<input type="text" class="input_readonly" name="categories['.$cat.'][url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['url'][$pos],true,true).'" maxlength="'.$max_strlen.'" readonly>';

                $pagecontent .= '</td><td width="6%" class="td_center_title">&nbsp;</td><td width="6%" valign="bottom" class="td_center_title">blank</td><td width="6%" valign="bottom" class="td_center_title">self</td><td class="td_left_title">&nbsp;</td>';
                $pagecontent .= '</tr><tr>';
                $pagecontent .= '';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$pos].' class="input_text" name="categories['.$cat.'][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_name.'></td>';
                $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_new_adress.'</b></td>';
                $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_url'][$pos].' class="input_text" name="categories['.$cat.'][new_url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_url'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_url.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"'.$tooltip_help_target.'><b>Target:</b></td><td width="6%" class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$pos.']" value="-_blank-"'.$post['categories'][$cat]['checked_blank'][$pos].''.$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td width="6%" class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$pos.']" value="-_self-"'.$post['categories'][$cat]['checked_selv'][$pos].''.$tooltip_help_target_self.'><input type="hidden" name="categories['.$cat.'][target]['.$pos.']" value="'.$post['categories'][$cat]['target'][$pos].'"></td><td>&nbsp;</td>';
                $pagecontent .= '</tr></table>';
            } else {
                $pagecontent .= '<table width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0"><tr><td width="30%" class="td_left_title" nowrap><b>'.$text_new_name.'</b></td><td class="td_left_title">&nbsp;</td></tr>';
                $pagecontent .= '<tr><td width="30%" class="td_left_title" nowrap><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$pos].' class="input_text" name="categories['.$cat.'][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_name.'></td><td>&nbsp;</td>';
                $pagecontent .= '</tr></table>';
            }

            if(getRequestParam('javascript', true)) {
                $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.substr($cat,0,2).'_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_page_button_edit.'\');</script>';
            }
            $pagecontent .= '</td></tr>';
        } # foreach page
        $pagecontent .= '</table>';
        $pagecontent .= '</td></tr></table>';
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= '<tr><td width="100%" class="td_toggle"><input type="submit" name="action_data[copymovesite]" value="'.getLanguageValue("page_button_change").'" class="input_submit"></td></tr>';
    $pagecontent .= '</table>';
    return array(getLanguageValue("page_button"), $pagecontent);
}


function editSite($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_PAGE;

    $cat = key($post['action_data']['editsite']);
    $page = $post['action_data']['editsite'][$cat];

    $input_merker = NULL;
    $post['editsite'] = 'yes';
    $content = "editsite";
    if(isset($_POST['save']) or isset($_POST['savetemp'])) {
        $new_ext = $_POST['saveas'];
        $new_page = substr($post['action_data']['editsite'][$cat],0,-(strlen($EXT_PAGE))).$new_ext;
        if($new_page != $post['action_data']['editsite'][$cat]) {
            @rename($CONTENT_DIR_REL."/".$cat."/".$page,$CONTENT_DIR_REL."/".$cat."/".$new_page);
            $line_error = __LINE__ - 1;
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(!is_file("$CONTENT_DIR_REL/$cat/$new_page")) {
                $post['error_messages']['page_message_rename'][] = $page." <b>></b> ".$new_page;
            } else {
                # ext hat sich geändert
                $page = $new_page;
            }
        }
        $error = saveContentToPage($_POST['pagecontent'],$CONTENT_DIR_REL."/".$cat."/".$page);
        if(is_array($error)) {
            if(isset($post['error_messages'])) {
                $post['error_messages'] = array_merge_recursive($post['error_messages'],$error);
            } else {
                $post['error_messages'] = $error;
            }
            $content = $_POST['pagecontent'];
        }
        if(isset($_POST['savetemp']) or isset($post['error_messages'])) {
            $post['content'] = showEditPageForm($cat, $page,$content).$input_merker;
        } else {
            $post['editsite'] = 'no';
        }
    } elseif(isset($_POST['cancel'])) {
        $post['editsite'] = 'no';
    } else {
        $post['content'] = showEditPageForm($cat, $page,$content).$input_merker;
    }
    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
    $post['makepara'] = "yes";
    return $post;
}

function copymoveSite($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_LINK;
    global $EXT_PAGE;
    $max_cat_page = 100;

    # wenn Verschoben wird unset die Position der Inhaltseite aus anderer Kategorie
    # damit bei position_move() die Positionen frei sind
    if(isset($post['move_copy'])) {
        foreach($post['move_copy']['source']['position'] as $position => $tmp) {
            if(!isset($post['move_copy']['source']['copy'][$position])) {
                unset($post['categories'][$post['move_copy']['source']['cat'][$position]]['position'][$position]);
                unset($post['categories'][$post['move_copy']['source']['cat'][$position]]['new_position'][$position]);
            }
        }
    }
    # Alle Kategorien durch gehen
    foreach($post['categories'] as $cat => $tmp) {
        # Kategorien die keine Inhaltseiten haben und auch keine neue = continue
        if(count($post['categories'][$cat]['position']) <= 1 and empty($post['categories'][$cat]['new_position'][$max_cat_page])) {
            continue;
        }

        # rename bauen wenn die position sich nicht geändert hat aber was anderes
        foreach($post['categories'][$cat]['position'] as $pos => $tmp) {
            # Neue Inhaltseite nicht hier
            if($pos == $max_cat_page) {
                continue;
            }
            # Neue Position nicht hier
            if($post['categories'][$cat]['position'][$pos] != $post['categories'][$cat]['new_position'][$pos]) {
                continue;
            }
            # Die Inhaltseite wird Kopiert also kein Rename hier
            if(isset($post['categories'][$cat]['copy'][$pos])) {
                continue;
            }
            # Nur wenn new_name oder new_url oder new_target
            if(isset($post['categories'][$cat]['url'][$pos])) {
                if(empty($post['categories'][$cat]['new_name'][$pos]) and empty($post['categories'][$cat]['new_url'][$pos]) and empty($post['categories'][$cat]['new_target'][$pos])) {
                    continue;
                }
            } elseif(empty($post['categories'][$cat]['new_name'][$pos])) {
                 continue;
            }
            $name = $post['categories'][$cat]['name'][$pos];
            if(!empty($post['categories'][$cat]['new_name'][$pos])) {
                $name = $post['categories'][$cat]['new_name'][$pos];
            }
            # Wenns kein Link ist
            if(!isset($post['categories'][$cat]['url'][$pos])) {
                $rename_newname[$cat][$pos] = $post['categories'][$cat]['new_position'][$pos].'_'.$name.$post['categories'][$cat]['ext'][$pos];
                $rename_orgname[$cat][$pos] = $post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos];
            } else {
                # Wenns ein Link ist
                $url = $post['categories'][$cat]['url'][$pos];
                if(!empty($post['categories'][$cat]['new_url'][$pos])) {
                    $url = $post['categories'][$cat]['new_url'][$pos];
                }
                $target = $post['categories'][$cat]['target'][$pos];
                if(!empty($post['categories'][$cat]['new_target'][$pos])) {
                    $target = $post['categories'][$cat]['new_target'][$pos];
                }
                $rename_newname[$cat][$pos] = $post['categories'][$cat]['new_position'][$pos].'_'.$name.$target.$url.$post['categories'][$cat]['ext'][$pos];
                $rename_orgname[$cat][$pos] = $post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['target'][$pos].$post['categories'][$cat]['url'][$pos].$post['categories'][$cat]['ext'][$pos];
            }
        }

        # Neue Inhaltseite
        $new_move_cat_page = false;
        if(!empty($post['categories'][$cat]['new_position'][$max_cat_page])) {
            $new_move_cat_page[] = $post['categories'][$cat]['new_position'][$max_cat_page];
        }
        # Die Copy Move sachen ins Arrey einsetzen (Inhaltseiten die aus andere Kategorie kommen)
        if(isset($post['move_copy']) and in_array($cat,$post['move_copy']['desti']['cat'])) {
            $z = 0;
            foreach($post['move_copy']['desti']['new_position'] as $position => $new_position) {
                $new_move_cat_page[] = $new_position;
                if(isset($post['move_copy']['source']['copy'][$position])) {
                    $move_copy_newname[$z]['copy'] = $post['move_copy']['source']['copy'][$position];
                }
                $move_copy_newname[$z]['name'] = $post['move_copy']['desti']['name'][$position].$post['move_copy']['desti']['ext'][$position];

                $move_copy_newname[$z]['position'] = $new_position;
                $move_copy_newname[$z]['org_name'] = $post['move_copy']['source']['cat'][$position]."/".$post['move_copy']['source']['position'][$position]."_".$post['move_copy']['source']['name'][$position].$post['move_copy']['desti']['ext'][$position];
                $z++;

            }
        }
        # jetzt alle position_move sachen machen (Neue Inhaltseite, copy_move und Positions wechsel)
        $array_return = position_move($post['categories'][$cat]['position'],$post['categories'][$cat]['new_position'],$new_move_cat_page);

        # hier die Inhatseiten aus anderer Kategorie fürs copy unlink bauen
        if(isset($move_copy_newname)) {
            foreach($move_copy_newname as $z => $tmp) {
                # wenn die Position von position_move() geändert wurde
                $new_position = $move_copy_newname[$z]['position'];
                if(isset($array_return['move_cat_page_newposition'][$move_copy_newname[$z]['position']])) {
                    $new_position = $array_return['move_cat_page_newposition'][$move_copy_newname[$z]['position']];
                }
                if(!isset($move_copy_newname[$z]['copy'])) {
                    $move_move[$cat][$z] = true;
                }
                $move_orgname[$cat][$z] = $move_copy_newname[$z]['org_name'];
                $move_newname[$cat][$z] = $cat."/".$new_position."_".$move_copy_newname[$z]['name'];
            }
            unset($move_copy_newname);
        }

        # die neuen Positionen einsetzen
        if($array_return['move'] !== false) {
            foreach($array_return['move'] as $pos => $new_position) {
                # Die Inhaltseite wird Kopiert also kein Rename hier
                if(isset($post['categories'][$cat]['copy'][$pos])) {
                    continue;
                }
                $name = $post['categories'][$cat]['name'][$pos];
                if(!empty($post['categories'][$cat]['new_name'][$pos])) {
                    $name = $post['categories'][$cat]['new_name'][$pos];
                }
                # Wenns kein Link ist
                if(!isset($post['categories'][$cat]['url'][$pos])) {
                    $rename_newname[$cat][$pos] = $new_position.'_'.$name.$post['categories'][$cat]['ext'][$pos];
                    $rename_orgname[$cat][$pos] = $post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos];
                } else {
                    # Wenns ein Link ist
                    $url = $post['categories'][$cat]['url'][$pos];
                    if(!empty($post['categories'][$cat]['new_url'][$pos])) {
                        $url = $post['categories'][$cat]['new_url'][$pos];
                    }
                    $target = $post['categories'][$cat]['target'][$pos];
                    if(!empty($post['categories'][$cat]['new_target'][$pos])) {
                        $target = $post['categories'][$cat]['new_target'][$pos];
                    }
                    $rename_newname[$cat][$pos] = $new_position.'_'.$name.$target.$url.$post['categories'][$cat]['ext'][$pos];
                    $rename_orgname[$cat][$pos] = $post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['target'][$pos].$post['categories'][$cat]['url'][$pos].$post['categories'][$cat]['ext'][$pos];
                }
            }
        }
        # die neue Inhaltseite
        if(!empty($post['categories'][$cat]['new_position'][$max_cat_page])) {
            $new_position = $post['categories'][$cat]['new_position'][$max_cat_page];
            $new_page[$cat] = $new_position."_".$post['categories'][$cat]['new_name'][$max_cat_page].$EXT_PAGE;
            if(!empty($post['categories'][$cat]['new_url'][$max_cat_page])) {
                $new_page[$cat] = $new_position."_".$post['categories'][$cat]['new_name'][$max_cat_page].$post['categories'][$cat]['new_target'][$max_cat_page].$post['categories'][$cat]['new_url'][$max_cat_page].$EXT_LINK;
            }
        }
    }
    # Rename
    if(isset($rename_newname)) {
        foreach($rename_newname as $cat => $tmp) {
            foreach($rename_newname[$cat] as $z => $tmp) {
                @rename($CONTENT_DIR_REL."/".$cat."/".$rename_orgname[$cat][$z],$CONTENT_DIR_REL."/".$cat."/".$rename_newname[$cat][$z]);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error = @error_get_last();
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
# was ist mit target??????????????????????????????????????????????
                    if(substr($rename_orgname[$cat][$z],3) != substr($rename_newname[$cat][$z],3)) {
                        $post['display'][$cat]['error_html']['display'][sprintf("%1d",substr($rename_orgname[$cat][$z],0,2))] = 'style="display:block;" '; # letzte cat ausklappen
                    }
                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
                    $post['makepara'] = "no"; # kein makePostCatPageReturnVariable()
                } elseif(!is_file($CONTENT_DIR_REL."/".$cat."/".$rename_newname[$cat][$z])) {
                    $post['error_messages']['page_error_rename'][] =  $cat."/".$rename_orgname[$cat][$z]." <b>></b> ".$cat."/".$rename_newname[$cat][$z];
 # was ist mit target??????????????????????????????????????????????
                   if(substr($rename_orgname[$cat][$z],3) != substr($rename_newname[$cat][$z],3)) {
                        $post['display'][$cat]['error_html']['display'][sprintf("%1d",substr($rename_orgname[$cat][$z],0,2))] = 'style="display:block;" '; # letzte cat ausklappen
                    }
                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
                    $post['makepara'] = "no"; # kein makePostCatPageReturnVariable()
                # Alles gut
                } else {
                    $post['messages']['page_message_rename'][] = $cat."/".$rename_orgname[$cat][$z]." <b>></b> ".$cat."/".$rename_newname[$cat][$z];
                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
                    $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                    # nur wenn name != neu_name also die Position wird nicht berücksichtigt
                    if(substr($rename_orgname[$cat][$z],3) != substr($rename_newname[$cat][$z],3)) {
                        $error_message['updateReferences'] = updateReferencesInAllContentPages($cat, $rename_orgname[$cat][$z], "", $rename_newname[$cat][$z]);
                        if(is_array($error_message['updateReferences'])) {
                            $post['makepara'] = "yes";
                            if(isset($post['error_messages']) and is_array($post['error_messages'])) {
                                $post['error_messages'] = array_merge_recursive($post['error_messages'],$error_message);
                            } else {
                                $post['error_messages'] = $error_message;
                            }
                        }
                    }
                }
            }
        }
    }
    # Copy Move
    if(isset($move_newname)) {
        foreach($move_newname as $cat => $tmp) {
            foreach($move_newname[$cat] as $z => $tmp) {
                # Einfach mal Kopieren
                @copy($CONTENT_DIR_REL."/".$move_orgname[$cat][$z],$CONTENT_DIR_REL."/".$move_newname[$cat][$z]);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error = @error_get_last();
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                    $error = true;
                } elseif(!is_file($CONTENT_DIR_REL."/".$move_newname[$cat][$z])) {
                    $post['error_messages']['page_error_copy'][] = $move_orgname[$cat][$z]." <b>></b> ".$move_newname[$cat][$z];
                    $error = true;
                }
                # wenn Verschoben werden soll und copy erfolgreich war
                if(isset($move_move[$cat][$z]) and is_file($CONTENT_DIR_REL."/".$move_newname[$cat][$z])) {
                    @unlink($CONTENT_DIR_REL."/".$move_orgname[$cat][$z]);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error = @error_get_last();
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                        $error = true;
                    } elseif(is_file($CONTENT_DIR_REL."/".$move_orgname[$cat][$z])) {
                        $post['error_messages']['page_error_delete'][] = $move_orgname[$cat][$z]." <b>></b> ".$move_newname[$cat][$z];
                        $error = true;
                    } else {
                        # Weil Verschoben
                        $error_message['updateReferences'] = updateReferencesInAllContentPages(dirname($move_orgname[$cat][$z]), basename($move_orgname[$cat][$z]), $cat, basename($move_newname[$cat][$z]));
                        if(is_array($error_message['updateReferences'])) {
                            $post['makepara'] = "yes";
                            if(isset($post['error_messages']) and is_array($post['error_messages'])) {
                                $post['error_messages'] = array_merge_recursive($post['error_messages'],$error_message);
                            } else {
                                $post['error_messages'] = $error_message;
                            }
                        }
                    }
                }
                # Fehler meldung
                if(isset($error)) {
                    $post['makepara'] = "yes"; # kein makePostCatPageReturnVariable()
#                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                    $post['display'][dirname($move_orgname[$cat][$z])]['error_html']['display_cat'] = 'style="display:block;" ';
                    unset($error);
                } else {
                # Erfogs meldungen
                    $post['messages']['page_message_copy_move'][] = $move_orgname[$cat][$z]." <b>></b> ".$move_newname[$cat][$z];
#                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                    $post['display'][dirname($move_orgname[$cat][$z])]['error_html']['display_cat'] = 'style="display:block;" ';
                    $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                }
            }
        }
    }
    # Neue Inhaltseite
    if(isset($new_page)) {
        foreach($new_page as $cat => $tmp) {
            $page_inhalt = "Das ist ein Link";#$EXT_LINK $EXT_PAGE
            if(substr($new_page[$cat],-4) == $EXT_PAGE) {
                $page_inhalt = "[ueber1|".str_replace(array("[","]"),array("^[","^]"),$specialchars->rebuildSpecialChars(substr($new_page[$cat], 3,strlen($new_page[$cat])-7), false, false))."]";
            }
            $error = saveContentToPage($page_inhalt,$CONTENT_DIR_REL."/".$cat."/".$new_page[$cat]);
            # Fehler beim anlegen also return
            if(is_array($error)) {
                if(isset($post['error_messages'])) {
                   $post['error_messages'] = array_merge_recursive($post['error_messages'], $error);
                } else {
                   $post['error_messages'] = $error;
                }
            } else {
                if(empty($post['categories'][$cat]['new_url'][$max_cat_page])) {
                    # Editieren der Neuen Inhaltseite und hier Fertig weiter gehts nach dem Speichern mit editSite()
                    $post['messages']['page_message_new'][] = $cat."/".$new_page[$cat];
                    $post['editsite'] = 'no';
                } else {
                    $post['messages']['message_link'][] = $cat."/".$new_page[$cat];
                }
            }
            unset($error);
            $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
        }
        $post['makepara'] = "yes"; # kein makePostCatPageReturnVariable()
        $post['editsite'] = 'no';
    }

    return $post;
}


function deleteSite($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;

    $icon_size = '24x24';
    $cat = key($post['action_data']['deletesite']);
    $del_page = $cat."/".$post['action_data']['deletesite'][$cat];
    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $post['ask'] = getLanguageValue("page_ask_delete").':<br><span style="font-weight:normal;">->&nbsp;&nbsp;'.$specialchars->rebuildSpecialChars($post['action_data']['deletesite'][$cat],true,true).'</span>&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="true" alt="'.getLanguageValue("yes").'" src="gfx/icons/'.$icon_size.'/accept.png" title="'.getLanguageValue("yes").'" style="vertical-align:middle;">&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="false" alt="'.getLanguageValue("no").'" src="gfx/icons/'.$icon_size.'/cancel.png" title="'.getLanguageValue("no").'" style="vertical-align:middle;"><input type="hidden" name="action_data[deletesite]['.$cat.']" value="'.$post['action_data']['deletesite'][$cat].'">';
        $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
        $post['makepara'] = "yes";
        return $post;
    }
    # Kategorie Löschen    
    if(isset($_POST['confirm'])) {
        if($_POST['confirm'] == "true") {
            @unlink("$CONTENT_DIR_REL/".$del_page);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(file_exists("$CONTENT_DIR_REL/".$del_page)) {
                $post['error_messages']['page_error_delete'][] = $del_cat;
                $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                return $post;
            } else {
                $post['messages']['page_message_delete'][] = $post['action_data']['deletesite'][$cat];
                $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                return $post;
            }
        } else {
            $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
            $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
            return $post;
        }
    }
}

function gallery($post) {
    global $ADMIN_CONF;
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    $icon_size = "24x24";
    $max_strlen = 255;

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("gallery_button").'">';

    if(isset($_POST['gallery']) and is_array($_POST['gallery'])) {
#echo "neu<br>\n";
        $post['gallery'] = $_POST['gallery'];

    }
#$post = null;
    if(isset($_FILES)) {
#echo "file<br>\n";
        $post = newGalleryImg($post);
    }
#    if(isset($_POST['gallery']['new_gallery']) and strlen($_POST['gallery']['new_gallery']) > 0) {
    if(isset($_POST['new_gallery']) and strlen($_POST['new_gallery']) > 0) {
#echo "neu<br>\n";
        $post = newGallery($post);

    }

    if(isset($post['action_data']) and !isset($post['error_messages'])) {
        $action_data_key = key($post['action_data']);
        if($action_data_key == "deletegalleryimg") {
echo "deletegalleryimg<br>\n";
$post = deleteGalleryImg($post);
        }
        if($action_data_key == "deletegallery") {
echo "deletegallery<br>\n";
$post = deleteGallery($post);
        }

$post = editGallery($post);

    }

    $pagecontent .= categoriesMessages($post);

    if(isset($post['ask'])) {
        $pagecontent .= returnMessage(true, $post['ask']);
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_gallery_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","gallery_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_gallery_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;page=30_Kategorien" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }
$tooltip_help_edit = NULL;
    $pagecontent .= '<span class="titel">'.getLanguageValue("gallery_button").'</span>';
    $pagecontent .= $tooltip_gallery_help;
    $pagecontent .= "<p>".getLanguageValue("gallery_text")."</p>";

    $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
$tooltip_file_help_overwrite = NULL;
$overwrite = NULL;
$tooltip_help_edit = NULL;
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[gallery]" value="'.getLanguageValue("gallery_button_change").'" class="input_submit">';
    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_file_help_overwrite.$overwrite.'>&nbsp;<span'.$tooltip_file_help_overwrite.$overwrite.'>'.getLanguageValue("files_button_overwrite").'</span>';
    $pagecontent .= '</td></tr>';

    $pagecontent .= '<tr><td width="100%" class="td_toggle_new">';
    # Neue GAllery
    $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
    $pagecontent .= '<tr><td width="30%" class="td_left_title"><b>'.getLanguageValue("gallery_new").'</b></td>';
    $pagecontent .= '<td width="70%" class="td_left_title">&nbsp;</td>';
    $pagecontent .= '</tr><tr>';
#<b>'.getLanguageValue("gallery_titel_scale").'</b>
    $pagecontent .= '';
    $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" class="input_text" name="new_gallery" value="" maxlength="'.$max_strlen.'"></td>';
    $pagecontent .= '<td width="70%" class="td_left_title">&nbsp;</td>';
#<input class="input_cms_zahl" size="4" name="new_max_width" value="" maxlength="4"><input class="input_cms_zahl" size="4" name="new_max_height" value="" maxlength="4">
    $pagecontent .= '</tr></table>';

    $pagecontent .= '</td></tr>';

    # Die getLanguageValue() und createTooltipWZ() erzeugen
#    $array_getLanguageValue = array("","","","","","",
#        "","","","","",
#        "","");
    $array_getLanguageValue = array("gallery_scale",
        "gallery_picsperrow","gallery_usethumbs","gallery_target","gallery_scaleimages",
        "gallery_rebuildthumbs","gallery_size","gallery_subtitle","gallery_button_cut","gallery_newname",
        "gallery_button_img_delete","gallery_button_gallery_delete");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }
 
    $array_getTooltipValue = array("help_target_blank","help_target_self","help_target","gallery_help_scale","gallery_help_input_scale",
        "gallery_help_picsperrow","gallery_help_input_picsperrow","gallery_help_use_thumbs","gallery_help_all_picture_scale",
        "gallery_help_all_thumbs_new","gallery_help_size","gallery_help_picture","gallery_help_subtitle","gallery_help_name",
        "gallery_help_del","gallery_help_target","gallery_help_conf","gallery_help_edit","gallery_help_newname");


    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }


    $dircontent = getDirs($GALLERIES_DIR_REL, true);
    $toggle_pos = 0;
    foreach ($dircontent as $pos => $currentgalerien) {
        if(!isset($post['gallery']['error_html']['display'][$currentgalerien])) {
            $post['gallery']['error_html']['display'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['display_setings'][$currentgalerien])) {
            $post['gallery']['error_html']['display_setings'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['newname'][$currentgalerien])) {
            $post['gallery']['error_html']['newname'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['maxwidth'][$currentgalerien])) {
            $post['gallery']['error_html']['maxwidth'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['maxheight'][$currentgalerien])) {
            $post['gallery']['error_html']['maxheight'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['picsperrow'][$currentgalerien])) {
            $post['gallery']['error_html']['picsperrow'][$currentgalerien] = NULL;
        }

        $conf = new Properties("$GALLERIES_DIR_REL/".$currentgalerien."/gallery.conf",true);
        $pagecontent .= '<tr><td width="100%" class="td_toggle">';
        $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';

        $pagecontent .= '<tr><td width="85%" class="td_titel"><span class="text_cat_page">'.$specialchars->rebuildSpecialChars($currentgalerien, true, true).'</span></td>';
        $pagecontent .= '<td width="15%" class="td_icons">';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$toggle_pos.'_linkBild"'.$tooltip_gallery_help_edit.'></span>';
        }
        $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletegallery]['.$currentgalerien.']" value="'.$text_gallery_button_gallery_delete.'" src="gfx/icons/'.$icon_size.'/delete.png" title="löschen"'.$tooltip_gallery_help_del.'></td></tr></table>';
        $pagecontent .= '</td></tr>';



        $pagecontent .= '<tr><td width="100%" id="toggle_'.$toggle_pos.'" align="right" class="td_togglen_padding_bottom"'.$post['gallery']['error_html']['display'][$currentgalerien].'>';

#$error_color['maxwidth'] = null;
#$error_color['maxheight'] = null;
#$error_color['picsperrow'] = null;
#$text_file_button_cut = "Entfernen";


        # gallery setup
        $pagecontent .= '<table width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data"><tr>';
        $pagecontent .= '<td width="55%" class="td_left_title_padding_bottom">';#<b>Setings</b>
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$toggle_pos.'conf_linkBild"'.$tooltip_gallery_help_conf.'></span>';
        }
        $pagecontent .= '</td>';
        $pagecontent .= '<td width="45%" align="right" valign="top" class="td_title"><b>Dateien Hochladen</b></td>';
        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="55%" align="left" valign="top">';


        $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data" id="toggle_'.$toggle_pos.'conf"'.$post['gallery']['error_html']['display_setings'][$currentgalerien].'><tr>';


        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_newname.'><b>'.$text_gallery_newname.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_text" name="gallery['.$currentgalerien.'][newname]" value="" maxlength="'.$max_strlen.'"'.$post['gallery']['error_html']['newname'][$currentgalerien].$tooltip_gallery_help_newname.'></td>';

#$post['gallery']['error_html']['new_name'][$gallery]

        $pagecontent .= '</tr><tr>';

        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_scale.'><b>'.$text_gallery_scale.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery['.$currentgalerien.'][setings][maxwidth]" value="'.$conf->get("maxwidth").'"'.$post['gallery']['error_html']['maxwidth'][$currentgalerien].$tooltip_gallery_help_input_scale.' />&nbsp;x&nbsp;<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery['.$currentgalerien.'][setings][maxheight]" value="'.$conf->get("maxheight").'"'.$post['gallery']['error_html']['maxheight'][$currentgalerien].$tooltip_gallery_help_input_scale.' />&nbsp;'.getLanguageValue("pixels").'</td>';

        $checket = NULL;
        if($conf->get("usethumbs") == "true") {
            $checket = ' checked="checked"';
        }
        $checket_self = NULL;
        $checket_blank = ' checked="checked"';
        if($conf->get("target") == "_self") {
            $checket_self = ' checked="checked"';
            $checket_blank = NULL;
        }

        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_target.'><b>'.$text_gallery_target.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_left_title_padding_bottom"><b'.$tooltip_help_target.'>Target:</b>&nbsp;&nbsp;blank&nbsp;<input type="radio" name="gallery['.$currentgalerien.'][setings][target]" value="_blank"'.$tooltip_help_target_blank.$checket_blank.'>&nbsp;oder&nbsp;self&nbsp;<input type="radio" name="gallery['.$currentgalerien.'][setings][target]" value="_self"'.$tooltip_help_target_self.$checket_self.'></td>';

        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_use_thumbs.'><b>'.$text_gallery_usethumbs.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="checkbox" name="gallery['.$currentgalerien.'][setings][usethumbs]" value="true"'.$tooltip_gallery_help_use_thumbs.$checket.'></td>';
        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_picsperrow.'><b>'.$text_gallery_picsperrow.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_cms_zahl" size="4" maxlength="2" name="gallery['.$currentgalerien.'][setings][picsperrow]" value="'.$conf->get("picsperrow").'"'.$post['gallery']['error_html']['picsperrow'][$currentgalerien].$tooltip_gallery_help_input_picsperrow.' /></td>';


        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_all_picture_scale.'><b>'.$text_gallery_scaleimages.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="checkbox" name="gallery['.$currentgalerien.'][scale_max]" value="true"'.$tooltip_gallery_help_all_picture_scale.'></td>';
        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_all_thumbs_new.'><b>'.$text_gallery_rebuildthumbs.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="checkbox" name="gallery['.$currentgalerien.'][make_thumbs]" value="true"'.$tooltip_gallery_help_all_thumbs_new.'></td>';
        $pagecontent .= '</tr>';
        $pagecontent .= '</table>';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$toggle_pos.'conf\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\'info_button\');</script>';
        }


        $pagecontent .= '</td>';
 
        $pagecontent .= '<td width="45%" align="right" valign="top" class="td_togglen_padding_bottom">';
        $pagecontent .= '<input type="file" id="uploadfileinput_'.$pos.'" name="uploadfile['.$currentgalerien.']" class="uploadfileinput">
                        <div id="files_list_'.$pos.'" class="text_cat_page"></div>
                        <script type="text/javascript">
                        <!-- Create an instance of the multiSelector class, pass it the output target and the max number of files -->
                        var multi_selector = new MultiSelector( document.getElementById( \'files_list_'.$pos.'\' ), \''.$ADMIN_CONF->get("maxnumberofuploadfiles").'\', \''.$text_gallery_button_cut.'\' );
                        <!-- Pass in the file element -->
                        multi_selector.addElement( document.getElementById( \'uploadfileinput_'.$pos.'\' ) , \''.$currentgalerien.'\' );
                        </script>';
        $pagecontent .= '</td>';
#        $pagecontent .= '</tr></table>';



 
        $pagecontent .= '</tr></table>';

/*
gallerymaxheight = 375
gallerymaxwidth = 400
gallerypicsperrow = 5
galleryusethumbs = false*/

# $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("resizeimages", $ADMIN_CONF->get("resizeimages") == "true") . getLanguageValue("admin_input_imagesmax").'<br><input type="text" class="input_cms_zahl" size="4" maxlength="4" name="maximagewidth" value="'.$ADMIN_CONF->get("maximagewidth").'"'.$error_color['maximagewidth'].' />&nbsp;x&nbsp;<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="maximageheight" value="'.$ADMIN_CONF->get("maximageheight").'"'.$error_color['maximageheight'].' />&nbsp;' . getLanguageValue("pixels") . '</td>';



        # inhalt gallery
        $pagecontent .= '<table width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
        $subtitle = new Properties("$GALLERIES_DIR_REL/".$currentgalerien."/texte.conf",true);
        $counter = 0;
        // alle Bilder der Galerie ins array
        $gallerypics = getFiles($GALLERIES_DIR_REL.'/'.$currentgalerien,"");
        foreach($gallerypics as $pos => $file) {
            # nur Bilder zulassen
            if(is_dir($file) or count(@getimagesize($GALLERIES_DIR_REL.'/'.$currentgalerien.'/'.$file)) < 2) {
                unset($gallerypics[$pos]);
            }
        }
        sort($gallerypics);

        $max_cols = 3;
        $max_cols_check = $max_cols;
#$test_zeile = 1;
#$neu = 1;
        $td_width = round(100 / $max_cols).'%';
        $pagecontent .= "<tr>";
        $new_tr = false;
        foreach ($gallerypics as $pos => $file) {
            # auspleich weil pos mit 0 anfängt
            $pos = $pos + 1;
#            $counter++;
            $lastsavedanchor = "";#$lastsavedanchor = " id=\"lastsavedimage\"";
            $size = getimagesize($GALLERIES_DIR_REL."/".$currentgalerien."/".$file);
            // Vorschaubild anzeigen, wenn vorhanden; sonst Originalbild
            if (file_exists("$GALLERIES_DIR_REL/".$currentgalerien."/$PREVIEW_DIR_NAME/".$file)) {
                $preview = $GALLERIES_DIR_REL."/".$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file;
            } else {
                $preview = $GALLERIES_DIR_REL."/".$currentgalerien."/".$file;
            }
#            if($pos == $max_cols_check + 1) {
            if($new_tr === true) {
                $pagecontent .= "<tr>";
                $new_tr = false;
            }

            $pagecontent .= '<td width="'.$td_width.'" align="left" valign="top" class="td_gallery_img">';
/*
$pagecontent .= 'neu pos = '.$neu."<br>\n";
$pagecontent .= 'zeile = '.$test_zeile."<br>\n";
$pagecontent .= "pos = $pos max_cols = $max_cols_check<br>\n";
*/

#100px


            $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_gallery_img">
                            <tr><td rowspan="5" valign="top" width="10%" style="padding-right:10px;"><a href="'.$specialchars->replaceSpecialChars($GALLERIES_DIR_REL."/".$currentgalerien."/".$file,true).'" target="_blank"'.$tooltip_gallery_help_picture.'>';
            $pagecontent .= '<img src="'.$specialchars->replaceSpecialChars($preview,true).'" alt="'.$specialchars->rebuildSpecialChars($file, true, true).'" style="height:60px;border:none;" />';

            $pagecontent .= '</a></td>
                            <td height="1%" class="td_left_title"'.$tooltip_gallery_help_size.'><b>'.$text_gallery_size.'</b></td></tr>
                            <tr><td height="1%" class="td_togglen_padding_bottom">'.$size[0].' x '.$size[1].' Pixel</td></tr>
                            <tr><td height="1%" class="td_left_title"><b>'.$text_gallery_subtitle.'</b></td></tr>
                            <tr><td height="1%"><input type="text" class="input_text" name="gallery['.$currentgalerien.'][subtitle]['.$file.']" value="'.$specialchars->rebuildSpecialChars($subtitle->get($file), true, true).'"'.$tooltip_gallery_help_subtitle.' /></td></tr>
                            <tr><td style="font-size:1px;">&nbsp;</td></tr>
                            <tr><td height="1%" colspan="2">
                            <table width="100%" cellspacing="0" border="0" cellpadding="0">
                            <tr><td width="99%">
                            <input type="text" class="input_readonly" name="gallery['.$currentgalerien.'][image][]" value="'.$specialchars->rebuildSpecialChars($file, true, true).'" maxlength="'.$max_strlen.'" readonly'.$tooltip_gallery_help_name.'></td>
                            <td width="1%" nowrap>&nbsp;&nbsp;<input type="image" class="input_img_button_last" name="action_data[deletegalleryimg]['.$currentgalerien.']['.$file.']" value="'.$text_gallery_button_img_delete.'" src="gfx/icons/'.$icon_size.'/delete.png" title="löschen"'.$tooltip_gallery_help_del.'>
                            </td></tr></table>
                            </td></tr>
                            </table>';
            $pagecontent .= "</td>";#action_data[gallery]
#action_data[deletegalleryimg]['.$currentgalerien.']['.$file.'] value="$text_file_button_delete"
            if($pos == $max_cols_check) {
#$test_zeile++;
                $pagecontent .= "</tr>";
                if(count($gallerypics) >  $max_cols_check) {
                    $max_cols_check = $pos + $max_cols;
                    $new_tr = true;
#$neu = $pos;
                }
            }
        }

        # wenn die spalten nicht $max_cols sind dann den rest noch erstellen
        if($pos != $max_cols_check) {
            for($rest = $pos + 1;$rest <= $max_cols_check;$rest++) {# '.$rest.'
                $pagecontent .= '<td valign="top" width="'.$td_width.'">&nbsp;</td>';
            }
            $pagecontent .= '</tr>';
        }

        $pagecontent .= '</table>';


        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$toggle_pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\'info_button\');</script>';
        }
        $pagecontent .= '</td></tr>';

        $toggle_pos++;
    }
    $pagecontent .= '</table>';





    return array(getLanguageValue("gallery_button"), $pagecontent);
}


function newGalleryImg($post) {
#    global $CONTENT_DIR_REL;
    global $error_color;
    global $PREVIEW_DIR_NAME;
    global $GALLERIES_DIR_REL;
    global $specialchars;

    $forceoverwrite = "";
    if (isset($_POST['overwrite'])) {
        $forceoverwrite = $_POST['overwrite'];
    }


    foreach($_FILES as $array_name => $tmp) {
        if($_FILES[$array_name]['error'] == 0) {
            $gallery = explode("_",$array_name);
/*
echo "<pre>";
print_r($gallery);
echo "</pre><br>\n";*/
#            $cat = sprintf("%02d",$gallery_tmp[1])."_".specialNrDir("$CONTENT_DIR_REL", sprintf("%02d",$gallery_tmp[1]));
#echo "file = ".$gallery[1]."<br>\n";

# $forceoverwrite muss noch eingebaut werden!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            $error = uploadFile($_FILES[$array_name], $gallery[1], $forceoverwrite, true);
            if(!empty($error)) {
                $post['error_messages'][key($error)][] = $gallery[1]."/".$error[key($error)];
#                $key = array_keys($post['categories']['cat']['position'], $gallery[1]);
#                $post['categories']['cat']['error_html']['display'][sprintf("%1d",$key[0])] = 'style="display:block;" ';
#                $post['categories']['cat']['error_html']['name'][sprintf("%1d",$key[0])] = 'style="background-color:'.$error_color[key($error)].';" ';
            } else {
                if(extension_loaded("gd")) {
                    require_once("../Image.php");
                    $gallery_setings = new Properties($GALLERIES_DIR_REL."/".$gallery[1]."/gallery.conf",true);
#                    $tn = new Thumbnail();
#                    $tn->createThumb($specialchars->replaceSpecialChars($_FILES[$array_name]['name'],false), $GALLERIES_DIR_REL."/".$gallery[1]."/", $GALLERIES_DIR_REL."/".$gallery[1]."/$PREVIEW_DIR_NAME/");

                    scaleImage($specialchars->replaceSpecialChars($_FILES[$array_name]['name'],false), $GALLERIES_DIR_REL."/".$gallery[1]."/", $GALLERIES_DIR_REL."/".$gallery[1]."/$PREVIEW_DIR_NAME/", $gallery_setings->get('maxthumbwidth'), $gallery_setings->get('maxthumbheight'));
                    // chmod, wenn so eingestellt
#                    if ($ADMIN_CONF->get("chmodnewfiles") == "true")
#                    if(strlen($ADMIN_CONF->get("chmodnewfilesatts")) > 0)
#                        chmod($GALLERIES_DIR_REL."/".$gallery[1]."/$PREVIEW_DIR_NAME/".$_FILES[$array_name]['name'], getChmod());
                }
                $post['messages']['gallery_message_new_img'][] = $_FILES[$array_name]['name']." <b>></b> ".$gallery[1];
                $post['gallery']['error_html']['display'][$gallery[1]] = ' style="display:block;"';
            }
        }
    }
    return $post;
}


function newGallery($post) {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $ADMIN_CONF;

    if(isset($_POST['new_gallery']))
        $galleryname = $specialchars->replaceSpecialChars($_POST['new_gallery'],false);

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($galleryname) and preg_match($ALLOWED_SPECIALCHARS_REGEX, $galleryname)) {
            // Galerieverzeichnis schon vorhanden? Wenn nicht: anlegen!
            if(!file_exists("$GALLERIES_DIR_REL/".$galleryname)) {
                @mkdir($GALLERIES_DIR_REL."/".$galleryname);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error = @error_get_last();
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                } elseif(!is_dir($GALLERIES_DIR_REL."/".$galleryname)) {
                    $post['error_messages']['gallery_error_new'][] = $galleryname;
                } else {
                    @mkdir($GALLERIES_DIR_REL."/".$galleryname."/".$PREVIEW_DIR_NAME);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error = @error_get_last();
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                    } elseif(!is_dir($GALLERIES_DIR_REL."/".$galleryname."/".$PREVIEW_DIR_NAME)) {
                        $post['error_messages']['gallery_error_new'][] = $galleryname;
                    } else {
                        @touch($GALLERIES_DIR_REL."/".$galleryname."/texte.conf");
                        $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                        $last_error = @error_get_last();
                        if($last_error['line'] == $line_error) {
                            $post['error_messages']['php_error'][] = $last_error['message'];
                        } elseif(!is_file($GALLERIES_DIR_REL."/".$galleryname."/texte.conf")) {
                            $post['error_messages']['gallery_error_datei_conf'][] = $galleryname."/texte.conf";
                        } else {
#                            @touch($GALLERIES_DIR_REL."/".$galleryname."/gallery.conf");
                            $handle = @fopen($GALLERIES_DIR_REL."/".$galleryname."/gallery.conf","w");
                            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                            $last_error = @error_get_last();
                            if($last_error['line'] == $line_error) {
                                $post['error_messages']['php_error'][] = $last_error['message'];
                            } elseif(!is_file($GALLERIES_DIR_REL."/".$galleryname."/gallery.conf")) {
                                $post['error_messages']['gallery_error_datei_conf'][] = $galleryname."/gallery.conf";
                            } else {
                                # das kann nur in der gallery.conf geändert werden deshalb hier rein
                                fwrite($handle,"maxthumbheight = 120\nmaxthumbwidth = 120\n");
                                fclose($handle);
                                $error = changeChmod($GALLERIES_DIR_REL."/".$galleryname);
                                if(is_array($error)) {
                                    $post['error_messages'][key($error)][] = $error[key($error)];
                                }
                                $error = changeChmod($GALLERIES_DIR_REL."/".$galleryname."/".$PREVIEW_DIR_NAME);
                                if(is_array($error)) {
                                    $post['error_messages'][key($error)][] = $error[key($error)];
                                }
                                $error = changeChmod($GALLERIES_DIR_REL."/".$galleryname."/texte.conf");
                                if(is_array($error)) {
                                    $post['error_messages'][key($error)][] = $error[key($error)];
                                }
                                $error = changeChmod($GALLERIES_DIR_REL."/".$galleryname."/gallery.conf");
                                if(is_array($error)) {
                                    $post['error_messages'][key($error)][] = $error[key($error)];
                                }
                                $post['messages']['gallery_message_new'][] = $specialchars->rebuildSpecialChars($galleryname, true, true);
                            }
                        }
                    }
                }
            } else {
                $post['error_messages']['gallery_error_exists'][] = $specialchars->rebuildSpecialChars($galleryname, true, true);
            }
        } else {
            $post['error_messages']['gallery_error_name'][] = $specialchars->rebuildSpecialChars($galleryname, true, true);
        }
    }
    return $post;
}

function editGallery($post) {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $PREVIEW_DIR_NAME;

    foreach($post['gallery'] as $gallery => $gallery_array) {
        # Neuer Gallery Name
        if(isset($post['gallery'][$gallery]['newname']) and strlen($post['gallery'][$gallery]['newname']) > 0) {
            $newname = $specialchars->replaceSpecialChars($post['gallery'][$gallery]['newname'],false);
            if(!preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname) or stristr($newname,"%5E")) {
                $post['error_messages']['check_name']['color'] = "#FFC197";
                $post['gallery']['error_html']['newname'][$gallery] = 'style="background-color:#FFC197;" ';
                $post['gallery']['error_html']['display_setings'][$gallery] = ' style="display:block;"';
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
            } else {
                @rename($GALLERIES_DIR_REL."/".$gallery,$GALLERIES_DIR_REL."/".$newname);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error = @error_get_last();
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                } elseif(!is_dir($GALLERIES_DIR_REL."/".$newname)) {
                    $post['error_messages']['gallery_error_newname'][] = $newname;
                } else {
                    $post['messages']['gallery_messages_newname'][] = $newname;
                    $post['gallery'][$newname] = $post['gallery'][$gallery];
                    unset($post['gallery'][$gallery]);
                    $gallery = $newname;
                }
            }
        }


        # Subtitel setzen
        if(isset($post['gallery'][$gallery]['subtitle'])) {
            $gallery_subtitel = new Properties($GALLERIES_DIR_REL."/".$gallery."/texte.conf",true);
            foreach($post['gallery'][$gallery]['subtitle'] as $img => $subtitel) {
                if($gallery_subtitel->get($img) != $subtitel) {
                    $gallery_subtitel->set($img, $subtitel);
                    $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                    if(isset($gallery_subtitel->properties['error'])) {
                        $post['error_messages']['gallery_error_subtitel'][] = $gallery_subtitel->properties['error'];
                        return $post;
                    } else {
                        $post['messages']['gallery_messages_subtitel'][] = $gallery.' - '.$img;
                    }
                }
            }
        }

        # Gallery Setings setzen
        $gallery_setings = new Properties($GALLERIES_DIR_REL."/".$gallery."/gallery.conf",true);
        if(!isset($post['gallery'][$gallery]['setings']['usethumbs'])) {
            $post['gallery'][$gallery]['setings']['usethumbs'] = NULL;
        }
        foreach($post['gallery'][$gallery]['setings'] as $seting => $value) {
            if($gallery_setings->get($seting) != $value) {
                if($seting == 'maxheight' and strlen($value) > 0 and !ctype_digit($value)) {
                    $post['error_messages']['check_digit']['color'] = "#FFC197";
                    $post['gallery']['error_html']['maxheight'][$gallery] = 'style="background-color:#FFC197;" ';
                } elseif($seting == 'maxwidth' and strlen($value) > 0 and !ctype_digit($value)) {
                    $post['error_messages']['check_digit']['color'] = "#FFC197";
                    $post['gallery']['error_html']['maxwidth'][$gallery] = 'style="background-color:#FFC197;" ';
                } elseif($seting == 'picsperrow' and !ctype_digit($value)) {
                    $post['error_messages']['check_digit']['color'] = "#FFC197";
                    $post['gallery']['error_html']['picsperrow'][$gallery] = 'style="background-color:#FFC197;" ';
                } else {
                    $gallery_setings->set($seting, $value);
                }
                $post['gallery']['error_html']['display_setings'][$gallery] = ' style="display:block;"';
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                if(isset($gallery_setings->properties['error'])) {
                    $post['error_messages']['gallery_error_setings'][] = $gallery_setings->properties['error'];
                    return $post;
                }
                $post['messages']['gallery_messages_setings'][] = NULL;
            }
        }

        #make_thumbs
        if(isset($post['gallery'][$gallery]['make_thumbs'])) {
            if($gallery_setings->get("usethumbs") == "true") {
                require_once("../Image.php");
                $gallerypics = getFiles($GALLERIES_DIR_REL.'/'.$gallery,"");
                foreach($gallerypics as $pos => $file) {
                    # nur Bilder zulassen
                    if(!is_dir($file) and count(@getimagesize($GALLERIES_DIR_REL.'/'.$gallery.'/'.$file)) > 2) {
                        scaleImage($file, $GALLERIES_DIR_REL.'/'.$gallery.'/', $GALLERIES_DIR_REL.'/'.$gallery.'/'.$PREVIEW_DIR_NAME.'/', $gallery_setings->get('maxthumbwidth'), $gallery_setings->get('maxthumbheight'));
                        $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                        $post['messages']['gallery_messages_make_thumbs'][] = NULL;
                    }
                }
            } else {
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                $post['gallery']['error_html']['display_setings'][$gallery] = ' style="display:block;"';
                $post['error_messages']['gallery_error_no_make_thumbs'][] = NULL;
            }
        }
        #scale_max
        if(isset($post['gallery'][$gallery]['scale_max'])) {
            if(isset($post['gallery']['error_html']['maxwidth'][$gallery]) or isset($post['gallery']['error_html']['maxheight'][$gallery])) {
                $post['error_messages']['gallery_error_no_scale_max'][] = NULL;
                $post['gallery']['error_html']['display_setings'][$gallery] = ' style="display:block;"';
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
            } else {
                require_once("../Image.php");
                $gallerypics = getFiles($GALLERIES_DIR_REL.'/'.$gallery,"");
                foreach($gallerypics as $pos => $file) {
                    $test_img = @getimagesize($GALLERIES_DIR_REL.'/'.$gallery.'/'.$file);
                    # nur Bilder zulassen
                    if(!is_dir($file) and count($test_img) > 2) {
                        if($test_img[0] > $gallery_setings->get('maxwidth') or $test_img[1] > $gallery_setings->get('maxheight')) {
                            scaleImage($file, $GALLERIES_DIR_REL.'/'.$gallery.'/', $GALLERIES_DIR_REL.'/'.$gallery.'/', $gallery_setings->get('maxwidth'), $gallery_setings->get('maxheight'));
                            $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                            $post['messages']['gallery_messages_scale_max'][] = NULL;
                        }
                    }
                }
            }
        }
    }
    return $post;
}


function deleteGalleryImg($post) {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    $icon_size = "24x24";

    $gallery = key($post['action_data']['deletegalleryimg']);
    $del_file = $post['action_data']['deletegalleryimg'][$gallery];

    if (isset($_POST['confirm']) and ($_POST['confirm'] == "true")) {
        if(file_exists($GALLERIES_DIR_REL."/".$gallery."/".$del_file)) {
            @unlink($GALLERIES_DIR_REL."/".$gallery."/".$del_file);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(is_file("$GALLERIES_DIR_REL/".$gallery."/".$del_file)) {
                $post['error_messages']['gallery_error_deleted_img'][] = $gallery." <b>></b> ".$del_file;
            } else {
#                $post['messages']['gallery_message_deleted'][] = $gallery." <b>></b> ".$del_file;
                if(file_exists($GALLERIES_DIR_REL."/".$gallery."/".$PREVIEW_DIR_NAME."/".$del_file)) {
                    @unlink($GALLERIES_DIR_REL."/".$gallery."/".$PREVIEW_DIR_NAME."/".$del_file);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error = @error_get_last();
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                    } elseif(is_file("$GALLERIES_DIR_REL/".$gallery."/".$del_file)) {
                        $post['error_messages']['gallery_error_deleted_img'][] = $gallery."/".$PREVIEW_DIR_NAME."/ <b>></b> ".$del_file;
                    } else {
#                       $post['messages']['gallery_message_deleted'][] = $gallery."/vorschau/ <b>></b> ".$del_file;
                    }
                }
                $subtitle = new Properties("$GALLERIES_DIR_REL/".$gallery."/texte.conf",true);
                $subtitle->delete($del_file);
                $post['messages']['gallery_message_deleted_img'][] = $gallery." <b>></b> ".$del_file;
            }
            $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
#            return $post;
        }
    } else {
        $post['ask'] = getLanguageValue("gallery_ask_delete_img").':<br><span style="font-weight:normal;">->&nbsp;&nbsp;'.$specialchars->rebuildSpecialChars($del_file,true,true).'</span>&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="true" alt="'.getLanguageValue("yes").'" src="gfx/icons/'.$icon_size.'/accept.png" title="'.getLanguageValue("yes").'" style="vertical-align:middle;">&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="false" alt="'.getLanguageValue("no").'" src="gfx/icons/'.$icon_size.'/cancel.png" title="'.getLanguageValue("no").'" style="vertical-align:middle;"><input type="hidden" name="action_data[deletegalleryimg]['.$gallery.']" value="'.$del_file.'">';
        $post['gallery']['error_html']['display'][$gallery] = 'style="display:block;" ';
#        return $post;
    }
    return $post;
}

function deleteGallery($post) {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;

    $icon_size = '24x24';
    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $del_gallery = key($post['action_data']['deletegallery']);
        $post['ask'] = getLanguageValue("gallery_ask_delete").'<br><span style="font-weight:normal;">-&gt;&nbsp;&nbsp;'.messagesOutLen($specialchars->rebuildSpecialChars($del_gallery, true, true)).'</span>&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="true" alt="'.getLanguageValue("yes").'" src="gfx/icons/'.$icon_size.'/accept.png" title="'.getLanguageValue("yes").'" style="vertical-align:middle;">&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="false" alt="'.getLanguageValue("no").'" src="gfx/icons/'.$icon_size.'/cancel.png" title="'.getLanguageValue("no").'" style="vertical-align:middle;"><input type="hidden" name="action_data[deletegallery]" value="'.$post['action_data']['deletegallery'].'"><input type="hidden" name="del_gallery" value="'.$del_gallery.'">';
#        return $post;
    }
    # Gallery Löschen    
    if(isset($_POST['confirm']) and $_POST['confirm'] == "true" and isset($_POST['del_gallery']) and !empty($_POST['del_gallery'])) {
        $del_gallery = $GALLERIES_DIR_REL."/".$_POST['del_gallery'];
        $post['error_messages'] = deleteDir($del_gallery);
        if(!file_exists($GALLERIES_DIR_REL."/".$_POST['del_gallery'])) {
            $post['messages']['gallery_message_deleted'][] = $_POST['del_gallery'];
#            return $post;
        } else {
            if(!isset($post['error_messages'])) {
                $post['error_messages']['gallery_error_deleted'][] = $_POST['del_gallery'];
            }
#            return $post;
        }
    }
    return $post;
}


function files($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_LINK;
    global $DOWNLOAD_COUNTS;
    global $ADMIN_CONF;
    $max_cat_page = 100;

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("files_button").'">';
    $post['categories'] = makePostCatPageReturnVariable($CONTENT_DIR_REL);

    if(isset($post['action_data']) and !isset($post['error_messages'])) {
        $action_data_key = key($post['action_data']);
        if($action_data_key == "newfile") {
            $post = newFile($post);
        } elseif($action_data_key == "deletefile") {
            $post = deleteFile($post);
        }
    }
    $icon_size = "24x24";
    if(isset($post['ask'])) {
        $pagecontent .= returnMessage(true, $post['ask']);
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_files_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","files_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_files_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;&page=50_Dateien" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }
    # Prüfen ob der Ordner dateien existiert wenn nicht anlegen
    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        if($pos == $max_cat_page or isset($post['categories']['cat']['url'][$pos])) {
            continue;
        }
        $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if(!file_exists($CONTENT_DIR_REL."/".$file."/dateien")) {
            $post['error_messages']['files_error_dateien'][] = $CONTENT_DIR_REL."/".$file."/dateien";
            @mkdir ($CONTENT_DIR_REL."/".$file."/dateien");
            $line_error = __LINE__ - 1;
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(!is_dir($CONTENT_DIR_REL."/".$file."/dateien")) {
                $post['error_messages']['files_error_mkdir_dateien'][] = $CONTENT_DIR_REL."/".$file."/dateien";
            } else {
                useChmod($CONTENT_DIR_REL."/".$file."/dateien");
            }
        }
    }
    $pagecontent .= categoriesMessages($post);

    $pagecontent .= '<span class="titel">'.getLanguageValue("files_button").'</span>';
    $pagecontent .= $tooltip_files_help;
    $pagecontent .= "<p>".getLanguageValue("files_text")."</p>";

# im admin prüfen wenn conf nicht von hand ????????????????????????????????????????????
    $maxnumberoffiles = $ADMIN_CONF->get("maxnumberofuploadfiles");
    if (!is_numeric($maxnumberoffiles) || ($maxnumberoffiles < 1)) {
        $maxnumberoffiles = 5;
    }

    $array_getLanguageValue = array("files","category","contents","file","files_downloads","files_uploaddate","files_size","files_text_upload","files_button_cut","files_button_delete","files_text_no_files");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }

    $array_getTooltipValue = array("files_help_edit","files_help_delete","files_help_show","files_help_upload",
                            "files_help_downloads","files_help_overwrite");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[newfile]" value="Dateien Hochladen" class="input_submit">';
    $overwrite = NULL;
    if($ADMIN_CONF->get("overwriteuploadfiles") == "true") {
            $overwrite = ' checked="checked"';
    }
    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_files_help_overwrite.$overwrite.'>&nbsp;<span'.$tooltip_files_help_overwrite.$overwrite.'>'.getLanguageValue("files_button_overwrite").'</span>';
    $pagecontent .= '</td></tr>';

    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        unset($display_titel_dateien);
        if($pos == $max_cat_page or isset($post['categories']['cat']['url'][$pos])) {
            continue;
        }
        $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        // Anzahl Dateien auslesen
        $filecount = 0;
        if(file_exists($CONTENT_DIR_REL."/".$file."/dateien")) {
            if($fileshandle = opendir($CONTENT_DIR_REL."/".$file."/dateien")) {
                 while (($filesdir = readdir($fileshandle))) {
                    if(isValidDirOrFile($filesdir))
                        $filecount++;
                }
                closedir($fileshandle);
            }
        }
        $text = '('.$filecount.' '.$text_files.')';

        $pagecontent .= '<tr><td class="td_toggle">';

        $pagecontent .= '<table width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
        if(!isset($display_titel)) {# Position:          
            $pagecontent .= '<tr><td width="30%" class="td_left_title"><b>'.$text_category.'</b></td><td>&nbsp;</td><td width="30%" class="td_left_title"><b>'.$text_contents.'</b></td><td width="15%" class="td_icons">&nbsp;</td></tr>';
            $display_titel = true;
        }
        $pagecontent .= '<tr><td width="30%" class="td_left_title"><span '.$post['categories']['cat']['error_html']['name'][$pos].'class="text_cat_page">'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'</span><input type="hidden" name="categories[cat][position]['.$pos.']" value="'.$post['categories']['cat']['position'][$pos].'"><input type="hidden" name="categories[cat][name]['.$pos.']" value="'.$post['categories']['cat']['name'][$pos].'"></td>';

        $pagecontent .= '<td>&nbsp;</td>';
        $pagecontent .= '<td width="30%" class="td_left_title"><span class="text_info">'.$text.'</span></td><td width="15%" class="td_icons">';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$pos.'_linkBild"'.$tooltip_files_help_edit.'></span>';
        }

        $pagecontent .= '</td></tr></table></td></tr>';
        $pagecontent .= '<tr>';
        $pagecontent .= '<td '.$post['categories']['cat']['error_html']['display'][$pos].'id="toggle_'.$pos.'" align="right" class="td_togglen">';

        $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if (isValidDirOrFile($file) && ($subhandle = @opendir("$CONTENT_DIR_REL/".$file."/dateien"))) {
            $hasdata = false;
            $pagecontent .= '<table width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
            $pagecontent .= '<tr><td class="td_left_title_padding_bottom" colspan="1">'.$text_files_text_upload.'</td><td colspan="4" class="td_right_title_padding_bottom"'.$tooltip_files_help_upload.'><input type="file" id="uploadfileinput_'.$pos.'" name="uploadfile" class="uploadfileinput"></td></tr><tr><td colspan="5" class="td_right_title_padding_bottom"><div id="files_list_'.$pos.'" class="text_cat_page"></div>';
            $pagecontent .= '<script type="text/javascript">
            <!-- Create an instance of the multiSelector class, pass it the output target and the max number of files -->
            var multi_selector = new MultiSelector( document.getElementById( \'files_list_'.$pos.'\' ), \''.$maxnumberoffiles.'\', \''.$text_files_button_cut.'\' );
            <!-- Pass in the file element -->
            multi_selector.addElement( document.getElementById( \'uploadfileinput_'.$pos.'\' ) , \''.$post['categories']['cat']['position'][$pos].'\' );
            </script>
            </td></tr>';

        $mysubfiles = array();
        while (($subfile = readdir($subhandle))) {
            array_push($mysubfiles, $subfile);
        }
        closedir($subhandle);
        sort($mysubfiles);
        foreach ($mysubfiles as $subfile) {
            if (isValidDirOrFile($subfile)) {
                $downloads = $DOWNLOAD_COUNTS->get($file.":".$subfile);
#                $countword = getLanguageValue("data_downloads"); // Plural
#                if ($downloads == "1")
#                    $countword = getLanguageValue("data_download"); // Singular
                if ($downloads == "")
                    $downloads = "0";
                // Downloads pro Tag berechnen
                $uploadtime = filemtime("$CONTENT_DIR_REL/$file/dateien/$subfile");
                $counterstart = $DOWNLOAD_COUNTS->get("_downloadcounterstarttime");
                // Berechnungsgrundlage fuer "Downloads pro Tag":
                // Entweder Upload-Zeitpunkt oder Beginn der Statistik - genommen wird der spÃ¤tere Zeitpunkt
                if ($uploadtime > $counterstart)
                    $starttime = $uploadtime;
                else
                    $starttime = $counterstart;
                $dayscounted = ceil((time() - $starttime) / (60*60*24));
#                if ($dayscounted == 0)
#                    $downloadsperday = 0;
#                else
#                    $downloadsperday = round(($downloads/$dayscounted), 2);
#                if ($downloads > 0)
#                    $downloadsperdaytext = "(".$downloadsperday." ".getLanguageValue("data_downloadsperday").")";
#                else
                    $downloadsperdaytext = "";
                // Dateigröße
                $filesize = filesize("$CONTENT_DIR_REL/$file/dateien/$subfile");

        $titel_dateien = NULL;
        if(!isset($display_titel_dateien)) {# Position:          
            $titel_dateien = '<tr><td class="td_left_title"><b>'.$text_file.'</b></td><td width="10%" class="td_left_title" nowrap><b>'.$text_files_size.'</b></td><td width="20%" class="td_left_title" nowrap><b>'.$text_files_uploaddate.'</b></td><td width="10%" class="td_center_title" nowrap><b'.$tooltip_files_help_downloads.'>'.$text_files_downloads.'</b></td><td width="5%" class="td_left_title" nowrap>&nbsp;</td></tr>';
            $display_titel_dateien = true;
        }


                $pagecontent .= $titel_dateien.'<tr><td class="td_left_title_padding_bottom"><a class="file_link" href="'.$CONTENT_DIR_REL.'/'.$specialchars->replaceSpecialChars($file,true).'/dateien/'.$specialchars->replaceSpecialChars($subfile,true).'" target="_blank"'.$tooltip_files_help_show.'>'.$specialchars->rebuildSpecialChars($subfile,true,true).'</a></td>'
                .'<td class="td_left_title_padding_bottom" nowrap><span class="text_info">'.convertFileSizeUnit($filesize).'</span></td>'
                .'<td class="td_left_title_padding_bottom" nowrap><span class="text_info">'.strftime(getLanguageValue("_dateformat"), $uploadtime).'</span></td>'
                .'<td class="td_center_title_padding_bottom" nowrap><span class="text_info">'.$downloads." ".$downloadsperdaytext.'</span></td>';
                $pagecontent .= '<td class="td_left_title_padding_bottom" nowrap>';
                $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletefile]['.$post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos].']['.$subfile.']" value="'.$text_files_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$text_files_button_delete.'"'.$tooltip_files_help_delete.'>';
                $pagecontent .= '</td></tr>';

                $hasdata = true;
            }
        }
            if (!$hasdata)
            $pagecontent .= '<tr><td class="td_left_title_padding_bottom" colspan="5"><span class="text_info">'.$text_files_text_no_files.'</span></td></tr>';
            $pagecontent .= "</table>";
        }

        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.getLanguageValue("button_edit").'\');</script>';
        }
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[newfile]" value="Dateien Hochladen" class="input_submit">';
    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_files_help_overwrite.$overwrite.'>&nbsp;<span'.$tooltip_files_help_overwrite.$overwrite.'>'.getLanguageValue("files_button_overwrite").'</span>';
    $pagecontent .= '</td></tr></table>';

    return array(getLanguageValue("files_button"), $pagecontent);
}

function newFile($post) {
    global $CONTENT_DIR_REL;
    global $error_color;
    $forceoverwrite = "";
    if (isset($_POST['overwrite'])) {
        $forceoverwrite = $_POST['overwrite'];
    }


    foreach($_FILES as $array_name => $tmp) {
        if($_FILES[$array_name]['error'] == 0) {
            $cat_pos= explode("_",$array_name);
            $cat = sprintf("%02d",$cat_pos[1])."_".specialNrDir("$CONTENT_DIR_REL", sprintf("%02d",$cat_pos[1]));
            $error = uploadFile($_FILES[$array_name], $cat, $forceoverwrite);
            if(!empty($error)) {
                $post['error_messages'][key($error)][] = $cat."/".$error[key($error)];
                $key = array_keys($post['categories']['cat']['position'], substr($cat,0,2));
                $post['categories']['cat']['error_html']['display'][sprintf("%1d",$key[0])] = 'style="display:block;" ';
                $post['categories']['cat']['error_html']['name'][sprintf("%1d",$key[0])] = 'style="background-color:'.$error_color[key($error)].';" ';
            } else {
                $post['messages']['files_message_new'][] = $_FILES[$array_name]['name']." <b>></b> ".$cat;
            }
        }
    }
    return $post;
}

function deleteFile($post) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $DOWNLOAD_COUNTS;

    $icon_size = "24x24";
    $cat = key($post['action_data']['deletefile']);
    $del_file = $post['action_data']['deletefile'][$cat];

        if (isset($_POST['confirm']) and ($_POST['confirm'] == "true")) {
        if(file_exists("$CONTENT_DIR_REL/".$cat."/dateien/".$del_file)) {
            @unlink("$CONTENT_DIR_REL/".$cat."/dateien/".$del_file);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(is_file("$CONTENT_DIR_REL/".$cat."/dateien/".$del_file)) {
                $post['error_messages']['files_error_delete'][] = $cat." <b>></b> ".$del_file;
            } else {
                $post['messages']['files_message_deleted'][] = $cat." <b>></b> ".$del_file;
            }
            $key = array_keys($post['categories']['cat']['position'], substr($cat,0,2));
            $post['categories']['cat']['error_html']['display'][$key[0]] = 'style="display:block;" ';
            return $post;
        }
    } else {
        $post['ask'] = getLanguageValue("files_ask_delete").':<br><span style="font-weight:normal;">->&nbsp;&nbsp;'.$specialchars->rebuildSpecialChars($del_file,true,true).'</span>&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="true" alt="'.getLanguageValue("yes").'" src="gfx/icons/'.$icon_size.'/accept.png" title="'.getLanguageValue("yes").'" style="vertical-align:middle;">&nbsp;&nbsp;&nbsp;<input type="image" name="confirm" value="false" alt="'.getLanguageValue("no").'" src="gfx/icons/'.$icon_size.'/cancel.png" title="'.getLanguageValue("no").'" style="vertical-align:middle;"><input type="hidden" name="action_data[deletefile]['.$cat.']" value="'.$del_file.'">';

        $key = array_keys($post['categories']['cat']['position'], substr($cat,0,2));
        $post['categories']['cat']['error_html']['display'][$key[0]] = 'style="display:block;" ';
        return $post;
    }
    return $post;
}

function config($post) {
    global $CMS_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $USER_SYNTAX_FILE;
    global $CONTACT_CONF;
    global $ADMIN_CONF;

    $icon_size = "24x24";

    $main = makeDefaultConf("main");

    # error colors für die input felder vorbereiten vom main array,usersyntax und input_mail array
    foreach($main as $type => $type_array) {
        if($type == 'expert') continue;
        foreach($main[$type] as $syntax_name => $dumy) {
            $error_color[$syntax_name] = NULL;
        }
    }
    $error_color['usersyntax'] = NULL;
    $input_mail = makeDefaultConf("formular");
    $error_color['formularmail'] = NULL;
    $error_color['contactformwaittime'] = NULL;
    foreach($input_mail as $syntax_name => $dumy) {
        if($syntax_name == 'formularmail') continue;
        $error_color['titel_'.$syntax_name] = NULL;
    }

    $language_array = getFiles('../sprachen',true);
    $cat_array = getDirs($CONTENT_DIR_REL,true,true);
    $layout_array = getDirs("../layouts",true);

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("config_button").'">';


    if(isset($post['apply'])) {
        $post['apply'] = $specialchars->rebuildSpecialChars(getRequestParam('apply', true), false, true);
    }


    // Ã„nderungen speichern
    if(isset($post['apply']) and $post['apply'] == getLanguageValue("config_submit")) {

        # nur wenns auch Layouts gibt
        if(count($layout_array) > 0) {
            # wens ein neues layout ist setings aus der layoutsettings hollen und schreiben
            if($CMS_CONF->get('cmslayout') != $specialchars->replaceSpecialChars($post['cmslayout'],false)) {
                $error = setLayoutAndDependentSettings($specialchars->replaceSpecialChars($post['cmslayout'],false));
                if(is_array($error)) {
                    $post['error_messages']['properties'][] = $error['error'];
                    $error_color['cmslayout'] = ' style="background-color:#FF7029;"';
                    # damits nicht gespeichert wird
                    unset($post['cmslayout'],$post['usesubmenu']);
                } else {
                    $post['usesubmenu'] = $error;
                }
            }
        }

        # usecmssyntax wurde eingeschaltet also posts fühlen
        if(isset($post['usecmssyntax']) and $post['usecmssyntax'] == "true" and $CMS_CONF->get('usecmssyntax') == "false") {
            $post['replaceemoticons'] = $CMS_CONF->get('replaceemoticons');
            $post['shortenlinks'] = $CMS_CONF->get('shortenlinks');
        }
        # usecmssyntax ist ausgeschaltet also posts fühlen
        if($CMS_CONF->get('usecmssyntax') == "false") {
            $post['replaceemoticons'] = $CMS_CONF->get('replaceemoticons');
        }

        foreach($main as $type => $type_array) {
            if($type == 'expert') continue;
            foreach($main[$type] as $syntax_name => $dumy) {
                $error_messages = false;
                if($ADMIN_CONF->get('showexpert') == "false" and in_array($syntax_name,$main['expert'])) {
                    continue;
                }
                if($type == 'text') {
                    if(isset($post[$syntax_name])) {
                        $text = $specialchars->replaceSpecialChars($post[$syntax_name],false);
                    } else continue;
                    if($error_messages === false and $CMS_CONF->get($syntax_name) != $text) {
                        $CMS_CONF->set($syntax_name, $text);
                    }
                }
                if($type == 'select') {
                    if(isset($post[$syntax_name])) { # wenn select lehr ist post lehr ja
                        $select = $specialchars->replaceSpecialChars($post[$syntax_name],false);
                    } else continue;
                    if($error_messages === false and $CMS_CONF->get($syntax_name) != $select) {
                        $CMS_CONF->set($syntax_name, $select);
                    }
                }
                if($type == 'checkbox') {
                    $checkbox = "false";
                    if(isset($post[$syntax_name])) {
                        $checkbox = $post[$syntax_name];
                    }
                    if($error_messages === false and $CMS_CONF->get($syntax_name) != $checkbox) {
                        $CMS_CONF->set($syntax_name, $checkbox);
                    }
                }
            }
        }
        # Mail daten speichern
        foreach($input_mail as $syntax_name => $dumy) {
            if($syntax_name == "contactformwaittime") {
                # wenn eingabe keine Zahl oder mehr wie 4stelig ist
                if(!ctype_digit($post[$syntax_name]) or strlen($post[$syntax_name]) > 4) {
                    $post['error_messages']['config_error_nodigit_tolong']['color'] = "#FF7029";
                    $error_color['contactformwaittime'] = ' style="background-color:#FF7029;"';
                } else {
                    $CONTACT_CONF->set($syntax_name, $post[$syntax_name]);
                }
                continue;
            }
            if($syntax_name == "contactformusespamprotection") {
                $checkbox = "false";
                if(isset($post[$syntax_name])) {
                    $checkbox = $post[$syntax_name];
                }
                if($CONTACT_CONF->get($syntax_name) != $checkbox) {
                    $CONTACT_CONF->set($syntax_name, $checkbox);
                }
                continue;
            }
            if($syntax_name == "formularmail" and $post[$syntax_name] != "") {
                 if(!preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/",$post[$syntax_name])) {
                    $post['error_messages']['config_error_formularmail']['color'] = "#FF7029";
                    $error_color['formularmail'] = ' style="background-color:#FF7029;"';
                    $error_messages = $syntax_name;
                } else {
                    $CONTACT_CONF->set($syntax_name,$post[$syntax_name]);
                }
                continue;
            }
            $mail_titel = $specialchars->replaceSpecialChars($post['titel_'.$syntax_name],false);
            $CONTACT_CONF->set($syntax_name, $mail_titel.",".checkBoxChecked('show_'.$syntax_name).",".checkBoxChecked('mandatory_'.$syntax_name));
        }

        // Speichern der benutzerdefinierten Syntaxelemente -> ERWEITERN UM PRÃœFUNG!
        # nur Speichern wenn auch benutzt wird
        if($CMS_CONF->get('usecmssyntax') == "true" and $ADMIN_CONF->get('showexpert') == "true") {
            # usecmssyntax wurde eingeschaltet aus syntax.conf lesen
            if(!isset($post['usersyntax'])) {
                $usersyntax_array = file($USER_SYNTAX_FILE);
            } else {
                $usersyntax_array = preg_split("/\r\n|\r|\n/", $post['usersyntax']);
            }
            $usersyntax_text = NULL;
            foreach($usersyntax_array as $zeile) {
                $zeile = trim($zeile);
                if(!empty($zeile)) {
                    $usersyntax_text .= $zeile."\n";
                    # in jeder zeile muss " =" enthalten sein sonst Fehler
                    if(!strstr($zeile," =") !== false) {
                        $post['error_messages']['config_error_usersyntax']['color'] = "#FF7029";
                        $post['error_messages']['config_error_usersyntax'][] = $zeile;
                        $error_color['usersyntax'] = ' style="background-color:#FF7029;"';
                    } else {
                        $count = 0;
                        $search_tmp = substr($zeile,0,strpos($zeile," =") + strlen(" = "));
                        # Dopelte einträge suchen
                        foreach($usersyntax_array as $zeile) {
                            if(strstr($zeile," =") !== false and strpos($zeile,$search_tmp) !== false) {
                                $count++;
                            }
                            if($count > 1) {
                                $post['error_messages']['config_error_usersyntax_doubles']['color'] = "#FF7029";
                                $post['error_messages']['config_error_usersyntax_doubles'][$search_tmp] = $search_tmp;
                                $error_color['usersyntax'] = ' style="background-color:#FF7029;"';
                            }
                        }
                    }
                }
            }
            if($handle = @fopen($USER_SYNTAX_FILE, "a+")) {
                $test = fread($handle,filesize($USER_SYNTAX_FILE));
                fclose($handle);
                # sonst kann es pasieren das filesize im cache gespeichert wird
                clearstatcache();
                # nur Speichern wenn sich was geändert hat
                if($test != $usersyntax_text) {
                    $handle = @fopen($USER_SYNTAX_FILE, "w");
                    fputs($handle, $usersyntax_text);
                    fclose($handle);
                }
            } else {
                $post['error_messages']['config_error_usersyntax_write'][] = NULL;
            }
        }
        $post['messages']['config_messages_changes'][] = NULL;
    } # setings end
    # Anzeige begin

    if(count($language_array) <= 0) {
        $post['error_messages']['config_error_language_emty'][] = NULL;
        $error_color['cmslanguage'] = ' style="background-color:#FF7029;"';
    } elseif(!in_array($CMS_CONF->get('cmslanguage').".conf",$language_array)) {
        $post['error_messages']['config_error_language_existed'][] = $CMS_CONF->get('cmslanguage');
        $error_color['cmslanguage'] = ' style="background-color:#FF7029;"';
    }

    if(count($cat_array) <= 0) {
        $post['error_messages']['config_error_defaultcat_emty'][] = NULL;
        $error_color['defaultcat'] = ' style="background-color:#FF7029;"';
    } elseif(!in_array($CMS_CONF->get('defaultcat'),$cat_array)) {
        $post['error_messages']['config_error_defaultcat_existed'][] = $CMS_CONF->get('defaultcat');
        $error_color['defaultcat'] = ' style="background-color:#FF7029;"';
    }

    if(count($layout_array) <= 0) {
        $post['error_messages']['config_error_layouts_emty'][] = NULL;
        $error_color['cmslayout'] = ' style="background-color:#FF7029;"';
    } elseif(!in_array($CMS_CONF->get('cmslayout'),$layout_array)) {
        $post['error_messages']['config_error_layouts_existed'][] = $CMS_CONF->get('cmslayout');
        $error_color['cmslayout'] = ' style="background-color:#FF7029;"';
    }
    $pagecontent .= categoriesMessages($post);
/* tooltips noch einbauen!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $array_getTooltipValue = array("admin_help_language","admin_help_adminmail","admin_help_chmodnewfiles",
        "admin_help_chmodupdate");

    # Variable erzeugen z.B. info_many_pages = $text_info_many_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }*/

    if(isset($post['javascript']) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_config_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","config_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_config_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;page=70_Konfiguration" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }

    $pagecontent .= '<span class="titel">'.getLanguageValue("config_button").'</span>';
    $pagecontent .= $tooltip_config_help;
    $pagecontent .= "<p>".getLanguageValue("config_text")."</p>";
    // ALLGEMEINE EINSTELLUNGEN
    $pagecontent .= "<table width=\"100%\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" class=\"table_data\">";
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"td_cms_titel\" colspan=\"2\">".getLanguageValue("config_titel_cmsglobal");
    $pagecontent .= "</td></tr>";
    // Zeile "WEBSITE-TITEL"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_websitetitle")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><input type=\"text\" class=\"input_cms_text\" name=\"websitetitle\" value=\"".$specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),true,true)."\"".$error_color['websitetitle']." /></td>";
    $pagecontent .= "</tr>";
    // Zeile "WEBSITE-TITELLEISTE"
    $titlebarsep = $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarseparator"),true,true);
    $txt_websitetitle = getLanguageValue("config_input_websitetitle");
    $txt_category = getLanguageValue("category_button");
    $txt_page = getLanguageValue("page_button");
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_websitetitlebar")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><select name=\"titlebarformat\" class=\"input_cms_select\">";
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
    foreach ($titlebarformats as $titlebarformat) {
        $selected = NULL;
        if ($titlebarformat == $specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarformat"),true,true)) {
            $selected = "selected ";
        }
        $text = preg_replace('/{WEBSITE}/', $txt_websitetitle, $titlebarformat);
        $text = preg_replace('/{CATEGORY}/', $txt_category, $text);
        $text = preg_replace('/{PAGE}/', $txt_page, $text);
        $text = preg_replace('/{SEP}/', $titlebarsep, $text);
        $pagecontent .= "<option ".$selected."value=\"".$titlebarformat."\">".$text."</option>";
    }
    $pagecontent .= "</select></td>";
    $pagecontent .= "</tr>";
    // Zeile "TITEL-TRENNER"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_websitetitleseparator")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><input type=\"text\" class=\"input_cms_text\" name=\"titlebarseparator\" value=\"".$specialchars->rebuildSpecialChars($CMS_CONF->get("titlebarseparator"),true,true)."\"".$error_color['titlebarseparator']." /></td>";
    $pagecontent .= "</tr>";
    // Zeile "WEBSITE-BESCHREIBUNG"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_websitedescription")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><input type=\"text\" class=\"input_cms_text\" name=\"websitedescription\" value=\"".$specialchars->rebuildSpecialChars($CMS_CONF->get("websitedescription"),true,true)."\"".$error_color['websitedescription']." /></td>";
    $pagecontent .= "</tr>";
    // Zeile "WEBSITE-KEYWORDS"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_websitekeywords")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><input type=\"text\" class=\"input_cms_text\" name=\"websitekeywords\" value=\"".$specialchars->rebuildSpecialChars($CMS_CONF->get("websitekeywords"),true,true)."\"".$error_color['websitekeywords']." /></td>";
    $pagecontent .= "</tr>";
    // Zeile "SPRACHAUSWAHL"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_cmslanguage")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><select name=\"cmslanguage\" class=\"input_cms_select\"".$error_color['cmslanguage'].">";
    foreach($language_array as $file) {
        $selected = NULL;
        if(substr($file,0,strlen($file)-strlen(".conf")) == $CMS_CONF->get("cmslanguage")) {
            $selected = " selected";
        }
        $pagecontent .= "<option".$selected." value=\"".substr($file,0,strlen($file)-strlen(".conf"))."\">";
        // Ãœbersetzer aus der aktuellen Sprachdatei holen
        $languagefile = new Properties("../sprachen/$file",true);
        $pagecontent .= substr($file,0,strlen($file)-strlen(".conf"))." (".getLanguageValue("config_input_translator")." ".$languagefile->get("_translator_0").")";
        $pagecontent .= "</option>";
    }
    $pagecontent .= "</select></td></tr>";
    // Zeile "LAYOUTAUSWAHL"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_cmslayout")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\"><select name=\"cmslayout\" class=\"input_cms_select\"".$error_color['cmslayout'].">";
    natcasesort($layout_array);
    foreach ($layout_array as $file) {
        $selected = NULL;
        if ($file == $CMS_CONF->get("cmslayout")) {
            $selected = " selected";
        }
        $pagecontent .= "<option".$selected." value=\"".$file."\">";
        // Ãœbersetzer aus der aktuellen Sprachdatei holen
        $pagecontent .= $specialchars->rebuildSpecialChars($file, true, true);
        $pagecontent .= "</option>";
    }
    $pagecontent .= "</select></td></tr>";
    // Zeile "STANDARD-KATEGORIE"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_defaultcat")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\">";
    $pagecontent .= "<select name=\"defaultcat\" class=\"input_cms_select\"".$error_color['defaultcat'].">";
    foreach($cat_array as $element) {
        if (count(getFiles("$CONTENT_DIR_REL/".$element, "")) == 0) {
            continue;
        }
        $selected = NULL;
        if ($element == $CMS_CONF->get("defaultcat")) {
            $selected = "selected ";
        }
        $pagecontent .= "<option ".$selected."value=\"".$element."\">".$specialchars->rebuildSpecialChars($element, true, true)."</option>";
    }
    $pagecontent .= "</select></td>";
    $pagecontent .= "</tr>";
    // Zeile "MENU2"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_menu2")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\">";
    $pagecontent .= "<select name=\"menu2\" class=\"input_cms_select\"".$error_color['menu2'].">";
    $selected = NULL;
    if($CMS_CONF->get("menu2") == "") {
        $selected = "selected ";
    }
    $pagecontent .= '<option '.$selected.'value="no_menu2">'.getLanguageValue("config_select_menu2").'</option>';
    foreach($cat_array as $element) {
        if (count(getFiles("$CONTENT_DIR_REL/".$element, "")) == 0) {
            continue;
        }
        $selected = NULL;
        if ($element == $CMS_CONF->get("menu2")) {
            $selected = "selected ";
        }
        $pagecontent .= "<option ".$selected."value=\"".$element."\">".$specialchars->rebuildSpecialChars($element, true, true)."</option>";
    }
    $pagecontent .= "</select></td>";
    $pagecontent .= "</tr>";

    if($ADMIN_CONF->get('showexpert') == "true") {
        // Zeile "NUTZE SUBMENÃœ"
        $checked0 = "";
        $checked1 = "";
        $checked2 = "";
        if ($CMS_CONF->get("usesubmenu") == "2") {
            $checked2 = " checked=\"checked\"";
        } elseif ($CMS_CONF->get("usesubmenu") == "1") {
            $checked1 = " checked=\"checked\"";
        } else {
            $checked0 = " checked=\"checked\"";
        }
        $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_usesubmenu")."</td>";
        $pagecontent .= "<td class=\"td_cms_left\">";
        $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"0\"$checked0 />".getLanguageValue("config_input_usesubmenu_1")."<br />";
        $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"1\"$checked1 />".getLanguageValue("config_input_usesubmenu_2")."<br />";
        $pagecontent .= "<input type=\"radio\" name=\"usesubmenu\" value=\"2\"$checked2 />".getLanguageValue("config_input_usesubmenu_3")."<br />";
        $pagecontent .= "</td>";
        $pagecontent .= "</tr>";
    }

    // SYNTAX-EINSTELLUNGEN
    $pagecontent .= "<tr>";
    $pagecontent .= "<td class=\"td_cms_titel\" colspan=\"2\">".getLanguageValue("config_titel_cmssyntax")."</td>";
    $pagecontent .= "</tr>";
    if($ADMIN_CONF->get('showexpert') == "true") {
        // Zeile "NUTZE CMS-SYNTAX"
        $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_usesyntax")."</td>";
        $pagecontent .= "<td class=\"td_cms_left\">"
        .buildCheckBox("usecmssyntax", $CMS_CONF->get("usecmssyntax"))
        .getLanguageValue("config_input_usesyntax");
        $pagecontent .= "</td></tr>";
    }
    // Die folgenden Einstellungen werden nur angezeigt, wenn die CMS-Syntax aktiv ist
    if ($CMS_CONF->get("usecmssyntax") == "true") {
        if($ADMIN_CONF->get('showexpert') == "true") {
            // Zeile "LINKS KÃœRZEN"
            $checked0 = "";
            $checked1 = "";
            $checked2 = "";
            if ($CMS_CONF->get("shortenlinks") == "2")
                $checked2 = " checked=\"checked\"";
            elseif ($CMS_CONF->get("shortenlinks") == "1")
                $checked1 = " checked=\"checked\"";
            else
                $checked0 = " checked=\"checked\"";
            $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_syntaxshortenlinks")."</td>";
            $pagecontent .= "<td class=\"td_cms_left\">";
            $pagecontent .= "<input type=\"radio\" name=\"shortenlinks\" value=\"0\"$checked0 />http://www.domain.com<br />";
            $pagecontent .= "<input type=\"radio\" name=\"shortenlinks\" value=\"1\"$checked1 />www.domain.com<br />";
            $pagecontent .= "<input type=\"radio\" name=\"shortenlinks\" value=\"2\"$checked2 />domain.com<br />";
            $pagecontent .= "</td>";
            $pagecontent .= "</tr>";
        }

        // Zeile "BENUTZERDEFINIERTE SYNTAX-ELEMENTE"
        $usersyntaxdefs = "";
        $handle = @fopen($USER_SYNTAX_FILE, "r");
        $usersyntaxdefs = @fread($handle, @filesize($USER_SYNTAX_FILE));
        @fclose($handle);
        $pagecontent .= "<tr><td class=\"td_cms_colspan2\" colspan=\"2\">".getLanguageValue("config_text_usersyntax")."</td>";
        $pagecontent .= "<tr><td class=\"td_cms_left\" colspan=\"2\"><textarea class=\"textarea_cms\" cols=\"116\" rows=\"6\" name=\"usersyntax\" ".$error_color['usersyntax'].">".$specialchars->rebuildSpecialChars($usersyntaxdefs,false,true)."</textarea></td></tr>";
        // Zeile "ERSETZE EMOTICONS"
        if($ADMIN_CONF->get('showexpert') == "true") {
            $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_replaceemoticons")."</td>";
            $pagecontent .= "<td class=\"td_cms_left\">"
            .buildCheckBox("replaceemoticons", ($CMS_CONF->get("replaceemoticons") == "true"))
            .getLanguageValue("config_input_replaceemoticons")."</td>";
            $pagecontent .= "</tr>";
        }
    }

            // KONTAKTFORMULAR-EINSTELLUNGEN formularmail
    $pagecontent .= "<tr>";
    $pagecontent .= '<td class="td_cms_titel" colspan="2">'.getLanguageValue("config_titel_contact").'</td>';
    $pagecontent .= "</tr>";
    $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_formularmail")."</td>";
    $pagecontent .= '<td class="td_cms_left">
    <input type="text" class="input_cms_text" name="formularmail" value="'.$specialchars->rebuildSpecialChars($CONTACT_CONF->get("formularmail"),true,true).'"'.$error_color['formularmail'].' /></td></tr>';
    $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_contactformwaittime")."</td>";
    $pagecontent .= '<td class="td_cms_left">
    <input type="text" class="input_cms_text" name="contactformwaittime" value="'.$specialchars->rebuildSpecialChars($CONTACT_CONF->get("contactformwaittime"),true,true).'"'.$error_color['contactformwaittime'].' /></td></tr>';
    $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_contactformusespamprotection")."</td>";
    $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("contactformusespamprotection", ($CONTACT_CONF->get("contactformusespamprotection") == "true")).'</td></tr>';
    // Zeile "ANGEZEIGTE FELDER / PFLICHTFELDER"
    $config_name = explode(",", ($CONTACT_CONF->get("name")));
    $config_mail = explode(",", ($CONTACT_CONF->get("mail")));
    $config_website = explode(",", ($CONTACT_CONF->get("website")));
    $config_message = explode(",", ($CONTACT_CONF->get("message")));
    $pagecontent .= '<tr><td class="td_cms_left" colspan="2">'.getLanguageValue("config_text_contact").'</td></tr>'
    .'<tr><td class="td_cms_left" colspan="2">'
    .'<table width="90%" cellspacing="0" border="0" cellpadding="0" align="right" class="table_contact">'
    .'<tr><td width="40%" class="td_contact_title" align="left">'.getLanguageValue("config_titel_contact_help").'</td>'
    .'<td width="30%" class="td_contact_title" align="left">'.getLanguageValue("config_titel_contact_input").'</td>'
    .'<td width="10%" class="td_contact_title" align="center">'.getLanguageValue("config_titel_contact_show").'</td>'
    .'<td width="10%" class="td_contact_title" align="center">'.getLanguageValue("config_titel_contact_mandatory").'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_mail").'</td><td><input type="text" class="input_cms_text" name="titel_mail" value="'.$specialchars->rebuildSpecialChars($config_mail[0],true,true).'"'.$error_color['titel_mail'].' /></td><td align="center">'.buildCheckBox("show_mail", ($config_mail[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_mail", ($config_mail[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_text").'</td><td><input type="text" class="input_cms_text" name="titel_name" value="'.$specialchars->rebuildSpecialChars($config_name[0],true,true).'"'.$error_color['titel_name'].' /></td><td align="center">'.buildCheckBox("show_name", ($config_name[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_name", ($config_name[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_text").'</td><td><input type="text" class="input_cms_text" name="titel_website" value="'.$specialchars->rebuildSpecialChars($config_website[0],true,true).'"'.$error_color['titel_website'].' /></td><td align="center">'.buildCheckBox("show_website", ($config_website[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_website", ($config_website[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_textarea").'</td><td><input type="text" class="input_cms_text" name="titel_message" value="'.$specialchars->rebuildSpecialChars($config_message[0],true,true).'"'.$error_color['titel_message'].' /></td><td align="center">'.buildCheckBox("show_message", ($config_message[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_message", ($config_message[2] == "true")).'</td></tr>'
    ."</table></td>";
    $pagecontent .= "</tr>";

    if($ADMIN_CONF->get('showexpert') == "true") {
        $pagecontent .= '<tr><td class="td_cms_titel" colspan="2">'.getLanguageValue("config_titel_expert").'</td></tr>';
        // Zeile "showhiddenpagesin"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_showhiddenpages").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("showhiddenpagesinlastchanged", ($CMS_CONF->get("showhiddenpagesinlastchanged") == "true")).getLanguageValue("config_input_lastchanged").'<br>'.buildCheckBox("showhiddenpagesinsearch", ($CMS_CONF->get("showhiddenpagesinsearch") == "true")).getLanguageValue("config_input_search").'<br>'.buildCheckBox("showhiddenpagesinsitemap", ($CMS_CONF->get("showhiddenpagesinsitemap") == "true")).getLanguageValue("config_input_sitemap").'</td></tr>';
        // Zeile "Links öffnen self blank"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_target").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("targetblank_download", ($CMS_CONF->get("targetblank_download") == "true")).getLanguageValue("config_input_download")
#.'<br>'.buildCheckBox("targetblank_gallery", ($CMS_CONF->get("targetblank_gallery") == "true")).getLanguageValue("config_input_gallery")
.'<br>'.buildCheckBox("targetblank_link", ($CMS_CONF->get("targetblank_link") == "true")).getLanguageValue("config_input_link").'</td></tr>';
        // Zeile "wenn page == cat"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_catnamedpages").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("hidecatnamedpages", ($CMS_CONF->get("hidecatnamedpages") == "true")).getLanguageValue("config_input_catnamedpages").'</td></tr>';
        // Zeile "mod_rewrite"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_modrewrite").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("modrewrite", ($CMS_CONF->get("modrewrite") == "true")).getLanguageValue("config_input_modrewrite").'</td></tr>';
        // Zeile "showsyntaxtooltips"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_showsyntaxtooltips").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("showsyntaxtooltips", ($CMS_CONF->get("showsyntaxtooltips") == "true")).getLanguageValue("config_input_showsyntaxtooltips").'</td></tr>';
    }
    // Zeile "ÃœBERNEHMEN"
    # Save Buttom nur Anzeigen wenn Properties auch Speichen kann
    if(!isset($CMS_CONF->properties['error']) or !isset($CONTACT_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit" colspan="2">';
        $pagecontent .= '<input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("config_submit").'" />';
/*
        if($ADMIN_CONF->get('showexpert') == "true") {
            $pagecontent .= '&nbsp;&nbsp;&nbsp;'.getLanguageValue("config_input_default").buildCheckBox("default", "false").'</td></tr>';
        }*/
    }

    $pagecontent .= "</table>";

    return array(getLanguageValue("config_button"), $pagecontent);
}

function admin($post) {
    global $ADMIN_CONF;
    global $LOGINCONF;
    global $CMS_CONF;
    global $MAILFUNCTIONS;
    global $specialchars;
    $icon_size = "24x24";


    $basic = makeDefaultConf("basic");

    # error colors für die input felder vorbereiten
    foreach($basic as $type => $type_array) {
        if($type == 'expert') continue;
        foreach($basic[$type] as $syntax_name => $dumy) {
            $error_color[$syntax_name] = NULL;
        }
    }

    $error_color['newname'] = NULL;
    $error_color['newpw'] = NULL;
    $error_color['newpwrepeat'] = NULL;
    // Ã„nderungen gespeichert
    $changesmade = false;

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("admin_button").'">';

    $error_color['language'] = NULL;
    $language_array = getFiles('sprachen',true);
    if(count($language_array) <= 0) {
        $post['error_messages']['admin_error_language_emty'][] = NULL;
        $error_color['language'] = ' style="background-color:#FF7029;"';
    } elseif(!in_array("language_".$ADMIN_CONF->get('language').".conf",$language_array)) {
        $post['error_messages']['admin_error_languagefile_error'][] = $ADMIN_CONF->get('language');
        $error_color['language'] = ' style="background-color:#FF7029;"';
    }

    if(isset($ADMIN_CONF->properties['error'])) {
        $post['apply'] = "false";
        $post['error_messages']['properties'][] = $ADMIN_CONF->properties['error'];
    }

    if(isset($post['apply'])) {
        $post['apply'] = $specialchars->rebuildSpecialChars($post['apply'], false, true);
    }

    if(isset($post['apply']) and $post['apply'] == getLanguageValue("admin_submit") and isset($post['default'])) {
#        makeDefaultConf("conf/basic.conf");
        $post['apply'] = "false";
    }

    if(isset($post['default_pw']) and $specialchars->rebuildSpecialChars($post['default_pw'], false, true) == getLanguageValue("admin_submit_default_pw")) {
        $LOGINCONF->set("initialsetup", "false");
    }

    if($LOGINCONF->get("initialsetup") == "true") {
        $post['error_messages']['initialsetup'][] = NULL;
    }

    // Auswertung des Formulars
    if(isset($post['apply']) and $post['apply'] == getLanguageValue("admin_submit")) {

        # auf jeden fall erst mal deDE setzen ist blöd wenn kein language gesetzt ist
        $ADMIN_CONF->set('language', "deDE");
        if(count($language_array) > 0) {
            $ADMIN_CONF->set('language', $post['language']);
        }

        # wenn expert eingeschaltet wird müssen die expert $post gefühlt werden
        if(checkBoxChecked('showexpert') == "true" and $ADMIN_CONF->get('showexpert') == "false") {
            foreach($basic['expert'] as $syntax) {
                if($syntax == 'lastbackup' or $syntax == 'usebigactionicons') {
                    continue;
                }
                $post[$syntax] = $ADMIN_CONF->get($syntax);
            }
        }



        foreach($basic as $type => $type_array) {
            if($type == 'expert') continue;
            foreach($basic[$type] as $syntax_name => $dumy) {
                $error_messages = false;
                if(checkBoxChecked('showexpert') == "false" and in_array($syntax_name,$basic['expert'])) {
                    continue;
                }
                if($type == 'text') {
                    if(isset($post[$syntax_name])) {
                        $text = $post[$syntax_name];
                    } else continue;
                    if($syntax_name == 'adminmail' and $text != "" and !preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/",$text)) {
                        $error_messages = $syntax_name;
                    }
                    if($error_messages === false and $ADMIN_CONF->get($syntax_name) != $text) {
                        $ADMIN_CONF->set($syntax_name, $specialchars->replaceSpecialChars($text,false));
                    }
        
                }
                if($type == 'digit') {
                    if($syntax_name == 'lastbackup') {
        # das muss noch!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                        continue;
                    }
                    if(isset($post[$syntax_name])) {
                        $digit = trim($post[$syntax_name]);
                    } else continue;
                    # wenn eingabe keine Zahl oder mehr wie 4stelig ist
                    if($digit != "" and (!ctype_digit($digit) or strlen($digit) > 4)) {
                        $error_messages = 'nodigit_tolong';
                    } elseif($syntax_name == 'chmodnewfilesatts' and !empty($digit) and !preg_match("/^[0-7]{3}$/",$digit)) {
                        $error_messages = $syntax_name;
                    } elseif($syntax_name == 'textareaheight' and $digit < 50) {
                        $error_messages = $syntax_name;
                    } elseif($syntax_name == 'maxnumberofuploadfiles' and $digit < 1) {
                        $error_messages = $syntax_name;
                    } elseif($syntax_name == 'backupmsgintervall' and ($digit < 0 or $digit == "")) {
                        $error_messages = $syntax_name;
                    }
        
                    if($error_messages === false and $ADMIN_CONF->get($syntax_name) != $digit) {
                        $ADMIN_CONF->set($syntax_name, $digit);
                    }
                }
                if($type == 'checkbox') {

                    $checkbox = "false";
                    if(isset($post[$syntax_name])) {
                        $checkbox = $post[$syntax_name];
                    }
                    if($syntax_name == 'sendadminmail' and $checkbox == "true" and $post['adminmail'] == "") {
                        $error_messages = $syntax_name;
                    }
        
                    if($error_messages === false and $ADMIN_CONF->get($syntax_name) != $checkbox) {
                        $ADMIN_CONF->set($syntax_name, $checkbox);
                    }
                }
                if($error_messages !== false) {
                    $post['error_messages']['admin_error_'.$syntax_name]['color'] = "#FF7029";
                    $post['error_messages']['admin_error_'.$syntax_name][] = NULL;
                    $error_color[$syntax_name] = ' style="background-color:#FF7029;"';
                }
            }
        }

        if(checkBoxChecked('chmodupdate') == "true" and $ADMIN_CONF->get('chmodnewfilesatts') != "") {
            $error = useChmod();
            if(!empty($error)) {
                $post['error_messages'] = $error;
            }
        }

        require_once("Crypt.php");
        $pwcrypt = new Crypt();
        if($post['newpw'] != "" and $pwcrypt->encrypt($post['newpw']) != $LOGINCONF->get("pw")) {
            if (($post['newname'] == "" ) or ($post['newpw'] == "" ) or ($post['newpwrepeat'] == "" )) {
                $post['error_messages']['pw_error_missingvalues']['color'] = "#FF7029";
                $post['error_messages']['pw_error_missingvalues'][] = NULL;
                $error_color['newname'] = ' style="background-color:#FF7029;"';
                $error_color['newpw'] = ' style="background-color:#FF7029;"';
                $error_color['newpwrepeat'] = ' style="background-color:#FF7029;"';
            } elseif(strlen($post['newname']) < 5) {
                $post['error_messages']['pw_error_tooshortname']['color'] = "#FF7029";
                $post['error_messages']['pw_error_tooshortname'][] = NULL;
                $error_color['newname'] = ' style="background-color:#FF7029;"';
            // Neues Paßwort zweimal exakt gleich eingegeben?
            } elseif ($post['newpw'] != $post['newpwrepeat']) {
                $post['error_messages']['pw_error_newpwmismatch']['color'] = "#FF7029";
                $post['error_messages']['pw_error_newpwmismatch'][] = NULL;
                $error_color['newpw'] = ' style="background-color:#FF7029;"';
                $error_color['newpwrepeat'] = ' style="background-color:#FF7029;"';
            // Neues Paßwort wenigstens sechs Zeichen lang und mindestens aus kleinen und großen Buchstaben sowie Zahlen bestehend?
            } elseif ((strlen($post['newpw']) <= 6) or !preg_match("/[0-9]/", $post['newpw']) or !preg_match("/[a-z]/", $post['newpw']) or !preg_match("/[A-Z]/", $post['newpw'])) {
                $post['error_messages']['pw_error_newpwerror']['color'] = "#FF7029";
                $post['error_messages']['pw_error_newpwerror'][] = NULL;
                $error_color['newpw'] = ' style="background-color:#FF7029;"';
            # Allles gut Speichen
            } else {
                # initialsetub sachen speichern
                if($LOGINCONF->get("initialsetup") == "true") {
                    $LOGINCONF->set("initialsetup", "false");
                    $LOGINCONF->set("initialpw", "false");
                    $ADMIN_CONF->set('lastbackup', time());
                }
                $LOGINCONF->set("name", $post['newname']);
                $LOGINCONF->set("pw", $pwcrypt->encrypt($post['newpw']));
                $post['messages']['pw_messages_changes'][] = NULL;
            }
        }

        $post['messages']['admin_messages_changes'][] = NULL;

    } #applay end

    # Anzeige begin
    $pagecontent .= categoriesMessages($post);

    $array_getTooltipValue = array("admin_help_language","admin_help_adminmail","admin_help_chmodnewfiles",
        "admin_help_chmodupdate");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(isset($post['javascript']) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    if(isset($post['javascript']) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_admin_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","admin_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_admin_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration&amp;page=70_Konfiguration" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }


    $pagecontent .= '<span class="titel">'.getLanguageValue("admin_button").'</span>';
    $pagecontent .= $tooltip_admin_help;
    $pagecontent .= "<p>".getLanguageValue("admin_text")."</p>";



    $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
    $pagecontent .= "<tr>";
    $pagecontent .= '<td class="td_cms_titel" colspan="2">'.getLanguageValue("admin_text").'</td>';
    $pagecontent .= "</tr>";
    if($LOGINCONF->get("initialsetup") == "false") {
        // Zeile "ZEIGE EXPERT" 
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_showexpert")."</td>";
        $pagecontent .= '<td class="td_cms_left">'
        .buildCheckBox("showexpert", ($ADMIN_CONF->get("showexpert") == "true"))
        .'<span '.$error_color['showexpert'].'>'.getLanguageValue("admin_input_showexpert")."</span></td>";
        $pagecontent .= "</tr>";
        // Zeile "ZEIGE TOOLTIPS"
        if($ADMIN_CONF->get('showexpert') == "true") {
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_tooltips")."</td>";
            $pagecontent .= '<td class="td_cms_left">'
            .buildCheckBox("showTooltips", ($ADMIN_CONF->get("showTooltips") == "true"))
            .getLanguageValue("admin_input_tooltips")."</td>";
            $pagecontent .= "</tr>";
        }
    }
    // Zeile "SPRACHAUSWAHL"
    $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_input_language").'</td><td class="td_cms_left"><select name="language" class="input_cms_select"'.$tooltip_admin_help_language.$error_color['language'].'>';

    foreach($language_array as $element) {
        if(substr($element,0,9) == "language_") {
            $selected = NULL;
            $tmp_array = file("sprachen/".$element);
            $currentlanguage = NULL;
            foreach($tmp_array as $line) {
                if (preg_match("/^#/",$line) || preg_match("/^\s*$/",$line)) {
                    continue;
                }
                if (preg_match("/^([^=]*)=(.*)/",$line,$matches)) {
                    if(trim($matches[1]) == "_translator") {
                        $currentlanguage = trim($matches[2]);
                        break;
                    }
                }
            }
            if (substr($element,9,4) == $ADMIN_CONF->get("language")) {
                $selected = "selected ";
            }
            $pagecontent .= "<option ".$selected."value=\"".substr($element,9,4)."\">".substr($element,9,4)." (".getLanguageValue("admin_input_translator")." ".$currentlanguage."</option>";
        }
    }

    $pagecontent .= "</select></td></tr>";
    if($LOGINCONF->get("initialsetup") == "false") {
        // Zeile "ADMIN-MAIL"
        if($MAILFUNCTIONS->isMailAvailable())
        {
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_adminmail").'</td>';
            $pagecontent .= '<td class="td_cms_left">'
            .buildCheckBox("sendadminmail", ($ADMIN_CONF->get("sendadminmail") == "true"))
            .getLanguageValue("admin_input_adminmail");
            $pagecontent .= "</td>";
            $pagecontent .= "</tr>";

            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_adminmail").'</td>';
            $pagecontent .= '<td class="td_cms_left">';
            $pagecontent .= '<input type="text" class="input_text" name="adminmail" value="'.$ADMIN_CONF->get("adminmail").'"'.$tooltip_admin_help_adminmail.$error_color['adminmail'].' />';
            $pagecontent .= "</td>";
            $pagecontent .= "</tr>";
        }
        if($ADMIN_CONF->get('showexpert') == "true") {
            // Zeile "HÃ–HE DES TEXTFELDES"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_textarea").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="textareaheight" value="'.$ADMIN_CONF->get("textareaheight").'"'.$error_color['textareaheight'].' /></td>';
            $pagecontent .= "</tr>";
            // Zeile "BACKUP-ERINNERUNG"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_backup").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="backupmsgintervall" value="'.$ADMIN_CONF->get("backupmsgintervall").'"'.$error_color['backupmsgintervall'].' /></td>';
            $pagecontent .= "</tr>";
        }
        // Zeile "SETZE DATEIRECHTE FÃœR NEUE DATEIEN"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_chmodnewfiles").'</td>';
        $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="chmodnewfilesatts" value="'.$ADMIN_CONF->get("chmodnewfilesatts").'"'.$tooltip_admin_help_chmodnewfiles.$error_color['chmodnewfilesatts'].' /><br />'
        .buildCheckBox("chmodupdate", false)
        .'<span'.$tooltip_admin_help_chmodupdate.'>'.getLanguageValue("admin_input_chmodupdate").'</span>'.'';
        $pagecontent .= '</td>';
        $pagecontent .= "</tr>";
        if($ADMIN_CONF->get('showexpert') == "true") {
            // Zeile "MAXIMALE DATEIANZAHL BEIM UPLOAD"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_maxuploadfiles").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_cms_zahl" size="2" maxlength="4" name="maxnumberofuploadfiles" value="'.$ADMIN_CONF->get("maxnumberofuploadfiles").'"'.$error_color['maxnumberofuploadfiles'].' /></td>';
            $pagecontent .= "</tr>";
            // Zeile "UPLOAD-FILTER"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_uploadfilter").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="noupload" value="'.$ADMIN_CONF->get("noupload").'"'.$error_color['noupload'].' /></td>';
            $pagecontent .= "</tr>";
            // Zeile "VORHANDENE DATEIEN BEIM UPLOAD ÃœBERSCHREIBEN"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_defaultoverwrite").'</td>';
            $pagecontent .= '<td class="td_cms_left">'
            .buildCheckBox("overwrite", ($ADMIN_CONF->get("overwriteuploadfiles") == "true"))
            .getLanguageValue("admin_input_defaultoverwrite").'</td>';
            $pagecontent .= "</tr>";
        }
        // BILD-EINSTELLUNGEN
        if (extension_loaded("gd"))
        {
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_imagesmax");
            $pagecontent .= "</td>";
            $pagecontent .= '<td class="td_cms_left">';
#            $pagecontent .= buildCheckBox("resizeimages", $ADMIN_CONF->get("resizeimages") == "true") . getLanguageValue("admin_input_imagesmax").'<br>';
            $pagecontent .= '<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="maximagewidth" value="'.$ADMIN_CONF->get("maximagewidth").'"'.$error_color['maximagewidth'].' />&nbsp;x&nbsp;<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="maximageheight" value="'.$ADMIN_CONF->get("maximageheight").'"'.$error_color['maximageheight'].' />&nbsp;' . getLanguageValue("pixels") . '</td>';
            $pagecontent .= "</tr>";
        }
    }
    $pagecontent .= '<tr><td class="td_cms_left" colspan="2">'.getLanguageValue("pw_text_login").'</td>'
    // Zeile "NEUER NAME"
    .'<tr><td class="td_cms_right">'.getLanguageValue("pw_titel_newname").'</td>'
    .'<td class="td_cms_left"><input type="text" class="input_text" name="newname" value=""'.$error_color['newname'].' /></td>'
    ."</tr>"
    // Zeile "NEUES PASSWORT" 
    .'<tr><td class="td_cms_right">'.getLanguageValue("pw_titel_newpw").'</td>'
    .'<td class="td_cms_left"><input type="password" class="input_text" value="'.NULL.'" name="newpw"'.$error_color['newpw'].' /></td>'
    ."</tr>"
    // Zeile "NEUES PASSWORT - WIEDERHOLUNG"
    .'<tr><td class="td_cms_right">'.getLanguageValue("pw_titel_newpwrepeat").'</td>'
    .'<td class="td_cms_left"><input type="password" class="input_text" value="" name="newpwrepeat"'.$error_color['newpwrepeat'].' /></td>'
    ."</tr>";

    // Zeile "ÃœBERNEHMEN"
    if(!isset($ADMIN_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit" colspan="2"><input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("admin_submit").'">';
        if($LOGINCONF->get("initialsetup") == "true") {
            $pagecontent .= '&nbsp;&nbsp;&nbsp;<input type="submit" name="default_pw" class="input_submit" value="'.getLanguageValue("admin_submit_default_pw").'">';
        }
/*
        if($ADMIN_CONF->get('showexpert') == "true") {
            $pagecontent .= '&nbsp;&nbsp;&nbsp;'.getLanguageValue("admin_input_default").buildCheckBox("default", "false");
        }*/
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= "</table>";
    //$pagecontent .= "</td></tr></table>";
    return array(getLanguageValue("admin_button"), $pagecontent);
}

function plugins($post) {
    global $ADMIN_CONF;
    $icon_size = "24x24";

    $PLUGIN_DIR = "plugins";

    require_once("../Plugin.php");

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("plugins_button").'">';

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_plugins_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","plugins_help",",WIDTH,400,CLICKCLOSE,true").'>';
        $tooltip_help_edit = createTooltipWZ("","plugins_help_edit",",WIDTH,200,CLICKCLOSE,true");
    } else {
        $tooltip_plugins_help = '<a href="http://cms.mozilo.de/index.php?cat=30_Administration" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
        $tooltip_help_edit = NULL;
    }
 
    $pagecontent .= '<span class="titel">'.getLanguageValue("plugins_titel").'</span>';
    $pagecontent .= $tooltip_plugins_help;
    $pagecontent .= "<p>".getLanguageValue("plugins_text")."</p>";
    $pagecontent .= '<input type="submit" class="input_submit" name="apply" value="Speichern Test"/>';
    $pagecontent .= '<table width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';

    $dircontent = getDirs("../$PLUGIN_DIR", true);
    $toggle_pos = 0;
    foreach ($dircontent as $currentelement) {
        if (file_exists("../$PLUGIN_DIR/".$currentelement."/index.php")) {
            require_once("../$PLUGIN_DIR/".$currentelement."/index.php");
            $plugin = new $currentelement();
            // Enthält der Code eine Klasse mit dem Namen des Plugins?
            if (class_exists($currentelement)) {
                $plugin_info = $plugin->getInfo();
                $pagecontent .= '<tr><td width="100%" class="td_toggle">';
                $pagecontent .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
                $plugin_error = false;
                if(isset($plugin_info[0])) {
                    $plugin_name = $plugin_info[0];
                } else {
                    $plugin_name = getLanguageValue('plugins_error').' <span style="color:#ff0000">'.$currentelement.'</span>';
                    $plugin_error = true;
                }
                $pagecontent .= '<tr><td width="85%" class="td_titel"><span class="text_cat_page">'.$plugin_name.'</span></td>';
                $pagecontent .= '<td width="85%" class="td_icons">';
                if(getRequestParam('javascript', true) and $plugin_error === false) {
                    $pagecontent .= '<span id="toggle_'.$toggle_pos.'_linkBild"'.$tooltip_help_edit.'></span>';
                }
                $pagecontent .= '&nbsp;</td></tr></table>';
                $pagecontent .= '</td></tr>';

                $display_toggle = NULL;
                $messages = NULL;
                $pagecontent_conf = NULL;
                if($plugin_error === false) {
                    $pagecontent_start_conf = '<table width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
                    # Plugin Infos
                    $plugins_info_array = array("plugins_titel_version","plugins_titel_over","plugins_titel_author","plugins_titel_web");
                    $pos = 1;
                    foreach($plugins_info_array as $info) {
                        if($pos == 4) {
                            $plugin_info[$pos] = '<a href="'.$plugin_info[$pos].'" target="_blank">'.$plugin_info[$pos].'</a>';
                        }
                        if(isset($plugin_info[$pos])) {
                            $pagecontent_conf .= '<tr><td width="0%" class="td_togglen_padding_bottom" nowrap><b class="text_grau">'.getLanguageValue($info).'</b></td><td width="90%" class="td_togglen_padding_bottom">'.$plugin_info[$pos].'</td></tr>';
                        }
                        $pos++;
                    }

                    if(count($plugin->getConfig()) > 1) {
                        $pagecontent_conf .= '<tr><td width="100%" colspan="2" class="td_togglen_padding_bottom" nowrap>';
                        $pagecontent_conf .= '<table width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';

                        $config = $plugin->getConfig();
                        foreach($config as $name => $inhalt) {
                            $error = NULL;
                            # plugin.conf in class einlessen wen vorhanden und schreibbar ist
                            if(file_exists("../$PLUGIN_DIR/".$currentelement."/plugin.conf")) {
                                $conf_plugin = new Properties("../$PLUGIN_DIR/".$currentelement."/plugin.conf",true);
                                if(isset($conf_plugin->properties['error'])) {
                                    unset($conf_plugin);
                                    $messages = returnMessage(false, getLanguageValue("properties_write").'&nbsp;&nbsp;<span style="font-weight:normal;">plugin.conf</span>');
                                    $display_toggle = ' style="display:block;"';
                                    break;
                                }
                            }
                            # Änderungen schreiben isset($_POST['apply'])
                            if(getRequestParam('apply', true)) {
                                if(isset($_POST[$currentelement][$name])) {
                                    # ist array bei radio und select multi
                                    if(is_array($_POST[$currentelement][$name])) {
                                        $conf_inhalt = implode(",", trim($_POST[$currentelement][$name]));
                                    # alle die kein array sind
                                    } else {
                                        $conf_inhalt = str_replace(array("\r\n","\r","\n"),"<br>",trim($_POST[$currentelement][$name]));
                                    }
                                    if(isset($config[$name]['regex']) and strlen($conf_inhalt) > 0) {
                                        if(isset($config[$name]['regex_error'])) {
                                            $regex_error = $config[$name]['regex_error'];
                                        } else {
                                            $regex_error = getLanguageValue("plugins_messages_input");
                                        }
                                        if(preg_match($config[$name]['regex'], $conf_inhalt)) {
                                            # bei Password und verschlüsselung an
                                            if($config[$name]['type'] == "password" and $config[$name]['saveasmd5'] == "true") {
                                                $conf_inhalt = md5($conf_inhalt);
                                            }
                                            # nur in conf schreiben wenn sich der wert geändert hat
                                            if($conf_plugin->get($name) != $conf_inhalt) {
                                                $conf_plugin->set($name,$conf_inhalt);
                                                $display_toggle = ' style="display:block;"';
                                                $messages = returnMessage(true, $regex_error);
                                            }
                                        } else {
                                            $error = ' style="background-color:#FF0000;"';
                                            $display_toggle = ' style="display:block;"';
                                            $messages = returnMessage(false, $regex_error);
                                        }
                                    } else {
                                        # nur in conf schreiben wenn sich der wert geändert hat und es kein password ist
                                        if($conf_plugin->get($name) != $conf_inhalt and $config[$name]['type'] != "password") {
                                            $conf_plugin->set($name,$conf_inhalt);
                                            $display_toggle = ' style="display:block;"';
                                            $messages = returnMessage(true, $regex_error);
                                        }
                                   }
                                # checkbox
                                } elseif($config[$name]['type'] == "checkbox" and $conf_plugin->get($name) == "true") {
                                    $conf_plugin->set($name,"false");
                                    $display_toggle = ' style="display:block;"';
                                    $messages = returnMessage(true, getLanguageValue("plugins_messages_input"));
                                # variable gibts also schreiben mit lehren wert
                                } elseif($conf_plugin->get($name)) {
                                    $conf_plugin->set($name,"");
                                    $display_toggle = ' style="display:block;"';
                                    $messages = returnMessage(true, getLanguageValue("plugins_messages_input"));
                                }
                           }
                            # Beschreibung und inputs der Konfiguration Bauen und ausgeben
                            $value = NULL;
                            if($conf_plugin->get($name)) {
                                $value = ' value="'.$conf_plugin->get($name).'"';
                                if($config[$name]['type'] == "textarea") {
                                    $value = str_replace("<br>","\n",$conf_plugin->get($name));
                                }
                                if($config[$name]['type'] == "password") {
                                    $value = NULL;
                                }
                            }
                            $maxlength = NULL;
                            if(isset($config[$name]['maxlength'])) {
                                $maxlength = ' maxlength="'.$config[$name]['maxlength'].'"';
                            }
                            $size = NULL;
                            if(isset($config[$name]['size'])) {
                                $size = ' size="'.$config[$name]['size'].'"';
                            }
                            $cols = NULL;
                            if(isset($config[$name]['cols'])) {
                                $cols = ' cols="'.$config[$name]['cols'].'"';
                            }
                            $rows = NULL;
                            if(isset($config[$name]['rows'])) {
                                $rows = ' rows="'.$config[$name]['rows'].'"';
                            }
                            $multiple = NULL;
                            if(isset($config[$name]['multiple']) and $config[$name]['multiple'] == "true") {
                                $multiple = ' multiple';
                            }
                            $type = NULL;
                            $input = NULL;
                            if(isset($config[$name]['type'])) {
                                $type = ' type="'.$config[$name]['type'].'"';
                                if($config[$name]['type'] == "textarea") {
                                    $input = '<textarea name="'.$currentelement.'['.$name.']"'.$cols.$rows.' class="plugin_textarea"'.$error.'>'.$value.'</textarea>';
                                } elseif($config[$name]['type'] == "select") {
                                    $plus_array = NULL;
                                    if(!empty($multiple)) {
                                        $plus_array = '[]';
                                    }
                                    $input = '<select name="'.$currentelement.'['.$name.']'.$plus_array.'"'.$size.$multiple.' class="plugin_select">';
                                    if(is_array($config[$name]['descriptions'])) {
                                        foreach($config[$name]['descriptions'] as $key => $descriptions) {
                                            $value = ' value="'.$key.'"';
                                            $selected = NULL;
                                            if($conf_plugin->get($name)) {
                                                $select_array = explode(",",$conf_plugin->get($name));
                                                foreach($select_array as $test) {
                                                    if($test == $key) {
                                                        $selected = ' selected';
                                                    }
                                                }
                                            }
                                            $input .= '<option'.$value.$selected.'>'.$descriptions.'</option>';
                                        }
                                    }
                                    $input .= '</select>';
                                } elseif($config[$name]['type'] == "radio") {
                                    if(is_array($config[$name]['descriptions'])) {
                                        foreach($config[$name]['descriptions'] as $key => $descriptions) {
                                            $value = ' value="'.$key.'"';
                                            $checked = NULL;
                                            if($conf_plugin->get($name) == $key) {
                                                $checked = ' checked="checked"';
                                            }
                                            $input .= $descriptions.'&nbsp;&nbsp;<input name="'.$currentelement.'['.$name.']"'.$type.$value.$checked.'><br>';
                                        }
                                    }
                                } elseif($config[$name]['type'] == "checkbox") {
                                    $checked = NULL;
                                    if($conf_plugin->get($name) == "true") {
                                        $checked = ' checked="checked"';
                                    }
                                    $input .= '<input name="'.$currentelement.'['.$name.']"'.$type.$checked.' value="true"><br>';
                                } elseif($config[$name]['type'] == "file") {
                                    $display_toggle = ' style="display:block;"';
                                    $messages .= returnMessage(false, getLanguageValue("plugins_error_type_file"));
                                    $input = '<span style="background-color:#FF0000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
                                } else {
                                    $input = '<input name="'.$currentelement.'['.$name.']"'.$type.$value.$maxlength.$size.' class="plugin_input"'.$error.'>';
                                }
                            }
                            # Ausgeben nowrap
                            $pagecontent_conf .= '<tr><td width="40%" valign="top" class="td_right_title_padding_bottom"><b>'.$config[$name]['description'].'</b></td>';
                            $pagecontent_conf .= '<td width="60%" valign="top" class="td_togglen_padding_bottom" nowrap>'.$input.'</td></tr>';
                        }
                        $pagecontent_conf .= '</table>';

                        $pagecontent_conf .= '</td></tr>';
                    }
                    $pagecontent_conf .= '</table>';


                    if(getRequestParam('javascript', true)) {
                        $pagecontent_conf .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$toggle_pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\'info_button\');</script>';
                    }
                    if(!empty($messages)) {
                        $messages = '<td colspan="2" align="left">'.$messages.'</td></tr>';
                    }
                    $pagecontent_toggle = '<tr><td width="100%" id="toggle_'.$toggle_pos.'" align="right" class="td_togglen_padding_bottom"'.$display_toggle.'>';
                    $pagecontent .= $pagecontent_toggle.$pagecontent_start_conf.$messages.$pagecontent_conf.'</td></tr>';
# conf end
                }
            }
        }
        $toggle_pos++;
    }
    $pagecontent .= '</table>';
    return array(getLanguageValue("plugins_button"), $pagecontent);
}

// Anzeige der Editieransicht
function showEditPageForm($cat, $page, $newsite)    {
    global $ADMIN_CONF;
    global $CMS_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $EXT_DRAFT;
    global $EXT_HIDDEN;
    global $EXT_PAGE;

    $content = "";
    $action = 'editsite';

    $file = $CONTENT_DIR_REL."/".$cat."/".$page;

    # Vorhandene Inhaltseite öffnen
    if ($newsite == 'editsite') {
        // Inhaltsseite: Inhalt ins Textfeld holen
        $handle=fopen($file, "r");
        if (filesize($file) > 0) {
            $pagecontent = $specialchars->rebuildSpecialChars(fread($handle, filesize($file)),true,true);
        } else
            $pagecontent = "";
        fclose($handle);
    } else {
        $pagecontent = $newsite;
    }
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
    $content .= '<textarea cols="96" rows="24" style="height:'.$height.';" name="pagecontent">'.$pagecontent.'</textarea><br />';
    $content .= '<input type="hidden" name="action_data['.$action.']['.$cat.']['.$page.']" value="'.$action.'" />';
    $content .= '<input type="submit" name="cancel" value="'.getLanguageValue("button_cancel").'" accesskey="a" /> ';
    // Zwischenspeichern-Button nicht beim Neuanlegen einer Inhaltsseite anzeigen
    if($newsite != 'newsite')
        $content .= '<input type="submit" name="savetemp" value="'.getLanguageValue("button_savetemp").'" accesskey="w" /> ';
    $content .= '<input type="submit" name="save" value="'.getLanguageValue("button_save").'" accesskey="s" /> ';
    // Auswahl "Speicher-Art"
    $extension = substr($page, strlen($page)-4, 4);
    $checkednormal = "";
    $checkedhidden = "";
    $checkeddraft = "";
    if ($extension == $EXT_PAGE) {
        $checkednormal = ' checked="checked"';
    }
    if ($extension == $EXT_HIDDEN) {
        $checkedhidden = ' checked="checked"';
    }
    if ($extension == $EXT_DRAFT) {
        $checkeddraft = ' checked="checked"';
    }
    $content .= '<input type="radio" name="saveas" value="'.$EXT_PAGE.'"'.$checkednormal.' accesskey="n" /> '.getLanguageValue("page_saveasnormal")
    .' <input type="radio" name="saveas" value="'.$EXT_HIDDEN.'"'.$checkedhidden.' accesskey="v" /> '.getLanguageValue("page_saveashidden")
    .' <input type="radio" name="saveas" value="'.$EXT_DRAFT.'"'.$checkeddraft.' accesskey="e" /> '.getLanguageValue("page_saveasdraft");
    return $content;
}

function saveContentToPage($content, $page) {

    $handle = @fopen($page, "a+");
    $line_error = __LINE__ - 1;
    $last_error = @error_get_last();
    fclose($handle);
    if($last_error['line'] == $line_error) {
        $error['php_error'][] = $last_error['message'];
        # wenns hier schonn ne meldung gibt dann gleich Raus
        return $error;
    } elseif(empty($handle)) {
        $error['page_error_save'][] = $page;
        return $error;
    } else {
        $handle = @fopen($page, "w");
        if(get_magic_quotes_gpc()) {
            fputs($handle, stripslashes($content));
        } else {
            fputs($handle, $content);
        }
        fclose($handle);
    }
    if(is_file($page)) {
        useChmod($page);
    } else {
        $error['page_error_save'][] = $page;
        return $error;
    }
    return;
}

// Lösche ein Verzeichnis rekursiv
function deleteDir($path) {

    // Existenz prüfen
    if (!file_exists($path)) {
        $error['check_is_file'][] = basename($path);
        return $error;
    }
    $handle = opendir($path);
    while ($currentelement = readdir($handle)) {
        if ($currentelement == "." or $currentelement == "..") {
            continue;
        }
        // Verzeichnis: Rekursiver Funktionsaufruf
        if (is_dir($path."/".$currentelement)) {
            $success = deleteDir($path."/".$currentelement);
        // Datei: löschen
        } else {
            $success = @unlink($path."/".$currentelement);
            $line_error = __LINE__ - 1;
            $last_error = @error_get_last();
            if($last_error['line'] == $line_error) {
                $error['php_error'][] = $last_error['message'];
                # wenns hier schonn ne meldung gibt dann gleich Raus
                return $error;
            } elseif(file_exists($path."/".$currentelement)) {
                $error['check_del_file'][] = $path."/".$currentelement;
                return $error;
            }
        }
    }
    closedir($handle);
    // Verzeichnis löschen
    $success = @rmdir($path);
    $line_error = __LINE__ - 1;
    $last_error = @error_get_last();
    if($last_error['line'] == $line_error) {
        $error['php_error'][] = $last_error['message'];
        # wenns hier schonn ne meldung gibt dann gleich Raus
        return $error;
    } elseif(file_exists($path)) {
        $error['check_del_dir'][] = $path;
        return $error;
    } else return;
}

// Überprüfe, ob die gegebene Datei eine der übergebenen Endungen hat
function fileHasExtension($filename, $extensions) {
    foreach ($extensions as $ext) {
        if (strtolower(substr($filename, strlen($filename)-(strlen($ext)+1), strlen($ext)+1)) == ".".strtolower($ext))
        return true;
    }
    return false;
}

// Gib Erfolgs- oder Fehlermeldung zurück
function returnMessage($success, $message) {
    if ($success === true) {
        return '<span class="message_erfolg">'.$message.'</span>';
    } else {
        return '<span class="message_fehler">'.$message.'</span>';
    }
}

// Smiley-Liste
function returnSmileyBar() {
    $smileys = new Smileys("../smileys");
    $content = "";
    foreach($smileys->getSmileysArray() as $icon => $emoticon) {
        if($icon == "readonly" or $icon == "error") {
            continue;
        }
        $content .= "<img class=\"jss\" title=\":$icon:\" alt=\"$emoticon\" src=\"../smileys/$icon.gif\" onClick=\"insert(' :$icon: ', '', false)\" />";
    }
    return $content;
}

// Selectbox mit allen benutzerdefinierten Syntaxelementen
function returnUserSyntaxSelectbox() {
    global $USER_SYNTAX;
    $usersyntaxarray = $USER_SYNTAX->toArray();
    ksort($usersyntaxarray);

    $content = "<select class=\"usersyntaxselectbox\" name=\"usersyntax\" onchange=\"insertTagAndResetSelectbox(this);\">"
    ."<option value=\"\">".getLanguageValue("toolbar_usersyntax")."</option>";
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
    // Ãœberschrift Syntaxelemente
    ."<td style=\"padding-right:10px;\">"
    .getLanguageValue("toolbar_syntaxelements")
    ."</td>"
    // Ãœberschrift Textformatierung
    ."<td style=\"padding-left:22px;\">"
    .getLanguageValue("toolbar_textformatting")
    ."</td>"
    // Ãœberschrift Farben
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
    .returnFormatToolbarIcon("absatz")
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

    // Ãœberschrift Inhalte
    $content .=    "<td>"
    .getLanguageValue("toolbar_contents")
    ."</td>";
    // Ãœberschrift Benutzerdefinierte Syntaxelemente
    if (count($usersyntaxarray) > 0) {
        $content .=    "<td style=\"padding-left:22px;\">"
        .getLanguageValue("toolbar_usersyntax")
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

// Rueckgabe eines Standard-Formatsymbolleisten-Icons
function returnFormatToolbarIcon($tag) {
    return "<img class=\"js\" alt=\"$tag\" title=\"[$tag| ... ] - ".getLanguageValue("toolbar_desc_".$tag)."\" src=\"gfx/jsToolbar/".$tag.".png\" onClick=\"insert('[".$tag."|', ']', true)\">";
}


// Rueckgabe einer Selectbox mit Elementen, die per Klick in die Inhaltsseite uebernommen werden können
// $type: 1=Kategorien 2=Inhaltsseiten 3=Dateien 4=Galerien
function returnOverviewSelectbox($type, $currentcat) {
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $GALLERIES_DIR_REL;
    global $EXT_PAGE;
    global $EXT_HIDDEN;
    global $EXT_LINK;

    $elements = array();
    $selectname = "";
    $spacer = "&nbsp;&bull;&nbsp;";

    switch ($type) {

        // Inhaltsseiten und Kategorien
        case 1:
            $categories = getDirContentAsArray($CONTENT_DIR_REL, false);
            foreach ($categories as $catdir) {
if(substr($catdir,-(strlen($EXT_LINK))) == $EXT_LINK) continue;
                if (isValidDirOrFile($catdir)) {
                    $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), true, true);
                    array_push($elements, array($cleancatname, $cleancatname));
                    $handle = opendir("$CONTENT_DIR_REL/$catdir");
                    while (($file = readdir($handle))) {
                        if (isValidDirOrFile($file) && is_file("$CONTENT_DIR_REL/$catdir/$file") && ((substr($file, strlen($file)-4, 4) == $EXT_PAGE) || (substr($file, strlen($file)-4, 4) == $EXT_HIDDEN))) {
                            $cleanpagename = $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file) - 3 - strlen($EXT_PAGE)), true, true);
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
                if(substr($catdir,-(strlen($EXT_LINK))) == $EXT_LINK) continue;
                if (isValidDirOrFile($catdir)) {
                    $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), true, true);
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
                        array_push($elements, array($spacer.$specialchars->rebuildSpecialChars($current_file, true, true), $specialchars->rebuildSpecialChars($current_file, true, true)));
                        else
                        array_push($elements, array($spacer.$specialchars->rebuildSpecialChars($current_file, true, true), $cleancatname.":".$specialchars->rebuildSpecialChars($current_file, true, true)));
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
                array_push($elements, array($specialchars->rebuildSpecialChars($currentgallery, false, true), $specialchars->rebuildSpecialChars($currentgallery, false, false)));
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
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("category_button")." / ".getLanguageValue("page_button").":</option>";
            break;
            // Dateien
        case 2:
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("files_button").":</option>";
            break;
            // Galerien
        case 3:
            $select .="<option class=\"noaction\" value=\"\">".getLanguageValue("gallery_button").":</option>";
            break;
    }
    // Elemente der Selectbox
    foreach ($elements as $element) {
        if (substr($element[1], 0, 1) == ":") {
            $select .= "<option class=\"noaction\" value=\"\">".$element[0]."</option>";
        }
        else {
        if(strstr($element[1],"[") or strstr($element[1],"]"))
            $element[1] = str_replace(array("[","]"),array("&#94;[","&#94;]"),$element[1]);
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
    // bearbeitetes Array wieder zurueck in die Download-Statistik schreiben
    $DOWNLOAD_COUNTS->setFromArray($downloadsarray);
}


// eine Kategorie in der Download-Statistik umbenennen
function renameCategoryInDownloadStats($oldcatname, $newcatname) {
    global $DOWNLOAD_COUNTS;
    // Download-Statistik als Array holen
    $downloadsarray = $DOWNLOAD_COUNTS->toArray();
    foreach($downloadsarray as $key => $value) {
        // Keys mit zu Ã¤nderndem Kategorienamen: im Array Ã¤ndern
        $keyparts = explode(":", $key);
        if ($keyparts[0] == $oldcatname) {
            $downloadsarray[$newcatname.":".$keyparts[1]] = $value; // Element mit neuem Key ans Array hÃ¤ngen
            unset($downloadsarray[$key]);                            // Element mit altem Key aus Array löschen
        }
    }
    // bearbeitetes Array wieder zurueck in die Download-Statistik schreiben
    $DOWNLOAD_COUNTS->setFromArray($downloadsarray);
}

// Ãœberschreibt die layoutabhÃ¤ngigen CMS-Einstellungen usesubmenu und gallerypicsperrow
function setLayoutAndDependentSettings($layoutfolder) {
    global $CMS_CONF;

    $settingsfile = "../layouts/$layoutfolder/layoutsettings.conf";

    // Einstellungen aus Layout-Settings laden und in den CMS-Einstellungen ueberschreiben
    $layoutsettings = new Properties($settingsfile);
    if(isset($layoutsettings->properties['error'])) {
        return $layoutsettings->properties;
    } else {
        if(isset($layoutsettings->properties['usesubmenu']))
            return $layoutsettings->properties['usesubmenu'];
            $CMS_CONF->set("usesubmenu", $layoutsettings->get("usesubmenu"));
    }
}

// Hochgeladene Datei ueberpruefen und speichern
function uploadFile($uploadfile, $cat, $forceoverwrite, $gallery = false){
    global $ADMIN_CONF;
    global $specialchars;
    global $CONTENT_DIR_REL;
    global $GALLERIES_DIR_REL;

    $dir_real = $CONTENT_DIR_REL;
    $die_dateien = "/dateien/";
    $MAX_IMG_WIDTH = $ADMIN_CONF->get("maximagewidth");
    $MAX_IMG_HEIGHT = $ADMIN_CONF->get("maximageheight");
    if($gallery !== false) {
        $dir_real = $GALLERIES_DIR_REL;
        $die_dateien = NULL;
        $gallery_conf = new Properties("$GALLERIES_DIR_REL/".$cat."/gallery.conf",true);
        $MAX_IMG_WIDTH = $gallery_conf->get("maxwidth");
        $MAX_IMG_HEIGHT = $gallery_conf->get("maxheight");

    }
    $uploadfile_name = $specialchars->replaceSpecialChars($uploadfile['name'],false);
    if (isset($uploadfile) and !$uploadfile['error']) {
        // nicht erlaubte Endung
        if (fileHasExtension($uploadfile_name, explode(",", $ADMIN_CONF->get("noupload")))) {
            return array("files_error_wrongext" => $uploadfile_name);
        }
        // ungueltige Zeichen im Dateinamen
        elseif(!preg_match($specialchars->getFileCharsRegex(), $uploadfile_name)) {
            return array("files_error_name" => $uploadfile_name);
        }
        // Datei vorhanden und "Ãœberschreiben"-Checkbox nicht aktiviert
        elseif (file_exists($dir_real.'/'.$cat.'/'.$die_dateien.$uploadfile_name) && ($forceoverwrite != "on")) {
            return array("files_error_exists" => $uploadfile_name);
        }
        // alles okay, hochladen!
        else {
            $savepath = $dir_real.'/'.$cat.'/'.$die_dateien.$uploadfile_name;
            move_uploaded_file($uploadfile['tmp_name'], $savepath);
            // chmod, wenn so eingestellt
            useChmod($savepath);
            if($ADMIN_CONF->get("resizeimages") == "true" or $gallery !== false) {
                // Bilddaten feststellen
                $size = getimagesize($savepath);
                // Mimetype herausfinden
                $image_typ = strtolower(str_replace('image/','',$size['mime']));
                # nur wenns ein bild ist
                if($image_typ == "gif" or $image_typ == "png" or $image_typ == "jpeg") {
                    require_once("../Image.php");
                    if($size[0] > $MAX_IMG_WIDTH or $size[1] > $MAX_IMG_HEIGHT) {
                        scaleImage($uploadfile_name, $dir_real.'/'.$cat.'/'.$die_dateien.'/', $dir_real.'/'.$cat.'/'.$die_dateien.'/', $MAX_IMG_WIDTH, $MAX_IMG_HEIGHT);
                    }
                }
            }
        return;
        }
    }
}


// Gibt eine Checkbox mit dem uebergebenen Namen zurueck. Der Parameter checked bestimmt, ob die Checkbox angehakt ist.
function buildCheckBox($name, $checked) {
    $checkbox = '<input type="checkbox" value="true" ';
    if ($checked == "true") {
        $checkbox .= 'checked=checked';
    }
    $checkbox .= ' name="'.$name.'">';
    return $checkbox;
}

// gibt zurueck, ob eine Checkbox angehakt ist
function checkBoxChecked($checkboxrequest) {
    if(isset($_POST[$checkboxrequest]) and ($_POST[$checkboxrequest] == "true")) {
        return "true";
    } else {
        return "false";
    }
}

// ------------------------------------------------------------------------------
// Hilfsfunktion: Sichert einen Input-Wert
// ------------------------------------------------------------------------------
    function cleanInput($input) {
        global $CHARSET;
        if (function_exists("mb_convert_encoding")) {
            $input = @mb_convert_encoding($input,$CHARSET,@mb_detect_encoding($input,"UTF-8,ISO-8859-1,ISO-8859-15",true));
        }
        return stripslashes($input);
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Prueft einen Requestparameter
// ------------------------------------------------------------------------------
    function getRequestParam($param, $clean) {
        if (isset($_POST[$param])) {
          // Nullbytes abfangen! "tmp" weil bei nur einem zeichen strpos fehlschlägt
            if (strpos("tmp".$_POST[$param], "\x00") > 0) {
                die();
            }
            if ($clean) {
                return cleanInput($_POST[$param]);
            } else {
                return $_POST[$param];
            }
        }
        // Parameter ist nicht im Request vorhanden
        else {
            return false;
        }
    }

?>