<?php

/* 
* 
* $Revision: 420 $
* $LastChangedDate: 2010-01-25 02:30:38 +0100 (Mo, 25. Jan 2010) $
* $Author: stefanbe $
*
*/


/**
 * 
 *  Copyright (c) 2000-2001 David Giffin
 *
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 * 
 *  Properties - reads java style properties files.
 * 
 *  $Id:Properties.php 19 2008-03-12 17:06:54Z arvid $
 *
 * @version   $Id:Properties.php 19 2008-03-12 17:06:54Z arvid $
 * @package Combine
 * @author   David Giffin <david@giffin.org>
 * @since    PHP 4.0
 * @copyright Copyright (c) 2000-2003 David Giffin : LGPL - See LICENCE
 *
 */


/**
 * Properties Class
 *
 * Similar to Java Properties or HashMap. Added some features to be more PHP.
 * Like toArray() returns a pointer to the internal PHP array so that it can be
 * manipulated easily.
 *
 *<code>
 *<?php
 *
 * require_once("Combine/runtime/Properties.php");
 *
 * $properties = new Properties();
 * $properties->loadProperties("combine/combine.properties");
 *
 * // get and set the log level
 * $oldLogLevel = $properties->get("combine.logLevel");
 * $properties->set("combine.logLevel",4);
 *
 * // Get an associative array of all properties which match
 * // the pattern "combine.database"
 * $matches = $properties->getMatch("combine.database");
 *
 *?>
 *</code>
 *
 * @author   David Giffin <david@giffin.org>
 * @package  Combine
 */

/*
properties['readonly'] = nur lesen
properties['error'] = kann nicht lesen oder schreiben oder Sperdatei anlegen
*/
class Properties {

    var $file = "";
    var $properties = array();

    /**
     * Constructor
     *
     * @param string $file The file name to load the properties from
     */
    function Properties($file = null, $is_admin = false) {
        global $BASIC_LANGUAGE;
        if(isset($BASIC_LANGUAGE->properties['_translator'])) {
            if(isset($BASIC_LANGUAGE->properties['properties_noinput']))
                $error_input = $BASIC_LANGUAGE->properties['properties_noinput'];
            else $error_input = "properties_noinput";
        } else {
            $error_input = "Keine Datei angegeben!";
        }

        if(!is_file($file) and !strstr($file,"sprachen")) {
            if($is_admin) {
                if($handle = @fopen($file, "w")) {
                    $default = makeDefaultConf($file);
                    $content = NULL;
                    foreach ($default as $key => $value) {
                        $content .= $key." = ".$value."\n";
                    }
                    fputs($handle, $content);
                    fclose($handle);
                } else {
                    die("Properties: Kann die $file Datei nicht Anlegen!");
                }
            } else {
                die("Properties: Darf die $file Datei nicht Anlegen rufen sie denn moziloCMS Admin auf!");
            }
        }

        if ($file == "")
            return $this->properties['error'] = $error_input;
        $this->file = $file;
        $this->loadProperties();
    }

    /**
     * Load Properties from a file
     *
     * @param string $file The file name to load the properties from
     */
    function loadProperties() {
        global $BASIC_LANGUAGE;

        $error_nofile = "die Datei Existiert nicht: ";
        if(isset($BASIC_LANGUAGE->properties['properties_nofile']))
            $error_nofile = $BASIC_LANGUAGE->properties['properties_nofile'];
        $error_readonly = "kann die Datei nur lesend öffnen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_readonly']))
            $error_readonly = $BASIC_LANGUAGE->properties['properties_readonly'];
        $error_write = "kann die Datei nicht schreibend öffnen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_write']))
            $error_write = $BASIC_LANGUAGE->properties['properties_write'];
        $error_read = "kann die Datei nicht Lesend oder schreibend öffnen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_read']))
            $error_read = $BASIC_LANGUAGE->properties['properties_read'];

        if (!file_exists($this->file)) {
            return $this->properties['error'] = $error_nofile.$this->file;
        } else {
            if(@fopen($this->file, "r")) {
                @fclose($this->file);
                $this->properties['readonly'] = $error_readonly.$this->file;
            } else {
                $this->properties['error'] = $error_read.$this->file;
            }
            if(!isset($this->properties['error']) and !(@fopen($this->file, "a+"))) {
                @fclose($this->file);
                $this->properties['error'] = $error_write.$this->file;
            }
        }

        if(isset($this->properties['readonly'])) {
            $lines = @file($this->file);
            foreach ($lines as $line) {
                // comments
                if (preg_match("/^#/",$line) || preg_match("/^\s*$/",$line)) {
                    continue;
                }
                if (preg_match("/^([^=]*)=(.*)/",$line,$matches)) {
                    $this->properties[trim($matches[1])] = trim($matches[2]);
                }
            }
        }
    }

