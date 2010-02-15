<?php

/***************************************************************
*
* Sitemap-Plugin für moziloCMS.
* 
* Mit der Variablen {SITEMAP} kann an beliebiger Stelle des CMS
* (Template oder Inhaltsseiten) die aktuelle Sitemap eingefügt 
* werden.
* 
***************************************************************/

class LASTCHANGE extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird. Der String-Parameter $value ist Pflicht, kann aber leer 
    * sein.
    * 
    ***************************************************************/
    function getContent($value) {
        global $language;

        if(!function_exists('getLastChangedContentPageAndDateLAST')) {
            // ------------------------------------------------------------------------------
            // Rueckgabe eines Array, bestehend aus:
            // - Name der zuletzt geaenderten Inhaltsseite
            // - kompletter Link auf diese Inhaltsseite  
            // - formatiertes Datum der letzten Aenderung
            // ------------------------------------------------------------------------------
            function getLastChangedContentPageAndDateLAST($dateformat){
                global $CONTENT_DIR_REL;
                global $language;
                global $specialchars;
                global $CMS_CONF;
                global $URL_BASE;

                $latestchanged = array("cat" => "catname", "file" => "filename", "time" => 0);
                $currentdir = opendir($CONTENT_DIR_REL);
                while ($file = readdir($currentdir)) {
                    if (isValidDirOrFile($file)) {
                        $latestofdir = getLastChangeOfCatLAST($CONTENT_DIR_REL.$file);
                        if ($latestofdir['time'] > $latestchanged['time']) {
                            $latestchanged['cat'] = $file;
                            $latestchanged['file'] = $latestofdir['file'];
                            $latestchanged['time'] = $latestofdir['time'];
                        }
                    }
                }
                closedir($currentdir);
                
                $lastchangedpage = $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true);
                # Mod Rewrite
                $url = "index.php?cat=".substr($latestchanged['cat'],3)."&amp;page=".substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7);
                if($CMS_CONF->get("modrewrite") == "true") {
                    $url = $URL_BASE.substr($latestchanged['cat'],3)."/".substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7).".html";
                }
                $linktolastchangedpage = "<a href=\"".$url."\"".getTitleAttribute($language->getLanguageValue2("tooltip_link_page_2", $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true, true), $specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true, true)))." id=\"lastchangelink\">".$lastchangedpage."</a>";
                $lastchangedate = @strftime($dateformat, date($latestchanged['time']));

                return array($lastchangedpage, $linktolastchangedpage,$lastchangedate);
            }
            // ------------------------------------------------------------------------------
            // Einlesen eines Kategorie-Verzeichnisses, Rueckgabe der zuletzt geaenderten Datei
            // ------------------------------------------------------------------------------
            function getLastChangeOfCatLAST($dir){
                global $EXT_HIDDEN;
                global $EXT_PAGE;
                global $CMS_CONF;

                $showhiddenpages = ($CMS_CONF->get("showhiddenpagesinlastchanged") == "true");
                
                $latestchanged = array("file" => "filename", "time" => 0);
                $currentdir = opendir($dir);
                while ($file = readdir($currentdir)) {
                    if (is_file($dir."/".$file)) {
                        // normale Inhaltsseiten
                        if ( 
                            (substr($file, strlen($file)-4, 4) == $EXT_PAGE)
                            // oder, wenn konfiguriert, auch versteckte
                            || ($showhiddenpages && substr($file, strlen($file)-4, 4) == $EXT_HIDDEN)
                            )
                            {
                            if (filemtime($dir."/".$file) > $latestchanged['time']) {
                                $latestchanged['file'] = $file;
                                $latestchanged['time'] = filemtime($dir."/".$file);
                            }
                        }
                }
                }
                closedir($currentdir);
                return $latestchanged;
            }


        }

        $messagetext = $language->getLanguageValue0("message_lastchange_0");
        if($this->settings->get("messagetext"))
             $messagetext = $this->settings->get("messagetext");
        $dateformat = $language->getLanguageValue0("_dateformat_0");
        if($this->settings->get("date"))
             $dateformat = $this->settings->get("date");
        if($value == "text") {
            return $messagetext;
        } elseif($value == "page") {
            $lastchangeinfo = getLastChangedContentPageAndDateLAST($dateformat);
            return $lastchangeinfo[0];
        } elseif($value == "pagelink") {
            $lastchangeinfo = getLastChangedContentPageAndDateLAST($dateformat);
            return $lastchangeinfo[1];
        } elseif($value == "date") {
            $lastchangeinfo = getLastChangedContentPageAndDateLAST($dateformat);
            return $lastchangeinfo[2];
        } else {
            $lastchangeinfo = getLastChangedContentPageAndDateLAST($dateformat);
            return $messagetext." ".$lastchangeinfo[1]." (".$lastchangeinfo[2].")";
        }

        return "";
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * Ist keine Konfiguration nötig, gibt die Funktion false zurück.
    * 
    ***************************************************************/
    function getConfig() {
        global $ADMIN_CONF;
        $language = $ADMIN_CONF->get("language");

        $config['deDE'] = array();
        // Nicht vergessen: Das gesamte Array zurückgeben
        $config['deDE']['messagetext']  = array(
            "type" => "text",
            "description" => "Text für {LASTCHANGE|text}",
            "maxlength" => "100",
            "size" => "30"
            );
        $config['deDE']['date']  = array(
            "type" => "text",
            "description" => "Datums Format",
            "maxlength" => "100",
            "size" => "30"
            );

        if(isset($config[$language])) {
            return $config[$language];
        } else {
            return $config['deDE'];
        }

    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück - in dieser 
    * Reihenfolge:
    *   - Name des Plugins
    *   - Version des Plugins
    *   - Kurzbeschreibung
    *   - Name des Autors
    *   - Download-URL
    * 
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        $language = $ADMIN_CONF->get("language");

        $info['deDE'] = array(
            // Plugin-Name
            "<b>LASTCHANGE 0.1</b>",
            // Plugin-Version
            "1.12",
            // Kurzbeschreibung
            'Erzeugt für die Platzhalter:<br>'
            .'<SPAN style="font-weight:bold;">{LASTCHANGE}</SPAN> = {LASTCHANGE|text} {LASTCHANGE|pagelink} {LASTCHANGE|date}<br>'
            .'<SPAN style="font-weight:bold;">{LASTCHANGE|text}</SPAN> Default "Letzte Änderung:"<br>'
            .'<SPAN style="font-weight:bold;">{LASTCHANGE|page}</SPAN> Name der Inhaltseite<br>'
            .'<SPAN style="font-weight:bold;">{LASTCHANGE|pagelink}</SPAN> Link zur Inhaltseite<br>'
            .'<SPAN style="font-weight:bold;">{LASTCHANGE|date}</SPAN> Datum der Letzten Änderung<br><br>'
            .'Das Datums Format kann Angepast werden Default "%d.%m.%Y, %H:%M:%S"<br>'
            .'%d = Tag 2stelig<br>'
            .'%m = Monat 2stelig<br>'
            .'%Y = Jahr 4stelig<br>'
            .'%H = Stunde<br>'
            .'%M = Minute<br>'
            .'%S = Sekunden<br>'
            .'Weitere Infos zum Datumsformat siehe PHP date<br>',
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            array('{LASTCHANGE|text}' => 'Lastchange Text Default "Letzte Änderung:"',
                '{LASTCHANGE|page}' => 'Name der zuletzt geaenderten Inhaltsseite',
                '{LASTCHANGE|pagelink}' => 'kompletter Link auf diese Inhaltsseite',
                '{LASTCHANGE|date}' => 'formatiertes Datum der letzten Aenderung')
            );

        if(isset($info[$language])) {
            return $info[$language];
        } else {
            return $info['deDE'];
        }
    } // function getInfo

} // class SITEMAP

?>