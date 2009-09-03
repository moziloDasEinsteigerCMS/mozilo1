<?php

/*
######
INHALT
######
		
		Projekt "Flatfile-basiertes CMS für Einsteiger"
		Syntaxersetzung
		Klasse ITF04-1
		Industrieschule Chemnitz

		Ronny Monser
		Arvid Zimmermann
		Oliver Lorenz
		-> mozilo

######
*/

class Syntax {
	
	var $CMS_CONF;
	var $LINK_REGEX;

	
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
	function Syntax(){
		$this->CMS_CONF	= new Properties("main.conf");
		// Regulärer Audruck zur Überprüfung von Links
		// Überprüfung auf Validität >> protokoll :// (username:password@) [(sub.)server.tld|ip-adresse] (:port) (subdirs|files)
					// protokoll 						(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh):\/\/
					// username:password@		(\w)+\:(\w)+\@
					// (sub.)server.tld 		((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}
					// ip-adresse (ipv4)		([\d]{1,3}\.){3}[\d]{1,3}
					// port									\:[\d]{1,5}
					// subdirs|files				(\w)+
		$this->LINK_REGEX = "/^(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh)\:\/\/((\w)+\:(\w)+\@)?[((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}|([\d]{1,3}\.){3}[\d]{1,3}](\:[\d]{1,5})?((\w)+)?$/";
	}
	

// ------------------------------------------------------------------------------
// Umsetzung der übergebenen CMS-Syntax in HTML, Rückgabe als String
// ------------------------------------------------------------------------------
	function convertContent($content, $firstrecursion){
		global $CONTENT_DIR_ABS;
		global $CONTENT_DIR_REL;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CAT_REQUEST;
		global $CONTENT_EXTENSION;
		global $specialchars;
		
		if ($firstrecursion) {
			// Inhaltsformatierungen
	    $content = htmlentities($content);
			$content = preg_replace("/&amp;#036;/Umsi", "&#036;", $content);
			$content = preg_replace("/&amp;#092;/Umsi", "&#092;", $content);
			$content = preg_replace("/\^(.)/Umsie", "'&#'.ord('\\1').';'", $content);
		}
		
		// Nach Texten in eckigen Klammern suchen
//		preg_match_all("/\[([\w|=]+)\|([^\[\]]+)\]/U", $content, $matches);
		preg_match_all("/\[([^\[\]]+)\|([^\[\]]+)\]/U", $content, $matches);
		$i = 0;
		// Für jeden Treffer...
		foreach ($matches[0] as $match) {
			// ...Auswertung und Verarbeitung der Informationen
			$attribute = $matches[1][$i];
			$value = $matches[2][$i];
			
			// externer Link
			if ($attribute == "link") {
				if (preg_match($this->LINK_REGEX, $value)) {
					$shortenendlink = $value;
					switch ($this->CMS_CONF->get("shortenlinks")) {
						// mit "http://www." beginnende Links ohne das "http://www." anzeigen
						case 2: { 
							if (substr($value, 0, 11) == "http://www.")
								$shortenendlink = substr($value, 11, strlen($value)-11);
							// zusätzlich: mit "http://" beginnende Links ohne das "http://" anzeigen
							elseif (substr($value, 0, 7) == "http://")
								$shortenendlink = substr($value, 7, strlen($value)-7);
							break;
						}
						// mit "http://" beginnende Links ohne das "http://" anzeigen
						case 1: { 
							if (substr($value, 0, 7) == "http://")
								$shortenendlink = substr($value, 7, strlen($value)-7);
							break;
						}
						default: { 
						}
					}
					$content = str_replace ($match, "<a href=\"$value\" title=\"Externe Adresse &quot;$value&quot; aufrufen\" target=\"_blank\">$shortenendlink</a>", $content);
				}
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"Fehlerhafte Link-Adresse &quot;$value&quot;\">$value</em>", $content);
			}

			// externer Link mit eigenem Text
			elseif (substr($attribute,0,5) == "link=") {
				// Überprüfung auf korrekten Link
				if (preg_match($this->LINK_REGEX, $value))
					$content = str_replace ($match, "<a href=\"$value\" title=\"Externe Adresse &quot;$value&quot; aufrufen\" target=\"_blank\">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"Fehlerhafte Link-Adresse &quot;$value&quot;\">".substr($attribute, 5, strlen($attribute)-5)."</em>", $content);
			}

			// Mail-Link
			elseif ($attribute == "mail"){
				// Überprüfung auf Validität
				if (preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/", $value))
					$content = str_replace ($match, "<a href=\"".obfuscateAdress("mailto:$value", 3)."\" title=\"Mail an &quot;".obfuscateAdress("$value", 3)."&quot; schreiben\">".obfuscateAdress("$value", 3)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"Fehlerhafte E-Mail-Adresse &quot;$value&quot;\">$value</em>", $content);
			}

			// Kategorie-Link (überprüfen, ob Kategorie existiert)
			elseif ($attribute == "kategorie"){
				$requestedcat = nameToCategory($specialchars->deleteSpecialChars($value));
				if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat")))
					$content = str_replace ($match, "<a href=\"index.php?cat=$requestedcat\" title=\"Zur Kategorie &quot;$value&quot; wechseln\">$value</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"Kategorie &quot;$value&quot; nicht vorhanden\">$value</em>", $content);
			}

			// Link auf Inhaltsseite in aktueller oder anderer Kategorie (überprüfen, ob Inhaltsseite existiert)
			elseif ($attribute == "seite"){
				$valuearray = explode(":", $value);
				// Inhaltsseite in aktueller Kategorie
				if (count($valuearray) == 1) {
					$requestedpage = nameToPage($specialchars->deleteSpecialChars($value), $CAT_REQUEST);
					if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$CAT_REQUEST/$requestedpage")))
						$content = str_replace ($match, "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($CONTENT_EXTENSION))."\" title=\"Inhaltsseite &quot;$value&quot; anzeigen\">$value</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Inhaltsseite &quot;$value&quot; nicht vorhanden\">$value</em>", $content);
				}
				// Inhaltsseite in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->deleteSpecialChars($valuearray[0]));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						$requestedpage = nameToPage($specialchars->deleteSpecialChars($valuearray[1]), $requestedcat);
						if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
							$content = str_replace ($match, "<a href=\"index.php?cat=$requestedcat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($CONTENT_EXTENSION))."\" title=\"Inhaltsseite &quot;".$valuearray[1]."&quot; der Kategorie &quot;".$valuearray[0]."&quot; anzeigen\">".$valuearray[1]."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"Inhaltsseite &quot;".$valuearray[1]."&quot; in der Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);	
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
				}
			}

			// Datei aus dem Dateiverzeichnis (überprüfen, ob Datei existiert)
			elseif ($attribute == "datei"){
				$valuearray = explode(":", $value);
				// Datei in aktueller Kategorie
				if (count($valuearray) == 1) {
					if (file_exists("./$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_FILES_DIR/$value"))
						$content = str_replace ($match, "<a href=\"$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $value)."\" title=\"Datei &quot;$value&quot; herunterladen\" target=\"_blank\">$value</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Datei &quot;$value&quot; nicht vorhanden\">$value</em>", $content);
				}
				// Datei in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->deleteSpecialChars($valuearray[0]));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/$valuearray[1]"))
							$content = str_replace ($match, "<a href=\"$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $valuearray[1])."\" title=\"Datei &quot;".$valuearray[1]."&quot; der Kategorie &quot;".$valuearray[0]."&quot; herunterladen\" target=\"_blank\">".$valuearray[1]."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"Datei &quot;".$valuearray[1]."&quot; in der Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
				}
			}

			// Galerie mit Bildern aus dem Galerieverzeichnis
			elseif ($attribute == "galerie"){
				$valuearray = explode(":", $value);
				// Galerie in aktueller Kategorie
				if (count($valuearray) == 1) {
					$handle = opendir("./$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_GALLERY_DIR");
					$j=0;
					while ($file = readdir($handle)) {
						if (is_file("./$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_GALLERY_DIR/".$file) && ($file <> "texte.conf")) {
		    			$j++;
		    		}
					}
					$content = str_replace ($match, "<a href=\"gallery.php?cat=$CAT_REQUEST\" title=\"Galerie &quot;".substr($specialchars->rebuildSpecialChars($CAT_REQUEST, true), 3, strlen($CAT_REQUEST) - 3)."&quot; ($j Bilder) ansehen\" target=\"_blank\">$value</a>", $content);
				}
				// Galerie in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->deleteSpecialChars($valuearray[0]));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						$handle = opendir("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_GALLERY_DIR");
						$j=0;
						while ($file = readdir($handle)) {
							if (is_file("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_GALLERY_DIR/".$file) && ($file <> "texte.conf")) {
			    			$j++;
			    		}
						}
						$content = str_replace ($match, "<a href=\"gallery.php?cat=$requestedcat\" title=\"Galerie &quot;".substr($specialchars->rebuildSpecialChars($requestedcat, true), 3, strlen($requestedcat) - 3)."&quot; ($j Bilder) ansehen\" target=\"_blank\">".$valuearray[1]."</a>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
				}
			}

			// Bild aus dem Dateiverzeichnis oder externes Bild
			elseif (($attribute == "bild") || ($attribute == "bildlinks") ||($attribute == "bildrechts")) {
				$cssclass = "";
				if ($attribute == "bildlinks")
					$cssclass = " class=\"leftcontentimage\"";
				elseif ($attribute == "bildrechts")
					$cssclass = " class=\"rightcontentimage\"";
				// Bei Links: NICHT nach ":" aufsplitten!
				if (preg_match($this->LINK_REGEX, $value))
					$valuearray = $value;
				// Ansonsten: Nach ":" aufsplitten
				else
					$valuearray = explode(":", $value);
				// Bild in aktueller Kategorie
				if (count($valuearray) == 1) {
					if (file_exists("./$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_FILES_DIR/$value"))
						$content = str_replace ($match, "<img src=\"$CONTENT_DIR_REL/$CAT_REQUEST/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $value)."\" alt=\"Bild &quot;$value&quot;\"$cssclass />", $content);
					elseif (preg_match($this->LINK_REGEX, $value))
						$content = str_replace ($match, "<img src=\"$value\" alt=\"Bild &quot;$value&quot;\"$cssclass />", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Unbekannte Datei oder fehlerhafte Adresse: &quot;$value&quot;\">$value</em>", $content);
				}
				// Bild in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->deleteSpecialChars($valuearray[0]));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".$valuearray[1]))
							$content = str_replace ($match, "<img src=\"$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $valuearray[1])."\" alt=\"Bild &quot;".$valuearray[1]."&quot;\"$cssclass />", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"Bild &quot;".$valuearray[1]."&quot; in der Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"Kategorie &quot;".$valuearray[0]."&quot; nicht vorhanden\">".$valuearray[1]."</em>", $content);
				}
			}

