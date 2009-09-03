<?php

/* 
* 
* $Revision: 26 $
* $LastChangedDate: 2008-04-11 21:51:19 +0200 (Fr, 11 Apr 2008) $
* $Author: arvid $
*
*/


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
		www.mozilo.de

######
*/

class Syntax {
	
	var $CMS_CONF;
	var $LANG;
	var $LINK_REGEX;
	var $MAIL_REGEX;
	var $USER_SYNTAX;

	
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
	function Syntax(){
		$this->CMS_CONF	= new Properties("conf/main.conf");
		$this->LANG	= new Language();
		// Regulärer Audruck zur Überprüfung von Links
		// Überprüfung auf Validität >> protokoll :// (username:password@) [(sub.)server.tld|ip-adresse] (:port) (subdirs|files)
					// protokoll 						(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh):\/\/
					// username:password@		(\w)+\:(\w)+\@
					// (sub.)server.tld 		((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}
					// ip-adresse (ipv4)		([\d]{1,3}\.){3}[\d]{1,3}
					// port									\:[\d]{1,5}
					// subdirs|files				(\w)+
		$this->LINK_REGEX = "/^(https?|t?ftps?|gopher|telnets?|mms|imaps?|irc|pop3s?|rdp|smb|smtps?|sql|ssh|svn)\:\/\/((\w)+\:(\w)+\@)?[((\w)+\.)?(\w)+\.[a-zA-Z]{2,4}|([\d]{1,3}\.){3}[\d]{1,3}](\:[\d]{1,5})?((\w)+)?$/";
		$this->MAIL_REGEX = "/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/";
		$this->USER_SYNTAX	= new Properties("conf/syntax.conf");
	}
	

// ------------------------------------------------------------------------------
// Umsetzung der übergebenen CMS-Syntax in HTML, Rückgabe als String
// ------------------------------------------------------------------------------
	function convertContent($content, $cat, $firstrecursion){
		global $CONTENT_DIR_ABS;
		global $CONTENT_DIR_REL;
		global $CONTENT_FILES_DIR;
		global $GALLERIES_DIR;
		global $PAGE_REQUEST;
		global $EXT_PAGE;
		global $specialchars;
		
		if ($firstrecursion) {
			// Inhaltsformatierungen
	    $content = htmlentities($content);
			$content = preg_replace("/&amp;#036;/Umsi", "&#036;", $content);
			$content = preg_replace("/&amp;#092;/Umsi", "&#092;", $content);
			$content = preg_replace("/\^(.)/Umsie", "'&#'.ord('\\1').';'", $content);
		}
		
		// Nach Texten in eckigen Klammern suchen
		preg_match_all("/\[([^\[\]]+)\|([^\[\]]*)\]/Um", $content, $matches);
		$i = 0;
		// Für jeden Treffer...
		foreach ($matches[0] as $match) {
			// ...Auswertung und Verarbeitung der Informationen
			$attribute = $matches[1][$i];
			$value = $matches[2][$i];

			// Ausgabe zu Testzwecken
			// echo "$i: $attribute - $value <br>";
			
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
					$content = str_replace ($match, "<a class=\"link\" href=\"$value\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_extern_1", $value)."\" target=\"_blank\">$shortenendlink</a>", $content);
				}
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_extern_error_1", $value)."\">$value</em>", $content);
			}

			// externer Link mit eigenem Text
			elseif (substr($attribute,0,5) == "link=") {
				// Überprüfung auf korrekten Link
				if (preg_match($this->LINK_REGEX, $value))
					$content = str_replace ($match, "<a class=\"link\" href=\"$value\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_extern_1", $value)."\" target=\"_blank\">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_extern_error_1", $value)."\">".substr($attribute, 5, strlen($attribute)-5)."</em>", $content);
			}

			// Mail-Link mit eigenem Text
			elseif (substr($attribute,0,5) == "mail=") {
				// Überprüfung auf korrekten Link
				if (preg_match($this->MAIL_REGEX, $value))
					$content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))."\">".substr($attribute, 5, strlen($attribute)-5)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_mail_error_1", $value)."\">".substr($attribute, 5, strlen($attribute)-5)."</em>", $content);
			}
			elseif ($attribute == "mail"){
				// Überprüfung auf Validität
				if (preg_match("/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/", $value))
					$content = str_replace ($match, "<a class=\"mail\" href=\"".obfuscateAdress("mailto:$value", 3)."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_mail_1", obfuscateAdress("$value", 3))."\">".obfuscateAdress("$value", 3)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_mail_error_1", $value)."\">$value</em>", $content);
			}

			// Kategorie-Link (überprüfen, ob Kategorie existiert)
			elseif ($attribute == "kategorie"){
				$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($value)));
				if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat")))
					$content = str_replace ($match, "<a class=\"category\" href=\"index.php?cat=$requestedcat\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_1", $value)."\">$value</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $value)."\">$value</em>", $content);
			}

			// Kategorie-Link mit eigenem Text
			elseif (substr($attribute,0,10) == "kategorie=") {
				$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($value)));
				if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat")))
					$content = str_replace ($match, "<a class=\"category\" href=\"index.php?cat=$requestedcat\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_1", $value)."\">".substr($attribute, 10, strlen($attribute)-10)."</a>", $content);
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $value)."\">$value</em>", $content);
			}

			// Link auf Inhaltsseite in aktueller oder anderer Kategorie (überprüfen, ob Inhaltsseite existiert)
			elseif ($attribute == "seite") {
				$valuearray = explode(":", $value);
				// Inhaltsseite in aktueller Kategorie
				if (count($valuearray) == 1) {
					$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value)), $cat);
					if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage")))
						$content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$cat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_page_1", $value)."\">$value</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)."\">$value</em>", $content);
				}
				// Inhaltsseite in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1])), $requestedcat);
						if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
							$content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$requestedcat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_page_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);	
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
				}
			}

			// Link auf Inhaltsseite in aktueller oder anderer Kategorie mit beliebigem Text
			elseif (substr($attribute,0,6) == "seite=") {
				$valuearray = explode(":", $value);
				// Inhaltsseite in aktueller Kategorie
				if (count($valuearray) == 1) {
					$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value)), $cat);
					if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage")))
						$content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$cat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_page_1", $value)."\">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)."\">$value</em>", $content);
				}
				// Inhaltsseite in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1])), $requestedcat);
						if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
							$content = str_replace ($match, "<a class=\"page\" href=\"index.php?cat=$requestedcat&amp;page=".substr($requestedpage, 0, strlen($requestedpage) - strlen($EXT_PAGE))."\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_page_2", $valuearray[1], $valuearray[0])."\">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);	
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
				}
			}

			// Datei aus dem Dateiverzeichnis (überprüfen, ob Datei existiert)
			elseif ($attribute == "datei"){
				$value = html_entity_decode($value);
				$valuearray = explode(":", $value);
				// Datei in aktueller Kategorie
				if (count($valuearray) == 1) {
					if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value"))
						$content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$cat&amp;file=".preg_replace("'\s'", "%20", $value)."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_file_1", $value)."\" target=\"_blank\">$value</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_file_error_1", $value)."\">$value</em>", $content);
				}
				// Datei in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/$valuearray[1]"))
							$content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$requestedcat&amp;file=".preg_replace("'\s'", "%20", $valuearray[1])."\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_file_2", $valuearray[1], $valuearray[0])."\" target=\"_blank\">".$valuearray[1]."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_file_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
				}
			}

			// Datei aus dem Dateiverzeichnis mit beliebigem Text
			elseif (substr($attribute,0,6) == "datei=") {
				$valuearray = explode(":", $value);
				// Datei in aktueller Kategorie
				if (count($valuearray) == 1) {
					if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value"))
						$content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$cat&amp;file=".preg_replace("'\s'", "%20", $value)."\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_file_1", $value)."\" target=\"_blank\">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_file_error_1", $value)."\">$value</em>", $content);
				}
				// Datei in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/$valuearray[1]"))
							$content = str_replace ($match, "<a class=\"file\" href=\"download.php?cat=$requestedcat&amp;file=".preg_replace("'\s'", "%20", $valuearray[1])."\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_file_2", $valuearray[1], $valuearray[0])."\" target=\"_blank\">".substr($attribute, 6, strlen($attribute)-6)."</a>", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_file_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
				}
			}

			// Galerie
			elseif ($attribute == "galerie") {
				$cleanedvalue = $specialchars->replaceSpecialChars(html_entity_decode($value));
				if (file_exists("./$GALLERIES_DIR/$cleanedvalue")) {
					$handle = opendir("./$GALLERIES_DIR/$cleanedvalue");
					$j=0;
					while ($file = readdir($handle)) {
						if (is_file("./$GALLERIES_DIR/$cleanedvalue/".$file) && ($file <> "texte.conf")) {
		    			$j++;
		    		}
					}
					$content = str_replace ($match, "<a class=\"gallery\" href=\"gallery.php?gal=$cleanedvalue\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_gallery_2", $value, $j)."\" target=\"_blank\">$value</a>", $content);
				}
				// Galerie nicht vorhanden
				else {
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_gallery_error_1", $value)."\">$value</em>", $content);
				}
			}

			// Galerielink mit eigenem Text
			elseif (substr($attribute,0,8) == "galerie=") {
				$cleanedvalue = $specialchars->replaceSpecialChars(html_entity_decode($value));
				if (file_exists("./$GALLERIES_DIR/$cleanedvalue")) {
					$handle = opendir("./$GALLERIES_DIR/$cleanedvalue");
					$j=0;
					while ($file = readdir($handle)) {
						if (is_file("./$GALLERIES_DIR/$cleanedvalue/".$file) && ($file <> "texte.conf")) {
		    			$j++;
		    		}
					}
					$content = str_replace ($match, "<a class=\"gallery\" href=\"gallery.php?gal=$cleanedvalue\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_gallery_2", $value, $j)."\" target=\"_blank\">".substr($attribute, 8, strlen($attribute)-8)."</a>", $content);
				}
				// Galerie nicht vorhanden
				else {
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_gallery_error_1", $value)."\">".substr($attribute, 8, strlen($attribute)-8)."</em>", $content);
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
					if (file_exists("./$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/$value"))
						$content = str_replace ($match, "<img src=\"$CONTENT_DIR_REL/$cat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $value)."\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $value)."\"$cssclass />", $content);
					elseif (preg_match($this->LINK_REGEX, $value))
						$content = str_replace ($match, "<img src=\"$value\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $value)."\"$cssclass />", $content);
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_image_error_1", $value)."\">$value</em>", $content);
				}
				// Bild in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						if (file_exists("./$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".$valuearray[1]))
							$content = str_replace ($match, "<img src=\"$CONTENT_DIR_REL/$requestedcat/$CONTENT_FILES_DIR/".preg_replace("'\s'", "%20", $valuearray[1])."\" alt=\"".$this->LANG->getLanguageValue1("alttext_image_1", $valuearray[1])."\"$cssclass />", $content);
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_image_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
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

			// Listenpunkt
			elseif ($attribute == "liste"){
				$content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
			}

			// numerierter Listenpunkt
			elseif ($attribute == "numliste"){
				$content = str_replace ("$match", "<ol><li>$value</li></ol>", $content);
			}

			// Liste, einfache Einrückung
			// (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
			elseif ($attribute == "liste1"){
				$content = str_replace ("$match", "<ul><li>$value</li></ul>", $content);
			}

			// Liste, doppelte Einrückung
			// (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
			elseif ($attribute == "liste2"){
				$content = str_replace ("$match", "<ul><ul><li>$value</li></ul></ul>", $content);
			}

			// Liste, dreifache Einrückung
			// (veraltet seit Version 1.10 - nur aus Gründen der Abwärtskompatibilität noch mitgeführt)
			elseif ($attribute == "liste3"){
				$content = str_replace ("$match", "<ul><ul><ul><li>$value</li></ul></ul></ul>", $content);
			}
			
			// HTML
			elseif ($attribute == "html"){
				$nobrvalue = preg_replace('/(\r\n|\r|\n)?/m', '', $value);
				$content = str_replace ("$match", html_entity_decode($nobrvalue), $content);
			}

