<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

class Gallery {

    // Initial: Fehlerausgabe unterdr�cken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
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

        $this->embedded = $GALLERY_CONF->get("target");

        if ($GALLERY_CONF->get("usethumbs") == "true")
            $this->usethumbs = true;
        else
            $this->usethumbs = false;

        // �bergebene Parameter �berpr�fen
        $this->gal_request        = $specialchars->replacespecialchars($gallery,false);
        $this->dir_gallery        = "./galerien/".$this->gal_request."/";
        $this->dir_thumbs         = $this->dir_gallery."vorschau/";
        $this->dir_thumbs_src     = $this->dir_gallery."vorschau/";
        $this->dir_gallery_src    = "./galerien/".$this->gal_request."/";
        if($CMS_CONF->get("modrewrite") == "true") {
            $this->dir_gallery_src    = $URL_BASE."galerien/".$this->gal_request."/";
            $this->dir_thumbs_src     = $this->dir_gallery_src."vorschau/";
        }
        if (($this->gal_request == "") || (!file_exists($this->dir_gallery))) {
            die($language->getlanguagevalue1("message_gallerydir_error_1", $this->gal_request));
        }
        $this->gal_name           = $specialchars->rebuildSpecialChars($this->gal_request, true, true);
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

        if ($this->embedded == "_self") {
            $template = $this->extractEmbeddedTemplate($template);
            if ($template == false) {
                return false;
            }
        }
        
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
        return true;
    }
    
// ------------------------------------------------------------------------------
// Galeriemen� erzeugen
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
        // Link "N�chstes Bild"
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->next."\" class=\"gallerymenu\">".$language->getLanguageValue0("message_nextimage_0")."</a></li>";
        // Link "Letztes Bild"
        if ($this->index == $this->last)
            $linkclass = "gallerymenuactive";
        else
            $linkclass = "gallerymenu";
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->last."\" class=\"$linkclass\">".$language->getLanguageValue0("message_lastimage_0")."</a></li>";
        // R�ckgabe des Men�s
        return $gallerymenu."</ul>";
    }
    
// ------------------------------------------------------------------------------
// Nummernmen� erzeugen
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
        // R�ckgabe des Men�s
        $numbermenu .= "</ul>";
        return $numbermenu;
    }
    
// ------------------------------------------------------------------------------
// Nummernmen� erzeugen
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
        // R�ckgabe der Thumbnails
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
        // Link zur Vollbildansicht �ffnen
        $currentpic = "<a href=\"".$specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" target=\"_blank\" title=\"".$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\">";
        // Bilder f�r die Anzeige skalieren
        $size = getimagesize($this->dir_gallery.$this->picarray[$this->index-1]);
        $currentpic .= "<img width=\"$size[0]\" height=\"$size[1]\" src=\"".$specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" alt=\"".$language->getLanguageValue1("alttext_galleryimage_1", $specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\" />";
        // Link zur Vollbildansicht schlie�en
        $currentpic .= "</a>";
        // R�ckgabe des Bildes
        return $currentpic;
    }
    
// ------------------------------------------------------------------------------
// Beschreibung zum aktuellen Bild anzeigen
// ------------------------------------------------------------------------------
    function getCurrentDescription($picname) {
        global $CHARSET;
    
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        // Bildbeschreibung einlesen
        $description = $this->alldescriptions->get($picname);
        if(strlen($description) > 0) {
            return htmlentities($description,ENT_COMPAT,$CHARSET);
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
// Auslesen des �bergebenen Galerieverzeichnisses, R�ckgabe als Array
// ------------------------------------------------------------------------------
    function getPicsAsArray($dir, $filetypes) {
        $picarray = array();
        $currentdir = opendir($dir);
        // Alle Dateien des �bergebenen Verzeichnisses einlesen...
        while ($file = readdir($currentdir)){
            // ...�berpr�fen, ob die aktuelle Datei weder "." noch ".." ist und eine erlaubte Endung besitzt (Array "$filetypes")
            if (($file <> ".") && ($file <> "..") && (in_array(strtolower(substr(strrchr($file, "."), 1, strlen(strrchr($file, "."))-1)), $filetypes))) {
                // ...wenn alles pa�t, ans Bilder-Array anh�ngen
                array_push($picarray, $file);
            }
        }
        closedir($currentdir);
        sort($picarray);
        return $picarray;
    }

// ------------------------------------------------------------------------------
// Pr�fen, ob alle Thumbnails vorhanden sind; evtl. anlegen
// ------------------------------------------------------------------------------
    function checkThumbs() {
        global $language;
        global $GALLERY_CONF;
        
        // Vorschauverzeichnis pr�fen
        if (!file_exists($this->dir_thumbs)) {
            die($language->getLanguageValue1("tooltip_link_category_error_1", $this->dir_thumbs));
        } else {
            // alle Bilder �berpr�fen: Vorschau dazu vorhanden?
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
        preg_match("/\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_START\}[\s|\t]*--\>(.*)\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_END\}[\s|\t]*--\>/Umsi", $template, $matches);
        if (sizeof($matches) > 1) {
            return "<div class=\"embeddedgallery\">".$matches[1]."</div>";
        }
        else {
            return false;
        }
    }
    
}

$gallery = new Gallery();
?>