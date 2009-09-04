<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/



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
// Erlaubte Sonderzeichen als RegEx zurückgeben
// ------------------------------------------------------------------------------
	function getSpecialCharsRegex() {
		$regex = "/^[a-zA-Z0-9_\-\s\?\!\@\.€".addslashes(html_entity_decode(implode("", get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)),ENT_COMPAT,'ISO-8859-1'))."]+$/";
		$regex = preg_replace("/&#39;/", "\'", $regex);
		return $regex;
	}
	
// ------------------------------------------------------------------------------    
// Erlaubte Sonderzeichen userlesbar als String zurückgeben
// ------------------------------------------------------------------------------
	function getSpecialCharsString($sep, $charsperline) {
		$specialcharsstring = "";
		$specialcharshtml = "";
		for ($i=65; $i<=90;$i++)
			$specialcharsstring .= chr($i);
		for ($i=97; $i<=122;$i++)
			$specialcharsstring .= chr($i);
		for ($i=48; $i<=57;$i++)
			$specialcharsstring .= chr($i);
		$specialcharsstring .= html_entity_decode("_- ?!€@.".stripslashes(preg_replace("/&#39;/", "\'", implode(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)))),ENT_COMPAT,'ISO-8859-1');
		for ($i=0; $i<=strlen($specialcharsstring); $i+=$charsperline) {
			$specialcharshtml .= htmlentities(substr($specialcharsstring, $i, $charsperline),ENT_COMPAT,'ISO-8859-1')."<br />";
		}
		return $specialcharshtml;
	}
	
// ------------------------------------------------------------------------------    
// Inhaltsseiten/Kategorien für Speicherung umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
	function replaceSpecialChars($text) {
		$text = htmlentities(stripslashes($text),ENT_COMPAT,'ISO-8859-1');
		// Leerzeichen
		$text = preg_replace("/(\s| |\240|&nbsp;)/", "-nbsp~", $text);
		$text = preg_replace("/\"/", "-quot~", $text);
		// €, @, ?
		$text = preg_replace("/€/", "-euro~", $text);
		$text = preg_replace("/@/", "-at~", $text);
		$text = preg_replace("/\?/", "-ques~", $text);
		// Alle HTML-Entities in mozilo-Entities umwandeln
		$text = preg_replace("/&(.*);/U", "-$1~", $text);
		return $text;
	}


// ------------------------------------------------------------------------------    
// Umlaute in Inhaltsseiten/Kategorien für Anzeige 
// ------------------------------------------------------------------------------
	function rebuildSpecialChars($text, $rebuildnbsp) {
		// Leerzeichen
		if ($rebuildnbsp)
			$text = preg_replace("/-nbsp~/", "&nbsp;", $text);
		else
			$text = preg_replace("/-nbsp~/", " ", $text);
		// @, ?
		$text = preg_replace("/-at~/", "@", $text);
		$text = preg_replace("/-ques~/", "?", $text);
		// Alle mozilo-Entities in HTML-Entities umwandeln!
		$text = preg_replace("/-([^-~]+)~/U", "&$1;", $text);
		// & escapen 
		//$text = preg_replace("/&+(?!(.+);)/U", "&amp;", $text);
		return $text;
	}


// ------------------------------------------------------------------------------    
// Für Datei-Uploads erlaubte Sonderzeichen als RegEx zurückgeben
// ------------------------------------------------------------------------------
	function getFileCharsRegex() {
		$regex = "/^[a-zA-Z0-9_\-\.]+$/";
		return $regex;
	}
	
// ------------------------------------------------------------------------------    
// Für Datei-Uploads erlaubte Sonderzeichen userlesbar als String zurückgeben
// ------------------------------------------------------------------------------
	function getFileCharsString($sep, $charsperline) {
		$filecharsstring = "";
		$filecharshtml = "";
		for ($i=65; $i<=90;$i++)
			$filecharsstring .= chr($i);
		for ($i=97; $i<=122;$i++)
			$filecharsstring .= chr($i);
		for ($i=48; $i<=57;$i++)
			$filecharsstring .= chr($i);
		$filecharsstring .= "_-.";
		for ($i=0; $i<=strlen($filecharsstring); $i+=$charsperline) {
			$filecharshtml .= htmlentities(substr($filecharsstring, $i, $charsperline),ENT_COMPAT,'ISO-8859-1')."<br />";
		}
		return $filecharshtml;
	}

	
// ------------------------------------------------------------------------------    
// String für SEO-Links umlaut- und sonderzeichenbereinigen 
// ------------------------------------------------------------------------------
	function replaceSeoSpecialChars($text) {
		$text = preg_replace("/ä/", "ae", $text);
		$text = preg_replace("/ö/", "oe", $text);
		$text = preg_replace("/ü/", "ue", $text);
		$text = preg_replace("/Ä/", "Ae", $text);
		$text = preg_replace("/Ö/", "Oe", $text);
		$text = preg_replace("/Ü/", "Ue", $text);
		$text = preg_replace("/ß/", "ss", $text);
		$text = preg_replace("/\s/", "+", $text);
		$text = preg_replace("/ /", "+", $text);
		$text = preg_replace("/\240/", "+", $text); // für den IE: Zeichen "ð", warum auch immer -.-
		$text = preg_replace("/\?/", "", $text);
		$text = preg_replace("/&/", "+", $text);
		$text = preg_replace("/€/", "euro", $text);
		$text = preg_replace("/</", "+", $text);
		$text = preg_replace("/>/", "+", $text);
		$text = preg_replace("/@/", "at", $text);
		return $text;
	}
}
?>