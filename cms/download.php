<?php

/* 
* 
* $Revision: 410 $
* $LastChangedDate: 2010-01-24 00:35:27 +0100 (So, 24. Jan 2010) $
* $Author: stefanbe $
*
*/

    require_once("DefaultConf.php");
    require_once("Properties.php");
    
    // Initial: Fehlerausgabe unterdrücken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
    @ini_set("display_errors", 0);

    $ERRORMESSAGE = "Fehlerhafter Parameter übergeben.";    
    $DOWNLOADS = new Properties("conf/downloads.conf",true);

    $CAT     = preg_replace('/(\/|\\\)/', "", rawurlencode($_REQUEST['cat']));
    $FILE = preg_replace('/(\/|\\\)/', "", rawurlencode($_REQUEST['file']));
    $PATH = "../kategorien/$CAT/dateien/$FILE";

    // Abbruch bei fehlerhaften Parametern
    if (($CAT == "") || ($FILE == "") || (!file_exists($PATH))) {
        die($ERRORMESSAGE);
    }
        
    // Alles okay, Downloadzähler inkrementieren und Datei ausliefern
    else {
        $DOWNLOADS->set($CAT.":".$FILE, $DOWNLOADS->get($CAT.":".$FILE) + 1);
        download($PATH);
    }
    

    function download($file){
        
        // Existiert die Datei?
        if (!is_file($file)) { 
            die($ERRORMESSAGE);
        }
        
        // Infos zur Datei
        $len = filesize($file);
        $filename = basename($file);
        $file_extension = strtolower(substr(strrchr($filename,"."),1));
        
        
        // abhängig von der Extension: Content-Type setzen
        switch( $file_extension ) {
              case "pdf": $ctype="application/pdf"; break;
              case "exe": $ctype="application/octet-stream"; break;
              case "zip": $ctype="application/zip"; break;
              case "doc": $ctype="application/msword"; break;
              case "xls": $ctype="application/vnd.ms-excel"; break;
              case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
              case "gif": $ctype="image/gif"; break;
              case "png": $ctype="image/png"; break;
              case "jpeg":
              case "jpg": $ctype="image/jpg"; break;
              case "mp3": $ctype="audio/mpeg"; break;
              case "wav": $ctype="audio/x-wav"; break;
              case "mpeg":
              case "mpg":
              case "mpe": $ctype="video/mpeg"; break;
              case "mov": $ctype="video/quicktime"; break;
              case "avi": $ctype="video/x-msvideo"; break;
              case "txt": $ctype="text/plain"; break;
              case "htm": 
              case "html":$ctype="Content-type:text/html"; break;
        
              // PHP-Dateien dürfen nicht heruntergeladen werden
              case "php": die($ERRORMESSAGE); break;
        
                  default: $ctype="application/force-download";
        }
        
        // Header schreiben
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public"); 
        header("Content-Description: File Transfer");
        
        // oben ausgewählter Content-Type
        header("Content-Type: $ctype");
        
        // Datei direkt im Browser anzeigen (inline); Dateinamen setzen
        $header="Content-Disposition: inline; filename=".$filename.";";
        // Mit "Content-Disposition: attachment" wird der Download über ein Downloadfenster erzwungen:
        //    $header="Content-Disposition: attachment; filename=".$filename.";";
        header($header );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".$len);
        @readfile($file);
        exit;
    } 

?>