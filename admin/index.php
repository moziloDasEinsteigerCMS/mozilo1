<?php

/*
 *
 * $Revision$
 * $LastChangedDate$
 * $Author$
 *
 */

session_start();

$ADMIN_TITLE = "moziloAdmin";

#$CHARSET = 'ISO-8859-1';
$CHARSET = 'UTF-8';
$ADMIN_DIR_NAME = "admin";
$BASE_DIR = str_replace($ADMIN_DIR_NAME,"",getcwd());
$CMS_DIR_NAME = "cms";
$BASE_DIR_CMS = $BASE_DIR.$CMS_DIR_NAME."/";
$BASE_DIR_ADMIN = $BASE_DIR.$ADMIN_DIR_NAME."/";
$URL_BASE = substr($_SERVER['PHP_SELF'],0,-(strlen($ADMIN_DIR_NAME."/index.php")));


$debug = "nein"; # ja oder nein
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

if($debug == "ja") {
    ob_start();
    echo "SESSION -------------------\n";
    print_r($_SESSION);
    echo "POST -------------------\n";
    print_r($_POST);
    echo "FILES -------------------\n";
    print_r($_FILES);
    echo "REQUEST -------------------\n";
    print_r($_REQUEST);
    $debug_txt = ob_get_contents();
    ob_end_clean();
}
if(is_file($BASE_DIR_CMS."DefaultConf.php")) {
    require_once($BASE_DIR_CMS."DefaultConf.php");
} else {
    die("Fatal Error ".$BASE_DIR_CMS."DefaultConf.php Datei existiert nicht");
}
require_once($BASE_DIR_ADMIN."filesystem.php");
require_once($BASE_DIR_ADMIN."string.php");
require_once($BASE_DIR_CMS."Smileys.php");
require_once($BASE_DIR_CMS."Mail.php");
require_once($BASE_DIR_ADMIN."categories_array.php");

# Fatal Errors sofort beenden
if(!is_dir($BASE_DIR_ADMIN."conf")) {
    die("Fatal Error ".$BASE_DIR_ADMIN."conf Verzeichnis existiert nicht");
}
if(!is_dir($BASE_DIR_CMS."conf")) {
    die("Fatal Error ".$BASE_DIR_CMS."conf Verzeichnis existiert nicht");
}

$ADMIN_CONF    = new Properties($BASE_DIR_ADMIN."conf/basic.conf",true);
if(!isset($ADMIN_CONF->properties['readonly'])) {
    die($ADMIN_CONF->properties['error']);
}
$BASIC_LANGUAGE = new Properties($BASE_DIR_ADMIN."sprachen/language_".$ADMIN_CONF->get("language").".conf",true);
if(!isset($BASIC_LANGUAGE->properties['readonly'])) {
    die($BASIC_LANGUAGE->properties['error']);
}
# Errors nicht ganz so tragisch
if(!is_dir($BASE_DIR."kategorien")) {
    die(getLanguageValue("error_dir")." ".$BASE_DIR."kategorien/");
}
if(!is_dir($BASE_DIR."layouts")) {
    die(getLanguageValue("error_dir")." ".$BASE_DIR."layouts/");
}
if(!is_dir($BASE_DIR_CMS."sprachen")) {
    die(getLanguageValue("error_dir")." ".$BASE_DIR_CMS."sprachen/");
}
if(!is_dir($BASE_DIR_CMS."formular")) {
    die(getLanguageValue("error_dir")." ".$BASE_DIR_CMS."formular/");
}
if(!is_dir($BASE_DIR."galerien")) {
    die(getLanguageValue("error_dir")." ".$BASE_DIR."galerien/");
}

$CMS_CONF    = new Properties($BASE_DIR_CMS."conf/main.conf",true);
if(!isset($CMS_CONF->properties['readonly'])) {
    die($CMS_CONF->properties['error']);
}

$VERSION_CONF    = new Properties($BASE_DIR_CMS."conf/version.conf",true);
if(!isset($VERSION_CONF->properties['readonly'])) {
    die($VERSION_CONF->properties['error']);
}

$DOWNLOAD_COUNTS = new Properties($BASE_DIR_CMS."conf/downloads.conf",true);
if(!isset($DOWNLOAD_COUNTS->properties['readonly'])) {
    die($DOWNLOAD_COUNTS->properties['error']);
}
$GALLERY_CONF = new Properties($BASE_DIR_CMS."conf/gallery.conf",true);
if(!isset($GALLERY_CONF->properties['readonly'])) {
    die($GALLERY_CONF->properties['error']);
}

$LOGINCONF = new Properties($BASE_DIR_ADMIN."conf/logindata.conf",true);
# die muss schreiben geöffnet werden können
if(isset($LOGINCONF->properties['error'])) {
    die($LOGINCONF->properties['error']);
}

$PASSWORDS = new Properties($BASE_DIR_CMS."conf/passwords.conf",true);
# die muss schreiben geöffnet werden können
if(isset($PASSWORDS->properties['error'])) {
    die($PASSWORDS->properties['error']);
}
// Login ueberpruefen
if (!isset($_SESSION['login_okay']) or !$_SESSION['login_okay']) {
    header("location:login.php?logout=true");
    die("");
}


$MAILFUNCTIONS = new Mail();

$USER_SYNTAX_FILE = $BASE_DIR_CMS."conf/syntax.conf";
$USER_SYNTAX = new Properties($USER_SYNTAX_FILE,true);
if($CMS_CONF->properties['usecmssyntax'] == "true" and !isset($USER_SYNTAX->properties['readonly'])) {
    die($USER_SYNTAX->properties['error']);
}

$CONTACT_CONF = new Properties($BASE_DIR_CMS."formular/formular.conf",true);
if(!isset($CONTACT_CONF->properties['readonly'])) {
    die($CONTACT_CONF->properties['error']);
}

if(!is_file($BASE_DIR_CMS."formular/aufgaben.conf")) {
    $AUFGABEN_CONF = new Properties($BASE_DIR_CMS."formular/aufgaben.conf",true);
    if(!isset($AUFGABEN_CONF->properties['readonly'])) {
        die($AUFGABEN_CONF->properties['error']);
    }
    unset($AUFGABEN_CONF);
}

if(!is_file($BASE_DIR_CMS."conf/passwords.conf")) {
    $PASSWORDS_CONF = new Properties($BASE_DIR_CMS."conf/passwords.conf",true);
    if(!isset($PASSWORDS_CONF->properties['readonly'])) {
        die($PASSWORDS_CONF->properties['error']);
    }
    unset($PASSWORDS_CONF);
}

// Abwärtskompatibilität: Downloadcounter initalisieren
if ($DOWNLOAD_COUNTS->get("_downloadcounterstarttime") == "" and !isset($DOWNLOAD_COUNTS->properties['error']))
    $DOWNLOAD_COUNTS->set("_downloadcounterstarttime", time());

// Pfade
$CONTENT_DIR_NAME        = "kategorien";
$CONTENT_DIR_REL        = $BASE_DIR.$CONTENT_DIR_NAME."/";
$GALLERIES_DIR_NAME    = "galerien";
$GALLERIES_DIR_REL    = $BASE_DIR.$GALLERIES_DIR_NAME."/";
$PREVIEW_DIR_NAME        = "vorschau";
$PLUGIN_DIR_REL = $BASE_DIR."plugins/";

$ALLOWED_SPECIALCHARS_REGEX = $specialchars->getSpecialCharsRegex();

// Dateiendungen fuer Inhaltsseiten 
$EXT_PAGE     = ".txt";
$EXT_HIDDEN     = ".hid";
$EXT_DRAFT     = ".tmp";
$EXT_LINK     = ".lnk";

$icon_size = "24x24"; # 16x16 22x22 24x24 32x32 48x48
$icon_size_tabs = "16x16"; # 16x16 22x22 24x24 32x32 48x48

$post = NULL;
# getRequestParam() ferarbeitet nur $_POST sachen deshalb hier eine ausname
if(isset($_REQUEST['javascript']) and $_REQUEST['javascript'] == "ja") $_POST['javascript'] = "ja";
# hier das tabs array
$array_tabs = array("home","category","page","files","gallery","config","admin","plugins");
$action = 'home';
foreach($array_tabs as $pos => $tab) {
    # actives tab
    if(isset($_REQUEST['action_'.$pos])) {
        $action = $tab;
        $post['makepara'] = "yes";
        break;
    }
    # actives tab in dem grade gearbeitet wird
    if(isset($_POST['action_activ'])) {
        $action_html = $specialchars->rebuildSpecialChars($_POST['action_activ'], false, true);
        if($action_html == getLanguageValue($tab."_button")) {
            $action = $tab;
            break;
        }
    }
}

# IE Hack input image
if(isset($_POST['confirm_true']) or isset($_POST['confirm_true_x']))
    $_POST['confirm'] = "true";
if(isset($_POST['confirm_false']) or isset($_POST['confirm_false_x']))
    $_POST['confirm'] = "false";

