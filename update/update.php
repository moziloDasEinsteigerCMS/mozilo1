<?php
$BASE_DIR = substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],'update/update.php'));
$CMS_DIR_NAME = "cms";
$ADMIN_DIR_NAME = "admin";
$BASE_DIR_CMS = $BASE_DIR.$CMS_DIR_NAME."/";
$BASE_DIR_ADMIN = $BASE_DIR.$ADMIN_DIR_NAME."/";
$OLD_CONF = "";
$old_conf_dir = "update";
$messages = "";
$messages_rename = "";
$convert = false;
if(isset($_GET['convert']) and $_GET['convert'] == "true") {
    $convert = true;
}

if(is_file($BASE_DIR_CMS."DefaultConf.php")) {
    require_once($BASE_DIR_CMS."DefaultConf.php");
} else {
    die("Fatal Error ".$BASE_DIR_CMS."DefaultConf.php Datei existiert nicht");
}
require_once($BASE_DIR_ADMIN."filesystem.php");

$conf_files = array(
                'ADMIN_CONF' => $ADMIN_DIR_NAME.'/conf/basic.conf',
#                'LOGIN_CONF' => $ADMIN_DIR_NAME.'/conf/logindata.conf',
                'CMS_CONF' => $CMS_DIR_NAME.'/conf/main.conf',
                'GALLERY_CONF' => $CMS_DIR_NAME.'/conf/gallery.conf',
                'PASSWORDS' => $CMS_DIR_NAME.'/conf/passwords.conf',
                'CONTACT_CONF' => $CMS_DIR_NAME.'/formular/formular.conf',
                'AUFGABEN_CONF' => $CMS_DIR_NAME.'/formular/aufgaben.conf');

$files_to_utf8 = array('.txt','.hid','.tmp','.html','.css');

$oldkey_newkey = array(
                    'maxheight' => 'gallerymaxheight',
                    'maxwidth' => 'gallerymaxwidth',
                    'usethumbs' => 'galleryusethumbs',
                    'target' => 'targetblank_gallery',
                    'maximageheight' => 'gallerymaxheight',
                    'maximagewidth' => 'gallerymaxwidth',
                    'formularmail' => 'adminmail'
                    );

$oldlang_newlang = array(
                    'Deutsch' => 'deDE',
                    'English' => 'enEN',
                    'France' => 'frFR',
                    'Italian' => 'itIT',
                    'Portuguese' => 'ptBR'
                    );

makeOldConf($old_conf_dir);
foreach($conf_files as $name => $file) {
    ${$name} = new Properties($BASE_DIR.$file,true);
    # die muss schreiben geöffnet werden können
    if(isset(${$name}->properties['error'])) {
        die(${$name}->properties['error']);
    }
    foreach(${$name}->properties as $key => $value) {
        if($key == 'error' or $key == 'readonly')
            continue;
        $old_key = $key;
        if(isset($oldkey_newkey[$key]))
            $old_key = $oldkey_newkey[$key];
        if(isset($OLD_CONF->properties[$old_key])) {
            $old_value = $specialchars->replaceSpecialChars(rebuildOldSpecialChars($OLD_CONF->properties[$old_key]),false);
            if($name == 'ADMIN_CONF' or $name == 'CONTACT_CONF')
                $old_value = str_replace(array('%2C','%40'),array(',','@'),$old_value);
            if($name == 'CONTACT_CONF') {
                if($key == 'mail' or $key == 'message' or $key == 'name' or $key == 'website')
                    $old_value = ",".$old_value;
            }
            if($key == 'target') {
                if($old_value = 'true')
                    $old_value = '_blank';
                else
                    $old_value = '_self';
            }
            if($key == 'cmslanguage' and isset($oldlang_newlang[$old_value])) {
                $old_value = $oldlang_newlang[$old_value];
            }
            if($value != $old_value) {
                $messages .= $name."->set(".$key.",".$old_value.")\n";
                if($convert)
                    ${$name}->set($key,$old_value);
            }
        }
    }
    unset(${$name});
}

