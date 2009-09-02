<?php
/*
######
INHALT
######
		
		Projekt "Flatfile-basiertes CMS für Einsteiger"
		Online-Bildergalerie
		Mai 2006
		Klasse ITF04-1
		Industrieschule Chemnitz

		Ronny Monser
		Arvid Zimmermann
		Oliver Lorenz
		-> mozilo

######
*/

require_once("Properties.php");
require_once("SpecialChars.php");
$mainconf = new Properties("main.conf");
$specialchars = new SpecialChars();

// Vorschaubilder nach Benutzereinstellung und wenn GDlib installiert
if (!extension_loaded("gd"))
	$mainconf->set("galleryusethumbs", "false");
if ($mainconf->get("galleryusethumbs") == "true")
	$USETHUMBS = true;
else
	$USETHUMBS = false;

$MAX_IMG_WIDTH 			= $mainconf->get("gallerymaxwidth");
if ($MAX_IMG_WIDTH == "")
	$MAX_IMG_WIDTH = 500;

$MAX_IMG_HEIGHT 		= $mainconf->get("gallerymaxheight");
if ($MAX_IMG_HEIGHT == "")
	$MAX_IMG_HEIGHT = 350;

$WEBSITE_TITLE			= $mainconf->get("websitetitle");
if ($WEBSITE_TITLE == "")
	$WEBSITE_TITLE = "Titel der Website";

$TEMPLATE_FILE			= $mainconf->get("gallerytemplatefile");
if ($TEMPLATE_FILE == "")
	$TEMPLATE_FILE = "gallerytemplate.html";

$CSS_FILE						= $mainconf->get("cssfile");
if ($CSS_FILE == "")
	$CSS_FILE = "css/style.css";

$FAVICON_FILE				= $mainconf->get("faviconfile");
if ($FAVICON_FILE == "")
	$FAVICON_FILE = "favicon.ico";

// Übergebene Parameter überprüfen
$CAT_REQUEST = $_GET['cat'];
$DIR_GALLERY = "inhalt/".$CAT_REQUEST."/galerie/";
$DIR_THUMBS = $DIR_GALLERY."vorschau/";
if (($CAT_REQUEST == "") || (!file_exists($DIR_GALLERY)))
	die ("FEHLER: Keine g&uuml;ltige Kategorie angegeben oder fehlendes Galerieverzeichnis!");
$CURRENTCATEGORY = substr($CAT_REQUEST,3);

// Galerieverzeichnis einlesen
$PICARRAY = getPicsAsArray($DIR_GALLERY, array("jpg", "jpeg", "jpe", "gif", "png"));
$ALLINDEXES = array();
for($i=1;$i<=count($PICARRAY);$i++) 
	array_push($ALLINDEXES, $i);
// globaler Index
if ((!isset($_GET['index'])) || (!in_array($_GET['index'], $ALLINDEXES)))
	$INDEX = 1;
else
	$INDEX = $_GET['index'];

// Bestimmung der Positionen
$FIRST = 1;
$LAST = count($ALLINDEXES);
if (!in_array($INDEX-1, $ALLINDEXES))
	$BEFORE = $LAST;
else
	$BEFORE = $INDEX-1;
if (!in_array($INDEX+1, $ALLINDEXES))
	$NEXT = 1;
else
	$NEXT = $INDEX+1;
	
if ($USETHUMBS) {
	checkThumbs();
	$THUMBARRAY = getPicsAsArray($DIR_THUMBS, array("jpg", "jpeg", "jpe", "gif", "png"));
}

// Galerie aufbauen und ausgeben
$HTML = "";
readTemplate();
echo $HTML;




// ------------------------------------------------------------------------------
// HTML-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
	function readTemplate() {
		global $CSS_FILE;
		global $CURRENTCATEGORY;
		global $HTML;
		global $FAVICON_FILE;
		global $PICARRAY;
		global $INDEX;
		global $specialchars;
		global $TEMPLATE_FILE;
		global $USE_CMS_SYNTAX;
		global $USETHUMBS;
		global $WEBSITE_TITLE;
		// Template-Datei auslesen
    if (!$file = @fopen($TEMPLATE_FILE, "r"))
        die("'$TEMPLATE_FILE' fehlt! Bitte kontaktieren Sie den Administrator.");
    $template = fread($file, filesize($TEMPLATE_FILE));
    fclose($file);
    
		// Platzhalter des Templates mit Inhalt füllen
    $HTML = preg_replace('/{CSS_FILE}/', $CSS_FILE, $template);
    $HTML = preg_replace('/{FAVICON_FILE}/', $FAVICON_FILE, $HTML);
    $HTML = preg_replace('/{WEBSITE_TITLE}/', $WEBSITE_TITLE, $HTML);
    $HTML = preg_replace('/{CURRENTCATEGORY}/', $specialchars->rebuildSpecialChars($CURRENTCATEGORY, true), $HTML);
    if (count($PICARRAY) == 0)
    	$HTML = preg_replace('/{NUMBERMENU}/', "Diese Galerie enth&auml;lt keine Bilder.", $HTML);
		if ($USETHUMBS) {
	    $HTML = preg_replace('/{GALLERYMENU}/', "&nbsp;", $HTML);
    	$HTML = preg_replace('/{NUMBERMENU}/', getThumbnails(), $HTML);
	    $HTML = preg_replace('/{CURRENTPIC}/', "&nbsp;", $HTML);
	    $HTML = preg_replace('/{CURRENTDESCRIPTION}/', "&nbsp;", $HTML);
	    $HTML = preg_replace('/{XOUTOFY}/', "&nbsp;", $HTML);
		}
		else {
	    $HTML = preg_replace('/{GALLERYMENU}/', getGalleryMenu(), $HTML);
	    $HTML = preg_replace('/{NUMBERMENU}/', getNumberMenu(), $HTML);
	    $HTML = preg_replace('/{CURRENTPIC}/', getCurrentPic(), $HTML);
	    $HTML = preg_replace('/{CURRENTDESCRIPTION}/', getCurrentDescription($PICARRAY[$INDEX-1]), $HTML);
	    $HTML = preg_replace('/{XOUTOFY}/', getXoutofY(), $HTML);
		}
	}
	
	
