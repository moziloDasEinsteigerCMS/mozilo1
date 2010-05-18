<?php
# key = suche
$css_new['.aligncenter'] = '
/* -------------------------------------------------------- */
/* [zentriert|...] */
/* --------------- */
.aligncenter {
    text-align:center;
}';

$css_new['.alignleft'] = '
/* -------------------------------------------------------- */
/* [links|...] */
/* ----------- */
.alignleft {
    text-align:left;
}';

$css_new['.alignright'] = '
/* -------------------------------------------------------- */
/* [rechts|...] */
/* ------------ */
.alignright {
    text-align:right;
}';

$css_new['.alignjustify'] = '
/* -------------------------------------------------------- */
/* [block|...] */
/* ----------- */
.alignjustify {
    text-align:justify;
}';

$css_new['.tableofcontents'] = '
/* -------------------------------------------------------- */
/* {TABLEOFCONTENTS} */
/* ----------------- */
div.tableofcontents ul ul {
    /*padding-left:15px;*/
}
div.tableofcontents li.blind {
    list-style-type:none;
    list-style-image:none;
}';

$css_new['fieldset#searchfieldset'] = '
fieldset#searchfieldset {
   border:none;
   margin:0px;
   padding:0px;
}
';
$css_new['form#contact_form'] = '
/* -------------------------------------------------------- */
/* Kontaktformular */
/* --------------- */
form#contact_form {
}
table#contact_table {
}
table#contact_table td {
    vertical-align:top;
    padding:5px;
}
span#contact_errormessage{
    color:#880000;
    font-weight:bold;
}
span#contact_successmessage{
    color:#008800;
    font-weight:bold;
}
input#contact_name, input#contact_mail, input#contact_website {
    width:200px;
}
textarea#contact_message {
    width:200px;
}
input#contact_submit {
    width:200px;
}
';

$css_replace['div.imagesubtitle'] = 'span.imagesubtitle';
$css_replace['div.leftcontentimage'] = 'span.leftcontentimage';
$css_replace['div.rightcontentimage'] = 'span.rightcontentimage';
$css_replace['em.deadlink'] = 'span.deadlink';
$css_replace['em.highlight'] = 'span.highlight';
$css_replace["\n".'b {'] = "\n".'b.contentbold {';
$css_replace["\n".'i {'] = "\n".'i.contentitalic {';
$css_replace["\n".'u {'] = "\n".'u.contentunderlined {';
$css_replace["\n".'s {'] = "\n".'s.contentstrikethrough {';
$css_replace["\n".'b{'] = "\n".'b.contentbold {';
$css_replace["\n".'i{'] = "\n".'i.contentitalic {';
$css_replace["\n".'u{'] = "\n".'u.contentunderlined {';
$css_replace["\n".'s{'] = "\n".'s.contentstrikethrough {';

# ein ! = darf nicht einthalten sein
$css_messages['!imagesubtitle'] = '
Suchen sie bitte in ihrer {datei}
nach [bild|...], [bildlinks|...] und [bildrechts|...]
und Ersetzen es mit dem hier und passen es an
/* -------------------------------------------------------- */
/* [bild|...] */
/* ---------- */
img {
    border:none;
}
span.imagesubtitle {
    margin:3px 3px;
    text-align:justify;
    font-size:87%;
}
/* -------------------------------------------------------- */
/* [bildlinks|...] */
/* --------------- */
span.leftcontentimage {
    margin:6px 20px 6px 0px;
    float:left;
}
img.leftcontentimage {
}
/* -------------------------------------------------------- */
/* [bildrechts|...] */
/* ---------------- */
span.rightcontentimage {
    margin:6px 0px 6px 20px;
    float:right;
}
img.rightcontentimage {
}
';

$css_messages['em.bold'] = '
Suchen sie bitte in ihrer {datei}
nach em.bold und Ersetzen es mit dem hier und passen es an

b.contentbold {
}
';
$css_messages['em.italic'] = '
Suchen sie bitte in ihrer {datei}
nach em.italic und Ersetzen es mit dem hier und passen es an

i.contentitalic {
}
';
$css_messages['em.bolditalic'] = '
Achtung em.bolditalic gibt es nicht mehr sie müssen in den Inhaltseiten [fettkursiv|]
durch [fett|[kursiv|]] ersetzen und den style em.bolditalic { ???? }
in {datei} entfernen
';
$css_messages['em.underlined'] = '
Suchen sie bitte in ihrer {datei}
nach em.underlined und Ersetzen es mit dem hier und passen es an

