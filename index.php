<?php

/*
######
INHALT
######
		
		Projekt "Flatfile-basiertes CMS für Einsteiger"
		Mai 2006
		Klasse ITF04-1
		Industrieschule Chemnitz

		Ronny Monser
		Arvid Zimmermann
		Oliver Lorenz
		-> mozilo

		Dieses Dokument stellt ein simples dateibasiertes
		Content Management System dar.
		
		Funktion:
		Siehe /admin/readme.htm

######
*/

	require_once("Properties.php");
	require_once("SpecialChars.php");
	require_once("Syntax.php");
	
	$mainconfig = new Properties("main.conf");
	$specialchars = new SpecialChars();
	$syntax = new Syntax();
	
	// Config-Parameter auslesen
	
	$WEBSITE_TITLE			= $mainconfig->get("websitetitle");
	if ($WEBSITE_TITLE == "")
		$WEBSITE_TITLE = "Titel der Website";
	
	$TEMPLATE_FILE			= $mainconfig->get("templatefile");
	if ($TEMPLATE_FILE == "")
		$TEMPLATE_FILE = "template.html";

	$CSS_FILE						= $mainconfig->get("cssfile");
	if ($CSS_FILE == "")
		$CSS_FILE = "css/style.css";

	$DEFAULT_CATEGORY		= $mainconfig->get("defaultcat");
	if ($DEFAULT_CATEGORY == "")
		$DEFAULT_CATEGORY = "10_Home";

	$DEFAULT_PAGE				= $mainconfig->get("defaultpage");
	if ($DEFAULT_PAGE == "")
		$DEFAULT_PAGE = "10_Home";

	$FAVICON_FILE				= $mainconfig->get("faviconfile");
	if ($FAVICON_FILE == "")
		$FAVICON_FILE = "favicon.ico";

	$USE_CMS_SYNTAX			= true;
	if ($mainconfig->get("usecmssyntax") == "false")
		$USE_CMS_SYNTAX = false;

	$CAT_REQUEST 				= htmlentities(stripslashes($_GET['cat']));
	$PAGE_REQUEST 			= htmlentities(stripslashes($_GET['page']));
	$ACTION_REQUEST 		= htmlentities(stripslashes($_GET['action']));
	
	$CONTENT_DIR_REL		= "inhalt";
	$CONTENT_DIR_ABS 		= getcwd() . "/$CONTENT_DIR_REL";
	$CONTENT_FILES_DIR	= "dateien";
	$CONTENT_GALLERY_DIR= "galerie";
	$CONTENT_EXTENSION	= ".txt";
	if ($ACTION_REQUEST == "draft")
		$CONTENT_EXTENSION	= ".tmp";
	$CONTENT 						= "";
	$HTML								= "";

	// Überprüfen: Ist die Startkategorie vorhanden? Wenn nicht, nimm einfach die allererste als Standardkategorie
	if (!file_exists("$CONTENT_DIR_REL/$DEFAULT_CATEGORY")) {
		$contentdir = opendir($CONTENT_DIR_REL);
		while ($cat = readdir($contentdir)) {
			if (($cat <> ".") && ($cat <> "..")) {
				$DEFAULT_CATEGORY = $cat;
				break;
			}
		}
	}
	
	
	// Zuerst: Übergebene Parameter überprüfen
	checkParameters();
	// Dann: HTML-Template einlesen und mit Inhalt füllen
	readTemplate();
	// Zum Schluß: Ausgabe des fertigen HTML-Dokuments
  echo $HTML;

	
