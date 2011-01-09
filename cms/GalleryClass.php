<?php
class GalleryClass {

    var $allowed_pics = array("jpg", "jpeg", "jpe", "gif", "png", "svg");
    var $GalleryArray = array();
    var $GalleryMenuArray = array();
    var $GalleriesArray = array();
    var $currentGallery = false;
    var $currentImageIndex = 1;
    var $currentImageGroup = 0;

    function GalleryClass() {
        global $BASE_DIR, $GALLERIES_DIR_NAME;
        # das ist nur ein array mit den Galerie Ordnernamen
        # ohne jegliche Prüfung
        $this->GalleriesArray = getDirAsArray($BASE_DIR.$GALLERIES_DIR_NAME,"dir");
#        if(isset($_REQUEST['gal']) and strlen($_REQUEST['gal']) > 0)
#            $this->currentGallery = $_REQUEST['gal'];
    }

    # Das muss nach $GalleryClass = new GalleryClass(); gemacht werden
    # $Galleries = "gallerie", array(gallerien) oder false für alle gallerien
    # $allowed_pics = array(erlaupte bilder endung mit oder ohne punkt)
    #                   false es wird Default liste genommen
    # $with_preview = wenn true es werden nur galerien und bilder ins array eingesetzt
    #               die auch Vorschaubilder haben
    #               wenn false es werden keine Vorschaubilder erzeugt
    # $with_description = wenn true es wird die Bildbeschreibung erzeugt
    #                   wenn false es wird keine Bildbeschreibung erzeugt
    function initial_Galleries($Galleries = false,$allowed_pics = false,
                                $with_preview = false,$with_description = false) {
        # wenn $allowed_pics ein array ist ansonsten wird Default $this->allowed_pics benutzt
        if($allowed_pics !== false and is_array($allowed_pics)) {
            $array = array();
            # alle ext im array in kleinschreibung wandeln und bei bedarf Punkt einsetzen
            foreach($allowed_pics as $z => $ext) {
                if(strlen($ext) < 2)
                    continue;
                $array[$z] = strtolower($ext);
                if(!strstr($ext,"."))
                    $array[$z] = ".".$ext;
            }
            if(count($array) > 0)
                $this->allowed_pics = $array;
        }
        if($Galleries !== false and !is_array($Galleries))
            $Galleries = array($Galleries);
        # Gallerien erstellen
        $this->GalleryArray = $this->make_DirGalleryArray($Galleries,$with_preview,$with_description);
        if(count($this->GalleryArray) < 1)
            return false;
        # wenn die currentGallery noch nicht gesetzt wurde einfach mal die erste setzen
        if($this->currentGallery === false)
            $this->currentGallery = key($this->GalleryArray);
        return true;
    }

    # Wenn man ein Menu braucht muss es vorher initaliesiert werden
    # $gallery = eine Galerie
    # $cols = Anzahl der Spalten
    # $rows = Anzahl der Zeilen
    # wenn $cols = false und $rows = false werden alle genommen
    function initial_GalleryMenu($gallery,$cols = false,$rows = false) {
        $menu_array = array();
        $image_group = count($this->GalleryArray[$gallery]);
        if($cols !== false and $rows !== false)
            $image_group = $cols * $rows;
        $group = 0;
        $image_z = 1;
        foreach($this->GalleryArray[$gallery] as $image => $tmp) {
            $menu_array[$group][$image_z] = $image;
            if(($image_z > 0) and ($image_z % $image_group == 0))
                $group++;
            $image_z++;
        }
        $this->GalleryMenuArray[$gallery] = $menu_array;
$this->currentGallery = $gallery;
$this->set_currentIndexFromRequest();
$this->set_currentGroupFromRequest();
    }

    function get_RequestGalery() {
        if(isset($_REQUEST['gal'])
                and strlen($_REQUEST['gal']) > 0
                and isset($this->GalleryArray[$_REQUEST['gal']]))
            return $_REQUEST['gal'];
        else
            return false;
    }

