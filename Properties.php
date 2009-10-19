<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
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
class Properties {

    var $file = "";
    var $properties = array();

    /**
     * Constructor
     *
     * @param string $file The file name to load the properties from
     */
    function Properties($file = null) {
        if ($file == "")
            die("Properties: Keine Datei angegeben!");
        $this->file = $file;
        $this->loadProperties();
    }

    /**
     * Load Properties from a file
     *
     * @param string $file The file name to load the properties from
     */
    function loadProperties() {
        // Datei exisiert nicht
        if (!file_exists($this->file)) {
            // Einfach nur anlegen
            if (!@touch($this->file)) {
                die("Properties.php: Kann ".$this->file." nicht schreiben - bitte Existenz der Datei und vergebene Dateirechte prüfen.");
            }
        }
        // Datei existiert bereits
        else {
            // Zeilenweise einlesen
            if(!$lines = @file($this->file)) {
                die("Properties.php: Kann ".$this->file." nicht öffnen - bitte Existenz der Datei und vergebene Dateirechte prüfen.");
            }
            foreach ($lines as $line) {
                // Kommentare
                if (preg_match("/^#/",$line) || preg_match("/^\s*$/",$line)) {
                    continue;
                }
                if (preg_match("/^([^=]*)=(.*)/",$line,$matches)) {
                    $this->properties[trim($matches[1])] = trim($matches[2]);
                }
            }
        }
        @fclose($this->file);
    }

    /**
     * Save Properties to a file
     *
     * @param string $file The file name to load the properties from
     */
    function saveProperties() {
        // Vorm Schreiben erst auf eine mögliche Sperre überprüfen (Schutz vor konkurrierenden Schreibzugriffen)
        $islocked = true;
        // 20 Mal versuchen
        for ($i=0; $i<20; $i++) {
            // für die aktuelle Properties-Datei existiert eine Sperrdatei, sie ist also bereits geöffnet!
            if (file_exists($this->file.".lck")) {
                // nächster Versuch nach einer Sekunde
                sleep(1);
                continue;
            }
            // keine Sperrdatei vorhanden, also darf geschrieben werden
            else {
                $islocked = false;
                break;
            }
        }
        
        // Existiert die Sperrdatei auch weiterhin?
        if ($islocked) {
            // einfach nix machen
        }
        else {
            // neue Sperrdatei anlegen
            if (!@touch($this->file.".lck")) {
                die("Properties.php: Kann ".$this->file.".lck nicht schreiben - bitte vergebene Dateirechte prüfen.");
            }
            // Datei schreibend öffnen
            if (!$file = @fopen($this->file, "w")) {
                // Löschen der Sperrdatei und Abbruch, wenn das Öffnen nicht klappt
                @unlink($this->file.".lck");
                die("Properties.php: Kann ".$this->file." nicht schreiben - bitte Existenz der Datei und vergebene Dateirechte prüfen.");
            }
            $content = "";
            // alphabetisch sortieren
            if (!$this->properties == null)
                   ksort($this->properties);
            // auslesen...
            foreach ($this->properties as $key => $value) {
                $content .= "$key = $value\n";
            }
            // ...und speichern
            fputs($file, $content);
            // Datei wieder schließen
            fclose($file);
            // Sperrdatei wieder löschen
            @unlink($this->file.".lck");
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