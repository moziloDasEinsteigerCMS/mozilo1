<?php
# die übergebenen variablen $cat und $page können in dieser form
# übergeben werden müssen aber urlcodiert sein (wie im filesystem)
#   nur name
#   mit position
#   mit endung
#   mit allen

#   Beispiel:
#   catpage
#   01_catpage
#   catpage.txt
#   01_catpage.txt
#   01_link-_blank-http://www.test.de

class CatPageClass {
    # mit $CatPageArray wird gearbeitet
    var $CatPageArray = array();
    # $OrgCatPageArray ist für die widerherstelung zuständig z.B. undelete_Page()
    var $OrgCatPageArray = array();
    # $SyntaxIncludeRemember wird zur verhinderung von syntax_include endlosschleife und
    # um bei Syntaxelementen die in include page sind die richtige cat zu ermiteln
    var $SyntaxIncludeRemember = array();
    # bei cat page als link müssen diese zeichen wiederhergestelt werden
    # wird im ["_link-"] benutzt
    var $link_search = array("%3A","%2F","%3F","%26","%3D","%23");
    var $link_replace = array(":","/","?","&amp;","=","#");
    function CatPageClass() {
        if(defined("isCatPage"))
            die("die class CatPage darf nur einmal initaliesiert werden");
        $this->CatPageArray = $this->make_DirCatPageArray(CONTENT_DIR_REL);
        $this->OrgCatPageArray = $this->CatPageArray;
        define("isCatPage",true);
    }

    function get_FirstCatPage() {
        global $CMS_CONF;
        $pages = array(EXT_PAGE);
        if($CMS_CONF->get("showhiddenpagesasdefaultpage") == "true") {
            $pages = array(EXT_PAGE,EXT_HIDDEN);
        }
        $firstcat = $this->get_CatArray(false,false,$pages);
        $firstcat = current($firstcat);
        if($firstcat) {
            $firstpage = $this->get_PageArray($firstcat,$pages,true);
            $firstpage = current($firstpage);
            return array($firstcat,$firstpage);
        }
        return array("","");
    }

    function get_FirstPageOfCat($cat) {
        $cat = $this->get_AsKeyName($cat);
        global $CMS_CONF;
        $pages = array(EXT_PAGE);
        if($CMS_CONF->get("showhiddenpagesasdefaultpage") == "true") {
            $pages = array(EXT_PAGE,EXT_HIDDEN);
        }
        $firstpage = $this->get_PageArray($cat,$pages,true);
        $firstpage = current($firstpage);
        if($firstpage) {
            return $firstpage;
        }
        return false;
     }

    # Erzeugt ein durchnummeriertes array mit cats in richtiger Reihenfolge
    # $any = true # alle cats $containspage werden übergangen
    # $showlink = false # keine catlinks ins array
    # $containspage = array(ext.) # mit page ext. die enthalten sein müssen damit cat ins array geht
    # Default = array mit catlinks und cat mit midestens einer normalen page oder link
    function get_CatArray($any = false, $showlink = true, $containspage = false) {
        $return = array();
        # Default $containspage array erzeugen
        if(!$containspage or !is_array($containspage))
            $containspage = array(EXT_PAGE, EXT_LINK);
        foreach($this->CatPageArray as $cat => $info) {
            # wenn cat ein Link ist und $showlink = true ist
            if($showlink and $info['_type-'] == EXT_LINK) {
                $return[] = $cat;
                continue;
            # wenn cat ein Link ist und $showlink = false ist
            } elseif(!$showlink and $info['_type-'] == EXT_LINK)
                continue;
            # alle cat
            if($any)
                $return[] = $cat;
            else {
                # nur cat zulassen wenn auch pages mit array($containspage) vorhanden sind
                if(count($this->get_PageArray($cat,$containspage,true)) > 0)
                    $return[] = $cat;
            }
        }
        return $return;
    }


