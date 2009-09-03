<?php

/*
######
INHALT
######
		
		Projekt "Flatfile-basiertes CMS für Einsteiger"
		Umlautersetzung
		Mai 2006
		Klasse ITF04-1
		Industrieschule Chemnitz

		Ronny Monser
		Arvid Zimmermann
		Oliver Lorenz
		www.mozilo.de

######
*/

class SpecialChars {
	
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
	function SpecialChars(){
	}

// ------------------------------------------------------------------------------    
// Inhaltsseiten/Kategorien für Speicherung umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
	function deleteSpecialChars($text) {
		$text = preg_replace("/&auml;/", "-auml-", $text);
		$text = preg_replace("/ä/", "-auml-", $text);
		$text = preg_replace("/&ouml;/", "-ouml-", $text);
		$text = preg_replace("/ö/", "-ouml-", $text);
		$text = preg_replace("/&uuml;/", "-uuml-", $text);
		$text = preg_replace("/ü/", "-uuml-", $text);
		$text = preg_replace("/&Auml;/", "-Auml-", $text);
		$text = preg_replace("/Ä/", "-Auml-", $text);
		$text = preg_replace("/&Ouml;/", "-Ouml-", $text);
		$text = preg_replace("/Ö/", "-Ouml-", $text);
		$text = preg_replace("/&Uuml;/", "-Uuml-", $text);
		$text = preg_replace("/Ü/", "-Uuml-", $text);
		$text = preg_replace("/&szlig;/", "-szlig-", $text);
		$text = preg_replace("/ß/", "-szlig-", $text);
		$text = preg_replace("/\s/", "-nbsp-", $text);
		$text = preg_replace("/ /", "-nbsp-", $text);
		$text = preg_replace("/&nbsp;/", "-nbsp-", $text);
		$text = preg_replace("/\240/", "-nbsp-", $text); // für den IE: Zeichen "ð", warum auch immer -.-
		$text = preg_replace("/\?/", "-ques-", $text);
		$text = preg_replace("/&/", "-amp-", $text);
		$text = preg_replace("/&amp;/", "-amp-", $text);
		$text = preg_replace("/&euro;/", "-euro-", $text);
		$text = preg_replace("/</", "-lt-", $text);
		$text = preg_replace("/&lt;/", "-lt-", $text);
		$text = preg_replace("/>/", "-gt-", $text);
		$text = preg_replace("/&gt;/", "-gt-", $text);
		$text = preg_replace("/€/", "-euro-", $text);
		$text = preg_replace("/@/", "-at-", $text);
		return $text;
	}


// ------------------------------------------------------------------------------    
// Umlaute in Inhaltsseiten/Kategorien für Anzeige 
// ------------------------------------------------------------------------------
	function rebuildSpecialChars($text, $rebuildnbsp) {
		$text = preg_replace("/-auml-/", "&auml;", $text);
		$text = preg_replace("/-ouml-/", "&ouml;", $text);
		$text = preg_replace("/-uuml-/", "&uuml;", $text);
		$text = preg_replace("/-Auml-/", "&Auml;", $text);
		$text = preg_replace("/-Ouml-/", "&Ouml;", $text);
		$text = preg_replace("/-Uuml-/", "&Uuml;", $text);
		$text = preg_replace("/-szlig-/", "&szlig;", $text);
		$text = preg_replace("/-ques-/", "?", $text);
		$text = preg_replace("/-amp-/", "&amp;", $text);
		$text = preg_replace("/-euro-/", "&euro;", $text);
		$text = preg_replace("/-lt-/", "&lt;", $text);
		$text = preg_replace("/-gt-/", "&gt;", $text);
		$text = preg_replace("/-at-/", "@", $text);
		if ($rebuildnbsp)
			$text = preg_replace("/-nbsp-/", "&nbsp;", $text);
		else
			$text = preg_replace("/-nbsp-/", " ", $text);
		return $text;
	}


}
?>