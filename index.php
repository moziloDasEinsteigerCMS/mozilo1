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
		www.mozilo.de

		Dieses Dokument stellt ein simples dateibasiertes
		Content Management System dar.
		
		Funktion:
		Siehe /admin/readme.htm

######
*/

	require_once("Language.php");
	require_once("Properties.php");
	require_once("SpecialChars.php");
	require_once("Syntax.php");
	require_once("Smileys.php");

	
	$language = new Language();
	$mainconfig = new Properties("conf/main.conf");
	$specialchars = new SpecialChars();
	$syntax = new Syntax();
	$smileys = new Smileys("smileys");
	
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
		$DEFAULT_CATEGORY = "10_Willkommen";

	$DEFAULT_PAGE				= $mainconfig->get("defaultpage");
	if ($DEFAULT_PAGE == "")
		$DEFAULT_PAGE = "10_Willkommen";

	$FAVICON_FILE				= $mainconfig->get("faviconfile");
	if ($FAVICON_FILE == "")
		$FAVICON_FILE = "favicon.ico";

	$USE_CMS_SYNTAX			= true;
	if ($mainconfig->get("usecmssyntax") == "false")
		$USE_CMS_SYNTAX = false;

	if (isset($_GET['cat']))
		$CAT_REQUEST 				= htmlentities(stripslashes($_GET['cat']));
	else
		$CAT_REQUEST 				= "";

	if (isset($_GET['page']))
		$PAGE_REQUEST 				= htmlentities(stripslashes($_GET['page']));
	else
		$PAGE_REQUEST 				= "";

	if (isset($_GET['action']))
		$ACTION_REQUEST 				= htmlentities(stripslashes($_GET['action']));
	else
		$ACTION_REQUEST 				= "";
		
	$CONTENT_DIR_REL		= "kategorien";
	$CONTENT_DIR_ABS 		= getcwd() . "/$CONTENT_DIR_REL";
	$CONTENT_FILES_DIR	= "dateien";
	$GALLERIES_DIR			= "galerien";
	$DEFAULT_CONTENT_EXT= ".txt";
	$CONTENT_EXTENSION	= $DEFAULT_CONTENT_EXT;
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
				|| (getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST", true) == "")
			)
			// ...dann verwende die Standardkategorie
			$CAT_REQUEST = $DEFAULT_CATEGORY;

		
		// Kategorie-Verzeichnis einlesen
		$pagesarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST/", true);
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
		global $language;
		global $syntax;
		global $mainconfig;
		global $smileys;
		
		// Template-Datei auslesen
    if (!$file = @fopen($TEMPLATE_FILE, "r"))
        die($language->getLanguageValue1("message_template_error_1", $TEMPLATE_FILE));
    $template = fread($file, filesize($TEMPLATE_FILE));
    fclose($file);
    
		// Platzhalter des Templates mit Inhalt füllen
		$pagecontentarray = array();
 	  // getSiteMap, getSearchResult und getContent liefern jeweils ein Array:
 	  // [0] = Inhalt
 	  // [1] = Name der Kategorie (leer bei getSiteMap, getSearchResult)
 	  // [2] = Name des Inhalts
    $pagecontent = "";
 	  $cattitle = "";
 	  $pagetitle = "";

    if ($ACTION_REQUEST == "sitemap") {
    	$pagecontentarray = getSiteMap();
	    $pagecontent	= $pagecontentarray[0];
	    $cattitle 		= $pagecontentarray[1];
  	  $pagetitle 		= $pagecontentarray[2];
    }
    elseif ($ACTION_REQUEST == "search") {
    	$pagecontentarray = getSearchResult();
	    $pagecontent	= $pagecontentarray[0];
	    $cattitle 		= $pagecontentarray[1];
  	  $pagetitle 		= $pagecontentarray[2];
    }
    elseif ($USE_CMS_SYNTAX) {
    	$pagecontentarray = getContent();
	    $pagecontent	= $syntax->convertContent($pagecontentarray[0], true);
	    $cattitle 		= $pagecontentarray[1];
  	  $pagetitle 		= $pagecontentarray[2];
  	}
    else {
    	$pagecontentarray = getContent();
	    $pagecontent	= $pagecontentarray[0];
	    $cattitle 		= $pagecontentarray[1];
  	  $pagetitle 		= $pagecontentarray[2];
  	}
  	
  	// Smileys ersetzen
 		if ($mainconfig->get("replaceemoticons") == "true")
  		$pagecontent = $smileys->replaceEmoticons($pagecontent);

		// Gesuchte Phrasen hervorheben
		if ((isset($_GET['highlight'])) &&  ($_GET['highlight'] <> ""))
			$pagecontent = highlight($pagecontent, $_GET['highlight']);
		
    $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $template);
    $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    $HTML = preg_replace('/{WEBSITE_TITLE}/', getWebsiteTitle($WEBSITE_TITLE, $cattitle, $pagetitle), $HTML);
		$HTML = preg_replace('/{CONTENT}/', $pagecontent, $HTML);
    $HTML = preg_replace('/{MAINMENU}/', getMainMenu(), $HTML);
		// Detailmenü nicht zeigen, wenn Submenüs aktiviert sind
		if ($mainconfig->get("usesubmenu") == "true") {
			$HTML = preg_replace('/{DETAILMENU}/', "", $HTML);
			$HTML = preg_replace('/{SUBMENU}/', getDetailMenu(), $HTML);
		}
		else {
    	$HTML = preg_replace('/{DETAILMENU}/', "<div class=\"detailmenu\">".getDetailMenu()."</div>", $HTML);
    	$HTML = preg_replace('/{SUBMENU}/', "", $HTML);
    }
    $HTML = preg_replace('/{SEARCH}/', getSearchForm(), $HTML);
    $HTML = preg_replace('/{LASTCHANGE}/', getLastChangedContentPage(), $HTML);
    $HTML = preg_replace('/{SITEMAPLINK}/', "<a href=\"index.php?action=sitemap\" class=\"latestchangedlink\" title=\"".$language->getLanguageValue0("tooltip_showsitemap_0")."\">".$language->getLanguageValue0("message_sitemap_0")."</a>", $HTML);
    $HTML = preg_replace('/{CMSINFO}/', getCmsInfo(), $HTML);
	}