    # Erzeugt ein durchnummeriertes array mit pages in richtiger Reihenfolge
    # $cat = cat
    # $showext = array() mit ext. die ins array solen
    # $hidecatnamedpages = true $CMS_CONF->get("hidecatnamedpages") wird übergangen
    # Default = alle pages der cat mit normalen page und links und $CMS_CONF->get("hidecatnamedpages")
    # wird benutzt
    function get_PageArray($cat,$showext = false,$hidecatnamedpages = false) {
        global $CMS_CONF;
        if(!$hidecatnamedpages and $CMS_CONF->get("hidecatnamedpages") == "true")
            $hidecatnamedpages = true;
        $cat = $this->get_AsKeyName($cat);
        $return = array();
        # Default page arten erzeugen
        if(!$showext or !is_array($showext))
            $showext = array(EXT_PAGE, EXT_LINK);
        if(isset($this->CatPageArray[$cat]['_pages-'])) {
            foreach($this->CatPageArray[$cat]['_pages-'] as $page => $info) {
                # wenn page art nicht in $showext array ist nächste page
                if(!in_array($info['_type-'],$showext))
                    continue;
                # wenn catname gleich pagename nächste page
                if($hidecatnamedpages and $cat == $page)
                    continue;
                $return[] = $page;
            }
        }
        return $return;
    }

    # gibt die files die im cat dateien ordner sind als array zurück
    # wenn nichts gefunden dann mit array() zurück
    # $only_ext = false oder array() dann werden nur die files zurückgeben die die extension haben
    # gross/kleinschreibung ist egal. Achtung mit Punkt angeben
    # z.B. array(".jpg",".png")
    function get_FileArray($cat,$only_ext = false) {
        $cat = $this->get_AsKeyName($cat);
        $return_array = array();
        # nur wens auch files array gibt
        if(isset($this->CatPageArray[$cat]['_files-'])) {
            # alle files zurück
            if($only_ext === false) {
                $return_array = $this->CatPageArray[$cat]['_files-'];
            # nur die files die extension haben die in $only_ext enthalten sind
            } elseif(is_array($only_ext) and count($only_ext) > 0) {
                # alle ext im array in kleinschreibung wandeln
                $only_ext = array_map('strtolower', $only_ext);
                foreach($this->CatPageArray[$cat]['_files-'] as $file) {
                    if(in_array($this->get_FileType($file),$only_ext))
                        $return_array[] = $file;
                }
            }
        }
        return $return_array;
    }

    # gibt die extension von $file kleingeschrieben zurück
    # es wird das als extension angesehen was nach dem letzten punk ist
    function get_FileType($file) {
        # ab denn letzen punkt ist die ext
        $type = substr($file,strrpos($file,"."));
        if(strlen($type) > 1)
            # kleingeschrieben zurück
            return strtolower($type);
        return false;
    }

    # Erzeugt einen HTML Link
    # $url = TEXT
    # $urltext = TEXT
    # $css = false oder TEXT
    # $titel = false oder TEXT
    # $target = false oder _blank, _self
    # $id = false oder TEXT
    function create_LinkTag($url,$urltext,$css = false,$titel = false,$target = false,$id = false) {
        global $syntax;
        $linkcss = NULL;
        # ist $css ein TEXT wird ein class atribut erzeugt ansonsten nicht
        if($css !== false)
            $linkcss = ' class="'.$css.'"';
        $linktitel = NULL;
        # ist $titel ein TEXT wird ein titel atribut erzeugt ansonsten nicht
        if($titel !== false)
            $linktitel = $syntax->getTitleAttribute($titel);
        $linktarget = NULL;
        if($target !== false)
            $linktarget = ' target="'.$target.'"';
        $linkid = NULL;
        if($id !== false)
            $linkid = ' id="'.$id.'"';
        return '<a href="'.$url.'"'.$linkcss.$linktitel.$linktarget.$linkid.'>'.$urltext.'</a>';
    }

