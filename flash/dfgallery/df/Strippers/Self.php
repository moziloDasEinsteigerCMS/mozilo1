<?php
# das ist um ohne xml file die gallery zu betreiben
require_once "ImageFetcher.php";
require_once "../../../SpecialChars.php";

class Strippers_Self extends ImageFetcher{
    # $url = cat = "" page = "../"
    public function Strippers_Self($url)
    {

        $dir = "../../../galerien/";
        $specialchars   = new SpecialChars();
        if(substr($url,-9) == "galerien/") {
            $galarray = array();
            $currentdir = opendir($dir);
            // Alle Dateien des �bergebenen Verzeichnisses einlesen...
            while (false !== ($file = readdir($currentdir))) {
                if(($file == ".") or ($file == "..") or ($file == ".svn")) continue;
                if (is_dir($dir.$file) and ($file <> ".") and ($file <> "..")) {
                    // ...wenn alles pa�t, ans Bilder-Array anh�ngen
                    $galarray[] = $file;
                }
            }
            closedir($currentdir);
            sort($galarray);
        } else {
            $galarray[] = basename($url);
            $url = str_replace(basename($url),"",$url);
        }

        $filetypes = array("jpg", "jpeg", "jpe", "gif", "png", "svg");
        foreach($galarray as $gallery) {
            $picarray = array();
            $currentdir = opendir($dir.$gallery);
            // Alle Dateien des �bergebenen Verzeichnisses einlesen...
            while ($file = readdir($currentdir)) {
                if (($file <> ".") && ($file <> "..") && (in_array(strtolower(substr(strrchr($file, "."), 1, strlen(strrchr($file, "."))-1)), $filetypes))) {
                    $picarray[] = $file;
                }
            }
            closedir($currentdir);
            sort($picarray);
            $lines = file($dir.$gallery."/texte.conf");
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
                if(!isset($alldescriptions[$pic])) $alldescriptions[$pic] = "";
                $this->photos[$album][$id]["title"] = $alldescriptions[$pic];
                $this->photos[$album][$id]["image"] = $url.rawurlencode($gallery).'/'.rawurlencode($pic);
                $this->photos[$album][$id]["thumb"] = $url.rawurlencode($gallery).'/vorschau/'.rawurlencode($pic);
                $this->photos[$album][$id]["date"] = @date("F d Y", @filemtime($dir.$gallery."/".$pic));
            }
        }
    }
}

?>