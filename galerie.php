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

$mainconf = new Properties("main.conf");
$MAX_IMG_WIDTH = $mainconf->get("gallerymaxwidth");
if ($MAX_IMG_WIDTH == "")
	$MAX_IMG_WIDTH = 500;
$MAX_IMG_HEIGHT = $mainconf->get("gallerymaxheight");
if ($MAX_IMG_HEIGHT == "")
	$MAX_IMG_HEIGHT = 350;
$HTML = "";

// Zuerst: Übergebene Parameter überprüfen
$CAT_REQUEST = $_GET['cat'];
$DIR_GALLERY = "inhalt/".$CAT_REQUEST."/galerie/";
if (($CAT_REQUEST == "") || (!file_exists($DIR_GALLERY)))
	die ("FEHLER: Keine g&uuml;ltige Kategorie angegeben oder fehlendes Galerieverzeichnis!");
// Dann: Galerie aufbauen
$HTML = createGallery(getPicsAsArray($DIR_GALLERY, array("jpg", "jpeg", "jpe", "gif", "png")));
// Zum Schluß: Ausgabe des fertigen HTML-Dokuments
echo $HTML;




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
// Aufbau der Galerie aus dem übergebenen Verzeichnis
// ------------------------------------------------------------------------------
function createGallery($mypics) {
	global $DIR_GALLERY;
	global $CAT_REQUEST;
	global $MAX_IMG_HEIGHT;
	global $MAX_IMG_WIDTH;
	global $mainconf;
	$alldescriptions = new Properties($DIR_GALLERY."texte.conf");
	$gallery = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"><html><head><style type=\"text/css\"> @import \"".$mainconf->get("cssfile")."\"; </style><title>".$mainconf->get("websitetitle")."</title><link rel=\"SHORTCUT ICON\" href=\"".$mainconf->get("faviconfile")."\" /></head><body>";
	$gallery .= "<div class=\"main\" style=\"text-align:center;\"><div class=\"fullheight\"></div><div class=\"header\"></div><div class=\"catcontent\" style=\"width:99%;float:left;\">";
	// Überprüfen, ob Galerie leer ist
	if (count($mypics) == 0) {
		$gallery .= "<div class=\"pagecontent\" style=\"text-align:center;\">Diese Galerie enth&auml;lt keine Bilder.</div>";
	}
	else {
		// Überprüfung der Parameter
		$allindexes = array();
		for($i=1;$i<=count($mypics);$i++) 
			array_push($allindexes, $i);
		if ((!isset($_GET['index'])) || (!in_array($_GET['index'], $allindexes)))
			$index = 1;
		else
			$index = $_GET['index'];
		// Beschreibungstext auslesen
		$first = 1;
		$last = count($allindexes);
		if (!in_array($index-1, $allindexes))
			$before = $last;
		else
			$before = $index-1;
		if (!in_array($index+1, $allindexes))
			$next = 1;
		else
			$next = $index+1;
		$gallery .= "<div class=\"detailmenu\" style=\"text-align:center;\">";
		// Link "Erstes Bild"
		if ($index == $first)
			$linkclass = "detailmenuactive";
		else
			$linkclass = "detailmenu";
		$gallery .= "<a href=\"galerie.php?cat=$CAT_REQUEST&amp;index=$first\" class=\"$linkclass\">Erstes Bild</a> ";
		// Link "Voriges Bild"
		$gallery .= "<a href=\"galerie.php?cat=$CAT_REQUEST&amp;index=$before\" class=\"detailmenu\">Voriges Bild</a> ";
		// Link "Nächstes Bild"
		$gallery .= "<a href=\"galerie.php?cat=$CAT_REQUEST&amp;index=$next\" class=\"detailmenu\">Nächstes Bild</a> ";
		// Link "Letztes Bild"
		if ($index == $last)
			$linkclass = "detailmenuactive";
		else
			$linkclass = "detailmenu";
		$gallery .= "<a href=\"galerie.php?cat=$CAT_REQUEST&amp;index=$last\" class=\"$linkclass\">Letztes Bild</a>";
		$gallery .= "</div>";
		
		$gallery .= "<div class=\"detailmenu\" style=\"text-align:center;width:".$MAX_IMG_WIDTH."px;float:none;margin:0px auto;line-height:16px;\">".getPicNumberLinks($first, $index, $last, $CAT_REQUEST)."</div>";
		
		$gallery .= "<div class=\"pagecontent\" style=\"text-align:center;\"><a href=\"".$DIR_GALLERY.$mypics[$index-1]."\" target=\"_blank\" title=\"Vollbildanzeige: ".$mypics[$index-1]."\">";
		// Bilder für die Anzeige verkleinern
		if (extension_loaded('gd')) {
			$size = getimagesize($DIR_GALLERY.$mypics[$index-1]);
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
			$gallery .= "<img src=\"".$DIR_GALLERY.$mypics[$index-1]."\" alt=\"Galeriebild &quot;".$mypics[$index-1]."&quot;\"  style=\"width:".$w."px;height:".$h."px;\"/>";
		}
		else
			$gallery .= "<img src=\"".$DIR_GALLERY.$mypics[$index-1]."\" alt=\"Galeriebild &quot;".$mypics[$index-1]."&quot;\"  style=\"width:".$MAX_IMG_WIDTH."px;width:inherit;height:inherit;max-width:".$MAX_IMG_WIDTH."px;max-height:".$MAX_IMG_HEIGHT."px;\"/>";
			$gallery .= "</a><br /><br />".$alldescriptions->get($mypics[$index-1])."<br /><br />(Bild $index von $last)</div>";
	}
	$gallery .= "</div>";
	$gallery .= "<br style=\"clear:both;\"></div>";
	$gallery .= "</body>";
	return $gallery;
}


// ------------------------------------------------------------------------------
// Anzeige aller Bilder als Direktlinks; Kürzung der Anzeige, wenn zuviele
// ------------------------------------------------------------------------------
function getPicNumberLinks($first, $current, $last, $cat) {
	$piclinks = "";
	for ($i=$first; $i<=$last; $i++) {
		if ($current == $i)
			$piclinks .= "<em class=\"bold\">".$i."</em> | ";
		else
			$piclinks .= "<a href=\"galerie.php?cat=".$cat."&amp;index=".$i."\">".$i."</a> | ";
	}
	return substr($piclinks, 0, strlen($piclinks)-2);
}

?>