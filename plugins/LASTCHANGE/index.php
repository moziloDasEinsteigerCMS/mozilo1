<?php

/***************************************************************
*
* Plugin für moziloCMS, das die letzte Änderungen zurückgibt
* 
***************************************************************/

class LASTCHANGE extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/
    function getContent($value) {
        global $language;

        if(!function_exists('getLastChangedContentPageAndDateLAST')) {
            // ------------------------------------------------------------------------------
            // Rueckgabe eines Arrays, bestehend aus:
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
    * 
    ***************************************************************/
    function getConfig() {
        global $ADMIN_CONF;
        $language = $ADMIN_CONF->get("language");

        $config['deDE'] = array();
        $config['deDE']['messagetext']  = array(
            "type" => "text",
            "description" => 'Eigener Text für "Letzte Änderung:"',
            "maxlength" => "100",
            "size" => "30"
            );
        $config['deDE']['date']  = array(
            "type" => "text",
            "description" => "Eigenes Datumsformat",
            "maxlength" => "100",
            "size" => "30"
            );

        // Nicht vergessen: Das gesamte Array zurückgeben
        if(isset($config[$language])) {
            return $config[$language];
        } else {
            return $config['deDE'];
        }

    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück. 
    * 
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        global $ADMIN_CONF;
        $adminlanguage = $ADMIN_CONF->get("language");
               
        $info['deDE'] = array(
            // Plugin-Name
            "<b>LastChange</b> 1.0",
            // CMS-Version
            "1.12",
            // Kurzbeschreibung
            'Zeigt die letzte Änderung an.<br />
            <br />
            <span style="font-weight:bold;">Nutzung:</span><br />
            {LASTCHANGE} gibt etwas aus wie: "Letzte Änderung: Willkommen (22.02.2010, 09:07:20)"<br />
            {LASTCHANGE|text} gibt etwas aus wie: "Letzte Änderung"<br />
            {LASTCHANGE|page} gibt etwas aus wie: "Willkommen"<br />
            {LASTCHANGE|pagelink} gibt etwas aus wie: "Willkommen" (mit Link auf die Inhaltsseite)<br />
            {LASTCHANGE|date} gibt etwas aus wie: "(22.02.2010, 09:07:20)"<br />
            <br />
            <span style="font-weight:bold;">Konfiguration:</span><br />
            Das Plugin bezieht den Text "Letzte Änderung" und das Datumsformat aus der CMS-Sprachdatei; man kann beides aber auch selbst angeben. Dabei orientiert sich das Datumsformat an der PHP-Funktion date().',
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            array(
                '{LASTCHANGE}' => 'Letzte Änderung mit Link und Datum',
                '{LASTCHANGE|text}' => 'Text "Letzte Änderung:"',
                '{LASTCHANGE|page}' => 'Name der zuletzt geänderten Inhaltsseite',
                '{LASTCHANGE|pagelink}' => 'Link auf die zuletzt geänderte Inhaltsseite',
                '{LASTCHANGE|date}' => 'Datum der letzten Änderung')
            );

        if(isset($info[$adminlanguage])) {
            return $info[$adminlanguage];
        } else {
            return $info['deDE'];
        }
    } // function getInfo

} // class LASTCHANGE

?>