// ------------------------------------------------------------------------------    
// Zu einem Kategorienamen passendes Kategorieverzeichnis suchen und zurückgeben
// Alle Kühe => 00_Alle-nbsp-K-uuml-he
// ------------------------------------------------------------------------------
	function nameToCategory($catname) {
		global $CONTENT_DIR_ABS;
		// Content-Verzeichnis einlesen
		$dircontent = getDirContentAsArray("$CONTENT_DIR_ABS", false);
		// alle vorhandenen Kategorien durchgehen...
		foreach ($dircontent as $currentelement) {
			// ...und wenn eine auf den Namen paßt...
			if (substr($currentelement, 3, strlen($currentelement)-3) == $catname){
				// ...den vollen Kategorienamen zurückgeben
				return $currentelement;
			}
		}
		// Wenn kein Verzeichnis paßt: Leerstring zurückgeben
		return "";
	}
	

// ------------------------------------------------------------------------------    
// Zu einer Inhaltsseite passende Datei suchen und zurückgeben
// Müllers Kuh => 00_M-uuml-llers-nbsp-Kuh.txt
// ------------------------------------------------------------------------------
	function nameToPage($pagename, $currentcat) {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_EXTENSION;
		global $DEFAULT_CONTENT_EXT;
		
		$temp = $CONTENT_EXTENSION;
		$CONTENT_EXTENSION = $DEFAULT_CONTENT_EXT;
		// Kategorie-Verzeichnis einlesen
		$dircontent = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcat", true);
		// alle vorhandenen Inhaltsdateien durchgehen...
		foreach ($dircontent as $currentelement) {
			// ...und wenn eine auf den Namen paßt...
			if (substr($currentelement, 3, strlen($currentelement) - 3 - strlen($CONTENT_EXTENSION)) == $pagename) {
				// ...den vollen Seitennamen zurückgeben
				$CONTENT_EXTENSION = $temp;
				return $currentelement;
			}
		}
		// Wenn keine Datei paßt: Leerstring zurückgeben
		$CONTENT_EXTENSION = $temp;
		return "";
	}


// ------------------------------------------------------------------------------    
// Kategorienamen aus komplettem Verzeichnisnamen einer Kategorie zurückgeben
// 00_Alle-nbsp-K-uuml-he => Alle Kühe
// ------------------------------------------------------------------------------
	function catToName($cat, $rebuildnbsp) {
		global $CONTENT_EXTENSION;
		global $specialchars;
		return $specialchars->rebuildSpecialChars(substr($cat, 3, strlen($cat)), $rebuildnbsp);
	}	


