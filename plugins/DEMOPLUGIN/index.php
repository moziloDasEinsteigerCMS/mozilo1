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
    * Ist keine Konfiguration nötig, ist dieses Array leer.
    * 
    ***************************************************************/
    function getConfig() {

        /***************************************************************
        * 
        * Details (Beispiele folgen weiter unten):
        * 
        * Die Funktion liefert ein Array zurück. Dieses enthält die 
        * Eingabefelder, mit denen der User im moziloAdmin Einstellungen 
        * am Plugin vornehmen kann.
        * Der "type"-Parameter der Eingabefelder bestimmt, um welche Art 
        * Eingabefeld es sich handelt und ist Pflicht. Folgende Werte
        * stehen zur Verfügung:
        *   text            Textfeld (beliebiger Text)
        *   textarea        mehrzeilige Texteingabe
        *   password        Passwortfeld (Anzeige des Inhalts als ***)
        *   checkbox        Checkbox (ja/nein)
        *   radio           Radio-Buttons (entweder/oder)
        *   select          Auswahlliste
        * 
        * Die Werte der Eingabefelder werden in die plugin.conf des 
        * Plugins geschrieben - der Name des Eingabefelds ist dabei der 
        * Schlüssel in der plugin.conf (siehe Beispiele).
        * 
        ***************************************************************/
        
        
        // Rückgabe-Array initialisieren
        // Das muß auf jeden Fall geschehen!
        $config = array();
        
        
        /***************************************************************
        * Beispiel: Normales Textfeld, beliebige Eingaben
        * - das Textfeld heißt "texteingabe"; gibt der Benutzer "abc" 
        *   ein und speichert die Plugin-Einstellungen, wird in der 
        *   plugin.conf folgende Zeile angelegt bzw. überschrieben:
        *   texteingabe = abc 
        ***************************************************************/
        
        $config['texteingabe']  = array(
            "type" => "text",                           // Pflicht:  Eingabetyp
            "description" => "Bitte Wert eingeben",     // Pflicht:  Beschreibung
            "maxlength" => "100",                       // optional: maximale Länge
            "size" => "30",                             // optional: dargestellte Zeichen
            "regex" => "/^[a-z]{3}$/",                   // optional: Erlaubte Werte als regulärer Ausdruck (hier: drei kleine Buchstaben; wird beim Speichern der Einstellungen überprüft)
            "regex_error" => "Die Eingabe muss genau 3 kleine Buchstaben enthalten." // optional: Fehlermeldung für die RegEx-Überprüfung
            );

            
        /***************************************************************
        * Beispiel: Mehrzeiliges Textfeld, beliebige Eingaben
        ***************************************************************/
            
        $config['mehrzeiligertext'] = array(
            "type" => "textarea",                       // Pflicht:  Eingabetyp 
            "cols" => "30",                             // Pflicht:  Spaltenanzahl 
            "rows" => "5",                              // Pflicht:  Zeilenanzahl
            "description" => "Bitte Text eingeben",     // Pflicht:  Beschreibung
            "regex" => "/^.+$/"           ,             // optional: Erlaubte Werte als regulärer Ausdruck (hier: wenigstens ein Zeichen; wird beim Speichern der Einstellungen überprüft)
            "regex_error" => "Die Eingabe darf nicht leer sein." // optional: Fehlermeldung für die RegEx-Überprüfung
        );

            
        /***************************************************************
        * Beispiel: Passwortfeld, beliebige Eingaben
        ***************************************************************/
        
        $config['passwort']  = array(
            "type" => "password",                       // Pflicht:  Eingabetyp
            "saveasmd5" => "true",                      // Pflicht:  soll das Passwort MD5-verschlüsselt in der plugin.conf abgelegt werden? (true/false)
            "description" => "Bitte Passwort eingeben", // Pflicht:  Beschreibung
            "maxlength" => "100",                       // optional: maximale Länge
            "size" => "30",                             // optional: dargestellte Zeichen
            "regex" => "/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", // optional: Erlaubte Werte als regulärer Ausdruck (hier: mindestens acht Zeichen, bestehend aus Klein- und Großbuchstaben sowie Ziffern); wird beim Speichern der Einstellungen überprüft
            "regex_error" => "Das Passwort muss aus mindestens acht Zeichen bestehen, Klein- und Großbuchstaben sowie Ziffern enthalten." // optional: Fehlermeldung für die RegEx-Überprüfung
        );

            
        /***************************************************************
        * Beispiel: Checkbox (aktiv oder nicht aktiv)
        ***************************************************************/
        
        $config['janeinoption'] = array(
            "type" => "checkbox",                       // Pflicht:  Eingabetyp 
            "description" => "Ja oder nein?"            // Pflicht:  Beschreibung
            );

        
        /***************************************************************
        * Beispiel: Radio-Buttons (entweder oder)
        ***************************************************************/
        
        $config['entwederoder'] = array(
            "type" => "radio",                          // Pflicht:  Eingabetyp
            "description" => "Welche Farbe?",           // Pflicht:  Beschreibung
            // Pflicht: Beschreibung der einzelnen Auswahlmöglichkeiten
            // entweder so...
            "descriptions" => array(
                "blau",                 // "blau" wird sowohl angezeigt als auch in die plugin.conf gespeichert
                "rot",
                "gruen"
                )
            // ..oder so:
/*
            "descriptions" => array(
                "blau" => "Blau",       // angezeigt wird "Blau", in die plugin.conf gespeichert wird "blau"
                "rot" => "Rot",
                "gruen" => "Grün"
                )
*/
            );
        
        
        /***************************************************************
        * Beispiel: Auswahlliste (Einzel- oder Mehrfachauswahl)
        ***************************************************************/
            
        $config['auswahl'] = array(
            "type" => "select",                         // Pflicht:  Eingabetyp
            "description" => "Ich mache Urlaub in...",  // Pflicht:  Beschreibung
            // Pflicht:  Beschreibung der einzelnen Auswahlmöglichkeiten
            // entweder so...
            "descriptions" => array(
                "Australien",           // "Australien" wird sowohl angezeigt als auch in die plugin.conf gespeichert
                "Venezuela",
                "Chemnitz"
                ),
            // ...oder so:
/*
            "descriptions" => array(
                "aus" => "Australien",  // angezeigt wird "Australien", in die plugin.conf gespeichert wird "aus"
                "ven" => "Venezuela",
                "che" => "Chemnitz"
                ),
*/
            "multiple" => "true"                        // optional: Mehrfachauswahl erlauben
            ); 

        
        // Nicht vergessen: Das gesamte Array zurückgeben
        return $config;
    } // function getConfig    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück - in dieser 
    * Reihenfolge:
    *   - Name des Plugins + Version
    *   - für moziloCMS-Version
    *   - Kurzbeschreibung
    *   - Name des Autors
    *   - Download-URL
    *   - Platzhalter für die Selectbox
    * 
    ***************************************************************/
    function getInfo() {
        return array(
            // Plugin-Name + Version
            "Plugin-Demo",
            // moziloCMS-Version
            "1.12",
            // Kurzbeschreibung
            "Beispiel-Plugin, das die Möglichkeiten des Plugin-Systems von moziloCMS aufzeigt",
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            # Platzhalter für die Selectbox im Inhaltseite Edit wenn lehr erscheint es nicht in Selectbox
            # Beispiel:
            #        Platzhalter => Kurtzbeschreibung
            # array('{Platzhalter|}' => 'Meine Platzhalter, Optinal Text für Link Platzhalter blank')
            # in Selectbox erscheint {Platzhalter|Meine Kurtzbeschreibung, Optinal Text für Link Platzhalter blank}
            # in Inhaltseite {Platzhalter|}
            # oder   Platzhalter => Kurtzbeschreibung
            # array('{Platzhalter}' => 'Optional Kurtzbeschreibung')
            # in Selectbox erscheint {Platzhalter}
            # in Inhaltseite {Platzhalter}
            # und mit mehreren die alle in der Selectbox erscheint
            # array('{Platzhalter_1}' => 'Kurtzbeschreibung_1', '{Platzhalter_2}' => 'Kurtzbeschreibung_2')
            array('{DEMOPLUGIN}' => 'Optional Kurtzbeschreibung',
                    '{DEMOPLUGIN|}' => 'Meine Kurtzbeschreibung, Optinal Text für Link Platzhalter blank',
                    '{DEMOPLUGIN|Value_1,}' => 'Meine Kurtzbeschreibung',
                    '{DEMOPLUGIN|Value_2,}' => 'Meine Kurtzbeschreibung'
                    )
            );
    } // function getInfo

} // class DEMOPLUGIN

?>