u.contentunderlined {
}
';
$css_messages['em.crossed'] = '
Suchen sie bitte in ihrer {datei}
nach em.crossed und Ersetzen es mit dem hier und passen es an

s.contentstrikethrough {
}
';

$BASE_DIR = substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],'update/update.php'));
$CMS_DIR_NAME = "cms";
$ADMIN_DIR_NAME = "admin";
$BASE_DIR_CMS = $BASE_DIR.$CMS_DIR_NAME."/";
$BASE_DIR_ADMIN = $BASE_DIR.$ADMIN_DIR_NAME."/";
$OLD_CONF = "";
$old_conf_dir = "update";

$messages_error = "!!! ACHTUNG !!! hier gibts anscheinend ein Rechte Problem mit volgenden Dateien\n";
$messages_error_lengt = strlen($messages_error);
$messages_rename = "Dateien umbennen\n";
$messages_to_utf8 = "Inhalt wandeln nach UTF-8\n";
$messages_css = "Ergänze css Style in Dateien\n";
$messages_css_replace = "Ändere css Style\n";
$messages_css_messages = "";
$messages_html = "Ändere Template Dateien\n";
$messages_cp = "Kopiere Dateien\n";
$messages_conf = "Ändere conf Dateien\n";
$messages_newconf = "Erstelle conf Dateien\n";
$messages_more_css = "Es gibt im Template mehrere css Dateien\nLagern sie bitte die css Dateien wo keine moziloCMS Syntax\nenthalten oder zu Backup zwecken Angelegten Dateien aus\nund rufen das Update Script noch mal auf\n";

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
                'LOGIN_CONF' => $ADMIN_DIR_NAME.'/conf/logindata.conf',
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
    if(!is_file($BASE_DIR.$file))
        $messages_newconf .= "\t".$file."\n";
    if($convert or is_file($BASE_DIR.$file)) {
        ${$name} = new Properties($BASE_DIR.$file,true);
        # die muss schreiben geöffnet werden können
        if(isset(${$name}->properties['error'])) {
            die(${$name}->properties['error']);
        }
        $conf_tmp = ${$name}->properties;
    } else {
        ${$name} = makeDefaultConf(basename($file));
        $conf_tmp = ${$name};
    }
    foreach($conf_tmp as $key => $value) {
        if($key == 'error' or $key == 'readonly')
            continue;
        $old_key = $key;
        # logindata.conf muss gesondert behandelt werden weil in Contagt schonn name = existiert
        if($name == "LOGIN_CONF" and $key == "name")
            $old_key = "admin_name";
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
                $messages_conf .= "\t".$name."->set(".$key.",".$old_value.")\n";
                if($convert)
                    ${$name}->set($key,$old_value);
            }
        }
    }
    unset(${$name});
}

if($convert or is_file($BASE_DIR.$CMS_DIR_NAME.'/conf/downloads.conf')) {
    $DOWNLOAD_COUNTS = new Properties($BASE_DIR.$CMS_DIR_NAME.'/conf/downloads.conf',true);
    $conf_tmp = $DOWNLOAD_COUNTS->properties;
} else {
    $DOWNLOAD_COUNTS = makeDefaultConf("downloads.conf");
    $conf_tmp = $DOWNLOAD_COUNTS;
}
if(!is_file($BASE_DIR.$CMS_DIR_NAME.'/conf/downloads.conf'))
    $messages_newconf .= "\t".$CMS_DIR_NAME.'/conf/downloads.conf'."\n";
$DOWNLOAD_COUNTS_OLD = new Properties($BASE_DIR.'/update/downloads.conf',true);
foreach($DOWNLOAD_COUNTS_OLD->properties as $key => $value) {
    if($key == 'error' or $key == 'readonly')
        continue;
    $key = $specialchars->replaceSpecialChars(rebuildOldSpecialChars($key),false);
    $key = str_replace('%3A',':',$key);
    if(!isset($conf_tmp[$key])) {
        $messages_conf .= "\tDOWNLOAD_COUNTS->set(".$key.",".$value.")\n";
        if($convert)
            $DOWNLOAD_COUNTS->set($key,$value);
    }
}

