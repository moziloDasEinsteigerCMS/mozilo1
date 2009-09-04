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
        Syntaxersetzung
        Klasse ITF04-1
        Industrieschule Chemnitz

        Ronny Monser
        Arvid Zimmermann
        Oliver Lorenz
        www.mozilo.de

######
*/

class Syntax {
    
    var $CMS_CONF;
    var $LANG;
    var $LINK_REGEX;
    var $MAIL_REGEX;
    var $USER_SYNTAX;
    var $TARGETBLANK_LINK;
    var $TARGETBLANK_GALLERY;
    var $TARGETBLANK_DOWNLOAD;

    
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
    function Syntax(){
        $this->CMS_CONF    = new Properties("conf/main.conf");
        $this->LANG    = new Language();
        // Regulärer Audruck zur Überprüfung von Links
        // Überprüfung auf Validität >> protokoll :// (username:password@) [(sub.)server.tld|ip-adresse] (:port) (subdirs|files)
                    // protokoll                         (https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh):\/\/
                    // username:password@        (\w)+\:(\w)+\@
                    // (sub.)server.tld         ((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}
                    // ip-adresse (ipv4)        ([\d]{1,3}\.){3}[\d]{1,3}
                    // port                                    \:[\d]{1,5}
                    // subdirs|files                (\w)+
        $this->LINK_REGEX = "/^(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh|svn)\:\/\/((\w)+\:(\w)+\@)?[((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}|([\d]{1,3}\.){3}[\d]{1,3}](\:[\d]{1,5})?((\w)+)?$/";
        $this->MAIL_REGEX = "/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/";
        $this->USER_SYNTAX    = new Properties("conf/syntax.conf");
        
        // Externe Links in neuem Fenster öffnen?
        if ($this->CMS_CONF->get("targetblank_link") == "true") {
            $this->TARGETBLANK_LINK = " target=\"_blank\"";
        }
        else {
            $this->TARGETBLANK_LINK = "";
        }
        // Galerie-Links in neuem Fenster öffnen?
        if ($this->CMS_CONF->get("targetblank_gallery") == "true") {
            $this->TARGETBLANK_GALLERY = " target=\"_blank\"";
        }
        else {
            $this->TARGETBLANK_GALLERY = "";
        }
        // Download-Links in neuem Fenster öffnen?
        if ($this->CMS_CONF->get("targetblank_download") == "true") {
            $this->TARGETBLANK_DOWNLOAD = " target=\"_blank\"";
        }
        else {
            $this->TARGETBLANK_DOWNLOAD = "";
        }
    }
    

// ------------------------------------------------------------------------------
// Umsetzung der übergebenen CMS-Syntax in HTML, Rückgabe als String
// ------------------------------------------------------------------------------
    function convertContent($content, $cat, $firstrecursion) {
        global $CONTENT_DIR_ABS;
        global $CONTENT_DIR_REL;
        global $CONTENT_FILES_DIR;
        global $GALLERIES_DIR;
        global $PAGE_REQUEST;
        global $EXT_PAGE;
        global $specialchars;
        
        if ($firstrecursion) {
            // Inhaltsformatierungen
            $content = htmlentities($content,ENT_COMPAT,'ISO-8859-1');
            $content = preg_replace("/&amp;#036;/Umsi", "&#036;", $content);
            $content = preg_replace("/&amp;#092;/Umsi", "&#092;", $content);
            $content = preg_replace("/\^(.)/Umsie", "'&#'.ord('\\1').';'", $content);
            // Für Einrückungen
            $content = str_replace("  ","&nbsp;&nbsp;",$content);

            // Platzhalter ersetzen
            $content = replacePlaceholders($content, "", "");
        }
        
        // Nach Texten in eckigen Klammern suchen
        preg_match_all("/\[([^\[\]]+)\|([^\[\]]*)\]/Um", $content, $matches);
        $i = 0;
        // Für jeden Treffer...
        foreach ($matches[0] as $match) {
            // ...Auswertung und Verarbeitung der Informationen
            $attribute = $matches[1][$i];
            $value = $matches[2][$i];

            // Ausgabe zu Testzwecken
            // echo "$i: $attribute - $value <br>";
            
            // externer Link
            if ($attribute == "link") {
                if (preg_match($this->LINK_REGEX, $value)) {
                    $shortenendlink = $value;
                    switch ($this->CMS_CONF->get("shortenlinks")) {
                        // mit "http://www." beginnende Links ohne das "http://www." anzeigen
                        case 2: { 
                            if (substr($value, 0, 11) == "http://www.")
                                $shortenendlink = substr($value, 11, strlen($value)-11);
                            // zusätzlich: mit "http://" beginnende Links ohne das "http://" anzeigen
                            elseif (substr($value, 0, 7) == "http://")
                                $shortenendlink = substr($value, 7, strlen($value)-7);
                            break;
                        }
                        // mit "http://" beginnende Links ohne das "http://" anzeigen
                        case 1: { 
                            if (substr($value, 0, 7) == "http://")
                                $shortenendlink = substr($value, 7, strlen($value)-7);
                            break;
                        }
                        default: { 
                        }
                    }
                    $content = str_replace ($match, "<a class=\"link\" href=\"$value\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_extern_1", $value)).$this->TARGETBLANK_LINK.">$shortenendlink</a>", $content);
                }
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_extern_error_1", $value)).">$value</span>", $content);
            }