// ------------------------------------------------------------------------------
// Parameter auf Korrektheit prüfen
// ------------------------------------------------------------------------------
	function checkParameters() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CONTENT_EXTENSION;
		global $DEFAULT_CATEGORY;
		global $ACTION_REQUEST;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		// Überprüfung der gegebenen Parameter
		if (
				// Wenn keine Kategorie übergeben wurde...
				($CAT_REQUEST == "") 
				// ...oder eine nicht existente Kategorie...
				|| (!file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST")) 
				// ...oder eine Kategorie ohne Contentseiten...
				|| (getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true) == "")
				// ...oder eine nicht existente Content-Seite...
				|| (($PAGE_REQUEST <> "") && (!file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$CONTENT_EXTENSION")))
			)
			// ...dann verwende die Standardkategorie
			$CAT_REQUEST = $DEFAULT_CATEGORY;
		
		// Kategorie-Verzeichnis einlesen
		$pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
		// Wenn Contentseite nicht explizit angefordert wurde oder nicht vorhanden ist...
		if (($PAGE_REQUEST == "") || (!file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$CONTENT_EXTENSION")))
			//...erste Contentseite der Kategorie setzen
			$PAGE_REQUEST = substr($pagesarray[0], 0, strlen($pagesarray[0]) - strlen($CONTENT_EXTENSION));
		// Wenn ein Action-Parameter übergeben wurde: keine aktiven Kat./Inhaltts. anzeigen
		if (($ACTION_REQUEST == "sitemap") || ($ACTION_REQUEST == "search")) {
			$CAT_REQUEST = "";
			$PAGE_REQUEST = "";
		}
	}
	
	
// ------------------------------------------------------------------------------
// HTML-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
	function readTemplate() {
		global $CSS_FILE;
		global $HTML;
		global $FAVICON_FILE;
		global $TEMPLATE_FILE;
		global $USE_CMS_SYNTAX;
		global $WEBSITE_TITLE;
		global $ACTION_REQUEST;
		global $syntax;
		// Template-Datei auslesen
    if (!$file = @fopen($TEMPLATE_FILE, "r"))
        die("'$TEMPLATE_FILE' fehlt! Bitte kontaktieren Sie den Administrator.");
    $template = fread($file, filesize($TEMPLATE_FILE));
    fclose($file);
    
		// Platzhalter des Templates mit Inhalt füllen
    $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $template);
    $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    $HTML = preg_replace('/{WEBSITE_TITLE}/', $WEBSITE_TITLE, $HTML);
    $pagecontent = "";
    if ($ACTION_REQUEST == "sitemap")
    	$pagecontent = getSiteMap();
    elseif ($ACTION_REQUEST == "search")
    	$pagecontent = getSearchResult();
    elseif ($USE_CMS_SYNTAX)
    	$pagecontent = $syntax->convertContent(getContent(), true);
    else
    	$pagecontent = getContent();

		// Gesuchte Phrasen hervorheben
		if ((isset($_GET['highlight'])) &&  ($_GET['highlight'] <> ""))
			$pagecontent = highlight($pagecontent, htmlentities($_GET['highlight']));
		
		$HTML = preg_replace('/{CONTENT}/', $pagecontent, $HTML);

    $HTML = preg_replace('/{MAINMENU}/', getMainMenu(), $HTML);
    $HTML = preg_replace('/{DETAILMENU}/', getDetailMenu(), $HTML);
    $HTML = preg_replace('/{SEARCH}/', getSearchForm(), $HTML);
    $HTML = preg_replace('/{LASTCHANGE}/', getLastChangedContentPage(), $HTML);
    $HTML = preg_replace('/{SITEMAPLINK}/', "<a href=\"index.php?action=sitemap\" class=\"latestchangedlink\" title=\"Sitemap anzeigen\">Sitemap</a>", $HTML);
    $HTML = preg_replace('/{CMSINFO}/', getCmsInfo(), $HTML);
	}


// ------------------------------------------------------------------------------    
// Zu einem Kategorienamen passendes Kategorieverzeichnis suchen und zurückgeben
// ------------------------------------------------------------------------------
	function nameToCategory($catname) {
		global $CONTENT_DIR_ABS;
		// Content-Verzeichnis einlesen
		$dircontent = getDirContentAsArray("$CONTENT_DIR_ABS", array(), false);
		// alle vorhandenen Kategorien durchgehen...
		foreach ($dircontent as $currentelement) {
			// ...und wenn eine auf den Namen paßt...
			if (substr($currentelement, 3, strlen($currentelement)-3) == $catname){
				// ...den Kategorie zurückgeben
				return $currentelement;
			}
		}
		// Wenn kein Verzeichnis paßt: Leerstring zurückgeben
		return "";
	}
	

// ------------------------------------------------------------------------------    
// Zu einer Inhaltsseite passende Datei suchen und zurückgeben
// ------------------------------------------------------------------------------
	function nameToPage($pagename, $currentcat) {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CONTENT_EXTENSION;
		// Kategorie-Verzeichnis einlesen
		$dircontent = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcat", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
		// alle vorhandenen Inhaltsdateien durchgehen...
		foreach ($dircontent as $currentelement) {
			// ...und wenn eine auf den Namen paßt...
			if (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($CONTENT_EXTENSION)) == $pagename) {
			//if (substr($currentelement, 3, strlen($currentelement)-3) == $pagename){
				// ...den Kategorie zurückgeben
				return $currentelement;
			}
		}
		// Wenn keine Datei paßt: Leerstring zurückgeben
		return "";
	}


// ------------------------------------------------------------------------------    
// Seitennamen aus komplettem Dateinamen einer Inhaltsseite zurückgeben
// ------------------------------------------------------------------------------
	function pageToName($page, $rebuildnbsp) {
		global $CONTENT_EXTENSION;
		global $specialchars;
		return $specialchars->rebuildSpecialChars(substr($page, 3, strlen($page) - 3 - strlen($CONTENT_EXTENSION)), $rebuildnbsp);
	}	


// ------------------------------------------------------------------------------    
// Kategorienamen aus komplettem Verzeichnisnamen einer Kategorie zurückgeben
// ------------------------------------------------------------------------------
	function catToName($cat, $rebuildnbsp) {
		global $CONTENT_EXTENSION;
		global $specialchars;
		return $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), $rebuildnbsp);
	}	


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rückgabe als String
// ------------------------------------------------------------------------------
	function getContent() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CONTENT_EXTENSION;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		// Contentseiten der angeforderten Kategorie in Array einlesen
		$pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
		// Das Array der Contentseiten elementweise prüfen...
		foreach ($pagesarray as $currentelement) {
			// ...und bei einem Treffer den Inhalt der Content-Datei zurückgeben
			if ($currentelement == "$PAGE_REQUEST$CONTENT_EXTENSION"){
				return implode("", file("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$CONTENT_EXTENSION"));
			}
		}
	}
	
	

