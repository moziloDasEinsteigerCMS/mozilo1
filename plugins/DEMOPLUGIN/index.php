<?php

/***************************************************************
* 
* Demo-Plugin für moziloCMS.
* 
* 
* Jedes moziloCMS-Plugin muß...
* - als Verzeichnis [PLUGINNAME] unterhalb von "plugins" liegen.
* - im Pluginverzeichnis eine plugin.conf mit den Plugin-
*   Einstellungen enthalten (diese kann auch leer sein).
* - eine index.php enthalten, in der eine Klasse "[PLUGINNAME]" 
*   definiert ist.
* 
* Die Plugin-Klasse muß...
* - von der Klasse "Plugin" erben ("class [PLUGINNAME] extends Plugin")
* - folgende Funktionen enthalten:
*   getContent($value)
*       -> gibt die HTML-Ersetzung der Plugin-Variable zurück
*   getConfig()
*       -> gibt die Konfigurationsoptionen als Array zurück
*   getInfo()
*       -> gibt die Plugin-Infos als Array zurück
* 
***************************************************************/
class DEMOPLUGIN extends Plugin {


	/***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird. Der String-Parameter $value ist Pflicht, kann aber leer 
    * sein.
    * 
    ***************************************************************/
    function getContent($value) {
    
        /***************************************************************
        * 
        * Details (Beispiele folgen weiter unten):
        * 
        * Es kann auf sämtliche Variablen und Funktionen der index.php 
        * zugegriffen werden.
        *
        * Über die lokale Variable $this->settings hat man Zugriff auf 
        * die Werte der Plugin-eigenen plugin.conf.
        * 
        * Der Wert, mit dem die Plugin-Variable letztenendes ersetzt 
        * werden soll, muß per "return" zurückgegeben werden.
        * 
        * Der String-Parameter $value entspricht dem Wert bei 
        * erweiterten Plugin-Variablen: {VARIABLE|wert}
        * Ist die Variable nicht erweitert ( {VARIABLE} ), wird $value
        * als Leerstring ("") übergeben.
        * Man kann den $value-Parameter nutzen, muß es aber nicht.
        * 
        ***************************************************************/
        

        /***************************************************************
        * Beispiel: Zugriff auf Werte aus der plugin.conf über das 
        * lokale Properties-Objekt $this->settings
        ***************************************************************/

        // Lesend: Der Wert des Schlüssels "demosetting" wird aus der plugin.conf ausgelesen
        // return $this->settings->get("demosetting"); // zum Testen entkommentieren!
        // Schreibend: Die aktuelle Unixtime wird als "timestring" in die plugin.conf geschrieben ("timestring = 1234567890")
        // $this->settings->set("timestring", time()); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Nutzung des Parameters $value 
        * - Nutzung: {DEMOPLUGIN|moziloCMS rockt!}
        * - Ausgabe: Yeah! moziloCMS rockt!
        ***************************************************************/
    	
        // return ("Yeah! ".$value); // zum Testen entkommentieren!

    	
    	/***************************************************************
        * Beispiel: Nutzung des Parameters $value (der mehrere 
        * kommaseparierte Werte enthält)
        * - Nutzung: {DEMOPLUGIN|Wert1,Wert2,Wert3,...}
        * - Ausgabe: Der erste Wert ist Wert1
        ***************************************************************/
        
    	$values = explode(",", $value);
        // return ("Der erste Wert ist ".$values[0]); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Nutzung des Parameters $value mit CMS-Variablen 
        * - Nutzung: {DEMOPLUGIN|{PAGE_NAME}}
        * - Ausgabe (Bsp.): WILLKOMMEN
        ***************************************************************/
        
        // Namen aktueller Inhaltsseite in Großbuchstaben zurückgeben
        // return (strtoupper($value)); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Auslesen des Website-Titels aus der CMS-
        * Konfiguration 
        ***************************************************************/

    	global $mainconfig; // eine globale Variable der index.php!
        // return $mainconfig->get("websitetitle"); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Anzeige des Hauptmenüs (durch Aufruf der Funktion 
        * der index.php, die das Hauptmenü erstellt) 
        ***************************************************************/
        
        // return getMainMenu(); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Sicheres Auslesen des POST- bzw. GET-Parameters 
        * "parameter" durch Aufruf der entsprechenden Hilfsfunktion der 
        * index.php 
        ***************************************************************/

        // return getRequestParam("parameter", true); // zum Testen entkommentieren!
    
    
        /***************************************************************
        * Beispiel: Beliebige Logik, hier eine tageszeitabhängige 
        * Begrüßung
        ***************************************************************/

        $stunde = date("H");
        if ($stunde <= 10) {
            return "Guten Morgen!";
        }
        else if ($stunde <= 16) {
            return "Guten Tag!";
        }
        else {
            return "Guten Abend!";
        }
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * Ist keine Konfiguration nötig, gibt die Funktion false zurück.
    * 
    ***************************************************************/
    function getConfig() {
        return false; // keine Konfiguration nötig
    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück - in dieser 
    * Reihenfolge:
    *   - Name des Plugins
    *   - Version des Plugins
    *   - Kurzbeschreibung
    *   - Name des Autors
    *   - Download-URL
    * 
    ***************************************************************/
    function getInfo() {
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
    } // function getInfo

} // class DEMOPLUGIN

?>