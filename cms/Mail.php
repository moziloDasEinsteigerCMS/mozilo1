<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

// Sendet eine Mail an die konfigurierte Admin-Adresse (Absender ist der CMS-Titel)
function sendMailToAdmin($subject, $content) {
    global $ADMIN_CONF;
    $from = $ADMIN_CONF->get("adminmail");
    sendMail($subject, $content, $from, $from, $from);
}

// Sendet eine Mail an die konfigurierte Kontakt-Adresse oder eine Kopie an die Usermail-Adresse
function sendMail($subject, $content, $from, $to, $replyto) {
    global $specialchars;
    if(class_exists('idna_convert')) {
        global $Punycode;
        $from = $Punycode->encode($from);
        $to = $Punycode->encode($to);
        $replyto = $Punycode->encode($replyto);
    }
    @mail(
           $specialchars->getHtmlEntityDecode($to),
           "=?".CHARSET."?B?".base64_encode($specialchars->getHtmlEntityDecode($subject))."?=",
           $specialchars->getHtmlEntityDecode($content),
           getHeader ($specialchars->getHtmlEntityDecode($from), $specialchars->getHtmlEntityDecode($replyto))
         );
}

// Baut den Mail-Header zusammen
function getHeader($from, $replyto) {
    global $VERSION_CONF;
    if (empty($replyto))
        $replyto = $from;
    return "From: ".$from."\r\n"
        ."MIME-Version: 1.0\r\n"
        ."Content-type: text/plain; charset=".CHARSET."\r\n"
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

?>