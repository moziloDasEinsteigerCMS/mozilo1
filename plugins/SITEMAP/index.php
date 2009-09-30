<?php

/***************************************************************
* Sitemap-Plugin für moziloCMS.
* 
* Mit der Variablen {SITEMAP} kann an beliebiger Stelle des CMS
* (Template oder Inhaltsseiten) die aktuelle Sitemap eingefügt 
* werden.
***************************************************************/



/***************************************************************
* 
* Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt wird.
* 
***************************************************************/
function get_SITEMAP_content($value) {
    $sitemapinfo = getSitemap();
    return $sitemapinfo[0];
}



/***************************************************************
* 
* Gibt den HTML-Code für die Plugin-Settings im Admin zurück.
* 
***************************************************************/
function get_SITEMAP_config() {
    return "Keine Einstellungen möglich bzw. nötig. No settings available or required.";
}



/***************************************************************
* 
* Gibt die Plugin-Infos als Array zurück.
* 
***************************************************************/
function get_SITEMAP_info() {
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
}



?>