function makeOldConf($old_conf_dir) {
    global $BASE_DIR;
    global $ADMIN_DIR_NAME;
    global $CMS_DIR_NAME;
    global $OLD_CONF;
    global $convert;
    global $messages_cp;
    global $messages_conf;

    $inhalt = "";
    $handle = opendir($BASE_DIR.$old_conf_dir);
    while($file = readdir($handle)) {
        if(!isValidDirOrFile($file)) continue;
        if($file == 'old.conf' or $file == 'README.txt' or $file == 'log.txt') {
            continue;
        } elseif($file == 'syntax.conf' and !file_exists($BASE_DIR.$CMS_DIR_NAME.'/conf/syntax.conf')) {
            $messages_cp .= "\t".$old_conf_dir.'/'.$file." -> ".$CMS_DIR_NAME.'/conf/syntax.conf'."\n";
            if($convert)
                copy($BASE_DIR.$old_conf_dir.'/'.$file,$BASE_DIR.$CMS_DIR_NAME.'/conf/syntax.conf');
            continue; 
        } else {
            $fp = fopen ($BASE_DIR.$old_conf_dir.'/'.$file, "r");
            # logindata.conf muss gesondert behandelt werden weil in Contagt schonn name = existiert
            if($file == 'logindata.conf') {
                $loginconf = fread($fp, filesize($BASE_DIR.$old_conf_dir.'/'.$file));
                $inhalt .= str_replace("name =","admin_name =",$loginconf);
            } else
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
    global $messages_to_utf8;
    global $messages_css;
    global $messages_html;
    global $messages_error;
    global $messages_css_replace;
    global $messages_css_messages;
    global $css_new;
    global $css_replace;
    global $css_messages;
    $change = false;
    $inhalt = "";
    if($fp = @fopen ($BASE_DIR.$dir.'/'.$file, "r")) {
        $inhalt = fread($fp, filesize($BASE_DIR.$dir.'/'.$file));
        fclose($fp);
    }
    if(!check_utf8($inhalt)) {
        $change = true;
        $messages_to_utf8 .= "\t".$dir."/".$file."\n";
        $inhalt = toUTF8($inhalt);
    }
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
            $messages_html .= "\t".$dir."/".$file."\n";
            $inhalt = str_replace($search,$replace,$inhalt);
        }
    }
    if(substr($file,-(strlen(".css"))) == ".css") {
        $css_new_inhalt = false;
        foreach($css_new as $search => $css_inhalt) {
            if(strpos($inhalt,$search) < 1) {
                if(!$css_new_inhalt) {
                    $inhalt .= "\n\n/*Ab hier Änderungen vom Update Script*/\n";
                    $css_new_inhalt = true;
                }
                $inhalt .= $css_inhalt;
            }
        }
        $first = true;
        foreach($css_replace as $search => $replace) {
            if(strpos($inhalt,$search) > 0) {
                if($first) {
                    $messages_css_replace .= "\tErsetze in Datei ".$dir."/".$file."\n";
                    $first = false;
                }
                $inhalt = str_replace($search,$replace,$inhalt);
                $messages_css_replace .= "\t\t\"".trim($search)."\" durch \"".trim($replace)."\"\n";
                $change = true;
            }
        }
        foreach($css_messages as $search => $messages) {
            $messages = str_replace("{datei}",$dir."/".$file,$messages);
            if(substr($search,0,1) == "!") {
                if(strpos($inhalt,substr($search,1)) < 1) {
                    $messages_css_messages .= $messages."\n";
                }
            } else {
                if(strpos($inhalt,$search) > 0) {
                    $messages_css_messages .= $messages."\n";
                }
            }
        }
        if($css_new_inhalt) {
            $change = true;
            $messages_css .= "\t".$dir."/".$file."\n";
        }
    }
    if($convert and $change) {
        if($fp_neu = @fopen($BASE_DIR.$dir.'/'.$file, "w")) {
            fputs ($fp_neu, $inhalt);
            fclose($fp_neu);
        } else
            $messages_error .= "\t".$BASE_DIR.$dir.'/'.$file."\n";
    }
}

