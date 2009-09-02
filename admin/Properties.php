<?php
/**
 * 
 *  Copyright (c) 2000-2001 David Giffin
 *
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 * 
 *  Properties - reads java style properties files.
 * 
 *  $Id: Properties.php,v 1.2 2003/12/16 20:08:53 pierre Exp $
 *
 * @version   $Id: Properties.php,v 1.2 2003/12/16 20:08:53 pierre Exp $
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
			die("Properties: No file given");
		$this->file = $file;
		$this->loadProperties();
	}

	/**
	 * Load Properties from a file
	 *
	 * @param string $file The file name to load the properties from
	 */
	function loadProperties() {
		if (!file_exists($this->file)) {
			if (!@fopen($this->file, "w")) 
            die("Properties.php: Could not write $this->file.");
		}

		$lines = file($this->file);
		foreach ($lines as $line) {
			if (preg_match("/^#/",$line) || preg_match("/^\s*$/",$line)) {
				continue;
			}
			if (preg_match("/^(.*)=(.*)/",$line,$matches)) {
				$this->properties[trim($matches[1])] = trim($matches[2]);
			}
		}
	}

	/**
	 * Save Properties to a file
	 *
	 * @param string $file The file name to load the properties from
	 */
	function saveProperties() {
   	if (!$file = @fopen($this->file, "w")) 
    	die("Properties.php: Could not write to $file");
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
		fclose($file);        
	}

	/**
	 * Get a property value
	 *
	 * @param string $match regex expression for matching keys
	 * @return array an associtive array of values
	 */
	function get($key) {
		if (isSet($this->properties[$key]) && ($this->properties[$key])) {
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
	 * Set Properties from an Array
	 *
	 * @param array $values an associtive array of values
	 */
	function setFromArray($values) {
		$ret = true;
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
