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
// Erlaubte Sonderzeichen userlesbar als String zurückgeben
// ------------------------------------------------------------------------------
    function getSpecialCharsString($sep, $charsperline) {
        global $CHARSET;
        $specialcharsstring = "";
        $specialcharshtml = "";
        for ($i=65; $i<=90;$i++)
            $specialcharsstring .= chr($i);
        for ($i=97; $i<=122;$i++)
            $specialcharsstring .= chr($i);
        for ($i=48; $i<=57;$i++)
            $specialcharsstring .= chr($i);
        $specialcharsstring .= html_entity_decode("_- ?!€@.".stripslashes(preg_replace("/&#39;/", "\'", implode(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)))),ENT_COMPAT,$CHARSET);
        for ($i=0; $i<=strlen($specialcharsstring); $i+=$charsperline) {
            $specialcharshtml .= htmlentities(substr($specialcharsstring, $i, $charsperline),ENT_COMPAT,$CHARSET)."<br />";
        }
        return $specialcharshtml;
    }
    
// ------------------------------------------------------------------------------    
// Inhaltsseiten/Kategorien für Speicherung umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
    function replaceSpecialChars($text,$nochmal_erlauben) {
        # $nochmal_erlauben = für Tags mit src z.B. img dann muss das % auch gewndelt werden
        $text = str_replace('/','ssslashhh',$text);
#        $text = str_replace('/','%2F',$text);

        if(preg_match('#\%([0-9a-f]{2})#ie',$text) < 1)
            $text = rawurlencode(stripslashes($text));
        if($nochmal_erlauben)
            $text = rawurlencode(stripslashes($text));
#        $text = str_replace('ssslashhh','/',$text);
        $text = str_replace('ssslashhh','%2F',$text);

        return $text;
    }


// ------------------------------------------------------------------------------    
// Umlaute in Inhaltsseiten/Kategorien für Anzeige 
// ------------------------------------------------------------------------------
    function rebuildSpecialChars($text, $rebuildnbsp, $html) {
        global $CHARSET;
        $text = rawurldecode($text);
        if($html) {
            $text = htmlentities($text,ENT_COMPAT,$CHARSET);
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