    # erzeugt einen default link wie im menue mit default tooltips und setzt in automatisch activ
    # $css = css class, wenn cat oder page activ wird an die class ein "active" angehängt
    function create_AutoLinkTag($cat,$page,$css) {
        global $language;
        if($page !== false) {
            if($this->get_Type($cat,$page) == EXT_LINK) {
                $title = $language->getLanguageValue1("tooltip_link_extern_1", $this->get_HrefText($cat,$page));
                $target = $this->get_HrefTarget($cat,$page);
            } else {
                $title = $language->getLanguageValue2("tooltip_link_page_2", $this->get_HrefText($cat,$page),$this->get_HrefText($cat,false));
                $target = false;
            }
            return $this->create_LinkTag(
                    $this->get_Href($cat,$page),
                    $this->get_HrefText($cat,$page),
                    $css.$this->get_CssActiv($cat,$page),
                    $title,$target);
        }
        if($this->get_Type($cat,false) == EXT_LINK) {
            $title = $language->getLanguageValue1("tooltip_link_extern_1", $this->get_HrefText($cat,false));
            $target = $this->get_HrefTarget($cat,false);
        } else {
            $title = $language->getLanguageValue1("tooltip_link_category_1", $this->get_HrefText($cat,false));
            $target = false;
        }
        return $this->create_LinkTag(
                $this->get_Href($cat,false),
                $this->get_HrefText($cat,false),
                $css.$this->get_CssActiv($cat,false),
                $title,$target);
    }

