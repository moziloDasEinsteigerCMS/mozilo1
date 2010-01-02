<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class Gallery {

    // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
    # @ini_set("display_errors", 0);

    // ------------------------------------------------------------------------------    
    // Konstruktor
    // ------------------------------------------------------------------------------
    function Gallery() {
        global $URL_BASE;
        global $CMS_CONF;
        global $CAT_REQUEST;
        global $PAGE_REQUEST;

        $this->linkprefix = "index.php?cat=$CAT_REQUEST&amp;page=$PAGE_REQUEST&amp;";
        if($CMS_CONF->get("modrewrite") == "true") {
            $this->linkprefix = $URL_BASE.$CAT_REQUEST."/".$PAGE_REQUEST.".html?";
        }
    }
    
    function parseGalleryParameters($gallery,$index) {
        global $URL_BASE;
        global $CMS_CONF;
        global $specialchars;
        global $language;
        global $GALLERY_CONF;
        global $CAT_REQUEST;
        global $CHARSET;


        $this->embedded = $GALLERY_CONF->get("target");

        $cat_activ = "";
        if($CAT_REQUEST == basename(dirname($_SERVER['REQUEST_URI'])) and $this->embedded == "_self") {
            $cat_activ = "../";
        }

        if ($GALLERY_CONF->get("usethumbs") == "true")
            $this->usethumbs = true;
        else
            $this->usethumbs = false;

        // Übergebene Parameter überprüfen
        $this->gal_request        = $specialchars->replacespecialchars($gallery,false);
        $this->dir_gallery        = "./galerien/".$this->gal_request."/";
        $this->dir_thumbs         = $this->dir_gallery."vorschau/";
        $this->dir_thumbs_src     = $this->dir_gallery."vorschau/";
        $this->dir_gallery_src    = "./galerien/".$this->gal_request."/";
        if ($GALLERY_CONF->get("usedfgallery") == "true") {
            $this->dir_base = $URL_BASE;
            $this->gallery_xml        = $this->dir_gallery."gallery.xml";
            $this->flash              = "flash/";
        }
        if($CMS_CONF->get("modrewrite") == "true") {
            $this->dir_gallery_src    = $URL_BASE."galerien/".$this->gal_request."/";
            $this->dir_thumbs_src     = $this->dir_gallery_src."vorschau/";

            if ($GALLERY_CONF->get("usedfgallery") == "true") {
                $this->gallery_xml        = $this->dir_gallery_src."gallery.xml";
                $this->flash              = $URL_BASE."flash/";
            }

        }
        if (($this->gal_request == "") || (!file_exists($this->dir_gallery))) {
            die($language->getlanguagevalue1("message_gallerydir_error_1", $this->gal_request));
        }
        $this->gal_name           = $specialchars->rebuildSpecialChars($this->gal_request, true, true);

        if ($GALLERY_CONF->get("usedfgallery") == "true") {

            $this->flashxmltime = 0;
            $old_xml = array();
            if(file_exists("./galerien/gallery.xml")) {
                $this->flashxmltime = filemtime("./galerien/gallery.xml");
                $old_xml = file("./galerien/gallery.xml");
            }
            $xml_kopf = '<?xml version="1.0" encoding="UTF-8"?>
            <gallery>
                <config>
                    <title>'.$specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"), false, false).'</title>
                    <thumbnail_dir>.</thumbnail_dir>
                    <image_dir>.</image_dir>
                    <slideshow_interval>4</slideshow_interval>
                    <pause_slideshow>true</pause_slideshow>
                    <rss_scale_images>true</rss_scale_images>
                    <link_images>true</link_images>
                    <disable_printscreen>false</disable_printscreen>
                    <disable_titles>false</disable_titles>
                </config>
                <language>
                    <string id="please wait" value="'.html_entity_decode($language->getlanguagevalue0("dfgallery_wait"), ENT_QUOTES, $CHARSET).'" />
                    <string id="loading" value="'.html_entity_decode($language->getlanguagevalue0("dfgallery_loading"), ENT_QUOTES, $CHARSET).'" />
                    <string id="previous page" value="'.html_entity_decode($language->getlanguagevalue0("dfgallery_previous_page"), ENT_QUOTES, $CHARSET).'" />
                    <string id="page % of %" value="'.html_entity_decode($language->getlanguagevalue0("dfgallery_pages"), ENT_QUOTES, $CHARSET).'" />
                    <string id="next page" value="'.html_entity_decode($language->getlanguagevalue0("dfgallery_next_page"), ENT_QUOTES, $CHARSET).'" />
                </language>
                <albums>'."\n";
            $xml_fuss = '</albums></gallery>'."\n";
            $this->galleryarray = $this->getgalsasarray("./galerien/",$this->flashxmltime);
            $xml = NULL;
            $make_xml = false;
            foreach($this->galleryarray as $gallery => $isnew) {
                if($isnew == "true") {
                    $make_xml = true;#$specialchars->rebuildSpecialChars($gallery, true, true)
                    $alldescriptions = new Properties("./galerien/".$gallery."/texte.conf");
                    $this->picarray = $this->getpicsasarray("./galerien/".$gallery, array("jpg", "jpeg", "jpe", "gif", "png", "svg"));
                    $xml .= '<album title="'.$specialchars->rebuildSpecialChars($gallery, false, false).' Bilder ('.count($this->picarray).')" description="'.$specialchars->rebuildSpecialChars($gallery, false, false).'">'."\n";
                    foreach($this->picarray as $pic) {
                        $title = NULL;
                        if(isset($alldescriptions->properties[$pic])) $title = $alldescriptions->properties[$pic];
                        $xml .= '<image title="'.$title.'" date="'.@date ("F d Y", filemtime("./galerien/".$gallery."/".$pic)).'" link="'.$cat_activ.'galerien/'.rawurlencode($gallery).'/'.rawurlencode($pic).'" thumbnail="'.$cat_activ.'galerien/'.rawurlencode($gallery).'/vorschau/'.rawurlencode($pic).'" image="'.$cat_activ.'galerien/'.rawurlencode($gallery).'/'.rawurlencode($pic).'">Bildname: '.$specialchars->rebuildSpecialChars($pic, false, false).'</image>'."\n";
                    }
                    $xml .= '</album>'."\n";
                } else {
                    $find = false;
                    foreach($old_xml as $zeile) {
                        if(!$find and substr($zeile,0,(14 + strlen($gallery))) == '<album title="'.$specialchars->rebuildSpecialChars($gallery, true, true)) {
                            $xml .= trim($zeile)."\n";
                            $find = true;
                        }
                        if($find and substr($zeile,0,7) == '<image ') {
                            $xml .= trim($zeile)."\n";
                        } elseif($find and substr($zeile,0,8) == '</album>') {
                            $xml .= trim($zeile)."\n";
                            break;
                        }
                    }
                }
            }
            if($make_xml) {
                if($handle = @fopen("./galerien/gallery.xml","w")) {
                    fputs($handle, $xml_kopf.$xml.$xml_fuss);
                    fclose($handle);
                }
            }
        } else {
            $this->alldescriptions = new Properties($this->dir_gallery."texte.conf");

            // Galerieverzeichnis einlesen
            $this->picarray = $this->getpicsasarray($this->dir_gallery, array("jpg", "jpeg", "jpe", "gif", "png", "svg"));
            $allindexes = array();
            for ($i=1; $i<=count($this->picarray); $i++) {
                array_push($allindexes, $i);
            }
            // globaler Index
            if ((!isset($index)) || (!in_array($index, $allindexes)))
                $this->index = 1;
            else
                $this->index = $index;
 
            // Bestimmung der Positionen
            $this->first = 1;
            $this->last = count($allindexes);
            if (!in_array($this->index-1, $allindexes))
                $this->previous = $this->last;
            else
                $this->previous = $this->index-1;
            if (!in_array($this->index+1, $allindexes))
                $this->next = 1;
            else
                $this->next = $this->index+1;
                
            if ($this->usethumbs) {
                $this->checkthumbs();
                $this->thumbarray = $this->getPicsAsArray($this->dir_thumbs, array("jpg", "jpeg", "jpe", "gif", "png", "svg"));
            }
        }
    }

// ------------------------------------------------------------------------------
// Gallerie aufbauen und ausgeben
// ------------------------------------------------------------------------------
    function renderGallery($template,$gal_request) {
        global $specialchars;
        $index = NULL;
        if (isset($_GET["gal"]) and $specialchars->replaceSpecialChars($_GET["gal"],false) == $gal_request and isset($_GET["index"]))
            $index = $_GET["index"];
        $this->parseGalleryParameters($gal_request,$index);
        $this->html = "";
        if ($this->readGalleryTemplate($template) == false) {
            return false;
        }
        return $this->html;
    }
    
// ------------------------------------------------------------------------------
// html-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
    function readGalleryTemplate($template) {
        global $CMS_CONF;
        global $specialchars;
        global $language;
        global $CHARSET;
        global $GALLERY_CONF;

        if ($this->embedded == "_self") {
            $template = $this->extractEmbeddedTemplate($template);
            if ($template == false) {
                return false;
            }
        }
        
        if ($GALLERY_CONF->get("usedfgallery") == "false") {

            $this->html = preg_replace('/{CURRENTGALLERY}/', $language->getLanguageValue1("message_gallery_1", $this->gal_name), $template);
            if (count($this->picarray) == 0) {
                $this->html = preg_replace('/{NUMBERMENU}/', $language->getLanguageValue0("message_galleryempty_0"), $this->html);
            }
            if ($this->usethumbs) {
                $this->html = preg_replace('/{GALLERYMENU}/', "&nbsp;", $this->html);
                $this->html = preg_replace('/{NUMBERMENU}/', $this->getThumbnails(), $this->html);
                $this->html = preg_replace('/{CURRENTPIC}/', "&nbsp;", $this->html);
                $this->html = preg_replace('/{CURRENTDESCRIPTION}/', "&nbsp;", $this->html);
                $this->html = preg_replace('/{XOUTOFY}/', "&nbsp;", $this->html);
            }
            else {
                $this->html = preg_replace('/{GALLERYMENU}/', $this->getGalleryMenu(), $this->html);
                $this->html = preg_replace('/{NUMBERMENU}/', $this->getNumberMenu(), $this->html);
                $this->html = preg_replace('/{CURRENTPIC}/', $this->getCurrentPic(), $this->html);
                if (count($this->picarray) > 0) {
                    $this->html = preg_replace('/{CURRENTDESCRIPTION}/', $this->getCurrentDescription($this->picarray[$this->index-1]), $this->html);
                } else {
                    $this->html = preg_replace('/{CURRENTDESCRIPTION}/', "", $this->html);
                }
                $this->html = preg_replace('/{XOUTOFY}/', $this->getXoutofY(), $this->html);
                $this->html = preg_replace('/{CURRENT_INDEX}/', $this->index, $this->html);
                $this->html = preg_replace('/{PREVIOUS_INDEX}/', $this->previous, $this->html);
                $this->html = preg_replace('/{NEXT_INDEX}/', $this->next, $this->html);
            }
        } else {
            $df = '<div class="embeddedgallery"><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="100%">';
            $df .= '<param name="allowFullScreen" value="true" />';
            $df .= '<param name="movie" value="'.$this->flash.'dfgallery/gallery.swf" />';
            $df .= '<param name="quality" value="best" />';
            $df .= '<param name="scale" value="noScale" />';
            $df .= '<param name="FlashVars" value="xmlFile='.$this->dir_base.'galerien/gallery.xml" />';
            $df .= '<embed src="'.$this->flash.'dfgallery/gallery.swf" FlashVars="xmlFile='.$this->dir_base.'galerien/gallery.xml" quality="best" scale="noScale" width="100%" height="100%" allowFullScreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />';
            $df .= '</object></div>';
            if ($this->embedded == "_blank") {
                $this->html = preg_replace('/{CURRENTGALLERY}/', "&nbsp;", $template);
                $this->html = str_replace($this->extractEmbeddedTemplate($template), $df, $this->html);
            } else {
                $this->html = $df;
            }
        }
        return true;
    }
    
// ------------------------------------------------------------------------------
// Galeriemenü erzeugen
// ------------------------------------------------------------------------------
    function getGalleryMenu() {
        global $language;
        
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        
        $gallerymenu = "<ul class=\"gallerymenu\">";
        
        // Link "Erstes Bild"
        if ($this->index == $this->first)
            $linkclass = "gallerymenuactive";
        else
            $linkclass = "gallerymenu";
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->first."\" class=\"$linkclass\">".$language->getLanguageValue0("message_firstimage_0")."</a></li>";
        // Link "Voriges Bild"
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->previous."\" class=\"gallerymenu\">".$language->getLanguageValue0("message_previousimage_0")."</a></li>";
        // Link "Nächstes Bild"
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->next."\" class=\"gallerymenu\">".$language->getLanguageValue0("message_nextimage_0")."</a></li>";
        // Link "Letztes Bild"
        if ($this->index == $this->last)
            $linkclass = "gallerymenuactive";
        else
            $linkclass = "gallerymenu";
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->last."\" class=\"$linkclass\">".$language->getLanguageValue0("message_lastimage_0")."</a></li>";
        // Rückgabe des Menüs
        return $gallerymenu."</ul>";
    }
    
// ------------------------------------------------------------------------------
// Nummernmenü erzeugen
// ------------------------------------------------------------------------------
    function getNumberMenu() {

        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";

        $numbermenu = "<ul class=\"gallerynumbermenu\">";
        for ($i=$this->first; $i<=$this->last; $i++) {
            $cssclass = $this->index == $i ? "gallerynumbermenuactive" : "gallerynumbermenu";
            $numbermenu .= "<li class=\"gallerynumbermenu\">"
                ."<a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$i."\" class=\"".$cssclass."\">".$i."</a>"
                ."</li>";
        }
        // Rückgabe des Menüs
        $numbermenu .= "</ul>";
        return $numbermenu;
    }
    
// ------------------------------------------------------------------------------
// Nummernmenü erzeugen
// ------------------------------------------------------------------------------
    function getThumbnails() {
        global $GALLERY_CONF;
        global $specialchars;
        global $language;
        // Aus Config auslesen: Wieviele Bilder pro Tabellenzeile?
        $picsperrow = $GALLERY_CONF->get("gallerypicsperrow");
        if (($picsperrow == "") || ($picsperrow == 0))
            $picsperrow = 4;

        $thumbs = "<table class=\"gallerytable\" summary=\"gallery table\"><tr>";
        $i = 0;
        for ($i=0; $i<count($this->thumbarray); $i++) {
            // Bildbeschreibung holen
            $description = $this->getCurrentDescription($this->thumbarray[$i]);
            if ($description == "")
                $description = "&nbsp;";
            // Neue Tabellenzeile aller picsperrow Zeichen
            if (($i > 0) && ($i % $picsperrow == 0))
                $thumbs .= "</tr><tr>";
            $thumbs .= "<td class=\"gallerytd\" style=\"width:".floor(100 / $picsperrow)."%;\">"
            ."<a href=\"".$specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$i],true)."\" target=\"_blank\" title=\"".$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($this->picarray[$i],true,true))."\">"
            ."<img src=\"".$specialchars->replaceSpecialChars($this->dir_thumbs_src.$this->thumbarray[$i],true)."\" alt=\"".$specialchars->rebuildSpecialChars($this->thumbarray[$i],true,true)."\" class=\"thumbnail\" />"
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
        global $specialchars;
        global $language;
    
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        // Link zur Vollbildansicht öffnen
        $currentpic = "<a href=\"".$specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" target=\"_blank\" title=\"".$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\">";
        // Bilder für die Anzeige skalieren
        $size = getimagesize($this->dir_gallery.$this->picarray[$this->index-1]);
        $currentpic .= "<img width=\"$size[0]\" height=\"$size[1]\" src=\"".$specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" alt=\"".$language->getLanguageValue1("alttext_galleryimage_1", $specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\" />";
        // Link zur Vollbildansicht schliessen
        $currentpic .= "</a>";
        // Rückgabe des Bildes
        return $currentpic;
    }
    
