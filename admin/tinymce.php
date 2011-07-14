<?php
define(TINYMCE_IMAGELIST_FILE,"tiny_mce/lists/image_list.js");
define(TINYMCE_LINKLIST_FILE,"tiny_mce/lists/link_list.js");

if (!is_writable(TINYMCE_IMAGELIST_FILE))
 die ("TinyMCE-Hilfsdatei admin/".TINYMCE_IMAGELIST_FILE." ist nicht beschreibbar.");
if (!is_writable(TINYMCE_LINKLIST_FILE))
 die ("TinyMCE-Hilfsdatei admin/".TINYMCE_LINKLIST_FILE." ist nicht beschreibbar.");

/*
Java-Script zur Verwendung von TinyMCE zurückgeben
*/
function returnTinyMCEScripts($currentcat,$cssfile)
{
// Load the TinyMCE compressor class
require_once("tiny_mce/tiny_mce_gzip.php");
// Renders script tag with compressed scripts
TinyMCE_Compressor::renderTag(array(
    "url" => "tiny_mce/tiny_mce_gzip.php",
    "plugins" => "lists,style,table,advimage,advlink,inlinepopups,media,contextmenu,paste",
    "themes" => "advanced",
    "languages" => "de"
    //"disk_cache" => true
));

/*
Linklisten vorbereiten
*/
createTinyMCEImageList($currentcat);
createTinyMCELinkList();

return '<script language="javascript" type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
tinyMCE.init({
        // General options
        mode : "textareas",
        theme : "advanced",
        plugins : "lists,style,table,advimage,advlink,inlinepopups,media,contextmenu,paste",
        language: "de",
    
        // Theme options
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,undo,redo,|,link,unlink,image,cleanup,code",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,|,media",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,

        // System settings
        document_base_url : "../..",
        
        // Example content CSS (should be your site CSS)
        content_css : "'.$cssfile.'",

        // Drop lists for link/image/media/template dialogs
        external_link_list_url : "admin/tiny_mce/lists/link_list.js",
        external_image_list_url : "admin/tiny_mce/lists/image_list.js"
});
</script>
';
}

/*
Erstellt Bildliste für übergebene Kategorie.
Modifizierte implementation von returnOverviewSelectbox($type, $currentcat)
*/
function createTinyMCEImageList($currentcat) {
global $CONTENT_DIR_REL;
$elements = array();
$handle = opendir("$CONTENT_DIR_REL/$currentcat/dateien");
while (($file = readdir($handle))) {
 if (($file <> ".") && ($file <> "..") && is_file("$CONTENT_DIR_REL/$currentcat/dateien/$file") && has_inet_image_extention($file))
  array_push($elements, "kategorien/$currentcat/dateien/$file");
 }
closedir($handle);
sort($elements);
$list = "var tinyMCEImageList = new Array(";
for ($i = 0; $i < count($elements); $i++) {
 if ($i == 0) $list .= '["'.basename($elements[$i]).'", "'.$elements[$i].'"]';
 else $list .= ',["'.basename($elements[$i]).'", "'.$elements[$i].'"]';
 }
$list .= ');';
file_put_contents(TINYMCE_IMAGELIST_FILE,$list);
}

/*
Erstellt Linkliste für alle vorhandenen Kategorien, deren Unterseiten und Dateien
*/
function createTinyMCELinkList() {
global $CONTENT_DIR_REL;
$list = "var tinyMCELinkList = new Array(";
//alle Kategorien durchgehen
$handle = opendir("$CONTENT_DIR_REL");
while (($file = readdir($handle))) {
 if ($file[0] != '.') {
  $list .= '["'.substr($file,3).'", "index.php?cat='.$file.'"],'."\n";
  $list .= returnTinyMCELinkListOfPages($file); // Inhaltsseiten in der Kategorie
  $list .= returnTinyMCELinkListOfFiles($file); // Dateien in der Kategorie
  }
 }
closedir($handle);
$list .= returnTinyMCELinkListOfGalleries(); // Gallerien
//letztes Komma entfernen
$list = substr($list,0,strlen($list)-2);
$list .= ');';
file_put_contents(TINYMCE_LINKLIST_FILE,$list);
}

function returnTinyMCELinkListOfPages($currentcat){
global $CONTENT_DIR_REL;
$list = '';
$handle = opendir("$CONTENT_DIR_REL/$currentcat");
while (($file = readdir($handle))) {
 if (($file[0] != '.') && is_file("$CONTENT_DIR_REL/$currentcat/$file") && ((substr($file, strlen($file)-4, 4) == ".txt")||(substr($file, strlen($file)-4, 4) == ".hid")))
  $list .= '["&nbsp;'.substr(substr($file,3),0,strlen($file)-7).'", "index.php?cat='.$currentcat.'&page='.substr($file,0,strlen($file)-4).'"],'."\n";
 }
closedir($handle);
return $list;
}

function returnTinyMCELinkListOfFiles($currentcat){
global $CONTENT_DIR_REL;
$list = '';
if (file_exists("$CONTENT_DIR_REL/$currentcat/dateien")) {
  $handle = opendir("$CONTENT_DIR_REL/$currentcat/dateien");
  while (($file = readdir($handle))) {
   if (($file[0] != '.') && is_file("$CONTENT_DIR_REL/$currentcat/dateien/$file"))
    $list .= '["&nbsp;Datei:'.$file.'", "kategorien/'.$currentcat.'/dateien/'.$file.'"],'."\n";
   }
  closedir($handle);
}
return $list;
}

function returnTinyMCELinkListOfGalleries(){
global $GALLERIES_DIR_REL;
$list = '';
$handle = opendir($GALLERIES_DIR_REL);
while (($file = readdir($handle))) {
 if ($file[0] != '.')
  $list .= '["Gallerie:'.$file.'", "gallery.php?gal='.$file.'"],'."\n";
 }
 closedir($handle);
return $list;
}

function has_inet_image_extention($file)
{
$extention = strtolower(strstr($file,"."));
 if (
    ($extention=='.jpeg')||
    ($extention=='.jpg')||
    ($extention=='.png')||
    ($extention=='.gif')
    )
    return TRUE;
 else return FALSE;
}
?>