// ------------------------------------------------------------------------------
// Auslesen des Content-Verzeichnisses unter Berücksichtigung 
// des auszuschließenden File-Verzeichnisses, Rückgabe als Array
// ------------------------------------------------------------------------------
	function getDirContentAsArray($dir, $iscatdir) {
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR; 
		global $CONTENT_GALLERY_DIR;
		$currentdir = opendir($dir);
		$i=0;
		// Einlesen des gesamten Content-Verzeichnisses außer dem 
		// auszuschließenden Verzeichnis und den Elementen . und ..
		while ($file = readdir($currentdir)) {
			if (
					// wenn Kategorieverzeichnis: Alle Dateien auslesen, die auf $CONTENT_EXTENSION enden...
					((substr($file, strlen($file)-4, strlen($file)) == $CONTENT_EXTENSION) || (!$iscatdir))
					// ...und nicht $CONTENT_FILES_DIR oder $CONTENT_GALLERY_DIR
					&& ((($file <> $CONTENT_FILES_DIR) && ($file <> $CONTENT_GALLERY_DIR))  || (!$iscatdir))
					// nicht "." und ".."
					&& ($file <> ".") 
					&& ($file <> "..")
					) {
	    	$files[$i] = $file;
	    	$i++;
	    }
		}
		// Rückgabe des sortierten Arrays
		if ($files <> "")
			sort($files);
		return $files;
	}


