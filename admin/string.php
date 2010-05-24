<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/



function createTooltipWZ($title, $body,$parameter = NULL) {
    global $BASIC_LANGUAGE;
    if($title != "" and $body == "") {
        if(!isset($BASIC_LANGUAGE->properties[$title]))
            return NULL;
        return ' onmouseover="Tip(\'<b>'.str_replace("'","\'",trim(getLanguageValue($title))).'</b>\''.$parameter.')" onmouseout="UnTip()"';
    } elseif($title == "" and $body != "") {
        if(!isset($BASIC_LANGUAGE->properties[$body]))
            return NULL;
        return ' onmouseover="Tip(\''.str_replace("'","\'",trim(getLanguageValue($body))).'\''.$parameter.')" onmouseout="UnTip()"';
    } else {
        if(!isset($BASIC_LANGUAGE->properties[$title]) and !isset($BASIC_LANGUAGE->properties[$body]))
            return NULL;
        return ' onmouseover="Tip(\'<b>'.str_replace("'","\'",trim(getLanguageValue($title))).'</b><br />'.str_replace("'","\'",trim(getLanguageValue($body))).'\''.$parameter.')" onmouseout="UnTip()"';
    }
}

function createUserTooltipWZ($title, $body,$parameter = NULL) {
    if($title != "" and $body == "") {
        return ' onmouseover="Tip(\'<b>'.str_replace("'","\'",trim($title)).'</b>\''.$parameter.')" onmouseout="UnTip()"';
    } elseif($title == "" and $body != "") {
        return ' onmouseover="Tip(\''.str_replace("'","\'",trim($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    } else {
        return ' onmouseover="Tip(\'<b>'.str_replace("'","\'",trim($title)).'</b><br />'.str_replace("'","\'",trim($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    }
}

?>