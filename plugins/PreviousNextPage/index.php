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

class PreviousNextPage extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird. Der String-Parameter $value ist Pflicht, kann aber leer 
    * sein.
    * 
    ***************************************************************/
    function getContent($value) {
        global $PAGE_REQUEST;

        $showhiddenpagesincmsvariables = false;
        if($this->settings->get("showhiddenpagesincmsvariables") == "true")
            $showhiddenpagesincmsvariables = true;

        if(!function_exists('getNeighbourPagesPlugin')) {
            function getNeighbourPagesPlugin($page,$showhiddenpagesincmsvariables) {
                global $CONTENT_DIR_REL;
                global $CAT_REQUEST;
                global $CMS_CONF;
                global $EXT_LINK;

                // leer initialisieren
                $neighbourPages = array("", "");
                // aktuelle Kategorie einlesen
                $pagesarray = getDirContentAsArray($CONTENT_DIR_REL.$CAT_REQUEST, true, $showhiddenpagesincmsvariables);
                // Schleife ueber alle Seiten
                for ($i = 0; $i < count($pagesarray); $i++) {
                    if(substr($pagesarray[$i], -(strlen($EXT_LINK))) == $EXT_LINK)
                        continue;
                    if ($page == substr($pagesarray[$i], 0, strlen($pagesarray[$i]) - 4)) {
                        // vorige Seite (nur setzen, wenn aktuelle nicht die erste ist)
                        if ($i > 0) {
                            $neighbourPages[0] = $pagesarray[$i-1];
                        }
                        // naechste Seite (nur setzen, wenn aktuelle nicht die letzte ist)
                        if($i < count($pagesarray)-1) {
                            $neighbourPages[1] = $pagesarray[$i+1];
                        }
                        // Schleife kann abgebrochen werden
                        break;
                    }
                }
                return $neighbourPages;
            }
        }


        $neighbourPages = getNeighbourPagesPlugin($PAGE_REQUEST,$showhiddenpagesincmsvariables);

        // "unbehandelter" Name der vorigen Inhaltsseite ("00_Der%20M%FCller")
        if($value == "PreviousPage") {
            return substr($neighbourPages[0], 0, strlen($neighbourPages[0]) - 4);
        // Dateiname der vorigen Inhaltsseite ("00_Der%20M%FCller.txt")
        } elseif($value == "PreviousPageFile") {
            return $neighbourPages[0];
        // "sauberer" Name der vorigen Inhaltsseite ("Der Mueller")
        } elseif($value == "PreviousPageName") {
            return pageToName($neighbourPages[0], true);
        // "unbehandelter" Name der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle")
        } elseif($value == "NextPage") {
            return substr($neighbourPages[1], 0, strlen($neighbourPages[1]) - 4);
        // Dateiname der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle.txt")
        } elseif($value == "NextPageFile") {
            return $neighbourPages[1];
        // "sauberer" Name der naechsten Inhaltsseite ("Muellers Muehle")
        } elseif($value == "NextPageName") {
            return pageToName($neighbourPages[1], true);
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
        $config['deDE']['showhiddenpagesincmsvariables']  = array(
            "type" => "checkbox",
            "description" => "Versteckte Inhaltsseiten in den Platzhaltern mit einbeziehen"
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
            "<b>PreviousNextPage 0.1</b>",
            // Plugin-Version
            "1.12",
            // Kurzbeschreibung
            'Erzeugt für die Platzhalter:<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|PreviousPage}</SPAN> "unbehandelter" Name der vorigen Inhaltsseite ("00_Der%20M%FCller")<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|PreviousPageFile}</SPAN> Dateiname der vorigen Inhaltsseite ("00_Der%20M%FCller.txt")<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|PreviousPageName}</SPAN> "sauberer" Name der vorigen Inhaltsseite ("Der Mueller")<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|NextPage}</SPAN> "unbehandelter" Name der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle")<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|NextPageFile}</SPAN> Dateiname der naechsten Inhaltsseite ("20_M%FCllers%20M%FChle.txt")<br>'
            .'<SPAN style="font-weight:bold;">{PreviousNextPage|NextPageName}</SPAN> "sauberer" Name der naechsten Inhaltsseite ("Muellers Muehle")',
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            array('{PreviousNextPage|PreviousPage}' => '',
                '{PreviousNextPage|PreviousPageFile}' => '',
                '{PreviousNextPage|PreviousPageName}' => '',
                '{PreviousNextPage|NextPage}' => '',
                '{PreviousNextPage|NextPageFile}' => '',
                '{PreviousNextPage|NextPageName}' => '')
            );

        if(isset($info[$language])) {
            return $info[$language];
        } else {
            return $info['deDE'];
        }
    } // function getInfo

} // class SITEMAP

?>