            // externer Link mit eigenem Text
            elseif (substr($attribute,0,5) == "link=") {
                // Überprüfung auf korrekten Link
                if (preg_match($this->LINK_REGEX, $value))
                    $content = str_replace ($match, "<a class=\"link\" href=\"$value\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_extern_1", $value)).$this->TARGETBLANK_LINK.">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_extern_error_1", $value)).">".substr($attribute, 5, strlen($attribute)-5)."</span>", $content);
            }

            // Mail-Link mit eigenem Text
            elseif (substr($attribute,0,5) == "mail=") {
                // Überprüfung auf korrekten Link
                if (preg_match($this->MAIL_REGEX, $value))
                    $content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))).">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_mail_error_1", $value)).">".substr($attribute, 5, strlen($attribute)-5)."</span>", $content);
            }
            elseif ($attribute == "mail"){
                // Überprüfung auf Validität
                if (preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/", $value))
                    $content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))).">".obfuscateAdress("$value", 3)."</a>", $content);
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_mail_error_1", $value)).">$value</span>", $content);
            }

            // Kategorie-Link (überprüfen, ob Kategorie existiert)
            elseif ($attribute == "kategorie"){
                $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1')));
                if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat")))
                    $content = str_replace ($match, "<a class=\"category\" href=\"index.php?cat=$requestedcat\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_1", $value)).">$value</a>", $content);
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $value)).">$value</span>", $content);
            }

            // Kategorie-Link mit eigenem Text
            elseif (substr($attribute,0,10) == "kategorie=") {
                $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1')));
                if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat")))
                    $content = str_replace ($match, "<a class=\"category\" href=\"index.php?cat=$requestedcat\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_1", $value)).">".substr($attribute, 10, strlen($attribute)-10)."</a>", $content);
                else
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $value)).">$value</span>", $content);
            }

            // Link auf Inhaltsseite in aktueller oder anderer Kategorie (überprüfen, ob Inhaltsseite existiert)
            elseif ($attribute == "seite") {
                $valuearray = explode(":", $value);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1')), $cat);
                    if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage")))
                        $content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$cat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_page_1", $value)).">$value</a>", $content);
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)).">$value</span>", $content);
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1],ENT_COMPAT,'ISO-8859-1')), $requestedcat);
                        if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
                            $content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$requestedcat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_page_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</a>", $content);
                        else
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);    
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                }
            }

            // Link auf Inhaltsseite in aktueller oder anderer Kategorie mit beliebigem Text
            elseif (substr($attribute,0,6) == "seite=") {
                $valuearray = explode(":", $value);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1')), $cat);
                    if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage")))
                        $content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$cat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_page_1", $value)).">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)).">$value</span>", $content);
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1],ENT_COMPAT,'ISO-8859-1')), $requestedcat);
                        if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
                            $content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$requestedcat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_page_2", $valuearray[1], $valuearray[0])).">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
                        else
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);    
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                }
            }

            // Datei aus dem Dateiverzeichnis (überprüfen, ob Datei existiert)
            elseif ($attribute == "datei"){
                $value = html_entity_decode($value,ENT_COMPAT,'ISO-8859-1');
                $valuearray = explode(":", $value);
                // Datei in aktueller Kategorie
                if (count($valuearray) == 1) {
                    if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value"))
                        $content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$cat&amp;file=".preg_replace("'\s'", "%20", $value)."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_file_1", $value))."".$this->TARGETBLANK_DOWNLOAD.">$value</a>", $content);
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_file_error_1", $value)).">$value</span>", $content);
                }
                // Datei in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/$valuearray[1]"))
                            $content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$requestedcat&amp;file=".preg_replace("'\s'", "%20", $valuearray[1])."\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_file_2", $valuearray[1], $valuearray[0])).$this->TARGETBLANK_DOWNLOAD.">".$valuearray[1]."</a>", $content);
                        else
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_file_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                }
            }

            // Datei aus dem Dateiverzeichnis mit beliebigem Text
            elseif (substr($attribute,0,6) == "datei=") {
                $valuearray = explode(":", $value);
                // Datei in aktueller Kategorie
                if (count($valuearray) == 1) {
                    if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value"))
                        $content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$cat&amp;file=".preg_replace("'\s'", "%20", $value)."\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_file_1", $value)).$this->TARGETBLANK_DOWNLOAD.">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_file_error_1", $value)).">$value</span>", $content);
                }
                // Datei in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/$valuearray[1]"))
                            $content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$requestedcat&amp;file=".preg_replace("'\s'", "%20", $valuearray[1])."\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_file_2", $valuearray[1], $valuearray[0])).$this->TARGETBLANK_DOWNLOAD.">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
                        else
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_file_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                }
            }

            // Galerie
            elseif ($attribute == "galerie") {
                $cleanedvalue = $specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1'));
                if (file_exists("./$GALLERIES_DIR/$cleanedvalue")) {
                    $handle = opendir("./$GALLERIES_DIR/$cleanedvalue");
                    $j=0;
                    while ($file = readdir($handle)) {
                        if (is_file("./$GALLERIES_DIR/$cleanedvalue/".$file) && ($file <> "texte.conf")) {
                        $j++;
                        }
                    }
                    closedir($handle);
                    $content = str_replace ($match, "<a class=\"gallery\" href=\"gallery.php?gal=$cleanedvalue\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_gallery_2", $value, $j)).$this->TARGETBLANK_GALLERY.">$value</a>", $content);
                }
                // Galerie nicht vorhanden
                else {
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_gallery_error_1", $value)).">$value</span>", $content);
                }
            }

            // Galerielink mit eigenem Text
            elseif (substr($attribute,0,8) == "galerie=") {
                $cleanedvalue = $specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1'));
                if (file_exists("./$GALLERIES_DIR/$cleanedvalue")) {
                    $handle = opendir("./$GALLERIES_DIR/$cleanedvalue");
                    $j=0;
                    while ($file = readdir($handle)) {
                        if (is_file("./$GALLERIES_DIR/$cleanedvalue/".$file) && ($file <> "texte.conf")) {
                        $j++;
                        }
                    }
                    closedir($handle);
                    $content = str_replace ($match, "<a class=\"gallery\" href=\"gallery.php?gal=$cleanedvalue\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_gallery_2", $value, $j)).$this->TARGETBLANK_GALLERY.">".substr($attribute, 8, strlen($attribute)-8)."</a>", $content);
                }
                // Galerie nicht vorhanden
                else {
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_gallery_error_1", $value)).">".substr($attribute, 8, strlen($attribute)-8)."</span>", $content);
                }
            }

            // Bild aus dem Dateiverzeichnis oder externes Bild
            elseif (
                ($attribute == "bild") || (substr($attribute,0,5) == "bild=")
                || ($attribute == "bildlinks") || (substr($attribute,0,10) == "bildlinks=")
                || ($attribute == "bildrechts") || (substr($attribute,0,11) == "bildrechts=")
                ){
                    
                // Bildunterschrift merken, wenn vorhanden
                $subtitle = "";
                if(substr($attribute,0,10) == "bildlinks=") {
                    $subtitle = substr($attribute,10,strlen($attribute)-10);
                    $attribute = "bildlinks";
                }
                
                elseif(substr($attribute,0,11) == "bildrechts=") {
                    $subtitle = substr($attribute,11,strlen($attribute)-11);
                    $attribute = "bildrechts";
                }
                
                $imgsrc = "";
                $error = false;
                
                // Bei externen Bildern: $value NICHT nach ":" aufsplitten!
                if (preg_match($this->LINK_REGEX, $value))
                    $valuearray = $value;
                // Ansonsten: Nach ":" aufsplitten
                else
                    $valuearray = explode(":", $value);
                // Bild in aktueller Kategorie
                if (count($valuearray) == 1) {
                    // Bilddatei existiert
                    if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value")) {
                        $imgsrc = "$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $value);
                    }
                    // externes Bild
                    elseif (preg_match($this->LINK_REGEX, $value)) {
                        $imgsrc = $value;
                    }
                    // Bilddatei existiert nicht
                    else {
                        $error = true;
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_image_error_1", $value)).">$value</span>", $content);
                    }
                }
                // Bild in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    // Kategorie existiert
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        // Bilddatei existiert
                        if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".$valuearray[1])) {
                            $imgsrc = "$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $valuearray[1]);
                        }
                        // Bilddatei existiert nicht
                        else {
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_image_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);
                            $error = true;
                        }
                    }
                    // Kategorie existiert nicht
                    else {
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                        $error = true;
                    }
                }
                
                // Nun aber das Bild ersetzen!
                if (!$error) {
                    // "bildlinks" / "bildrechts"
                    if (($attribute == "bildlinks") || ($attribute == "bildrechts")) {
                        $cssclass = "";
                        if ($attribute == "bildlinks")
                            $cssclass = "leftcontentimage";
                        elseif ($attribute == "bildrechts")
                            $cssclass = "rightcontentimage";
                        // ohne Untertitel
                        if ($subtitle == "") {
                            $content = str_replace ($match, "<span class=\"$cssclass\"><img src=\"$imgsrc\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $value)."\" class=\"$cssclass\" /></span>", $content);
                        }
                        // mit Untertitel
                        else {
                            $content = str_replace ($match, "<span class=\"$cssclass\"><img src=\"$imgsrc\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $value)."\" class=\"$cssclass\" /><br /><span class=\"imagesubtitle\">$subtitle</span></span>", $content);
                        }
                    } 
                    // "bild"
                    else {
                        $content = str_replace ($match, "<img src=\"$imgsrc\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $value)."\" />", $content);
                    }
                }
            }

            // linksbündiger Text
            if ($attribute == "links"){
                $content = str_replace ("$match", "<div style=\"text-align:left;\">".$value."</div>", $content);
            }

            // zentrierter Text
            elseif ($attribute == "zentriert"){
                $content = str_replace ("$match", "<div style=\"text-align:center;\">".$value."</div>", $content);
            }

            // Text im Blocksatz
            elseif ($attribute == "block"){
                $content = str_replace ("$match", "<div style=\"text-align:justify;\">".$value."</div>", $content);
            }

            // rechtsbündiger Text
            elseif ($attribute == "rechts"){
                $content = str_replace ("$match", "<div style=\"text-align:right;\">".$value."</div>", $content);
            }

            // Text fett
            elseif ($attribute == "fett"){
                $content = str_replace ($match, "<b>$value</b>", $content);
            }

            // Text kursiv
            elseif ($attribute == "kursiv"){
                $content = str_replace ($match, "<i>$value</i>", $content);
            }

            // Text fettkursiv 
            // (veraltet seit Version 1.7 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "fettkursiv"){
                $content = str_replace ($match, "<b><i>$value</i></b>", $content);
            }

            // Text unterstrichen
            elseif ($attribute == "unter"){
                $content = str_replace ($match, "<u>$value</u>", $content);
            }

            // Text durchgestrichen
            elseif ($attribute == "durch"){
                $content = str_replace ($match, "<s>$value</s>", $content);
            }

            // Überschrift groß
            elseif ($attribute == "ueber1"){
                $content = str_replace ("$match", "<h1>$value</h1>", $content);
            }

            // Überschrift mittel
            elseif ($attribute == "ueber2"){
                $content = str_replace ("$match", "<h2>$value</h2>", $content);
            }

            // Überschrift normal
            elseif ($attribute == "ueber3"){
                $content = str_replace ("$match", "<h3>$value</h3>", $content);
            }

            // Listenpunkt
            elseif ($attribute == "liste"){
                $content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
            }

            // numerierter Listenpunkt
            elseif ($attribute == "numliste"){
                $content = str_replace ("$match", "<ol><li>$value</li></ol>", $content);
            }

            // Liste, einfache Einrückung
            // (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste1"){
                $content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
            }

            // Liste, doppelte Einrückung
            // (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste2"){
                $content = str_replace ("$match", "<ul><ul><li>$value</li></ul></ul>", $content);
            }

            // Liste, dreifache Einrückung
            // (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste3"){
                $content = str_replace ("$match", "<ul><ul><ul><li>$value</li></ul></ul></ul>", $content);
            }
            
            // HTML
            elseif ($attribute == "html"){
                $nobrvalue = preg_replace('/(\r\n|\r|\n)/m', '{newline_in_html_tag}', $value);
                $content = str_replace ("$match", html_entity_decode($nobrvalue,ENT_COMPAT,'ISO-8859-1'), $content);
            }