function changeToRawurl($dir = false) {
    global $BASE_DIR;
    global $files_to_utf8;
    global $specialchars;
    global $convert;
    global $messages_rename;
    global $messages_error;
    global $messages_more_css;

    if($dir === false) {
        $ordner = array("kategorien","galerien","layouts");
        foreach($ordner as $dirs) {
            changeToRawurl($dirs);
        }
        return;
    }
    $handle = opendir($BASE_DIR.$dir);
    $files = array();
    while($file = readdir($handle)) {
        if(!isValidDirOrFile($file))
            continue;
        if(substr($file,-4) == ".css")
            $css_file[] = $file;
        $files[] = $file;
    }
    foreach($files as $file) {
        $error = false;
        $new_name = $specialchars->replaceSpecialChars(rebuildOldSpecialChars($file),false);
        if(is_dir($BASE_DIR.$dir.'/'.$file)) {
            changeToRawurl($dir.'/'.$file);
            if($new_name != $file and $specialchars->replaceSpecialChars($new_name,false) != $file) {
                $messages_rename .= "\t$dir/$file -> $dir/$new_name\n";
                if($convert)
                    @rename($BASE_DIR.$dir.'/'.$file, $BASE_DIR.$dir.'/'.$new_name);
                if($convert and !is_dir($BASE_DIR.$dir.'/'.$new_name)) {
                    $messages_error .= "\t".$BASE_DIR.$dir.'/'.$file."\n";
                    $error = true;
                }
            }
        } elseif(is_file($BASE_DIR.$dir.'/'.$file)) {
            $change_file = $new_name;
            if($new_name != $file and $specialchars->replaceSpecialChars($new_name,false) != $file) {
                $messages_rename .= "\t$dir/$file -> $dir/$new_name\n";
                if($convert)
                    @rename($BASE_DIR.$dir.'/'.$file, $BASE_DIR.$dir.'/'.$new_name);
                if($convert and !is_file($BASE_DIR.$dir.'/'.$new_name)) {
                    $messages_error .= "\t".$BASE_DIR.$dir.'/'.$file."\n";
                    $error = true;
                }
            } else $change_file = $file;
            if(!$convert)
                $change_file = $file;
            foreach($files_to_utf8 as $ext) {
                if(!$error and substr($new_name,-(strlen($ext))) == $ext) {
                    if(isset($css_file) and count($css_file) > 1 and in_array($change_file,$css_file)) {
                        $messages_more_css .= "\t$dir/$change_file\n";
                        continue;
                    }
                    inhaltChange($change_file,$dir);
                    break;
                }
            }
        }
    }
    closedir($handle);
}


changeToRawurl();
$html = "";
$html .= '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
$html .= "<html>\n";
$html .= "<head>";
$html .= '<meta http-equiv="Content-Type" content="text/html;charset='.$CHARSET.'">';
$html .= "<title>Update moziloCMS 1.11.2 nach 1.12</title>";
$html .= "</head>";
$html .= "<body>";
$html .= 'Scheint bis hierhin alles gut zu sein.<br>Unten Steht was alles gemacht wierd<br><br>';
$html .= '<a href="?convert=true">Update Starten</a>';
$html .= '<br><br><pre style="font-size:12px;">';
$logtext = "";
if($messages_error_lengt != strlen($messages_error)) {
    $logtext = $messages_error."\n";
    $html .= $messages_error."\n";
} else {
    if(strpos($messages_to_utf8,"\t") > 0)
        $logtext .= $messages_to_utf8."\n";
    if(strpos($messages_css,"\t") > 0)
        $logtext .= $messages_css."\n";
    if(strpos($messages_css_replace,"\t") > 0)
        $logtext .= $messages_css_replace."\n";
    if(strpos($messages_html,"\t") > 0)
        $logtext .= $messages_html."\n";
    if(strpos($messages_cp,"\t") > 0)
        $logtext .= $messages_cp."\n";
    if(strpos($messages_newconf,"\t") > 0)
        $logtext .= $messages_newconf."\n";
    if(strpos($messages_conf,"\t") > 0)
        $logtext .= $messages_conf."\n";
    if(strpos($messages_rename,"\t") > 0)
        $logtext .= $messages_rename."\n";
    if(strpos($messages_more_css,"\t") > 0)
        $logtext .= $messages_more_css."\n";
    if(!empty($messages_css_messages)) {
        $logtext .= "\n\n".'!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
ACHTUNG ab Hier gehtz von Hand weiter
Die volgenden aussagen müssen nicht zutreffen
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n\n";
        $logtext .= $messages_css_messages."\n";
    }
}
if(empty($logtext))
    $logtext = "Es braucht nichts Aktualiesiert werden";
$html .= $logtext."\n";

if($convert) {
    $log = fopen($BASE_DIR.'/update/log.txt', "a+");
    fputs ($log, date('j.n.Y H:i:s')." ###################\n".$logtext."\n\n");
    fclose($log);
}
@unlink($BASE_DIR.'/update/old.conf');
$html .= "&nbsp;</pre></body></html>";

echo $html;


?>