/* 
Sie sollten diese Auskommentierung nur entfernen, wenn Sie wirklich sicher wissen, was 
sie tun. mozilo weist mit Nachdruck darauf hin, daß die verwendete PHP-Funktion "eval()" 
ein erhebliches Sicherheitsrisiko darstellen kann und deswegen nicht leichtfertig 
verwendet werden sollte!

			// Ausführung von PHP-Code
			elseif ($attribute == "php") {
				// Formatierungen rückgängig machen, um den reinen PHP-Code zu erhalten!
				$value = preg_replace("/&#(\d*);/Umsie", "''.chr('\\1').''", $value);
				$value = preg_replace("/&#092;/Umsi", "&amp;#092;", $value);
				$value = preg_replace("/&#036;/Umsi", "&amp;#036;", $value);
				$value = html_entity_decode($value);
				$content = str_replace ("$match", eval($value), $content);
			}
*/

			// Tabellen
			elseif ($attribute == "tabelle") {
				// Tabelleninhalt aufbauen
				$tablecontent = "";
				$j = 0;
				// Tabellenzeilen
				// preg_match_all("/&lt;([^&gt;]*)&gt;/Um", $value, $tablelines);
				// preg_match_all("/(&lt;|&lt;&lt;)([^&gt;]*)(&gt;|&gt;&gt;)/Um", $value, $tablelines);
				preg_match_all("/(&lt;|&lt;&lt;)(.*)(&gt;|&gt;&gt;)/Umsie", $value, $tablelines);
				foreach ($tablelines[0] as $tablematch) {
					// Kopfzeilen
					if (preg_match("/&lt;&lt;([^&gt;]*)/Umsi", $tablematch)) {
						$linecontent = preg_replace('/\|/', '</th><th class="contenttable">', $tablelines[2][$j]);
						$linecontent = preg_replace('/&#38;/', '&', $linecontent);
						$linecontent = preg_replace('/&lt;(.*)/', "$1", $linecontent);
						$tablecontent .= "<tr><th class=\"contenttable\">$linecontent</th></tr>";
					}
					// normale Tabellenzeilen
					else {
						// CSS-Klasse immer im Wechsel
						$css = "contenttable1";
						if ($j%2 == 0)
							$css = "contenttable2";
						// Pipes durch TD-Wechsel ersetzen
						$linecontent = preg_replace('/\|/', '</td><td class="'.$css.'">', $tablelines[2][$j]);
						$linecontent = preg_replace('/&#38;/', '&', $linecontent);
						$tablecontent .= "<tr><td class=\"$css\">$linecontent</td></tr>";
					}
					$j++;
				}
				$content = str_replace ("$match", "<table class=\"contenttable\">$tablecontent</table>", $content);
			}

			// Includes
			elseif ($attribute == "include") {
				$valuearray = explode(":", $value);
				// Inhaltsseite in aktueller Kategorie
				if (count($valuearray) == 1) {
					$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($value)), $cat);
					if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$cat/$requestedpage"))) {
						// Seite darf sich nicht selbst includen!
						if (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST) {
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue0("tooltip_include_recursion_error_0")."\">$value</em>", $content);
						}
						// Includierte Inhaltsseite parsen
						else {
							$file = "./$CONTENT_DIR_REL/$cat/$requestedpage";
							$handle = fopen($file, "r");
							$pagecontent = "";
							if (filesize($file) > 0) {
								// "include"-Tags in includierten Seiten sind nicht erlaubt, um Rekursionen zu vermeiden
								$pagecontent = preg_replace("/\[include\|([^\[\]]*)\]/Um", "[html|<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue0("tooltip_include_reinclude_error_0")."\">$value</em>]", fread($handle, filesize($file)));
								$pagecontent = $this->convertContent($pagecontent, $cat, true);
							}
							fclose($handle);
							$content = str_replace ($match, $pagecontent, $content);
						}
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_page_error_1", $value)."\">$value</em>", $content);
				}
				// Inhaltsseite in anderer Kategorie
				else {
					$requestedcat = nameToCategory($specialchars->replaceSpecialChars(html_entity_decode($valuearray[0])));
					if ((!$requestedcat=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat"))) {
						$requestedpage = nameToPage($specialchars->replaceSpecialChars(html_entity_decode($valuearray[1])), $requestedcat);
						if ((!$requestedpage=="") && (file_exists("./$CONTENT_DIR_REL/$requestedcat/$requestedpage")))
							// Seite darf sich nicht selbst includen!
							if (($requestedcat == $cat) && (substr($requestedpage, 0, strlen($requestedpage)-strlen($EXT_PAGE)) == $PAGE_REQUEST)) {
								$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue0("tooltip_include_recursion_error_0")."\">$value</em>", $content);
							}
							// Includierte Inhaltsseite parsen
							else {
								$file = "./$CONTENT_DIR_REL/$requestedcat/$requestedpage";
								$handle = fopen($file, "r");
								$pagecontent = "";
								if (filesize($file) > 0)
									$pagecontent = $this->convertContent(fread($handle, filesize($file)), $requestedcat, true);
								fclose($handle);
								$content = str_replace ($match, $pagecontent, $content);
							}
						else
							$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue2("tooltip_link_page_error_2", $valuearray[1], $valuearray[0])."\">".$valuearray[1]."</em>", $content);	
					}
					else
						$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_link_category_error_1", $valuearray[0])."\">".$valuearray[1]."</em>", $content);
				}
			}
			
			// Farbige Elemente
			elseif (substr($attribute,0,6) == "farbe=") {
				// Überprüfung auf korrekten Hexadezimalwert
				if (preg_match("/^([a-f]|\d){6}$/i", substr($attribute, 6, strlen($attribute)-6))) 
					$content = str_replace ("$match", "<em style=\"color:#".substr($attribute, 6, strlen($attribute)-6).";\">".$value."</em>", $content);
				else
					$content = str_replace ("$match", "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_color_error_1", substr($attribute, 6, strlen($attribute)-6))."\">$value</em>", $content);
			}

			// Attribute, die nicht zugeordnet werden können
			else {
				// Benutzerdefinierte Attribute überprüfen
				if ($this->USER_SYNTAX->keyExists($attribute)) {
					$replacetext = str_replace("{VALUE}", $value, $this->USER_SYNTAX->get($attribute));
					$content = str_replace ("$match",$replacetext , $content);
				}
				else
					$content = str_replace ($match, "<em class=\"deadlink\" title=\"".$this->LANG->getLanguageValue1("tooltip_attribute_error_1", $attribute)."\">$value</em>", $content);
			}

			// Immer ersetzen: Horizontale Linen
			$content = preg_replace('/\[----\]/', '<hr />', $content);

			$i++;
		}
		
		// Rekursion, wenn noch Fundstellen
		if ($i > 0)
			$content = $this->convertContent($content, $cat, false);
		else {
			// Zeilenwechsel setzen
			$content = preg_replace('/\n/', '<br />', $content);
			// Zeilenwechsel nach Blockelementen entfernen
			// Tag-Beginn																							<
			// optional: Slash bei schließenden Tags									(\/)?
			// Blockelemente 																					(address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)
			// optional: sonstige Attribute bis zum Slash							(\s[^\/]*?)?
			// optional: Slash bei implizit schließenden Tags 				(\s\/)?
			// Tag-Ende																								>
			// Danach Zeilenwechsel und <br>													[\r\n|\r|\n]<br \/>
			$content = preg_replace('/<(\/)?(address|blockquote|div|dl|fieldset|form|h[123456]|hr|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu)(\s[^\/]*?)?(\s\/)?>[\r\n|\r|\n]<br \/>/', "<$1$2$3$4>", $content);
			// Leerzeichen für Zeilen ohne Inhalt erzwingen
			$content = preg_replace('/>(\r\n|\r|\n)<br/', ">$1&nbsp;<br", $content);
			// direkt aufeinanderfolgende numerierte Listen zusammenführen
			$content = preg_replace('/<\/ol><ol>/', '', $content);
		}

		// Platzhalter ersetzen
		$content = replacePlaceholders($content);
	
		// Konvertierten Seiteninhalt zurückgeben
    return $content;
	}
	

}

?>