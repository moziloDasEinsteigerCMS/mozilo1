<?php
	
	require_once("../Properties.php");
	require_once("../SpecialChars.php");
	
	$specialchars = new SpecialChars();
	/* Variablen */
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Gibt zurueck welche Sprache aktuell verwendet wird.
	--------------------------------------------------------------------------------*/
	function getLanguage() 
	{
		$BASIC_CONFIG 		= new Properties("conf/basic.conf");
		return $BASIC_CONFIG->get("language");
	}
	
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Gibt zurueck wann der letzte Backup gemacht wurde...
	--------------------------------------------------------------------------------*/
	function getLastBackup() 
	{
		$BASIC_CONFIG 		= new Properties("conf/basic.conf");
		return $BASIC_CONFIG->get("lastbackup");
	}
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Schreibt die aktuelle Uhrzeit in lastbackup
	--------------------------------------------------------------------------------*/
	function setLastBackup() 
	{
		$BASIC_CONFIG 		= new Properties("conf/basic.conf");
		return $BASIC_CONFIG->set("lastbackup",time());
	}
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Liest aus dem Language-File eine Bistimmte Variable	aus.
	--------------------------------------------------------------------------------*/
	function getLanguageValue($confpara) 
	{
		$BASIC_LANGUAGE 	= new Properties("conf/language_".getLanguage().".conf");
		return htmlentities($BASIC_LANGUAGE->get($confpara));
	}
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	
	Fragt die Konfiguration ab, ob die Tooltips	angezeigt werden sollen oder nicht.
	--------------------------------------------------------------------------------*/
	function showTooltips() 
	{
		$BASIC_CONFIG 		= new Properties("conf/basic.conf");
		return $BASIC_CONFIG->get("showTooltips");
	}
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Gibt eine Dropdown-Liste mit den allen möglichen und belegten Positionen zurueck 
	--------------------------------------------------------------------------------*/
	function show_dirs($maindir, $selecteddir)
	{
		$content = "<select name=\"position\" size=1>";
		global $specialchars;
		$vergeben = getDirs($maindir);

	    for($pos = 0; $pos < 100; $pos++ )
	    {
	    	if(!in_array($pos,$vergeben))
	    	{
	    		$content .= "<option>";
					$content .= addFrontZero($pos);
					$content .= "</option>";		
	    	}
	    	else
	    	{
	    		$selected = "";
	    		if (addFrontZero($pos)."_".specialNrDir($maindir, $pos) == $selecteddir)
	    			$selected = "selected=\"selected\" ";
	    		$content .= "<option style=\"color:lightgrey;\"$selected>";
					$content .= addFrontZero($pos)." ".$specialchars->rebuildSpecialChars(specialNrDir($maindir, $pos), true);
					$content .= "</option>";		
	    	}
	    
	    }
	    $content .= "</select>";
	    
	    return $content;
	}
	
	/**-------------------------------------------------------------------------------- 
	@author: Oliver Lorenz
	Gibt eine Dropdown-Liste mit den allen möglichen und belegten Positionen zurueck 
	--------------------------------------------------------------------------------*/
	function show_files($dir, $currentfile, $includedrafts)
	{
		global $specialchars;
		$content = "<select name=\"position\" class=\"select1\" size=1>";
		if ($includedrafts)
			$vergeben = getFiles($dir, "");
		else
			$vergeben = getFiles($dir, ".tmp");

    for($pos = 0; $pos < 100; $pos++ )
    {
    	if(!in_array($pos,$vergeben))
    	{
    		$content .= "<option>";
				$content .= addFrontZero($pos);
				$content .= "</option>";		
    	}
    	else
    	{
    		if ((specialNrDir($dir, $pos) == $currentfile.".txt") || (specialNrDir($dir, $pos) == $currentfile.".tmp"))
    			$selected = "selected=\"selected\" ";
    		else
    			$selected = " ";
    		$content .= "<option ".$selected."style=\"color:lightgrey;\">";
    		$fullname = $specialchars->rebuildSpecialChars(specialNrDir($dir, $pos), true);
				$content .= addFrontZero($pos)." ".substr($fullname, 0, strlen($fullname)-strlen(".txt"));
				$content .= "</option>";		
    	}
    
    }
    $content .= "</select>";
    
    return $content;
	}

	/**--------------------------------------------------------------------------------
	@author: Oliver Lorenz
	Gibt alle enthaltenen Ordner in ein Array aus
	--------------------------------------------------------------------------------*/
	function getDirs($dir)
	{
		global $specialchars;
		$vergeben = array();
		if (is_dir($dir))
		{
			$handle = opendir($dir);
			$file = readdir($handle);
			while($file = readdir($handle))
			{
				if(($file != ".") && ($file != "..") && !is_file($file))
				{
					array_push($vergeben, substr($file,0,2));
				}
			}
			closedir($handle);
		}
		sort($vergeben);
		return $vergeben;
	}

	/**--------------------------------------------------------------------------------
	@author: Arvid Zimmermann
	Gibt alle enthaltenen Dateien in ein Array aus
	--------------------------------------------------------------------------------*/
	function getFiles($dir, $excludeextension)
	{
		$files = array();
		$handle = opendir($dir);
		while($file = readdir($handle)) {
			if(($file != ".") && ($file != "..") && ($file != "dateien")) {
				// auszuschließende Extensions nicht berücksichtigen
				if ($excludeextension != "") {
					if (substr($file, strlen($file)-4, strlen($file)) != "$excludeextension")
						array_push($files, $file);
				}
				else
					array_push($files, $file);
			}
		}
		closedir($handle);
		return $files;
	}

	/*--------------------------------------------------------------------------------
	@author: Oliver Lorenz
	Sucht nach einem Ordner der mit einer Bestimmten Nummern-Praefix beginnt
	--------------------------------------------------------------------------------*/
	function specialNrDir($dir, $nr)
	{
	    if (!is_file($dir)){
	        $handle = opendir($dir);
	        $file = readdir($handle);
	        $vergeben = array();
	        while($file = readdir($handle))
	        {
	            if(($file != ".") && ($file != "..") && !is_file($file))
	            {
								if(substr($file,0,2)==$nr)
								{
									return substr($file,3);
								}
	            }
	        }
	        closedir($handle);
	    }
	  }
	  
	/*--------------------------------------------------------------------------------
	@author: Oliver Lorenz
	Legt die Ordnerstuktur für eine neue Kategorie an
	--------------------------------------------------------------------------------*/
  function createInhalt()
  {
  	global $specialchars;
  	global $CMS_CONF;
  	$betterString = $specialchars->deleteSpecialChars($_GET["name"]);
		mkdir ("../kategorien/".$_GET["position"]."_".$betterString, 0777);
		mkdir ("../kategorien/".$_GET["position"]."_".$betterString."/dateien", 0777);
		// chmod, wenn so eingestellt
	    if ($CMS_CONF->get("chmodnewfiles") == "true") {
	    	chmod ("../kategorien/".$_GET["position"]."_".$betterString, octdec($CMS_CONF->get("chmodnewfilesatts")));
	    	chmod ("../kategorien/".$_GET["position"]."_".$betterString."/dateien", octdec($CMS_CONF->get("chmodnewfilesatts")));
		}

  }
  
  function getFreeDirs($dir)
	{
		$dirarray = array();
		global $specialchars;
		$vergeben = getDirs($dir);

	    for($pos = 0; $pos < 100; $pos++ )
	    {
	    	if(!in_array($pos,$vergeben))
	    	{
	    		array_push($dirarray, addFrontZero($pos));
	    	}
	    }
	    return $dirarray;
	}
	
	function getCatsAsSelect($selectedcat) {
		global $specialchars;
		$dirs = array();
		$handle = opendir('../kategorien');
		while (($file = readdir($handle))) {
			if (($file <> ".") && ($file <> ".."))
				array_push($dirs, $file);
		}
		closedir($handle);
		sort($dirs);
		$select = "<select name=\"cat\">";
		foreach ($dirs as $file) {
			if (($selectedcat <> "") && ($file == $selectedcat))
				$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</option>";
			else
				$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3), true)."</option>";
		}
		$select .= "</select>";
		return $select;
	}

	function getGalleriesAsSelect($selectedgallery) {
		global $specialchars;
		$dirs = array();
		$handle = opendir('../galerien');
		while (($file = readdir($handle))) {
			if (($file <> ".") && ($file <> ".."))
				array_push($dirs, $file);
		}
		closedir($handle);
		sort($dirs);
		$select = "<select name=\"gal\">";
		foreach ($dirs as $file) {
			if (($selectedgallery <> "") && ($file == $selectedgallery))
				$select .= "<option selected=\"selected\" value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true)."</option>";
			else
				$select .= "<option value=\"".$file."\">".$specialchars->rebuildSpecialChars($file, true)."</option>";
		}
		$select .= "</select>";
		return $select;
	}
?>