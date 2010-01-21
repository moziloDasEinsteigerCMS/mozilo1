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

require_once 'Syntax.php';
require_once 'Language.php';

class Plugin {
    
    // Membervariable für eventuelle Fehlermeldungen
    var $error;
    
    // Membervariable für bequemen Zugriff auf die Plugin-Settings
    var $settings; 
    
    /*
    * Konstruktor
    */
    function Plugin(){
        $plugin_str = 'Plugin';
        $plugin_class = get_class($this);
        $plugin_class_dir = $plugin_class;
        $base_dir = str_replace("/admin","",getcwd());
        if((version_compare( phpversion(), '5.0' ) < 0)) {
            # php4
            $plugin_str = strtolower($plugin_str);
            $declared_classes = get_declared_classes();
            if ($handle = opendir($base_dir."/plugins/")) {
                while (false !== ($plugin_dir = readdir($handle))) {
                    $key = array_search(strtolower($plugin_dir), $declared_classes);
                    if(isset($declared_classes[$key]) and $key > 0) {
                        $plugin_class = $declared_classes[$key];
                        $plugin_class_dir = $plugin_dir;
                        break;
                    }
                }
                closedir($handle);
            }
        }
        // diese (abstrakte) Klasse darf nicht direkt instanziiert werden!
        if ($plugin_class == $plugin_str || !is_subclass_of($this, $plugin_str)){
#        if (get_class($this) == 'Plugin' || !is_subclass_of ($this, 'Plugin')){
            trigger_error('This class is abstract; it cannot be instantiated.', E_USER_ERROR);
        }

        // prüfen, ob alle "abstrakten" Methoden implementiert wurden:
        $this->error = null;
        $this->checkForMethod("getContent");
        $this->checkForMethod("getConfig");
        $this->checkForMethod("getInfo");
        
        // Settings-Variable als Properties-Objekt der plugin.conf instanziieren
#        if (file_exists("plugins/".get_class($this)."/plugin.conf")) {
#            $this->settings = new Properties("plugins/".get_class($this)."/plugin.conf");
#        }
        if (file_exists($base_dir."/plugins/".$plugin_class_dir."/plugin.conf")) {
            $this->settings = new Properties($base_dir."/plugins/".$plugin_class_dir."/plugin.conf");
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