    # Sortiert die Galerien
    # $sort_type = ksort, krsort, rsort, sort, natcasesort, natsort, rnatcasesort, rnatsort
    #       oder number_(first/last)_($sort_type für Zahlen)_($sort_type für Text)
    #       z.B. $sort_type = number_first_sort_sort
    #           Es werden alle Galerien die mit einer Zahl beginnen als erstes dargestelt
    #           und mit sort Sortiert danach kommen alle die nicht mit einer Zahl beginen
    #           und werden mit sort Sortiert hier wird auch der $flag angewendet.
    #           Zuläsige $sort_type sind: none, rsort, sort, natcasesort, natsort,
    #                                    rnatcasesort und rnatsort
    #       none für keine Sortierung
    #       rnatcasesort und rnatsort ist in umgekehrter Reihenfolge
    # $flag = false(ist gleich Default), numeric, string und locale
    #       false = SORT_REGULAR
    #       numeric = SORT_NUMERIC
    #       string = SORT_STRING
    #       locale = SORT_LOCALE_STRING
    #       Mehr Info siehe PHP Beschreibung von sort()
    # Info die Sortierung ksort und krsort sind die schnelsten da direckt Sortiert wird
    function sort_Galleries($sort_type = false,$flag = false) {
        if($sort_type == "ksort" or $sort_type == "krsort") {
            $sort_type($this->GalleryArray,$this->helpSortFlags($flag));
        } elseif($sort_type == "natcasesort" or $sort_type == "natsort"
                or $sort_type == "rnatcasesort" or $sort_type == "rnatsort"
                or $sort_type == "rsort" or $sort_type == "sort") {
            $galleries = $this->get_GalleriesArray();
            if($sort_type == "natcasesort" or $sort_type == "natsort")
                $sort_type($galleries);
            elseif($sort_type == "rnatcasesort" or $sort_type == "rnatsort")
                $galleries = $this->$sort_type($galleries);
            else
                $sort_type($galleries,$this->helpSortFlags($flag));
            $this->helpMakeSortGalleries($galleries);
        } elseif(substr($sort_type,0,7) == "number_") {
            list($func,$order,$sortdigit,$sorttext) = explode("_",$sort_type);
            $this->helpSortGalleriesNumber($order,$sortdigit,$sorttext,$this->helpSortFlags($flag));
        }
    }

    # Sortiert die Bilder der Galerie(n)
    # $Galleries = TEXT, array(liste der Galerien) oder false für alle
    # $sort_type = siehe sort_Galleries()
    # $flag = siehe sort_Galleries()
    function sort_Images($Galleries = false,$sort_type = false,$flag = false) {
        if($Galleries !== false and is_array($Galleries))
            $Galleries = $Galleries;
        elseif($Galleries !== false and !is_array($Galleries))
            $Galleries = array($Galleries);
        else
            $Galleries = $this->get_GalleriesArray();
        foreach($Galleries as $gallery) {
            if(isset($this->GalleryArray[$gallery])) {
                if($sort_type == "ksort" or $sort_type == "krsort") {
                    $sort_type($this->GalleryArray[$gallery],$this->helpSortFlags($flag));
                } elseif($sort_type == "natcasesort" or $sort_type == "natsort"
                        or $sort_type == "rnatcasesort" or $sort_type == "rnatsort"
                        or $sort_type == "rsort" or $sort_type == "sort") {
                    $images = $this->get_GalleryImagesArray($gallery);
                    if($sort_type == "natcasesort" or $sort_type == "natsort")
                        $sort_type($images);
                    elseif($sort_type == "rnatcasesort" or $sort_type == "rnatsort")
                        $images = $this->$sort_type($images);
                    else
                        $sort_type($images,$this->helpSortFlags($flag));
                    $this->helpMakeSortImages($gallery,$images);

                } elseif(substr($sort_type,0,7) == "number_") {
                    list($func,$order,$sortdigit,$sorttext) = explode("_",$sort_type);
                    $this->helpSortImagesNumber($gallery,$order,$sortdigit,$sorttext,$this->helpSortFlags($flag));
                }
            }
        }
    }

    function get_GalleriesArray() {
        $return_array = array();
        foreach($this->GalleryArray as $gallery => $tmp) {
            $return_array[] = $gallery;
        }
        return $return_array;
    }

    function get_GalleryImagesArray($gallery) {
        $return_array = array();
        if(isset($this->GalleryArray[$gallery])
            and is_array($this->GalleryArray[$gallery])
            and count($this->GalleryArray[$gallery]) > 0) {
            foreach($this->GalleryArray[$gallery] as $images => $tmp) {
                $return_array[] = $images;
            }
        }
        return $return_array;
    }