$DOWNLOAD_COUNTS = new Properties($BASE_DIR.$CMS_DIR_NAME.'/conf/downloads.conf',true);
$DOWNLOAD_COUNTS_OLD = new Properties($BASE_DIR.'/update/downloads.conf',true);
foreach($DOWNLOAD_COUNTS_OLD->properties as $key => $value) {
    if($key == 'error' or $key == 'readonly')
        continue;
    $key = $specialchars->replaceSpecialChars(rebuildOldSpecialChars($key),false);
    $key = str_replace('%3A',':',$key);
    if($convert and !isset($DOWNLOAD_COUNTS->properties[$key])) {
        $messages .= "DOWNLOAD_COUNTS->set(".$key.",".$value.")\n";
        $DOWNLOAD_COUNTS->set($key,$value);
    }
}

function makeOldConf($old_conf_dir) {
    global $BASE_DIR;
    global $ADMIN_DIR_NAME;
    global $CMS_DIR_NAME;
    global $OLD_CONF;
    global $convert;
    global $messages;

    $inhalt = "";
    $handle = opendir($BASE_DIR.$old_conf_dir);
    while($file = readdir($handle)) {
        if(!isValidDirOrFile($file)) continue;
        if($file == 'old.conf' or $file == 'README.txt') {
            continue; 
        } elseif($file == 'logindata.conf' and !file_exists($BASE_DIR.$ADMIN_DIR_NAME.'/conf/logindata.conf')) {
            $messages .= "Copy ".$old_conf_dir.'/'.$file." -> ".$ADMIN_DIR_NAME.'/conf/logindata.conf'."\n";
            if($convert)
                copy($BASE_DIR.$old_conf_dir.'/'.$file,$BASE_DIR.$ADMIN_DIR_NAME.'/conf/logindata.conf');
            continue; 
        } elseif($file == 'syntax.conf' and !file_exists($BASE_DIR.$CMS_DIR_NAME.'/conf/syntax.conf')) {
            $messages .= "Copy ".$old_conf_dir.'/'.$file." -> ".$CMS_DIR_NAME.'/conf/syntax.conf'."\n";
            if($convert)
                copy($BASE_DIR.$old_conf_dir.'/'.$file,$BASE_DIR.$CMS_DIR_NAME.'/conf/syntax.conf');
            continue; 
        } else {
            $fp = fopen ($BASE_DIR.$old_conf_dir.'/'.$file, "r");
            $inhalt .= fread($fp, filesize($BASE_DIR.$old_conf_dir.'/'.$file));
            fclose($fp);
        }
    }
    $fp_neu = fopen ($BASE_DIR.$old_conf_dir.'/old.conf', "w");
    fputs ($fp_neu, $inhalt);
    fclose($fp_neu);
    $OLD_CONF = new Properties($BASE_DIR.$old_conf_dir.'/old.conf',true);
    if(!isset($OLD_CONF->properties['readonly'])) {
        die($OLD_CONF->properties['error']);
    }
}

function rebuildOldSpecialChars($text) {
    global $specialchars;
    $text = preg_replace("/-nbsp~/", " ", $text);
    // @, ?
    $text = preg_replace("/-at~/", "@", $text);
    $text = preg_replace("/-ques~/", "?", $text);
    // Alle mozilo-Entities in HTML-Entities umwandeln!
    $text = preg_replace("/-([^-~]+)~/U", "&$1;", $text);
    // & escapen 
    //$text = preg_replace("/&+(?!(.+);)/U", "&amp;", $text);
    $text = html_entity_decode($text,ENT_COMPAT,'ISO-8859-1');
    $text = toUTF8($text);
    return $text;

}

function toUTF8($text) {
    global $CHARSET;
    if(!check_utf8($text)) {
        if(function_exists("utf8_encode")) {
            $text = utf8_encode($text);
        } elseif(function_exists("mb_convert_encoding")) {
            $text = mb_convert_encoding($text, $CHARSET);
        } elseif(function_exists("iconv")) {
            $text = iconv('ISO-8859-1', $CHARSET.'//IGNORE',$text);
        } else die("kein utf-8 converter zur verfügung");
    }
    return $text;
}

function check_utf8($str) {
    $len = strlen($str);
    for($i = 0; $i < $len; $i++){
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c > 247)) return false;
            elseif ($c > 239) $bytes = 4;
            elseif ($c > 223) $bytes = 3;
            elseif ($c > 191) $bytes = 2;
            else return false;
            if (($i + $bytes) > $len) return false;
            while ($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) return false;
                $bytes--;
            }
        }
    }
    return true;
} // end of check_utf8