    # gibt $activtext zurück wenn cat oder page activ ist
    # Default $activtext = active
    function get_CssActiv($cat,$page,$activtext = "active") {
        $req_cat = $this->get_AsKeyName(CAT_REQUEST);
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $req_page = $this->get_AsKeyName(PAGE_REQUEST);
            $page = $this->get_AsKeyName($page);
            if($cat == $req_cat and $page == $req_page and $this->get_Type($cat,$page) != EXT_LINK)
                return $activtext;
            return NULL;
        }
        if($cat == $req_cat and $this->get_Type($cat,false) != EXT_LINK)
            return $activtext;
        return NULL;
    }

    # gibt nur denn Namen zurück ohne Position, Endungen, Linksachen
    # wie er in $this->CatPageArray steht
    # $name kann sein z.B.
    #       01_catpage
    #       01_catpage.txt
    #       01_link-_blank-http://www.test.de
    # $change_chars = true, es werden sonderzeichen und htmltities nach %?? gewandelt
    # Achtung $change_chars nur benutzen wenn nötig wegen geschwindigkeit
    # Achtung wenn $change_chars = false wird auch false zurückgegeben
    # damit auf z.B. $page !== false geprüft werden kann
    function get_AsKeyName($name, $change_chars = false) {
        $ext = array(EXT_PAGE, EXT_HIDDEN, EXT_LINK, EXT_DRAFT);
        if(strpos($name,"-_self-") > 1)
            $name = substr($name,0,strpos($name,"-_self-"));
        if(strpos($name,"-_blank-") > 1)
            $name = substr($name,0,strpos($name,"-_blank-"));
        if(ctype_digit(substr($name,0,2)) and substr($name,2,1) == "_")
            $name = substr($name,3);
        if(in_array(substr($name,-(strlen(EXT_PAGE))),$ext))
            $name = substr($name,0,-(strlen(EXT_PAGE)));
        if($change_chars === true) {
            $name = $this->get_UrlCoded($name);
        }
        return $name;
    }

    # prüft ob $name text enthält also nicht boolean oder lehr ist
    # alle zeichen die trim() entfernt sind kein text
    function is_ParaString($name) {
        if($name === false or $name === true) {
            return false;
        }
        $name = trim($name);
        if(strlen($name) <= 0) {
            return false;
        }
        return true;
    }

    # wandelt $name von z.B. "Über uns" nach "%C3%9Cber%20uns"
    function get_UrlCoded($name,$protectUrlChr = false) {
        global $specialchars;
        $name = $specialchars->replaceSpecialChars($specialchars->getHtmlEntityDecode($name),false);
        if($protectUrlChr === true)
            $name = str_replace($this->link_search,$this->link_replace,$name);
        return $name;
    }

    function get_FileSystemName($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page])) {
                $pos = $this->CatPageArray[$cat]['_pages-'][$page]['_pos-'].'_';
                if($this->get_Type($cat,$page) == EXT_LINK) {
                    return $pos.$page.'-'.$this->CatPageArray[$cat]['_pages-'][$page]['_target-'].'-'.$this->CatPageArray[$cat]['_pages-'][$page]['_link-'].$this->CatPageArray[$cat]['_pages-'][$page]['_type-'];
                } else {
                    return $pos.$page.$this->CatPageArray[$cat]['_pages-'][$page]['_type-'];
                }
            } else
                return false;
        }
        if(isset($this->CatPageArray[$cat])) {
            $pos = $this->CatPageArray[$cat]['_pos-'].'_';
            if($this->get_Type($cat,false) == EXT_LINK) {
                return $pos.$cat.'-'.$this->CatPageArray[$cat]['_target-'].'-'.$this->CatPageArray[$cat]['_link-'].$this->CatPageArray[$cat]['_type-'];
            } else {
                return $pos.$cat;
            }
        }
        return false;
    }

    function get_CatPageWithPos($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            return $this->CatPageArray[$cat]['_pages-'][$page]['_pos-'].'_'.$page;
        }
        return $this->CatPageArray[$cat]['_pos-'].'_'.$cat;
    }

    # gibt anhand eines Syntaxelement cat:page string ein array($cat,$page) zurück
    # der Inhalt von array($cat,$page) ist filesystem konform formatiert
    # $syntax_catpage kann sein "nur page" oder "cat:page" wobei page auch eine datei
    # sein kann dann muss $file true sein
    # $default_cat = optionale cat wenn $CAT_REQUEST nicht zutrift z.B. bei include
    # $file = optinal und muss true sein wenn page eine datei ist
    # bei nur page wird wenn vorhanden $default_cat genommen ansonsten $CAT_REQUEST
    function split_CatPage_fromSyntax($syntax_catpage,$default_cat = false, $file = false) {
        $valuearray = explode(":", $syntax_catpage);
        # cat:page wurde übergeben
        if(count($valuearray) > 1) {
            $cat = $this->get_AsKeyName($valuearray[0], true);
            if($file === true) {
                $page = $this->get_UrlCoded($valuearray[1]);
            } else {
                $page = $this->get_AsKeyName($valuearray[1], true);
            }
            return array($cat,$page);
        }
        # es gibt nur page
        # wenn $default_cat übergeben wurde
        if($default_cat !== false) {
            $cat = $this->get_AsKeyName($default_cat, true);
        # ansonsten nim einfach $CAT_REQUEST
        } else {
            $cat = $this->get_AsKeyName(CAT_REQUEST);
        }
        if($file === true) {
            $page = $this->get_UrlCoded($valuearray[0]);
        } else {
            $page = $this->get_AsKeyName($valuearray[0], true);
        }
        # bei include pages Syntaxelementen wird meistens nur die page angegeben
        # deshalb ein versuch anhand des $this->SyntaxIncludeRemember arrays
        # die cat zu ermiteln
        if((count($this->SyntaxIncludeRemember) > 0)
            and (!$this->exists_CatPage($cat,$page)
                or !$this->exists_File($cat,$page))
        ) {
            foreach($this->SyntaxIncludeRemember as $tmp_cat_page => $tmp_page) {
                $tmp_array = explode(":", $tmp_cat_page);
                if($file === false and $this->exists_CatPage($tmp_array[0],$page)) {
                    $cat = $tmp_array[0];
                    break;
                } elseif($file === true and $this->exists_File($tmp_array[0],$page)) {
                    $cat = $tmp_array[0];
                    break;
                }
            }
        }
        return array($cat,$page);
    }

    function exists_CatPage($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]))
                return true;
            else
                return false;
        }
        if(isset($this->CatPageArray[$cat]))
            return true;
        return false;
    }

    function is_Protectet($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_protect-'])
                    and $this->CatPageArray[$cat]['_pages-'][$page]['_protect-'])
                return true;
            else
                return false;
        }
        if(isset($this->CatPageArray[$cat]['_protect-']) and $this->CatPageArray[$cat]['_protect-'])
            return true;
        return false;
    }

    function exists_File($cat,$file) {
        $cat = $this->get_AsKeyName($cat);
        if(isset($this->CatPageArray[$cat]['_files-'])) {
            $file = $this->get_UrlCoded($file);
            if(in_array($file,$this->CatPageArray[$cat]['_files-']))
                return true;
        }
        return false;
    }

    # gibt die art der cat page zurück
    # bei cat ist art = cat oder .lnk
    # bei page ist art = .txt, .hid, .tmp oder .lnk
    function get_Type($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_type-']))
                return $this->CatPageArray[$cat]['_pages-'][$page]['_type-'];
            else
                return NULL;
        }
        if(isset($this->CatPageArray[$cat]['_type-']))
            return $this->CatPageArray[$cat]['_type-'];
        return NULL;
    }

    # gibt den Timstamp der cat page zurück
    function get_Time($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_time-']))
                return $this->CatPageArray[$cat]['_pages-'][$page]['_time-'];
            else
                return NULL;
        }
        if(isset($this->CatPageArray[$cat]['_time-']))
            return $this->CatPageArray[$cat]['_time-'];
        return NULL;
    }
