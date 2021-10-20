<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class Language {
    
    var $LANG_CONF;

    
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
    function __construct($lang_dir = false) {
        global $CMS_CONF;
        if(!$lang_dir) {
            $currentlanguage = $CMS_CONF->get("cmslanguage");
            // Standardsprache Deutsch verwenden, wenn konfigurierte Sprachdatei nicht vorhanden
            if (($currentlanguage == "") || (!file_exists(BASE_DIR_CMS."sprachen/language_$currentlanguage.conf"))) {
                $currentlanguage = "deDE";
            }
            $this->LANG_CONF = new Properties(BASE_DIR_CMS."sprachen/language_$currentlanguage.conf");
        } else {
            $this->LANG_CONF = new Properties($lang_dir);
        }
    }
    

// ------------------------------------------------------------------------------
// Sprachelement ohne Zusatz aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue0($phrase) {
        return htmlentities($this->LANG_CONF->get($phrase),ENT_COMPAT,CHARSET);
    }


// ------------------------------------------------------------------------------
// Sprachelement mit einem zusätzlichen Parameter aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue1($phrase, $param1) {
        $text = $this->LANG_CONF->get($phrase);
        $text = str_replace("{PARAM1}", $param1, $text);
        return htmlentities($text,ENT_COMPAT,CHARSET);
    }


// ------------------------------------------------------------------------------
// Sprachelement mit zwei zusätzlichen Parametern aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue2($phrase, $param1, $param2) {
        $text = $this->LANG_CONF->get($phrase);
        $text = str_replace("{PARAM1}", $param1, $text);
        $text = str_replace("{PARAM2}", $param2, $text);
        return htmlentities($text,ENT_COMPAT,CHARSET);
    }


}

?>