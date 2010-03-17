<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class Syntax {
    
    var $LANG;
    var $LINK_REGEX;
    var $MAIL_REGEX;
    var $TARGETBLANK_LINK;
    var $TARGETBLANK_DOWNLOAD;
    var $anchorcounter;
    var $headlineinfos;

    
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
    function Syntax(){
        global $CMS_CONF;
        // Regulärer Audruck zur überprüfung von Links
        // überprüfung auf Validität >> protokoll :// (username:password@) [(sub.)server.tld|ip-adresse] (:port) (subdirs|files)
                    // protokoll                (https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh):\/\/
                    // username:password@       (\w)+\:(\w)+\@
                    // (sub.)server.tld         ((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}
                    // ip-adresse (ipv4)        ([\d]{1,3}\.){3}[\d]{1,3}
                    // port                     \:[\d]{1,5}
                    // subdirs|files            (\w)+
        $this->LINK_REGEX   = "/^(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh|svn)\:\/\/((\w)+\:(\w)+\@)?[((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}|([\d]{1,3}\.){3}[\d]{1,3}](\:[\d]{1,5})?((\w)+)?$/";
        $this->MAIL_REGEX   = "/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/";
        
        // Externe Links in neuem Fenster öffnen?
        if ($CMS_CONF->get("targetblank_link") == "true") {
            $this->TARGETBLANK_LINK = " target=\"_blank\"";
        }
        else {
            $this->TARGETBLANK_LINK = "";
        }
        // Download-Links in neuem Fenster öffnen?
        if ($CMS_CONF->get("targetblank_download") == "true") {
            $this->TARGETBLANK_DOWNLOAD = " target=\"_blank\"";
        }
        else {
            $this->TARGETBLANK_DOWNLOAD = "";
        }
        $this->anchorcounter            = 1;
    }
    

// ------------------------------------------------------------------------------
// Umsetzung der übergebenen CMS-Syntax in HTML, Rückgabe als String
// ------------------------------------------------------------------------------
    function convertContent($content, $cat, $firstrecursion) {
        global $CONTENT_DIR_NAME;
        global $CONTENT_DIR_REL;
        global $CONTENT_FILES_DIR_NAME;
        global $GALLERIES_DIR_NAME;
        global $PAGE_REQUEST;
        global $EXT_PAGE;
        global $CAT_REQUEST;
        global $specialchars;
        global $URL_BASE;
        global $CHARSET;
        global $CMS_CONF;
        global $language;
        global $GALLERY_CONF;
        global $LAYOUT_DIR;
        global $USER_SYNTAX;
        global $BASE_DIR;
        global $CMS_DIR_NAME;

        if ($firstrecursion) {
            $content = $this->prepareContent($content);
            // Überschriften einlesen
            $this->headlineinfos = $this->getHeadlineInfos($content);
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
            // echo "$i: $attribute - $value <br />";
            
            // externer Link
            if ($attribute == "link") {
                if (preg_match($this->LINK_REGEX, $value)) {
                    $shortenendlink = $value;
                    switch ($CMS_CONF->get("shortenlinks")) {
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
                    # erstmal alle HTML Zeichen wandeln
                    $link = $specialchars->getHtmlEntityDecode($value);
                    # alle url encodete Zeichen wandeln
                    $link = $specialchars->rebuildSpecialChars($link,false,false);
                    # alles url encodeten
                    $link = $specialchars->replaceSpecialChars($link,false);
                    # alle :,?,&,;,= zurück wandeln
                    $link = str_replace(array('%3A','%3F','%26','%3B','%3D'),array(':','?','&amp;',';','='),$link);
                    $content = str_replace ($match, "<a class=\"link\" href=\"$link\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1", $value)).$this->TARGETBLANK_LINK.">$shortenendlink</a>", $content);
                }
                else {
                    $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_extern_error_1", $value)), $content);
                }
            }

            // externer Link mit eigenem Text
            elseif (substr($attribute,0,5) == "link=") {
                # erstmal alle HTML Zeichen wandeln
                $link = $specialchars->getHtmlEntityDecode($value);
                # alle url encodete Zeichen wandeln
                $link = $specialchars->rebuildSpecialChars($link,false,false);
                # alles url encodeten
                $link = $specialchars->replaceSpecialChars($link,false);
                # alle :,?,&,;,= zurück wandeln
                $link = str_replace(array('%3A','%3F','%26','%3B','%3D'),array(':','?','&amp;',';','='),$link);
                // überprüfung auf korrekten Link
                if (preg_match($this->LINK_REGEX, $value)) {
                    $content = str_replace ($match, "<a class=\"link\" href=\"$link\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_extern_1", $value)).$this->TARGETBLANK_LINK.">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
                }
                else {
                    $content = str_replace ($match, $this->createDeadlink(substr($attribute, 5, strlen($attribute)-5), $language->getLanguageValue1("tooltip_link_extern_error_1", $value)), $content);
                }
            }

            // Mail-Link mit eigenem Text
            elseif (substr($attribute,0,5) == "mail=") {
                // überprüfung auf korrekten Link
                if (preg_match($this->MAIL_REGEX, $value)) {
                    $content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))).">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
                }
                else {
                    $content = str_replace ($match, $this->createDeadlink(substr($attribute, 5, strlen($attribute)-5), $language->getLanguageValue1("tooltip_link_mail_error_1", $value)), $content);
                }
            }
            elseif ($attribute == "mail"){
                // Überprüfung auf Validität
                if (preg_match($this->MAIL_REGEX, $value))
                    $content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))).">".obfuscateAdress("$value", 3)."</a>", $content);
                else
                    $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_mail_error_1", $value)), $content);
            }

            // Kategorie-Link (überprüfen, ob Kategorie existiert)
            // Kategorie-Link mit eigenem Text
            elseif ($attribute == "kategorie" or substr($attribute,0,10) == "kategorie=") {
                $link_text = $value;
                if(substr($attribute,0,10) == "kategorie=") {
                    $link_text = substr($attribute, 10, strlen($attribute)-10);
                }
                $requestedcat = nameToCategory($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($value),false));
                $requestedcat_url = $specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($value),false);
                $url = "index.php?cat=$requestedcat_url";
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.$requestedcat_url.".html";
                }
                if ((!$requestedcat=="") && (file_exists($CONTENT_DIR_REL.$requestedcat))) {
                    $content = str_replace ($match, "<a class=\"category\" href=\"$url\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_category_1", $value)).">$link_text</a>", $content);
                }
                else {
                    $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_category_error_1", $value)), $content);
                }
            }

            // Link auf Inhaltsseite in aktueller oder anderer Kategorie (überprüfen, ob Inhaltsseite existiert)
            // Link auf Inhaltsseite in aktueller oder anderer Kategorie mit beliebigem Text
            elseif ($attribute == "seite" or substr($attribute,0,6) == "seite=") {
                $seite = $specialchars->getHtmlEntityDecode($value);
                $valuearray = explode(":", $seite);
                $link_text = "";
                if(substr($attribute,0,6) == "seite=") {
                    $link_text = substr($attribute, 6, strlen($attribute)-6);
                }
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $requestedpage = nameToPage($specialchars->replaceSpecialChars($seite,false), $cat);
                    if(empty($link_text)) {
                        $link_text = $value;
                    }
                    $url = "index.php?cat=".substr($cat,3)."&amp;page=".substr($requestedpage, 3, strlen($requestedpage) - strlen($EXT_PAGE) - 3);
                    if($CMS_CONF->get("modrewrite") == "true") {
                        $url = $URL_BASE.substr($cat,3)."/".substr($requestedpage, 3, strlen($requestedpage) - strlen($EXT_PAGE) - 3).".html";
                    }
                    if ((!$requestedpage == "") && (file_exists($CONTENT_DIR_REL.$cat."/".$requestedpage))) {
                        $content = str_replace ($match, "<a class=\"page\" href=\"$url\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_page_1", $value)).">$link_text</a>", $content);
                    }
                    else {
                        $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_page_error_1", $value)), $content);
                    }
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars($valuearray[0],false));
                    $html_seite = $specialchars->rebuildSpecialChars($valuearray[1],true,true);
                    $html_seite_cat = $specialchars->rebuildSpecialChars($valuearray[0],true,true);
                    if(empty($link_text)) {
                        $link_text = $html_seite;
                    }
                    if ((!$requestedcat == "") && (file_exists($CONTENT_DIR_REL.$requestedcat))) {
                        $requestedpage = nameToPage($specialchars->replaceSpecialChars($valuearray[1],false), $requestedcat);
                        $url = "index.php?cat=".substr($requestedcat,3)."&amp;page=".substr($requestedpage, 3, strlen($requestedpage) - strlen($EXT_PAGE) - 3);
                        if($CMS_CONF->get("modrewrite") == "true") {
                            $url = $URL_BASE.substr($requestedcat,3)."/".substr($requestedpage, 3, strlen($requestedpage) - strlen($EXT_PAGE) - 3).".html";
                        }
                        if ((!$requestedpage == "") && (file_exists($CONTENT_DIR_REL.$requestedcat."/".$requestedpage))) {
                            $content = str_replace ($match, "<a class=\"page\" href=\"$url\"".$this->getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $html_seite, $html_seite_cat)).">".$link_text."</a>", $content);
                        }
                        else {
                            $content = str_replace ($match, $this->createDeadlink($html_seite, $language->getLanguageValue2("tooltip_link_page_error_2", $html_seite, $html_seite_cat)), $content);    
                        }
                    }
                    else {
                        $content = str_replace ($match, $this->createDeadlink($html_seite, $language->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])), $content);
                    }
                }
            }

            // Verweise auf Absätze innerhalb der Inhaltsseite
            elseif (($attribute == "absatz") || (substr($attribute,0,7) == "absatz=")) {
                // Beschreibungstext extrahieren
                if(substr($attribute,0,7) == "absatz=") {
                    $link_text = substr($attribute, 7, strlen($attribute)-7);
                }
                else {
                    $link_text = $value;
                } 
                $pos = 0;
                foreach ($this->headlineinfos as $headline_info) {
                    // $headline_info besteht aus Überschriftstyp (1/2/3) und Wert
                    if ($headline_info[1] == $value) {
                        // "Nach oben"-Verweis
                        if ($pos == 0)
                            $content = str_replace ($match, "<a class=\"paragraph\" href=\"#a$pos\"".$this->getTitleAttribute($language->getLanguageValue0("tooltip_anchor_gototop_0")).">$link_text</a>", $content);
                        // sonstige Anker-Verweise
                        else
                            $content = str_replace ($match, "<a class=\"paragraph\" href=\"#a$pos\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_anchor_goto_1", $value)).">$link_text</a>", $content);
                    }
                    $pos++;
                }
                $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_anchor_error_1", $value)), $content);
            }
            

            // Datei aus dem Dateiverzeichnis (überprüfen, ob Datei existiert)
            // Datei aus dem Dateiverzeichnis mit beliebigem Text
            elseif ($attribute == "datei" or substr($attribute,0,6) == "datei=") {
                $datei = $specialchars->getHtmlEntityDecode($value);
                $valuearray = explode(":", $datei);
                $link_text = "";
                if(substr($attribute,0,6) == "datei=") {
                    $link_text = substr($attribute, 6, strlen($attribute)-6);
                }
                // Datei in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $datei = $specialchars->replaceSpecialChars($datei,false);
                    if(empty($link_text)) {
                        $link_text = $value;
                    }
                    if (file_exists($CONTENT_DIR_REL.$cat."/".$CONTENT_FILES_DIR_NAME."/".$datei)) {
                        $content = str_replace ($match, "<a class=\"file\" href=\"".$URL_BASE.$CMS_DIR_NAME."/download.php?cat=$cat&amp;file=$datei\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_link_file_1", $value))."".$this->TARGETBLANK_DOWNLOAD.">$link_text</a>", $content);
                    }
                    else {
                        $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_file_error_1", $value)), $content);
                    }
                }
                // Datei in anderer Kategorie
                else {
                    $datei_cat = nameToCategory($specialchars->replaceSpecialChars($valuearray[0],false));
                    $datei = $specialchars->replaceSpecialChars($valuearray[1],false);
                    $html_datei_cat = $specialchars->rebuildSpecialChars($datei_cat,true,true);
                    $html_datei = $specialchars->rebuildSpecialChars($datei,true,true);
                    if(empty($link_text))
                        $link_text = $specialchars->rebuildSpecialChars($datei,true,true);
                    if ((!$datei_cat == "") && (file_exists($CONTENT_DIR_REL.$datei_cat))) {
                        if (file_exists($CONTENT_DIR_REL.$datei_cat."/".$CONTENT_FILES_DIR_NAME."/".$datei)) {
                            $content = str_replace ($match, "<a class=\"file\" href=\"".$URL_BASE.$CMS_DIR_NAME."/download.php?cat=$datei_cat&amp;file=$datei\"".$this->getTitleAttribute($language->getLanguageValue2("tooltip_link_file_2", $html_datei, $html_datei_cat)).$this->TARGETBLANK_DOWNLOAD.">".$link_text."</a>", $content);
                        }
                        else {
                            $content = str_replace ($match, $this->createDeadlink($html_datei, $language->getLanguageValue2("tooltip_link_file_error_2", $html_datei, $html_datei_cat)), $content);
                        }
                    }
                    else {
                        $content = str_replace ($match, $this->createDeadlink($html_datei, $language->getLanguageValue1("tooltip_link_category_error_1", $html_datei_cat)), $content);
                    }
                }
            }

            // Galerie
            elseif (($attribute == "galerie") || (substr($attribute,0,8) == "galerie=")) {
                $cleanedvalue = $specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($value),false);
                $link_text = "";
                if(substr($attribute,0,8) == "galerie=") {
                    $link_text = ",".substr($attribute, 8, strlen($attribute)-8);
                }
                $content = str_replace ($match, '{Galerie|'.$cleanedvalue.$link_text.'}', $content);
            }

            // Bild aus dem Dateiverzeichnis oder externes Bild
            elseif (
                ($attribute == "bild") || (substr($attribute,0,5) == "bild=")
                || ($attribute == "bildlinks") || (substr($attribute,0,10) == "bildlinks=")
                || ($attribute == "bildrechts") || (substr($attribute,0,11) == "bildrechts=")
                ){

                // Bildunterschrift merken, wenn vorhanden
                $subtitle = "";
                if(substr($attribute,0,5) == "bild=") {
                    $subtitle = substr($attribute,5,strlen($attribute)-5);
                    $attribute = "bild";
                }
                elseif(substr($attribute,0,10) == "bildlinks=") {
                    $subtitle = substr($attribute,10,strlen($attribute)-10);
                    $attribute = "bildlinks";
                }
                elseif(substr($attribute,0,11) == "bildrechts=") {
                    $subtitle = substr($attribute,11,strlen($attribute)-11);
                    $attribute = "bildrechts";
                }

                $imgsrc = "";
                $error = false;

                $value = $specialchars->getHtmlEntityDecode($value);
                // Bei externen Bildern: $value NICHT nach ":" aufsplitten!
                if (preg_match($this->LINK_REGEX, $value)) {
                    $valuearray = $specialchars->replaceSpecialChars($value,false);
                }

                // Ansonsten: Nach ":" aufsplitten
                else {
                    $valuearray = explode(":", $value);
                    if(count($valuearray) > 1) {
                        $valuearray[0] = $specialchars->replaceSpecialChars($valuearray[0],false);
                        $valuearray[1] = $specialchars->replaceSpecialChars($valuearray[1],false);
                    }
                }
                // Bild in aktueller Kategorie
                if (count($valuearray) == 1) {
                    // Bilddatei existiert
                    if (file_exists($CONTENT_DIR_REL.$cat."/".$CONTENT_FILES_DIR_NAME."/".$specialchars->replaceSpecialChars($value,false))) {
                        $imgsrc = $specialchars->replaceSpecialChars($URL_BASE.$CONTENT_DIR_NAME."/".$cat."/".$CONTENT_FILES_DIR_NAME."/".$specialchars->replaceSpecialChars($value,false),true);
                    }
                    // externes Bild
                    elseif (preg_match($this->LINK_REGEX, $value)) {
                        $imgsrc = $value;
                    }
                    // Bilddatei existiert nicht
                    else {
                        $error = true;
                        $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_image_error_1", $value)), $content);
                    }
                }
                // Bild in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($valuearray[0]);
                    // Kategorie existiert
                    if ((!$requestedcat=="") && (file_exists($CONTENT_DIR_REL.$requestedcat))) {
                        // Bilddatei existiert
                        if (file_exists($CONTENT_DIR_REL.$requestedcat."/".$CONTENT_FILES_DIR_NAME."/".$valuearray[1])) {
                            $imgsrc = $specialchars->replaceSpecialChars($URL_BASE.$CONTENT_DIR_NAME."/".$requestedcat."/".$CONTENT_FILES_DIR_NAME."/".$valuearray[1],true);
                        }
                        // Bilddatei existiert nicht
                        else {
                            $content = str_replace ($match, $this->createDeadlink($valuearray[1], $language->getLanguageValue2("tooltip_image_error_2", $valuearray[1], $valuearray[0])), $content);
                            $error = true;
                        }
                    }
                    // Kategorie existiert nicht
                    else {
                        $content = str_replace ($match, $this->createDeadlink($valuearray[1], $language->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])), $content);
                        $error = true;
                    }
                }
                
                // Nun aber das Bild ersetzen!
                if (!$error) {
                    $alt = $specialchars->rebuildSpecialChars($value,true,true);
                    $cssclass = "";
                    if ($attribute == "bild") {
                        $cssclass = "contentimage";
                    }
                    if ($attribute == "bildlinks") {
                        $cssclass = "leftcontentimage";
                    }
                    elseif ($attribute == "bildrechts") {
                        $cssclass = "rightcontentimage";
                    }
                    // ohne Untertitel
                    if ($subtitle == "") {
                        // normales Bild: ohne <span> rundrum
                        if ($attribute == "bild") {
                            $content = str_replace ($match, "<img src=\"$imgsrc\" alt=\"".$language->getLanguageValue1("alttext_image_1", $alt)."\" class=\"$cssclass\" />", $content);
                        }
                        else {
                            $content = str_replace ($match, "<span class=\"$cssclass\"><img src=\"$imgsrc\" alt=\"".$language->getLanguageValue1("alttext_image_1", $alt)."\" class=\"$cssclass\" /></span>", $content);
                        }
                    }
                    // mit Untertitel
                    else {
                        $content = str_replace ($match, "<span class=\"$cssclass\"><img src=\"$imgsrc\" alt=\"".$language->getLanguageValue1("alttext_image_1", $alt)."\" class=\"$cssclass\" /><br /><span class=\"imagesubtitle\">$subtitle</span></span>", $content);
                    }
                }
            }

            // linksbündiger Text
            if ($attribute == "links"){
                $content = str_replace ("$match", "<p class=\"alignleft\">".$value."</p>", $content);
            }

            // zentrierter Text
            elseif ($attribute == "zentriert"){
                $content = str_replace ("$match", "<p class=\"aligncenter\">".$value."</p>", $content);
            }

            // Text im Blocksatz
            elseif ($attribute == "block"){
                $content = str_replace ("$match", "<p class=\"alignjustify\">".$value."</p>", $content);
            }

            // rechtsbündiger Text
            elseif ($attribute == "rechts"){
                $content = str_replace ("$match", "<p class=\"alignright\">".$value."</p>", $content);
            }

            // Text fett
            elseif ($attribute == "fett"){
                $content = str_replace ($match, "<b class=\"contentbold\">$value</b>", $content);
            }

            // Text kursiv
            elseif ($attribute == "kursiv") {
                $content = str_replace ($match, "<i class=\"contentitalic\">$value</i>", $content);
            }

            // Text fettkursiv 
            // (VERALTET seit Version 1.7 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "fettkursiv") {
                $content = str_replace ($match, "<b class=\"contentbold\"><i class=\"contentitalic\">$value</i></b>", $content);
            }

            // Text unterstrichen
            elseif ($attribute == "unter"){
                $content = str_replace ($match, "<u class=\"contentunderlined\">$value</u>", $content);
            }

            // Text durchgestrichen
            elseif ($attribute == "durch"){
                $content = str_replace ($match, "<s class=\"contentstrikethrough \">$value</s>", $content);
            }

            // Überschrift groß
            elseif ($attribute == "ueber1"){
                $content = preg_replace("/".preg_quote($match, '/')."/", "<h1 id=\"a".$this->anchorcounter++."\">$value</h1>", $content,1);
            }

            // Überschrift mittel
            elseif ($attribute == "ueber2"){
                $content = preg_replace("/".preg_quote($match, '/')."/", "<h2 id=\"a".$this->anchorcounter++."\">$value</h2>", $content,1);
            }

            // Überschrift normal
            elseif ($attribute == "ueber3"){
                $content = preg_replace("/".preg_quote($match, '/')."/", "<h3 id=\"a".$this->anchorcounter++."\">$value</h3>", $content,1);
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
            // (VERALTET seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste1"){
                $content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
            }

            // Liste, doppelte Einrückung
            // (VERALTET seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste2"){
                $content = str_replace ("$match", "<ul><ul><li>$value</li></ul></ul>", $content);
            }

            // Liste, dreifache Einrückung
            // (VERALTET seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
            elseif ($attribute == "liste3"){
                $content = str_replace ("$match", "<ul><ul><ul><li>$value</li></ul></ul></ul>", $content);
            }

            // HTML
            elseif ($attribute == "html"){
                $nobrvalue = preg_replace('/(\r\n|\r|\n)/m', '{newline_in_html_tag}', $value);
                $content = str_replace ("$match", $specialchars->getHtmlEntityDecode($nobrvalue), $content);
            }