/*
    function has_CatPage($cat = false) {
        if($cat) {
            $cat = $this->get_AsKeyName($cat);
            if($this->CatPageArray[$cat]['_type-'] == EXT_LINK)
                return true;
            if(isset($this->CatPageArray[$cat]['_pages-']) and count($this->CatPageArray[$cat]['_pages-']) >= 1)
                return true;
        }
        return false;
    }
*/


    # erzeugt einen Query String anhand $_SERVER['QUERY_STRING'] und $query
    # wenn $query ein String ist werden die keys die in $query sind
    # aus $_SERVER['QUERY_STRING'] rausgenommen fals vorhanden
    # alle & werden nach $amp; gewandelt
    function get_Query($query = false) {
        if($query === false)
            return $_SERVER['QUERY_STRING'];
        $uri_query = array();
        if(strlen($_SERVER['QUERY_STRING']) > 1)
            $uri_query = explode("&",$_SERVER['QUERY_STRING']);
        $uri = array();
        foreach($uri_query as $para) {
            $key = explode("=",$para);
            if(!isset($key[1]))
                $key[1] = "QUERY_STRING_DUMMY";
            $uri[$key[0]] = $key[1];
        }
        $query = str_replace("&amp;","&",$query);
        if($query[0] == "&")
            $query = substr($query,1);
        $query = explode("&",$query);
        $query_string = NULL;
        foreach($query as $para) {
            $query_string .= "&".$para;
            $key = explode("=",$para);
            if(isset($uri[$key[0]]))
                unset($uri[$key[0]]);
        }
        foreach($uri as $key => $para) {
            if($para == "QUERY_STRING_DUMMY")
                $query_string .= "&".$key;
            else
                $query_string .= "&".$key."=".$para;
        }
        $query_string = substr($query_string,1);
        $query_string = str_replace("&","&amp;",$query_string);
        return $query_string;
    }

    # erzeugt eine url in abhängikeit von $CMS_CONF->get("modrewrite")
    # wenn $cat und $page = false oder nicht existieren wird nur index.php benutzt
    # $request = TEXT für url Parameter und alle & werden nach $amp; gewandelt
    function get_Href($cat,$page,$request = false) {
        global $CMS_CONF;
        global $specialchars;

        $requesturl = NULL;
        if($request !== false) {
            $request = str_replace("&amp;","&",$request);
            if($request[0] == "&")
                $request = substr($request,1);
            $request = str_replace("&","&amp;",$request);
            $requesturl = "?".$request;
        }
        if($cat !== false) {
            $cat = $this->get_AsKeyName($cat);
            # cat gibts nicht dann setzen wir auch $page auf false
            if(!isset($this->CatPageArray[$cat])) {
                $cat = false;
                $page = false;
            }
        }
        if($cat !== false and $page !== false) {
            $page = $this->get_AsKeyName($page);
            if(!isset($this->CatPageArray[$cat]['_pages-'][$page]))
                $page = false;
        }
        # wenn cat und page false sind
        if($cat === false and $page === false) {
            $dummy = ".php";
            if($CMS_CONF->get("modrewrite") == "true")
                $dummy = ".html";
            return URL_BASE."index".$dummy.$requesturl;
        }
        $cat = $this->get_AsKeyName($cat);
        # wenn cat ein link ist
        if($this->get_Type($cat,false) == EXT_LINK) {
            return $this->CatPageArray[$cat]['_link-'];
        }
        # wenn page ein link ist
        if($this->get_Type($cat,$page) == EXT_LINK) {
            return $this->CatPageArray[$cat]['_pages-'][$page]['_link-'];
        }
        $pageurl = NULL;
        $url = URL_BASE;
        if($CMS_CONF->get("modrewrite") == "true") {
            if($page !== false)
                $pageurl = "/".$page;
            $url .= $cat.$pageurl.".html".$requesturl;
        } else {
            if($request)
                $requesturl = "&amp;".$request;
            $caturl = "?cat=".$cat;
            if($page !== false)
                $pageurl = "&amp;page=".$page;
            $url .= "index.php".$caturl.$pageurl.$requesturl;
        }
        return $url;
    }

    # erzeugt eine url für Datei Download
    function get_HrefFile($cat,$datei) {
        $cat = $this->get_FileSystemName($cat,false);
        if($cat !== false and $this->exists_File($cat,$datei)) {
            global $specialchars;
            $datei = $this->get_UrlCoded($datei);
            return URL_BASE.CMS_DIR_NAME.'/download.php?cat='.$cat.'&amp;file='.$datei;
        }
        return false;
    }

    # erzeugt eine url für alle tags die src= verwenden
    # $twice = true ist nur nötig für src von z.B. einem flashplayer
    function get_srcFile($cat,$file,$twice = false) {
        $cat = $this->get_FileSystemName($cat,false);
        if($cat !== false and $this->exists_File($cat,$file)) {
            global $specialchars;
            $file = $this->get_UrlCoded($file);
            $file = $specialchars->replaceSpecialChars(URL_BASE.CONTENT_DIR_NAME."/".$cat."/".CONTENT_FILES_DIR_NAME."/".$file,true);
            if($twice === true)
                $file = str_replace("%","%25",$file);;
            return $file;
        }
        return false;
    }

    # gibt wenns ein link ist den target zurück ansonsten false
    function get_HrefTarget($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_target-']))
                return $this->CatPageArray[$cat]['_pages-'][$page]['_target-'];
            else
                return false;
        }
        if(isset($this->CatPageArray[$cat]['_target-']))
            return $this->CatPageArray[$cat]['_target-'];
        return false;
    }

    # erzeugt einen Link text
    function get_HrefText($cat,$page) {
        global $specialchars;
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_name-'])) {
                return $specialchars->rebuildSpecialChars($this->CatPageArray[$cat]['_pages-'][$page]['_name-'], true, true);
            } else
                return NULL;
        }
        if(isset($this->CatPageArray[$cat]['_name-'])) {
            return $specialchars->rebuildSpecialChars($this->CatPageArray[$cat]['_name-'], true, true);
        }
        return NULL;
    }

    function is_Activ($cat,$page) {
        $req_cat = $this->get_AsKeyName(CAT_REQUEST);
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            $req_page = $this->get_AsKeyName(PAGE_REQUEST);
            if($cat == $req_cat and $page == $req_page and $this->get_Type($cat,$page) != EXT_LINK)
                return true;
            return false;
        }
        if($cat == $req_cat and $this->get_Type($cat,false) != EXT_LINK)
            return true;
        return false;
    }

    # ändert denn Namen der von get_HrefText() ausgegeben wird
    function change_Name($cat,$page,$newname) {
        # prüfen ob $newname ein text ist
        if($this->is_ParaString($newname)) {
            $cat = $this->get_AsKeyName($cat);
            if($page !== false) {
                $page = $this->get_AsKeyName($page);
                if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_name-'])) {
                    $this->CatPageArray[$cat]['_pages-'][$page]['_name-'] = $newname;
                    $this->OrgCatPageArray[$cat]['_pages-'][$page]['_name-'] = $newname;
                    return true;
                } else {
                    return false;
                }
            }
            if(isset($this->CatPageArray[$cat]['_name-'])) {
                $this->CatPageArray[$cat]['_name-'] = $newname;
                $this->OrgCatPageArray[$cat]['_name-'] = $newname;
                return true;
            }
            return false;
        }
        return false;
    }

    # stellt den Original Namen wieder her
    function unchange_Name($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        if($page !== false) {
            $page = $this->get_AsKeyName($page);
            if(isset($this->OrgCatPageArray[$cat]['_pages-'][$page]['_orgname-'])) {
                # fals delete_Page() benutz wurde
                if(isset($this->CatPageArray[$cat]['_pages-'][$page]['_orgname-']))
                    $this->CatPageArray[$cat]['_pages-'][$page]['_name-'] = $this->CatPageArray[$cat]['_pages-'][$page]['_orgname-'];
                $this->OrgCatPageArray[$cat]['_pages-'][$page]['_name-'] = $this->OrgCatPageArray[$cat]['_pages-'][$page]['_orgname-'];
                return true;
            } else
                return false;
        }
        if(isset($this->OrgCatPageArray[$cat]['_orgname-'])) {
            # fals delete_Cat() benutz wurde
            if(isset($this->CatPageArray[$cat]['_orgname-']))
                $this->CatPageArray[$cat]['_name-'] = $this->CatPageArray[$cat]['_orgname-'];
            $this->OrgCatPageArray[$cat]['_name-'] = $this->OrgCatPageArray[$cat]['_orgname-'];
            return true;
        }
        return false;
    }

    function delete_Cat($cat) {
        $cat = $this->get_AsKeyName($cat);
        if(isset($this->CatPageArray[$cat])) {
            unset($this->CatPageArray[$cat]);
            return true;
        }
        return false;
    }

    function delete_Page($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        $page = $this->get_AsKeyName($page);
        if(isset($this->CatPageArray[$cat]['_pages-'][$page])) {
            unset($this->CatPageArray[$cat]['_pages-'][$page]);
            return true;
        }
        return false;
    }

    function undelete_Cat($cat,$includepage = true) {
        $cat = $this->get_AsKeyName($cat);
        $tmp_array = array();
        $undelete = false;
        foreach($this->OrgCatPageArray as $cattmp => $inhalt) {
            if(isset($this->CatPageArray[$cattmp])) {
                $tmp_array[$cattmp] = $this->CatPageArray[$cattmp];
            } elseif($cattmp == $cat) {
                $tmp_array[$cat] = $this->OrgCatPageArray[$cat];
                if(!$includepage and isset($tmp_array[$cat]['_pages-']))
                    unset($tmp_array[$cat]['_pages-']);
                $undelete = true;
            }
        }
        if($undelete)
            $this->CatPageArray = $tmp_array;
        return $undelete;
    }

    function undelete_Page($cat,$page) {
        $cat = $this->get_AsKeyName($cat);
        $page = $this->get_AsKeyName($page);
        $tmp_array = array();
        $undelete = false;
        foreach($this->OrgCatPageArray[$cat]['_pages-'] as $pagetmp => $inhalt) {
            if(isset($this->CatPageArray[$cat]['_pages-'][$pagetmp])) {
                $tmp_array[$pagetmp] = $this->CatPageArray[$cat]['_pages-'][$pagetmp];
            } elseif($pagetmp == $page) {
                $tmp_array[$pagetmp] = $this->OrgCatPageArray[$cat]['_pages-'][$page];
                $undelete = true;
            }
        }
        if($undelete)
            $this->CatPageArray[$cat]['_pages-'] = $tmp_array;
        return $undelete;
    }

    function get_PageContent($cat,$page,$for_syntax = false) {
        $cat = $this->get_AsKeyName($cat);
        $page = $this->get_AsKeyName($page);
        if($this->CatPageArray[$cat]['_protect-']) {
            return false;
        }
        if($this->CatPageArray[$cat]['_pages-'][$page]['_protect-']) {
            return false;
        }

        $cat = $this->get_FileSystemName($cat,false);
        $page = $this->get_FileSystemName($cat,$page);
        if($this->get_Type($cat,$page) != EXT_LINK) {
            if(file_exists(CONTENT_DIR_REL.$cat.'/'.$page)) {
                $page_content = file_get_contents(CONTENT_DIR_REL.$cat.'/'.$page);
                if($for_syntax) {
                    global $syntax;
                    $page_content = $syntax->preparePageContent($page_content);
                }
                return $page_content;
           }
        }
        return false;
    }