    # $coded_as = html, url ,false = wie in texte.conf
    function get_ImageDescription($gallery,$image,$coded_as = false) {
        if(isset($this->GalleryArray[$gallery][$image]['description']) and false !== $this->GalleryArray[$gallery][$image]['description']) {
            $description = $this->GalleryArray[$gallery][$image]['description'];
            if($coded_as == "html") {
#                global $CHARSET;
                global $specialchars;
$description = $specialchars->rebuildSpecialChars($description,false,true)
#                $description = htmlentities($description,ENT_COMPAT,$CHARSET);
            } elseif($coded_as == "url")
                $description = rawurlencode($description);
            return $description;
        }
        return NULL;
    }
    function get_Description($group,$index,$coded_as = false) {

        return NULL;
    }
# $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$pic

    function get_ImagePath($gallery,$image,$preview = false) {
        global $BASE_DIR, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        if($preview === true)
            return $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$image;
    }

    function get_currentHrefImage($preview = false) {
        global $URL_BASE, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        $image = $this->get_fromMenuImagName($this->currentImageGroup,$this->currentImageIndex);
        if($preview === true)
            return $URL_BASE.$GALLERIES_DIR_NAME."/".$this->currentGallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $URL_BASE.$GALLERIES_DIR_NAME."/".$this->currentGallery."/".$image;
    }

    function get_currentImageName() {
        return $this->get_fromMenuImagName($this->currentImageGroup,$this->currentImageIndex);
    }

    function get_srcImage($gallery,$image,$preview = false) {
        global $URL_BASE, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        $gallery = rawurlencode($gallery);
        $image = rawurlencode($image);
#        $img = str_replace("%","%25",$URL_BASE.$GALLERIES_DIR_NAME."/".$img);
        if($preview === true)
            return $URL_BASE.$GALLERIES_DIR_NAME."/".$gallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $URL_BASE.$GALLERIES_DIR_NAME."/".$gallery."/".$image;
    }

    function get_ImageType($image) {
        # ab denn letzen punkt ist die ext
        $type = substr($image,strrpos($image,"."));
        if(strlen($type) > 1)
            # kleingeschrieben zurück
            return strtolower($type);
        return false;
    }

    function set_currentIndexFromRequest() {
        if(
                $this->get_RequestGalery() == $this->currentGallery
                and isset($_REQUEST['index'])
                and strlen($_REQUEST['index']) > 0
                and ctype_digit($_REQUEST['index'])
        )
            $this->currentImageIndex = $_REQUEST['index'];
    }

    function set_currentGroupFromRequest() {
        if(
                $this->get_RequestGalery() == $this->currentGallery
                and count($this->GalleryMenuArray[$this->currentGallery]) > 1
                and isset($_REQUEST['group'])
                and strlen($_REQUEST['group']) > 0
                and ctype_digit($_REQUEST['group'])
        )
            $this->currentImageGroup = $_REQUEST['group'];
    }

    function get_firstImageIndex($group = false) {
        if($group !== false)
            return array($this->get_firstGroupIndex(),"1");
        return "1";
#$this->currentGallery
#$this->GalleryMenuArray[$Gallery]
    }

    function get_lastImageIndex($group = false) {
        $lastgroup = $this->get_lastGroupIndex();
        $lastimage = (count($this->GalleryMenuArray[$this->currentGallery][$lastgroup]) - 1);
        if($group !== false)
            return array($lastgroup,$lastimage);
        return $lastimage;
    }
#$this->currentImageIndex
#$this->currentImageGroup
    function get_nextImageIndex($group = false,$circular = true) {
        if(isset($this->GalleryMenuArray[$this->currentGallery][$this->currentImageGroup][($this->currentImageIndex + 1)]))
            $return_array = array($this->currentImageGroup,($this->currentImageIndex + 1));
        elseif(isset($this->GalleryMenuArray[$this->currentGallery][($this->currentImageGroup + 1)][($this->currentImageIndex + 1)])) {
            $return_array array(($this->currentImageGroup + 1),($this->currentImageIndex + 1));
        } elseif($circular === true)
            $return_array $this->get_firstImageIndex(true);
        $return_array array($this->currentImageGroup,$this->currentImageIndex);
        if($group !== false)
            return $return_array;
        return $return_array[1]
    }

