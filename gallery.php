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
		global $specialchars;
		global $TEMPLATE_FILE;
		global $USE_CMS_SYNTAX;
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
    $HTML = preg_replace('/{CURRENTCATEGORY}/', $specialchars->rebuildSpecialChars($CURRENTCATEGORY), $HTML);
    $HTML = preg_replace('/{GALLERYMENU}/', getGalleryMenu(), $HTML);
    $HTML = preg_replace('/{NUMBERMENU}/', getNumberMenu(), $HTML);
    $HTML = preg_replace('/{CURRENTPIC}/', getCurrentPic(), $HTML);
    $HTML = preg_replace('/{CURRENTDESCRIPTION}/', getCurrentDescription(), $HTML);
    $HTML = preg_replace('/{XOUTOFY}/', getXoutofY(), $HTML);
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
			$currentpic .= "<img src=\"".$DIR_GALLERY.$PICARRAY[$INDEX-1]."\" alt=\"Galeriebild &quot;".$PICARRAY[$INDEX-1]."&quot;\"  style=\"width:".$MAX_IMG_WIDTH."px;width:inherit;height:inherit;max-width:".$MAX_IMG_WIDTH."px;max-height:".$MAX_IMG_HEIGHT."px;\"/>";
			// Link zur Vollbildansicht schließen
			$currentpic .= "</a>";
		// Rückgabe des Bildes
		return $currentpic;
	}
	
	
// ------------------------------------------------------------------------------
// Beschreibung zum aktuellen Bild anzeigen
// ------------------------------------------------------------------------------
	function getCurrentDescription() {
		global $DIR_GALLERY;
		global $INDEX;
		global $PICARRAY;
		// Keine Bilder im Galerieverzeichnis?
		if (count($PICARRAY) == 0)
			return "Diese Galerie enth&auml;lt keine Bilder.";
		// Texte einlesen
		$alldescriptions = new Properties($DIR_GALLERY."texte.conf");
		return htmlentities($alldescriptions->get($PICARRAY[$INDEX-1]));
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


?>