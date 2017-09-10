<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class SpecialChars {
    
    function getHtmlEntityDecode($string) {

        if((version_compare( phpversion(), '5.0' ) < 0)) {
            $replace = array_keys(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES));
            # get_html_translation_table liefert die Zeichen im ISO-8859-1 Format - wir brauchen UTF-8
            $replace = implode(",",$replace);
            if(function_exists("utf8_encode")) {
                $replace = utf8_encode($replace);
            } elseif(function_exists("mb_convert_encoding")) {
                $replace = mb_convert_encoding($replace, CHARSET);
            } elseif(function_exists("iconv")) {
                $replace = iconv('ISO-8859-1', CHARSET.'//IGNORE',$replace);
            }
            $replace = explode(",",$replace);
            $string = str_replace(array_values(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)), $replace, $string);
        } else {
            $string = html_entity_decode($string,ENT_QUOTES,CHARSET);
        }
        return $string;
    }

// ------------------------------------------------------------------------------    
// Erlaubte Sonderzeichen als RegEx zurückgeben
// ------------------------------------------------------------------------------
    function getSpecialCharsRegex() {

        $regex = "/^[a-zA-Z0-9_\%\-\s\?\!\@\.€".addslashes($this->getHtmlEntityDecode(implode("", get_html_translation_table(HTML_ENTITIES, ENT_QUOTES))))."]+$/";
        $regex = str_replace("&#39;", "'", $regex);
        return $regex;
    }

// ------------------------------------------------------------------------------    
// Inhaltsseiten/Kategorien für Speicherung umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
    function replaceSpecialChars($text,$nochmal_erlauben) {
        # $nochmal_erlauben = für Tags mit src z.B. img dann muss das % auch gewandelt werden
        $text = str_replace('/','ssslashhh',$text);
        if(preg_match('#\%([0-9a-f]{2})#ie',$text) < 1)
            $text = rawurlencode(stripslashes($text));
        if($nochmal_erlauben)
            $text = rawurlencode(stripslashes($text));
        $text = str_replace('ssslashhh','/',$text);
        return $text;
    }

// ------------------------------------------------------------------------------    
// Umlaute in Inhaltsseiten/Kategorien für Anzeige 
// ------------------------------------------------------------------------------
    function rebuildSpecialChars($text, $rebuildnbsp, $html) {
        $text = rawurldecode($text);
        if($html) {
            $test = htmlentities($text,ENT_COMPAT,CHARSET);
# hier muss noch geschraubt werden iconv gibts auf manchen systemen nicht!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            if(empty($test) and function_exists("iconv")) {
                # htmlentities gibt einen leeren sring zurück wenn im string ein unbekantes zeichen ist
                # iconv entfernt es einfach
                $test = htmlentities(@iconv(CHARSET,CHARSET.'//IGNORE',$text),ENT_COMPAT,CHARSET);
            }
            $text = $test;
            $text = str_replace('&amp;#','&#',$text);
        }
        // Leerzeichen
        if ($rebuildnbsp and !$html)
            $text = str_replace(" ", "&nbsp;", $text);
        elseif(!$rebuildnbsp and $html)
            $text = str_replace("&nbsp;", " ", $text);
        return $text;
    }


// ------------------------------------------------------------------------------    
// Für Datei-Uploads erlaubte Sonderzeichen als RegEx zurückgeben
// ------------------------------------------------------------------------------
    function getFileCharsRegex() {
        $regex = "/^[a-zA-Z0-9_\%\-\.]+$/";
        return $regex;
    }

// ------------------------------------------------------------------------------    
// Für Datei-Uploads erlaubte Sonderzeichen userlesbar als String zurückgeben
// ------------------------------------------------------------------------------
    function getFileCharsString($sep, $charsperline) {
        $filecharsstring = "";
        $filecharshtml = "";
        for ($i=65; $i<=90;$i++)
            $filecharsstring .= chr($i);
        for ($i=97; $i<=122;$i++)
            $filecharsstring .= chr($i);
        for ($i=48; $i<=57;$i++)
            $filecharsstring .= chr($i);
        $filecharsstring .= "_-.";
        for ($i=0; $i<=strlen($filecharsstring); $i+=$charsperline) {
            $filecharshtml .= htmlentities(substr($filecharsstring, $i, $charsperline),ENT_COMPAT,CHARSET)."<br />";
        }
        return $filecharshtml;
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Wandelt numerische Entities im übergebenen Text in Zeichen
// ------------------------------------------------------------------------------
    function numeric_entities_decode($text) {
        return str_replace('&amp;#', '&#', $text);
    }

// ------------------------------------------------------------------------------
// Geschütze zeichen codieren
// ------------------------------------------------------------------------------
    function encodeProtectedChr($text) {# protected
        # alle geschützten zeichen suchen und in html code wandeln auch das ^
        $text = preg_replace("/\^(.)/Umsi", "'&#94;&#'.ord('\\1').';'", $text);
        return $text;
    }

// ------------------------------------------------------------------------------
// Geschütze zeichen wieder herstellen
// ------------------------------------------------------------------------------
    function decodeProtectedChr($text) {
        # alle &#94;&#?????; suchen und als zeichen ohne &#94; (^) ersetzen
        $text = preg_replace("/&#94;&#(\d{2,5});/", "chr('\\1')", $text);
        return $text;
    }

}
?>