    function get_previousImageIndex($group = false,$circular = true) {
        if(isset($this->GalleryMenuArray[$this->currentGallery][$this->currentImageGroup][($this->currentImageIndex - 1)]))
            $return_array array($this->currentImageGroup,($this->currentImageIndex - 1));
        elseif(isset($this->GalleryMenuArray[$this->currentGallery][($this->currentImageGroup - 1)][($this->currentImageIndex - 1)])) {
            $return_array array(($this->currentImageGroup - 1),($this->currentImageIndex - 1));
        } elseif($circular === true)
            $return_array $this->get_lastImageIndex(true);
        $return_array array($this->currentImageGroup,$this->currentImageIndex);
        if($group !== false)
            return $return_array;
        return $return_array[1]
    }

    function get_firstGroupIndex() {
        return 0;
    }

    function get_lastGroupIndex() {
        return (count($this->GalleryMenuArray[$this->currentGallery]) - 1);
    }

    function get_firstImageFromGroup($group) {
        return key($this->GalleryMenuArray[$this->currentGallery][$group]);
    }

    function get_lastImageFromGroup($group) {
        end($this->GalleryMenuArray[$this->currentGallery][$group]);
        return key($this->GalleryMenuArray[$this->currentGallery][$group]);
    }

    function get_nextGroupIndex($circular = true) {
        if(isset($this->GalleryMenuArray[$this->currentGallery][($this->currentImageGroup + 1)])) {
            $group = ($this->currentImageGroup + 1);
#echo $this->get_firstImageFromGroup($group)."=first $group=group<br>\n";
            return array($group,$this->get_firstImageFromGroup($group));
        } elseif($circular === true) {
            $group = $this->get_firstGroupIndex();
#echo $this->get_firstImageFromGroup($group)."=first $group=group<br>\n";
            return array($group,$this->get_firstImageFromGroup($group));
#            return $this->get_firstGroupIndex();
        }
        return array($this->currentImageGroup,$this->currentImageIndex);
#        return $this->currentImageGroup;
    }
#get_lastImageFromGroup()
#get_firstImageFromGroup()
    function get_previousGroupIndex($circular = true) {
        if(isset($this->GalleryMenuArray[$this->currentGallery][($this->currentImageGroup - 1)])) {
            $group = ($this->currentImageGroup - 1);
            return array($group,$this->get_lastImageFromGroup($group));
        } elseif($circular === true) {
            $group = $this->get_lastGroupIndex();
            return array($group,$this->get_lastImageFromGroup($group));
        }
        return array($this->currentImageGroup,$this->currentImageIndex);
    }

    function get_currentGroupImageIndexArray($group = false) {# ,$shift = false
        $link_array = array_keys($this->GalleryMenuArray[$this->currentGallery][$this->currentImageGroup]);
#        if($shift !== false)
#$link_array = array_slice($link_array, $this->currentImageIndex, $shift);   // liefert "a", "b" und "c"
        if($group !== false)
            return array($this->currentImageGroup,$link_array);
        return $link_array;
    }

    function get_fromMenuImagName($group,$index) {
        return $this->GalleryMenuArray[$this->currentGallery][$group][$index];
    }

    function create_currentImgTag($alt = false) {
#get_ImageDescription($gallery,$image,$coded_as = false) {
#get_srcImage($gallery,$image,$preview = false)
        global $URL_BASE;
        global $GALLERIES_DIR_NAME;
        $image = $this->get_fromMenuImagName($this->currentImageGroup,$this->currentImageIndex);
        $alttext = $alt;
        if($alt === false)
            $alttext = $this->get_ImageDescription($this->currentGallery,$image,"html");
#        $text = $this->get_ImageDescription($this->currentGallery,$image);
#        $img = $this->currentGallery."/".$this->GalleryMenuArray[$this->currentGallery][$this->currentImageGroup][$this->currentImageIndex];
        $img = $this->get_srcImage($this->currentGallery,$image);

#        $img = str_replace("%","%25",$URL_BASE.$GALLERIES_DIR_NAME."/".$img);
#        $alt = 
        $img_tag = '<img src="'.$img.'" alt="'.$alttext.'" hspace="0" vspace="0" border="0" />';
        return $img_tag;
#<img align="middle" border="0">
    }

    function is_ImageActiv($index) {
        if($index == $this->currentImageIndex)
            return true;
        return false;
    }
###############################################################################
# Hilfs Functionen
###############################################################################