###############################################################################
# Ab hier solten die functionen nur von der function CatPage() verwendet werden
###############################################################################

    function make_DirPageArray($dir) {
        if(defined("isCatPage"))
            die("make_DirPageArray() darf nur von der class CatPage verwendet werden");
        $page_a = array();
        $page_sort = array();
        $currentdir = getDirAsArray($dir,"file");
        foreach($currentdir as $file) {
            if(substr($file, -4) == EXT_LINK) {
                $target = "-_blank-";
                if(strpos($file,"-_self-") > 1)
                    $target = "-_self-";
                $tmp = explode($target,$file);
                $key = substr($tmp[0],3);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = EXT_LINK;
                $page_a[$key]["_link-"] = str_replace($this->link_search,$this->link_replace,substr($tmp[1],0,strlen($tmp[1])-4));
                $page_a[$key]["_target-"] = str_replace("-","",$target);
            } else {
                $key = substr($file,3,strlen($file)-7);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = substr($file,-4);
                $page_a[$key]["_time-"] = filemtime($dir."/".$file);
                $page_a[$key]["_protect-"] = false;
            }
        }
        return $page_a;
    }

    function make_DirCatPageArray($dir) {
        if(defined("isCatPage"))
            die("make_DirCatPageArray() darf nur von der class CatPage verwendet werden");
        $cat_a = array();
        $cat_sort = array();
        $currentdir = getDirAsArray($dir,"dir");
        foreach($currentdir as $file) {
            if(substr($file, -4) == EXT_LINK) {
                $target = "-_blank-";
                if(strpos($file,"-_self-") > 1)
                    $target = "-_self-";
                $tmp = explode($target,$file);
                $key = substr($tmp[0],3);
                $cat_a[$key]["_name-"] = $key;
                $cat_a[$key]["_orgname-"] = $cat_a[$key]["_name-"];
                $cat_a[$key]["_pos-"] = substr($file,0,2);
                $cat_a[$key]["_type-"] = EXT_LINK;
                $cat_a[$key]["_link-"] = str_replace($this->link_search,$this->link_replace,substr($tmp[1],0,strlen($tmp[1])-4));
                $cat_a[$key]["_target-"] = str_replace("-","",$target);
            } else {
                $key = substr($file,3);
                $cat_a[$key]['_pages-'] = $this->make_DirPageArray($dir."/".$file);
                $cat_a[$key]["_name-"] = $key;
                $cat_a[$key]["_orgname-"] = $cat_a[$key]["_name-"];
                $cat_a[$key]["_pos-"] = substr($file,0,2);
                $cat_a[$key]["_type-"] = "cat";
                $cat_a[$key]["_files-"] = getDirAsArray($dir."/".$file."/".CONTENT_FILES_DIR_NAME,"file");
                $cat_a[$key]["_time-"] = filemtime($dir."/".$file);
                $cat_a[$key]["_protect-"] = false;
            }
        }
        return $cat_a;
    }
}
?>