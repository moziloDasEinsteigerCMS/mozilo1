<?php

/***************************************************************
* 
* Eingebettete Galerie für moziloCMS.
* 
***************************************************************/
class Galerie extends Plugin {


	/***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/
    function getContent($value) {
        $values = explode(",", $value);

        global $URL_BASE;
        global $CMS_CONF;
        global $specialchars;
        global $language;
        global $GALLERY_CONF;
        global $CAT_REQUEST;
        global $CHARSET;
        global $PAGE_REQUEST;
        global $LAYOUT_DIR;
        global $BASE_DIR;
        global $GALLERIES_DIR_NAME;
        global $PREVIEW_DIR_NAME;

        // ------------------------------------------------------------------------------
        // Galeriemenü erzeugen
        // ------------------------------------------------------------------------------
        if(!function_exists('getGalleryMenu')) {
	        function getGalleryMenu($picarray,$linkprefix,$gal_request,$index,$first,$previous,$next,$last) {
	            global $language;
	        
	            // Keine Bilder im Galerieverzeichnis?
	            if (count($picarray) == 0)
	                return "&nbsp;";
	
	            $gallerymenu = "<ul class=\"gallerymenu\">";
	        
	            // Link "Erstes Bild"
	            if ($index == $first)
	                $linkclass = "gallerymenuactive";
	            else
	                $linkclass = "gallerymenu";
	            $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$linkprefix."gal=".$gal_request."&amp;index=".$first."\" class=\"$linkclass\">".$language->getLanguageValue0("message_firstimage_0")."</a></li>";
	            // Link "Voriges Bild"
	            $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$linkprefix."gal=".$gal_request."&amp;index=".$previous."\" class=\"gallerymenu\">".$language->getLanguageValue0("message_previousimage_0")."</a></li>";
	            // Link "Nächstes Bild"
	            $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$linkprefix."gal=".$gal_request."&amp;index=".$next."\" class=\"gallerymenu\">".$language->getLanguageValue0("message_nextimage_0")."</a></li>";
	            // Link "Letztes Bild"
	            if ($index == $last)
	                $linkclass = "gallerymenuactive";
	            else
	                $linkclass = "gallerymenu";
	            $gallerymenu .= "<li class=\"gallerymenu\"><a href=\"".$linkprefix."gal=".$gal_request."&amp;index=".$last."\" class=\"$linkclass\">".$language->getLanguageValue0("message_lastimage_0")."</a></li>";
	            // Rückgabe des Menüs
	            return $gallerymenu."</ul>";
	        }

		    // ------------------------------------------------------------------------------
	        // Nummernmenü erzeugen
	        // ------------------------------------------------------------------------------
	        function getNumberMenu($picarray,$linkprefix,$index,$gal_request,$first,$last) {
	        
	            // Keine Bilder im Galerieverzeichnis?
	            if (count($picarray) == 0)
	                return "&nbsp;";
	        
	            $numbermenu = "<ul class=\"gallerynumbermenu\">";
	            for ($i=$first; $i<=$last; $i++) {
	                $cssclass = $index == $i ? "gallerynumbermenuactive" : "gallerynumbermenu";
	                $numbermenu .= "<li class=\"gallerynumbermenu\">"
	                    ."<a href=\"".$linkprefix."gal=".$gal_request."&amp;index=".$i."\" class=\"".$cssclass."\">".$i."</a>"
	            ."</li>";
	            }
	            // Rückgabe des Menüs
	            $numbermenu .= "</ul>";
	            return $numbermenu;
	        }
	    
	        // ------------------------------------------------------------------------------
	        // Nummernmenü erzeugen
	        // ------------------------------------------------------------------------------
	        function getThumbnails($picarray,$dir_thumbs_src,$dir_thumbs,$dir_gallery_src,$alldescriptions) {
	            global $GALLERY_CONF;
	            global $specialchars;
	            global $language;
	            // Aus Config auslesen: Wieviele Bilder pro Tabellenzeile?
	            $picsperrow = $GALLERY_CONF->get("gallerypicsperrow");
	                if (($picsperrow == "") || ($picsperrow == 0))
	                    $picsperrow = 4;
	            
	            $thumbs = "<table class=\"gallerytable\" summary=\"gallery table\"><tr>";
	            $i = 0;
	            for ($i=0; $i<count($picarray); $i++) {
	                // Bildbeschreibung holen
	                $description = getCurrentDescription($picarray[$i],$picarray,$alldescriptions);
	                if ($description == "")
	                    $description = "&nbsp;";
	                // Neue Tabellenzeile aller picsperrow Zeichen
	                if (($i > 0) && ($i % $picsperrow == 0))
	                    $thumbs .= "</tr><tr>";
	                $thumbs .= "<td class=\"gallerytd\" style=\"width:".floor(100 / $picsperrow)."%;\">";

                    if (file_exists($specialchars->replaceSpecialChars($dir_thumbs.$picarray[$i],false))) {
                        $thumbs .= "<a href=\"".$specialchars->replaceSpecialChars($dir_gallery_src.$picarray[$i],true)."\" target=\"_blank\" title=\"".$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($picarray[$i],true,true))."\"><img src=\"".$specialchars->replaceSpecialChars($dir_thumbs_src.$picarray[$i],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[$i],true,true)."\" class=\"thumbnail\" /></a><br />";
                    } else {
                         $thumbs .= '<div style="text-align:center;"><a style="color:red;" href="'.$specialchars->replaceSpecialChars($dir_gallery_src.$picarray[$i],true).'" target="_blank" title="'.$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($picarray[$i],true,true)).'"><b>'.$language->getLanguageValue0('message_gallery_no_preview').'</b></a></div>';
                    }
	                $thumbs .= $description
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
	        function getCurrentPic($picarray,$dir_gallery_src,$dir_gallery,$index) {
	            global $specialchars;
	            global $language;
	        
	            // Keine Bilder im Galerieverzeichnis?
	            if (count($picarray) == 0)
	                return "&nbsp;";
	            // Link zur Vollbildansicht öffnen
	            $currentpic = "<a href=\"".$specialchars->replaceSpecialChars($dir_gallery_src.$picarray[$index-1],true)."\" target=\"_blank\" title=\"".$language->getLanguageValue1("tooltip_gallery_fullscreen_1", $specialchars->rebuildSpecialChars($picarray[$index-1],true,true))."\">";
	            // Bilder für die Anzeige skalieren
	            $size = getimagesize($dir_gallery.$picarray[$index-1]);
	            $currentpic .= "<img width=\"$size[0]\" height=\"$size[1]\" src=\"".$specialchars->replaceSpecialChars($dir_gallery_src.$picarray[$index-1],true)."\" alt=\"".$language->getLanguageValue1("alttext_galleryimage_1", $specialchars->rebuildSpecialChars($picarray[$index-1],true,true))."\" />";
	            // Link zur Vollbildansicht schliessen
	            $currentpic .= "</a>";
	            // Rückgabe des Bildes
	            return $currentpic;
	        }
	    
	        // ------------------------------------------------------------------------------
	        // Beschreibung zum aktuellen Bild anzeigen
	        // ------------------------------------------------------------------------------
	        function getCurrentDescription($picname,$picarray,$alldescriptions) {
	            global $specialchars;
	            
	            // Keine Bilder im Galerieverzeichnis?
	            if (count($picarray) == 0)
	            return "&nbsp;";
	            // Bildbeschreibung einlesen
	            $description = $alldescriptions->get($picname);
	            if(strlen($description) > 0) {
	                    return $specialchars->rebuildSpecialChars($description,false,true);
	            } else {
	                return "&nbsp;";
	            }
	        }
	
	
	        // ------------------------------------------------------------------------------
	        // Position in der Galerie anzeigen
	        // ------------------------------------------------------------------------------
	        function getXoutofY($picarray,$index,$last) {
	            global $language;
	        
	            // Keine Bilder im Galerieverzeichnis?
	            if (count($picarray) == 0)
	            return "&nbsp;";
	            return $language->getLanguageValue2("message_gallery_xoutofy_2", $index, $last);
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
	        // Hilfsfunktion: Extrahiert das Embedded-Template aus dem Gesamt-Template
	        // ------------------------------------------------------------------------------
	/*
	        function extractEmbeddedTemplate($template) {
	            global $GALLERY_CONF;
	            preg_match("/\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_START\}[\s|\t]*--\>(.*)\<!--[\s|\t]*\{EMBEDDED_TEMPLATE_END\}[\s|\t]*--\>/Umsi", $template, $matches);
	            if (sizeof($matches) > 1) {
	                return "<div class=\"embeddedgallery\">".$matches[1]."</div>";
	            }
	            else {
	                return false;
	            }
	        }*/
	        // ------------------------------------------------------------------------------
	        // Hilfsfunktion: "title"-Attribut zusammenbauen (oder nicht, wenn nicht konfiguriert)
	        // ------------------------------------------------------------------------------
	/*
	        function getTitleAttribute($value) {
	            global $CMS_CONF;
	            if ($CMS_CONF->get("showsyntaxtooltips") == "true") {
	                return " title=\"".$value."\"";
	            }
	            return "";
	        }*/
	        // ------------------------------------------------------------------------------
	        // Hilfsfunktion: Deadlink erstellen
	        // ------------------------------------------------------------------------------
	        function createDeadlink($content, $title) {
	            return "<span class=\"deadlink\"".getTitleAttribute($title).">$content</span>";
	        }
        }

        $embedded = $GALLERY_CONF->get("target");

        $linkprefix = "index.php?cat=$CAT_REQUEST&amp;page=$PAGE_REQUEST&amp;";
        if ($embedded == "_blank" and getRequestParam('gal', false)) {
            $linkprefix = "index.php?plugin=Galerie&amp;";
        }
        if($CMS_CONF->get("modrewrite") == "true") {
            $linkprefix = $URL_BASE.$CAT_REQUEST."/".$PAGE_REQUEST.".html?";
            if ($embedded == "_blank" and getRequestParam('gal', false)) {
                $linkprefix = "index.php.html?plugin=Galerie&amp;";
            }
        }

        $index = NULL;
        if (getRequestParam('index', false))
            $index = getRequestParam('index', false);


        $cat_activ = "";
        if($CAT_REQUEST == basename(dirname($_SERVER['REQUEST_URI'])) and $embedded == "_self") {
            $cat_activ = "../";
        }

        if ($GALLERY_CONF->get("usethumbs") == "true")
            $usethumbs = true;
        else
            $usethumbs = false;

        // Übergebene Parameter überprüfen
        $gal_request = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($values[0]),false);
        if (getRequestParam("gal", true))
            $gal_request = $specialchars->replacespecialchars(getRequestParam("gal", true),false);
        $dir_gallery        = $GALLERIES_DIR_NAME."/".$gal_request."/";
        $dir_thumbs         = $dir_gallery.$PREVIEW_DIR_NAME."/";
        $dir_thumbs_src     = $dir_gallery.$PREVIEW_DIR_NAME."/";
        $dir_gallery_src    = $GALLERIES_DIR_NAME."/".$gal_request."/";

        if($CMS_CONF->get("modrewrite") == "true") {
            $dir_gallery_src    = $URL_BASE.$GALLERIES_DIR_NAME."/".$gal_request."/";
            $dir_thumbs_src     = $dir_gallery_src.$PREVIEW_DIR_NAME."/";
            $dir_gallery        = $BASE_DIR.$GALLERIES_DIR_NAME."/".$gal_request."/";
            $dir_thumbs         = $dir_gallery.$PREVIEW_DIR_NAME."/";
        }

        # keine Galerie angegeben oder Galerie gibts nicht
        if (($gal_request == "") || (!file_exists($dir_gallery))) {
            if($gal_request == "") {
                return createDeadlink($language->getLanguageValue0("message_gallerydir_error_0"),$language->getLanguageValue0("message_gallerydir_error_0"));
            } else {
                return createDeadlink($specialchars->rebuildSpecialChars($gal_request, false, true), $language->getLanguageValue1("message_gallerydir_error_1", $specialchars->rebuildSpecialChars($gal_request, false, true)));
            }
        }

        # Galerie erzeugen
        if (($embedded == "_self") or (getRequestParam('gal', false))) {

            $alldescriptions = new Properties($dir_gallery."texte.conf");

            // Galerieverzeichnis einlesen
            $picarray = getPicsAsArray($dir_gallery, array("jpg", "jpeg", "jpe", "gif", "png", "svg"));
            $allindexes = array();
            for ($i=1; $i<=count($picarray); $i++) {
                array_push($allindexes, $i);
            }
            // globaler Index
            if ((!isset($index)) || (!in_array($index, $allindexes)))
                $index = 1;
            else
                $index = $index;
 
            // Bestimmung der Positionen
            $first = 1;
            $last = count($allindexes);
            if (!in_array($index-1, $allindexes))
                $previous = $last;
            else
                $previous = $index-1;
            if (!in_array($index+1, $allindexes))
                $next = 1;
            else
                $next = $index+1;
            $template = NULL;
            if($this->settings->get("gallerytemplate")) {
                if ($embedded == "_self") {
                    $template = '<div class="embeddedgallery">'.$this->settings->get("gallerytemplate").'</div>';
                } else {
                    $template = $this->settings->get("gallerytemplate");
                    if(strrpos("tmp".$value,'{NUMBERMENU}') > 0) {
                        $template = $value;
                    }
                }
            } else { 
                $template = "{GALLERYMENU}{NUMBERMENU}\n{CURRENTPIC}\n{CURRENTDESCRIPTION}";
                if(strrpos("tmp".$value,'{NUMBERMENU}') > 0) {
                    $template = $value;
                }
            }
            $html = $template;

            if (count($picarray) == 0) {
                $html = preg_replace('/{NUMBERMENU}/', $language->getLanguageValue0("message_galleryempty_0"), $html);
            }
            # Titel der Galerie
            $html = preg_replace('/{CURRENTGALLERY}/', $specialchars->rebuildSpecialChars($gal_request,false,true), $html);
            if ($usethumbs) {
                $html = preg_replace('/{GALLERYMENU}/', "&nbsp;", $html);
                $html = preg_replace('/{NUMBERMENU}/', getThumbnails($picarray,$dir_thumbs_src,$dir_thumbs,$dir_gallery_src,$alldescriptions), $html);
                $html = preg_replace('/{CURRENTPIC}/', "&nbsp;", $html);
                $html = preg_replace('/{CURRENTDESCRIPTION}/', "&nbsp;", $html);
                $html = preg_replace('/{XOUTOFY}/', "&nbsp;", $html);
            } else {
                $html = preg_replace('/{GALLERYMENU}/', getGalleryMenu($picarray,$linkprefix,$gal_request,$index,$first,$previous,$next,$last), $html);
                $html = preg_replace('/{NUMBERMENU}/', getNumberMenu($picarray,$linkprefix,$index,$gal_request,$first,$last), $html);
                $html = preg_replace('/{CURRENTPIC}/', getCurrentPic($picarray,$dir_gallery_src,$dir_gallery,$index), $html);
                if (count($picarray) > 0) {
                    $html = preg_replace('/{CURRENTDESCRIPTION}/', getCurrentDescription($picarray[$index-1],$picarray,$alldescriptions), $html);
                } else {
                    $html = preg_replace('/{CURRENTDESCRIPTION}/', "", $html);
                }
                $html = preg_replace('/{XOUTOFY}/', getXoutofY($picarray,$index,$last), $html);
                $html = preg_replace('/{CURRENT_INDEX}/', $index, $html);
                $html = preg_replace('/{PREVIOUS_INDEX}/', $previous, $html);
                $html = preg_replace('/{NEXT_INDEX}/', $next, $html);
            }
            return $html;
        # Galerie Link erzeugen
        } else {
            $j=0;
            if(file_exists($dir_gallery)) {
                $handle = opendir($dir_gallery);
                while ($file = readdir($handle)) {
                    if (is_file($dir_gallery.$file) and ($file <> "texte.conf")) {
                        $j++;
                    }
                }
                closedir($handle);
            } else {
                // Galerie nicht vorhanden
                return createDeadlink($specialchars->rebuildSpecialChars($values[0], false, true), $language->getLanguageValue1("tooltip_link_gallery_error_1", $specialchars->rebuildSpecialChars($values[0], false, true)));
            }
            $gal_name = NULL;
            if(isset($values[0])) {
                $gal_name = $specialchars->rebuildSpecialChars($values[0], false, true);
            }
            if(isset($values[1])) {
                $gal_name = $specialchars->rebuildSpecialChars($values[1], false, true);
            }
            return "<a class=\"gallery\" href=\"".$linkprefix."gal=".$gal_request."\" ".getTitleAttribute($language->getLanguageValue2("tooltip_link_gallery_2", $specialchars->rebuildSpecialChars($values[0], false, true), $j))."target=\"".$GALLERY_CONF->get("target")."\">".$gal_name."</a>";
        }
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * 
    ***************************************************************/
    function getConfig() {
        global $ADMIN_CONF;
        $language = $ADMIN_CONF->get("language");

        // Rückgabe-Array initialisieren
        // Das muß auf jeden Fall geschehen!
        $config['deDE'] = array();
        $config['enEN'] = array();
        $config['deDE']['gallerytemplate'] = array(
            "type" => "textarea",                       // Pflicht:  Eingabetyp 
            "cols" => "50",                             // Pflicht:  Spaltenanzahl 
            "rows" => "7",                              // Pflicht:  Zeilenanzahl
            "description" => "Hier können die Galerie-Platzhalter in die gewünschte Reihenfolge gebracht werden (Zeilenumbrüche sind erlaubt).",     // Pflicht:  Beschreibung
        );
        $config['enEN']['gallerytemplate'] = array(
            "type" => "textarea",                       // Pflicht:  Eingabetyp 
            "cols" => "50",                             // Pflicht:  Spaltenanzahl 
            "rows" => "7",                              // Pflicht:  Zeilenanzahl
            "description" => "Here, you may change the order of the gallery placeholders.",     // Pflicht:  Beschreibung
        );
        // Nicht vergessen: Das gesamte Array zurückgeben
        if(isset($config[$language])) {
            return $config[$language];
        } else {
            return $config['deDE'];
        }
    } // function getConfig    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück
    *  
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        $language = $ADMIN_CONF->get("language");

        $info['deDE'] = array(
            // Plugin-Name
            "<b>moziloCMS-Standardgalerie</b> 0.1",
            // CMS-Version
            "1.12",
            // Kurzbeschreibung
            'Erstellt die Standard-Galerieansicht.<br />
            <br />
            In den Galerie-Einstellungen im moziloAdmin kann bestimmt werden, ob die Galerie in einem neuen Fenster bzw. Tab ("Target blank") angezeigt werden soll oder im aktuellen ("Target self").<br />
            Außerdem läßt sich in den erweiteren Galerie-Optionen mit dem Punkt "Vorschaubilder erzeugen und benutzen?" bestimmen, ob die Bilder im Einzelbildmodus (EM) oder im Übersichtsmodus (ÜM) dargestellt werden sollen.<br /> 
            <br />
            <span style="font-weight:bold;">Nutzung:</span><br />
            {Galerie|moziloCMS} fügt die Galerie "moziloCMS" ein (bei "Target self") oder einen Link darauf (bei "Target blank").<br />
            {Galerie|moziloCMS,Beliebiger Anzeigetext} fügt einen Link auf die Galerie "moziloCMS" mit dem angegebenen Anzeigetext ein (nur für "Target blank").<br />
            <br />
            <span style="font-weight:bold;">Konfiguration:</span><br />
            Die Anordnung der einzelnen Funktionselemente mithilfe der Platzhalter im Textfeld geändert werden. Folgende Platzhalter stehen zur Verfügung:<br />
            - {CURRENTGALLERY}: Name der Galerie<br />
            - {GALLERYMENU}: Menü "erstes/voriges/nächstes/letztes Bild" (nur EM)<br />
            - {NUMBERMENU}: Nummernmenü (im EM) / Übersicht aller Bilder inklusive Untertitel (im ÜM)<br />
            - {CURRENTPIC}: aktuelles Bild (nur EM)<br />
            - {CURRENTDESCRIPTION}: Untertitel des aktuellen Bildes (nur EM)<br />
            - {XOUTOFY}: "Bild x von y" (nur EM)<br />
            - {CURRENT_INDEX}: Aktueller Bildindex (nur EM)<br />
            - {PREVIOUS_INDEX}: Voriger Bildindex (nur EM)<br />
            - {NEXT_INDEX}: Nächster Bildindex (nur EM)<br />
            <br />
            Für die Verwendung in neuem Fenster/Tab können die beschriebenen Platzhalter in der gallerytemplate.html angeordnet werden.',
            // Name des Autors
           "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            # Platzhalter => Kurzbeschreibung
            array('{Galerie|}' => 'moziloCMS-Standardgalerie')
            );

        if(isset($info[$language])) {
            return $info[$language];
        } else {
            return $info['deDE'];
        }
    } // function getInfo

}

?>