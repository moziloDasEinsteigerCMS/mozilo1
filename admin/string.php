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


    /* ------------------------------------------------- 
    @author: Oliver Lorenz
    
    Erstellt aus dem Language-File den 
    Tooltip-Text
    --------------------------------------------------
    function createBasicTooltip($title, $body) 
    {
        createComplexTooltip($title, $body, "1", "75", "150"); 
    }
*/
    /* ------------------------------------------------- 
    @author: Oliver Lorenz
    Erstellt aus dem Language-File den 
    Tooltip-Text
    --------------------------------------------------
    function createNormalTooltip($title, $body, $laenge) 
    {
        return createComplexTooltip($title, $body, $laenge, 1, 85); 
    }
*/

    /* ------------------------------------------------- 
    @author: Oliver Lorenz
    Erstellt aus dem Language-File den 
    Tooltip-Text
    --------------------------------------------------*
    function createComplexTooltip($title, $body, $laenge, $rahmen, $transparenz ) 
    {
        if(showTooltips()=="true")
        {
            if($body=="")
            {
                return "tooltip:".trim(getLanguageValue($title)).";;$laenge;$rahmen;$transparenz;100";
            }
            else
            {
                return "tooltip:".trim(getLanguageValue($title)).";".trim(getLanguageValue($body)).";$laenge;$rahmen;$transparenz;100";
            }
        }
    }
    /
    /* ------------------------------------------------- 
    @author: Oliver Lorenz
    
    Haengt an eine einstellige Zahl eine vorangestellte
    0 an
    --------------------------------------------------
    function addFrontZero($number) 
    {
        return sprintf("%02d",$number);
#        if(strlen($number)<2)
#        {
#            return "0".$number;
#        }
#        else
#        {
#            return $number;
#        }
    }*/
    
    
?>