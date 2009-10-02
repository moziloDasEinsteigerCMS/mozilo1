<?php

/***************************************************************
* Sitemap-Plugin für moziloCMS.
* 
* Mit der Variablen {SITEMAP} kann an beliebiger Stelle des CMS
* (Template oder Inhaltsseiten) die aktuelle Sitemap eingefügt 
* werden.
***************************************************************/

class SITEMAP extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt wird.
    * 
    ***************************************************************/
    function getContent($value) {
        $sitemapinfo = getSitemap();
        return $sitemapinfo[0];
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt den HTML-Code für die Plugin-Settings im Admin zurück.
    * 
    ***************************************************************/
    function getConfig() {
        return "Keine Einstellungen möglich bzw. nötig. No settings available or required.";
    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück.
    * 
    ***************************************************************/
    function getInfo() {
        return array(
            // Plugin-Name
            "Sitemap",
            // Plugin-Version
            "1.0",
            // Kurzbeschreibung
            "Sitemap zum Einfügen an beliebiger Stelle",
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de"
            );
    } // function getInfo

} // class SITEMAP

?>