// ------------------------------------------------------------------------------    
// Seitennamen aus komplettem Dateinamen einer Inhaltsseite zurückgeben
// 00_M-uuml-llers-nbsp-Kuh.txt => Müllers Kuh
// ------------------------------------------------------------------------------
	function pageToName($page, $rebuildnbsp) {
		global $CONTENT_EXTENSION;
		global $specialchars;
		return $specialchars->rebuildSpecialChars(substr($page, 3, strlen($page) - 3 - strlen($CONTENT_EXTENSION)), $rebuildnbsp);
	}	


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rückgabe als String
// ------------------------------------------------------------------------------
	function getContent() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CONTENT_EXTENSION;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		global $specialchars;
		
		if (file_exists("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$CONTENT_EXTENSION"))
			return array (
										implode("", file("$CONTENT_DIR_ABS/$CAT_REQUEST/$PAGE_REQUEST$CONTENT_EXTENSION")), 
										catToName($CAT_REQUEST, true),
										pageToName($PAGE_REQUEST.$CONTENT_EXTENSION, true)
										);
		else
			return "";
	}
	
	

// ------------------------------------------------------------------------------
// Auslesen des Content-Verzeichnisses unter Berücksichtigung 
// des auszuschließenden File-Verzeichnisses, Rückgabe als Array
// ------------------------------------------------------------------------------
	function getDirContentAsArray($dir, $iscatdir) {
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR; 
		$currentdir = opendir($dir);
		$i=0;
		// Einlesen des gesamten Content-Verzeichnisses außer dem 
		// auszuschließenden Verzeichnis und den Elementen . und ..
		while ($file = readdir($currentdir)) {
			if (
					// wenn Kategorieverzeichnis: Alle Dateien auslesen, die auf $CONTENT_EXTENSION enden...
					((substr($file, strlen($file)-strlen($CONTENT_EXTENSION), strlen($file)) == $CONTENT_EXTENSION) || (!$iscatdir))
					// ...und nicht $CONTENT_FILES_DIR
					&& (($file <> $CONTENT_FILES_DIR) || (!$iscatdir))
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
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR;
		global $CAT_REQUEST;
		global $DEFAULT_CONTENT_EXT;
		global $PAGE_REQUEST;
		global $specialchars;
		global $mainconfig;

		$mainmenu = "";
		$temp = $CONTENT_EXTENSION;
		$CONTENT_EXTENSION = $DEFAULT_CONTENT_EXT;
		// Kategorien-Verzeichnis einlesen
		$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false);
		// numerische Accesskeys für angezeigte Menüpunkte
		$currentaccesskey = 0;
		// Jedes Element des Arrays ans Menü anhängen
		foreach ($categoriesarray as $currentcategory) {
			// Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
			if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true) == "")
				$mainmenu .= "";
			// Aktuelle Kategorie als aktiven Menüpunkt anzeigen...
			elseif ($currentcategory == $CAT_REQUEST) {
				$currentaccesskey++;
				$mainmenu .= "<a href=\"index.php?cat=$currentcategory\" class=\"menuactive\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a> ";
				if ($mainconfig->get("usesubmenu") == "true")
					$mainmenu .= "{SUBMENU}";
			}
			// ...alle anderen als normalen Menüpunkt.
			else {
				$currentaccesskey++;
				$mainmenu .= "<a href=\"index.php?cat=$currentcategory\" class=\"menu\" accesskey=\"$currentaccesskey\">".catToName($currentcategory, false)."</a> ";
			}
		}
		// Rückgabe des Menüs
		$CONTENT_EXTENSION = $temp;
		return $mainmenu;
	}


