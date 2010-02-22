<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/



function createTooltipWZ($title, $body,$parameter = NULL) 
{
    global $BASIC_LANGUAGE;
    if($title != "" and $body == "") {
        if(!isset($BASIC_LANGUAGE->properties[$title]))
            return NULL;
        return ' onmouseover="Tip(\'<b>'.trim(getLanguageValue($title)).'</b>\''.$parameter.')" onmouseout="UnTip()"';
    } elseif($title == "" and $body != "") {
        if(!isset($BASIC_LANGUAGE->properties[$body]))
            return NULL;
        return ' onmouseover="Tip(\''.trim(getLanguageValue($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    } else {
        if(!isset($BASIC_LANGUAGE->properties[$title]) and !isset($BASIC_LANGUAGE->properties[$body]))
            return NULL;
        return ' onmouseover="Tip(\'<b>'.trim(getLanguageValue($title)).'</b><br />'.trim(getLanguageValue($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    }
}
    
?>