// ------------------------------------------------------------------------------
// Beschreibung zum aktuellen Bild anzeigen
// ------------------------------------------------------------------------------
    function getCurrentDescription($picname) {
        global $specialchars;

        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        // Bildbeschreibung einlesen
        $description = $this->alldescriptions->get($picname);
        if(strlen($description) > 0) {
            return $specialchars->rebuildSpecialChars($description,false,true);
        } else {
            return "&nbsp;";
        }
    }


// ------------------------------------------------------------------------------
// Position in der Galerie anzeigen
// ------------------------------------------------------------------------------
    function getXoutofY() {
        global $language;
    
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        return $language->getLanguageValue2("message_gallery_xoutofy_2", $this->index, $this->last);
    }
    
// ------------------------------------------------------------------------------
// Auslesen des übergebenen Galerieverzeichnisses, Rückgabe als Array
// ------------------------------------------------------------------------------
    function getPicsAsArray($dir, $filetypes) {
        $picarray = array();
        $currentdir = opendir($dir);
        // Alle Dateien des übergebenen Verzeichnisses einlesen...
        while ($file = readdir($currentdir)){
            if(isValidDirOrFile($file) and (in_array(strtolower(substr(strrchr($file, "."), 1, strlen(strrchr($file, "."))-1)), $filetypes))) {
                // ...wenn alles passt, ans Bilder-Array anhängen
                $picarray[] = $file;
            }
        }
        closedir($currentdir);
        sort($picarray);
        return $picarray;
    }

