<?php

function getSearchString($searchstring,$array = true) {
    if(trim($searchstring) != "") {
        $string = preg_replace("/[\"|\'](.*)[\"|\']/Umsie", "str_replace(\" \",\"%20\",'\\1')", $searchstring);
        $return_array = array();
        $matches = explode(" ",$string);
        foreach($matches as $string) {
            $string = str_replace("%20"," ",trim($string));
            if(!empty($string))
                $return_array[] = $string;
        }
        if($array)
            return $return_array;
        else
            return implode(",", $return_array);
    } else
        return false;
}


function searchInPages() {
    global $CMS_CONF;
    global $QUERY_REQUEST;
    global $specialchars;
    global $language;
    global $CatPage;

    if(!$highlightparameter = getSearchString($QUERY_REQUEST,false))
        return false;

    $include_pages = array(EXT_PAGE);
    if($CMS_CONF->get("showhiddenpagesinsearch") == "true")
        $include_pages = array(EXT_PAGE,EXT_HIDDEN);

    // Kategorien-Verzeichnis einlesen
    $categoriesarray = $CatPage->get_CatArray(false, false, $include_pages);
    $matchingpages = array();
    // Alle Kategorien durchsuchen
    foreach ($categoriesarray as $currentcategory) {
        $contentarray = $CatPage->get_PageArray($currentcategory,$include_pages,true);
        // Alle Inhaltsseiten durchsuchen
        foreach ($contentarray as $currentcontent) {
            // Treffer in der aktuellen Seite?
            if (searchPage($currentcategory,$currentcontent)) {
                $matchingpages[$currentcategory][$currentcontent] = "true";
            }
        }
    }
    $searchresults = "";
    $matchesoverall = 0;
    foreach ($matchingpages as $cat => $tmp) {
        $categoryname = $CatPage->get_HrefText($cat,false);
        $searchresults .= "<h2>$categoryname</h2><ul>";
        if(!isset($matchingpages[$cat])) continue;
        foreach ($matchingpages[$cat] as $page => $tmp) {
            if($tmp != "true") continue;
            $matchesoverall++;
            $pagename = $CatPage->get_HrefText($cat,$page);
            $searchresults .= "<li>".
                $CatPage->create_LinkTag($CatPage->get_Href($cat,$page,"highlight=".$specialchars->replaceSpecialChars($highlightparameter,false)),
                highlightSearch($pagename,$highlightparameter),
                false,
                $titel = $language->getLanguageValue2("tooltip_link_page_2", $pagename, $categoryname)
                ).
                "</li>";
        }
        $searchresults .= "</ul>";
    }
    // Keine Inhalte gefunden?
    if ($matchesoverall == 0)
        $searchresults .= $language->getLanguageValue0("message_nodatafound_0", trim($QUERY_REQUEST));
    // Rueckgabe des Menues
    return array($searchresults, $language->getLanguageValue0("message_search_0"), $language->getLanguageValue1("message_searchresult_1", (trim($QUERY_REQUEST))));
}

function searchPage($cat,$page) {
    global $syntax;
    global $QUERY_REQUEST;
    global $specialchars;
    global $CatPage;

    if(!$queryarray = getSearchString($QUERY_REQUEST))
        return false;

    // Dateiinhalt auslesen, wenn vorhanden...
    if (false !== ($pagecontent = $CatPage->get_PageContent($cat,$page))) {
        if(empty($pagecontent))
            return false;
        $pagecontent = $syntax->convertContent($pagecontent, $cat, true);
        # alle Komentare raus
        $pagecontent = preg_replace("/\<!--(.*)-->/Umsi"," ", $pagecontent);
        # alle script, select, object, embed sachen raus
        $pagecontent = preg_replace("/\<script(.*)\<\/script>/Umsi", "", $pagecontent);
        $pagecontent = preg_replace("/\<select(.*)\<\/select>/Umsi", "", $pagecontent);
        $pagecontent = preg_replace("/\<object(.*)\<\/object>/Umsi", "", $pagecontent);
        $pagecontent = preg_replace("/\<embed(.*)\<\/embed>/Umsi", "", $pagecontent);
        # alle tags raus
        $pagecontent = strip_tags($pagecontent);
        # Achtung strtolower macht keine Umlaute deshalb bleiben Sonderzeichen in html
        $pagecontent = strtolower($pagecontent);
        # nach alle Suchbegrieffe suchen
        foreach($queryarray as $query) {
            if ($query == "")
                continue;
            $query = $specialchars->rebuildSpecialChars($query, false, true);
            // Wenn...
            if (
                // ...der aktuelle Suchbegriff im Seitennamen...
                (substr_count(strtolower($CatPage->get_HrefText($cat,$page)), strtolower($query)) > 0)
                // ...oder im eigentlichen Seiteninhalt vorkommt
                or (substr_count($pagecontent, strtolower($query)) > 0)
                ) {
                // gefunden
                return true;
            }
        }
    } else
        return false;
}

// ------------------------------------------------------------------------------
// Phrasen in Inhalt hervorheben
// ------------------------------------------------------------------------------
function highlightSearch($content, $phrasestring) {
    global $specialchars;
    global $syntax;
    # in $syntax den content setzen
    $syntax->content = $content;
    # alle script style sachen mit dumy ersetzen
    $syntax->find_script_style();
    // Zu highlightende Begriffe kommen kommasepariert ("begriff1,begriff2")-> in Array wandeln
    $phrasearray = explode(",", $phrasestring);
    // jeden Begriff highlighten
    foreach($phrasearray as $phrase) {
        $phrase = $specialchars->rebuildSpecialChars($phrase, false, true);
        // Regex-Zeichen im zu highlightenden Text escapen (.\+*?[^]$(){}=!<>|:)
        $phrase = preg_quote($phrase);
        // Slashes im zu highlightenden Text escapen
        $phrase = preg_replace("/\//", "\\\\/", $phrase);
        # die such worte hervorheben
#!!!!!! wir brauchen eine regex die nicht in script style tags text hervorhebt
# dann kann auch das find_script_style() wieder raus
        $syntax->content = preg_replace("/((<[^>]*)|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $syntax->content); 
    }
    # alle script style sachen wieder einsetzen
    $syntax->find_script_style(false);
    # inhalt zurück
    return $syntax->content;
}

?>