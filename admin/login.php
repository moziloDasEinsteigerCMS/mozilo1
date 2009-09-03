<?php
require_once("Crypt.php");
require("filesystem.php");
// Wichtig: Die Session muss gestartet werden
// bevor die erste Textausgabe an den Browser
// erfolgt ist. Daher am Besten immer als ersten
// Befehl einfügen
session_start();

// Überprüfen: Existiert ein Benutzer? Wenn nicht: admin:install anlegen
$adminconf = new Properties("conf/logindata.conf");
$pwcrypt = new Crypt();
if (($adminconf->get("name") == "") || ($adminconf->get("pw") == "")) {
	$adminconf->set("name", "admin");
	$adminconf->set("pw", $pwcrypt->encrypt("install"));
	$adminconf->set("initialpw", "true");
}

$HTML = "<!doctype html public \"-//W3C//DTD HTML 4.0 //EN\"><html>";

// für den Logout-Link wird der Parameter über die URL übergeben
// daher wird hier $_GET abgefragt
if (isset($_GET['logout']))
{
    // Session beenden und die Sessiondaten löschen
    session_destroy();
    // Auch bei zerstörter Session, ist die Variable
    // $_SESSION noch vorhanden, bis die Seite im Browser
    // neu angezeigt wird. Daher muss auch diese Variable
    // gesondert zerstört werden.
    unset($_SESSION);
}

// Wurde das Anmeldeformular verschickt?
// Dann die Zugangsdaten in der Funktion check_login() prüfen
if  (isset($_POST['login'])) {
		if (check_login($_POST['username'], $_POST['password']))
    {
        // Bei erfolgreichem Zugang, die Daten in der
        // Session speichern. Das Passwort sollte nie
        // in der Session gespeichert werden, nur
        // der Benutzername und die erfolgreiche Anmeldung
        // an sich werden hier abgelegt.
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['login_okay'] = true;
    }
}

// war die Anmeldung schon erfolgreich?
// Dann einen Willkommensgruß anzeigen
if (isset($_SESSION['login_okay']) and $_SESSION['login_okay'])
	header("location:index.php");
elseif  (isset($_POST['login'])) {
	$HTML .= "<head><link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" /><title>Login fehlgeschlagen</title></head><body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\" ><div class=\"fehler\">".getlanguagevalue("incorrect_login")."</div>".login_formular();
	
// Keine erfolgreiche Anmeldung und noch kein
// Formular versandt? Dann wurde die Seite
// zum ersten Mal aufgerufen.
} else {
	$HTML .= "<head><link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" /><title>Login - bitte anmelden</title></head><body onLoad=\"document.loginform.username.focus();document.loginform.username.select()\" >".login_formular();
} // if-else Ende

$HTML .= "</body></html>";

echo $HTML;

// Die Funktion login_formular() zeigt das Formular mit
// den Eingabefeldern an. Da dieses Formular oben zweimal
// benötigt wurde (beim ersten Aufruf und bei fehlerhafter
// Anmeldung), wird es in eine Funktion gepackt, die man
// leicht mehrmals verwenden kann.
function login_formular()
{
	$form = "<div id=\"mozilo_Logo\"></div>";
	$form .= "<div class=\"loginform_shadowdiv\"></div>";
	$form .= "<div class=\"loginform_maindiv\">";
	$form .= "<form name=\"loginform\" action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">";
  $form .= "<table>";
  $form .= "<tr>";
  $form .= "<td class=\"loginImage\" rowspan=\"2\">";
  $form .= "<img src=\"gfx/login.gif\" alt=\"Login\"/>";
  $form .= "</td>";
  $form .= "<td class=\"description\">";
  $form .= getLanguageValue("username").":";
  $form .= "</td>";
  $form .= "<td>";
  $form .= "<input class=\"text2\" type=\"text\" name=\"username\">";
  $form .= "</td>";
  $form .= "</tr>";
  $form .= "<tr>";
  $form .= "<td class=\"description\">";
  $form .= getLanguageValue("password").":";
  $form .= "</td>";
  $form .= "<td>";
  $form .= "<input class=\"text2\" type=\"password\" name=\"password\">";
  $form .= "</td>";
  $form .= "</tr>";
  $form .= "<tr>";
  $form .= "<td colspan=\"3\" style=\"text-align: center;\"><input name=\"login\" value=\"Login\" class=\"submit\" type=\"submit\"></td>";
  $form .= "</tr>";
  $form .= "</table>";
  $form .= "</form>";
	$form .= "</div>";
	return $form;
}

// Die Funktion check_login prüft Benutzername und Passwort.
// Diese Funktion könnte man später mit weiteren Zugangsdaten
// erweitern. Am Besten wäre es, die Zugangsdaten aus
// einer Datenbank zu holen, da man hier die Benutzer
// flexibel verwalten kann, ohne jedes Mal den Code
// zu ändern.
function check_login($user, $pass)
{
	global $adminconf;
	global $pwcrypt;
    if ( ($user == $adminconf->get("name")) and ($pwcrypt->encrypt($pass) == $adminconf->get("pw")) )
    {
        return true;
    } else {
        return false;
    }
}
// Copyright 2004 Marian Heddesheimer, 23562 Lübeck