// ------------------------------------------------------------------------------
// Aufbau des Hauptmenüs, Rückgabe als String
// ------------------------------------------------------------------------------
	function getMainMenu() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		global $specialchars;
		$mainmenu = "";
		// Kategorien-Verzeichnis einlesen
		$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, array(), false);
		// numerische Accesskeys für angezeigte Menüpunkte
		$currentaccesskey = 0;
		// Jedes Element des Arrays ans Menü anhängen
		foreach ($categoriesarray as $currentcategory) {
			// Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
			if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true) == "")
				$mainmenu .= "";
			// Aktuelle Kategorie als aktiven Menüpunkt anzeigen...
			elseif ($currentcategory == $CAT_REQUEST) {
				$currentaccesskey++;
				$mainmenu .= "<a href=\"index.php?cat=$currentcategory\" class=\"menuactive\" accesskey=\"$currentaccesskey\">".substr($specialchars->rebuildSpecialChars($currentcategory, false), 3, strlen($currentcategory))."</a>";
			}
			// ...alle anderen als normalen Menüpunkt.
			else {
				$currentaccesskey++;
				$mainmenu .= "<a href=\"index.php?cat=$currentcategory\" class=\"menu\" accesskey=\"$currentaccesskey\">".substr($specialchars->rebuildSpecialChars($currentcategory, false), 3, strlen($currentcategory))."</a>";
			}
		}
		// Rückgabe des Menüs
		return $mainmenu;
	}


// ------------------------------------------------------------------------------
// Aufbau des Detailmenüs, Rückgabe als String
// ------------------------------------------------------------------------------
	function getDetailMenu(){
		global $ACTION_REQUEST;
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		global $CONTENT_EXTENSION;
		global $specialchars;
		// Wurde keine Kategorie übergeben, dann leeres Detailmenü ausgeben
		if ($ACTION_REQUEST == "sitemap")
			return "<a href=\"index.php?action=sitemap\" class=\"detailmenuactive\">Sitemap</a>";
		elseif ($ACTION_REQUEST == "search")
			return "<a href=\"index.php?action=search&amp;query=".htmlentities($_GET['query'])."\" class=\"detailmenuactive\">Suchergebnisse f&uuml;r &quot;".htmlentities($_GET['query'])."&quot;</a>";
		elseif ($ACTION_REQUEST == "draft")
			return "<a href=\"index.php?action=draft\" class=\"detailmenuactive\">".substr($specialchars->rebuildSpecialChars($PAGE_REQUEST, true), 3, strlen($PAGE_REQUEST) - 3)." (Entwurf)</a>";
		$detailmenu = "";
		// Content-Verzeichnis der aktuellen Kategorie einlesen
		$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
		// alphanumerische Accesskeys (über numerischen ASCII-Code) für angezeigte Menüpunkte
		$currentaccesskey = 0;
		// Jedes Element des Arrays ans Menü anhängen
		foreach ($contentarray as $currentcontent) {
			$currentaccesskey++;
			// Aktuelle Kategorie als aktiven Menüpunkt anzeigen...
			if (substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)) == $PAGE_REQUEST) {
				$detailmenu .= "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=".
												substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
												"\" class=\"detailmenuactive\" accesskey=\"".chr($currentaccesskey+96)."\">".
												$specialchars->rebuildSpecialChars(substr($currentcontent, 3, strlen($currentcontent) - strlen($CONTENT_EXTENSION) - 3), false).
												"</a> ";
			}
			// ...alle anderen als normalen Menüpunkt.
			else {
				$detailmenu .= "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=".
												substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
												"\" class=\"detailmenu\" accesskey=\"".chr($currentaccesskey+96)."\">".
												$specialchars->rebuildSpecialChars(substr($currentcontent, 3, strlen($currentcontent) - strlen($CONTENT_EXTENSION) - 3), false).
												"</a> ";
			}
		}
		// Rückgabe des Menüs
		return $detailmenu;
	}


// ------------------------------------------------------------------------------
// Einlesen des Inhalts-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
	function getSearchForm(){
		$form = "<form method=\"get\" action=\"index.php\" name=\"search\" class=\"searchform\">"
		."<input type=\"hidden\" name=\"action\" value=\"search\" />"
		."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" accesskey=\"s\" />"
		."<input type=\"image\" name=\"action\" value=\"search\" src=\"grafiken/searchicon.gif\" alt=\"Suchen\" class=\"searchbutton\" title=\"Suchen\" />"
		."</form>";
		return $form;
	}


