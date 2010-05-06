<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

// Session starten!
session_start();

if(!isset($BASE_DIR)) {
    $CMS_DIR_NAME = "cms";
    $ADMIN_DIR_NAME = "admin";
#    $BASE_DIR = str_replace($ADMIN_DIR_NAME,"",getcwd());
    $BASE_DIR = substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],$ADMIN_DIR_NAME));
    $BASE_DIR_ADMIN = $BASE_DIR.$ADMIN_DIR_NAME."/";
    $BASE_DIR_CMS = $BASE_DIR.$CMS_DIR_NAME."/";
}

if(is_file($BASE_DIR_CMS."DefaultConf.php")) {
    require_once($BASE_DIR_CMS."DefaultConf.php");
} else {
    die("Fatal Error ".$BASE_DIR_CMS."DefaultConf.php Datei existiert nicht");
}

$_GET = cleanREQUEST($_GET);
$_REQUEST = cleanREQUEST($_REQUEST);
$_POST = cleanREQUEST($_POST);

require_once($BASE_DIR_ADMIN."Crypt.php");
require_once($BASE_DIR_CMS."Mail.php");
require_once($BASE_DIR_ADMIN."filesystem.php");


// Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
#@ini_set("display_errors", 0);

// Initialisierungen
$logindataconf = new Properties($BASE_DIR_ADMIN."conf/logindata.conf");
if(!isset($logindataconf->properties['readonly'])) {
    die($logindataconf->properties['error']);
}
$VERSION_CONF    = new Properties($BASE_DIR_CMS."conf/version.conf");
if(!isset($VERSION_CONF->properties['readonly'])) {
    die($VERSION_CONF->properties['error']);
}

$ADMIN_CONF = new Properties($BASE_DIR_ADMIN."conf/basic.conf");
if(!isset($ADMIN_CONF->properties['readonly'])) {
    die($ADMIN_CONF->properties['error']);
}
$pwcrypt = new Crypt();
$BASIC_LANGUAGE = new Properties($BASE_DIR_ADMIN."sprachen/language_".$ADMIN_CONF->get("language").".conf");

// MAXIMALE ANZAHL FALSCHER LOGINS
$FALSELOGINLIMIT = 3;
// DAUER DER SPERRE NACH FALSCHEN LOGINS IN MINUTEN
$LOGINLOCKTIME = 10;


// Überprüfen: Existiert ein Benutzer? Wenn nicht: admin:install anlegen
if (($logindataconf->get("name") == "") || ($logindataconf->get("pw") == "")) {
    $logindataconf->set("name", "admin");
    $logindataconf->set("pw", $pwcrypt->encrypt("install"));
    $logindataconf->set("initialpw", "true");
}

$HTML = "<!doctype html public \"-//W3C//DTD HTML 4.0 //EN\"><html>";
// User hat sich ausgeloggt
if (isset($_POST['logout']) or isset($_POST['logout_x'])) {
    // Session beenden und die Sessiondaten löschen
    session_destroy();
    unset($_SESSION);
}

// Wurde das Anmeldeformular verschickt?
if  (isset($_POST['login'])) {
    // Zugangsdaten prüfen
        if (checkLoginData($_POST['username'], $_POST['password'])) {
            // Daten in der Session merken
      $_SESSION['username'] = $_POST['username'];
      $_SESSION['login_okay'] = true;
    }
}

// Anmeldung erfolgreich
if (isset($_SESSION['login_okay']) and $_SESSION['login_okay']) {
    // Counter für falsche Logins innerhalb der Sperrzeit zurücksetzen
    $logindataconf->set("falselogincounttemp", 0);
    // ...ab in den Admin!
    header("location:index.php");
}