// ------------------------------------------------------------------------------
// Galeriemenü erzeugen
// ------------------------------------------------------------------------------
	function getGalleryMenu() {
		global $ALLINDEXES;
		global $BEFORE;
		global $CAT_REQUEST;
		global $FIRST;
		global $INDEX;
		global $PICARRAY;
		global $LAST;
		global $NEXT;
		
		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "&nbsp;";
		// Link "Erstes Bild"
		if ($INDEX == $FIRST)
			$linkclass = "detailmenuactive";
		else
			$linkclass = "detailmenu";
		$gallerymenu = "<a href=\"gallery.php?cat=$CAT_REQUEST&amp;index=$FIRST\" class=\"$linkclass\">Erstes Bild</a> ";
		// Link "Voriges Bild"
		$gallerymenu .= "<a href=\"gallery.php?cat=$CAT_REQUEST&amp;index=$BEFORE\" class=\"detailmenu\">Voriges Bild</a> ";
		// Link "Nächstes Bild"
		$gallerymenu .= "<a href=\"gallery.php?cat=$CAT_REQUEST&amp;index=$NEXT\" class=\"detailmenu\">Nächstes Bild</a> ";
		// Link "Letztes Bild"
		if ($INDEX == $LAST)
			$linkclass = "detailmenuactive";
		else
			$linkclass = "detailmenu";
		$gallerymenu .= "<a href=\"gallery.php?cat=$CAT_REQUEST&amp;index=$LAST\" class=\"$linkclass\">Letztes Bild</a>";
		// Rückgabe des Menüs
		return $gallerymenu;
	}
	
	
// ------------------------------------------------------------------------------
// Nummernmenü erzeugen
// ------------------------------------------------------------------------------
	function getNumberMenu() {
		global $mainconf;
		global $CAT_REQUEST;
		global $FIRST;
		global $INDEX;
		global $LAST;
		global $PICARRAY;

		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "&nbsp;";

		$numbermenu = "";
		for ($i=$FIRST; $i<=$LAST; $i++) {
			if ($INDEX == $i)
					$numbermenu .= "<em class=\"bold\">".$i."</em> | ";
			else
					$numbermenu .= "<a href=\"gallery.php?cat=".$CAT_REQUEST."&amp;index=".$i."\">".$i."</a> | ";
		}
		// Rückgabe des Menüs
		return substr($numbermenu, 0, strlen($numbermenu)-2);
	}
	

// ------------------------------------------------------------------------------
// Nummernmenü erzeugen
// ------------------------------------------------------------------------------
	function getThumbnails() {
		global $DIR_GALLERY;
		global $DIR_THUMBS;
		global $PICARRAY;
		global $THUMBARRAY;
		global $mainconf;
		
		// Aus Config auslesen: Wieviele Bilder pro Tabellenzeile?
		$picsperrow = $mainconf->get("gallerypicsperrow");
		if (($picsperrow == "") || ($picsperrow == 0))
			$picsperrow = 4;

		$thumbs = "<table class=\"gallerytable\"><tr>";
		$i = 0;
		for ($i=0; $i<count($THUMBARRAY); $i++) {
			// Bildbeschreibung holen
			$description = getCurrentDescription($THUMBARRAY[$i]);
			if ($description == "")
				$description = "&nbsp;";
			// Neue Tabellenzeile aller picsperrow Zeichen
			if (($i > 0) && ($i % $picsperrow == 0))
				$thumbs .= "</tr><tr>";
			$thumbs .= "<td class=\"gallerytd\" style=\"width:".floor(100 / $picsperrow)."%;\">"
			."<a href=\"".$DIR_GALLERY.$PICARRAY[$i]."\" target=\"_blank\" title=\"Vollbildanzeige: ".$PICARRAY[$i]."\">"
			."<img src=\"".$DIR_THUMBS.$THUMBARRAY[$i]."\" alt=\"".$THUMBARRAY[$i]."\" class=\"thumbnail\" />"
			."</a><br />"
			.$description
			."</td>";
		}
		while ($i % $picsperrow > 0) {
			$thumbs .= "<td class=\"gallerytd\">&nbsp;</td>";
			$i++;
		}
		$thumbs .= "</tr></table>";
		// Rückgabe der Thumbnails
		return $thumbs;
	}
	
	