// ------------------------------------------------------------------------------
// Einlesen des Inhalts-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
	function getLastChangedContentPage(){
		global $specialchars;
		$latestchanged = array("cat" => "catname", "file" => "filename", "time" => 0);
		$currentdir = opendir("inhalt");
		while ($file = readdir($currentdir)) {
			if (($file <> ".") && ($file <> "..")) {
				$latestofdir = getLastChangeOfCat("inhalt/".$file);
				if ($latestofdir['time'] > $latestchanged['time']) {
					$latestchanged['cat'] = $file;
					$latestchanged['file'] = $latestofdir['file'];
					$latestchanged['time'] = $latestofdir['time'];
				}
	    }
		}
		return "<a href=\"index.php?cat=".$latestchanged['cat']."&amp;page=".substr($latestchanged['file'], 0, strlen($latestchanged['file'])-4)."\" title=\"Inhaltsseite &quot;".$specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true)."&quot; in der Kategorie &quot;".$specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true)."&quot; anzeigen\" class=\"latestchangedlink\">".$specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true)."</a> (".strftime("%d.%m.%Y, %H:%M:%S", date($latestchanged['time'])).")";
	}



// ------------------------------------------------------------------------------
// Einlesen eines Kategorie-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
	function getLastChangeOfCat($dir){
		global $CONTENT_EXTENSION;
		$latestchanged = array("file" => "filename", "time" => 0);
		$currentdir = opendir($dir);
		while ($file = readdir($currentdir)) {
			if (is_file($dir."/".$file) && (substr($file, strlen($file)-strlen($CONTENT_EXTENSION), strlen($CONTENT_EXTENSION)) == $CONTENT_EXTENSION)) {
				if (filemtime($dir."/".$file) > $latestchanged['time']) {
					$latestchanged['file'] = $file;
					$latestchanged['time'] = filemtime($dir."/".$file);
				}
	    }
		}
		return $latestchanged;
	}



// ------------------------------------------------------------------------------
// Erzeugung einer Sitemap
// ------------------------------------------------------------------------------
	function getSiteMap() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $specialchars;
		$sitemap = "<h1>Sitemap</h1>";
		// Kategorien-Verzeichnis einlesen
		$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, array(), false);
		// Jedes Element des Arrays an die Sitemap anhängen
		foreach ($categoriesarray as $currentcategory) {
			// Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
			if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true) == "")
				continue;
			$sitemap .= "<h2>".substr($specialchars->rebuildSpecialChars($currentcategory, true), 3, strlen($currentcategory))."</h2><ul>";
			// Alle Inhaltsseiten der aktuellen Kategorie auflisten...
			$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
			// Jedes Element des Arrays an die Sitemap anhängen
			foreach ($contentarray as $currentcontent) {
				$sitemap .= "<li><a href=\"index.php?cat=$currentcategory&amp;page=".
													substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
													"\" title=\"Inhaltsseite &quot;".
													substr($specialchars->rebuildSpecialChars($currentcontent, true), 3, strlen($currentcontent) - strlen($CONTENT_EXTENSION) - 3).
													"&quot; anzeigen\">".
													substr($specialchars->rebuildSpecialChars($currentcontent, true), 3, strlen($currentcontent) - strlen($CONTENT_EXTENSION) - 3).
													"</a></li>";
			}
			$sitemap .= "</ul>";
		}
		// Rückgabe des Menüs
		return $sitemap;
	}