// ------------------------------------------------------------------------------
// Aufbau des Detailmenüs, Rückgabe als String
// ------------------------------------------------------------------------------
	function getDetailMenu(){
		global $ACTION_REQUEST;
		global $CONTENT_DIR_ABS;
		global $CONTENT_FILES_DIR;
		global $CAT_REQUEST;
		global $PAGE_REQUEST;
		global $CONTENT_EXTENSION;
		global $language;
		global $specialchars;
		global $mainconfig;
		
		if ($mainconfig->get("usesubmenu") == "true")
			$cssprefix = "submenu";
		else
			$cssprefix = "detailmenu";
		
		// Wurde keine Kategorie übergeben, dann leeres Detailmenü ausgeben
		if ($ACTION_REQUEST == "sitemap")
			return "<a href=\"index.php?action=sitemap\" class=\"".$cssprefix."active\">".$language->getLanguageValue0("message_sitemap_0")."</a>";
		elseif ($ACTION_REQUEST == "search")
			return "<a href=\"index.php?action=search&amp;query=".htmlentities($_GET['query'])."\" class=\"".$cssprefix."active\">".$language->getLanguageValue1("message_searchresult_1", htmlentities($_GET['query']))."</a>";
		elseif ($ACTION_REQUEST == "draft")
			return "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=$PAGE_REQUEST&amp;action=draft\" class=\"".$cssprefix."active\">".pageToName($PAGE_REQUEST.$CONTENT_EXTENSION, false)." (".$language->getLanguageValue0("message_draft_0").")</a>";
		$detailmenu = "";
		// Content-Verzeichnis der aktuellen Kategorie einlesen
		$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$CAT_REQUEST", true);
		// alphanumerische Accesskeys (über numerischen ASCII-Code) für angezeigte Menüpunkte
		$currentaccesskey = 0;
		// Jedes Element des Arrays ans Menü anhängen
		foreach ($contentarray as $currentcontent) {
			$currentaccesskey++;
			// Aktuelle Inhaltsseite als aktiven Menüpunkt anzeigen...
			if (substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)) == $PAGE_REQUEST) {
				$detailmenu .= "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=".
												substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
												"\" class=\"".$cssprefix."active\" accesskey=\"".chr($currentaccesskey+96)."\">".
												pageToName($currentcontent, false).
												"</a> ";
			}
			// ...alle anderen als normalen Menüpunkt.
			else {
				$detailmenu .= "<a href=\"index.php?cat=$CAT_REQUEST&amp;page=".
												substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
												"\" class=\"".$cssprefix."\" accesskey=\"".chr($currentaccesskey+96)."\">".
												pageToName($currentcontent, false).
												"</a> ";
			}
		}
		// Rückgabe des Menüs
		return $detailmenu;
	}


// ------------------------------------------------------------------------------
// Rückgabe des Suchfeldes
// ------------------------------------------------------------------------------
	function getSearchForm(){
		global $language;
		$form = "<form method=\"get\" action=\"index.php\" name=\"search\" class=\"searchform\">"
		."<input type=\"hidden\" name=\"action\" value=\"search\" />"
		."<input type=\"text\" name=\"query\" value=\"\" class=\"searchtextfield\" accesskey=\"s\" />"
		."<input type=\"image\" name=\"action\" value=\"search\" src=\"grafiken/searchicon.gif\" alt=\"".$language->getLanguageValue0("message_search_0")."\" class=\"searchbutton\" title=\"".$language->getLanguageValue0("message_search_0")."\" />"
		."</form>";
		return $form;
	}


