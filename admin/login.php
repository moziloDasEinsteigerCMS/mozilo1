<?php

/* 
* 
* $Revision: 115 $
* $LastChangedDate: 2009-01-27 21:14:39 +0100 (Di, 27 Jan 2009) $
* $Author: arvid $
*
*/


require_once("Crypt.php");
require_once("../Mail.php");
require("filesystem.php");

// Session starten!
session_start();

// Initialisierungen
$logindataconf = new Properties("conf/logindata.conf");
$basicconf = new Properties("conf/basic.conf");
$pwcrypt = new Crypt();
$mailfunctions = new Mail(true);

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
if (isset($_GET['logout'])) {
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
	$HTML .= "<head>"
		."<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" />"
		."<title>".getlanguagevalue("incorrect_login")."</title>"
		."</head>"
		."<body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\" >"
		."<div class=\"fehler\">".getlanguagevalue("incorrect_login")."</div>";
	// maximale Anzahl falscher Logins erreicht?
	if ($falselogincounttemp >= $FALSELOGINLIMIT) {
		// Sperrzeit starten
		$logindataconf->set("loginlockstarttime", time());
		// Mail an Admin
		if ($basicconf->get("sendadminmail") == "true") {
			$mailcontent = getLanguageValue("loginlocked_mailcontent")."\r\n\r\n"
				.strftime(getLanguageValue("_dateformat"), time())."\r\n"
				.$_SERVER['REMOTE_ADDR']." / ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."\r\n"
				.getLanguageValue("username").": ".$_POST['username'];
				
				// Prüfen ob die Mail-Funktion vorhanden ist
				if($mailfunctions->isMailAvailable())
				{
					$mailfunctions->sendMailToAdmin(getLanguageValue("loginlocked_mailsubject"), $mailcontent);
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
		."<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" />"
		."<title>".getlanguagevalue("loginplease")."</title>"
		."</head>"
		."<body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\" >";
		
		// Login noch gesperrt?
		if (($logindataconf->get("falselogincounttemp") > 0) && (time() - $logindataconf->get("loginlockstarttime")) <= $LOGINLOCKTIME*60) {
			// gesperrtes Formular anzeigen
			$HTML .= login_formular(false);
		}
		else {
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
	$form = "<div id=\"mozilo_Logo\"></div>"
		."<div id=\"loginform_shadowdiv\"></div>";
  if ($enabled)
		$form .= "<div id=\"loginform_maindiv\">";
	else
		$form .= "<div id=\"loginform_maindiv_disabled\">";
	if ($enabled)
		$form .= "<form accept-charset=\"ISO-8859-1\" name=\"loginform\" action=\"".htmlentities($_SERVER['PHP_SELF'])."\" method=\"POST\">";
  $form .= "<table>"
  	."<tr>"
  	."<td class=\"loginImage\" rowspan=\"2\">"
  	."<img src=\"gfx/login.gif\" alt=\"Login\"/>"
  	."</td>"
  	."<td class=\"description\">"
  	.getLanguageValue("username").":"
  	."</td>"
  	."<td>";
  if ($enabled)
		$form .= "<input class=\"text2\" type=\"text\" name=\"username\">";
	else
		$form .= "<input class=\"text2\" type=\"text\" name=\"username\" readonly=\"readonly\">";
  $form .= "</td>"
  	."</tr>"
  	."<tr>"
  	."<td class=\"description\">"
  	.getLanguageValue("password").":"
  	."</td>"
  	."<td>";
  if ($enabled)
		$form .= "<input class=\"text2\" type=\"password\" name=\"password\">";
	else
		$form .= "<input class=\"text2\" type=\"password\" name=\"password\" readonly=\"readonly\">";
  $form .= "</td>"
  	."</tr>"
  	."<tr>"
  	."<td colspan=\"3\" style=\"text-align: center;\">";
  if ($enabled)
  	$form .= "<input name=\"login\" value=\"Login\" class=\"submit\" type=\"submit\">";
  else
  	$form .= "<input name=\"login\" value=\"Login\" class=\"submit\" type=\"submit\" readonly=\"readonly\">";
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