/*
            Das "php"-Element ist seit moziloCMS 1.12 veraltet und wird nur noch aus Gründen 
            der Abwärtskompatibilität mitgeführt. Bitte erstellen Sie für die Ausführung von 
            eigenem PHP-Code ein Plugin. 

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
                        if ($j%2 == 0) {
                            $css = "contenttable2";
                        }
                        // Pipes durch TD-Wechsel ersetzen
                        $linecontent = preg_replace('/\|/', '</td><td class="'.$css.'">', $tablelines[2][$j]);
                        $linecontent = preg_replace('/&#38;/', '&', $linecontent);
                        $tablecontent .= "<tr><td class=\"$css\">$linecontent</td></tr>";
                    }
                    $j++;
                }
                $content = str_replace ("$match", "<table class=\"contenttable\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" summary=\"\">$tablecontent</table>", $content);
            }

            // Includes
            elseif ($attribute == "include") {
                $valuearray = explode(":", $value);
                // Inhaltsseite in aktueller Kategorie
                if (count($valuearray) == 1) {
                    $requestedpage = nameToPage($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($value),false), $cat);
                    if ((!$requestedpage=="") && (file_exists($CONTENT_DIR_REL.$cat."/".$requestedpage))) {
                        // Seite darf sich nicht selbst includen!
                        if (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST) {
                            $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue0("tooltip_include_recursion_error_0")), $content);
                        }
                        // Includierte Inhaltsseite parsen
                        else {
                            $file = $CONTENT_DIR_REL.$cat."/".$requestedpage;
                            $handle = fopen($file, "r");
                            $pagecontent = "";
                            if (filesize($file) > 0) {
                                // "include"-Tags in includierten Seiten sind nicht erlaubt, um Rekursionen zu vermeiden
                                $pagecontent = preg_replace("/\[include\|([^\[\]]*)\]/Um", "[html|".$this->createDeadlink("$1", $language->getLanguageValue0("tooltip_include_reinclude_error_0"))."]", fread($handle, filesize($file)));
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
                        $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_link_page_error_1", $value)), $content);
                }
                // Inhaltsseite in anderer Kategorie
                else {
                    $requestedcat = nameToCategory($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($valuearray[0]),false));
                    if ((!$requestedcat=="") && (file_exists($CONTENT_DIR_REL.$requestedcat))) {
                        $requestedpage = nameToPage($specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($valuearray[1]),false), $requestedcat);
                        if ((!$requestedpage=="") && (file_exists($CONTENT_DIR_REL.$requestedcat."/".$requestedpage)))
                            // Seite darf sich nicht selbst includen!
                            if (($requestedcat == $cat) && (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST)) {
                                $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue0("tooltip_include_recursion_error_0")), $content);
                            }
                            // Includierte Inhaltsseite parsen
                            else {
                                $file = $CONTENT_DIR_REL.$requestedcat."/".$requestedpage;
                                $handle = fopen($file, "r");
                                $pagecontent = "";
                                if (filesize($file) > 0) {
                                    // "include"-Tags in includierten Seiten sind nicht erlaubt, um Rekursionen zu vermeiden
                                    $pagecontent = preg_replace("/\[include\|([^\[\]]*)\]/Um", "[html|".$this->createDeadlink("$1", $language->getLanguageValue0("tooltip_include_reinclude_error_0"))."]", fread($handle, filesize($file)));
                                    // Zeilenwechsel sichern
                                    $pagecontent = preg_replace('/(\r\n|\r|\n)/', '{newline_in_include_tag}', $pagecontent);
                                    // Seiteninhalt konvertieren
                                    $pagecontent = $this->convertContent($pagecontent, $requestedcat, true);
                                }
                                fclose($handle);
                                $content = str_replace ($match, $pagecontent, $content);
                            }
                        else {
                            $content = str_replace ($match, $this->createDeadlink($valuearray[1], $language->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])), $content);    
                        }
                    }
                    else {
                        $content = str_replace ($match, $this->createDeadlink($valuearray[1], $language->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])), $content);
                    }
                }
            }

            // Farbige Elemente
            elseif (substr($attribute,0,6) == "farbe=") {
                // Überprüfung auf korrekten Hexadezimalwert
                if (preg_match("/^([a-f]|\d){6}$/i", substr($attribute, 6, strlen($attribute)-6))) {
                    $content = str_replace ("$match", "<span style=\"color:#".substr($attribute, 6, strlen($attribute)-6).";\">".$value."</span>", $content);
                }
                else {
                    $content = str_replace ("$match", $this->createDeadlink($value, $language->getLanguageValue1("tooltip_color_error_1", substr($attribute, 6, strlen($attribute)-6))), $content);
                }
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
                if ($USER_SYNTAX->keyExists($attribute)) {
                    // Platzhalter {VALUE} im definierten Syntaxelement ersetzen
                    $replacetext = str_replace("{VALUE}", $value, replacePlaceholders($USER_SYNTAX->get($attribute),"", ""));
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
                    $content = str_replace ($match, $this->createDeadlink($value, $language->getLanguageValue1("tooltip_attribute_error_1", $attribute)), $content);
                }
            }

            $i++;
        }
 
        // Rekursion, wenn noch Fundstellen
        if ($i > 0)
            $content = $this->convertContent($content, $cat, false);
        else {
            // Immer ersetzen: Horizontale Linen
            $content = preg_replace('/\[----\]/', '<hr />', $content);
            // Zeilenwechsel setzen
            $content = preg_replace('/(\r\n|\r|\n)/', '$1<br />', $content);
            // Zeilenwechsel nach Blockelementen entfernen
            // Tag-Beginn                                       <
            // optional: Slash bei schließenden Tags            (\/?)
            // Blockelemente                                    (address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)
            // optional: sonstige Zeichen (z.B. Attribute)      ([^>]*)
            // Tag-Ende                                         >
            // optional: Zeilenwechsel                          (\r\n|\r|\n)?
            // <br /> mit oder ohne Slash (das, was raus muß!)  <br \/? >
            $content = preg_replace('/<(\/?)(address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)([^>]*)>(\r\n|\r|\n)?<br \/?>/', "<$1$2$3>$4",$content);
            // direkt aufeinanderfolgende Listen zusammenführen
            $content = preg_replace('/<\/ul>(\r\n|\r|\n)?<ul>/', '', $content);
            // direkt aufeinanderfolgende numerierte Listen zusammenf�hren
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
        global $CMS_CONF;
        if ($CMS_CONF->get("showsyntaxtooltips") == "true") {
            return " title=\"".$value."\"";
        }
        return "";
    }
    

// ------------------------------------------------------------------------------
// Inhaltsverzeichnis aus den übergebenen Überschrift-Infos aufbauen
// ------------------------------------------------------------------------------
    function getToC($pagerequest) {
        global $language;
        $tableofcontents = "<div class=\"tableofcontents\">";
        if (count($this->headlineinfos) > 1) {
            $tableofcontents .= "<ul>";
            // Schleife über Überschriften-Array (0 ist der Seitenanfang - auslassen)
            for ($toc_counter=1; $toc_counter < count($this->headlineinfos); $toc_counter++) {
                $link = "<a class=\"page\" href=\"#a$toc_counter\"".$this->getTitleAttribute($language->getLanguageValue1("tooltip_anchor_goto_1", $this->headlineinfos[$toc_counter][1])).">".$this->headlineinfos[$toc_counter][1]."</a>";
                if ($this->headlineinfos[$toc_counter][0] >= "2") {
                    $tableofcontents .= "<li class=\"blind\"><ul>";
                }
                if ($this->headlineinfos[$toc_counter][0] >= "3") {
                    $tableofcontents .= "<li class=\"blind\"><ul>";
                }
                $tableofcontents .= "<li class=\"toc_".$this->headlineinfos[$toc_counter][0]."\">".$link."</li>";
                if ($this->headlineinfos[$toc_counter][0] >= "2") {
                    $tableofcontents .= "</ul></li>";
                }
                if ($this->headlineinfos[$toc_counter][0] >= "3") {
                    $tableofcontents .= "</ul></li>";
                }
            }
            $tableofcontents .= "</ul>";
        }
        $tableofcontents .= "</div>";
        return $tableofcontents;
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Überschrift-Infos einlesen
// ------------------------------------------------------------------------------
    function getHeadlineInfos($content) {
        global $language;
        // "absatz"-Links vorbereiten: Alle Überschriften einlesen
        preg_match_all("/\[(ueber([\d]))\|([^\[\]]+)\]/", $content, $matches);
        // $headlines besteht aus Arrays, die zwei Werte beinhalten: Überschriftstyp (1/2/3) und Wert
        $headlines = array();
        $headlines[0] = array("0", $language->getLanguageValue0("anchor_top_0"));

        $i = 0;
        foreach ($matches[0] as $match) {
            // gefundene Überschriften im Array $headlines merken
            $headlines[$i+1] = (array($matches[2][$i], $matches[3][$i]));
            //echo ($i+1) ." >>> ". $matches[2][$i].", ".$matches[3][$i]."<hr>";
            $i++;
        }
        return $headlines;
    }


// ------------------------------------------------------------------------------
// Hilfsfunktion: Inhalte vorbereiten
// ------------------------------------------------------------------------------
    function prepareContent($content) {
        global $specialchars;
        global $CHARSET;
        
        // Inhaltsformatierungen
        $content = htmlentities($content,ENT_COMPAT,$CHARSET);
        $content = preg_replace("/&amp;#036;/Umsi", "&#036;", $content);
        $content = preg_replace("/&amp;#092;/Umsi", "&#092;", $content);
        $content = preg_replace("/\^(.)/Umsie", "'&#'.ord('\\1').';'", $content);
        $content = $specialchars->numeric_entities_decode($content); 
        // Für Einrückungen
        $content = str_replace("  ","&nbsp;&nbsp;",$content);

        // Platzhalter ersetzen
        $content = replacePlaceholders($content, "", "");
        
        return $content;
    }
    
    
// ------------------------------------------------------------------------------
// Hilfsfunktion: Deadlink erstellen
// ------------------------------------------------------------------------------
    function createDeadlink($content, $title) {
        return "<span class=\"deadlink\"".$this->getTitleAttribute($title).">$content</span>";
    }

}

?>