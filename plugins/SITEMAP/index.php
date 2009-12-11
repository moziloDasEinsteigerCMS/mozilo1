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

class SITEMAP extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird. Der String-Parameter $value ist Pflicht, kann aber leer 
    * sein.
    * 
    ***************************************************************/
    function getContent($value) {
        $sitemapinfo = getSitemap();
        return $sitemapinfo[0];
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * Ist keine Konfiguration nötig, gibt die Funktion false zurück.
    * 
    ***************************************************************/
    function getConfig() {
        return array(); // keine Konfiguration nötig
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
        return array(
            // Plugin-Name
            "Sitemap",
            // Plugin-Version
            "1.0",
            // Kurzbeschreibung
            "Standard-Sitemap zum Einfügen an beliebiger Stelle",
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de"
            );
    } // function getInfo

} // class SITEMAP

?>