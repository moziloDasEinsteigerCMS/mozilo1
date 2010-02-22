<?php

/***************************************************************
* 
* PayPal-Button-Plugin für moziloCMS
* Gibt einen PayPal-Button aus.
* 
***************************************************************/
class moziloPayPal extends Plugin {


    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/
    function getContent($value) {
        global $URL_BASE;
        global $PLUGIN_DIR_NAME;

        $paypal = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" title="Unterst&uuml;tzen Sie mozilo mit einer Spende!">
                <input type="hidden" name="cmd" value="_s-xclick" />
                <input type="image" src="'.$URL_BASE.$PLUGIN_DIR_NAME.'/moziloPayPal/spendenbanner105x40.png" name="submit" alt="Zahlen Sie mit PayPal - schnell, kostenlos und sicher!" />
                <img alt="Unterst&uuml;tzen Sie mozilo mit einer Spende" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
                <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
                </form>';
        return $paypal;
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * 
    ***************************************************************/
    function getConfig() {
        // keine Konfiguration nötig
        return array();
    } // function getConfig    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück.
    * 
    ***************************************************************/
    function getInfo() {
        return array(
            // Plugin-Name
            "<b>PayPal-Button</b> 1.0",
            // CMS-Version
            "1.12",
            // Kurzbeschreibung
            'Erzeugt den PayPal-Spendenbutton. <br />
            <br />
            <span style="font-weight:bold;">Nutzung:</span><br />
            {moziloPayPal} erzeugt den PayPal-Button.',
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            array('{moziloPayPal}' => 'PayPal-Button')
            );
    } // function getInfo

} // class DEMOPLUGIN

?>