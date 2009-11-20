<?php
/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/


/* 
* Abstrakte Basisklasse für moziloCMS-Plugins.
*
* PHP4 kennt das Prinzip der Abstraktion noch nicht,
* deswegen ist es durch die Hintertür implementiert:
* Im Konstruktor wird sichergestellt, daß niemand 
* diese abstrakte Klasse hier direkt instanziieren 
* kann; dann wird geprüft, ob erbende Klassen auch 
* sauber alle wichtigen Funktionen implementieren.

*/

class Plugin {
    
    // Membervariable für eventuelle Fehlermeldungen
    var $error;
    
    // Membervariable für bequemen Zugriff auf die Plugin-Settings
    var $settings; 
    
    /*
    * Konstruktor
    */
    function Plugin(){
        // diese (abstrakte) Klasse darf nicht direkt instanziiert werden!
        if (get_class($this) == 'Plugin' || !is_subclass_of ($this, 'Plugin')){
            trigger_error('This class is abstract; it cannot be instantiated.', E_USER_ERROR);
        }

        // prüfen, ob alle "abstrakten" Methoden implementiert wurden:
        $this->error = null;
        $this->checkForMethod("getContent");
        $this->checkForMethod("getConfig");
        $this->checkForMethod("getInfo");
        
        // Settings-Variable als Properties-Objekt der plugin.conf instanziieren
        if (file_exists("plugins/".get_class($this)."/plugin.conf")) {
            $this->settings = new Properties("plugins/".get_class($this)."/plugin.conf");
        }
        // Wenn plugin.conf nicht vorhanden ist, wird die Fehlervariable gefüllt
        else {
        	// im Admin wird die Klasse Plugin verwendet; die Klasse Syntax kann aber nicht geladen werden. Die Abfrage verhindert einfach eine Fehlermeldung. 
            if(class_exists("Syntax")) {
                $syntax = new Syntax();
                $language = new Language();
                $this->error = $syntax->createDeadlink("{".get_class($this)."}", $language->getLanguageValue1("plugin_error_missing_pluginconf_1", get_class($this)));
            }
        }
    }
    
    /*
    * Gibt den Inhalt des Plugins zurück
    */
    function getPluginContent($param) {
        // erst prüfen, ob bei der Initialisierung ein Fehler aufgetreten ist
        if ($this->error == null) {
            return $this->getContent($param);
        }
        // Bei Fehler: Inhalt der Fehlervariablen zurückgeben
        else {
            return $this->error;
        }
    }
    
    /*
    * Prüft, ob das Objekt eine Methode mit dem übergebenen Namen besitzt
    */
    function checkForMethod($method) {
        // wenn die Methode nicht existiert, wird die Fehlervariable gefüllt
        if (!method_exists($this, $method)) {
            $syntax = new Syntax();
            $language = new Language();
            $this->error = $syntax->createDeadlink("{".get_class($this)."}", $language->getLanguageValue2("plugin_error_missing_method_2", get_class($this), $method));
        }
    }
    
}
?>