<?php

/* 
* 
* $Revision: 115 $
* $LastChangedDate: 2009-01-27 21:14:39 +0100 (Di, 27 Jan 2009) $
* $Author: arvid $
*
*/


/* Mailklasse für moziloCMS */

class Mail {
	
	var $ADMIN_CONF;
	var $CMS_CONF;
	
	// Konstruktor
	function Mail($callFromAdmin) {
		if ($callFromAdmin) {
			require_once("../Properties.php");
			$this->ADMINCONF = new Properties("conf/basic.conf");
			$this->CMSCONF = new Properties("../conf/main.conf");
		}
		else {
			require_once("Properties.php");
			$this->ADMINCONF = new Properties("admin/conf/basic.conf");
			$this->CMSCONF = new Properties("conf/main.conf");
		}
	}
	
	
/*	// Sendet eine Mail
	function sendMail($to, $from, $subject, $content) {
		@mail(html_entity_decode($to), html_entity_decode($subject), html_entity_decode($content), $this->getHeader(html_entity_decode($from)));
	}
*/

	// Sendet eine Mail an die konfigurierte Admin-Adresse
	function sendMailToAdmin($subject, $content) {
		$to = $this->ADMINCONF->get("adminmail");
		$from = "\"".$this->CMSCONF->get("websitetitle")."\"";
		@mail(html_entity_decode($to), html_entity_decode($subject), html_entity_decode($content), $this->getHeader(html_entity_decode($from)));
	}


	// Baut den Mail-Header zusammen
	function getHeader($from) {
		return "From: ".$from."\r\n"
			."MIME-Version: 1.0\r\n"
			."Content-type: text/plain; charset=iso-8859-1\r\n"
			."Reply-To: ".$from."\r\n"
			."X-Priority: 0\r\n"
			."X-MimeOLE: \r\n"
			."X-mailer: moziloCMS";
	}
	
	// Prüft ob die Mail-Funktion verfügbar ist
	function isMailAvailable()
	{
		return function_exists("mail");
	}
	
}

?>