function inhaltChange($file,$dir) {
    global $BASE_DIR;
    global $convert;
    global $messages;

    $change = false;
    $fp = fopen ($BASE_DIR.$dir.'/'.$file, "r");
    $inhalt = fread($fp, filesize($BASE_DIR.$dir.'/'.$file));
    fclose($fp);
    if(substr($file,-(strlen(".html"))) == ".html") {
        $search = array(
                    $dir,
                    'layouts/{LAYOUT_DIR}',
                    'ISO-8859-1',
                    'iso-8859-1'
                    );
        $replace = array(
                    '{LAYOUT_DIR}',
                    '{LAYOUT_DIR}',
                    '{CHARSET}',
                    '{CHARSET}'
                    );

        $serch_match = str_replace(array('/','{','}'),array('\/','\{','\}'),implode("|", $search));
        if(preg_match("/(".$serch_match.")/",$inhalt)) {
            $change = true;
            $messages .= "Ändere Template = ".$dir."/".$file."\n";
            $inhalt = str_replace($search,$replace,$inhalt);
        }
    }
    if(substr($file,-(strlen(".css"))) == ".css") {
        $search_fildset = "\n".'fieldset#searchfieldset {'.
                            '   border:none;'.
                            '   margin:0px;'.
                            '   padding:0px;'.
                            '}';
        if(strpos($inhalt,'fieldset#searchfieldset') < 1) {
            $change = true;
            $messages .= "Ändere CSS = ".$dir."/".$file."\n";
            $inhalt .= $search_fildset;
        }
    }
    if(!check_utf8($inhalt)) {
        $change = true;
        $messages .= "Wandle Datei nach UTF-8= ".$dir."/".$file."\n";
    }
    if($convert and $change) {
        $fp_neu = fopen ($BASE_DIR.$dir.'/'.$file, "w");
        $inhalt = toUTF8($inhalt);
        fputs ($fp_neu, $inhalt);
        fclose($fp_neu);
    }
}

function changeToRawurl($dir = false) {
    global $BASE_DIR;
    global $files_to_utf8;
    global $specialchars;
    global $convert;
    global $messages_rename;

    if($dir === false) {
        $ordner = array("kategorien","galerien","layouts");
        foreach($ordner as $dirs) {
            changeToRawurl($dirs);
        }
        return;
    }
    $handle = opendir($BASE_DIR.$dir);
    while($file = readdir($handle)) {
        if(isValidDirOrFile($file)) {
            $new_name = $specialchars->replaceSpecialChars(rebuildOldSpecialChars($file),false);
            if(is_dir($BASE_DIR.$dir.'/'.$file)) {
                changeToRawurl($dir.'/'.$file);
                if($new_name != $file and $specialchars->replaceSpecialChars($new_name,false) != $file) {
                    $messages_rename .= "Rename = $dir/$file -> $dir/$new_name\n";
                    if($convert)
                        rename($BASE_DIR.$dir.'/'.$file, $BASE_DIR.$dir.'/'.$new_name);
                }
            } elseif(is_file($BASE_DIR.$dir.'/'.$file)) {
                $change_file = $new_name;
                if($new_name != $file and $specialchars->replaceSpecialChars($new_name,false) != $file) {
                    $messages_rename .= "Rename = $dir/$file -> $dir/$new_name\n";
                    if($convert)
                        rename($BASE_DIR.$dir.'/'.$file, $BASE_DIR.$dir.'/'.$new_name);
                } else $change_file = $file;
                if(!$convert)
                    $change_file = $file;
                foreach($files_to_utf8 as $ext) {
                    if(substr($new_name,-(strlen($ext))) == $ext) {
                        inhaltChange($change_file,$dir);
                        break;
                    }
                }
            }
        }
    }
    closedir($handle);
}


changeToRawurl();

$html = '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
$html .= "<html>\n";
$html .= "<head>";
$html .= '<meta http-equiv="Content-Type" content="text/html;charset='.$CHARSET.'">';
$html .= "<title>Update moziloCMS 1.11.2 nach 1.12</title>";
$html .= "</head>";
$html .= "<body>";
$html .= 'Scheint bis hierhin alles gut zu sein.<br>Unten Steht was alles gemacht wierd<br><br>';
$html .= '<a href="?convert=true">Update Starten</a>';
if(!$convert) {
$html .= '<br><br><pre style="font-size:12px;">';
$html .= $messages;
$html .= $messages_rename;
$html .= '</pre>';
}
$html .= "</body></html>";

echo $html;


?>