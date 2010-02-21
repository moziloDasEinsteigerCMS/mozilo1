<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/



/*
######
INHALT
######
        
        Projekt "Flatfile-basiertes CMS für Einsteiger"
        Sprachunterst�tzung
        Klasse ITF04-1
        Industrieschule Chemnitz

        Ronny Monser
        Arvid Zimmermann
        Oliver Lorenz
        www.mozilo.de

######
*/

class Language {
    
    var $LANG_CONF;

    
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
    function Language() {
        global $CMS_CONF;
        global $BASE_DIR_CMS;

        $currentlanguage = $CMS_CONF->get("cmslanguage");
        if (($currentlanguage == "") || (!file_exists($BASE_DIR_CMS."sprachen/$currentlanguage.conf")))
            $currentlanguage = "Deutsch";
        $this->LANG_CONF = new Properties($BASE_DIR_CMS."sprachen/$currentlanguage.conf");
    }
    

// ------------------------------------------------------------------------------
// Sprachelement ohne Zusatz aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue0($phrase) {
        global $CHARSET;
        return htmlentities($this->LANG_CONF->get($phrase),ENT_COMPAT,$CHARSET);
    }


// ------------------------------------------------------------------------------
// Sprachelement mit einem zusätzlichen Parameter aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue1($phrase, $param1) {
        global $CHARSET;
        $text = htmlentities($this->LANG_CONF->get($phrase),ENT_COMPAT,$CHARSET);
        $text = preg_replace("/{PARAM1}/", $param1, $text);
        return $text;
    }


// ------------------------------------------------------------------------------
// Sprachelement mit zwei zusätzlichen Parametern aus Sprachdatei holen
// ------------------------------------------------------------------------------
    function getLanguageValue2($phrase, $param1, $param2) {
        global $CHARSET;
        $text = htmlentities($this->LANG_CONF->get($phrase),ENT_COMPAT,$CHARSET);
        $text = preg_replace("/{PARAM1}/", $param1, $text);
        $text = preg_replace("/{PARAM2}/", $param2, $text);
        return $text;
    }


}
?>