			// linksbündiger Text
			if ($attribute == "links"){
				$content = str_replace ("$match", "<div style=\"text-align:left;\">".$value."</div>", $content);
			}

			// zentrierter Text
			elseif ($attribute == "zentriert"){
				$content = str_replace ("$match", "<div style=\"text-align:center;\">".$value."</div>", $content);
			}

			// Text im Blocksatz
			elseif ($attribute == "block"){
				$content = str_replace ("$match", "<div style=\"text-align:justified;\">".$value."</div>", $content);
			}

			// rechtsbündiger Text
			elseif ($attribute == "rechts"){
				$content = str_replace ("$match", "<div style=\"text-align:right;\">".$value."</div>", $content);
			}

			// Text fett
			elseif ($attribute == "fett"){
				$content = str_replace ($match, "<em class=\"bold\">$value</em>", $content);
			}

			// Text kursiv
			elseif ($attribute == "kursiv"){
				$content = str_replace ($match, "<em class=\"italic\">$value</em>", $content);
			}

			// Text fettkursiv 
			// (veraltet seit Version 1.7 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
			elseif ($attribute == "fettkursiv"){
				$content = str_replace ($match, "<em class=\"bolditalic\">$value</em>", $content);
			}

			// Text unterstrichen
			elseif ($attribute == "unter"){
				$content = str_replace ($match, "<em class=\"underlined\">$value</em>", $content);
			}

