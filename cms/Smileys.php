<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class Smileys {
    
    var $smileysarray;
    
    function Smileys($path) {
        $smileys = new Properties("$path/smileys.conf");
        $this->smileysarray = $smileys->toArray();
    }
    
    function replaceEmoticons($content) {
        global $URL_BASE;
        global $CMS_DIR_NAME;

        foreach ($this->smileysarray as $icon => $emoticon) {
            if($icon == "readonly" or $icon == "error") {
                continue;
            }
            $emoticon = preg_replace("/(\.|\!|\?|\(|\)|\'|\||\\\\|\/)/", "\\\\$1", $emoticon);
            // Bedingungen, unter denen Emoticons NICHT ersetzt werden sollen
            $regex =     "/(".
                                    "(".
                                        // nicht zwischen <a ...> und </a> (Links)
                                        "(<a\s[^<>]*>[^<>]*(\:".$icon."\:)[^<>]*<\/a>)".
                                        // nicht zwischen <em class="deadlink"> and </em> (Deadlinks)
                                        "|(<em\sclass=\"deadlink\"[^<>]*>[^<>]*(\:".$icon."\:)[^<>]*<\/em>)".
                                        // nicht zwischen < und > (HTML-Tags)
                                        "|(<[^>]*(\:".$icon."\:).*>)".
                                        // nicht bei HTML-Entities gefolgt von Klammer - z.B. &quot;) oderr &#93;)
                                        "|([\&\w{2,7}|\&\#\d?](\:".$icon."\:))".
                                    ")".
                                    "|(\:".$icon."\:)".
                                ")/Uie";
            # ACHTUNG der Modifikator e maskiert alle ', ", \ und NULL deshalb stripslashes("\1")
            $content = preg_replace($regex, '"\2"=="\1"? stripslashes("\1"):"$12<img src=\"".$URL_BASE.$CMS_DIR_NAME."/smileys/".$icon.".gif\" class=\"noborder\" alt=\"".stripslashes($emoticon)."\" />$14"', $content);
        }
        return $content;
    }
    
    function getSmileysArray() {
        return $this->smileysarray;
    }    
}

?>