    /**
     * Save Properties to a file
     *
     * @param string $file The file name to load the properties from
     */
    function saveProperties() {
        global $BASIC_LANGUAGE;

        $error_lock_existed = "kann setings nicht schreiben es existiert eine Sperdatei: ";
        if(isset($BASIC_LANGUAGE->properties['properties_lock_existed']))
            $error_lock_existed = $BASIC_LANGUAGE->properties['properties_lock_existed'];
        $error_lock_touch = "kann Sperdatei Datei nicht anlegen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_lock_touch']))
            $error_lock_touch = $BASIC_LANGUAGE->properties['properties_lock_touch'];
        $error_lock_del = "kann Sperdatei Datei nicht löschen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_lock_del']))
            $error_lock_del = $BASIC_LANGUAGE->properties['properties_lock_del'];
        $error_write = "kann die Datei nicht schreibend öffnen (Dateirechte prüfen): ";
        if(isset($BASIC_LANGUAGE->properties['properties_write']))
            $error_write = $BASIC_LANGUAGE->properties['properties_write'];

        // Vorm Schreiben erst auf eine mögliche Sperre überprüfen (Schutz vor konkurrierenden Schreibzugriffen)
        $islocked = true;
        // für die aktuelle Properties-Datei existiert eine Sperrdatei, sie ist also bereits geöffnet!
        if (file_exists($this->file.".lck")) {
            return $this->properties['error'] = $error_lock_existed.$this->file.".lck";
        }
        // keine Sperrdatei vorhanden, also darf geschrieben werden
        else {
            $islocked = false;
        }
        if($islocked === false) {
            // neue Sperrdatei anlegen
            if (!@touch($this->file.".lck")) {
                return $this->properties['error'] = $error_lock_touch.$this->file.".lck";
            }
            // Datei schreibend öffnen
            if (!@fopen($this->file, "a+")) {
                @fclose($this->file);
                // Löschen der Sperrdatei und Abbruch, wenn das öffnen nicht klappt
                @unlink($this->file.".lck");
                return $this->properties['error'] = $error_write.$this->file;
                if (file_exists($this->file.".lck")) {
                    return $this->properties['error'] = $error_lock_del.$this->file.".lck";
                }
            } else {
                $file = @fopen($this->file, "w");
            }
            $content = "";
            // alphabetisch sortieren
            if (!$this->properties == null)
                   ksort($this->properties);
            // auslesen...
            foreach ($this->properties as $key => $value) {
                if($key == "error" or $key == "readonly") {
                    continue;
                }
                $content .= $key." = ".$value."\n";
            }
            // ...und speichern
            fputs($file, $content);
            // Datei wieder schliessen
            fclose($file);
            // Sperrdatei wieder löschen
            @unlink($this->file.".lck");
            if (file_exists($this->file.".lck")) {
                return $this->properties['error'] = $error_lock_del.$this->file.".lck";
            }
        }
    }

    /**
     * Get a property value
     *
     * @param string $match regex expression for matching keys
     * @return array an associtive array of values
     */
    function get($key) {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
        return null;
    }


    /**
     * Get Properites which match a pattern
     *
     * @param string $match regex expression for matching keys
     * @return array an associtive array of values
     */
    function getMatch($match) {
        $ret = array();
        foreach ($this->properties as $key => $value) {
            if (preg_match("/$match/",$key)) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }


    /**
     * Search value which matches a pattern
     *
     * @param string $match regex expression for matching keys
     * @return array an associtive array of values
     */
    function valueExists($searchkey, $searchvalue) {
        foreach ($this->properties as $key => $value) {
            if ((preg_match("/$searchkey/",$key)) && (preg_match("/$searchvalue/",$value))) {
                return true;
            }
        }
        return false;
    }


    /**
     * Search key which matches a pattern
     *
     */
    function keyExists($searchkey) {
        foreach ($this->properties as $key => $value) {
            if ($searchkey == $key){
                return true;
            }
        }
        return false;
    }


    /**
     * Set Properties from an Array
     *
     * @param array $values an associtive array of values
     */
    function setFromArray($values) {
        $ret = true;
        unset($this->properties);
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value))
                $ret = false;
        }
        return $ret;
    }


    /**
     * Set a Property
     *
     * @param string $key The key for the property
     * @param string $value The value to set the property
     */
    function set($key,$value) {
        if (($key != "") || ($value != "")) {
            $this->properties[$key] = $value;
            $this->saveProperties();
            return true;
        }
        else
            return false;
    }

    /**
     * Unset a Property
     *
     * @param string $key The key for the property
     */
    function delete($deletekey) {
        $ret = false;
        foreach ($this->properties as $key => $value) {
            if ($key == $deletekey)
                unset($this->properties[$key]);
                $ret = true;
        }
        $this->saveProperties();
        return $ret;
    }


    /**
     * Get the internal PHP Array
     *
     * @return array an associtive array of values
     */
    function toArray() {
        return $this->properties;
    }
}

?>
