<?php

$error_color = array('check_name' => '#CCFFCC',
                'check_url' => '#CCE9F9',
                'check_digit' => '#CCE6FF',
                'check_new_position_empty' => '#E6CCFF',
                'check_is_file' => '#FFCC99',
                'check_name_too_long' => '#FFFF99',
                'check_name_exist' => '#99FF99',
                'check_new_name_empty' => '#FF6666',
                'check_too_many_pages' => '#FFFF33',
                'check_doubles_digit_copy_move' => '#4D9900',
                'check_copy_same_cat' => '#33FFFF',
                'files_error_exists' => '#33FFFF',
                'files_error_name' => '#33FFFF',
                'files_error_wrongext' => '#33FFFF',
                'php_error' => '#33FFFF',
                'check_doubles_name_cat' => '#33FFFF',
                'check_doubles_name_page' => '#33FFFF',
                'check_doubles_digit_same_cat' => '#FFCCCC');

# beim ersten aufruf von cat oder page mussen einige variable vorbereitet werden
function makePostCatPageReturnVariable($CONTENT_DIR_REL,$pages = false) {
    global $EXT_LINK;
    global $error_color;

    $max_cat_page = 100;

    $cat_array = getDirs("$CONTENT_DIR_REL",true);

    if(count($cat_array) > $max_cat_page) {
        $post['error_messages']['check_too_many_categories'][] = NULL;
    }
    # wenn es nur cat ist wird $cat_array = cat $page_array = $cat_array
    if($pages === false) {
        sort($cat_array);
        # Wichtig wegen new Kategorie
        $cat_array[$max_cat_page] = NULL;
        $page_array = $cat_array;
        unset($cat_array);
        $cat_array['cat'] = 'cat';
    }
    $test_doubles_position_cat = array();
    $test_doubles_name_cat = array();
    foreach($cat_array as $cat) {
        $test_doubles_position[$cat] = array();
        $test_doubles_name[$cat] = array();
        # nach dopelten Positionen und namen suchen wichtig bei FTP
        if($cat != 'cat' and strpos($cat,$EXT_LINK) === false) {
            $cat_position = substr($cat,0,2);
            if(in_array($cat_position,$test_doubles_position_cat)) {
                $post['error_messages']['check_doubles_position_cat'][] = $cat_position;
            }
            $test_doubles_position_cat[] = $cat_position;
            $cat_name = substr($cat,3);
            if(in_array($cat_name,$test_doubles_name_cat)) {
                foreach(array_keys($test_doubles_name_cat,$cat_name) as $pos_doubles) {
                    $post[sprintf("%02d",$pos_doubles).'_'.$cat_name]['error_html']['cat_name'] = 'style="background-color:'.$error_color['check_doubles_name_cat'].';" ';
                 }
                 $post[$cat]['error_html']['cat_name'] = 'style="background-color:'.$error_color['check_doubles_name_cat'].';" ';
                 $post['error_messages']['check_doubles_name_cat']['color'] = $error_color['check_doubles_name_cat'];
            }
            $test_doubles_name_cat[sprintf("%1d",$cat_position)] = $cat_name;
        }
        if($pages === true) {
            if(substr($cat,-(strlen($EXT_LINK))) == $EXT_LINK) {
                continue;
            }
            $page_array = getFiles("$CONTENT_DIR_REL/".$cat, "");
            if(!isset($post[$cat]['error_html']['cat_name'])) {
                $post[$cat]['error_html']['cat_name'] = NULL;
            }
            if(count($page_array) > $max_cat_page) {
                $post['error_messages']['check_too_many_pages']['color'] = $error_color['check_too_many_pages'];
                $post[$cat]['error_html']['cat_name'] = 'style="background-color:'.$error_color['check_too_many_pages'].';" ';
            }
            sort($page_array);
            # Wichtig wegen new Inhaltseite
            $page_array[$max_cat_page] = NULL;
            # die möglichkeit nach action die sachen aufzucklapen (editsite)
            $post[$cat]['error_html']['display_cat'] = NULL;
        }
        foreach($page_array as $pos => $file) {
            $position = substr($file,0,2);
            # New cat page
            if($pos == $max_cat_page) {
                $file = $EXT_LINK;
                $position = NULL;
            } else {
                $pos = sprintf("%1d",substr($file,0,2));
            }

            $post[$cat]['error_html']['name'][$pos] = NULL;
            $post[$cat]['error_html']['display'][$pos] = NULL;
            $post[$cat]['error_html']['new_position'][$pos] = NULL;
            $post[$cat]['error_html']['new_name'][$pos] = NULL;
            $post[$cat]['position'][$pos] = $position;
            $post[$cat]['new_position'][$pos] = $post[$cat]['position'][$pos];
            $post[$cat]['new_name'][$pos] = NULL;
            $post[$cat]['copy'][$pos] = NULL;
            # wird nur bei Inhaltseiten gebraucht (move Inhaltseiten in andere Kategory)
            if($pages === true) {
                $post[$cat]['new_cat'][$pos] = NULL;
                $post[$cat]['ext'][$pos] = substr($file,-(strlen($EXT_LINK)));
            }
            # file ist ein LINK
            if(strpos("tmp".$file,$EXT_LINK)) { # "tmp" deshalb damit strpos() $EXT_LINK findet (strpos = schneller)
                $post[$cat]['checked_selv'][$pos] = NULL;
                $post[$cat]['checked_blank'][$pos] = ' checked="checked"';
                if(strpos($file,"-_self-")) {
                    $exlink = explode("-_self-",$file);
                    $post[$cat]['checked_selv'][$pos] = ' checked="checked"';
                    $post[$cat]['checked_blank'][$pos] = NULL;
                    $post[$cat]['target'][$pos] = '-_self-';
                }
                elseif(strpos($file,"-_blank-")) {
                    $exlink = explode("-_blank-",$file);
                    $post[$cat]['target'][$pos] = '-_blank-';
                }
                if($pos != $max_cat_page) {
                    $post[$cat]['name'][$pos] = substr($exlink[0],3);
                    $post[$cat]['url'][$pos] = substr($exlink[1],0,-(strlen($EXT_LINK)));
                    if($pages === true) {
                        $post[$cat]['ext'][$pos] = $EXT_LINK;
                    }
                }
                $post[$cat]['new_url'][$pos] = NULL;
                $post[$cat]['error_html']['new_url'][$pos] = NULL;
            # file ist eine Kategorie oder Inhaltseite
            } else {
                if($pages === true) {
                    $post[$cat]['name'][$pos] = substr($file,3,-(strlen($EXT_LINK)));
                } else {
                    $post[$cat]['name'][$pos] = substr($file,3);
                }
            }
            # nach dopelten Positionen und Namen suchen wichtig bei FTP
            if(in_array($position,$test_doubles_position[$cat])) {
                if($cat != 'cat') {
                    $post['error_messages']['check_doubles_position_page'][$pos] = substr($cat,3)." -&gt; ".$position;
                } else {
                    $post['error_messages']['check_doubles_position_cat'][$pos] = $position;
                }
            }
            $test_doubles_position[$cat][] = $position;
            $file_name = substr($file,3);
            if(strpos($file,$EXT_LINK) === false) {
                if(in_array($file_name,$test_doubles_name[$cat])) {
                    if($cat != 'cat') {
                        foreach(array_keys($test_doubles_name[$cat],$file_name) as $pos_doubles) {
                            $post[$cat]['error_html']['name'][$pos_doubles] = 'style="background-color:'.$error_color['check_doubles_name_page'].';" ';
                        }
                        $post[$cat]['error_html']['name'][$pos] = 'style="background-color:'.$error_color['check_doubles_name_page'].';" ';
                        $post[$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                        $post['error_messages']['check_doubles_name_page']['color'] = $error_color['check_doubles_name_page'];
                    } else {
                        foreach(array_keys($test_doubles_name[$cat],$file_name) as $pos_doubles) {
                            $post[$cat]['error_html']['name'][$pos_doubles] = 'style="background-color:'.$error_color['check_doubles_name_cat'].';" ';
                        }
                        $post[$cat]['error_html']['name'][$pos] = 'style="background-color:'.$error_color['check_doubles_name_cat'].';" ';
                        $post['error_messages']['check_doubles_name_cat']['color'] = $error_color['check_doubles_name_cat'];
                    }
                }
                $test_doubles_name[$cat][$pos] = $file_name;
            }
        }
    }
    return $post;
}

# die daten die von cat oder page übermitelt wurden überprüfen
function checkPostCatPageReturnVariable($CONTENT_DIR_REL) {
    global $EXT_LINK;
    global $specialchars;
    global $ALLOWED_SPECIALCHARS_REGEX;
    global $error_color;

    $max_cat_page = 100;

    foreach ($_POST['categories'] as $cat => $tmp) {
        # die möglichkeit nach action die sachen aufzucklapen (editsite)
        $post[$cat]['error_html']['display_cat'] = NULL;
        if(isset($post[$cat]['error_html']['display_cat'])) {
            $post[$cat]['error_html']['display_cat'] = 'style="display:block;" ';
        }
        if(count($_POST['categories'][$cat]['position']) > $max_cat_page + 1) {
            $post['error_messages']['check_too_many_categories'][] = NULL;
        }
        foreach ($_POST['categories'][$cat]['position'] as $pos => $tmp) {# str_replace('/','%2F',)
            # erstmal die Sonderzeichen umwandeln
            if(isset($_POST['categories'][$cat]['name'][$pos])) {
                $_POST['categories'][$cat]['name'][$pos] = $specialchars->replaceSpecialChars($_POST['categories'][$cat]['name'][$pos],false);
            }
            if(isset($_POST['categories'][$cat]['new_name'][$pos])) {
                $_POST['categories'][$cat]['new_name'][$pos] = $specialchars->replaceSpecialChars($_POST['categories'][$cat]['new_name'][$pos],false);
            }
            if(isset($_POST['categories'][$cat]['url'][$pos])) {
                $_POST['categories'][$cat]['url'][$pos] = str_replace('/','%2F',$specialchars->replaceSpecialChars($_POST['categories'][$cat]['url'][$pos],false));
            }
            if(isset($_POST['categories'][$cat]['new_url'][$pos])) {
                $_POST['categories'][$cat]['new_url'][$pos] = str_replace('/','%2F',$specialchars->replaceSpecialChars($_POST['categories'][$cat]['new_url'][$pos],false));
            }
            if(isset($_POST['categories'][$cat]['new_cat'][$pos])) {
                $_POST['categories'][$cat]['new_cat'][$pos] = $specialchars->replaceSpecialChars($_POST['categories'][$cat]['new_cat'][$pos],false);
            }
            $post[$cat]['error_html']['cat_name'] = NULL;
            if(count($_POST['categories'][$cat]['position'][$pos]) > $max_cat_page + 1) {
                $post['error_messages']['check_too_many_pages']['color'] = $error_color['check_too_many_pages'];
                $post['error_messages']['check_too_many_pages'][] = $cat;
                $post[$cat]['error_html']['cat_name'] = 'style="background-color:'.$error_color['check_too_many_pages'].';" ';
            }

            if($cat == 'cat') {
                $post[$cat]['ext'][$pos] = NULL;
            }
            $post[$cat]['error_html']['display'][$pos] = NULL;
            if(isset($post[$cat]['error_html']['display'][$pos])) {
                $post[$cat]['error_html']['display'][$pos] = 'style="display:block;" ';
            }
            # Neue Kategorie oder Inhaltseite Position oder Name dürfen nicht lehr sein
            if($pos  == $max_cat_page) {
                if(empty($_POST['categories'][$cat]['new_position'][$pos]) and !empty($_POST['categories'][$cat]['new_name'][$pos])) {
                    $post['error_messages']['check_new_position_empty']['color'] = $error_color['check_new_position_empty'];
                    $post['error_messages']['check_new_position_empty'][] = NULL;
                    $post[$cat]['error_html']['new_position'][$pos] = 'style="background-color:'.$error_color['check_new_position_empty'].';" ';
                }
                if(!empty($_POST['categories'][$cat]['new_position'][$pos]) and empty($_POST['categories'][$cat]['new_name'][$pos])) {
                    $post['error_messages']['check_new_name_empty']['color'] = $error_color['check_new_name_empty'];
                    $post['error_messages']['check_new_name_empty'][] = NULL;
                    $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_new_name_empty'].';" ';
                }
                if(empty($_POST['categories'][$cat]['new_position'][$pos]) and empty($_POST['categories'][$cat]['new_name'][$pos]) and !empty($_POST['categories'][$cat]['new_url'][$pos])) {
                    $post['error_messages']['check_new_position_empty']['color'] = $error_color['check_new_position_empty'];
                    $post['error_messages']['check_new_position_empty'][] = NULL;
                    $post[$cat]['error_html']['new_position'][$pos] = 'style="background-color:'.$error_color['check_new_position_empty'].';" ';
                    $post['error_messages']['check_new_name_empty'][] = NULL;
                    $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_new_name_empty'].';" ';
                }
                $_POST['categories'][$cat]['new_cat'][$pos] = NULL;
            }
            $post[$cat]['copy'][$pos] = NULL;
            if(isset($_POST['categories'][$cat]['copy'][$pos])) {
                $post[$cat]['copy'][$pos] = ' checked';
            }

            if(isset($_POST['categories'][$cat]['position'][$pos])) {
                $post[$cat]['position'][$pos] = $_POST['categories'][$cat]['position'][$pos];
            }
            if(!isset($post[$cat]['error_html']['new_position'][$pos])) {
                $post[$cat]['error_html']['new_position'][$pos] = NULL;
            }

            if(isset($_POST['categories'][$cat]['new_position'][$pos])) {
                # nach doppelten new_position suchen auser new cat page oder move copy page
                $doubles = array_keys($_POST['categories'][$cat]['new_position'],sprintf("%02d",$pos));
                $doubles_count = count($doubles);
                if(in_array($max_cat_page,$doubles)) {
                    $doubles_count--;
                }
                if(in_array(sprintf("%02d",$pos),$doubles)) {
                    $doubles_count--;
                }
                if($doubles_count > 1) {
                    foreach($doubles as $tmp => $error_posi) {
                        $post['error_messages']['check_doubles_digit_same_cat']['color'] = $error_color['check_doubles_digit_same_cat'];
                        $post['error_messages']['check_doubles_digit_same_cat'][] = NULL;
                        $post[$cat]['error_html']['new_position'][$error_posi] = 'style="background-color:'.$error_color['check_doubles_digit_same_cat'].';" ';
                    }
                }
                # Neue Page Cat
                if(empty($_POST['categories'][$cat]['new_position'][$pos]) and empty($_POST['categories'][$cat]['position'][$pos])) {
                    $post[$cat]['new_position'][$pos] = NULL;
                # Neue Position auf Zahl Prüfen und 2stelig machen
                } elseif(ctype_digit($_POST['categories'][$cat]['new_position'][$pos])) {
                    $post[$cat]['new_position'][$pos] = sprintf("%02d",$_POST['categories'][$cat]['new_position'][$pos]);
                } else {
#                    $post[$cat]['error']['invalid_digit'][$pos] = true;
                    $post['error_messages']['check_digit']['color'] = $error_color['check_digit'];
                    $post['error_messages']['check_digit'][] = NULL;
                    $post[$cat]['error_html']['new_position'][$pos] = 'style="background-color:'.$error_color['check_digit'].';" ';
                    $post[$cat]['new_position'][$pos] = trim($_POST['categories'][$cat]['new_position'][$pos]);
                }
            }
            # wenn es keine Kategorie ist 0 ansonsten $ext_len = strlen($post[$cat]['ext'][$pos])
            $ext_len = 0;
            if(isset($_POST['categories'][$cat]['ext'][$pos])) {
                $post[$cat]['ext'][$pos] = $_POST['categories'][$cat]['ext'][$pos];
                $ext_len = strlen($post[$cat]['ext'][$pos]);
            }
            $name_len = 0;
            $post[$cat]['name'][$pos] = NULL;
            if(isset($_POST['categories'][$cat]['name'][$pos])) {
                $post[$cat]['name'][$pos] = $_POST['categories'][$cat]['name'][$pos];
                $name_len = strlen($post[$cat]['name'][$pos]);
            }
            if(!isset($post[$cat]['error_html']['new_name'][$pos])) {
                 $post[$cat]['error_html']['new_name'][$pos] = NULL;
            }
            if(!isset($post[$cat]['error_html']['name'][$pos])) {
                $post[$cat]['error_html']['name'][$pos] = NULL;
            }
            if(isset($_POST['categories'][$cat]['new_name'][$pos])) {
                $post[$cat]['new_name'][$pos] = NULL;
                # Cat Page Umbenen bei Page in gleicher Cat
                if(strlen(trim($_POST['categories'][$cat]['new_name'][$pos])) > "0") {
                    $post[$cat]['new_name'][$pos] = $_POST['categories'][$cat]['new_name'][$pos];
                   $newname = $post[$cat]['new_name'][$pos];
                    $name_len = strlen($newname);
                    if(!preg_match($ALLOWED_SPECIALCHARS_REGEX, $newname) or stristr($newname,"%5E")) {
                        $post['error_messages']['check_name']['color'] = $error_color['check_name'];
                        $post['error_messages']['check_name'][] = NULL;
                        $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_name'].';" ';
                    }
                    $test_cat = $cat;
                    if(isset($_POST['categories'][$cat]['new_cat'][$pos])) {
                        $test_cat = $_POST['categories'][$cat]['new_cat'][$pos];
                    }
                    if(!isset($_POST['categories'][$cat]['url'][$pos]) and empty($_POST['categories'][$cat]['new_url'][$pos]) and $test_cat == $cat) {
                        if(isset($_POST['categories'][$cat]['name']) and is_array($_POST['categories'][$cat]['name']) and count(array_keys($_POST['categories'][$cat]['name'], $newname)) > 0) {
                            $tmp_pos = array_keys($_POST['categories'][$cat]['name'], $newname);
                            # Neu Name gipts schonn auser wenn der Name auch Umbenant wird
                            if(empty($_POST['categories'][$cat]['new_name'][$tmp_pos[0]])) {
                                $post[$cat]['error_html']['name'][$tmp_pos[0]] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                                $post['error_messages']['check_name_exist']['color'] = $error_color['check_name_exist'];
                                $post['error_messages']['check_name_exist'][] = NULL;
                                $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                            }
                            # wenn Neu Name mehr wie einmal in Neu Nmamen ist
                            if(count(array_keys($_POST['categories'][$cat]['new_name'], $_POST['categories'][$cat]['new_name'][$pos])) > 1) {
                                $post['error_messages']['check_name_exist']['color'] = $error_color['check_name_exist'];
                                $post['error_messages']['check_name_exist'][] = NULL;
                                $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                            }
                        }
                    } else {
                        if(!isset($post[$cat]['error_html']['new_name'][$pos])) {
                            $post[$cat]['error_html']['new_name'][$pos] = NULL;
                        }
                    }
                }
            }
            $url_len = 0;
            if(isset($_POST['categories'][$cat]['url'][$pos])) {
                $post[$cat]['url'][$pos] = $_POST['categories'][$cat]['url'][$pos];
                $url_len = strlen($post[$cat]['url'][$pos]);
                # urls haben immer eine ext.
                $ext_len = strlen($EXT_LINK);
            }
            $post[$cat]['error_html']['new_url'][$pos] = NULL;
            $post[$cat]['new_url'][$pos] = NULL;
            if(isset($_POST['categories'][$cat]['new_url'][$pos])) {
                $post[$cat]['new_url'][$pos] = trim(str_replace(array("\r\n","\n","\r"),'',$_POST['categories'][$cat]['new_url'][$pos]));
                if(strlen($post[$cat]['new_url'][$pos]) > "0") {
                    $url_len = strlen($post[$cat]['new_url'][$pos]);
                    # urls haben immer eine ext.
                    $ext_len = strlen($EXT_LINK);
                    if(!preg_match($ALLOWED_SPECIALCHARS_REGEX, $post[$cat]['new_url'][$pos]) or stristr($post[$cat]['new_url'][$pos],"%5E")) {
                        $post['error_messages']['check_url']['color'] = $error_color['check_url'];
                        $post['error_messages']['check_url'][] = NULL;
                        $post[$cat]['error_html']['new_url'][$pos] = 'style="background-color:'.$error_color['check_url'].';" ';
                    }
                }
            }
            $target_len = 0;
            $post[$cat]['target'][$pos] = NULL;
            if(isset($_POST['categories'][$cat]['target'][$pos])) {
                $post[$cat]['target'][$pos] = $_POST['categories'][$cat]['target'][$pos];
                $target_len = 8; # -_blank-
                if($post[$cat]['target'][$pos] == "-_self-") {
                    $target_len = 7; # -_self-
                }
            }
            if(isset($_POST['categories'][$cat]['new_target'][$pos])) {
                $post[$cat]['checked_selv'][$pos] = '';
                $post[$cat]['checked_blank'][$pos] = ' checked="checked"';
                $target_len = 8; # -_blank-
                if($_POST['categories'][$cat]['new_target'][$pos] == "-_self-") {
                    $target_len = 7; # -_self-
                    $post[$cat]['checked_selv'][$pos] = ' checked="checked"';
                    $post[$cat]['checked_blank'][$pos] = '';
                }
                $post[$cat]['new_target'][$pos] = NULL;
                if($_POST['categories'][$cat]['new_target'][$pos] != $post[$cat]['target'][$pos]) {
                    $post[$cat]['new_target'][$pos] = $_POST['categories'][$cat]['new_target'][$pos];
                }
            }

            # hab ich hier, drin um bei copymove nicht extra noch eine foreach zu machen
            # ist dafür zuständig wenn Inhaltseite in andere Kategorie verschoben wird
            if(isset($_POST['categories'][$cat]['new_cat'][$pos])) {
                $post[$cat]['new_cat'][$pos] = $_POST['categories'][$cat]['new_cat'][$pos];
                if($post[$cat]['new_cat'][$pos] != $cat or !empty($post[$cat]['copy'][$pos])) {
                    $post['move_copy']['source']['cat'][$pos] = $cat;
                    $post['move_copy']['source']['position'][$pos] = $post[$cat]['position'][$pos];
                    $post['move_copy']['source']['name'][$pos] = $post[$cat]['name'][$pos];
                    $move_copy_test['new_position'][] = $post[$cat]['new_position'][$pos];
                    if(!empty($post[$cat]['copy'][$pos])) {
                        $post['move_copy']['source']['copy'][$pos] = $post[$cat]['copy'][$pos];
                    }
                    $post['move_copy']['desti']['cat'][$pos] = $post[$cat]['new_cat'][$pos];
                    $post['move_copy']['desti']['new_position'][$pos] = $post[$cat]['new_position'][$pos];
                    $post['move_copy']['desti']['ext'][$pos] = $post[$cat]['ext'][$pos];

                    $move_copy_test['new_cat'][] = $post[$cat]['new_cat'][$pos];
                    $move_copy_test['org_cat'][] = $cat;
                    if(!empty($post[$cat]['new_name'][$pos])) {
                        $post['move_copy']['desti']['name'][$pos] = $post[$cat]['new_name'][$pos];
                        $move_copy_test['name'][] = $post[$cat]['new_name'][$pos];
                        $move_copy_test['pos_org_name'][] = $pos;
                    } else {
                        $post['move_copy']['desti']['name'][$pos] = $post[$cat]['name'][$pos];
                        $move_copy_test['name'][] = $post[$cat]['name'][$pos];
                        $move_copy_test['pos_org_name'][] = $pos;
                    }
                    if(isset($post[$cat]['url'][$pos])) {
                        $post['move_copy']['source']['name'][$pos] .= $post[$cat]['target'][$pos].$post[$cat]['url'][$pos];

                        $new_url = $post[$cat]['new_target'][$pos].$post[$cat]['url'][$pos];
                        if(!empty($post[$cat]['new_url'][$pos])) {
                            $new_url = $post[$cat]['new_target'][$pos].$post[$cat]['new_url'][$pos];
                        }
                        $post['move_copy']['desti']['name'][$pos] .= $new_url;
                    }
                }
            }
            # maximale länge der ordner oder dateinamen
            $max_zeichen = 255;
            # prüfen der cat page auf max_zeichen
            if((3 + $name_len + $target_len + $url_len + $ext_len) > $max_zeichen) {
                if(!empty($post[$cat]['new_url'][$pos])) {
                    $post[$cat]['error_html']['new_url'][$pos] = 'style="background-color:'.$error_color['check_name_too_long'].';" ';
                }
                if(!empty($post[$cat]['new_name'][$pos])) {
                    $post[$cat]['error_html']['new_name'][$pos] = 'style="background-color:'.$error_color['check_name_too_long'].';" ';
                }
                $post['error_messages']['check_name_too_long']['color'] = $error_color['check_name_too_long'];
                $post['error_messages']['check_name_too_long'][] = NULL;
            }
            unset($name_len,$target_len,$url_len,$ext_len);

            if($cat != "cat") {
                $kategorie = $cat."/";
            } else {
                $kategorie = NULL;
            }
            # existiert die cat oder page überhaupt
            if(!isset($post[$cat]['url'][$pos]) and isset($post[$cat]['position'][$pos]) and isset($post[$cat]['name'][$pos])) {
                $file_test = $CONTENT_DIR_REL.$kategorie.$post[$cat]['position'][$pos]."_".$post[$cat]['name'][$pos].$post[$cat]['ext'][$pos];
                If(is_file($file_test)) {
                    $tt = true;
                } elseif(is_dir($file_test)) {
                    $tt = true;
                } else {
                    $post['error_messages']['check_is_file']['color'] = $error_color['check_is_file'];
                    $post['error_messages']['check_is_file'][] = NULL;
                    $post[$cat]['error_html']['name'][$pos] = 'style="background-color:'.$error_color['check_is_file'].';" ';
                }
            } elseif(isset($post[$cat]['url'][$pos]) and isset($post[$cat]['position'][$pos]) and isset($post[$cat]['name'][$pos]) and isset($post[$cat]['target'][$pos]) and isset($post[$cat]['url'][$pos])) {
                $file_test =$CONTENT_DIR_REL.$kategorie.$post[$cat]['position'][$pos]."_".$post[$cat]['name'][$pos].$post[$cat]['target'][$pos].$post[$cat]['url'][$pos].$EXT_LINK;
                if(is_file($file_test)) {
                    $tt = true;
                } elseif(is_dir($file_test)) {
                    $tt = true;
                } else {
                    $post['error_messages']['check_is_file']['color'] = $error_color['check_is_file'];
                    $post['error_messages']['check_is_file'][] = NULL;
                    $post[$cat]['error_html']['name'][$pos] = 'style="background-color:'.$error_color['check_is_file'].';" ';
                }
            }
            # nochmal wenn error gesetzt ist um alle edit sachen auszucklapen
            if(!empty($post[$cat]['error_html'])) {
                foreach ($post[$cat]['error_html'] as $test => $tmp) {
                    if(!empty($post[$cat]['error_html'][$test][$pos])) {
                        if($cat != "cat") {
                            $post[$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                        }
                        if(!empty($post[$cat]['new_name'][$pos]) or !empty($post[$cat]['new_url'][$pos])) {
                            $post[$cat]['error_html']['display'][$pos] = 'style="display:block;" ';
                        }
                    }
                }
            }
        }
    } # foreach cat end

    # prüfen wenn page in andere cat cp oder mv wird obs die da schonn gibt
    if(isset($move_copy_test)) {
        foreach($move_copy_test['new_cat'] as $move_copy_pos => $tmp) {
            $newname = $move_copy_test['name'][$move_copy_pos];
            $cat = $move_copy_test['new_cat'][$move_copy_pos];
            $org_cat = $move_copy_test['org_cat'][$move_copy_pos];
            $org_pos = $move_copy_test['pos_org_name'][$move_copy_pos];

            # wenn bei move copy in gleiche Kategorie source new_position mehr wie 2 mal vorkomt error
            $doubles = array_keys($move_copy_test['new_position'],$move_copy_test['new_position'][$move_copy_pos]);
            if(count($doubles) > 1) {
                $post['error_messages']['check_doubles_digit_copy_move']['color'] = $error_color['check_doubles_digit_copy_move'];
                $post['error_messages']['check_doubles_digit_copy_move'][] = NULL;
                $post[$org_cat]['error_html']['new_position'][$org_pos] = 'style="background-color:'.$error_color['check_doubles_digit_copy_move'].';" ';
                $post[$org_cat]['error_html']['display_cat'] = 'style="display:block;" ';
            }


            if(count(array_keys($post[$cat]['name'], $newname)) > 0 or count(array_keys($post[$cat]['new_name'], $newname)) > 0) {
                $tmp_pos_name = array_keys($post[$cat]['name'], $newname);
                $tmp_pos_new_name = array_keys($post[$cat]['new_name'], $newname);
                # Neu Name gipts schonn auser wenn der Name auch Umbenant wird
                if(count($tmp_pos_name) > 0 and $cat != $org_cat) {
                    $post[$cat]['error_html']['name'][$tmp_pos_name[0]] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                    $post['error_messages']['check_name_exist']['color'] = $error_color['check_name_exist'];
                    $post['error_messages']['check_name_exist'][] = NULL;
                }
                if(count($tmp_pos_new_name) > 0 and $cat != $org_cat) {
                    $post['error_messages']['check_name_exist']['color'] = $error_color['check_name_exist'];
                    $post['error_messages']['check_name_exist'][] = NULL;
                    $post[$cat]['error_html']['new_name'][$tmp_pos_new_name[0]] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                    $post[$cat]['error_html']['display'][$tmp_pos_new_name[0]] = 'style="display:block;" ';
                }
                if(empty($post[$org_cat]['new_name'][$org_pos])) {
                    $post[$org_cat]['error_html']['name'][$org_pos] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                } else {
                    $post[$org_cat]['error_html']['new_name'][$org_pos] = 'style="background-color:'.$error_color['check_name_exist'].';" ';
                }
                if($cat == $org_cat) {
                        $post['error_messages']['check_copy_same_cat']['color'] = $error_color['check_copy_same_cat'];
                        $post['error_messages']['check_copy_same_cat'][] = NULL;
                        $post[$org_cat]['error_html']['name'][$org_pos] = 'style="background-color:'.$error_color['check_copy_same_cat'].';" ';
                        if(isset($post[$org_cat]['error_html']['new_name'][$org_pos])) {
                            unset($post[$org_cat]['error_html']['new_name'][$org_pos]);
                        }
                }
                $post[$cat]['error_html']['display_cat'] = 'style="display:block;" ';
                $post[$org_cat]['error_html']['display_cat'] = 'style="display:block;" ';
                $post[$org_cat]['error_html']['display'][$org_pos] = 'style="display:block;" ';
            }
        }
    }
    return $post;
}

# hilfs function um messges text der keine lehrzeichen hat ab $max_string_len umzubrechen
function messagesOutLen($string) {
    $max_string_len = 105;
    $new_string = NULL;
    for($s = 0;strlen($string) > strlen($new_string);$s = ($s + $max_string_len)) {
        # String oder Restsring ist kleiner $max_string_len
        if(strlen(substr($string,$s)) <= $max_string_len) {
            $new_string .= substr($string,$s)."<br>";
        # im Teilsring ist ein Lehrzeichen. Ab dem Lehrzeichen ist neuer Teilstring
        } elseif(strrpos(substr($string,$s,$max_string_len)," ") > 1) {
            $len = strrpos(substr($string,$s,$max_string_len)," ");
            $new_string .= substr($string,$s,$len);
            $s = $s - ($max_string_len - strrpos(substr($string,$s,$max_string_len)," "));
        # im Teilsring ist kein Lehrzeichen <br> einsetzen. Ab dem <br> ist neuer Teilstring
        } else {
            $new_string .= substr($string,$s,$max_string_len)."<br>";
        }
    }
    return $new_string;
}

# messages und error_messages erzeugen
function categoriesMessages($post) {

    function Messages($post, $message_art, $message_error) {
        global $specialchars;
        $return_text = NULL;
        foreach($post[$message_art] as $error_language => $error_array) {
            $error_titel = getLanguageValue($error_language);
            if(empty($error_titel)) {
                $error_titel = $error_language." MUSS NOCH INS LANGUAGE";
            }

            if($error_language == 'updateReferences') {
                $titel_tmp = getLanguageValue(key($post[$message_art][$error_language]));
                if(empty($titel_tmp)) {
                    $titel_tmp = $error_language." MUSS NOCH INS LANGUAGE";
                }
                $error_titel = $error_titel."<br>".$titel_tmp;
                $error_array = $post[$message_art][$error_language][key($post[$message_art][$error_language])];
            }
            $error_inhalt = NULL;
            foreach($error_array as $inhalt) {
                if(empty($inhalt)) {
                    continue;
                }
                if(isset($post[$message_art][$error_language]['color'])) {
                    if(!empty($post[$message_art][$error_language]['color'])) {
                        $error_titel .= ' <span style="background-color:'.$inhalt.';">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
                        continue;
                    } else {
                        $error_titel .= ' BITTE MELDEN! Farben Fehlt';
                        continue;
                    }
                }
                $error_inhalt .= '<b>-&gt;</b>&nbsp;&nbsp;'.messagesOutLen(str_replace("&lt;b&gt;&gt;&lt;/b&gt;","<b>&gt;</b>",$specialchars->rebuildSpecialChars($inhalt, true, true)));
            }
            if(!empty($error_inhalt)) {
                $error_text = $error_titel.'<br><span style="font-weight:normal;">'.$error_inhalt.'</span>';
            } else {
                $error_text = $error_titel;
            }
            $return_text .= returnMessage($message_error, $error_text);
        }
        return $return_text;
    }

    $return_text = NULL;
    if(isset($post['error_messages'])) {
        $message_art = 'error_messages';
        $message_error = false;
        $return_text .= Messages($post, $message_art, $message_error);
    }
    if(isset($post['messages'])) {
        $message_art = 'messages';
        $message_error = true;
        $return_text .= Messages($post, $message_art, $message_error);
    }
    return $return_text;
}

# Nur Positions Verschiebung Position -> Neue Position inerhalb einer Kategorie oder Kategorie selbst
# !!!!! new_position darf nur einmal vorkommen !!!!!!!!!!!!!!
# Reihenvolge der änderungen:
# 1. new_positionen
# 2. Neue Kategorie, Inhaltseiten oder move_copy aus anderer Kategorie wenn diese auf eine new_positionen wierd
#    die Position geandert und $new_move_cat_page_newposition erzeugt
# 3. Kategorie oder Inhaltseiten die betroffen sind weil auf ihrer Position eine Verschobene oder
#    neue Kategorie oder Inhaltseiten soll, dann wierd ein freier Platz für sie gesucht
# Gebraucht wird immer Original Position und Neue Position optional ein array mit den neuen Kategorie oder Inhaltseiten
# wenn bei Neue Kategorie oder Inhaltseite die Position nicht möglich ist wird array $new_move_cat_page_newposition
# zurückgegeben wo die alten neuen Positionen drin sind
# Aufbau der Arrays:
# $org_position Position ohne null => Position mit null
# $new_position Neue Position ohne null => Neue Position mit null
# $new_move_cat_page irgend eine zahl => Neue Position mit null
function position_move($org_position,$new_position,$new_move_cat_page = false) {
    $max_cat_page = 100;
    $array_return['move'] = false;

    $array_sorce_desti = array_combine($org_position,$new_position);
    # nur die geänderten positionen ins array
    foreach($array_sorce_desti as $key => $value) {
        if(empty($key)) unset($array_sorce_desti[$key]);
        if($key == $value) unset($array_sorce_desti[$key]);
    }

    if(count($array_sorce_desti) == 0 and $new_move_cat_page === false) {
        # raushier nichts zu tun
        return $array_return;
    }

    # ein flip damit es einfacher ist die array zu bilden
    $array_desti_sorce = array_flip($array_sorce_desti);
    # $array_org $array_new_posi bilden
    for($i = 0; $i < $max_cat_page; $i++) {
        $array_org[$i] = NULL;
        $array_new_posi[$i] = NULL;
        # hier die Org_positionen rein die nicht umbenant werden
        if(isset($org_position[$i]) and !isset($array_sorce_desti[sprintf("%02d",$i)])) {
            $array_org[$i] = $org_position[$i];
        }
        # hier die New_Positionen rein
        if(isset($array_desti_sorce[sprintf("%02d",$i)])) {
            $array_new_posi[$i] = $array_desti_sorce[sprintf("%02d",$i)];
        }
    }
    # die neuen (Kategorie oder Inhaltseite) oder aus anderer Kategorie stammenden einbauen
    if($new_move_cat_page !== false) {
        foreach($new_move_cat_page as $new_cat_page) {
            # die neuen (move copy aus anderer Kategorie) Kategorien oder Inhaltseiten einbauen
            # wenn der Platz im $array_new_posi frei einfach rein ansonsten eine freie suchen
            if(empty($array_new_posi[sprintf("%1d",$new_cat_page)])) {
                $array_new_posi[sprintf("%1d",$new_cat_page)] = $new_cat_page;
            } else {
                # freie Position suchen
                # Richtung = $posi bis $max_cat_page
                for($new_posi = sprintf("%1d",$new_cat_page); $new_posi < $max_cat_page; $new_posi++) {
                    if(empty($array_new_posi[$new_posi])) {
                        $array_new_posi[$new_posi] = sprintf("%02d",$new_posi);
                        # array erstellen wo die new_move_cat_page_Position => neuen Positionen
                        $new_move_cat_page_newposition[$new_cat_page] = sprintf("%02d",$new_posi);
                        $treffer = true;
                        break;
                    }
                }
                # keine frei Position gefunden also noch mal von hinten suchen
                if($treffer === false) {
                    # Richtung = $posi bis 0
                    for($new_posi = sprintf("%1d",$new_cat_page); $new_posi >= 0; $new_posi--) {
                        if(empty($array_new_posi[$new_posi])) {
                            $array_new_posi[$new_posi] = sprintf("%02d",$new_posi);
                            # array erstellen wo die new_move_cat_page_Position => neuen Positionen
                            $new_move_cat_page_newposition[$new_cat_page] = sprintf("%02d",$new_posi);
                            break;
                        }
                    }
                }
            }
        }
    }
    # wann muss ich von hinten anfangen und wann ist alles erledigt um die for abzubrechen
    $change_count = 0;
    $lehr = 0;
    for($posi = $max_cat_page - 1; $posi >= 0; $posi--) {
        if(!empty($array_new_posi[$posi])) {
            $change_count++;
        }
        if(!isset($array_org[$posi])) {
            $lehr++;
        }
        if($lehr > $change_count and !isset($back)) {
            $back = $posi;
            break;
        }
    }
    # ab hier die Org_Position in $array_new_posi einbauen
    for($posi = 0; $posi < $max_cat_page; $posi++) {
        # nichts mehr zu tun raus
        if(isset($back) and $back == $posi) {
            break;
        }
        # wenn Org_Position nicht umbenant wird continue
        if(isset($array_org[$posi]) and empty($array_new_posi[$posi])) {
            continue;
        # hier wird umbenant
        } elseif(isset($array_org[$posi]) and !empty($array_new_posi[$posi])) {
            # ab der position ersten freien nehmen weil die Org_Position bereitz mit einer New_position belegt ist
            # Richtung = $posi bis $max_cat_page
            for($new_posi = $posi; $new_posi < $max_cat_page; $new_posi++) {
                if(empty($array_new_posi[$new_posi])) {
                    $array_new_posi[$new_posi] = $array_org[$posi];
                    break;
                }
            }
        }
    }
    if(isset($back) and $back == $posi) {
        $merk_posi = $posi;
        for($posi = $max_cat_page - 1; $posi >= $merk_posi; $posi--) {
            if(isset($array_org[$posi]) and empty($array_new_posi[$posi])) {
                continue;
            # hier wird umbenant
            } elseif(isset($array_org[$posi]) and !empty($array_new_posi[$posi])) {
                # ab der position ersten freien nehmen weil die Org_Position bereitz mit einer New_position belegt ist
                # Richtung = Rest $posi bis 0
                for($new_posi = $posi; $new_posi >= 0; $new_posi--) {
                    if(empty($array_new_posi[$new_posi])) {
                        $array_new_posi[$new_posi] = $array_org[$posi];
                        break;
                    }
                }
            }
        }
    }
    # in $array_new_posi sind die Positionen so eingeortnet New_Position => Org_Position
    foreach($array_new_posi as $new_posi => $org_posi) {
        if(!empty($array_new_posi[$new_posi])) {
            if(sprintf("%02d",$new_posi) != $org_posi) {
                # New_Position => Org_Position ändern nach Org_Position => New_Position und lehre entfernen
                # Aufbau: Org Position mit null => Neue Position ohne null
                $array_return['move'][sprintf("%1d",$org_posi)] = sprintf("%02d",$new_posi);
            }
        }
    }

    # nur wenn von dieser fuction die Positionen geändert wurden
    if(isset($new_move_cat_page_newposition)) {
        # Aufbau: Orig Position mit null => Neue (von dieser function) oder Orig Position mit null
        $array_return['move_cat_page_newposition'] = $new_move_cat_page_newposition;
    } else {
        $array_return['move_cat_page_newposition'] = false;
    }
    return $array_return;
}




?>