// Anmeldung fehlerhaft
elseif  (isset($_POST['login'])) {
    // Counter hochzählen
    $falselogincounttemp = ($logindataconf->get("falselogincounttemp"))+1;
    $logindataconf->set("falselogincounttemp", $falselogincounttemp); // Zähler für die aktuelle Sperrzeit
    $falselogincount = ($logindataconf->get("falselogincount"))+1;
    $logindataconf->set("falselogincount", $falselogincount); // Gesamtzähler
    $icon_size = "24x24";
    $HTML .= "<head>"
        ."<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" />"
        ."<title>".getLanguageValue("incorrect_login")."</title>"
        ."</head>"
        ."<body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\" >"
        .'<div class="message_fehler" style="background-image:url(gfx/icons/'.$icon_size.'/error.png);padding-left:'.(substr($icon_size,0,2) + 10).'px;">'.getLanguageValue("incorrect_login")."</div>";
    // maximale Anzahl falscher Logins erreicht?
    if ($falselogincounttemp >= $FALSELOGINLIMIT) {
        // Sperrzeit starten
        $logindataconf->set("loginlockstarttime", time());
        // Mail an Admin
        if ($ADMIN_CONF->get("sendadminmail") == "true") {
            $mailcontent = getLanguageValue("loginlocked_mailcontent")."\r\n\r\n"
                .strftime(getLanguageValue("_dateformat"), time())."\r\n"
                .$_SERVER['REMOTE_ADDR']." / ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."\r\n"
                .getLanguageValue("username").": ".$_POST['username'];
                
                // Prüfen, ob die Mail-Funktion vorhanden ist
                if(isMailAvailable())
                {
                    sendMailToAdmin(getLanguageValue("loginlocked_mailsubject"), $mailcontent);
                }
        }
        // Formular ausgrauen
        $HTML .= login_formular(false);
    }
    else {
        // Formular nochmal normal anzeigen
        $HTML .= login_formular(true);
    }
}

// Formular noch nicht abgeschickt? Dann wurde die Seite zum ersten Mal aufgerufen.
else {
    $HTML .= "<head>"
        ."<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\">"
        ."<title>".getLanguageValue("loginplease")."</title>"
        ."</head>"
        ."<body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\">";
        // Login noch gesperrt?
        if (($logindataconf->get("falselogincounttemp") > 0) and (time() - $logindataconf->get("loginlockstarttime")) <= $LOGINLOCKTIME * 60) {
#        if (($logindataconf->get("falselogincounttemp") > 0) && (time() - $logindataconf->get("loginlockstarttime")) <= $LOGINLOCKTIME*60) {
            // gesperrtes Formular anzeigen
            $HTML .= login_formular(false);
        } else {
            // Zähler zurücksetzen
            $logindataconf->set("falselogincounttemp", 0);
            // normales Formular anzeigen
            $HTML .= login_formular(true);
        }
} 

$HTML .= "</body></html>";

echo $HTML;

// Aufbau des Login-Formulars
function login_formular($enabled) {
    global $CHARSET;
  if ($enabled)
        $form .= "<div id=\"loginform_maindiv\">";
    else
        $form .= "<div id=\"loginform_maindiv_disabled\">";
    if ($enabled)
        $form .= "<form accept-charset=\"$CHARSET\" name=\"loginform\" action=\"".htmlentities($_SERVER['PHP_SELF'],ENT_COMPAT,$CHARSET)."\" method=\"POST\">";
  $form .= "<table id=\"table_loginform\" width=\"100%\" cellspacing=\"10\" border=\"0\" cellpadding=\"0\">"
      ."<tr>"
      ."<td width=\"5%\" rowspan=\"2\" align=\"center\" valign=\"middle\">"
      ."<img src=\"gfx/login.gif\" alt=\"Login\"/>"
      ."</td>"
      ."<td width=\"5%\" class=\"description\">"
      .getLanguageValue("username").":"
      ."</td>"
      ."<td>";
  if ($enabled)
        $form .= "<input type=\"text\" name=\"username\" size=\"15\" maxlength=\"20\" class=\"login_input\">";
    else
        $form .= "<input class=\"login_input\" type=\"text\" size=\"15\" name=\"username\" readonly=\"readonly\">";
  $form .= "</td>"
      ."</tr>"
      ."<tr>"
      ."<td class=\"description\">"
      .getLanguageValue("password").":"
      ."</td>"
      ."<td>";
  if ($enabled)
        $form .= "<input class=\"login_input\" size=\"15\" maxlength=\"20\" type=\"password\" name=\"password\">";
    else
        $form .= "<input class=\"login_input\" size=\"15\" type=\"password\" name=\"password\" readonly=\"readonly\">";
  $form .= "</td>"
      ."</tr>"
      ."<tr>"
      ."<td colspan=\"3\" style=\"text-align: center;\">";
  if ($enabled)
      $form .= "<input name=\"login\" value=\"Login\" class=\"login_submit\" type=\"submit\">";
  else
      $form .= "<input name=\"login\" value=\"Login\" class=\"login_submit\" type=\"submit\" readonly=\"readonly\">";
  $form .= "</td>"
      ."</tr>"
      ."</table>";
  if ($enabled)
      $form .= "</form>";
    $form .= "</div>";
    return $form;
}

// Logindaten überprüfen
function checkLoginData($user, $pass)
{
    global $logindataconf;
    global $pwcrypt;
    if ( ($user == $logindataconf->get("name")) and ($pwcrypt->encrypt($pass) == $logindataconf->get("pw")) )
    {
        return true;
    } else {
        return false;
    }
}

?>