// ------------------------------------------------------------------------------
// Alle Galerien, Rückgabe als Array
// ------------------------------------------------------------------------------
function getGalsAsArray($dir,$flashxmltime) {
    $currentdir = opendir($dir);
    $galarray = array();
    // Alle Dateien des übergebenen Verzeichnisses einlesen...
    while (false !== ($file = readdir($currentdir))) {
        if(isValidDirOrFile($file)) {
            $if_new = "false";
            if(is_dir($dir.$file) and filemtime($dir.$file."/texte.conf") > $flashxmltime) {
                $if_new = "true";
            }
            // ...wenn alles passt, ans Bilder-Array anhängen
            # ist erstes zeichen eine zahl
            if(ctype_digit(substr($file,0,1))) {
                $galarray_digit[$file] = $if_new;
            # ist erstes zeichen keine zahl
            } else {
                $galarray_text[$file] = $if_new;
            }
        }
    }
    closedir($currentdir);
    # die mit einer zahl anfangen rückwerts sortieren
    krsort($galarray_digit);
    # die nicht mit einer zahl anfangen normal sortieren
    ksort($galarray_text);
    # zuerst die mit einer zahl beginnen ins array schreiben
    if(isset($galarray_digit)) {
        foreach($galarray_digit as $dir => $if_new) {
            $galarray[$dir] = $if_new;
        }
    }
    # dann die nicht mit einer zahl beginnen ins array schreiben
    if(isset($galarray_text)) {
        foreach($galarray_text as $dir => $if_new) {
            $galarray[$dir] = $if_new;
        }
    }
    return $galarray;
}