/* 
Sie sollten diese Auskommentierung nur entfernen, wenn Sie wirklich sicher wissen, was 
sie tun. mozilo weist mit Nachdruck darauf hin, daß die verwendete PHP-Funktion "eval()" 
ein erhebliches Sicherheitsrisiko darstellen kann und deswegen nicht leichtfertig 
verwendet werden sollte!

            // Ausführung von PHP-Code
            elseif ($attribute == "php") {
                // Formatierungen rückgängig machen, um den reinen PHP-Code zu erhalten!
                $value = preg_replace("/&#(\d*);/Umsie", "''.chr('\\1').''", $value);
                $value = preg_replace("/&#092;/Umsi", "&amp;#092;", $value);
                $value = preg_replace("/&#036;/Umsi", "&amp;#036;", $value);
                $value = html_entity_decode($value);
                $content = str_replace ("$match", eval($value), $content);
            }
*/

            // Tabellen
            elseif ($attribute == "tabelle") {
                // Tabelleninhalt aufbauen
                $tablecontent = "";
                $j = 0;
                // Tabellenzeilen
                // preg_match_all("/&lt;([^&gt;]*)&gt;/Um", $value, $tablelines);
                // preg_match_all("/(&lt;|&lt;&lt;)([^&gt;]*)(&gt;|&gt;&gt;)/Um", $value, $tablelines);
                preg_match_all("/(&lt;|&lt;&lt;)(.*)(&gt;|&gt;&gt;)/Umsie", $value, $tablelines);
                foreach ($tablelines[0] as $tablematch) {
                    // Kopfzeilen
                    if (preg_match("/&lt;&lt;([^&gt;]*)/Umsi", $tablematch)) {
                        $linecontent = preg_replace('/\|/', '</th><th class="contenttable">', $tablelines[2][$j]);
                        $linecontent = preg_replace('/&#38;/', '&', $linecontent);
                        $linecontent = preg_replace('/&lt;(.*)/', "$1", $linecontent);
                        $tablecontent .= "<tr><th class=\"contenttable\">$linecontent</th></tr>";
                    }
                    // normale Tabellenzeilen
                    else {
                        // CSS-Klasse immer im Wechsel
                        $css = "contenttable1";
                        if ($j%2 == 0)
                            $css = "contenttable2";
                        // Pipes durch TD-Wechsel ersetzen
                        $linecontent = preg_replace('/\|/', '</td><td class="'.$css.'">', $tablelines[2][$j]);
                        $linecontent = preg_replace('/&#38;/', '&', $linecontent);
                        $tablecontent .= "<tr><td class=\"$css\">$linecontent</td></tr>";
                    }
                    $j++;
                }
                $content = str_replace ("$match", "<table class=\"contenttable\" summary=\"\">$tablecontent</table>", $content);
            }

            // Includes
            elseif ($attribute == "include") {
                $valuearray = explode(":", $value);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value,ENT_COMPAT,'ISO-8859-1')), $cat);
                    if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage"))) {
                        // Seite darf sich nicht selbst includen!
                        if (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST) {
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue0("tooltip_include_recursion_error_0")).">$value</span>", $content);
                        }
                        // Includierte Inhaltsseite parsen
                        else {
                            $file = "./$CONTENT_DIR_REL/$cat/$requestedpage";
                            $handle = fopen($file, "r");
                            $pagecontent = "";
                            if (filesize($file) > 0) {
                                // "include"-Tags in includierten Seiten sind nicht erlaubt, um Rekursionen zu vermeiden
                                $pagecontent = preg_replace("/\[include\|([^\[\]]*)\]/Um", "[html|<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue0("tooltip_include_reinclude_error_0")).">$1</span>]", fread($handle, filesize($file)));
                                // Zeilenwechsel sichern
                                $pagecontent = preg_replace('/(\r\n|\r|\n)/', '{newline_in_include_tag}', $pagecontent);
                                // Seiteninhalt konvertieren
                                $pagecontent = $this->convertContent($pagecontent, $cat, true);
                            }
                            fclose($handle);
                            $content = str_replace ($match, $pagecontent, $content);
                        }
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)).">$value</span>", $content);
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0],ENT_COMPAT,'ISO-8859-1')));
                    if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
                        $requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1],ENT_COMPAT,'ISO-8859-1')), $requestedcat);
                        if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
                            // Seite darf sich nicht selbst includen!
                            if (($requestedcat == $cat) && (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST)) {
                                $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue0("tooltip_include_recursion_error_0")).">$value</span>", $content);
                            }
                            // Includierte Inhaltsseite parsen
                            else {
                                $file = "./$CONTENT_DIR_REL/$requestedcat/$requestedpage";
                                $handle = fopen($file, "r");
                                $pagecontent = "";
                                if (filesize($file) > 0) {
                                    // "include"-Tags in includierten Seiten sind nicht erlaubt, um Rekursionen zu vermeiden
                                    $pagecontent = preg_replace("/\[include\|([^\[\]]*)\]/Um", "[html|<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue0("tooltip_include_reinclude_error_0")).">$1</span>]", fread($handle, filesize($file)));
                                    // Zeilenwechsel sichern
                                    $pagecontent = preg_replace('/(\r\n|\r|\n)/', '{newline_in_include_tag}', $pagecontent);
                                    // Seiteninhalt konvertieren
                                    $pagecontent = $this->convertContent($pagecontent, $requestedcat, true);
                                }
                                fclose($handle);
                                $content = str_replace ($match, $pagecontent, $content);
                            }
                        else
                            $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])).">".$valuearray[1]."</span>", $content);    
                    }
                    else
                        $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])).">".$valuearray[1]."</span>", $content);
                }
            }
            
            // Farbige Elemente
            elseif (substr($attribute,0,6) == "farbe=") {
                // Überprüfung auf korrekten Hexadezimalwert
                if (preg_match("/^([a-f]|\d){6}$/i", substr($attribute, 6, strlen($attribute)-6))) 
                    $content = str_replace ("$match", "<span style=\"color:#".substr($attribute, 6, strlen($attribute)-6).";\">".$value."</span>", $content);
                else
                    $content = str_replace ("$match", "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_color_error_1", substr($attribute, 6, strlen($attribute)-6))).">$value</span>", $content);
            }

            // Attribute, die nicht zugeordnet werden können
            else {
                // Attribut am "=" aufsplitten und Attributnamen herausfiltern
                $equalpos = strpos($attribute, "=");
                
                // Es ist ein = im Attribut vorhanden
                if ($equalpos > 0) {
                    $description = substr($attribute, $equalpos+1, strlen($attribute)-$equalpos);
                    $attribute = substr($attribute, 0, $equalpos);
                }
                // ...oder eben nicht.
                else {
                    $description = "";
                }
                
                // das Attribut ist als benutzerdefiniertes Syntaxelement bekannt
                if ($this->USER_SYNTAX->keyExists($attribute)) {
                    // Platzhalter {VALUE} im definierten Syntaxelement ersetzen
                    $replacetext = str_replace("{VALUE}", $value, replacePlaceholders($this->USER_SYNTAX->get($attribute),"", ""));
                    /* 
                    //Einfach Kommentarzeichen entfernen, wenn folgende Funktionalität gewünscht ist:
                    // Platzhalter {DESCRIPTION} wird durch $value ersetzt, wenn $description selbst leer ist
                    if ($description == "")
                        $replacetext = str_replace("{DESCRIPTION}", $value, $replacetext);
                    else
                    */
                        // Platzhalter {DESCRIPTION} im definierten Syntaxelement durch die Beschreibung ersetzen
                        $replacetext = str_replace("{DESCRIPTION}", $description, $replacetext);
    
                    // alles im Inhalt ersetzen
                    $content = str_replace ("$match", $replacetext , $content);
                }
                // Wenn das Attribut unbekannt ist: Fehler ausgeben
                else {
                    $content = str_replace ($match, "<span class=\"deadlink\"".$this->getTitleAttribute($this->LANG->getLanguageValue1("tooltip_attribute_error_1", $attribute)).">".$value."</span>", $content);
                }
            }

            // Immer ersetzen: Horizontale Linen
            $content = preg_replace('/\[----\]/', '<hr />', $content);

            $i++;
        }
        
        // Rekursion, wenn noch Fundstellen
        if ($i > 0)
            $content = $this->convertContent($content, $cat, false);
        else {
            // Zeilenwechsel setzen
            $content = preg_replace('/(\r\n|\r|\n)/', '$1<br />', $content);
            // Zeilenwechsel nach Blockelementen entfernen
            // Tag-Beginn                                           <
            // optional: Slash bei schließenden Tags                (\/?)
            // Blockelemente                                        (address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)
            // optional: sonstige Zeichen (z.B. Attribute)          ([^>]*)
            // Tag-Ende                                             >
            // optional: Zeilenwechsel                              (\r\n|\r|\n)?
            // <br /> mit oder ohne Slash (das, was raus muß!)      <br \/? >
            $content = preg_replace('/<(\/?)(address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)([^>]*)>(\r\n|\r|\n)?<br \/?>/', "<$1$2$3>$4",$content);
            // direkt aufeinanderfolgende Listen zusammenführen
            $content = preg_replace('/<\/ul>(\r\n|\r|\n)?<ul>/', '', $content);
            // direkt aufeinanderfolgende numerierte Listen zusammenführen
            $content = preg_replace('/<\/ol>(\r\n|\r|\n)?<ol>/', '', $content);
        }

        // Zeilenwechsel in Include-Tags wiederherstellen    
        $content = preg_replace('/{newline_in_include_tag}/', "\n", $content);

        // Zeilenwechsel in HTML-Tags wiederherstellen    
        $content = preg_replace('/{newline_in_html_tag}/', "\n", $content);
    
        // Konvertierten Seiteninhalt zurückgeben
        return $content;
    }

    
// ------------------------------------------------------------------------------
// Hilfsfunktion: "title"-Attribut zusammenbauen (oder nicht, wenn nicht konfiguriert)
// ------------------------------------------------------------------------------
    function getTitleAttribute($value) {
        if ($this->CMS_CONF->get("showsyntaxtooltips") == "true") {
            return " title=\"".$value."\"";
        }
        return "";
    }
    
}

?>