
<?php
/*
zeile 292
$EDIT_AREA = true;
if($EDIT_AREA and isset($post['action_data']['editsite'])) {
    require_once(BASE_DIR_ADMIN."edit_area/mozilo_edit_area.php");
    $html .= $editor_area_html;
} else
    $html .= '<script type="text/javascript" src="buttons.js"></script>';
*/




/*
<div id="content_highlight" style="height: 108px; visibility: visible; font-family: monospace; font-size: 12pt; line-height: 18px; width: 831px; ">
    <span class="mozilo">
        <span class="delimiters">[</span>
        <span class="msyntax">ueber1</span>
        <span class="operators">|</span>
        <span class="delimiters">]</span>
        <span class="delimiters">[</span>
        <span class="msyntax">ueber1</span>
        <span class="operators">|</span>
        <span class="delimiters">]</span>

</span></div>

<select id="area_font_size" onchange="javascript:editArea.execCommand(&quot;change_font_size&quot;)" filespecific="yes"><option value="-1">--Schriftgröße--</option><option value="8">8 pt</option><option value="9">9 pt</option><option value="10">10 pt</option><option value="11">11 pt</option><option value="12">12 pt</option><option value="14">14 pt</option></select>


<select id="syntax_selection" onchange="javascript:editArea.execCommand(&quot;change_syntax&quot;, this.value)" filespecific="yes"><option value="-1">--Syntax--</option><option value="mozilo">Mozilo</option><option value="css">Css</option><option value="html">Html</option><option value="js">Js</option><option value="php">Php</option><option value="robotstxt">Robotstxt</option><option value="xml">Xml</option></select>
alert(o.isWebKit+"=isWebKit "+o.isGecko+"=isGecko "+o.isMac+"=isMac "+o.isIE+"=isIE "+o.isOpera+"=isOpera "+o.isFirefox+"=isFirefox "+o.isCamino+"=isCamino "+o.isSafari+"=isSafari "+o.isChrome+"=isChromes ");
span.wmozilo{line-height:14px;}
alert(o);
*/
list($activ_plugins,$deactiv_plugins) = meditor_findPlugins();
$var_PluginsActiv = 'var moziloPluginsActiv = "";';
if(isset($activ_plugins) and count($activ_plugins) > 0) {
    rsort($activ_plugins);
    $var_PluginsActiv = 'var moziloPluginsActiv = "'.implode('|',$activ_plugins).'";';
}
$var_PluginsDeactiv = 'var moziloPluginsDeactiv = "";';
if(isset($deactiv_plugins) and count($deactiv_plugins) > 0) {
    rsort($deactiv_plugins);
    $var_PluginsDeactiv = 'var moziloPluginsDeactiv = "'.implode('|',$deactiv_plugins).'";';
}
$moziloPlace = makePlatzhalter(true);
foreach($moziloPlace as $key => $value) {
    $moziloPlace[$key] = substr($value,1,-1);
}
rsort($moziloPlace);
$var_Place = 'var moziloPlace = "'.implode('|',$moziloPlace).'";';

$var_UserSyntax = 'var moziloUserSyntax = "";';
$moziloUserSyntax  = $USER_SYNTAX->toArray();
if(count($moziloUserSyntax) > 0) {
    $moziloUserSyntax = array_keys($moziloUserSyntax);
    rsort($moziloUserSyntax);
    $var_UserSyntax = 'var moziloUserSyntax = "'.implode('|',$moziloUserSyntax).'";';
}

$smileys = new Properties(BASE_DIR_CMS."smileys/smileys.conf");
$moziloSmileys = $smileys->toArray();
$var_Smileys = 'var moziloSmileys = "";';
if(count($moziloSmileys) > 0) {
    $moziloSmileys = array_keys($moziloSmileys);
    rsort($moziloSmileys);
    $var_Smileys = 'var moziloSmileys = ":'.implode(':|:',$moziloSmileys).':";';
}

$editor_toggle_status = "onload";
if(isset($_POST['meditor_toggle_status']) and $_POST['meditor_toggle_status'] == "later") {
    $editor_toggle_status = "later";
}

# das mit edit_area_compressor.php hergestelte file was benutzt wird
$editor_area_script = '<script language="Javascript" type="text/javascript" src="edit_area/edit_area_full_mozilo.js"></script>';
# zum entwikeln es wird der inhalt von src/ benutzt
#$editor_area_script = '<script language="Javascript" type="text/javascript" src="edit_area/src/edit_area_loader.js"></script>';

$editor_area_html = '<script type="text/javascript" src="edit_area/mozilo_buttons.js"></script>
'.$editor_area_script.'
<script language="Javascript" type="text/javascript">
'.$var_PluginsActiv.'
'.$var_PluginsDeactiv.'
'.$var_Place.'
'.$var_UserSyntax.'
'.$var_Smileys.'
var moziloSyntax = "link|mail|kategorie|seite|absatz|datei|galerie|bildlinks|bildrechts|bild|----|links|zentriert|block|rechts|fett|kursiv|fettkursiv|unter|durch|ueber1|ueber2|ueber3|liste|numliste|liste1|liste2|liste3|html|tabelle|include|farbe";

var meditorID = "pagecontent";

editAreaLoader.init({
    id: meditorID // id of the textarea to transform      
    ,start_highlight: true
    ,display: "'.$editor_toggle_status.'"
    ,allow_resize: "y"
    ,allow_toggle: true
    ,cursor_position: "auto"
    ,word_wrap: true
    ,language: "'.substr($ADMIN_CONF->get("language"),0,2).'"
    ,font_size: 12
    ,replace_tab_by_spaces: 4
    ,syntax: "mozilo"
    ,toolbar: "search, | , undo, redo, | , select_font, | , change_smooth_selection, highlight, reset_highlight , | ,syntax_selection,help"
    ,EA_toggle_on_callback: "meditor_toggle_status_on"
    ,EA_toggle_off_callback: "meditor_toggle_status_off"
    ,EA_init_callback: "meditor_init_callback"
});
function meditor_init_callback(id) {
    var minput = document.createElement("input");
    minput.setAttribute("type", "hidden");
    minput.setAttribute("name", "meditor_toggle_status");
    minput.setAttribute("value", "onload");
    minput.setAttribute("id", "meditor_toggle_status_id");
    document.getElementsByName("form")[0].appendChild(minput);
};
function meditor_toggle_status_on(id) {
    document.getElementById("meditor_toggle_status_id").value = "onload";
};
function meditor_toggle_status_off(id) {
    document.getElementById("meditor_toggle_status_id").value = "later";
    document.getElementById(meditorID).removeAttribute("wrap", 0);
};
</script>';

function meditor_findPlugins() {
    $activ_plugins = array();
    $deactiv_plugins = array();
    // alle Plugins einlesen
    $dircontent = getDirAsArray(PLUGIN_DIR_REL,"dir");
    foreach ($dircontent as $currentelement) {
        # nach schauen ob das Plugin active ist
        if(file_exists(PLUGIN_DIR_REL.$currentelement."/plugin.conf")
            and file_exists(PLUGIN_DIR_REL.$currentelement."/index.php")) {
            $conf_plugin = new Properties(PLUGIN_DIR_REL.$currentelement."/plugin.conf",true);
            if($conf_plugin->get("active") == "false") {
                # array fuehlen mit deactivierte Plugin Platzhalter
                $deactiv_plugins[] = $currentelement;
            } elseif($conf_plugin->get("active") == "true") {
                $activ_plugins[] = $currentelement;
            }
            unset($conf_plugin);
        }
    }
    return array($activ_plugins,$deactiv_plugins);
}

?>