<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class SpecialChars {
    
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
    function SpecialChars(){
    }

// ------------------------------------------------------------------------------    
// Erlaubte Sonderzeichen als RegEx zurückgeben
// ------------------------------------------------------------------------------
    function getSpecialCharsRegex() {
        global $CHARSET;
        $regex = "/^[a-zA-Z0-9_\%\-\s\?\!\@\.€".addslashes(html_entity_decode(implode("", get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)),ENT_COMPAT,$CHARSET))."]+$/";
        $regex = preg_replace("/&#39;/", "\'", $regex);
        return $regex;
    }

// ------------------------------------------------------------------------------    
// Inhaltsseiten/Kategorien für Speicherung umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
    function replaceSpecialChars($text,$nochmal_erlauben) {
        # $nochmal_erlauben = für Tags mit src z.B. img dann muss das % auch gewndelt werden
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
        global $CHARSET;
        $text = rawurldecode($text);
        if($html) {
            $test = htmlentities($text,ENT_COMPAT,$CHARSET);
            if(empty($test)) {
                # htmlentities gibt einen lehren sring zurück wenn im string ein unbekantes zeichen ist
                # iconv entfernt es einfach
                $test = htmlentities(@iconv($CHARSET,$CHARSET.'//IGNORE',$text),ENT_COMPAT,$CHARSET);
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
        global $CHARSET;
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
            $filecharshtml .= htmlentities(substr($filecharsstring, $i, $charsperline),ENT_COMPAT,$CHARSET)."<br />";
        }
        return $filecharshtml;
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: Wandelt numerische Entities im übergebenen Text in Zeichen
// ------------------------------------------------------------------------------
    function numeric_entities_decode($text) {
        return str_replace('&amp;#', '&#', $text);
    }

}
?>