    function rnatcasesort($Array) {
        natcasesort($Array);
        $Array = array_reverse($Array);
        return $Array;
    }

    function rnatsort($Array) {
        natsort($Array);
        $Array = array_reverse($Array);
        return $Array;
    }

    function helpSortFlags($flag) {
        $return_flag = SORT_REGULAR;
        if($flag == "numeric")
            $return_flag = SORT_NUMERIC;
        elseif($flag == "string")
            $return_flag = SORT_STRING;
        elseif($flag == "locale")
            $return_flag = SORT_LOCALE_STRING;
        return $return_flag;
    }

    function helpSortGalleriesNumber($order,$sortdigit,$sorttext,$flag) {
        if($sortdigit == "ksort" or $sortdigit == "krsort"
                or $sorttext == "ksort" or $sorttext == "krsort")
            return;
        $galarray_digit = array();
        $galarray_string = array();
        foreach($this->GalleryArray as $gallery => $tmp) {
            # ist erstes zeichen eine zahl
            if(ctype_digit($gallery[0])) {
                $galarray_digit[] = $gallery;
            # ist erstes zeichen keine zahl
            } else  {
                $galarray_string[] = $gallery;
            }
        }
        if($sortdigit == "natcasesort" or $sortdigit == "natsort")
            $sortdigit($galarray_digit);
        elseif($sortdigit == "rnatcasesort" or $sortdigit == "rnatsort")
            $galarray_digit = $this->$sortdigit($galarray_digit);
        elseif($sortdigit == "sort" or $sortdigit == "rsort")
            $sortdigit($galarray_digit,SORT_NUMERIC);

        if($sorttext == "natcasesort" or $sorttext == "natsort")
            $sorttext($galarray_string);
        elseif($sorttext == "rnatcasesort" or $sorttext == "rnatsort")
            $galarray_string = $this->$sorttext($galarray_string);
        elseif($sorttext == "sort" or $sorttext == "rsort")
            $sorttext($galarray_string,$flag);

        if($order == "last")
            $sortresult = array_merge($galarray_string, $galarray_digit);
        else
            $sortresult = array_merge($galarray_digit, $galarray_string);
        $this->helpMakeSortGalleries($sortresult);
    }

    function helpMakeSortGalleries($Galleries) {
        $tmp_array = array();
        foreach($Galleries as $gallery) {
            $tmp_array[$gallery] = $this->GalleryArray[$gallery];
        }
        $this->GalleryArray = $tmp_array;
        unset($tmp_array);
    }

    function helpMakeSortImages($Gallery,$Images) {
        $tmp_array = array();
        foreach($Images as $image) {
            $tmp_array[$image] = $this->GalleryArray[$Gallery][$image];
        }
        $this->GalleryArray[$Gallery] = $tmp_array;
        unset($tmp_array);
    }

    function helpSortImagesNumber($gallery,$order,$sortdigit,$sorttext,$flag) {
        if($sortdigit == "ksort" or $sortdigit == "krsort"
                or $sorttext == "ksort" or $sorttext == "krsort")
            return;
        $image_digit = array();
        $image_string = array();
        foreach($this->GalleryArray[$gallery] as $image => $tmp) {
            # ist erstes zeichen eine zahl
            if(ctype_digit($image[0])) {
                $image_digit[] = $image;
            # ist erstes zeichen keine zahl
            } else  {
                $image_string[] = $image;
            }
        }

        if($sortdigit == "natcasesort" or $sortdigit == "natsort")
            $sortdigit($image_digit);
        elseif($sortdigit == "rnatcasesort" or $sortdigit == "rnatsort")
            $image_digit = $this->$sortdigit($image_digit);
        elseif($sortdigit == "sort" or $sortdigit == "rsort")
            $sortdigit($image_digit,SORT_NUMERIC);

        if($sorttext == "natcasesort" or $sorttext == "natsort")
            $sorttext($image_string);
        elseif($sorttext == "rnatcasesort" or $sorttext == "rnatsort")
            $image_string = $this->$sorttext($image_string);
        elseif($sorttext == "sort" or $sorttext == "rsort")
            $sorttext($image_string,$flag);

        if($order == "last")
            $sortresult = array_merge($image_string, $image_digit);
        else
            $sortresult = array_merge($image_digit, $image_string);

        $this->helpMakeSortImages($gallery,$sortresult);

    }

###############################################################################
# Ab hier solten die functionen nur von der function GalleryClass() verwendet werden
###############################################################################