// ------------------------------------------------------------------------------
// Einlesen des Inhalts-Verzeichnisses, Rückgabe der zuletzt geänderten Datei
// ------------------------------------------------------------------------------
	function getLastChangedContentPage(){
		global $CONTENT_DIR_REL;
		global $CONTENT_EXTENSION;
		global $DEFAULT_CONTENT_EXT;
		global $language;
		global $specialchars;

		$temp = $CONTENT_EXTENSION;
		$CONTENT_EXTENSION = $DEFAULT_CONTENT_EXT;

		$latestchanged = array("cat" => "catname", "file" => "filename", "time" => 0);
		$currentdir = opendir($CONTENT_DIR_REL);
		while ($file = readdir($currentdir)) {
			if (($file <> ".") && ($file <> "..")) {
				$latestofdir = getLastChangeOfCat($CONTENT_DIR_REL."/".$file);
				if ($latestofdir['time'] > $latestchanged['time']) {
					$latestchanged['cat'] = $file;
					$latestchanged['file'] = $latestofdir['file'];
					$latestchanged['time'] = $latestofdir['time'];
				}
	    }
		}
		$CONTENT_EXTENSION = $temp;
		return $language->getLanguageValue0("message_lastchange_0")." <a href=\"index.php?cat=".$latestchanged['cat']."&amp;page=".substr($latestchanged['file'], 0, strlen($latestchanged['file'])-4)."\" title=\"".$language->getLanguageValue2("tooltip_link_page_2", $specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true), $specialchars->rebuildSpecialChars(substr($latestchanged['cat'], 3, strlen($latestchanged['cat'])-3), true))."\" class=\"latestchangedlink\">".$specialchars->rebuildSpecialChars(substr($latestchanged['file'], 3, strlen($latestchanged['file'])-7), true)."</a> (".strftime($language->getLanguageValue0("_dateformat_0"), date($latestchanged['time'])).")";
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
		global $language;
		global $specialchars;
		$sitemap = "<h1>".$language->getLanguageValue0("message_sitemap_0")."</h1>";
		// Kategorien-Verzeichnis einlesen
		$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false);
		// Jedes Element des Arrays an die Sitemap anhängen
		foreach ($categoriesarray as $currentcategory) {
			// Wenn die Kategorie keine Contentseiten hat, zeige sie nicht an
			if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true) == "")
				continue;
			$sitemap .= "<h2>".catToName($currentcategory, false)."</h2><ul>";
			// Alle Inhaltsseiten der aktuellen Kategorie auflisten...
			$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true);
			// Jedes Element des Arrays an die Sitemap anhängen
			foreach ($contentarray as $currentcontent) {
				$sitemap .= "<li><a href=\"index.php?cat=$currentcategory&amp;page=".
													substr($currentcontent, 0, strlen($currentcontent) - strlen($CONTENT_EXTENSION)).
													"\" title=\"".$language->getLanguageValue2("tooltip_link_page_2", pageToName($currentcontent, false), catToName($currentcategory, false))."\">".
													pageToName($currentcontent, false).
													"</a></li>";
			}
			$sitemap .= "</ul>";
		}
		// Rückgabe der Sitemap
		return array($sitemap, $language->getLanguageValue0("message_sitemap_0"), $language->getLanguageValue0("message_sitemap_0"));
	}


