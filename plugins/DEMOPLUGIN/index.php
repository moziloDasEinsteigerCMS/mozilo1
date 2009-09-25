<?php

/***************************************************************
* Demo-Plugin für moziloCMS.
* 
* Folgende Funktionen muß jedes Plugin enthalten:
* 
* get_PLUGINNAME_content($value)
*   -> gibt die HTML-Ersetzung der Plugin-Variable zurück.
*   -> der String-Parameter ist Pflicht
* 
* get_PLUGINNAME_config()
*   -> gibt den HTML-Code zum Einfügen der Plugin-Settings im Admin zurück.
* 
* get_PLUGINNAME_info()
*   -> gibt ein Array von Plugin-Infos zurück (in dieser Reihenfolge):
*      - Name des Plugins
*      - Version des Plugins
*      - Kurzbeschreibung
*      - Name des Autors
*      - Download-URL
* 
***************************************************************/



/***************************************************************
* 
* Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt wird.
* 
***************************************************************/
function get_DEMOPLUGIN_content($value) {

    /***************************************************************
    * Es kann auf sämtliche Variablen und Funktionen der index.php 
    * zugegriffen werden.
    * 
    * Der Wert, mit dem die Plugin-Variable ersetzt werden soll, muß
    * per "return" zurückgegeben werden.
    * 
    * Der String-Parameter $value ist der Wert bei erweiterten
    * Plugin-Variablen: {VARIABLE|wert}
    * Ist die Variable nicht erweitert ( {VARIABLE} ), wird $value
    * als Leerstring ("") übergeben.
    * Man kann den $value-Parameter nutzen, muß es aber nicht.
    * 
    * Beispiele:
    ***************************************************************/


    // Nutzung des Parameters mit mehreren kommaseparierten Werten
    // (werden in das Array $values gepackt)
    // - Nutzung: {DEMOPLUGIN|Wert1,Wert2,Wert3,...}
    // - Ausgabe: Der erste Wert ist Wert1
    $values = explode(",", $value);
    // return ("Der erste Wert ist ".$values[0]); // zum Testen entkommentieren!


    // Nutzung des Parameters mit CMS-Variablen - Namen aktueller 
    // Inhaltsseite in Großbuchstaben zurückgeben:
    // - Nutzung: {DEMOPLUGIN|{PAGE_NAME}}
    // return (strtoupper($value)); // zum Testen entkommentieren!


    // Auslesen des Website-Titels aus der CMS-Konfiguration:
    global $mainconfig;
    $titelderseite = $mainconfig->get("websitetitle");
    // return $titelderseite; // zum Testen entkommentieren!


    // Aufruf der Funktion, die das Hauptmenü erstellt:
    $hauptmenue = getMainMenu();
    // return $hauptmenue; // zum Testen entkommentieren!


    // Sicheres Auslesen eines übergebenen POST- bzw. GET-Parameters:
    $anfrage = getRequestParam("parameter", true);
    // return $anfrage; // zum Testen entkommentieren!


    // Tageszeitabhängige Begrüßung:
    $stunde = date("H");
    if ($stunde <= 10) {
        $begruessung ="Guten Morgen!";
    }
    else if ($stunde <= 16) {
        $begruessung ="Guten Tag!";
    }
    else {
        $begruessung ="Guten Abend!";
    }
    return $begruessung; // zum Testen entkommentieren!
}



/***************************************************************
* 
* Gibt den HTML-Code für die Plugin-Settings im Admin zurück.
* 
***************************************************************/
function get_DEMOPLUGIN_config() {
    return "Keine Einstellungen möglich bzw. nötig. No settings available or required.";
}



/***************************************************************
* 
* Gibt die Plugin-Infos als Array zurück.
* 
***************************************************************/
function get_DEMOPLUGIN_info() {
    return array(
        // Plugin-Name
        "Plugin-Demo",
        // Plugin-Version
        "1.0",
        // Kurzbeschreibung
        "Beispiel-Plugin, das die Möglichkeiten des Plugin-Systems von moziloCMS aufzeigt",
        // Name des Autors
        "mozilo",
        // Download-URL
        "http://cms.mozilo.de"
        );
}



?>