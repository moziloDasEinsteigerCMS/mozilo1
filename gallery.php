<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/

require_once("Language.php");
require_once("Properties.php");
require_once("SpecialChars.php");

class Gallery {

    // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
    # @ini_set("display_errors", 0);

    var $language;
    var $mainconf;
    var $versionconf;
    var $specialchars;

    // ------------------------------------------------------------------------------    
    // Konstruktor
    // ------------------------------------------------------------------------------
    function Gallery() {
        global $LAYOUT_DIR;
        global $URL_BASE;
        global $CMS_CONF;

        $this->language       = new Language();
        $this->mainconf       = new Properties("conf/main.conf");
        $this->versionconf    = new Properties("conf/version.conf");
        $this->specialchars   = new SpecialChars();
        
        $this->embedded = $this->mainconf->get("embeddedgallery") == "true";

        // Vorschaubilder nach Benutzereinstellung und wenn GDlib installiert
        if (!extension_loaded("gd"))
            $this->mainconf->set("galleryusethumbs", "false");
        if ($this->mainconf->get("galleryusethumbs") == "true")
            $this->usethumbs = true;
        else
            $this->usethumbs = false;

        $this->max_img_width             = $this->mainconf->get("gallerymaxwidth");
        if ($this->max_img_width == "")
            $this->max_img_width = 500;

        $this->max_img_height         = $this->mainconf->get("gallerymaxheight");
        if ($this->max_img_height == "")
            $this->max_img_height = 350;

        $this->website_title            = $this->mainconf->get("websitetitle");
        if ($this->website_title == "")
            $this->website_title = "Titel der Website";

        $this->layout_dir         = $this->mainconf->get("cmslayout");
        $this->template_file      = "layouts/".$this->layout_dir."/gallerytemplate.html";
        if($CMS_CONF->get("modrewrite") == "false") {
            $URL_BASE = NULL;
        }
        $this->css_file           = $URL_BASE."layouts/".$this->layout_dir."/css/style.css";
        $this->favicon_file       = $URL_BASE."layouts/".$this->layout_dir."/favicon.ico";

        $this->linkprefix = "gallery.php?";
        
        if (!$this->embedded) {
            if (!isset($_GET['index'])) {
                $_GET['index'] = "1";
            }
            $this->parseGalleryParameters($_GET['gal'],$_GET['index']);
            echo $this->renderGallery();
        }
        else if (basename($_SERVER['PHP_SELF']) == "gallery.php") {
            die($this->language->getLanguageValue0("message_galleryembed_error_0"));
        }
    }
    