// ------------------------------------------------------------------------------
// Anzeige der Suchergebnisse
// ------------------------------------------------------------------------------
	function getSearchResult() {
		global $CONTENT_DIR_ABS;
		global $CONTENT_DIR_REL;
		global $CONTENT_EXTENSION;
		global $CONTENT_FILES_DIR;
		global $USE_CMS_SYNTAX;
		global $language;
		global $specialchars;
		
		$matchesoverall = 0;
		$searchresults = "";
		
		// Überhaupt erst etwas machen, wenn die Suche nicht leer ist
		if (trim($_GET['query']) != "") {
			// Damit die Links in der Ergbnisliste korrekt sind: Suchanfrage bereinigen
			$queryarray = explode(" ", preg_replace('/"/',"",$_GET['query']));
			$searchresults .= "<h1>".$language->getLanguageValue1("message_searchresult_1", htmlentities(trim($_GET['query'])))."</h1>";

			// Kategorien-Verzeichnis einlesen
			$categoriesarray = getDirContentAsArray($CONTENT_DIR_ABS, false);

			// Alle Kategorien durchsuchen
			foreach ($categoriesarray as $currentcategory) {

				// Wenn die Kategorie keine Contentseiten hat, direkt zur nächsten springen
				if (getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true) == "")
					continue;

				$contentarray = getDirContentAsArray("$CONTENT_DIR_ABS/$currentcategory", true);
				$matchingpages = array();
				$i = 0;

				// Alle Inhaltsseiten durchsuchen
				foreach ($contentarray as $currentcontent) {
					$pagename = pageToName($currentcontent, false);
					$filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$currentcontent;
					$ismatch = false;
					$content = "";
					// Dateiinhalt auslesen, wenn vorhanden...
					if (filesize($filepath) > 0) {
						$handle = fopen($filepath, "r");
						$content = fread($handle, filesize($filepath));
						fclose($handle);
						// ...und alle Syntax-Tags entfernen. Gesucht werden soll nur im reinen Text
						$content = preg_replace("/\[[^\[\]]+\|([^\[\]]*)\]/U", "$1", $content);
						// Auch Emoticons in Doppelpunkten (z.B. ":lach:") sollen nicht berücksichtigt werden
						$content = preg_replace("/:[^\s]+:/U", "", $content);
						// Zum Schluß noch die horizontalen Linien ("[----]") von der Suche ausschließen
						$content = preg_replace("/\[----\]/U", "", $content);
					}
					// für jede Seite alle Suchbegriffe suchen
					foreach($queryarray as $query) {
						if ($query == "")
							continue;
						// Wenn...
						if (
							// ...der aktuelle Suchbegriff im Seitennamen...
							(substr_count(strtolower($pagename), strtolower($query)) > 0)
							// ...oder im eigentlichen Seiteninhalt vorkommt (überpürüft werden nur Seiten, die nicht leer sind), ...
							|| ((filesize($filepath) > 0) && (substr_count(strtolower($content), strtolower(html_entity_decode($query))) > 0))
							)
							// ...dann setze das Treffer-Flag
							$ismatch = true;
					}
					// Treffer? -> Seite in die Ergebnisliste aufnehmen
					if ($ismatch) {
						$matchingpages[$i] = $currentcontent;
						$i++;
					}
				}
				
				// die gesammelten Seiten ausgeben
				if (count($matchingpages) > 0) {
					$highlightparameter = implode(",", $queryarray);
					$categoryname = catToName($currentcategory, false);
					$searchresults .= "<h2>$categoryname</h2><ul>";
					foreach ($matchingpages as $matchingpage) {
						$pagename = pageToName($matchingpage, false);
						$filepath = $CONTENT_DIR_REL."/".$currentcategory."/".$matchingpage;
						$searchresults .= "<li>".
							highlight(
								"<a href=\"index.php?cat=$currentcategory&amp;page=".
								substr($matchingpage, 0, strlen($matchingpage) - strlen($CONTENT_EXTENSION)).
								"&amp;highlight=$highlightparameter\" title=\"".$language->getLanguageValue2("tooltip_link_page_2", $pagename, $categoryname)."\">".
								$pagename.
								"</a>", 
								$_GET['query']).
							"</li>";
					}
					$searchresults .= "</ul>";
					$matchesoverall += count($matchingpages);
				}
			}
		}
		// Keine Inhalte gefunden?
		if ($matchesoverall == 0)
			$searchresults .= $language->getLanguageValue0("message_nodatafound_0", htmlentities(trim($_GET['query'])));
		// Rückgabe des Menüs
		return array($searchresults, $language->getLanguageValue0("message_search_0"), $language->getLanguageValue1("message_searchresult_1", htmlentities(trim($_GET['query']))));
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
	function highlight($content, $phrasestring) {
		// Zu highlightende Begriffe kommen kommasepariert ("begriff1,begriff2")-> in Array wandeln
		$phrasearray = explode(",", htmlentities($phrasestring));
		// jeden Begriff highlighten
		foreach($phrasearray as $phrase) {
			// Regex-Zeichen im zu highlightenden Text escapen (.\+*?[^]$(){}=!<>|:)
			$phrase = preg_quote($phrase);
			// Slashes im zu highlightenden Text escapen
			$phrase = preg_replace("/\//", "\\\\/", $phrase);
			$content = preg_replace("/((<[^>]*|&[^;]*)|$phrase)/ie", '"\2"=="\1"? "\1":"<em class=\"highlight\">\1</em>"', $content);
		}
		return $content;
	}



// ------------------------------------------------------------------------------
// Rückgabe des Website-Titels
// ------------------------------------------------------------------------------
	function getWebsiteTitle($websitetitle, $cattitle, $pagetitle) {
		global $mainconfig;

		$title = $mainconfig->get("titlebarformat");
		$sep = $mainconfig->get("titlebarseparator");
		
    $title = preg_replace('/{WEBSITE}/', $websitetitle, $title);
		if ($cattitle == "")
			$title = preg_replace('/{CATEGORY}/', "", $title);
		else
			$title = preg_replace('/{CATEGORY}/', $cattitle, $title);
    $title = preg_replace('/{PAGE}/', $pagetitle, $title);
    $title = preg_replace('/{SEP}/', $sep, $title);
    return $title;
	}



// ------------------------------------------------------------------------------
// Anzeige der Informationen zum System
// ------------------------------------------------------------------------------
	function getCmsInfo() {
		global $mainconfig;
		global $language;
		return "<a href=\"http://cms.mozilo.de/\" target=\"_blank\" class=\"latestchangedlink\" title=\"".$language->getLanguageValue1("tooltip_link_extern_1", "http://cms.mozilo.de")."\">moziloCMS ".$mainconfig->get("cmsversion")."</a>";
	}


?>