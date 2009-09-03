<?php

/* 
* 
* $Revision: 190 $
* $LastChangedDate: 2009-04-23 21:42:09 +0200 (Do, 23 Apr 2009) $
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
	
	// Sendet eine Mail an die konfigurierte Admin-Adresse (Absender ist der CMS-Titel)
	function sendMailToAdmin($subject, $content) {
		//$from = "\"".$this->CMSCONF->get("websitetitle")."\"";
		$from = $this->ADMINCONF->get("adminmail");
		$this->sendMailToAdminWithFrom($subject, $content, $from);
	}
	
	// Sendet eine Mail an die konfigurierte Admin-Adresse (mit definierter Absender-Adresse) 
	function sendMailToAdminWithFrom($subject, $content, $from) {
		$to = $this->ADMINCONF->get("adminmail");
		@mail(html_entity_decode($to), html_entity_decode($subject), html_entity_decode($content), $this->getHeader(html_entity_decode($to), html_entity_decode($from)));
	}
	

	// Baut den Mail-Header zusammen
	function getHeader($from, $replyto) {
		return "From: ".$from."\r\n"
			."MIME-Version: 1.0\r\n"
			."Content-type: text/plain; charset=iso-8859-1\r\n"
			."Reply-To: ".$replyto."\r\n"
			."X-Priority: 0\r\n"
			."X-MimeOLE: \r\n"
			."X-mailer: moziloCMS ".$this->CMSCONF->get("cmsversion");
	}
	
	// Prüft ob die Mail-Funktion verfügbar ist
	function isMailAvailable()
	{
		return function_exists("mail");
	}
	
}

?>