    function parseGalleryParameters($gallery,$index) {
        global $URL_BASE;
        global $CMS_CONF;
        // Übergebene Parameter überprüfen
        $this->gal_request        = $this->specialchars->replacespecialchars($gallery,false);
        $this->dir_gallery_src    = "./galerien/".$this->gal_request."/";
        if($CMS_CONF->get("modrewrite") == "true") {
            $this->dir_gallery_src    = $URL_BASE."/galerien/".$this->gal_request."/";
        }
        $this->dir_gallery        = "./galerien/".$this->gal_request."/";
        $this->dir_thumbs         = $this->dir_gallery."vorschau/";
        if (($this->gal_request == "") || (!file_exists($this->dir_gallery))) {
            die($this->language->getlanguagevalue1("message_gallerydir_error_1", $this->gal_request));
        }
        $this->gal_name           = $this->specialchars->rebuildSpecialChars($this->gal_request, true, true);

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
    function renderGallery() {
        $this->html = "";
        if ($this->readTemplate() == false) {
            return false;
        }
        return $this->html;
    }
    
// ------------------------------------------------------------------------------
// Gallerienamen herausgeben
// ------------------------------------------------------------------------------
    function getGalleryName() {
        return $this->gal_name;
    }
    
// ------------------------------------------------------------------------------
// Index herausgeben
// ------------------------------------------------------------------------------
    function getCurrentIndex() {
        return $this->index;
    }
    
// ------------------------------------------------------------------------------
// html-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
    function readTemplate() {
       
        // Template-Datei auslesen
        if (!$file = @fopen($this->template_file, "r")) {
            die($this->language->getLanguageValue1("message_template_error_1", $this->template_file));
        }
        $template = fread($file, filesize($this->template_file));
        fclose($file);
        
        if ($this->embedded) {
            $template = $this->extractEmbeddedTemplate($template);
            if ($template == false) {
                return false;
            }
        }

        // Platzhalter des Templates mit Inhalt füllen
        $this->html = preg_replace('/{CSS_FILE}/', $this->specialchars->replaceSpecialChars($this->css_file, true), $template);
        $this->html = preg_replace('/{FAVICON_FILE}/', $this->specialchars->replaceSpecialChars($this->favicon_file, true), $this->html);
        $this->html = preg_replace('/{LAYOUT_DIR}/', $this->specialchars->replaceSpecialChars($this->layout_dir, true), $this->html);
        $this->html = preg_replace('/{CMSINFO}/', $this->getCmsInfo(), $this->html);
        $this->html = preg_replace('/{WEBSITE_TITLE}/', $this->getwebsitetitle($this->website_title, $this->language->getlanguagevalue0("message_galleries_0"), $this->gal_name), $this->html);
        $this->html = preg_replace('/{WEBSITE_KEYWORDS}/', $this->mainconf->get("websitekeywords"), $this->html);
        $this->html = preg_replace('/{WEBSITE_DESCRIPTION}/', $this->mainconf->get("websitedescription"), $this->html);
        
        $this->html = preg_replace('/{CURRENTGALLERY}/', $this->language->getLanguageValue1("message_gallery_1", $this->gal_name), $this->html);
        if (count($this->picarray) == 0) {
            $this->html = preg_replace('/{NUMBERMENU}/', $this->language->getLanguageValue0("message_galleryempty_0"), $this->html);
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
// Galeriemenü erzeugen
// ------------------------------------------------------------------------------
    function getGalleryMenu() {
        
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        
        $gallerymenu = "<ul class=\"gallerymenu\">";
        
        // Link "Erstes Bild"
        if ($this->index == $this->first)
            $linkclass = "gallerymenuactive";
        else
            $linkclass = "gallerymenu";
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->first."\" class=\"$linkclass\">".$this->language->getLanguageValue0("message_firstimage_0")."</a></li>";
        // Link "Voriges Bild"
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->previous."\" class=\"gallerymenu\">".$this->language->getLanguageValue0("message_previousimage_0")."</a></li>";
        // Link "Nächstes Bild"
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->next."\" class=\"gallerymenu\">".$this->language->getLanguageValue0("message_nextimage_0")."</a></li>";
        // Link "Letztes Bild"
        if ($this->index == $this->last)
            $linkclass = "gallerymenuactive";
        else
            $linkclass = "gallerymenu";
        $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$this->linkprefix."gal=".$this->gal_request."&amp;index=".$this->last."\" class=\"$linkclass\">".$this->language->getLanguageValue0("message_lastimage_0")."</a></li>";
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
        // Aus Config auslesen: Wieviele Bilder pro Tabellenzeile?
        $picsperrow = $this->mainconf->get("gallerypicsperrow");
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
            ."<a href=\"".$this->specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$i],true)."\" target=\"_blank\" title=\"".$this->language->getLanguageValue1("tooltip_gallery_fullscreen_1", $this->specialchars->rebuildSpecialChars($this->picarray[$i],true,true))."\">"
            ."<img src=\"".$this->specialchars->replaceSpecialChars($this->dir_thumbs.$this->thumbarray[$i],true)."\" alt=\"".$this->specialchars->rebuildSpecialChars($this->thumbarray[$i],true,true)."\" class=\"thumbnail\" />"
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
    
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        // Link zur Vollbildansicht öffnen
        $currentpic = "<a href=\"".$this->specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" target=\"_blank\" title=\"".$this->language->getLanguageValue1("tooltip_gallery_fullscreen_1", $this->specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\">";
        // Bilder für die Anzeige skalieren
        if (extension_loaded('gd')) {
            $size = getimagesize($this->dir_gallery.$this->picarray[$this->index-1]);
            $w = $size[0];
            $h = $size[1];
            // Breite skalieren
            if ($w > $this->max_img_width) {
                $w=$this->max_img_width;
                $h=round(($this->max_img_width*$size[1])/$size[0]);
            }
            // Höhe skalieren
            if ($h > $this->max_img_height){
                $h=$this->max_img_height;
                $w=round(($this->max_img_height*$size[0])/$size[1]);
            }
            $currentpic .= "<img src=\"".$this->specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" alt=\"".$this->language->getLanguageValue1("alttext_galleryimage_1", $this->specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\"  style=\"width:".$w."px;height:".$h."px;\" />";
        }
        else
            $currentpic .= "<img src=\"".$this->specialchars->replaceSpecialChars($this->dir_gallery_src.$this->picarray[$this->index-1],true)."\" alt=\"".$this->language->getLanguageValue1("alttext_galleryimage_1", $this->specialchars->rebuildSpecialChars($this->picarray[$this->index-1],true,true))."\"  style=\"max-width:".$this->max_img_width."px;max-height:".$this->max_img_height."px;\" />";
            // Link zur Vollbildansicht schließen
            $currentpic .= "</a>";
        // Rückgabe des Bildes
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
        // Texte einlesen
        $alldescriptions = new Properties($this->dir_gallery."texte.conf");
        $description = $alldescriptions->get($picname);
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
    
        // Keine Bilder im Galerieverzeichnis?
        if (count($this->picarray) == 0)
            return "&nbsp;";
        return $this->language->getLanguageValue2("message_gallery_xoutofy_2", $this->index, $this->last);
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
    closedir($currentdir);
    sort($picarray);
    return $picarray;
}


// ------------------------------------------------------------------------------
// Prüfen, ob alle Thumbnails vorhanden sind; evtl. anlegen
// ------------------------------------------------------------------------------
function checkThumbs() {
    // Thumbnail-Funktionalität
    require_once("Image.php");
#    $thumbnailfunction = new Thumbnail();
    
    // Vorschauverzeichnis prüfen
    if (!file_exists($this->dir_thumbs))
        die($language->getLanguageValue1("tooltip_link_category_error_1", $this->dir_thumbs));
    // alle Bilder überprüfen: Vorschau dazu vorhanden?
    foreach($this->picarray as $pic) {
        // Vorschaubild anlegen, wenn nicht vorhanden
        if (!file_exists($this->dir_thumbs.$pic))
            scaleImage($pic, $this->dir_gallery, $this->dir_thumbs);
#            $thumbnailfunction->createThumb($pic, $this->dir_gallery, $this->dir_thumbs);
   }
}


// ------------------------------------------------------------------------------
// Rückgabe des Website-Titels
// ------------------------------------------------------------------------------
    function getWebsiteTitle($websitetitle, $cattitle, $pagetitle) {

        $title = $this->mainconf->get("titlebarformat");
        $sep = $this->mainconf->get("titlebarseparator");
        
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
        return "<a href=\"http://cms.mozilo.de/\" target=\"_blank\" id=\"cmsinfolink\"".$this->getTitleAttribute($this->language->getLanguageValue1("tooltip_link_extern_1", "http://cms.mozilo.de")).">moziloCMS ".$this->versionconf->get("cmsversion")."</a>";
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: "title"-Attribut zusammenbauen (oder nicht, wenn nicht konfiguriert)
// ------------------------------------------------------------------------------
    function getTitleAttribute($value) {
        if ($this->mainconf->get("showsyntaxtooltips") == "true") {
            return " title=\"".$value."\"";
        }
        return "";
    }

// ------------------------------------------------------------------------------
// Hilfsfunktion: "title"-Attribut zusammenbauen (oder nicht, wenn nicht konfiguriert)
// ------------------------------------------------------------------------------
    function setLinkPrefix($prefix) {
        $this->linkprefix = $prefix;
    }
    
    
// ------------------------------------------------------------------------------
// Hilfsfunktion: Extrahiert das Embedded-Template aus dem Gesamt-Template
// ------------------------------------------------------------------------------
    function extractEmbeddedTemplate($template) {
        //preg_match("/\<!--\s*\{EMBEDDED_TEMPLATE_START\}\s*--\>(.*)\<!--\s*\{EMBEDDED_TEMPLATE_END\}\s*--\>/Umsi", $template, $matches);
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