<?php

require_once "ImageFetcher.php";
#require_once "../../../Properties.php";
require_once "../../../SpecialChars.php";

class Strippers_Self extends ImageFetcher{

    public function Strippers_Self($url)
    {
    $specialchars   = new SpecialChars();
        $galarray = array();
        $dir = "../../../galerien/";
        $currentdir = opendir($dir);
        // Alle Dateien des übergebenen Verzeichnisses einlesen...
         while (false !== ($file = readdir($currentdir))) {
            if(($file == ".") or ($file == "..")) continue;
            if (is_dir($dir.$file) and ($file <> ".") and ($file <> "..")) {
                // ...wenn alles paßt, ans Bilder-Array anhängen
                $galarray[] = $file;
            }
        }
        closedir($currentdir);
        sort($galarray);

        $filetypes = array("jpg", "jpeg", "jpe", "gif", "png", "svg");
        foreach($galarray as $gallery) {
            $picarray = array();
            $currentdir = opendir("../../../galerien/".$gallery);
            // Alle Dateien des übergebenen Verzeichnisses einlesen...
            while ($file = readdir($currentdir)) {
                if (($file <> ".") && ($file <> "..") && (in_array(strtolower(substr(strrchr($file, "."), 1, strlen(strrchr($file, "."))-1)), $filetypes))) {
                    $picarray[] = $file;
                }
            }
            closedir($currentdir);
            sort($picarray);
            $lines = file("../../../galerien/".$gallery."/texte.conf");
            foreach ($lines as $line) {
                // comments
                if (preg_match("/^#/",$line) || preg_match("/^\s*$/",$line)) {
                    continue;
                }
                if (preg_match("/^([^=]*)=(.*)/",$line,$matches)) {
                    $alldescriptions[trim($matches[1])] = trim($matches[2]);
                }
            }
$album = $specialchars->rebuildSpecialChars($gallery, false, false);
            foreach($picarray as $id => $pic) {
#                $this->photos[$gallery][$id]["title"] = $album;
#                $this->photos[$album][$id]["description"]    = $album;
#                $this->photos[$gallery][$id]["id"]    = "";
                $this->photos[$album][$id]["title"] = $alldescriptions[$pic];
                $this->photos[$album][$id]["image"] = 'galerien/'.rawurlencode($gallery).'/'.rawurlencode($pic);
                $this->photos[$album][$id]["thumb"] = 'galerien/'.rawurlencode($gallery).'/vorschau/'.rawurlencode($pic);
                $this->photos[$album][$id]["date"] = date ("F d Y", filemtime("../../../galerien/".$gallery."/".$pic));
            }
        }
    }
}

/*
                # erstes album
                $this->photos['test2'][0]["title"] = $galarray[0];
                $this->photos['test2'][0]["image"] = 'galerien/test2/pa290003.jpg';
                $this->photos['test2'][0]["thumb"] = 'galerien/test2/vorschau/pa290003.jpg';
                $this->photos['test2'][0]["date"] = '2009';

                # zweites album
                $this->photos['test4'][1]["title"] = 'test4';
                $this->photos['test4'][1]["image"] = 'galerien/test2/pa290003.jpg';
                $this->photos['test4'][1]["thumb"] = 'galerien/test2/vorschau/pa290003.jpg';
                $this->photos['test4'][1]["date"] = '2009';*/
?>