// ------------------------------------------------------------------------------
// Aktuelles Bild anzeigen
// ------------------------------------------------------------------------------
	function getCurrentPic() {
		global $DIR_GALLERY;
		global $INDEX;
		global $MAX_IMG_HEIGHT;
		global $MAX_IMG_WIDTH;
		global $PICARRAY;
		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "&nbsp;";
		// Link zur Vollbildansicht öffnen
		$currentpic = "<a href=\"".$DIR_GALLERY.$PICARRAY[$INDEX-1]."\" target=\"_blank\" title=\"Vollbildanzeige: ".$PICARRAY[$INDEX-1]."\">";
		// Bilder für die Anzeige skalieren
		if (extension_loaded('gd')) {
			$size = getimagesize($DIR_GALLERY.$PICARRAY[$INDEX-1]);
			$w = $size[0];
			$h = $size[1];
			// Breite skalieren
			if ($w > $MAX_IMG_WIDTH) {
				$w=$MAX_IMG_WIDTH;
				$h=round(($MAX_IMG_WIDTH*$size[1])/$size[0]);
			}
			// Höhe skalieren
			if ($h > $MAX_IMG_HEIGHT){
				$h=$MAX_IMG_HEIGHT;
				$w=round(($MAX_IMG_HEIGHT*$size[0])/$size[1]);
			}
			$currentpic .= "<img src=\"".$DIR_GALLERY.$PICARRAY[$INDEX-1]."\" alt=\"Galeriebild &quot;".$PICARRAY[$INDEX-1]."&quot;\"  style=\"width:".$w."px;height:".$h."px;\"/>";
		}
		else
			$currentpic .= "<img src=\"".$DIR_GALLERY.$PICARRAY[$INDEX-1]."\" alt=\"Galeriebild &quot;".$PICARRAY[$INDEX-1]."&quot;\"  style=\"max-width:".$MAX_IMG_WIDTH."px;max-height:".$MAX_IMG_HEIGHT."px;\"/>";
			// Link zur Vollbildansicht schließen
			$currentpic .= "</a>";
		// Rückgabe des Bildes
		return $currentpic;
	}
	
	
// ------------------------------------------------------------------------------
// Beschreibung zum aktuellen Bild anzeigen
// ------------------------------------------------------------------------------
	function getCurrentDescription($picname) {
		global $DIR_GALLERY;
		global $INDEX;
		global $PICARRAY;
		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "&nbsp;";
		// Texte einlesen
		$alldescriptions = new Properties($DIR_GALLERY."texte.conf");
		return htmlentities($alldescriptions->get($picname));
	}


// ------------------------------------------------------------------------------
// Position in der Galerie anzeigen
// ------------------------------------------------------------------------------
	function getXoutofY() {
		global $INDEX;
		global $LAST;
		global $PICARRAY;
		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "&nbsp;";
		return "(Bild $INDEX von $LAST)";
	}
	
	
// ------------------------------------------------------------------------------
// Auslesen des übergebenen Galerieverzeichnisses, Rückgabe als Array
// ------------------------------------------------------------------------------
function getPicsAsArray($dir, $filetypes) {
	$picarray = array();
	$currentdir = opendir($dir);
	// Alle Dateien des übergebenen Verzeichnisses einlesen...
	while ($file = readdir($currentdir)){
		// ...überprüfen, ob die aktuelle Datei weder "." noch ".." ist und eine erlaubte Endung besitzt (Array "$filetypes")
		if (($file <> ".") && ($file <> "..") && (in_array(strtolower(substr(strrchr($file, "."), 1, strlen(strrchr($file, "."))-1)), $filetypes))) {
			// ...wenn alles paßt, ans Bilder-Array anhängen
			array_push($picarray, $file);
    }
	}
	sort($picarray);
	return $picarray;
}


// ------------------------------------------------------------------------------
// Prüfen, ob alle Thumbnails vorhanden sind; evtl. anlegen
// ------------------------------------------------------------------------------
function checkThumbs() {
	// Thumbnail-Funktionalität
	require_once("Thumbnail.php");
	$thumbnailfunction = new Thumbnail();

	global $DIR_GALLERY;
	global $DIR_THUMBS;
	global $PICARRAY;
	
	// Vorschauverzeichnis prüfen
	if (!file_exists($DIR_THUMBS))
		die ("FEHLER: Fehlendes Vorschauverzeichnis!");
	// alle Bilder überprüfen: Vorschau dazu vorhanden?
	foreach($PICARRAY as $pic) {
		// Vorschaubild anlegen, wenn nicht vorhanden
		if (!file_exists($DIR_THUMBS.$pic))
			$thumbnailfunction->createThumb($pic, $DIR_GALLERY, $DIR_THUMBS);
	}
}
?>