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
    if($title != "" and $body == "") {
        return ' onmouseover="Tip(\'<b>'.trim(getLanguageValue($title)).'</b>\''.$parameter.')" onmouseout="UnTip()"';
    } elseif($title == "" and $body != "") {
        return ' onmouseover="Tip(\''.trim(getLanguageValue($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    } else {
        return ' onmouseover="Tip(\'<b>'.trim(getLanguageValue($title)).'</b><br>'.trim(getLanguageValue($body)).'\''.$parameter.')" onmouseout="UnTip()"';
    }
}
    
?>