			// Text durchgestrichen
			elseif ($attribute == "durch"){
				$content = str_replace ($match, "<em class=\"crossed\">$value</em>", $content);
			}

			// Überschrift groß
			elseif ($attribute == "ueber1"){
				$content = str_replace ("$match", "<h1>$value</h1>", $content);
			}

			// Überschrift mittel
			elseif ($attribute == "ueber2"){
				$content = str_replace ("$match", "<h2>$value</h2>", $content);
			}

			// Überschrift normal
			elseif ($attribute == "ueber3"){
				$content = str_replace ("$match", "<h3>$value</h3>", $content);
			}

			// Liste, einfache Einrückung
			elseif ($attribute == "liste1"){
				$content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
			}

			// Liste, doppelte Einrückung
			elseif ($attribute == "liste2"){
				$content = str_replace ("$match", "<ul><ul><li>$value</li></ul></ul>", $content);
			}

			// Liste, dreifache Einrückung
			elseif ($attribute == "liste3"){
				$content = str_replace ("$match", "<ul><ul><ul><li>$value</li></ul></ul></ul>", $content);
			}
			
			// HTML
			elseif ($attribute == "html"){
				$nobrvalue = preg_replace('/(\r\n|\r|\n)?/m', '', $value);
				$content = str_replace ("$match", html_entity_decode($nobrvalue), $content);
			}

