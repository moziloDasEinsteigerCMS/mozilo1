<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/


/* Mailklasse für moziloCMS */

class Mail {
    
    // Konstruktor
    function Mail() {
    }
    
    // Sendet eine Mail an die konfigurierte Admin-Adresse (Absender ist der CMS-Titel)
    function sendMailToAdmin($subject, $content) {
        #global $CMS_CONF;
        global $ADMIN_CONF;
        //$from = "\"".$this->CMSCONF->get("websitetitle")."\"";
        $from = $ADMIN_CONF->get("adminmail");
        $this->sendMailToAdminWithFrom($subject, $content, $from);
    }
    
    // Sendet eine Mail an die konfigurierte Admin-Adresse (mit definierter Absender-Adresse) 
    function sendMailToAdminWithFrom($subject, $content, $from) {
        global $CHARSET;
        #global $CMS_CONF;
        global $ADMIN_CONF;
        $to = $ADMIN_CONF->get("adminmail");
        @mail(html_entity_decode($to,ENT_COMPAT,$CHARSET), html_entity_decode($subject), html_entity_decode($content), $this->getHeader(html_entity_decode($to), html_entity_decode($from)));
    }
    

    // Baut den Mail-Header zusammen
    function getHeader($from, $replyto) {
        global $CHARSET;
        global $VERSION_CONF;
        return "From: ".$from."\r\n"
            ."MIME-Version: 1.0\r\n"
            ."Content-type: text/plain; charset=$CHARSET\r\n"
            ."Reply-To: ".$replyto."\r\n"
            ."X-Priority: 0\r\n"
            ."X-MimeOLE: \r\n"
            ."X-mailer: moziloCMS ".$VERSION_CONF->get("cmsversion");
    }
    
    // Prüft ob die Mail-Funktion verfügbar ist
    function isMailAvailable()
    {
        return function_exists("mail");
    }
    
}

?>