// ------------------------------------------------------------------------------
// Anzeige der Suchergebnisse
// ------------------------------------------------------------------------------
	function getSearchResult() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_DIR_REL;
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR;
		global $CONTENT_GALLERY_DIR;
		global $USE_CMS_SYNTAX;
		global $specialchars;
		
		$query = htmlentities(trim($_GET['query']));
		$searchresults = "<h1>Suchergebnisse f&uuml;r &quot;".$query."&quot;</h1>";
		// Kategorien-Verzeichnis einlesen
		$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, array(), false);
		// Alle Kategorien durchsuchen
		$matchesoverall = 0;
		foreach ($categoriesarray as $currentcategory) {
			// Wenn die Kategorie keine Contentseiten hat, zur nächsten springen
			if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true) == "")
				continue;
			// Alle Inhaltsseiten der aktuellen Kategorie sammeln, die das Suchwort enthalten...
			$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", array($CONTENT_FILES_DIR, $CONTENT_GALLERY_DIR), true);
			$matchingpages = array();
			$i = 0;
			foreach ($contentarray as $currentcontent) {
				$pagename = substr($specialchars->rebuildSpecialChars($currentcontent, true), 3, strlen($currentcontent) - strlen($CONTENT_EXTENSION) - 3);
				$filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$currentcontent;
				if (filesize($filepath) > 0) {
					$handle = fopen($filepath, "r");
					$content = fread($handle, filesize($filepath));
					fclose($handle);
					if (
						($query == "") 
						|| (substr_count(strtolower($content), strtolower(html_entity_decode($query))) > 0) 
						|| (substr_count(strtolower($pagename), strtolower($query)) > 0)) {
						$matchingpages[$i] = $currentcontent;
						$i++;
					}
				}
			}
			// die gesammelten Seiten ausgeben
			if (count($matchingpages) > 0) {
				$categoryname = $specialchars->rebuildSpecialChars(substr($currentcategory, 3, strlen($currentcategory)-3), true);
				$searchresults .= "<h2>$categoryname</h2><ul>";
				foreach ($matchingpages as $matchingpage) {
					$pagename = substr($specialchars->rebuildSpecialChars($matchingpage, true), 3, strlen($matchingpage) - strlen($CONTENT_EXTENSION) - 3);
					$filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$matchingpage;
					$searchresults .= "<li><a href=\"index.php?cat=$currentcategory&amp;page=".
												substr($matchingpage, 0, strlen($matchingpage) - strlen($CONTENT_EXTENSION)).
												"&amp;highlight=$query\" title=\"Inhaltsseite &quot;$pagename&quot; ".
												"in der Kategorie &quot;".$categoryname."&quot; anzeigen\">".
												$pagename.
												"</a></li>";
				}
				$searchresults .= "</ul>";
				$matchesoverall += count($matchingpages);
			}
		}
		// Keine Inhalte gefunden?
		if ($matchesoverall == 0)
			$searchresults .= "Keine passenden Inhalte gefunden.";
		// Rückgabe des Menüs
		return $searchresults;
	}
	
	
// ------------------------------------------------------------------------------
// E-Mail-Adressen verschleiern 
// ------------------------------------------------------------------------------
// Dank für spam-me-not.php an Rolf Offermanns! 
// Spam-me-not in JavaScript: http://www.zapyon.de
	function obfuscateAdress($originalString, $mode) {
		// $mode == 1			dezimales ASCII
		// $mode == 2			hexadezimales ASCII
		// $mode == 3			zufällig gemischt
		$encodedString = "";
		$nowCodeString = "";
		$randomNumber = -1;

		$originalLength = strlen($originalString);
		$encodeMode = $mode;
		
		for ( $i = 0; $i < $originalLength; $i++) {
			if ($mode == 3) $encodeMode = rand(1,2);
			switch ($encodeMode) {
				case 1: // Decimal code
					$nowCodeString = "&#" . ord($originalString[$i]) . ";";
					break;
				case 2: // Hexadecimal code
					$nowCodeString = "&#x" . dechex(ord($originalString[$i])) . ";";
					break;
				default:
					return "ERROR: wrong encoding mode.";
			}
			$encodedString .= $nowCodeString;
		}
		return $encodedString;
	}



// ------------------------------------------------------------------------------
// Phrasen in Inhalt hervorheben
// ------------------------------------------------------------------------------
	function highlight($content, $phrase) {
		$phrase = preg_quote($phrase);
		// nicht ersetzen zwischen < und > sowie zwischen & und ;
		$content = preg_replace("/((<[^>]*|&[^;]*)|$phrase)/ie", '"\2"=="\1"? "\1":"<em class=\"highlight\">\1</em>"', $content);
		return $content;
	}



// ------------------------------------------------------------------------------
// Anzeige der Informationen zum System
// ------------------------------------------------------------------------------
	function getCmsInfo() {
		return "<a href=\"http://cms.mozilo.de/\" target=\"_blank\" class=\"latestchangedlink\" title=\"cms.mozilo.de\">moziloCMS 1.7</a>";
	}


?>