			// Farbige Elemente
			elseif (substr($attribute,0,6) == "farbe=") {
				// Überprüfung auf korrekten Hexadezimalwert
				if (preg_match("/^([a-f]|\d){6}$/i", substr($attribute, 6, strlen($attribute)-6))) 
					$content = str_replace ("$match", "<em style=\"color:#".substr($attribute, 6, strlen($attribute)-6).";\">".$value."</em>", $content);
				else
					$content = str_replace ("$match", "<em class=\"deadlink\" title=\"Fehlerhafter Farbwert: &quot;".substr($attribute, 6, strlen($attribute)-6)."&quot;\">$value</em>", $content);
			}

			// Attribute, die nicht zugeordnet werden können
			else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"Falsche Syntax: Unbekanntes Attribut &quot;$attribute&quot;\">$value</em>", $content);

			$i++;
		}

		// Immer ersetzen: Horizontale Linen
		$content = preg_replace('/\[----\](\r\n|\r|\n)?/m', '<hr />', $content);
		// Zeilenwechsel setzen
		$content = preg_replace('/\n/', '<br />', $content);
		// Zeilenwechsel vor und nach Blockelementen wieder herausnehmen
		//$content = preg_replace('/<br \/><hr \/>/', "<hr />", $content);
		$content = preg_replace('/<\/ul>(\r\n|\r|\n)<br \/>/', "</ul>", $content);
		$content = preg_replace('/<\/ol>(\r\n|\r|\n)<br \/>/', "</ol>", $content);
		$content = preg_replace('/(<\/h[123]>)(\r\n|\r|\n)<br \/>/', "$1", $content);

		// Rekursion, wenn noch Fundstellen
		if ($i > 0)
			$content = $this->convertContent($content, false);
			
		// Konvertierten Seiteninhalt zurückgeben
    return $content;
	}


}

?>