// ------------------------------------------------------------------------------
// Prüfen, ob alle Thumbnails vorhanden sind; evtl. anlegen
// ------------------------------------------------------------------------------
    function checkThumbs() {
        global $language;
        global $GALLERY_CONF;
        
        // Vorschauverzeichnis prüfen
        if (!file_exists($this->dir_thumbs)) {
            die($language->getLanguageValue1("tooltip_link_category_error_1", $this->dir_thumbs));
        } else {
            // alle Bilder überprüfen: Vorschau dazu vorhanden?
            foreach($this->picarray as $pic) {
                // Vorschaubild anlegen, wenn nicht vorhanden
                if (!file_exists($this->dir_thumbs.$pic)) {
                    require_once("Image.php");
                    scaleImage($pic, $this->dir_gallery, $this->dir_thumbs,$GALLERY_CONF->get("maxthumbwidth"),$GALLERY_CONF->get("maxthumbheight"));
                }
            }
        }
    }
    
// ------------------------------------------------------------------------------
// Hilfsfunktion: Extrahiert das Embedded-Template aus dem Gesamt-Template
// ------------------------------------------------------------------------------
    function extractEmbeddedTemplate($template) {
        global $GALLERY_CONF;
        preg_match("/\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_START\}[\s|\t]*--\>(.*)\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_END\}[\s|\t]*--\>/Umsi", $template, $matches);
        if (sizeof($matches) > 1) {
            if ($GALLERY_CONF->get("usedfgallery") == "true") {
                return $matches[1];
            } else {
                return "<div class=\"embeddedgallery\">".$matches[1]."</div>";
            }
        }
        else {
            return false;
        }
    }
// ------------------------------------------------------------------------------
// Handelt es sich um ein valides Verzeichnis / eine valide Datei?
// ------------------------------------------------------------------------------
    function isValidDirOrFile($file) {
        # Alles was einen Punkt vor der Datei hat
        if(strpos($file,".") === 0) {
            return false;
        }
        # alle php Dateien
        if(substr($file,-4) == ".php") {
            return false;
        }
        # und der Rest
        if(in_array($file, array(
            "Thumbs.db", // Windows-spezifisch
            "__MACOSX", // Mac-spezifisch
            "settings" // Eclipse
            ))) {
            return false;
        }
        return true;
    }
}

$gallery = new Gallery();
?>