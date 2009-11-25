<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/



/*

        moziloGB - Smileys.php
        
        A useful addition to moziloWiki, which replaces 
        Emoticons with graphical smileys. 
        Feel free to change it to your personal purposes.

        Arvid Zimmermann 2007 <moziloWiki@azett.com>
        
*/
// require_once("Properties.php"); // unnötig, da bereits in index.php includiert

class Smileys {
    
    var $smileysarray;
    
    function Smileys($path) {
        $smileys = new Properties("$path/smileys.conf");
        $this->smileysarray = $smileys->toArray();
    }
    
    function replaceEmoticons($content) {
        foreach ($this->smileysarray as $icon => $emoticon) {
            if($icon == "readonly" or $icon == "error") {
                continue;
            }
            $emoticon = preg_replace("/(\.|\!|\?|\(|\)|\'|\||\\\\|\/)/", "\\\\$1", $emoticon);
            // conditions NOT to replace emoticons
            $regex =     "/(".
                                    "(".
                                        // not between <p class="code"> and </p> (code areas) - STILL TO REWORK
                                        //"(<p\sclass=\"code\"[^<>]*>[^<>]*(".$smil1."|".$smil2.")[^<>]*<\/p>)|".
                                        // not between <a ...> and </a> (link texts)
                                        "(<a\s[^<>]*>[^<>]*(\:".$icon."\:|".$emoticon.")[^<>]*<\/a>)".
                                        // not between <em class="deadlink"> and </em> (link texts)
                                        "|(<em\sclass=\"deadlink\"[^<>]*>[^<>]*(\:".$icon."\:|".$emoticon.")[^<>]*<\/em>)".
                                        // not between < and > (inside html tags)
                                        "|(<[^>]*(\:".$icon."\:|".$emoticon.").*>)".
                                        // not with HTML entities followes by bracket, like &quot;) or &#93;)
                                        "|([\&\w{2,7}|\&\#\d?](\:".$icon."\:|".$emoticon."))".
                                    ")".
                                    //"|((\&nbsp;|\b|\s|\t|\n|\r|>)(\:".$icon."\:|".$emoticon.")(\b|\s|\t|\n|\r|<|\&nbsp;))".
                                    "|(\:".$icon."\:|".$emoticon.")".
                                ")/Uie";
            $content = preg_replace($regex, '"\2"=="\1"? "\1":"$12<img src=\"smileys/".$icon.".gif\" class=\"noborder\" alt=\"".stripslashes($emoticon)."\" />$14"', $content);
        }
        return $content;
    }
    
    function getSmileysArray() {
        return $this->smileysarray;
    }    
}

?>