    function make_DirGalleryArray($Galleries,$with_preview,$with_description) {
#!!!!!!!!! hier muss noch nee prüfung rein das wenn galerie keine bilder hat sie erst garnich erscheint

        global $BASE_DIR;
        global $GALLERIES_DIR_NAME;
        global $PREVIEW_DIR_NAME;
        $GALERIE_DIR = $BASE_DIR.$GALLERIES_DIR_NAME."/";
#echo "$GALERIE_DIR<br>\n";
        $return_array = array();
        if($Galleries !== false and is_array($Galleries)) {
            $galleries_array = $Galleries;
        } else
            $galleries_array = getDirAsArray($GALERIE_DIR,"dir");

        foreach($galleries_array as $gallery) {
#echo $GALERIE_DIR.$gallery."<br>\n";
            $description = array();
            $gallery_images = getDirAsArray($GALERIE_DIR.$gallery,$this->allowed_pics);
            # Galerie hat keine Bilder also nicht erstellen
            if(count($gallery_images) < 1)
                continue;
/*echo "<pre>";
print_r($gallery_images);
echo "</pre><br>\n";*/
            # Bildbeschreibung soll benutzt werden und texte.conf gibts also erzeugen
            if($with_description === true
                    and count($gallery_images) > 0
                    and file_exists($GALERIE_DIR.$gallery."/"."texte.conf")) {
                if(false !== ($tmp_description = file($GALERIE_DIR.$gallery."/"."texte.conf"))) {
                    foreach($tmp_description as $zeile) {
                        if(strpos($zeile," = ") < 3)
                            continue;
                        $zeile = trim($zeile);
                        $image = substr($zeile,0,strpos($zeile," = "));
                        $descript = substr($zeile,(strpos($zeile," = ") + 3));
                        $description[$image] = $descript;
                    }
                }
                unset($tmp_description);
            }
            foreach($gallery_images as $image) {
                # Bild hat kein Vorschaubild, Vorschaubilder sollen aber benutzt werden
                # dann nicht ins array
                if($with_preview === true
                        and !file_exists($GALERIE_DIR.$gallery."/".$PREVIEW_DIR_NAME."/".$image))
                    continue;
#echo "$image<br>\n";
                $return_array[$gallery][$image]['preview'] = false;
                # Vorschaubilder sollen benutzt werden und Vorschaubild gibt es
                if($with_preview === true)
                    $return_array[$gallery][$image]['preview'] = true;
                $return_array[$gallery][$image]['description'] = false;
                # Bildbeschreibung soll benutzt werden wenn vorhanden ins array
                if($with_description === true and isset($description[$image]))
                    $return_array[$gallery][$image]['description'] = $description[$image];
            }
            # hat Galerie keine Bilder dann löschen
            if(isset($return_array[$gallery]) and count($return_array[$gallery]) < 1)
                unset($return_array[$gallery]);
        }
        return $return_array;
    }
/*
            if(in_array($this->get_ImageType($image),$this->allowed_pics))

    $size    = getimagesize($dir_origin.$pic);
    $mime    = $size['mime'];
    $width  = $size[0];
    $height = $size[1];

[GALLERY]
    [BILDNAME] array(
                    [preview] false oder true
                    [description] false oder TEXT
                    )

        global $EXT_LINK;
        global $CONTENT_FILES_DIR_NAME;
        $page_a = array();
        $page_sort = array();
        $currentdir = getDirAsArray($dir,"file");
        foreach($currentdir as $file) {
            if(substr($file, -4) == $EXT_LINK) {
                $target = "-_blank-";
                if(strpos($file,"-_self-") > 1)
                    $target = "-_self-";
                $tmp = explode($target,$file);
                $key = substr($tmp[0],3);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = $EXT_LINK;
                $page_a[$key]["_link-"] = str_replace($this->link_search,$this->link_replace,substr($tmp[1],0,strlen($tmp[1])-4));
                $page_a[$key]["_target-"] = str_replace("-","",$target);
            } else {
                $key = substr($file,3,strlen($file)-7);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = substr($file,-4);
                $page_a[$key]["_time-"] = filemtime($dir."/".$file);
            }
        }
        return $page_a;
    }*/

}
?>