# action_data parameter die mit cat page zu tun haben
if(isset($_POST['action_data'])) {
    # ist das Array für Kategorien oder Inhaltseiten nicht erzeugen
    $post['makepara'] = "no";
    if(isset($_POST['checkpara']) and $_POST['checkpara'] == "yes") {
        $post['categories'] = checkPostCatPageReturnVariable($CONTENT_DIR_REL);
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

# POST umsetzen nach $post bei config und admin
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
$html .= '<link type="text/css" rel="stylesheet" href="adminstyle.css">';
$html .= '<link type="text/css" rel="stylesheet" href="editsite.css">';
$html .= '<link type="text/css" rel="stylesheet" media="screen" href="js_color_picker_v2/js_color_picker_v2.css">';
$html .= '<script type="text/javascript" src="js_color_picker_v2/color_functions.js"></script>';
$html .= '<script type="text/javascript" src="js_color_picker_v2/js_color_picker_v2.js"></script>';
$html .= "</head>";
$html .= "<body>";
$html .= '<script type="text/javascript" src="wz_tooltip.js"></script>';


if($ADMIN_CONF->get("showTooltips") == "true") {
    $tooltip_help_logout_button = createTooltipWZ("help_logout_button","",",WIDTH,200,CLICKCLOSE,true");
    $tooltip_help_website_button = createTooltipWZ("help_website_button","",",WIDTH,200,CLICKCLOSE,true");
} else {
    $tooltip_help_logout_button = NULL;
    $tooltip_help_website_button = NULL;
}
$width_height = substr($icon_size,0,2);
$html .= '<table summary="" width="90%" cellspacing="0" border="0" cellpadding="0" id="table_admin">';
$html .= '<tr><td width="100%" id="td_title">';
$html .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" id="table_titel">';
$html .= '<tr><td id="td_table_titel_text">'.$ADMIN_TITLE.' - '.$pagetitle.'</td>';
$html .= '<td width="'.$width_height.'" height="'.$width_height.'"'.$tooltip_help_website_button.' nowrap><form class="form" accept-charset="'.$CHARSET.'" action="../index.php" method="post" target="_blank"><input class="input_img_button" type="image" value="'.getLanguageValue("website_button").'" src="gfx/icons/'.$icon_size.'/website.png" title="'.getLanguageValue("help_website_button",true).'"'.$tooltip_help_website_button.'></form></td>';
$html .= '<td width="'.$width_height.'" height="'.$width_height.'" id="td_table_titel_logout" nowrap><form class="form" accept-charset="'.$CHARSET.'" action="login.php" method="post"><input id="design_logout" class="input_img_button" type="image" name="logout" value="'.getLanguageValue("logout_button").'&nbsp;" accesskey="x" src="gfx/icons/'.$icon_size.'/logout.png" title="'.getLanguageValue("help_logout_button",true).'"'.$tooltip_help_logout_button.'></form></td></tr>';
$html .= '</table>';

$html .= "</td></tr>";
$html .= '<tr><td width="100%" id="td_tabs">';

$html .= '<table summary="" width="100%" id="table_buttons" cellspacing="0" cellpadding="0" border="0">';
$html .= '<tr>';

# Menue Tabs erzeugen
foreach($array_tabs as $position => $language) {
    if($ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip = createTooltipWZ($language."_button",$language."_text",",WIDTH,400");
    } else {
        $tooltip = NULL;
    }
    if($action == $language) $activ = "_activ"; else $activ = NULL;
    $html .= '<td align="left" valign="bottom" width="2%" class="td_button'.$activ.'" nowrap'.$tooltip.'>';
    $html .= '<a id="tab_'.$position.'" href="index.php?action_'.$position.'='.$language.'" title="'.getLanguageValue($language."_button",true).'"><img class="tab_img" src="gfx/icons/'.$icon_size_tabs.'/'.$language.'.png" alt="" hspace="0" vspace="0" border="0">'.getLanguageValue($language."_button").'</a>';
    $html .= '<script type="text/javascript">document.getElementById("tab_'.$position.'").href="index.php?action_'.$position.'='.$language.'&javascript=ja";</script>';
    $html .= '</td>';
# width="24" height="24"
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


if($LOGINCONF->get("initialpw") == "true" and $LOGINCONF->get("initialsetup") == "false") {
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

if(!isset($_REQUEST['link']) and $CMS_CONF->get('modrewrite') == "true") {
    $html .= returnMessage(false, getLanguageValue("error_no_modrewrite"));
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
            $html .= returnMessage(false, getLanguageValue("admin_messages_backup").'<br />Bitte bestätigen <input type="submit" name="lastbackup_yes" value="'.getLanguageValue("yes").'">');
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
 Zusätzliche Funktionen
 ------------------------------ */

function home($post) {
    global $CMS_CONF;
    global $VERSION_CONF;
    global $ADMIN_CONF;
    global $MAILFUNCTIONS;
    
    $pagecontent = NULL;
    $modrewrite = getLanguageValue("home_error_mod_rewrite");
    if(isset($_REQUEST['link']) and $_REQUEST['link'] == "rewrite") {
        $modrewrite = getLanguageValue("home_messages_mod_rewrite");
    }

    $safemode = getLanguageValue("no");
    if(ini_get('safe_mode')) {
        $post['error_messages']['home_error_safe_mode'][] = NULL;
        $safemode = '<span style="color:#ff0000;font-weight:bold;">'.getLanguageValue("yes").'</span>';
    }

    if(!extension_loaded("gd")) {
        $post['error_messages']['home_error_gd'][] = NULL;
        $gdlibinstalled = getLanguageValue("no");
    }
    else {
            $gdlibinstalled = getLanguageValue("yes");
    }

    $test_mail_adress = NULL;
/*    if($ADMIN_CONF->get("adminmail") != "") {
        $test_mail_adress = $ADMIN_CONF->get("adminmail");
    }*/
    if(getRequestParam('test_mail_adresse', true) != "") {
        $test_mail_adress = getRequestParam('test_mail_adresse', true);
    }
    // Testmail schicken
    if (getRequestParam('test_mail', true)) {
        if (getRequestParam('test_mail_adresse', true) and getRequestParam('test_mail_adresse', true) != "") {
            if($MAILFUNCTIONS->isMailAvailable()) {
                $MAILFUNCTIONS->sendMail(getLanguageValue("mailtest_mailsubject"), getLanguageValue("mailtest_mailcontent"),getRequestParam('test_mail_adresse', true),getRequestParam('test_mail_adresse', true));
                $post['messages']['home_messages_test_mail'][] = getRequestParam('test_mail_adresse', true);
            } else {
                $post['error_messages']['home_messages_no_mail'][] = NULL;
            }
        } else {
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
    $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">'
    // CMS-INFOS
    ."<tr>"
    .'<td width="100%" class="td_cms_titel" colspan="2"><b>'.getLanguageValue("cmsinfo").'</b></td>'
    ."</tr>"
    // Zeile "CMS-VERSION"
    ."<tr>"
    .'<td width="50%" class="td_cms_left">'.getLanguageValue("cmsversion_text")."</td>"
    .'<td width="50%" class="td_cms_left">'.$VERSION_CONF->get("cmsversion").' ("'.$VERSION_CONF->get("cmsname").'")<br />'.getLanguageValue("cmsrevision_text").' '.$VERSION_CONF->get("revision").'</td>'
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
    .'<td width="50%" class="td_cms_left">'.$modrewrite.'</td>'
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
    global $icon_size;
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
        $pagecontent .= $post['ask'];
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_category_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","category_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_category_help = '<a href="'.getHelpUrlForSubject("kategorien").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }

    $pagecontent .= '<span class="titel">'.getLanguageValue("category_button").'</span>';
    $pagecontent .= $tooltip_category_help;
    $pagecontent .= "<p>".getLanguageValue("category_text")."</p>";

    # Die getLanguageValue() und createTooltipWZ() erzeugen
    $array_getLanguageValue = array("pages","files","url","position","name","new_name",
        "url_adress","url_new_adress","url_adress_description","contents","category_button_change",
        "category_button_delete","target","toggle_show","toggle_hide");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }
    $title_category_button_delete = getLanguageValue('category_button_delete',true);

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

    $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        if(isset($post['displays']['cat']['error_html']['display'][$pos])) {
            $post['categories']['cat']['error_html']['display'][$pos] = $post['displays']['cat']['error_html']['display'][$pos];
        }
        if(!isset($display_new_cat)) {
            $pagecontent .= '<tr><td width="100%" class="td_toggle">';
            $pagecontent .= '<input type="submit" name="action_data[editcategory]" value="'.$text_category_button_change.'" class="input_submit">';
            $pagecontent .= '</td></tr>';
            # Neue Kategorie nicht Anzeigen wenn es schonn 100 Kategorien gibt
            if(count($post['categories']['cat']['position']) < $max_cat_page + 1) {
                $pagecontent .= '<tr><td width="100%" class="td_toggle_new">'
                    .'<table summary="" width="100%" class="table_new" border="0" cellspacing="0" cellpadding="0">'
                    .'<tr><td class="td_left_title"><b>'.$text_position.'</b></td>'
                    .'<td class="td_left_title"><b>'.$text_name.'</b>'
                    .'</td><td class="td_left_title"><b>'.$text_url_adress.'</b> '.$text_url_adress_description.'</td>'
                    .'<td class="td_center_title">&nbsp;</td>'
                    .'<td class="td_center_title">'.getLanguageValue("blank").'</td>'
                    .'<td class="td_center_title">'.getLanguageValue("self").'</td></tr>';
                $pagecontent .= '<tr>';
                $pagecontent .= '<td class="td_left_title"><input type="hidden" name="categories[cat][position]['.$max_cat_page.']" value="'.$post['categories']['cat']['position'][$max_cat_page].'">';
                $pagecontent .= '<input '.$post['categories']['cat']['error_html']['new_position'][$max_cat_page].'class="input_text" type="text" name="categories[cat][new_position]['.$max_cat_page.']" value="'.$post['categories']['cat']['new_position'][$max_cat_page].'" size="2" maxlength="2"'.$tooltip_category_help_new_position.'></td>';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$max_cat_page].' class="input_text" name="categories[cat][new_name]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$max_cat_page], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_category_help_new_name.'></td>';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_url'][$max_cat_page].' class="input_text" name="categories[cat][new_url]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_url'][$max_cat_page], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_new_url.'></td>';
                $pagecontent .= '<td class="td_center_title"'.$tooltip_help_target.'><b>'.$text_target.'</b></td>'
                .'<td class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$max_cat_page.']" value="-_blank-"'.$post['categories']['cat']['checked_blank'][$max_cat_page].$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$max_cat_page.']" value="-_self-"'.$post['categories']['cat']['checked_selv'][$max_cat_page].$tooltip_help_target_self.'></td>';
                $pagecontent .= '</tr>';
                $pagecontent .= '</table>';
                $pagecontent .= '</td></tr>';
            }
            $display_new_cat = true;
        }
        if($pos == $max_cat_page) {
            continue;
        }
        if (!isset($post['categories']['cat']['url'][$pos])) {
            $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
            // Anzahl Inhaltsseiten auslesen
            $pagescount = 0;
            if($pageshandle = opendir($CONTENT_DIR_REL.$file)) {
                while (($currentpage = readdir($pageshandle))) {
                    if(is_file($CONTENT_DIR_REL.$file."/".$currentpage))
                        $pagescount++;
                }
                closedir($pageshandle);
            }
            // Anzahl Dateien auslesen
            $filecount = 0;
            if($fileshandle = opendir($CONTENT_DIR_REL.$file."/dateien")) {
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

        $pagecontent .= '<table summary="" width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
       if(!isset($display_new_cat_name)) {
            $pagecontent .= '<tr><td width="8%" class="td_left_title"><b>'.$text_position.'</b></td><td class="td_left_title"><b>'.$text_name.'</b></td><td width="30%" class="td_left_title"><b>'.$text_contents.'</b></td><td width="15%" class="td_icons">&nbsp;</td></tr>';
            $display_new_cat_name = true;
        }
        $pagecontent .= '<tr><td width="8%" class="td_left_title"><input type="hidden" name="categories[cat][position]['.$pos.']" value="'.$post['categories']['cat']['position'][$pos].'">';
        $pagecontent .= '<input '.$post['categories']['cat']['error_html']['new_position'][$pos].'class="input_text" type="text" name="categories[cat][new_position]['.$pos.']" value="'.$post['categories']['cat']['new_position'][$pos].'" size="2" maxlength="2"'.$tooltip_category_help_position.'>';
        $pagecontent .= '</td><td class="td_left_title">';
        $pagecontent .= '<span '.$post['categories']['cat']['error_html']['name'][$pos].'class="text_cat_page">'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'</span>';
# ein Test das auch der Name Toggle bar ist
#        $pagecontent .= '<span onclick="cat_togglen(\'toggle_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\',\'true\');" '.$post['categories']['cat']['error_html']['name'][$pos].'class="text_cat_page">'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'</span>';
        $pagecontent .= '<input type="hidden" name="categories[cat][name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['name'][$pos], true, true).'"></td><td width="30%" class="td_left_title"><span class="text_info">'.$text.'</span></td><td width="15%" class="td_icons">';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$pos.'_linkBild"'.$tooltip_category_help_edit.'></span>';
        }
        $del_dir = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if(isset($post['categories']['cat']['url'][$pos])) {
            $del_dir = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos].$post['categories']['cat']['target'][$pos].$post['categories']['cat']['url'][$pos].$EXT_LINK;
        }
        $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletecategory]['.$del_dir.']" value="'.$text_category_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$title_category_button_delete.'"'.$tooltip_category_help_delete.'>';
        $pagecontent .= '</td></tr></table></td></tr>';
        $pagecontent .= '<tr>';
        $pagecontent .= '<td width="100%" '.$post['categories']['cat']['error_html']['display'][$pos].'id="toggle_'.$pos.'" align="right" class="td_togglen_padding_bottom">';
        if(isset($post['categories']['cat']['url'][$pos])) {
            $pagecontent .= '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
            $pagecontent .= '<tr><td width="30%" class="td_left_title" valign="bottom" nowrap><b>'.$text_new_name.'</b></td>';
            $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_adress.'</b></td>';
            $pagecontent .= '<td width="30%" class="td_left_title">';
            $pagecontent .= '<input type="text" class="input_readonly" name="categories[cat][url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['url'][$pos], true, true).'" maxlength="'.$max_strlen.'" readonly>';
            $pagecontent .= '</td><td>&nbsp;</td><td width="6%" valign="bottom" class="td_center_title">'.getLanguageValue("blank").'</td><td width="6%" align="center" valign="bottom" class="td_center_title">'.getLanguageValue("self").'</td><td>&nbsp;</td>';
            $pagecontent .= '</tr><tr>';
            $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$pos].' class="input_text" name="categories[cat][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$pos], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_category_help_name.'></td>';
            $pagecontent .= '<td width="9%" class="td_right_title" nowrap><b>'.$text_url_new_adress.'</b></td>';
            $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_url'][$pos].' class="input_text" name="categories[cat][new_url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_url'][$pos], true, true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_url.'></td>';
            $pagecontent .= '<td width="6%" class="td_center_title"><b'.$tooltip_help_target.'>'.$text_target.'</b></td><td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$pos.']" value="-_blank-"'.$post['categories']['cat']['checked_blank'][$pos].$tooltip_help_target_blank.'></td>';
            $pagecontent .= '<td width="6%" class="td_center_title"><input type="radio" name="categories[cat][new_target]['.$pos.']" value="-_self-"'.$post['categories']['cat']['checked_selv'][$pos].$tooltip_help_target_self.'><input type="hidden" name="categories[cat][target]['.$pos.']" value="'.$post['categories']['cat']['target'][$pos].'"></td>';
            $pagecontent .= '<td>&nbsp;</td></tr></table>';
        } else
            $pagecontent .= '<table summary="" width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0"><tr><td colspan="2" class="td_left_title" align="left" valign="bottom" ><b>'.$text_new_name.'</b></td></tr><tr><td width="30%" class="td_left_title"><input type="text" '.$post['categories']['cat']['error_html']['new_name'][$pos].'class="input_text" name="categories[cat][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories']['cat']['new_name'][$pos], true, true).'"'.$tooltip_category_help_name.'></td><td>&nbsp;</td></tr></table>';

        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\');</script>';
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
    global $PASSWORDS;
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
        foreach ($newname as $pos => $tmp) {
            @rename($CONTENT_DIR_REL.$orgname[$pos], $CONTENT_DIR_REL.$newname[$pos]);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
                $post['display']['cat']['error_html']['display'][$pos] = 'style="display:block;" ';
            } elseif(!is_dir($CONTENT_DIR_REL.$newname[$pos])) {
                $post['error_messages']['category_error_rename'][] = $orgname[$pos];
                $post['displays']['cat']['error_html']['display'][$pos] = 'style="display:block;" ';
            } else {
                if(!isset($post['categories']['cat']['url'][$pos])) {
                    // Referenzen auf die umbenannte Kategorie in der Download-Statistik ändern
                    renameCategoryInDownloadStats($orgname[$pos],$newname[$pos]);
                    // Referenzen auf die umbenannte Kategorie in allen Inhaltsseiten ändern
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
                # CatPage vom Password ändern
                if(is_array($PASSWORDS->properties)) {
                    foreach($PASSWORDS->properties as $catpage => $pass) {
                        $pas_cat = explode("/",$catpage);
                        if($pas_cat[0] == $orgname[$pos]) {
                            $tmp_pas = $PASSWORDS->get($catpage);
                            $PASSWORDS->delete($catpage);
                            $PASSWORDS->set($newname[$pos].'/'.$pas_cat[1],$tmp_pas);
                        }
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
    global $PASSWORDS;
    global $icon_size;

    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $del_cat = key($post['action_data']['deletecategory']);
        $post['ask'] = askMessages("category_ask_delete",$del_cat,'action_data[deletecategory]',$post['action_data']['deletecategory'],"del_cat",$del_cat);
        $post['makepara'] = "yes";
        return $post;
    }
    # Kategorie Löschen    
    if(isset($_POST['confirm']) and $_POST['confirm'] == "true" and isset($_POST['del_cat']) and !empty($_POST['del_cat'])) {
        $del_cat = $CONTENT_DIR_REL.$_POST['del_cat'];
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
            # CatPage vom Password ändern
            if(is_array($PASSWORDS->properties)) {
                foreach($PASSWORDS->properties as $catpage => $pass) {
                    $pas_cat = explode("/",$catpage);
                    if($pas_cat[0] == $_POST['del_cat']) {
                        $PASSWORDS->delete($catpage);
                    }
                }
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
    global $PASSWORDS;
    global $icon_size;

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
                $pagecontent .= '<span class="titel">'.getLanguageValue("page_edit").' -&gt; </span>'.$specialchars->rebuildSpecialChars(substr($cat,3), true, true).'/'.$specialchars->rebuildSpecialChars(substr($page,3,-(strlen($EXT_PAGE))), true, true).'<br /><br />';
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
    $pagecontent .= '<input type="hidden" name="checkpara" value="yes">';

    if(isset($post['makepara']) and $post['makepara'] == "yes") {
        $post['categories'] = makePostCatPageReturnVariable($CONTENT_DIR_REL,true);
        if(isset($post['categories']['error_messages'])) {
            $post['error_messages'] = $post['categories']['error_messages'];
            unset($post['categories']['error_messages']);
        }
    }

    if(isset($post['ask'])) {
        $pagecontent .= $post['ask'];
    }

    $pagecontent .= categoriesMessages($post);

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }
    $pagecontent .= '<span class="titel">'.getLanguageValue("page_button").'</span>';
    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_page_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","page_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_page_help = '<a href="'.getHelpUrlForSubject("inhaltsseiten").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }
    $pagecontent .= $tooltip_page_help;
    $pagecontent .= "<p>".getLanguageValue("page_text")."</p>";

    $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" cellpadding="0">';# border="0"
    $pagecontent .= '<tr><td width="100%" class="td_toggle"><input type="submit" name="action_data[copymovesite]" value="'.getLanguageValue("page_button_change").'" class="input_submit"></td></tr>';


    # Die getLanguageValue() und createTooltipWZ() erzeugen
    $array_getLanguageValue = array("pages","position","name","new_name","url_adress","url_new_adress","url_adress_description","page_button_edit","page_move","page_status","page_saveasdraft","page_saveasnormal","page_saveashidden","page_copy","page_button_edit","page_button_delete","page_password","page_password_del","target","toggle_show","toggle_hide");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }
    $title_page_button_edit = getLanguageValue('page_button_edit',true);
    $title_page_button_delete = getLanguageValue('page_button_delete',true);

    $array_getTooltipValue = array("help_new_url","help_target_blank","help_target_self","help_target","help_url",
        "page_help_edit","page_help_position","page_help_new_position","page_help_name","page_help_new_name",
        "page_help_move","page_help_editieren","page_help_edit_pages","page_help_delete","page_help_copy","page_help_password","page_help_password_del");

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
        $pagecontent .= '<table summary="" width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
        $pagecontent .= '<tr><td class="td_left_title">';
        $pagecontent .= '<span '.$post['categories'][$cat]['error_html']['cat_name'].'class="text_cat_page">'.$specialchars->rebuildSpecialChars(substr($cat,3), true, true).'</span><input type="hidden" name="categories['.$cat.']" value="'.$cat.'">';
        $pagescount = 0;
        if($pageshandle = opendir($CONTENT_DIR_REL.$cat."/")) {
            while (($currentpage = readdir($pageshandle))) {
                if(is_file($CONTENT_DIR_REL.$cat."/".$currentpage) and ctype_digit(substr($currentpage,0,2)))
                    $pagescount++;
            }
            closedir($pageshandle);
        }
        $text = '('.$pagescount.' '.$text_pages.')'; 
        $pagecontent .= '<td width="20%" class="td_left_title"><span class="text_info">'.$text.'</span></td>';
        $pagecontent .= '<td width="15%" class="td_icons">&nbsp;';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.substr($cat,0,2).'_linkBild"'.$tooltip_page_help_edit_pages.'></span>';
        }
        $pagecontent .= '</td></tr>';
        $pagecontent .= '</table>';
        $pagecontent .= '</td></tr>';
        $pagecontent .= '<tr><td width="100%" '.$post['categories'][$cat]['error_html']['display_cat'].'class="td_togglen" align="right" id="toggle_'.substr($cat,0,2).'">';# align="right"
        if(getRequestParam('javascript', true)) {

            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.substr($cat,0,2).'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\');</script>';
        }
        $pagecontent .= '<table summary="" width="98%" cellspacing="0" cellpadding="0" border="0"><tr><td width="100%">';

        $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" cellpadding="0" border="0">';
        foreach ($post['categories'][$cat]['position'] as $pos => $tmp) {
            if(isset($post['display'][$cat]['error_html']['display'][$pos])) {
                $post['categories'][$cat]['error_html']['display'][$pos] = $post['display'][$cat]['error_html']['display'][$pos];
            }
            # Neue Inhaltseite oder Link
            if(!isset($display_new_cat)) {
                $pagecontent .= '<tr><td width="100%" class="td_toggle_new">';
                $pagecontent .= '<table summary="" width="100%" class="table_data" border="0" cellspacing="0" cellpadding="0">';
                $pagecontent .= '<tr><td class="td_left_title"><b>'.$text_position.'</b></td>'
                .'<td class="td_left_title"><b>'.$text_name.'</b></td>'
                .'<td class="td_left_title"><b>'.$text_url_adress.'</b> '.$text_url_adress_description.'</td>'
                .'<td class="td_center_title">&nbsp;</td>'
                .'<td class="td_center_title">'.getLanguageValue("blank").'</td>'
                .'<td class="td_center_title">'.getLanguageValue("self").'</td></tr>';
                $pagecontent .= '<tr>';
                $pagecontent .= '<td width="6%" class="td_left_title"><input type="hidden" name="categories['.$cat.'][position]['.$max_cat_page.']" value="'.$post['categories'][$cat]['position'][$max_cat_page].'">';
                $pagecontent .= '<input '.$post['categories'][$cat]['error_html']['new_position'][$max_cat_page].'class="input_text" type="text" name="categories['.$cat.'][new_position]['.$max_cat_page.']" value="'.$post['categories'][$cat]['new_position'][$max_cat_page].'" size="2" maxlength="2"'.$tooltip_page_help_new_position.'></td>';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$max_cat_page].' class="input_text" name="categories['.$cat.'][new_name]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$max_cat_page],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_new_name.'></td>';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_url'][$max_cat_page].' class="input_text" name="categories['.$cat.'][new_url]['.$max_cat_page.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_url'][$max_cat_page],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_new_url.'></td>';
                $pagecontent .= '<td class="td_center_title"'.$tooltip_help_target.'><b>'.$text_target.'</b></td>'
                .'<td class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$max_cat_page.']" value="-_blank-"'.$post['categories'][$cat]['checked_blank'][$max_cat_page].''.$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$max_cat_page.']" value="-_self-"'.$post['categories'][$cat]['checked_selv'][$max_cat_page].''.$tooltip_help_target_self.'></td>';
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

            $pagecontent .= '<table summary="" width="100%" class="table_data" border="0" cellspacing="0" cellpadding="0">';
            if(!isset($display_new_cat_name)) {
                $pagecontent .= '<tr><td width="8%" class="td_left_title"><b>'.$text_position.'</b></td><td class="td_left_title"><b>'.$text_name.'</b></td><td width="12%" class="td_left_title"><b>'.$text_page_status.'</b></td><td width="17%" class="td_left_title"><b>'.$text_page_move.'</b></td><td width="12%" class="td_left_title">&nbsp;</td><td width="15%" class="td_icons">&nbsp;</td></tr>';
                $display_new_cat_name = true;
            }
            $pagecontent .= '<tr><td width="8%" class="td_left_title"><input '.$post['categories'][$cat]['error_html']['new_position'][$pos].'class="input_text" type="text" name="categories['.$cat.'][new_position]['.$pos.']" value="'.$post['categories'][$cat]['new_position'][$pos].'" size="2" maxlength="2"'.$tooltip_page_help_position.'><input type="hidden" name="categories['.$cat.'][position]['.$pos.']" value="'.$post['categories'][$cat]['position'][$pos].'"></td>';
            $pagecontent .= '<td class="td_left_title">';
            if($post['categories'][$cat]['ext'][$pos] == $EXT_PAGE) {
                $text = "(".$text_page_saveasnormal.")";
            } elseif($post['categories'][$cat]['ext'][$pos] == $EXT_DRAFT) {
                $text = "(".$text_page_saveasdraft.")";
            } elseif($post['categories'][$cat]['ext'][$pos] == $EXT_HIDDEN) {
                $text = "(".$text_page_saveashidden.")";
            } elseif(isset($post['categories'][$cat]['url'][$pos])) {
                $text = "(".$text_target." ".substr($post['categories'][$cat]['target'][$pos],2,-1).")";
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
                $pagecontent .= '<input type="image" class="input_img_button" name="action_data[editsite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_edit.'"  src="gfx/icons/'.$icon_size.'/page-edit.png" title="'.$title_page_button_edit.'"'.$tooltip_page_help_editieren.'>';
                $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletesite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$title_page_button_delete.'"'.$tooltip_page_help_delete.'>';
            } else {#'.substr($icon_size,0,2).'
                $pagecontent .= '<IMG class="input_img_button" src="gfx/clear.gif" alt=" " width="'.substr($icon_size,0,2).'" height="1" hspace="0" vspace="0" border="0"><input type="image" class="input_img_button_last" name="action_data[deletesite]['.$cat.']['.$post['categories'][$cat]['position'][$pos].'_'.$post['categories'][$cat]['name'][$pos].$post['categories'][$cat]['target'][$pos].$post['categories'][$cat]['url'][$pos].$post['categories'][$cat]['ext'][$pos].']" value="'.$text_page_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$title_page_button_delete.'"'.$tooltip_page_help_delete.'>';
            }
            $pagecontent .= '</td></tr></table>';
            $pagecontent .= '</td></tr>';
            $pagecontent .= '<tr>';
            $pagecontent .= '<td width="100%" class="td_togglen_padding_bottom" align="right" '.$post['categories'][$cat]['error_html']['display'][$pos].' id="toggle_'.substr($cat,0,2).'_'.$pos.'">';

            if(isset($post['categories'][$cat]['url'][$pos])) {
                $pagecontent .= '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">'
                .'<tr><td width="30%" valign="bottom" nowrap class="td_left_title"><b>'.$text_new_name.'</b></td>';
                $pagecontent .= '<td class="td_right_title" nowrap><b>'.$text_url_adress.'</b></td>';
                $pagecontent .= '<td class="td_left_title">';
                $pagecontent .= '<input type="text" class="input_readonly" name="categories['.$cat.'][url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['url'][$pos],true,true).'" maxlength="'.$max_strlen.'" readonly>';
                $pagecontent .= '</td>'
                .'<td class="td_center_title">&nbsp;</td>'
                .'<td valign="bottom" class="td_center_title">'.getLanguageValue("blank").'</td>'
                .'<td valign="bottom" class="td_center_title">'.getLanguageValue("self").'</td>';
                $pagecontent .= '</tr><tr>';
                $pagecontent .= '';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$pos].' class="input_text" name="categories['.$cat.'][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_name.'></td>';
                $pagecontent .= '<td class="td_right_title" nowrap><b>'.$text_url_new_adress.'</b></td>';
                $pagecontent .= '<td class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['new_url'][$pos].' class="input_text" name="categories['.$cat.'][new_url]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_url'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_help_url.'></td>';
                $pagecontent .= '<td class="td_center_title"'.$tooltip_help_target.'><b>'.$text_target.'</b></td>'
                .'<td width="6%" class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$pos.']" value="-_blank-"'.$post['categories'][$cat]['checked_blank'][$pos].''.$tooltip_help_target_blank.'></td>';
                $pagecontent .= '<td class="td_center_title"><input type="radio" name="categories['.$cat.'][new_target]['.$pos.']" value="-_self-"'.$post['categories'][$cat]['checked_selv'][$pos].''.$tooltip_help_target_self.'><input type="hidden" name="categories['.$cat.'][target]['.$pos.']" value="'.$post['categories'][$cat]['target'][$pos].'"></td>';
                $pagecontent .= '</tr></table>';
            } else {
                $pagecontent .= '<table summary="" width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0"><tr>'
                .'<td width="30%" class="td_left_title" nowrap><b>'.$text_new_name.'</b></td><td width="6%" class="td_left_title">&nbsp;</td>'.
                '<td width="30%" class="td_left_title"><b>'.$text_page_password.'</b></td>'
                .'<td class="td_left_title">&nbsp;</td></tr>';
                $pagecontent .= '<tr><td class="td_left_title" nowrap><input type="text" '.$post['categories'][$cat]['error_html']['new_name'][$pos].' class="input_text" name="categories['.$cat.'][new_name]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['new_name'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_name.'></td>'
                .'<td>&nbsp;</td>'
                .'<td class="td_left_title"><input type="text" '.$post['categories'][$cat]['error_html']['password'][$pos].' class="input_text" name="categories['.$cat.'][password]['.$pos.']" value="'.$specialchars->rebuildSpecialChars($post['categories'][$cat]['password'][$pos],true,true).'" maxlength="'.$max_strlen.'"'.$tooltip_page_help_password.'></td>'
                .'<td class="td_left_title">';
                if($PASSWORDS->get($cat."/".$post['categories'][$cat]['position'][$pos]."_".$post['categories'][$cat]['name'][$pos])) {
                    $pagecontent .= '<b>'.$text_page_password_del.'</b>&nbsp;<input class="input_check" type="checkbox" name="categories['.$cat.'][password_del]['.$pos.']" value="yes"'.$tooltip_page_help_password_del.'>';
                } else {
                    $pagecontent .= '&nbsp;';
                }
                $pagecontent .= '</td>';
                $pagecontent .= '</tr></table>';
            }

            if(getRequestParam('javascript', true)) {
                $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.substr($cat,0,2).'_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\');</script>';
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

    $post['editsite'] = 'yes';
    $content = "editsite";
    if(isset($_POST['save']) or isset($_POST['savetemp'])) {
        $new_ext = $_POST['saveas'];
        $new_page = substr($post['action_data']['editsite'][$cat],0,-(strlen($EXT_PAGE))).$new_ext;
        if($new_page != $post['action_data']['editsite'][$cat]) {
            @rename($CONTENT_DIR_REL.$cat."/".$page,$CONTENT_DIR_REL.$cat."/".$new_page);
            $line_error = __LINE__ - 1;
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(!is_file($CONTENT_DIR_REL.$cat."/".$new_page)) {
                $post['error_messages']['page_message_rename'][] = $page." <b>></b> ".$new_page;
            } else {
                # ext hat sich geändert
                $page = $new_page;
            }
        }
        $error = saveContentToPage($_POST['pagecontent'],$CONTENT_DIR_REL.$cat."/".$page);
        if(is_array($error)) {
            if(isset($post['error_messages'])) {
                $post['error_messages'] = array_merge_recursive($post['error_messages'],$error);
            } else {
                $post['error_messages'] = $error;
            }
            $content = $_POST['pagecontent'];
        }
        if(!isset($post['error_messages']))
            $post['messages']['page_message_edit'][] = NULL;
        if(isset($_POST['savetemp']) or isset($post['error_messages'])) {
            $post['content'] = showEditPageForm($cat, $page,$content);
        } else {
            $post['editsite'] = 'no';
        }
    } elseif(isset($_POST['cancel'])) {
        $post['editsite'] = 'no';
    } else {
        $post['content'] = showEditPageForm($cat, $page,$content);
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
    global $PASSWORDS;
    global $BASE_DIR_ADMIN;
    $max_cat_page = 100;

    require_once($BASE_DIR_ADMIN."Crypt.php");
    $pwcrypt = new Crypt();
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
# kommentiert weil Inhaltseite in Lehre copy move nach cat nicht ging
//         if(count($post['categories'][$cat]['position']) <= 1 and empty($post['categories'][$cat]['new_position'][$max_cat_page])) {
//             continue;
//         }

        # rename bauen wenn die position sich nicht geändert hat aber was anderes
        foreach($post['categories'][$cat]['position'] as $pos => $tmp) {
            # Neue Inhaltseite nicht hier
            if($pos == $max_cat_page) {
                continue;
            }
            # Inhaltseiten Paswort Schutz sachen
            $tmp_page = $post['categories'][$cat]['position'][$pos]."_".$post['categories'][$cat]['name'][$pos];
            if($PASSWORDS->get($cat."/".$tmp_page)
                    and !empty($post['categories'][$cat]['password_del'][$pos]))
            {
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                $post['messages']['page_message_del_password'][] = $tmp_page;
                $PASSWORDS->delete($cat."/".$tmp_page);
            }
            if(!$PASSWORDS->get($cat."/".$tmp_page)
                    and !empty($post['categories'][$cat]['password'][$pos])
                    and empty($post['categories'][$cat]['password_del'][$pos]))
            {
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                $post['display'][$cat]['error_html']['display'][$pos] = 'style="display:block;" ';
                $post['messages']['page_message_new_password'][] = $tmp_page;
                $PASSWORDS->set($cat."/".$tmp_page,$pwcrypt->encrypt($post['categories'][$cat]['password'][$pos]));
                $post['categories'][$cat]['password'][$pos] = NULL;
            } elseif($PASSWORDS->get($cat."/".$tmp_page) != $pwcrypt->encrypt($post['categories'][$cat]['password'][$pos])
                    and !empty($post['categories'][$cat]['password'][$pos])
                    and empty($post['categories'][$cat]['password_del'][$pos]))
            {
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                $post['display'][$cat]['error_html']['display'][$pos] = 'style="display:block;" ';
                $post['messages']['page_message_change_password'][] = $tmp_page;
                $PASSWORDS->set($cat."/".$tmp_page,$pwcrypt->encrypt($post['categories'][$cat]['password'][$pos]));
                $post['categories'][$cat]['password'][$pos] = NULL;
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
                @rename($CONTENT_DIR_REL.$cat."/".$rename_orgname[$cat][$z],$CONTENT_DIR_REL.$cat."/".$rename_newname[$cat][$z]);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error['line'] = NULL;
                if(function_exists("error_get_last")) {
                    $last_error = @error_get_last();
                }
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                    if(substr($rename_orgname[$cat][$z],3) != substr($rename_newname[$cat][$z],3)) {
                        $post['display'][$cat]['error_html']['display'][sprintf("%1d",substr($rename_orgname[$cat][$z],0,2))] = 'style="display:block;" '; # letzte cat ausklappen
                    }
                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
                    $post['makepara'] = "no"; # kein makePostCatPageReturnVariable()
                } elseif(!is_file($CONTENT_DIR_REL.$cat."/".$rename_newname[$cat][$z])) {
                    $post['error_messages']['page_error_rename'][] =  $cat."/".$rename_orgname[$cat][$z]." <b>></b> ".$cat."/".$rename_newname[$cat][$z];
                   if(substr($rename_orgname[$cat][$z],3) != substr($rename_newname[$cat][$z],3)) {
                        $post['display'][$cat]['error_html']['display'][sprintf("%1d",substr($rename_orgname[$cat][$z],0,2))] = 'style="display:block;" '; # letzte cat ausklappen
                    }
                    $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" '; # letzte cat ausklappen
                    $post['makepara'] = "no"; # kein makePostCatPageReturnVariable()
                # Alles gut
                } else {
                    if($PASSWORDS->get($cat."/".substr($rename_orgname[$cat][$z],0,-(strlen($EXT_LINK))))) {
                        $tmp_pas = $PASSWORDS->get($cat."/".substr($rename_orgname[$cat][$z],0,-(strlen($EXT_LINK))));
                        $PASSWORDS->delete($cat."/".substr($rename_orgname[$cat][$z],0,-(strlen($EXT_LINK))));
                        $PASSWORDS->set($cat."/".substr($rename_newname[$cat][$z],0,-(strlen($EXT_LINK))),$tmp_pas);
                    }
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
                @copy($CONTENT_DIR_REL.$move_orgname[$cat][$z],$CONTENT_DIR_REL.$move_newname[$cat][$z]);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error['line'] = NULL;
                if(function_exists("error_get_last")) {
                    $last_error = @error_get_last();
                }
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                    $error = true;
                } elseif(!is_file($CONTENT_DIR_REL.$move_newname[$cat][$z])) {
                    $post['error_messages']['page_error_copy'][] = $move_orgname[$cat][$z]." <b>></b> ".$move_newname[$cat][$z];
                    $error = true;
                }
                # wenn Verschoben werden soll und copy erfolgreich war
                if(isset($move_move[$cat][$z]) and is_file($CONTENT_DIR_REL.$move_newname[$cat][$z])) {
                    @unlink($CONTENT_DIR_REL.$move_orgname[$cat][$z]);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error['line'] = NULL;
                    if(function_exists("error_get_last")) {
                        $last_error = @error_get_last();
                    }
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                        $error = true;
                    } elseif(is_file($CONTENT_DIR_REL.$move_orgname[$cat][$z])) {
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
                    $post['display'][dirname($move_orgname[$cat][$z])]['error_html']['display_cat'] = 'style="display:block;" ';
                    unset($error);
                } else {
                    if($PASSWORDS->get(substr($move_orgname[$cat][$z],0,-(strlen($EXT_LINK))))) {
                        $tmp_pas = $PASSWORDS->get(substr($move_orgname[$cat][$z],0,-(strlen($EXT_LINK))));
                        $PASSWORDS->delete(substr($move_orgname[$cat][$z],0,-(strlen($EXT_LINK))));
                        $PASSWORDS->set(substr($move_newname[$cat][$z],0,-(strlen($EXT_LINK))),$tmp_pas);
                    }
                    # Erfogs meldungen
                    $post['messages']['page_message_copy_move'][] = $move_orgname[$cat][$z]." <b>></b> ".$move_newname[$cat][$z];
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
            $error = saveContentToPage($page_inhalt,$CONTENT_DIR_REL.$cat."/".$new_page[$cat]);
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
    global $EXT_LINK;
    global $PASSWORDS;
    global $icon_size;

    $cat = key($post['action_data']['deletesite']);
    $del_page = $cat."/".$post['action_data']['deletesite'][$cat];
    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $post['ask'] = askMessages("page_ask_delete",$post['action_data']['deletesite'][$cat],'action_data[deletesite]['.$cat.']',$post['action_data']['deletesite'][$cat]);
        $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
        $post['makepara'] = "yes";
        return $post;
    }
    # Seite Löschen    
    if(isset($_POST['confirm'])) {
        if($_POST['confirm'] == "true") {
            @unlink($CONTENT_DIR_REL.$del_page);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(file_exists($CONTENT_DIR_REL.$del_page)) {
                $post['error_messages']['page_error_delete'][] = $del_cat;
                $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                return $post;
            } else {
                $post['messages']['page_message_delete'][] = $post['action_data']['deletesite'][$cat];
                $post['makepara'] = "yes"; # makePostCatPageReturnVariable()
                $post['display'][$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                if($PASSWORDS->get(substr($del_page,0,-(strlen($EXT_LINK))))) {
                    $PASSWORDS->delete(substr($del_page,0,-(strlen($EXT_LINK))));
                }
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
    global $GALLERY_CONF;
    global $icon_size;
    global $BASE_DIR_CMS;
    global $URL_BASE;
    global $GALLERIES_DIR_NAME;

    $max_strlen = 255;

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("gallery_button").'">';

    $gallery = makeDefaultConf("gallery");

    if(isset($_POST['gallery']) and is_array($_POST['gallery'])) {
        $post['gallery'] = $_POST['gallery'];
    }

    # error colors mit null fühlen
    foreach($gallery as $type => $type_array) {
        if($type == 'expert') continue;
        foreach($gallery[$type] as $syntax_name => $dumy) {
            $post['gallery']['error_html'][$syntax_name] = NULL;
        }
    }

    if(isset($post['action_data']) and !isset($post['error_messages'])) {
        $action_data_key = key($post['action_data']);
        if($action_data_key == "deletegalleryimg") {
            $post = deleteGalleryImg($post);
        }
        if($action_data_key == "deletegallery") {
            $post = deleteGallery($post);
        }
        $post = editGallery($post);
    }
    if(isset($_FILES)) {
        $post = newGalleryImg($post);
    }
    if(isset($_POST['new_gallery']) and strlen($_POST['new_gallery']) > 0) {
        $post = newGallery($post);
    }

    $dircontent = getDirs($GALLERIES_DIR_REL, true);
    # Galerien array bilden mit den dazugehörigen Bilder
    # wenn die Galerie oder die Bilder mit FTP Hochgeladen wurden Automatisch umbennen
    foreach ($dircontent as $pos => $currentgalerien) {
        $test_galerie = $specialchars->replaceSpecialChars($specialchars->rebuildSpecialChars($currentgalerien, false, false),false);
        if(!file_exists($GALLERIES_DIR_REL.$currentgalerien."/texte.conf")) {
            touch($GALLERIES_DIR_REL.$currentgalerien."/texte.conf");
            $error = changeChmod($GALLERIES_DIR_REL.$currentgalerien."/texte.conf");
            if(is_array($error)) {
                $post['error_messages'][key($error)][] = $error[key($error)];
            }
        }
        if($test_galerie != $currentgalerien) {
            if(in_array($test_galerie,$dircontent)) {
                $post['error_messages']['gallery_error_ftp_rename_exist'][] = $test_galerie;
                # die Galerie aus dem array nehmen der fehler muss mit ftp behoben werden
                unset($dircontent[$pos]);
                continue;
            } else {
                @rename($GALLERIES_DIR_REL.$currentgalerien,$GALLERIES_DIR_REL.$test_galerie);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error['line'] = NULL;
                if(function_exists("error_get_last")) {
                    $last_error = @error_get_last();
                }
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                    # die Galerie aus dem array nehmen der fehler muss mit ftp behoben werden
                    unset($dircontent[$pos]);
                    continue;
                } elseif(!is_dir($GALLERIES_DIR_REL.$test_galerie)) {
                    $post['error_messages']['gallery_error_ftp_rename'][] = $test_galerie;
                    # die Galerie aus dem array nehmen der fehler muss mit ftp behoben werden
                    unset($dircontent[$pos]);
                    continue;
                } else {
                    $post['messages']['gallery_message_ftp_rename'][] =  $test_galerie;
                    $currentgalerien = $test_galerie;
                    $dircontent[$pos] = $test_galerie;
                }
            }
        }
/*
        $error = changeChmod($GALLERIES_DIR_REL.$currentgalerien);
        if(is_array($error)) {
            $post['error_messages'][key($error)][] = $error[key($error)];
        }*/

        if(!is_dir($GALLERIES_DIR_REL.$currentgalerien.'/'.$PREVIEW_DIR_NAME)) {
            @mkdir($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(!is_dir($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME)) {
                $post['error_messages']['gallery_error_new_preview'][] = $currentgalerien;
            } else {
                $post['messages']['gallery_message_ftp_preview'][] =  $test_galerie;
            }
        }
/*
        if(is_dir($GALLERIES_DIR_REL.$currentgalerien.'/'.$PREVIEW_DIR_NAME)) {
            $error = changeChmod($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME);
            if(is_array($error)) {
                $post['error_messages'][key($error)][] = $error[key($error)];
            }
        }*/
        $gallerypics[$currentgalerien] = getFiles($GALLERIES_DIR_REL.$currentgalerien,"");
        $count_preview_pic = 0;
        foreach($gallerypics[$currentgalerien] as $pos => $file) {
            # nur Bilder zulassen
            if(is_dir($file) or count(@getimagesize($GALLERIES_DIR_REL.$currentgalerien.'/'.$file)) < 2) {
                unset($gallerypics[$currentgalerien][$pos]);
                continue;
            }
/*
            $error = changeChmod($GALLERIES_DIR_REL.$currentgalerien."/".$file);
            if(is_array($error)) {
                $post['error_messages'][key($error)][] = $error[key($error)];
            }*/
            if(is_file($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file)) {
/*
                $error = changeChmod($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file);
                if(is_array($error)) {
                    $post['error_messages'][key($error)][] = $error[key($error)];
                }*/
                $count_preview_pic++;
            }
            $test_pic = $specialchars->replaceSpecialChars($specialchars->rebuildSpecialChars($file, false, false),false);
            if($test_pic != $file) {
                if(in_array($test_pic,$gallerypics[$currentgalerien])) {
                    $post['error_messages']['gallery_error_ftp_rename_pic_exist'][] = $currentgalerien." - ".$test_pic;
                    # das Bild aus dem array nehmen der fehler muss mit ftp behoben werden
                    unset($gallerypics[$currentgalerien][$pos]);
                } else {
                    @rename($GALLERIES_DIR_REL.$currentgalerien."/".$file,$GALLERIES_DIR_REL.$currentgalerien."/".$test_pic);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error['line'] = NULL;
                    if(function_exists("error_get_last")) {
                        $last_error = @error_get_last();
                    }
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                        # das Bild aus dem array nehmen der fehler muss mit ftp behoben werden
                        unset($gallerypics[$currentgalerien][$pos]);
                    } elseif(!is_file($GALLERIES_DIR_REL.$currentgalerien."/".$test_pic)) {
                        $post['error_messages']['gallery_error_ftp_rename_pic'][] = $currentgalerien." - ".$test_pic;
                        # das Bild aus dem array nehmen der fehler muss mit ftp behoben werden
                        unset($gallerypics[$currentgalerien][$pos]);
                    } else {
                        if(is_file($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file)) {
                            @rename($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file,$GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$test_pic);
                            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                            $last_error['line'] = NULL;
                            if(function_exists("error_get_last")) {
                                $last_error = @error_get_last();
                            }
                            if($last_error['line'] == $line_error) {
                                $post['error_messages']['php_error'][] = $last_error['message'];
                            } elseif(!is_file($GALLERIES_DIR_REL.$currentgalerien."/".$test_pic)) {
                                $post['error_messages']['gallery_error_ftp_rename_pic'][] = $PREVIEW_DIR_NAME."/".$currentgalerien." - ".$test_pic;
                            }
                        }
#                        if($GALLERY_CONF->get("usethumbs") == "true" and !is_file($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$test_pic)) {
                        if(!is_file($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$test_pic)) {
                            require_once($BASE_DIR_CMS."Image.php");
                            scaleImage($test_pic, $GALLERIES_DIR_REL.$currentgalerien.'/', $GALLERIES_DIR_REL.$currentgalerien.'/'.$PREVIEW_DIR_NAME.'/', $GALLERY_CONF->get('maxthumbwidth'), $GALLERY_CONF->get('maxthumbheight'));
                            if(is_file($GALLERIES_DIR_REL.$currentgalerien."/".$PREVIEW_DIR_NAME."/".$test_pic)) {
                                $post['messages']['gallery_message_ftp_make_thumb'][] =  $currentgalerien." - ".$test_pic;
                                $count_preview_pic++;
                            }
                        }
                        $post['messages']['gallery_message_ftp_rename_pic'][] =  $currentgalerien." - ".$test_pic;
                        $gallerypics[$currentgalerien][$pos] = $test_pic;
                    }
                }
            }
        }
        # hinweis wenn nicht alle vorschau bilder existieren
#        if($GALLERY_CONF->get("usethumbs") == "true" and count($gallerypics[$currentgalerien]) != $count_preview_pic) {
        if(count($gallerypics[$currentgalerien]) != $count_preview_pic) {
            $post['error_messages']['gallery_error_ftp_preview_pic'][] = $currentgalerien;
        }
        sort($gallerypics[$currentgalerien]);
    }

    if($ADMIN_CONF->get('showexpert') == "false") {
        foreach($gallery['expert'] as $syntax_name) {
            $post['gallery']['setings'][$syntax_name] = $GALLERY_CONF->get($syntax_name);
        }
    }

    $pagecontent .= categoriesMessages($post);

    if(isset($post['ask'])) {
        $pagecontent .= $post['ask'];
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_gallery_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","gallery_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_gallery_help = '<a href="'.getHelpUrlForSubject("galerien").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }

    $pagecontent .= '<span class="titel">'.getLanguageValue("gallery_button").'</span>';
    $pagecontent .= $tooltip_gallery_help;
    $pagecontent .= "<p>".getLanguageValue("gallery_text")."</p>";

    $array_getLanguageValue = array("gallery_scale","gallery_scale_thumbs",
        "gallery_picsperrow","gallery_usethumbs","gallery_target","gallery_scaleimages",
        "gallery_rebuildthumbs","gallery_size","gallery_subtitle","gallery_button_cut","gallery_newname",
        "gallery_button_img_delete","gallery_button_gallery_delete","target","toggle_show","toggle_hide","gallery_no_preview");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }

    $array_getTooltipValue = array("help_target_blank","help_target_self","help_target","gallery_help_scale","gallery_help_scale_thumbs","gallery_help_input_scale",
        "gallery_help_picsperrow","gallery_help_input_picsperrow","gallery_help_use_thumbs","gallery_help_all_picture_scale",
        "gallery_help_all_thumbs_new","gallery_help_size","gallery_help_picture","gallery_help_subtitle","gallery_help_name",
        "gallery_help_del","gallery_help_target","gallery_help_conf","gallery_help_edit","gallery_help_newname","gallery_help_usedfgallery","gallery_help_overwrite");


    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    if(!isset($post['gallery']['error_html']['display_setings'])) {
        $post['gallery']['error_html']['display_setings'] = NULL;
    }

    $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    $overwrite = NULL;
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';

    $pagecontent .= '<tr><td width="85%" class="td_left_title"><input type="submit" name="action_data[gallery]" value="'.getLanguageValue("gallery_button_change").'" class="input_submit">';
    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_gallery_help_overwrite.$overwrite.'>&nbsp;<span'.$tooltip_gallery_help_overwrite.'><b>'.getLanguageValue("files_button_overwrite").'</b></span></td>';

    $pagecontent .= '<td width="15%" class="td_icons">';
    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<span id="toggle_settings_conf_linkBild"'.$tooltip_gallery_help_conf.'></span>';
    }
    $pagecontent .= '</td></tr></table>';
    $pagecontent .= '</td></tr>';
    $pagecontent .= '<tr><td width="100%" align="right" class="td_toggle" id="toggle_settings_conf"'.$post['gallery']['error_html']['display_setings'].'>';
    # setings start
    $pagecontent .= '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data"><tr>';
    $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_scale.'><b>'.$text_gallery_scale.'</b></td>';
    $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery[setings][maxwidth]" value="'.$GALLERY_CONF->get("maxwidth").'"'.$post['gallery']['error_html']['maxwidth'].$tooltip_gallery_help_input_scale.' />&nbsp;x&nbsp;<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery[setings][maxheight]" value="'.$GALLERY_CONF->get("maxheight").'"'.$post['gallery']['error_html']['maxheight'].$tooltip_gallery_help_input_scale.' />&nbsp;'.getLanguageValue("pixels").'</td>';

    $checket_self = NULL;
    $checket_blank = ' checked="checked"';
    if($GALLERY_CONF->get("target") == "_self") {
        $checket_self = ' checked="checked"';
        $checket_blank = NULL;
    }

    $pagecontent .= '</tr><tr>';
    $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_target.'><b>'.$text_gallery_target.'</b></td>';
    $pagecontent .= '<td width="20%" class="td_left_title_padding_bottom">
    <table summary="" width="100%" cellspacing="0" border="0" cellpadding="0">
    <tr>
    <td width="33%">&nbsp;</td>
    <td width="33%" class="td_center_title">&nbsp;&nbsp;'.getLanguageValue("blank").'&nbsp;&nbsp;</td>
    <td width="33%" class="td_center_title">&nbsp;&nbsp;'.getLanguageValue("self").'&nbsp;&nbsp;</td>
    </tr><tr>
    <td class="td_center_title"><b'.$tooltip_help_target.'>'.$text_target.'</b></td>
    <td class="td_center_title"><input type="radio" name="gallery[setings][target]" value="_blank"'.$tooltip_help_target_blank.$checket_blank.'></td>
    <td class="td_center_title"><input type="radio" name="gallery[setings][target]" value="_self"'.$tooltip_help_target_self.$checket_self.'></td>
    </tr>
    </table></td>';
    if($ADMIN_CONF->get('showexpert') == "true") {
        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_use_thumbs.'><b>'.$text_gallery_usethumbs.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"'.$tooltip_gallery_help_use_thumbs.'>'.buildCheckBox("gallery[setings][usethumbs]", $GALLERY_CONF->get("usethumbs")).'</td>';

        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_scale_thumbs.'><b>'.$text_gallery_scale_thumbs.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery[setings][maxthumbwidth]" value="'.$GALLERY_CONF->get("maxthumbwidth").'"'.$post['gallery']['error_html']['maxthumbwidth'].$tooltip_gallery_help_input_scale.' />&nbsp;x&nbsp;<input type="text" class="input_cms_zahl" size="4" maxlength="4" name="gallery[setings][maxthumbheight]" value="'.$GALLERY_CONF->get("maxthumbheight").'"'.$post['gallery']['error_html']['maxthumbheight'].$tooltip_gallery_help_input_scale.' />&nbsp;'.getLanguageValue("pixels").'</td>';


        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td width="35%" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_picsperrow.'><b>'.$text_gallery_picsperrow.'</b></td>';
        $pagecontent .= '<td width="20%" class="td_togglen_padding_bottom"><input type="text" class="input_cms_zahl" size="4" maxlength="2" name="gallery[setings][gallerypicsperrow]" value="'.$GALLERY_CONF->get("gallerypicsperrow").'"'.$post['gallery']['error_html']['gallerypicsperrow'].$tooltip_gallery_help_input_picsperrow.' /></td>';
    }
    $pagecontent .= '</tr>';
    $pagecontent .= '</table>';

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_settings_conf\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\');</script>';
    }
    $pagecontent .= '</td></tr>';
    # setings end

    $pagecontent .= '<tr><td width="100%" class="td_toggle_new">';
    # Neue GAllery
    $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
    $pagecontent .= '<tr><td width="30%" class="td_left_title"><b>'.getLanguageValue("gallery_new").'</b></td>';
    $pagecontent .= '<td width="70%" class="td_left_title">&nbsp;</td>';
    $pagecontent .= '</tr><tr>';
    $pagecontent .= '';
    $pagecontent .= '<td width="30%" class="td_left_title"><input type="text" class="input_text" name="new_gallery" value="" maxlength="'.$max_strlen.'"></td>';
    $pagecontent .= '<td width="70%" class="td_left_title">&nbsp;</td>';
    $pagecontent .= '</tr></table>';
    $pagecontent .= '</td></tr>';
    $toggle_pos = 0;

    foreach ($dircontent as $pos => $currentgalerien) {
        if(!isset($post['gallery']['error_html']['newname'][$currentgalerien])) {
            $post['gallery']['error_html']['newname'][$currentgalerien] = NULL;
        }
        if(!isset($post['gallery']['error_html']['display'][$currentgalerien])) {
            $post['gallery']['error_html']['display'][$currentgalerien] = NULL;
        }

        $pagecontent .= '<tr><td width="100%" class="td_toggle">';
        $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';

        $pagecontent .= '<tr><td width="85%" class="td_titel"><span class="text_cat_page">'.$specialchars->rebuildSpecialChars($currentgalerien, true, true).'</span></td>';
        $pagecontent .= '<td width="15%" class="td_icons" nowrap>';
        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<span id="toggle_'.$toggle_pos.'_linkBild"'.$tooltip_gallery_help_edit.'></span>';
        }
        $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletegallery]['.$currentgalerien.']" value="'.$text_gallery_button_gallery_delete.'" src="gfx/icons/'.$icon_size.'/delete.png" title="löschen"'.$tooltip_gallery_help_del.'></td></tr></table>';
        $pagecontent .= '</td></tr>';
        $pagecontent .= '<tr><td width="100%" id="toggle_'.$toggle_pos.'" align="right" class="td_togglen_padding_bottom"'.$post['gallery']['error_html']['display'][$currentgalerien].'>';
        # gallery setup
        $pagecontent .= '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data"><tr>';

#    $pagecontent .= '</tr><tr>';
    $pagecontent .= '<td colspan="2" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_all_picture_scale.'><input type="checkbox" name="gallery['.$currentgalerien.'][scale_max]" value="true"'.$tooltip_gallery_help_all_picture_scale.'>&nbsp;&nbsp;&nbsp;<b>'.$text_gallery_scaleimages.'</b>';
    $pagecontent .= '</td>';
    $pagecontent .= '</tr><tr>';
    $pagecontent .= '<td colspan="2" class="td_left_title_padding_bottom"'.$tooltip_gallery_help_all_thumbs_new.'><input type="checkbox" name="gallery['.$currentgalerien.'][make_thumbs]" value="true"'.$tooltip_gallery_help_all_thumbs_new.'>&nbsp;&nbsp;&nbsp;<b>'.$text_gallery_rebuildthumbs.'</b>';
    $pagecontent .= '</td>';
    $pagecontent .= '</tr><tr>';



        $pagecontent .= '<td class="td_left_title_padding_bottom td_toggle_new"'.$tooltip_gallery_help_newname.'>';
        $pagecontent .= '<b>'.$text_gallery_newname.'</b>';
        $pagecontent .= '</td>';
        $pagecontent .= '<td align="right" valign="top" class="td_title td_toggle_new"><b>'.getLanguageValue("gallery_newimage").'</b></td>';
        $pagecontent .= '</tr><tr>';
        $pagecontent .= '<td class="td_left_title_padding_bottom td_toggle_padding"'.$tooltip_gallery_help_newname.'><input type="text" class="input_text" name="gallery['.$currentgalerien.'][newname]" value="" maxlength="'.$max_strlen.'"'.$post['gallery']['error_html']['newname'][$currentgalerien].$tooltip_gallery_help_newname.'></td>';
        $pagecontent .= '<td align="right" valign="top" class="td_togglen_padding_bottom td_toggle_padding">';
        $pagecontent .= '<input type="file" id="uploadfileinput_'.$pos.'" name="uploadfile['.$currentgalerien.']" class="uploadfileinput">
                        <div id="files_list_'.$pos.'" class="text_cat_page"></div>
                        <script type="text/javascript">
                        <!-- Create an instance of the multiSelector class, pass it the output target and the max number of files -->
                        var multi_selector = new MultiSelector( document.getElementById( \'files_list_'.$pos.'\' ), \''.$ADMIN_CONF->get("maxnumberofuploadfiles").'\', \''.$text_gallery_button_cut.'\' );
                        <!-- Pass in the file element -->
                        multi_selector.addElement( document.getElementById( \'uploadfileinput_'.$pos.'\' ) , \''.$currentgalerien.'\' );
                        </script>';
        $pagecontent .= '</td>';
        $pagecontent .= '</tr></table>';
        # inhalt gallery
        $pagecontent .= '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
        $subtitle = new Properties($GALLERIES_DIR_REL.$currentgalerien."/texte.conf",true);
        $max_cols = 3;
        $max_cols_check = $max_cols;
        $td_width = round(100 / $max_cols).'%';
        $pagecontent .= "<tr>";
        $new_tr = false;
        foreach ($gallerypics[$currentgalerien] as $pos => $file) {
            # ausgleich weil pos mit 0 anfängt
            $pos = $pos + 1;
            $lastsavedanchor = "";#$lastsavedanchor = " id=\"lastsavedimage\"";
            $size = getimagesize($GALLERIES_DIR_REL.$currentgalerien."/".$file);
            // Vorschaubild anzeigen, wenn vorhanden; sonst Text
            if (file_exists($GALLERIES_DIR_REL.$currentgalerien."/$PREVIEW_DIR_NAME/".$file)) {
                $preview = '<a href="'.$specialchars->replaceSpecialChars($URL_BASE.$GALLERIES_DIR_NAME."/".$currentgalerien."/".$file,true).'" target="_blank"'.$tooltip_gallery_help_picture.'>'
                .'<img src="'.$specialchars->replaceSpecialChars($URL_BASE.$GALLERIES_DIR_NAME."/".$currentgalerien."/".$PREVIEW_DIR_NAME."/".$file,true).'" alt="'.$specialchars->rebuildSpecialChars($file, true, true).'" style="height:60px;border:none;" /></a>';
            } else {
                $preview = '<div style="color:red;text-align:center;"><b>'.$text_gallery_no_preview.'</b></div>';
            }
            if($new_tr === true) {
                $pagecontent .= "<tr>";
                $new_tr = false;
            }#
            $pagecontent .= '<td width="'.$td_width.'" align="left" valign="top" class="td_gallery_img">';
            $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_gallery_img">
                            <tr><td rowspan="5" valign="top" width="10%" style="padding-right:10px;">';
            $pagecontent .= $preview;
            $pagecontent .= '</td>
                            <td height="1%" class="td_left_title"'.$tooltip_gallery_help_size.'><b>'.$text_gallery_size.'</b></td></tr>
                            <tr><td height="1%" class="td_togglen_padding_bottom">'.$size[0].' x '.$size[1].' Pixel</td></tr>
                            <tr><td height="1%" class="td_left_title"><b>'.$text_gallery_subtitle.'</b></td></tr>
                            <tr><td height="1%"><input type="text" class="input_text" name="gallery['.$currentgalerien.'][subtitle]['.$file.']" value="'.$specialchars->rebuildSpecialChars($subtitle->get($file), true, true).'"'.$tooltip_gallery_help_subtitle.' /></td></tr>
                            <tr><td style="font-size:1px;">&nbsp;</td></tr>
                            <tr><td height="1%" colspan="2">
                            <table summary="" width="100%" cellspacing="0" border="0" cellpadding="0">
                            <tr><td width="99%">
                            <input type="text" class="input_readonly" name="gallery['.$currentgalerien.'][image][]" value="'.$specialchars->rebuildSpecialChars($file, true, true).'" maxlength="'.$max_strlen.'" readonly'.$tooltip_gallery_help_name.'></td>
                            <td width="1%" nowrap>&nbsp;&nbsp;<input type="image" class="input_img_button_last" name="action_data[deletegalleryimg]['.$currentgalerien.']['.$file.']" value="'.$text_gallery_button_img_delete.'" src="gfx/icons/'.$icon_size.'/delete.png" title="löschen"'.$tooltip_gallery_help_del.'>
                            </td></tr></table>
                            </td></tr>
                            </table>';
            $pagecontent .= "</td>";
            if($pos == $max_cols_check) {
                $pagecontent .= "</tr>";
                if(count($gallerypics[$currentgalerien]) >  $max_cols_check) {
                    $max_cols_check = $pos + $max_cols;
                    $new_tr = true;
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
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$toggle_pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.$text_toggle_show.'\',\''.$text_toggle_hide.'\');</script>';
        }
        $pagecontent .= '</td></tr>';
        $toggle_pos++;
    }

    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[gallery]" value="'.getLanguageValue("gallery_button_change").'" class="input_submit">';
    $pagecontent .= '</td></tr>';

    $pagecontent .= '</table>';
    return array(getLanguageValue("gallery_button"), $pagecontent);
}


function newGalleryImg($post) {
    global $PREVIEW_DIR_NAME;
    global $GALLERIES_DIR_REL;
    global $specialchars;
    global $GALLERY_CONF;
    global $BASE_DIR_CMS;

    $forceoverwrite = "";
    if (isset($_POST['overwrite'])) {
        $forceoverwrite = $_POST['overwrite'];
    }

    foreach($_FILES as $array_name => $tmp) {
        if($_FILES[$array_name]['error'] == 0) {
            $gallery = explode("_",$array_name);
            unset($gallery[0]);
            unset($gallery[count($gallery)]);
            $gallery_tmp = NULL;
            foreach($gallery as $tmp) {
                $gallery_tmp .= $tmp."_";
            }
            $gallery[1] = substr($gallery_tmp,0,-1);
            $error = uploadFile($_FILES[$array_name], $GALLERIES_DIR_REL.$gallery[1]."/", $forceoverwrite,$GALLERY_CONF->get('maxwidth'), $GALLERY_CONF->get('maxtheight'), true);
            if(!empty($error)) {
                $post['error_messages'][key($error)][] = $gallery[1]."/".$error[key($error)];
            } else {
                require_once($BASE_DIR_CMS."Image.php");
                $pict = $specialchars->replaceSpecialChars($_FILES[$array_name]['name'],false);
                $size    = GetImageSize($GALLERIES_DIR_REL.$gallery[1]."/".$pict);
                if($size[0] <= $GALLERY_CONF->get('maxthumbwidth') and $size[1] <= $GALLERY_CONF->get('maxthumbheight')) {
                    copy($GALLERIES_DIR_REL.$gallery[1]."/".$pict,$GALLERIES_DIR_REL.$gallery[1]."/".$PREVIEW_DIR_NAME."/".$pict);
                } else {
                    scaleImage($pict, $GALLERIES_DIR_REL.$gallery[1]."/", $GALLERIES_DIR_REL.$gallery[1]."/".$PREVIEW_DIR_NAME."/", $GALLERY_CONF->get('maxthumbwidth'), $GALLERY_CONF->get('maxthumbheight'));
                }
                useChmod($GALLERIES_DIR_REL.$gallery[1]."/".$PREVIEW_DIR_NAME."/".$pict);
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
            if(!file_exists($GALLERIES_DIR_REL.$galleryname)) {
                @mkdir($GALLERIES_DIR_REL.$galleryname);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error['line'] = NULL;
                if(function_exists("error_get_last")) {
                    $last_error = @error_get_last();
                }
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                } elseif(!is_dir($GALLERIES_DIR_REL.$galleryname)) {
                    $post['error_messages']['gallery_error_new'][] = $galleryname;
                } else {
                    @mkdir($GALLERIES_DIR_REL.$galleryname."/".$PREVIEW_DIR_NAME);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error['line'] = NULL;
                    if(function_exists("error_get_last")) {
                        $last_error = @error_get_last();
                    }
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                    } elseif(!is_dir($GALLERIES_DIR_REL.$galleryname."/".$PREVIEW_DIR_NAME)) {
                        $post['error_messages']['gallery_error_new_preview'][] = $galleryname;
                    } else {
                        @touch($GALLERIES_DIR_REL.$galleryname."/texte.conf");
                        $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                        $last_error['line'] = NULL;
                        if(function_exists("error_get_last")) {
                            $last_error = @error_get_last();
                        }
                        if($last_error['line'] == $line_error) {
                            $post['error_messages']['php_error'][] = $last_error['message'];
                        } elseif(!is_file($GALLERIES_DIR_REL.$galleryname."/texte.conf")) {
                            $post['error_messages']['gallery_error_datei_conf'][] = $galleryname."/texte.conf";
                        } else {
                            $error = changeChmod($GALLERIES_DIR_REL.$galleryname);
                            if(is_array($error)) {
                                $post['error_messages'][key($error)][] = $error[key($error)];
                            }
                            $error = changeChmod($GALLERIES_DIR_REL.$galleryname."/".$PREVIEW_DIR_NAME);
                            if(is_array($error)) {
                                $post['error_messages'][key($error)][] = $error[key($error)];
                            }
                            $error = changeChmod($GALLERIES_DIR_REL.$galleryname."/texte.conf");
                            if(is_array($error)) {
                                $post['error_messages'][key($error)][] = $error[key($error)];
                            }
                            $post['messages']['gallery_message_new'][] = $specialchars->rebuildSpecialChars($galleryname, true, true);
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
    global $GALLERY_CONF;
    global $ADMIN_CONF;
    global $BASE_DIR_CMS;

    # wenn expert eingeschaltet wird müssen die expert $post gefüllt werden
    $gallery = makeDefaultConf("gallery");
    if(isset($GALLERY_CONF->properties['error'])) {
        $post['error_messages']['gallery_error_setings'][] = $GALLERY_CONF->properties['error'];
        return $post;
    }

    foreach($gallery as $type => $type_array) {
        if($type == 'expert') continue;
        foreach($gallery[$type] as $syntax_name => $dumy) {
            $error_messages = false;
            $post['gallery']['error_html'][$syntax_name] = NULL;
            if($ADMIN_CONF->get('showexpert') == "false" and in_array($syntax_name,$gallery['expert'])) {
                continue;
            }
            if($type == 'digit') {
                if(isset($post['gallery']['setings'][$syntax_name])) {
                    $syntax_value = trim($post['gallery']['setings'][$syntax_name]);
                } else continue;
                if($syntax_name == "maxthumbheight" or $syntax_name == "maxthumbwidth") {
                    if($post['gallery']['setings']['maxthumbheight'] == "" and $post['gallery']['setings']['maxthumbwidth'] == "") {
                        $error_messages = 'gallery_error_thumbs_no_digit';
                    }
                }
                # wenn eingabe keine Zahl oder mehr wie 4stelig ist
                if($syntax_value != "" and (!ctype_digit($syntax_value) or strlen($syntax_value) > 4)) {
                    $error_messages = 'gallery_error_digit';
                }
            }
            if($type == 'checkbox') {
                $syntax_value = "false";
                if(isset($post['gallery']['setings'][$syntax_name])) {
                    $syntax_value = $post['gallery']['setings'][$syntax_name];
                }
            }
            if($type == 'text') {
                if(isset($post['gallery']['setings'][$syntax_name])) {
                    $syntax_value = $post['gallery']['setings'][$syntax_name];
                } else continue;
            }
            if($error_messages === false and $GALLERY_CONF->get($syntax_name) != $syntax_value) {
                $GALLERY_CONF->set($syntax_name, $syntax_value);
                if(!isset($post['messages']['gallery_messages_setings'])) {
                    $post['messages']['gallery_messages_setings'][] = NULL;
                }
            $post['gallery']['error_html']['display_setings'] = ' style="display:block;"';
            }
            if($error_messages !== false) {
                $post['error_messages'][$error_messages]['color'] = "#FF7029";
                $post['error_messages'][$error_messages][] = NULL;
                $post['gallery']['error_html'][$syntax_name] = 'style="background-color:#FF7029;" ';
            }
        }
    }

    # Galerien durchgehen und änderungen machen
    foreach($post['gallery'] as $gallery => $gallery_array) {
        if($gallery == "setings" or $gallery == "error_html" or $gallery == "make_thumbs" or $gallery == "scale_max") continue;
        # Neuer Gallery Name
        if(isset($post['gallery'][$gallery]['newname']) and strlen($post['gallery'][$gallery]['newname']) > 0) {
            $newname = $specialchars->replaceSpecialChars($post['gallery'][$gallery]['newname'],false);
            if(!preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname) or stristr($newname,"%5E")) {
                $post['error_messages']['check_name']['color'] = "#FFC197";
                $post['gallery']['error_html']['newname'][$gallery] = 'style="background-color:#FFC197;" ';
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
            } else {
                @rename($GALLERIES_DIR_REL.$gallery,$GALLERIES_DIR_REL.$newname);
                $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                $last_error['line'] = NULL;
                if(function_exists("error_get_last")) {
                    $last_error = @error_get_last();
                }
                if($last_error['line'] == $line_error) {
                    $post['error_messages']['php_error'][] = $last_error['message'];
                } elseif(!is_dir($GALLERIES_DIR_REL.$newname)) {
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
            $gallery_subtitel = new Properties($GALLERIES_DIR_REL.$gallery."/texte.conf",true);
            foreach($post['gallery'][$gallery]['subtitle'] as $img => $subtitel) {
                if($gallery_subtitel->get($img) != $subtitel and is_file($GALLERIES_DIR_REL.$gallery."/".$img)) {
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

        #make_thumbs
        if(isset($post['gallery'][$gallery]['make_thumbs'])) {
            require_once($BASE_DIR_CMS."Image.php");
            $gallerypics = getFiles($GALLERIES_DIR_REL.$gallery,"");
            foreach($gallerypics as $pos => $file) {
                # nur Dateien zulassen
                if(!is_dir($GALLERIES_DIR_REL.$gallery.'/'.$file)) {
                    scaleImage($file, $GALLERIES_DIR_REL.$gallery.'/', $GALLERIES_DIR_REL.$gallery.'/'.$PREVIEW_DIR_NAME.'/', $GALLERY_CONF->get('maxthumbwidth'), $GALLERY_CONF->get('maxthumbheight'),true);
                    $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                    $post['messages']['gallery_messages_make_thumbs'][$gallery] = $gallery;
                }
            }
        }
        #scale_max
        if(isset($post['gallery'][$gallery]['scale_max']) and ($GALLERY_CONF->get('maxwidth') > 0 or $GALLERY_CONF->get('maxheight') > 0)) {
            if(isset($post['gallery']['error_html']['maxwidth']) or isset($post['gallery']['error_html']['maxheight'])) {
                $post['error_messages']['gallery_error_no_scale_max'][] = NULL;
                $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
            } else {
                require_once($BASE_DIR_CMS."Image.php");
                $gallerypics = getFiles($GALLERIES_DIR_REL.$gallery,"");
                foreach($gallerypics as $pos => $file) {
                    $test_img = @getimagesize($GALLERIES_DIR_REL.$gallery.'/'.$file);
                    # nur Bilder zulassen
                    if(!is_dir($file) and count($test_img) > 2) {
                        if($test_img[0] > $GALLERY_CONF->get('maxwidth') or $test_img[1] > $GALLERY_CONF->get('maxheight')) {
                            scaleImage($file, $GALLERIES_DIR_REL.$gallery.'/', $GALLERIES_DIR_REL.$gallery.'/', $GALLERY_CONF->get('maxwidth'), $GALLERY_CONF->get('maxheight'));
                            $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
                            $post['messages']['gallery_messages_scale_max'][$gallery] = $gallery;
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
    global $icon_size;

    $gallery = key($post['action_data']['deletegalleryimg']);
    $del_file = $post['action_data']['deletegalleryimg'][$gallery];

    if (isset($_POST['confirm']) and ($_POST['confirm'] == "true")) {
        if(file_exists($GALLERIES_DIR_REL.$gallery."/".$del_file)) {
            @unlink($GALLERIES_DIR_REL.$gallery."/".$del_file);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(is_file($GALLERIES_DIR_REL.$gallery."/".$del_file)) {
                $post['error_messages']['gallery_error_deleted_img'][] = $gallery." <b>></b> ".$del_file;
            } else {
                if(file_exists($GALLERIES_DIR_REL.$gallery."/".$PREVIEW_DIR_NAME."/".$del_file)) {
                    @unlink($GALLERIES_DIR_REL.$gallery."/".$PREVIEW_DIR_NAME."/".$del_file);
                    $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
                    $last_error['line'] = NULL;
                    if(function_exists("error_get_last")) {
                        $last_error = @error_get_last();
                    }
                    if($last_error['line'] == $line_error) {
                        $post['error_messages']['php_error'][] = $last_error['message'];
                    } elseif(is_file($GALLERIES_DIR_REL.$gallery."/".$del_file)) {
                        $post['error_messages']['gallery_error_deleted_img'][] = $gallery."/".$PREVIEW_DIR_NAME."/ <b>></b> ".$del_file;
                    }
                }
                $subtitle = new Properties($GALLERIES_DIR_REL.$gallery."/texte.conf",true);
                $subtitle->delete($del_file);
                $post['messages']['gallery_message_deleted_img'][] = $gallery." <b>></b> ".$del_file;
            }
            $post['gallery']['error_html']['display'][$gallery] = ' style="display:block;"';
        }
    } else {
        if (isset($_POST['confirm']) and ($_POST['confirm'] == "false")) {
            $post['gallery']['error_html']['display'][$gallery] = 'style="display:block;" ';
        } else {
            $post['ask'] = askMessages("gallery_ask_delete_img",$del_file,'action_data[deletegalleryimg]['.$gallery.']',$del_file);
            $post['gallery']['error_html']['display'][$gallery] = 'style="display:block;" ';
        }
    }
    return $post;
}

function deleteGallery($post) {
    global $specialchars;
    global $GALLERIES_DIR_REL;
    global $PREVIEW_DIR_NAME;
    global $icon_size;

    # Nachfragen wirklich Löschen
    if(!isset($_POST['confirm'])) {
        $del_gallery = key($post['action_data']['deletegallery']);
        $post['ask'] = askMessages("gallery_ask_delete",$del_gallery,'action_data[deletegallery]',$post['action_data']['deletegallery'],"del_gallery",$del_gallery);
    }
    # Gallery Löschen    
    if(isset($_POST['confirm']) and $_POST['confirm'] == "true" and isset($_POST['del_gallery']) and !empty($_POST['del_gallery'])) {
        $del_gallery = $GALLERIES_DIR_REL.$_POST['del_gallery'];
        $post['error_messages'] = deleteDir($del_gallery);
        if(!file_exists($GALLERIES_DIR_REL.$_POST['del_gallery'])) {
            unset($post['gallery'][$_POST['del_gallery']]);
            $post['messages']['gallery_message_deleted'][] = $_POST['del_gallery'];
        } else {
            if(!isset($post['error_messages'])) {
                $post['error_messages']['gallery_error_deleted'][] = $_POST['del_gallery'];
            }
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
    global $icon_size;
    global $URL_BASE;

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
    if(isset($post['ask'])) {
        $pagecontent .= $post['ask'];
    }

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_files_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","files_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_files_help = '<a href="'.getHelpUrlForSubject("dateien").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }
    # Prüfen ob der Ordner dateien existiert wenn nicht anlegen
    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        if($pos == $max_cat_page or isset($post['categories']['cat']['url'][$pos])) {
            continue;
        }
        $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        if(!file_exists($CONTENT_DIR_REL.$file."/dateien")) {
            $post['error_messages']['files_error_dateien'][] = $CONTENT_DIR_REL.$file."/dateien";
            @mkdir ($CONTENT_DIR_REL.$file."/dateien");
            $line_error = __LINE__ - 1;
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(!is_dir($CONTENT_DIR_REL.$file."/dateien")) {
                $post['error_messages']['files_error_mkdir_dateien'][] = $CONTENT_DIR_REL.$file."/dateien";
            } else {
                useChmod($CONTENT_DIR_REL.$file."/dateien");
            }
        }
    }
    $pagecontent .= categoriesMessages($post);

    $pagecontent .= '<span class="titel">'.getLanguageValue("files_button").'</span>';
    $pagecontent .= $tooltip_files_help;
    $pagecontent .= "<p>".getLanguageValue("files_text")."</p>";

    $maxnumberoffiles = $ADMIN_CONF->get("maxnumberofuploadfiles");
    if (!is_numeric($maxnumberoffiles) || ($maxnumberoffiles < 1)) {
        $maxnumberoffiles = 5;
    }

    $array_getLanguageValue = array("files","category","contents","file","files_downloads","files_uploaddate","files_size","files_text_upload","files_button_cut","files_button_delete","files_text_no_files");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getLanguageValue as $language) {
        ${"text_".$language} = getLanguageValue($language);
    }
    $title_files_button_delete = getLanguageValue('files_button_delete',true);
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

    $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[newfile]" value="'.getLanguageValue("files_button_upload").'" class="input_submit">';
    $overwrite = NULL;
    if($ADMIN_CONF->get("overwriteuploadfiles") == "true") {
            $overwrite = ' checked="checked"';
    }
    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_files_help_overwrite.$overwrite.'>&nbsp;<span class="td_left_title"'.$tooltip_files_help_overwrite.'><b>'.getLanguageValue("files_button_overwrite").'</b></span>';
    $pagecontent .= '</td></tr>';

    foreach ($post['categories']['cat']['position'] as $pos => $position) {
        unset($display_titel_dateien);
        if($pos == $max_cat_page or isset($post['categories']['cat']['url'][$pos])) {
            continue;
        }
        $file = $post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos];
        // Anzahl Dateien auslesen
        $filecount = 0;
        if(file_exists($CONTENT_DIR_REL.$file."/dateien")) {
            if($fileshandle = opendir($CONTENT_DIR_REL.$file."/dateien")) {
                 while (($filesdir = readdir($fileshandle))) {
                    if(isValidDirOrFile($filesdir))
                        $filecount++;
                }
                closedir($fileshandle);
            }
        }
        $text = '('.$filecount.' '.$text_files.')';

        $pagecontent .= '<tr><td class="td_toggle">';

        $pagecontent .= '<table summary="" width="100%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
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
        if (isValidDirOrFile($file) && ($subhandle = @opendir($CONTENT_DIR_REL.$file."/dateien"))) {
            $hasdata = false;
            $pagecontent .= '<table summary="" width="98%" class="table_data" cellspacing="0" border="0" cellpadding="0">';
            $pagecontent .= '<tr><td class="td_left_title_padding_bottom td_toggle_new" colspan="1">'.$text_files_text_upload.'</td>'
            .'<td colspan="4" class="td_right_title_padding_bottom td_toggle_new"'.$tooltip_files_help_upload.'><input type="file" id="uploadfileinput_'.$pos.'" name="uploadfile" class="uploadfileinput"></td></tr>'
            .'<tr><td colspan="5" class="td_right_title_padding_bottom td_toggle"><div id="files_list_'.$pos.'" class="text_cat_page"></div>';
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
                $uploadtime = filemtime($CONTENT_DIR_REL.$file."/dateien/".$subfile);
                $counterstart = $DOWNLOAD_COUNTS->get("_downloadcounterstarttime");
                // Berechnungsgrundlage fuer "Downloads pro Tag":
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
#                if ($downloads > 0)
#                    $downloadsperdaytext = "/ ".getLanguageValue("files_text_downloads_day")." ".$downloadsperday;
 #               else
                    $downloadsperdaytext = "";
                // Dateigröße
                $filesize = filesize($CONTENT_DIR_REL.$file."/dateien/".$subfile);

        $titel_dateien = NULL;
        if(!isset($display_titel_dateien)) {# Position:          
            $titel_dateien = '<tr><td class="td_left_title"><b>'.$text_file.'</b></td><td width="10%" class="td_left_title" nowrap><b>'.$text_files_size.'</b></td><td width="20%" class="td_left_title" nowrap><b>'.$text_files_uploaddate.'</b></td><td width="10%" class="td_center_title" nowrap><b'.$tooltip_files_help_downloads.'>'.$text_files_downloads.'</b></td><td width="5%" class="td_left_title" nowrap>&nbsp;</td></tr>';
            $display_titel_dateien = true;
        }

                $pagecontent .= $titel_dateien.'<tr><td class="td_left_title_padding_bottom"><a class="file_link" href="'.$URL_BASE.'kategorien/'.$specialchars->replaceSpecialChars($file.'/dateien/'.$subfile,true).'" target="_blank"'.$tooltip_files_help_show.'>'.$specialchars->rebuildSpecialChars($subfile,true,true).'</a></td>'
                .'<td class="td_left_title_padding_bottom" nowrap><span class="text_info">'.convertFileSizeUnit($filesize).'</span></td>'
                .'<td class="td_left_title_padding_bottom" nowrap><span class="text_info">'.@strftime(getLanguageValue("_dateformat"), $uploadtime).'</span></td>'
                .'<td class="td_center_title_padding_bottom" nowrap><span class="text_info">'.$downloads." ".$downloadsperdaytext.'</span></td>';
                $pagecontent .= '<td class="td_left_title_padding_bottom" nowrap>';
                $pagecontent .= '<input type="image" class="input_img_button_last" name="action_data[deletefile]['.$post['categories']['cat']['position'][$pos]."_".$post['categories']['cat']['name'][$pos].']['.$subfile.']" value="'.$text_files_button_delete.'"  src="gfx/icons/'.$icon_size.'/delete.png" title="'.$title_files_button_delete.'"'.$tooltip_files_help_delete.'>';
                $pagecontent .= '</td></tr>';

                $hasdata = true;
            }
        }
            if (!$hasdata)
            $pagecontent .= '<tr><td class="td_left_title_padding_bottom" colspan="5"><span class="text_info">'.$text_files_text_no_files.'</span></td></tr>';
            $pagecontent .= "</table>";
        }

        if(getRequestParam('javascript', true)) {
            $pagecontent .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.getLanguageValue("toggle_show").'\',\''.getLanguageValue("toggle_hide").'\');</script>';
        }
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= '<tr><td class="td_toggle">';
    $pagecontent .= '<input type="submit" name="action_data[newfile]" value="'.getLanguageValue("files_button_upload").'" class="input_submit">';
#    $pagecontent .= '&nbsp;&nbsp;<input class="input_check_copy" type="checkbox" name="overwrite" value="on"'.$tooltip_files_help_overwrite.$overwrite.'>&nbsp;<span'.$tooltip_files_help_overwrite.$overwrite.'>'.getLanguageValue("files_button_overwrite").'</span>';
    $pagecontent .= '</td></tr></table>';

    return array(getLanguageValue("files_button"), $pagecontent);
}

function newFile($post) {
    global $CONTENT_DIR_REL;
    global $error_color;
    global $ADMIN_CONF;

    $forceoverwrite = "";
    if (isset($_POST['overwrite'])) {
        $forceoverwrite = $_POST['overwrite'];
    }


    foreach($_FILES as $array_name => $tmp) {
        if($_FILES[$array_name]['error'] == 0) {
            $cat_pos= explode("_",$array_name);
            $cat = sprintf("%02d",$cat_pos[1])."_".specialNrDir("$CONTENT_DIR_REL", sprintf("%02d",$cat_pos[1]));
            $error = uploadFile($_FILES[$array_name], $CONTENT_DIR_REL.$cat."/dateien/", $forceoverwrite,$ADMIN_CONF->get("maximagewidth"),$ADMIN_CONF->get("maximageheight"));
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
    global $icon_size;

    $cat = key($post['action_data']['deletefile']);
    $del_file = $post['action_data']['deletefile'][$cat];

    if(isset($_POST['confirm']) and ($_POST['confirm'] == "false")) {
        return $post;
    } elseif(isset($_POST['confirm']) and ($_POST['confirm'] == "true")) {
        if(file_exists($CONTENT_DIR_REL.$cat."/dateien/".$del_file)) {
            @unlink($CONTENT_DIR_REL.$cat."/dateien/".$del_file);
            $line_error = __LINE__ - 1; # wichtig direckt nach Befehl
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                $post['error_messages']['php_error'][] = $last_error['message'];
            } elseif(is_file($CONTENT_DIR_REL.$cat."/dateien/".$del_file)) {
                $post['error_messages']['files_error_delete'][] = $cat." <b>></b> ".$del_file;
            } else {
                $post['messages']['files_message_deleted'][] = $cat." <b>></b> ".$del_file;
            }
            $key = array_keys($post['categories']['cat']['position'], substr($cat,0,2));
            $post['categories']['cat']['error_html']['display'][$key[0]] = 'style="display:block;" ';
            return $post;
        }
    } else {
        $post['ask'] = askMessages("files_ask_delete",$del_file,'action_data[deletefile]['.$cat.']',$del_file);
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
    global $icon_size;
    global $BASE_DIR;
    global $BASE_DIR_CMS;

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

    $language_array = getFiles($BASE_DIR_CMS.'sprachen',true);
    $cat_array = getDirs($CONTENT_DIR_REL,true,true);
    $layout_array = getDirs($BASE_DIR."layouts",true);

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("config_button").'">';


    if(isset($post['apply'])) {
        $post['apply'] = $specialchars->rebuildSpecialChars(getRequestParam('apply', true), false, true);
    }


    // Änderungen speichern
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

        # usecmssyntax wurde eingeschaltet also posts füllen
        if(isset($post['usecmssyntax']) and $post['usecmssyntax'] == "true" and $CMS_CONF->get('usecmssyntax') == "false") {
            $post['replaceemoticons'] = $CMS_CONF->get('replaceemoticons');
            $post['shortenlinks'] = $CMS_CONF->get('shortenlinks');
        }
        # usecmssyntax ist ausgeschaltet also posts füllen
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
                    if($syntax_name == "modrewrite" and !isset($_REQUEST['link']) and isset($post[$syntax_name]) and $post[$syntax_name] == "true") {
                        $checkbox = "false";
                        $post['error_messages']['config_error_modrewrite'][] = NULL;
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
            if($syntax_name == "formularmail") {
                 if($post[$syntax_name] != "" and !preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/",$post[$syntax_name])) {
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

        // Speichern der benutzerdefinierten Syntaxelemente -> ERWEITERN UM PRÜFUNG!
        # nur Speichern wenn auch benutzt wird
        if($CMS_CONF->get('usecmssyntax') == "true") {
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
                            if(substr($zeile,0,strpos($zeile," =") + strlen(" = ")) == $search_tmp) {
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
    } elseif(!in_array("language_".$CMS_CONF->get('cmslanguage').".conf",$language_array)) {
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

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_config_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","config_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_config_help = '<a href="'.getHelpUrlForSubject("konfiguration").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }

    $pagecontent .= '<span class="titel">'.getLanguageValue("config_button").'</span>';
    $pagecontent .= $tooltip_config_help;
    $pagecontent .= "<p>".getLanguageValue("config_text")."</p>";
    // ALLGEMEINE EINSTELLUNGEN
    $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
    // Zeile "ÜBERNEHMEN"
    # Save Buttom nur Anzeigen wenn Properties auch Speichen kann
    if(!isset($CMS_CONF->properties['error']) or !isset($CONTACT_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit_top" colspan="2">';
        $pagecontent .= '<input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("config_submit").'" />';
    }
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
    $txt_category = getLanguageValue("category");
    $txt_page = getLanguageValue("page");
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
    	$currentlanguagecode = substr($file,strlen("language_"),strlen($file)-strlen("language_")-strlen(".conf"));
        $selected = NULL;
        // aktuell ausgewählte Sprache als ausgewählt markieren 
        if($currentlanguagecode == $CMS_CONF->get("cmslanguage")) {
            $selected = " selected";
        }
        $pagecontent .= "<option".$selected." value=\"".$currentlanguagecode."\">";
        // Übersetzer aus der aktuellen Sprachdatei holen
        $languagefile = new Properties("../cms/sprachen/$file",true);
        $pagecontent .= $currentlanguagecode." (".getLanguageValue("config_input_translator")." ".$languagefile->get("_translator_0").")";
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
        // Übersetzer aus der aktuellen Sprachdatei holen
        $pagecontent .= $specialchars->rebuildSpecialChars($file, true, true);
        $pagecontent .= "</option>";
    }
    $pagecontent .= "</select></td></tr>";
    // Zeile "STANDARD-KATEGORIE"
    $pagecontent .= "<tr><td class=\"td_cms_left\">".getLanguageValue("config_text_defaultcat")."</td>";
    $pagecontent .= "<td class=\"td_cms_left\">";
    $pagecontent .= "<select name=\"defaultcat\" class=\"input_cms_select\"".$error_color['defaultcat'].">";
    foreach($cat_array as $element) {
        if (count(getFiles($CONTENT_DIR_REL.$element, "")) == 0) {
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

    if($ADMIN_CONF->get('showexpert') == "true") {
        // Zeile "NUTZE SUBMENÜ"
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
    .'<table summary="" width="90%" cellspacing="0" border="0" cellpadding="0" align="right" class="table_contact">'
    .'<tr><td width="40%" class="td_contact_title" align="left">'.getLanguageValue("config_titel_contact_help").'</td>'
    .'<td width="30%" class="td_contact_title" align="left">'.getLanguageValue("config_titel_contact_input").'</td>'
    .'<td width="10%" class="td_contact_title" align="center">'.getLanguageValue("config_titel_contact_show").'</td>'
    .'<td width="10%" class="td_contact_title" align="center">'.getLanguageValue("config_titel_contact_mandatory").'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_name").'</td><td><input type="text" class="input_cms_text" name="titel_name" value="'.$specialchars->rebuildSpecialChars($config_name[0],true,true).'"'.$error_color['titel_name'].' /></td><td align="center">'.buildCheckBox("show_name", ($config_name[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_name", ($config_name[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_mail").'</td><td><input type="text" class="input_cms_text" name="titel_mail" value="'.$specialchars->rebuildSpecialChars($config_mail[0],true,true).'"'.$error_color['titel_mail'].' /></td><td align="center">'.buildCheckBox("show_mail", ($config_mail[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_mail", ($config_mail[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_website").'</td><td><input type="text" class="input_cms_text" name="titel_website" value="'.$specialchars->rebuildSpecialChars($config_website[0],true,true).'"'.$error_color['titel_website'].' /></td><td align="center">'.buildCheckBox("show_website", ($config_website[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_website", ($config_website[2] == "true")).'</td></tr>'
    .'<tr><td>'.getLanguageValue("config_input_contact_textarea").'</td><td><input type="text" class="input_cms_text" name="titel_message" value="'.$specialchars->rebuildSpecialChars($config_message[0],true,true).'"'.$error_color['titel_message'].' /></td><td align="center">'.buildCheckBox("show_message", ($config_message[1] == "true")).'</td><td align="center">'.buildCheckBox("mandatory_message", ($config_message[2] == "true")).'</td></tr>'
    ."</table></td>";
    $pagecontent .= "</tr>";

    if($ADMIN_CONF->get('showexpert') == "true") {
        $pagecontent .= '<tr><td class="td_cms_titel" colspan="2">'.getLanguageValue("config_titel_expert").'</td></tr>';
        // Zeile "showhiddenpagesin"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_showhiddenpages").'</td>';
        $pagecontent .= '<td class="td_cms_left">'
            // Obsolet seit 1.12
            //.buildCheckBox("showhiddenpagesinlastchanged", ($CMS_CONF->get("showhiddenpagesinlastchanged") == "true")).getLanguageValue("config_input_lastchanged").'<br />'
            .buildCheckBox("showhiddenpagesinsearch", ($CMS_CONF->get("showhiddenpagesinsearch") == "true")).getLanguageValue("config_input_search").'<br />'
            .buildCheckBox("showhiddenpagesinsitemap", ($CMS_CONF->get("showhiddenpagesinsitemap") == "true")).getLanguageValue("config_input_sitemap").'<br />'
            .buildCheckBox("showhiddenpagesasdefaultpage", ($CMS_CONF->get("showhiddenpagesasdefaultpage") == "true")).getLanguageValue("config_input_pagesasdefaultpage").'<br />'
            .buildCheckBox("showhiddenpagesincmsvariables", ($CMS_CONF->get("showhiddenpagesincmsvariables") == "true")).getLanguageValue("config_input_pagesincmsvariables").'</td></tr>';
        // Zeile "Links öffnen self blank"
        $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("config_text_target").'</td>';
        $pagecontent .= '<td class="td_cms_left">'.buildCheckBox("targetblank_download", ($CMS_CONF->get("targetblank_download") == "true")).getLanguageValue("config_input_download").'<br />'.buildCheckBox("targetblank_link", ($CMS_CONF->get("targetblank_link") == "true")).getLanguageValue("config_input_link").'</td></tr>';
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
    // Zeile "ÜBERNEHMEN"
    # Save Buttom nur Anzeigen wenn Properties auch Speichen kann
    if(!isset($CMS_CONF->properties['error']) or !isset($CONTACT_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit" colspan="2">';
        $pagecontent .= '<input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("config_submit").'" />';
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
    global $icon_size;
    global $BASE_DIR_ADMIN;

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
    // Änderungen gespeichert
    $changesmade = false;

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("admin_button").'">';

    $error_color['language'] = NULL;
    $language_array = getFiles($BASE_DIR_ADMIN.'sprachen',true);
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
        $post['messages']['initialsetup'][] = NULL;
        if(strpos("tmp".$_SERVER['QUERY_STRING'],"action") > 0) {
            $post['error_messages']['initialsetup_error'][] = NULL;
        }
    }

    // Auswertung des Formulars
    if(isset($post['apply']) and $post['apply'] == getLanguageValue("admin_submit")) {

        # auf jeden fall erst mal deDE setzen ist blöd wenn kein language gesetzt ist
        $ADMIN_CONF->set('language', "deDE");
        if(count($language_array) > 0) {
            $ADMIN_CONF->set('language', $post['language']);
        }

        # wenn expert eingeschaltet wird müssen die expert $post gefüllt werden
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
                        if($syntax_name == 'adminmail') {
                            $ADMIN_CONF->set($syntax_name, $text);
                        } else {
                            $ADMIN_CONF->set($syntax_name, $specialchars->replaceSpecialChars($text,false));
                        }
                    }
        
                }
                if($type == 'digit') {
                    if($syntax_name == 'lastbackup') {
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

        require_once($BASE_DIR_ADMIN."Crypt.php");
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
            } elseif ((strlen($post['newpw']) < 6) or !preg_match("/[0-9]/", $post['newpw']) or !preg_match("/[a-z]/", $post['newpw']) or !preg_match("/[A-Z]/", $post['newpw'])) {
                $post['error_messages']['pw_error_newpwerror']['color'] = "#FF7029";
                $post['error_messages']['pw_error_newpwerror'][] = NULL;
                $error_color['newpw'] = ' style="background-color:#FF7029;"';
            # Allles gut Speichen
            } else {
                # initialsetub sachen speichern
                $LOGINCONF->set("initialsetup", "false");
                $LOGINCONF->set("initialpw", "false");
                $ADMIN_CONF->set('lastbackup', time());
                $LOGINCONF->set("name", $post['newname']);
                $LOGINCONF->set("pw", $pwcrypt->encrypt($post['newpw']));
                $post['messages']['pw_messages_changes'][] = NULL;
            }
        }

        if($LOGINCONF->get('initialsetup') == "false") {
            $post['messages']['admin_messages_changes'][] = NULL;
        } else {
            $post['error_messages']['admin_error_initialsetup'][] = NULL;
        }


    } #applay end

    # Anzeige begin
    $pagecontent .= categoriesMessages($post);

    $array_getTooltipValue = array("admin_help_language","admin_help_adminmail","admin_help_chmodnewfiles",
        "admin_help_chmodupdate");

    # Variable erzeugen z.B. pages = $text_pages
    foreach($array_getTooltipValue as $language) {
        if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
            ${"tooltip_".$language} = createTooltipWZ("",$language,",WIDTH,200,CLICKCLOSE,true");
        } else {
            ${"tooltip_".$language} = NULL;
        }
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_admin_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","admin_help",",WIDTH,400,CLICKCLOSE,true").'>';
    } else {
        $tooltip_admin_help = '<a href="'.getHelpUrlForSubject("moziloadmin").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
    }


    $pagecontent .= '<span class="titel">'.getLanguageValue("admin_button").'</span>';
    $pagecontent .= $tooltip_admin_help;
    $pagecontent .= "<p>".getLanguageValue("admin_text")."</p>";



    $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
    // Zeile "ÜBERNEHMEN"
    if(!isset($ADMIN_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit_top" colspan="2"><input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("admin_submit").'">';
        if($LOGINCONF->get("initialsetup") == "true") {
            $pagecontent .= '&nbsp;&nbsp;&nbsp;<input type="submit" name="default_pw" class="input_submit" value="'.getLanguageValue("admin_submit_default_pw").'">';
        }
        $pagecontent .= '</td></tr>';
    }
/*    $pagecontent .= "<tr>";
    $pagecontent .= '<td class="td_cms_titel" colspan="2">'.getLanguageValue("admin_text").'</td>';
    $pagecontent .= "</tr>";*/
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
            $pagecontent .= "<option ".$selected."value=\"".substr($element,9,4)."\">".substr($element,9,4)." (".getLanguageValue("admin_input_translator")." ".$currentlanguage.")</option>";
        }
    }

    $pagecontent .= "</select></td></tr>";
    if($LOGINCONF->get("initialsetup") == "false") {
        // Zeile "ADMIN-MAIL"
        if($MAILFUNCTIONS->isMailAvailable())
        {
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_send_adminmail").'</td>';
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
            // Zeile "HÖHE DES TEXTFELDES"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_textarea").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="textareaheight" value="'.$ADMIN_CONF->get("textareaheight").'"'.$error_color['textareaheight'].' /></td>';
            $pagecontent .= "</tr>";
            // Zeile "BACKUP-ERINNERUNG"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_backup").'</td>';
            $pagecontent .= '<td class="td_cms_left"><input type="text" class="input_text" name="backupmsgintervall" value="'.$ADMIN_CONF->get("backupmsgintervall").'"'.$error_color['backupmsgintervall'].' /></td>';
            $pagecontent .= "</tr>";
        }
        // Zeile "SETZE DATEIRECHTE FÜR NEUE DATEIEN"
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
            // Zeile "VORHANDENE DATEIEN BEIM UPLOAD ÜBERSCHREIBEN"
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_defaultoverwrite").'</td>';
            $pagecontent .= '<td class="td_cms_left">'
            .buildCheckBox("overwriteuploadfiles", ($ADMIN_CONF->get("overwriteuploadfiles") == "true"))
            .getLanguageValue("admin_input_defaultoverwrite").'</td>';
            $pagecontent .= "</tr>";
        }
        // BILD-EINSTELLUNGEN
        if (extension_loaded("gd"))
        {
            $pagecontent .= '<tr><td class="td_cms_left">'.getLanguageValue("admin_text_imagesmax");
            $pagecontent .= "</td>";
            $pagecontent .= '<td class="td_cms_left">';
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

    // Zeile "ÜBERNEHMEN"
    if(!isset($ADMIN_CONF->properties['error'])) {
        $pagecontent .= '<tr><td class="td_cms_submit" colspan="2"><input type="submit" name="apply" class="input_submit" value="'.getLanguageValue("admin_submit").'">';
        if($LOGINCONF->get("initialsetup") == "true") {
            $pagecontent .= '&nbsp;&nbsp;&nbsp;<input type="submit" name="default_pw" class="input_submit" value="'.getLanguageValue("admin_submit_default_pw").'">';
        }
        $pagecontent .= '</td></tr>';
    }
    $pagecontent .= "</table>";
    //$pagecontent .= "</td></tr></table>";
    return array(getLanguageValue("admin_button"), $pagecontent);
}

function plugins($post) {
    global $ADMIN_CONF;
    global $CHARSET;
    global $PLUGIN_DIR_REL;
    global $icon_size;
    global $BASE_DIR_CMS;

    require_once($BASE_DIR_CMS."Plugin.php");

    $pagecontent = '<input type="hidden" name="action_activ" value="'.getLanguageValue("plugins_button").'">';

    if(getRequestParam('javascript', true)) {
        $pagecontent .= '<script type="text/javascript" src="toggle.js"></script>';
    }

    if(getRequestParam('javascript', true) and $ADMIN_CONF->get("showTooltips") == "true") {
        $tooltip_plugins_help = '<img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"'.createTooltipWZ("","plugins_help",",WIDTH,400,CLICKCLOSE,true").'>';
        $tooltip_plugins_help_edit = createTooltipWZ("","plugins_help_edit",",WIDTH,200,CLICKCLOSE,true");
    } else {
        $tooltip_plugins_help = '<a href="'.getHelpUrlForSubject("plugins").'" target="_blank"><img src="gfx/icons/'.$icon_size.'/help.png" alt="help" hspace="0" vspace="0" align="right" border="0"></a>';
        $tooltip_plugins_help_edit = NULL;
    }
 
    $pagecontent .= '<span class="titel">'.getLanguageValue("plugins_titel").'</span>';
    $pagecontent .= $tooltip_plugins_help;
    $pagecontent .= "<p>".getLanguageValue("plugins_text")."</p>";
    $pagecontent .= '<table summary="" width="100%" class="table_toggle" cellspacing="0" border="0" cellpadding="0">';
    $pagecontent .= '<tr><td width="100%" class="td_toggle"><input type="submit" class="input_submit" name="apply" value="'.getLanguageValue("plugins_submit").'"/></td></tr>';

    $dircontent = getDirs($PLUGIN_DIR_REL, true);
    $toggle_pos = 0;
    foreach ($dircontent as $currentelement) {
        if (file_exists($PLUGIN_DIR_REL.$currentelement."/index.php")) {
            require_once($PLUGIN_DIR_REL.$currentelement."/index.php");
            $plugin = new $currentelement();
            $plugin_error = false;
            if(file_exists($PLUGIN_DIR_REL.$currentelement."/plugin.conf")) {
                $conf_plugin = new Properties($PLUGIN_DIR_REL.$currentelement."/plugin.conf",true);
                $plugin_error_conf = NULL;
                if(isset($conf_plugin->properties['error'])) {
                    $plugin_error_conf = returnMessage(false, getLanguageValue("properties_write").'&nbsp;&nbsp;<span style="font-weight:normal;">'.$currentelement.'/plugin.conf</span>');
                    $plugin_error = true;
                }
            }
            if(getRequestParam('apply', true)) {
                $check_activ = "false";
                if(isset($_POST[$currentelement]['active'])) {
                    $check_activ = "true";
                }
                if($conf_plugin->get("active") != $check_activ) {
                    $conf_plugin->set("active",$check_activ);
                }
            }

            // Enthält der Code eine Klasse mit dem Namen des Plugins?
            if (class_exists($currentelement)) {
                $plugin_info = $plugin->getInfo();
                $pagecontent .= '<tr><td width="100%" class="td_toggle">';
                $pagecontent .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
                # Plugin Info Prüfen
                if(isset($plugin_info) and count($plugin_info) > 0) {
                    $plugin_name = str_replace(array("&lt;","&gt;"),array("<",">"),htmlentities(strip_tags($plugin_info[0], '<b>'),ENT_COMPAT,$CHARSET),$plugin_info[0]);
                } else {
                    $plugin_name = getLanguageValue('plugins_error').' <span style="color:#ff0000">'.$currentelement.'</span>';
                    $plugin_error = true;
                }
                $pagecontent .= '<tr><td width="70%" class="td_titel" nowrap>'.$plugin_name.$plugin_error_conf.'</td>';
                $pagecontent .= '<td width="15%" class="">'.getLanguageValue("plugins_input_active").'&nbsp;'.buildCheckBox($currentelement.'[active]', ($conf_plugin->get("active") == "true")).'</td>';
                $pagecontent .= '<td width="15%" class="td_icons">';
                if(getRequestParam('javascript', true) and $plugin_error === false) {
                    $pagecontent .= '<span id="toggle_'.$toggle_pos.'_linkBild"'.$tooltip_plugins_help_edit.'></span>';
                }
                $pagecontent .= '&nbsp;</td></tr></table>';
                $pagecontent .= '</td></tr>';

                $display_toggle = NULL;
                $messages = NULL;
                $pagecontent_conf = NULL;
                if($plugin_error === false) {
                    $pagecontent_start_conf = '<table summary="" width="98%" cellspacing="0" border="0" cellpadding="0" class="table_data">';
                    # Plugin Infos die reinvolge ist wichtig
                    $plugins_info_array = array("Plugin_name","plugins_titel_version","plugins_titel_description","plugins_titel_author","plugins_titel_web");
                    foreach($plugins_info_array as $pos => $info) {
                        # Plugin Name Brauchen wir hier nicht
                        if($pos == 0) continue;
                        if($pos == 2) {
                            $plugin_info[$pos] = str_replace(array("&lt;","&gt;"),array("<",">"),$plugin_info[$pos]);
                            $plugin_info[$pos] = strip_tags($plugin_info[$pos], '<span><br>');
                            $plugin_info[$pos] = htmlentities($plugin_info[$pos],ENT_NOQUOTES,$CHARSET);
                            $plugin_info[$pos] = str_replace(array('&amp;#',"&lt;","&gt;"),array('&#',"<",">"),$plugin_info[$pos]);
                        } elseif($pos == 4) {
                            $plugin_info[$pos] = '<a href="'.strip_tags($plugin_info[$pos]).'" target="_blank">'.strip_tags($plugin_info[$pos]).'</a>';
                        } else {
                            $plugin_info[$pos] = htmlentities(strip_tags($plugin_info[$pos]),ENT_COMPAT,$CHARSET);
                        }
                        if(isset($plugin_info[$pos])) {
                            $pagecontent_conf .= '<tr><td width="30%" valign="top" class="td_right_title_padding_bottom" nowrap><b class="text_grau">'.getLanguageValue($info).'</b></td>'
                                .'<td width="70%" class="td_togglen_padding_bottom">'.$plugin_info[$pos].'</td></tr>';
                        }
                        if($pos == 4) {
                            # Das getInfo() array hat mehr als 4 einträge wir brauchen hier aber nur die 4
                            break;
                        }
                    }

                    if(count($plugin->getConfig()) >= 1) {
//                        $pagecontent_conf .= '<tr><td width="100%" colspan="2" class="td_togglen_padding_bottom" nowrap>';
//                        $pagecontent_conf .= '<table summary="" width="100%" cellspacing="0" border="0" cellpadding="0" class="table_data">';

                        $config = $plugin->getConfig();
                        foreach($config as $name => $inhalt) {
                            $error = NULL;
                            # Änderungen schreiben isset($_POST['apply'])
                            if(getRequestParam('apply', true)) {
                                if(isset($_POST[$currentelement][$name])) {
                                    # ist array bei radio und select multi
                                    if(is_array($_POST[$currentelement][$name])) {
                                        $conf_inhalt = implode(",", trim($_POST[$currentelement][$name]));
                                    # alle die kein array sind
                                    } else {
                                        $conf_inhalt = str_replace(array("\r\n","\r","\n"),"<br />",trim($_POST[$currentelement][$name]));
                                    }
                                    if(isset($config[$name]['regex_error'])) {
                                        $regex_error = $config[$name]['regex_error'];
                                    } else {
                                        $regex_error = getLanguageValue("plugins_messages_input");
                                    }
                                    if(isset($config[$name]['regex']) and strlen($conf_inhalt) > 0) {
                                        if(preg_match($config[$name]['regex'], $conf_inhalt)) {
                                            # bei Password und verschlüsselung an
                                            if($config[$name]['type'] == "password" and $config[$name]['saveasmd5'] == "true") {
                                                $conf_inhalt = md5($conf_inhalt);
                                            }
                                            # nur in conf schreiben wenn sich der wert geändert hat
                                            if($conf_plugin->get("active") == "true" and $conf_plugin->get($name) != $conf_inhalt) {
                                                $conf_plugin->set($name,$conf_inhalt);
                                                $display_toggle = ' style="display:block;"';
                                                $messages = returnMessage(true, getLanguageValue("plugins_messages_input"));
                                            }
                                        } else {
                                            $error = ' style="background-color:#FF0000;"';
                                            $display_toggle = ' style="display:block;"';
                                            $messages = returnMessage(false, $regex_error);
                                        }
                                    } else {
                                        # nur in conf schreiben wenn sich der wert geändert hat und es kein password ist
                                        if($conf_plugin->get("active") == "true" and $conf_plugin->get($name) != $conf_inhalt and $config[$name]['type'] != "password") {
                                            $conf_plugin->set($name,$conf_inhalt);
                                            $display_toggle = ' style="display:block;"';
                                            $messages = returnMessage(true, getLanguageValue("plugins_messages_input"));
                                        }
                                   }
                                # checkbox
                                } elseif($conf_plugin->get("active") == "true" and $config[$name]['type'] == "checkbox" and $conf_plugin->get($name) == "true") {
                                    $conf_plugin->set($name,"false");
                                    $display_toggle = ' style="display:block;"';
                                    $messages = returnMessage(true, getLanguageValue("plugins_messages_input"));
                                # variable gibts also schreiben mit lehren wert
                                } elseif($conf_plugin->get("active") == "true" and $conf_plugin->get($name)) {
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
                                    $value = str_replace("<br />","\n",$conf_plugin->get($name));
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
                                            $input .= $descriptions.'&nbsp;&nbsp;<input name="'.$currentelement.'['.$name.']"'.$type.$value.$checked.'><br />';
                                        }
                                    }
                                } elseif($config[$name]['type'] == "checkbox") {
                                    $checked = NULL;
                                    if($conf_plugin->get($name) == "true") {
                                        $checked = ' checked="checked"';
                                    }
                                    $input .= '<input name="'.$currentelement.'['.$name.']"'.$type.$checked.' value="true"><br />';
                                } elseif($config[$name]['type'] == "file") {
                                    $display_toggle = ' style="display:block;"';
                                    $messages .= returnMessage(false, getLanguageValue("plugins_error_type_file"));
                                    $input = '<span style="background-color:#FF0000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
                                } else {
                                    $input = '<input name="'.$currentelement.'['.$name.']"'.$type.$value.$maxlength.$size.' class="plugin_input"'.$error.'>';
                                }
                            }
                            # Ausgeben nowrap
                            $pagecontent_conf .= '<tr><td width="30%" valign="top" class="td_right_title_padding_bottom"><b>'.$config[$name]['description'].'</b></td>';
                            $pagecontent_conf .= '<td width="70%" valign="top" class="td_togglen_padding_bottom" nowrap>'.$input.'</td></tr>';
                        }
//                        $pagecontent_conf .= '</table>';

//                        $pagecontent_conf .= '</td></tr>';
                    }
                    $pagecontent_conf .= '</table>';


                    if(getRequestParam('javascript', true)) {
                        $pagecontent_conf .= '<script type="text/javascript">window.onload = cat_togglen(\'toggle_'.$toggle_pos.'\',\'gfx/icons/'.$icon_size.'/edit.png\',\'gfx/icons/'.$icon_size.'/edit-hide.png\',\''.getLanguageValue("toggle_show").'\',\''.getLanguageValue("toggle_hide").'\');</script>';
                    }
                    if(!empty($messages)) {
                        $messages = '<tr><td colspan="2" align="left">'.$messages.'</td></tr>';
                    }
                    $pagecontent_toggle = '<tr><td width="100%" id="toggle_'.$toggle_pos.'" align="right" class="td_togglen_padding_bottom"'.$display_toggle.'>';
                    $pagecontent .= $pagecontent_toggle.$pagecontent_start_conf.$messages.$pagecontent_conf.'</td></tr>';
# conf end
                }
            }
        }
        $toggle_pos++;
    }
    $pagecontent .= '<tr><td width="100%" class="td_toggle"><input type="submit" class="input_submit" name="apply" value="'.getLanguageValue("plugins_submit").'"/></td></tr>';
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

    $file = $CONTENT_DIR_REL.$cat."/".$page;

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
    $content .= '<textarea cols="96" rows="24" style="width:99%;height:'.$height.';" name="pagecontent">'.$pagecontent.'</textarea><br />';
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
    $last_error['line'] = NULL;
    if(function_exists("error_get_last")) {
        $last_error = @error_get_last();
    }
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
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
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
    $last_error['line'] = NULL;
    if(function_exists("error_get_last")) {
        $last_error = @error_get_last();
    }
    if($last_error['line'] == $line_error) {
        $error['php_error'][] = $last_error['message'];
        # wenns hier schonn ne meldung gibt dann gleich Raus
        return $error;
    } elseif(file_exists($path)) {
        $error['check_del_dir'][] = $path;
        return $error;
    } else return;
}

// überprüfe, ob die gegebene Datei eine der übergebenen Endungen hat
function fileHasExtension($filename, $extensions) {
    foreach ($extensions as $ext) {
        if (strtolower(substr($filename, strlen($filename)-(strlen($ext)+1), strlen($ext)+1)) == ".".strtolower($ext))
        return true;
    }
    return false;
}

// Gib Erfolgs- oder Fehlermeldung zurück
function returnMessage($success, $message) {
    global $icon_size;
    if ($success === true) {
        return '<span class="message_erfolg" style="background-image:url(gfx/icons/'.$icon_size.'/information.png);padding-left:'.(substr($icon_size,0,2) + 10).'px;">'.$message.'</span>';
    } else {
        return '<span class="message_fehler" style="background-image:url(gfx/icons/'.$icon_size.'/error.png);padding-left:'.(substr($icon_size,0,2) + 10).'px;">'.$message.'</span>';
    }
}

// Smiley-Liste
function returnSmileyBar() {
    global $BASE_DIR_CMS;
    global $URL_BASE;
    global $CMS_DIR_NAME;
    $smileys = new Smileys($BASE_DIR_CMS."smileys");
    $content = "";
    foreach($smileys->getSmileysArray() as $icon => $emoticon) {
        if($icon == "readonly" or $icon == "error") {
            continue;
        }
        $content .= "<img class=\"jss\" title=\":$icon:\" alt=\"$emoticon\" src=\"".$URL_BASE.$CMS_DIR_NAME."/smileys/$icon.gif\" onClick=\"insert(' :$icon: ', '', false)\" />";
    }
    return $content;
}

// Selectbox mit allen benutzerdefinierten Syntaxelementen
function returnUserSyntaxSelectbox() {
    global $USER_SYNTAX;
    $usersyntaxarray = $USER_SYNTAX->toArray();
    ksort($usersyntaxarray);

    $content = "<select class=\"usersyntaxselectbox\" name=\"usersyntax\" onchange=\"insertTagAndResetSelectbox(this);\">"
    ."<option class=\"noaction\" value=\"\">".getLanguageValue("toolbar_usersyntax")."</option>";
    foreach ($usersyntaxarray as $key => $value) {
        if($key == "readonly") continue;
        $content .= "<option value=\"".$key."\">[".$key."|...]</option>";
    }
    $content .= "</select>";
    return $content;
}

// Selectbox mit allen benutzerdefinierten Syntaxelementen
function returnPlatzhalterSelectbox() {
    global $specialchars;

    $platzhalter_array = makePlatzhalter();
    $selectbox = '<select class="overviewselect" name="platzhalter" onchange="insertAndResetSelectbox(this);">'
    .'<option class="noaction" value="">'.getLanguageValue("toolbar_platzhalter").'</option>';
    foreach ($platzhalter_array as $value) {
        $language = str_replace(array('{','}'),'',$value);
        $selectbox .= '<option title="'.getLanguageValue("toolbar_platzhalter_".$language).'" value="'.$value.'">'.$value.'</option>';
    }
    $selectbox .= '</select>';
    return $selectbox;
}


// Selectbox mit allen Plugin Platzhaltern die nichts mit dem Template zu tun haben
function returnPluginSelectbox() {
    global $PLUGIN_DIR_REL;
    global $specialchars;
    global $BASE_DIR_CMS;

    require_once($BASE_DIR_CMS."Plugin.php");
    $plugins = getDirContentAsArray($PLUGIN_DIR_REL, false);
    $selectbox = '<select class="overviewselect" name="plugins" onchange="insertPluginAndResetSelectbox(this);">'
    .'<option class="noaction" value="">'.getLanguageValue("toolbar_plugins").'</option>';
    foreach ($plugins as $currentplugin) {
        if (file_exists($PLUGIN_DIR_REL.$currentplugin."/index.php")) {
            require_once($PLUGIN_DIR_REL.$currentplugin."/index.php");
            $plugin = new $currentplugin();
            $plugin_info = $plugin->getInfo();
            // Plugin nur in der Auswahlliste zeigen, wenn es aktiv geschaltet ist
            $plugin_conf = new Properties($PLUGIN_DIR_REL.$currentplugin."/plugin.conf",true);
            if ($plugin_conf->get("active") == "true") {
                if(isset($plugin_info[5]) and is_array($plugin_info[5])) {
                    foreach($plugin_info[5] as $platzh => $info) {
                        // wenn es vorgegebene Werte gibt: {PLUGIN|wert} 
                        /*if(strpos($platzh,'|') > 0) {
                            $info = str_replace('}',''.$info.'}',$platzh);
                            $selectbox .= '<option title="'.$specialchars->rebuildSpecialChars($info, false, true).'" value="'.str_replace('}','',$platzh).'">'.$specialchars->rebuildSpecialChars($info, false, true).'</option>';
                        }
                        // keine vorgegebenen Werte: {PLUGIN}
                        else {
                            $info = $platzh.' '.$info;
                            $selectbox .= '<option value="'.$platzh.'">'.$specialchars->rebuildSpecialChars($info, false, true).'</option>';
                        }*/
                        //$info = $platzh.' '.$info;
                        $selectbox .= '<option title="'.$specialchars->rebuildSpecialChars($info, false, true).'" value="'.$platzh.'">'.$platzh.'</option>';
                    }
                }
            }
        }
    }
    $selectbox .= "</select>";
    return $selectbox;
}


function returnFormatToolbar($currentcat) {
    global $CMS_CONF;
    global $USER_SYNTAX;

    // Information zeigen, wenn JavaScript nicht aktiviert
    $content = "<noscript>".returnMessage(false,getLanguageValue("toolbar_nojs_text"))."</noscript>"
    .'<table summary="" width="99%" cellspacing="2" border="0" cellpadding="0">'
    ."<tr>"
    // Überschrift Syntaxelemente
    ."<td width=\"59%\" nowrap>"
    .getLanguageValue("toolbar_syntaxelements")
    ."</td>"
    // Überschrift Textformatierung
    ."<td width=\"29%\" nowrap>"
    .getLanguageValue("toolbar_textformatting")
    ."</td>"
    // Überschrift Farben
    ."<td width=\"11%\" nowrap>"
    .getLanguageValue("toolbar_textcoloring")
    ."</td>"
    ."</tr>"
    ."<tr>"
    // Syntaxelemente
    ."<td nowrap>"
    .returnFormatToolbarIcon("link")
    .returnFormatToolbarIcon("mail")
    .returnFormatToolbarIcon("seite")
    .returnFormatToolbarIcon("kategorie")
    .returnFormatToolbarIcon("datei")
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
    ."<td nowrap>"
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
    ."<td nowrap>"
    .'<table summary="" cellspacing="0" border="0" cellpadding="0"><tr><td>'
    ."<img class=\"js\" style=\"background-color:#AA0000\" alt=\"Farbe\" id=\"farbicon\" title=\"[farbe=RRGGBB| ... ] - ".getLanguageValue("toolbar_desc_farbe")."\" src=\"gfx/jsToolbar/farbe.png\" onClick=\"insert('[farbe=' + document.getElementById('farbcode').value + '|', ']', true)\">"
    ."</td><td nowrap>"
    ."<div class=\"colordiv\">"
    ."<input type=\"text\" readonly=\"readonly\" maxlength=\"6\" value=\"AA0000\" class=\"colorinput\" id=\"farbcode\" size=\"0\">"
    ."<img class=\"colorimage\" src=\"js_color_picker_v2/images/select_arrow.gif\" onmouseover=\"this.src='js_color_picker_v2/images/select_arrow_over.gif'\" onmouseout=\"this.src='js_color_picker_v2/images/select_arrow.gif'\" onclick=\"showColorPicker(this,document.getElementById('farbcode'))\" alt=\"...\" title=\"Farbauswahl\" />"
    ."</div>"
    ."</td></tr></table>"
    ."</td>"
    ."</tr>"
    ."</table>"
    .'<table summary="" width="99%" cellspacing="2" border="0" cellpadding="0">'
    ."<tr>";

    // Benutzerdefinierte Syntaxelemente vorbereiten
    $usersyntaxarray = $USER_SYNTAX->toArray();

    // Überschrift Inhalte
    $content .=    "<td width=\"66%\" colspan=\"3\">"
    .getLanguageValue("toolbar_contents")
    ."</td>";
    // Überschrift Benutzerdefinierte Syntaxelemente
    if (count($usersyntaxarray) > 0) {
        $content .=    "<td width=\"33%\">"
        .getLanguageValue("toolbar_usersyntax")
        ."</td>";
    }
    $content .= "</tr>"
    ."<tr>"
    // Inhalte
    ."<td width=\"22%\">".returnOverviewSelectbox(1, $currentcat)."</td>"
    ."<td width=\"22%\">".returnOverviewSelectbox(2, $currentcat)."</td>"
    ."<td width=\"22%\">".returnOverviewSelectbox(3, $currentcat)."</td>";
    // Benutzerdefinierte Syntaxelemente
    if (count($usersyntaxarray) > 0) {
        $content .=    "<td>"
        .returnUserSyntaxSelectbox()
        ."</td>";
    }
    $content .=     "</tr>"
    ."</table>";

    // Smileys
    if ($CMS_CONF->get("replaceemoticons") == "true") {
        $content .= '<table summary="" width="33%" cellspacing="2" border="0" cellpadding="0"><tr><td nowrap>'.returnSmileyBar().'</td></tr></table>';
    }
    # Plugins
    $content .= '<table summary="" width="66%" cellspacing="2" border="0" cellpadding="0">'
                .'<tr><td width="66%" colspan="2">'.getLanguageValue("toolbar_platzhalter").'</td></tr>'
                .'<tr><td width="33%">'.returnPluginSelectbox().'</td>'
                .'<td width="33%">'.returnPlatzhalterSelectbox().'</td>'
                .'</tr></table>';

    return $content;
}

// Rueckgabe eines Standard-Formatsymbolleisten-Icons
function returnFormatToolbarIcon($tag) {
    return "<img class=\"js\" alt=\"$tag\" title=\"[$tag| ... ] - ".getLanguageValue("toolbar_desc_".$tag)."\" src=\"gfx/jsToolbar/".$tag.".png\" onClick=\"insert('[".$tag."|', ']', true)\">";
}


// Rueckgabe einer Selectbox mit Elementen, die per Klick in die Inhaltsseite uebernommen werden können
// $type: 1=Kategorien & nhaltsseiten 2=Dateien 3=Galerien
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
                $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), true, true);
                $elements[] = array($cleancatname, ":".$cleancatname);
                $files = getFiles($CONTENT_DIR_REL.$catdir, $EXT_LINK);
                natcasesort($files);
                foreach($files as $file) {
                    if ((substr($file, strlen($file)-4, 4) == $EXT_PAGE) || (substr($file, strlen($file)-4, 4) == $EXT_HIDDEN)) {
                        $cleanpagename = $specialchars->rebuildSpecialChars(substr($file, 3, strlen($file) - 3 - strlen($EXT_PAGE)), true, true);
                        $completepagename = $cleanpagename;
                        if (substr($file, strlen($file)-4, 4) == $EXT_HIDDEN)
                            $completepagename = $cleanpagename." (".getLanguageValue("page_saveashidden").")";
                        if ($catdir == $currentcat)
                            $elements[] = array($spacer.$completepagename, $cleanpagename);
                        else
                            $elements[] = array($spacer.$completepagename, $cleancatname.":".$cleanpagename);
                    }
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
                $cleancatname = $specialchars->rebuildSpecialChars(substr($catdir, 3, strlen($catdir)), true, true);
                $elements[] = array($cleancatname, ":".$cleancatname);
                $currentcat_filearray = getFiles($CONTENT_DIR_REL.$catdir."/dateien","");
                natcasesort($currentcat_filearray);
                foreach ($currentcat_filearray as $current_file) {
                    if ($catdir == $currentcat)
                        $elements[] = array($spacer.$specialchars->rebuildSpecialChars($current_file, true, true), $specialchars->rebuildSpecialChars($current_file, true, true));
                    else
                        $elements[] = array($spacer.$specialchars->rebuildSpecialChars($current_file, true, true), $cleancatname.":".$specialchars->rebuildSpecialChars($current_file, true, true));
                }
            }
            $selectname = "files";
            break;

        // Galerien
        case 3:
            $galleries = getDirContentAsArray($GALLERIES_DIR_REL, false);
            foreach ($galleries as $currentgallery) {
                $elements[] = array($specialchars->rebuildSpecialChars($currentgallery, false, true), $specialchars->rebuildSpecialChars($currentgallery, false, false));
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
        } else {
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
        // Keys mit zu änderndem Kategorienamen: im Array ändern
        $keyparts = explode(":", $key);
        if ($keyparts[0] == $oldcatname) {
            $downloadsarray[$newcatname.":".$keyparts[1]] = $value; // Element mit neuem Key ans Array hängen
            unset($downloadsarray[$key]);                            // Element mit altem Key aus Array löschen
        }
    }
    // bearbeitetes Array wieder zurueck in die Download-Statistik schreiben
    $DOWNLOAD_COUNTS->setFromArray($downloadsarray);
}

// Überschreibt die layoutabhängigen CMS-Einstellungen usesubmenu und gallerypicsperrow
function setLayoutAndDependentSettings($layoutfolder) {
    global $CMS_CONF;
    global $GALLERY_CONF;

    $settingsfile = "../layouts/$layoutfolder/layoutsettings.conf";

    // Einstellungen aus Layout-Settings laden und in den CMS-Einstellungen ueberschreiben
    $layoutsettings = new Properties($settingsfile);
    if(!isset($layoutsettings->properties['readonly'])) {
        return $layoutsettings->properties;
    } else {
        $CMS_CONF->set("usesubmenu", $layoutsettings->get("usesubmenu"));
        $GALLERY_CONF->set("gallerypicsperrow", $layoutsettings->get("gallerypicsperrow"));
        if(isset($layoutsettings->properties['usesubmenu']))
            return $layoutsettings->properties['usesubmenu'];
    }
}

// Hochgeladene Datei ueberpruefen und speichern
function uploadFile($uploadfile, $destination, $forceoverwrite,$MAX_IMG_WIDTH,$MAX_IMG_HEIGHT,$image = false){
    global $ADMIN_CONF;
    global $specialchars;
    global $BASE_DIR_CMS;

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
        // Datei vorhanden und "Überschreiben"-Checkbox nicht aktiviert
        elseif (file_exists($destination.$uploadfile_name) && ($forceoverwrite != "on")) {
            return array("files_error_exists" => $uploadfile_name);
        }
        // alles okay, hochladen!
        else {
            move_uploaded_file($uploadfile['tmp_name'], $destination.$uploadfile_name);
            $line_error = __LINE__ - 1;
            $last_error['line'] = NULL;
            if(function_exists("error_get_last")) {
                $last_error = @error_get_last();
            }
            if($last_error['line'] == $line_error) {
                return array("php_error" => $last_error['message']);
            } elseif(!is_file($destination.$uploadfile_name)) {
                return array("files_error_upload" => $uploadfile_name);
            }
            // chmod, wenn so eingestellt
            useChmod($destination.$uploadfile_name);#maximagewidth maximageheight
            if(!empty($MAX_IMG_WIDTH) or !empty($MAX_IMG_HEIGHT) and $image) {
                // Bilddaten feststellen
                $size = getimagesize($destination.$uploadfile_name);
                // Mimetype herausfinden
                $image_typ = strtolower(str_replace('image/','',$size['mime']));
                # nur wenns ein bild ist
                if($image_typ == "gif" or $image_typ == "png" or $image_typ == "jpeg") {
                    require_once($BASE_DIR_CMS."Image.php");
                    scaleImage($uploadfile_name, $destination, $destination, $MAX_IMG_WIDTH, $MAX_IMG_HEIGHT);
                } else {
                    return array("files_error_no_image" => $uploadfile_name);
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
    
// ------------------------------------------------------------------------------
// Hilfsfunktion: Baut für das übergebene Thema den URL zur Hilfe zusammen
// ------------------------------------------------------------------------------
    function getHelpUrlForSubject($subject) {
        // Das könnte später noch mehrsprachig erweitert werden, wenn anderssprachige Dokus existieren
        return "http://cms.mozilo.de/hilfe/?thema=".$subject;
    }

?>