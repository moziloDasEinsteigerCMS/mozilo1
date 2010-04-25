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
    global $CONTENT_DIR_REL;
    global $EXT_LINK;
    global $CMS_CONF;
    global $QUERY_REQUEST;
    global $URL_BASE;
    global $specialchars;
    global $language;

    if(!$highlightparameter = getSearchString($QUERY_REQUEST,false))
        return false;

    $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinsearch") == "true");
    // Kategorien-Verzeichnis einlesen
    $categoriesarray = getDirContentAsArray($CONTENT_DIR_REL, false, false);
    $matchingpages = array();
    // Alle Kategorien durchsuchen
    foreach ($categoriesarray as $currentcategory) {
        // Wenn die Kategorie keine Contentseiten hat, direkt zur naechsten springen
        $contentarray = getDirContentAsArray($CONTENT_DIR_REL.$currentcategory, true, $showhiddenpages);
        if ($contentarray == "") {
            continue;
        }
        // Alle Inhaltsseiten durchsuchen
        foreach ($contentarray as $currentcontent) {
            # wenns ein link ist
            if(substr($currentcontent,-(strlen($EXT_LINK))) == $EXT_LINK) {
                continue;
            }
            // Treffer in der aktuellen Seite?
            if (searchPage($currentcategory,$currentcontent)) {
                $matchingpages[$currentcategory][$currentcontent] = "true";
            }
        }
    }
    $searchresults = "";
    $matchesoverall = 0;
    foreach ($matchingpages as $cat => $tmp) {
        $categoryname = catToName($cat, false);
        $searchresults .= "<h2>$categoryname</h2><ul>";
        if(!isset($matchingpages[$cat])) continue;
        foreach ($matchingpages[$cat] as $page => $tmp) {
            if($tmp != "true") continue;
            $matchesoverall++;
            $url = "index.php?cat=".substr($cat,3)."&amp;page=".substr($page, 3, strlen($page) - 7)."&amp;";
            if($CMS_CONF->get("modrewrite") == "true") {
                $url = $URL_BASE.substr($cat,3)."/".substr($page, 3, strlen($page) - 7).".html?";
            }
            $pagename = pageToName($page, false);
            $filepath = $CONTENT_DIR_REL.$cat."/".$page;
            $searchresults .= "<li>".
                "<a href=\"".$url.
                "highlight=".$specialchars->replaceSpecialChars($highlightparameter,false)."\"".
                getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $pagename, $categoryname)).">".
                highlightSearch($pagename,$highlightparameter).
                "</a>".
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
    global $activ_plugins;
    global $deactiv_plugins;
    global $syntax;
    global $QUERY_REQUEST;
    global $CONTENT_DIR_REL;
    global $specialchars;

    $filepath = $CONTENT_DIR_REL.$cat."/".$page;

    if(!$queryarray = getSearchString($QUERY_REQUEST))
        return false;

    // Dateiinhalt auslesen, wenn vorhanden...
    if (filesize($filepath) > 0) {
        $pagecontent = implode(file($CONTENT_DIR_REL.$cat."/".$page));
        $pagecontent = $syntax->convertContent($pagecontent, $cat, true);
        list($pagecontent,$css) = replacePluginVariables($pagecontent,$activ_plugins,$deactiv_plugins);
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
                (substr_count(strtolower(pageToName($page, false)), strtolower($query)) > 0)
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
    // Zu highlightende Begriffe kommen kommasepariert ("begriff1,begriff2")-> in Array wandeln
    $phrasearray = explode(",", $phrasestring);
    // jeden Begriff highlighten
    foreach($phrasearray as $phrase) {
        $phrase = $specialchars->rebuildSpecialChars($phrase, false, true);
        // Regex-Zeichen im zu highlightenden Text escapen (.\+*?[^]$(){}=!<>|:)
        $phrase = preg_quote($phrase);
        // Slashes im zu highlightenden Text escapen
        $phrase = preg_replace("/\//", "\\\\/", $phrase);
        $content = preg_replace("/((<[^>]*|{CONTACT})|$phrase)/ie", '"\2"=="\1"? "\1":"<span class=\"highlight\">\1</span>"', $content); 
    }
    return $content;
}


?>