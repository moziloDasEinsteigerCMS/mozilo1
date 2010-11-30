<?php

class CONTACT extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird. Der String-Parameter $value ist Pflicht, kann aber leer 
    * sein.
    * 
    ***************************************************************/
    function getContent($value) {
        global $PLUGIN_DIR_REL;
        global $CMS_CONF;
        global $contactformcalcs;
        global $lang_contact;

        $dir = $PLUGIN_DIR_REL."CONTACT/";
        $lang_contact = new Language($dir."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".conf");

        // existiert eine Mailadresse? Wenn nicht: Das Kontaktformular gar nicht anzeigen!
        if(strlen($this->settings->get("formularmail")) < 1) {
            return "<span class=\"deadlink\"".getTitleAttribute($lang_contact->getLanguageValue0("tooltip_no_mail_error_0")).">&#123;CONTACT&#125;</span>";
        }

        $default_contactformcalcs = '3 + 7 = 10<br />'
                                    .'5 - 3 = 2<br />'
                                    .'1 plus 1 = 2<br />'
                                    .'17 minus 7 = 10<br />'
                                    .'4 * 2 = 8<br />'
                                    .'3x3 = 9<br />'
                                    .'2 divided by 2 = 1<br />'
                                    .'Abraham Lincols first Name = Abraham<br />'
                                    .'James Bonds family name = Bond<br />'
                                    .'bronze, silver, ... ? = gold';

        if($this->settings->get("contactformcalcs"))
            $default_contactformcalcs = $this->settings->get("contactformcalcs");
        $tmp = explode("<br />",$default_contactformcalcs);
        $contactformcalcs = array();
        foreach($tmp as $zeile) {
            $tmp_z = explode(" = ",$zeile);
            if(isset($tmp_z[0]) and isset($tmp_z[1]) and !empty($tmp_z[0]) and !empty($tmp_z[1]))
                $contactformcalcs[$tmp_z[0]] = $tmp_z[1];
        }

        require_once($dir."func_contact.php");

        $return = buildContactForm($this->settings);
        return $return;

    } // function getContent
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * Ist keine Konfiguration nötig, ist dieses Array leer.
    * 
    ***************************************************************/
    function getConfig() {
        global $lang_contact_admin;

        $config = array();
        $config['formularmail']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_text_formularmail"),
            "maxlength" => "100",
            "size" => "40",
            "regex" => "/^[\w-]+(\.[\w-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})$/i",
            "regex_error" => $lang_contact_admin->get("config_error_formularmail")
        );
        $config['contactformwaittime']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_text_contactformwaittime"),
            "maxlength" => "100",
            "size" => "40",
            "regex" => "/^[\d+]+$/",
            "regex_error" => getLanguageValue("check_digit")
        );
        $config['contactformusespamprotection'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_text_contactformusespamprotection")
        );
        $config['contactformcalcs'] = array(
            "type" => "textarea",
            "cols" => "60",
            "rows" => "10",
            "description" => $lang_contact_admin->get("config_titel_spam_question")
        );
        # name
        $config['titel_name']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_titel_contact_input")." ".$lang_contact_admin->get("config_input_contact_name"),
            "maxlength" => "100",
            "size" => "40"
        );
        $config['titel_name_show'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_show")
        );
        $config['titel_name_mandatory'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_mandatory")
        );
        # mail
        $config['titel_mail']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_titel_contact_input")." ".$lang_contact_admin->get("config_input_contact_mail"),
            "maxlength" => "100",
            "size" => "40"
        );
        $config['titel_mail_show'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_show")
        );
        $config['titel_mail_mandatory'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_mandatory")
        );
        # website
        $config['titel_website']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_titel_contact_input")." ".$lang_contact_admin->get("config_input_contact_website"),
            "maxlength" => "100",
            "size" => "40"
        );
        $config['titel_website_show'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_show")
        );
        $config['titel_website_mandatory'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_mandatory")
        );
        # message
        $config['titel_message']  = array(
            "type" => "text",
            "description" => $lang_contact_admin->get("config_titel_contact_input")." ".$lang_contact_admin->get("config_input_contact_textarea"),
            "maxlength" => "100",
            "size" => "40"
        );
        $config['titel_message_show'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_show")
        );
        $config['titel_message_mandatory'] = array(
            "type" => "checkbox",
            "description" => $lang_contact_admin->get("config_titel_contact_mandatory")
        );

        return $config;
    } // function getConfig    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück - in dieser 
    * Reihenfolge:
    *   - Name und Version des Plugins
    *   - für moziloCMS-Version
    *   - Kurzbeschreibung
    *   - Name des Autors
    *   - Download-URL
    *   - Platzhalter für die Selectbox
    * 
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        global $PLUGIN_DIR_REL;
        global $lang_contact_admin;
        $dir = $PLUGIN_DIR_REL."CONTACT/";
        $language = $ADMIN_CONF->get("language");
        $lang_contact_admin = new Properties($dir."sprachen/admin_language_".$language.".conf",false);
        if(!isset($lang_contact_admin->properties['readonly'])) {
            die($lang_contact_admin->properties['error']);
        }
        $info = array(
            // Plugin-Name + Version
            "<b>CONTACT</b>",
            // moziloCMS-Version
            "1.12",
            // Kurzbeschreibung nur <span> und <br /> sind erlaubt
            $lang_contact_admin->get("config_titel_contact")."<br><br>".$lang_contact_admin->get("config_text_contact"),
            // Name des Autors
            "mozilo",
            // Download-URL
            "http://cms.mozilo.de",
            // Platzhalter für die Selectbox in der Editieransicht 
            // - ist das Array leer, erscheint das Plugin nicht in der Selectbox
            array(
                '{CONTACT}' => $lang_contact_admin->get("toolbar_platzhalter_CONTACT")
            )
        );
        return $info;
    } // function getInfo

}

?>