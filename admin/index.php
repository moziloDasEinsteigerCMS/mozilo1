<?php
$ADMIN_TITLE = "moziloAdmin";

/* Login überprüfen */
	session_start();
	if (!$_SESSION['login_okay'])
		header("location:login.php?logout=true");

	require("filesystem.php");
	require("string.php");
	$ADMIN_CONF	= new Properties("conf/basic.conf");
	$CMS_CONF	= new Properties("../main.conf");

/* Aktion abhängig vom action-Parameter */
	$action = $_GET['action'];
	if ($action == "")
		$action = $_POST['action'];
	$functionreturn = array();
	
	// Startseite
	if($action=="home")
		$functionreturn = home();
	// Kategorien
	elseif ($action=="category")
		$functionreturn = category();
	elseif ($action=="newcategory")
		$functionreturn = newCategory();
	elseif ($action=="editcategory")
		$functionreturn = editCategory();
	elseif ($action=="deletecategory")
		$functionreturn = deleteCategory();
	// Inhaltsseiten
	elseif ($action=="site")
		$functionreturn = site();
	elseif ($action=="newsite")
		$functionreturn = newSite();
	elseif ($action=="editsite")
		$functionreturn = editSite();
	elseif ($action=="deletesite")
		$functionreturn = deleteSite();
	// Galerien
	elseif ($action=="gallery")
		$functionreturn = gallery();
	elseif ($action=="newgallery")
		$functionreturn = newGallery();
	elseif ($action=="editgallery")
		$functionreturn = editGallery();
	elseif ($action=="deletegallery")
		$functionreturn = deleteGallery();
	// Dateien
	elseif ($action=="file")
		$functionreturn = files();
	elseif ($action=="newfile")
		$functionreturn = newFile();
	elseif ($action=="deletefile")
		$functionreturn = deleteFile();
	// Einstellungen
	elseif ($action=="config")
		$functionreturn = config();
	elseif ($action=="displaycmsconfig")
		$functionreturn = configCmsDisplay();
	elseif ($action=="displayadminconfig")
		$functionreturn = configAdminDisplay();
	elseif ($action=="loginadminconfig")
		$functionreturn = configAdminLogin();
	// Bei unbekanntem oder leerem action-Parameter
	else
		$functionreturn = home();

	$pagetitle = $functionreturn[0];
	$pagecontent = $functionreturn[1];
	
	
/* Aufbau der gesamten Seite */
	
	$html = "<!doctype html public \"-//W3C//DTD HTML 4.01 Transitional//EN\">";
	$html .= "<html>";
	$html .= "<head>";
	$html .= "<script src=\"crossTooltips.js\" type=\"text/javascript\"></script>";
	$html .= "<script src=\"buttons.js\" type=\"text/javascript\"></script>";
	$html .= "<title>$ADMIN_TITLE - $pagetitle</title>";
	$html .= "<link rel=\"stylesheet\" href=\"adminstyle.css\" type=\"text/css\" />";
	$html .= "</head>";
	$html .= "<body onload=\"htmlOverlopen(document.documentElement,0)\">";
	
	// DEEEEEEEEEEEBUG ;)
	// Ausgabe aller übergebenen Werte zu Testzwecken
	/*
	echo "<h2>POST</h2>";
	foreach ($_POST as $a => $b)
		echo $a." -> ".$b."<br />";
	echo "<h2>GET</h2>";
	foreach ($_GET as $a => $b)
		echo $a." -> ".$b."<br />";
	*/
	
	$html .= "<div id=\"mozilo_Logo\"></div>";
	$html .= "<div id=\"main_div\">";
	// Titelleiste
	$html .= "<div id=\"design_Title\">";
	$html .= "<div id=\"design_Titletext\">$ADMIN_TITLE - $pagetitle</div>";
	$html .= "<a href=\"login.php?logout=true\" accesskey=\"".createNormalTooltip("button_home_logout", "button_home_logout_tooltip", 150)."\"><span id=\"design_Logout\"></span></a>";
	$html .= "</div>";
	// Titelleiste Ende
	$html .= "<div id=\"navi_left\">";
	
/* Menü */
	
	// Menüpunkt "Home"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=home\" accesskey=\"".createNormalTooltip("button_home", "button_home_tooltip", 150)."\"><span id=\"navi_btn_home\">".getLanguageValue("button_home")."</span></a>";
	//Menüpunkt "Kategorien"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=category\" accesskey=\"".createNormalTooltip("button_category", "button_category_tooltip", 150)."\"><span id=\"navi_btn_category\">".getLanguageValue("button_category")."</span></a>";
	// Menüpunkt "Seiten"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=site\" accesskey=\"".createNormalTooltip("button_site", "button_site_tooltip", 150)."\"><span id=\"navi_btn_site\">".getLanguageValue("button_site")."</span></a>";
	// Menüpunkt "Galerie"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=gallery\" accesskey=\"".createNormalTooltip("button_gallery", "button_gallery_tooltip", 150)."\"><span id=\"navi_btn_gallery\">".getLanguageValue("button_gallery")."</span></a>";
	// Menüpunkt "Dateien"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=file\" accesskey=\"".createNormalTooltip("button_data", "button_data_tooltip", 150)."\"><span id=\"navi_btn_upload\">".getLanguageValue("button_data")."</span></a>";
	// Menüpunkt "Konfiguration"
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=config\" accesskey=\"".createNormalTooltip("button_config", "button_config_tooltip", 150)."\"><span id=\"navi_btn_help\">".getLanguageValue("button_config")."</span></a>";
	
/* Unterkategorien */	            	
/* Home */
	/* Logout */
	$html .= "<a class=\"leftmenu\" href=\"login.php?logout=true\" accesskey=\"".createNormalTooltip("button_home_logout", "button_home_logout_tooltip", 150)."\"><span id=\"home_logout\"></span></a>";
	            
/* Categories */
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=newcategory\" accesskey=\"".createNormalTooltip("button_category_new", "", 150)."\"><span id=\"kategorie_new\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=editcategory\" accesskey=\"".createNormalTooltip("button_category_edit", "", 150)."\"><span id=\"kategorie_edit\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletecategory\" accesskey=\"".createNormalTooltip("button_category_delete", "", 150)."\"><span id=\"kategorie_delete\"> </span></a>";
	            
/* Sites */
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=newsite\" accesskey=\"".createNormalTooltip("button_site_new", "", 150)."\"><span id=\"site_new\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=editsite\" accesskey=\"".createNormalTooltip("button_site_edit", "", 150)."\"><span id=\"site_edit\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletesite\" accesskey=\"".createNormalTooltip("button_site_delete", "", 150)."\"><span id=\"site_delete\"> </span></a>";
	            
/* Galleries */
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=newgallery\" accesskey=\"".createNormalTooltip("button_gallery_new", "", 150)."\"><span id=\"gallery_new\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=editgallery\" accesskey=\"".createNormalTooltip("button_gallery_edit", "", 150)."\"><span id=\"gallery_edit\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletegallery\" accesskey=\"".createNormalTooltip("button_gallery_delete", "", 150)."\"><span id=\"gallery_delete\"> </span></a>";
	            
/* Config */
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=displaycmsconfig\" accesskey=\"".createNormalTooltip("button_config_cms", "", 150)."\"><span id=\"config_cms\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=displayadminconfig\" accesskey=\"".createNormalTooltip("button_config_admin", "", 150)."\"><span id=\"config_admin\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=loginadminconfig\" accesskey=\"".createNormalTooltip("button_config_pw", "", 150)."\"><span id=\"config_login\"> </span></a>";

/* Files */
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=newfile\" accesskey=\"".createNormalTooltip("button_data_new", "", 150)."\"><span id=\"upload_new\"> </span></a>";
	$html .= "<a class=\"leftmenu\" href=\"index.php?action=deletefile\" accesskey=\"".createNormalTooltip("button_data_delete", "", 150)."\"><span id=\"upload_delete\"> </span></a>";
	$html .= "</div>";
	
/* Seiteninhalt */
	$html .= "<div id=\"div_content\">";
	$loginconf = new Properties("conf/logindata.conf");
	if ($loginconf->get("initialpw") == "true")
		$html .= returnMessage(false, getLanguageValue("warning_initial_pw"));
	$html .= $pagecontent;
	$html .= "</div>";

	
	$html .= "</div>";
	$html .= "</body>";
	$html .= "</html>";



/* Ausgabe der kompletten Seite */
echo $html;


/* 	------------------------------
		Zusätzliche Funktionen
		------------------------------ */
		
	function home() {
		$pagecontent .= "<h2>".getLanguageValue("button_home")."</h2>";
		$pagecontent .= "<p>";
		$month = getLastBackup() + 2592000;
		if( $month < time())
		{
			$pagecontent .= returnMessage(false, getLanguageValue("reminder_backup"));
			setLastBackup();
		}
		$pagecontent .= getLanguageValue("welcome_text");
		$pagecontent .= "</p>";
		return array(getLanguageValue("button_home"), $pagecontent);
	}

	function category() {
		$pagecontent = "<h2>".getLanguageValue("button_category")."</h2>";
		$pagecontent .= "<p>".getLanguageValue("category_text")."</p>";
		$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
		$pagecontent .= "<ul>";
		$pagecontent .= "<li><a href=\"index.php?action=newcategory\">".getLanguageValue("button_category_new")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=editcategory\">".getLanguageValue("button_category_edit")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=deletecategory\">".getLanguageValue("button_category_delete")."</a></li>";
		$pagecontent .= "</ul>";
		return array(getLanguageValue("button_category"), $pagecontent);
	}

	function newCategory() 
	{
		
		global $action;
		$pagecontent = "";
		
		$title = getLanguageValue("button_category_new");
		$message1 = "";
		$message2 = "";
		$message3 = "";
		$nameconflict = false;
		if(isset($_GET["position"]))
		{
			if(strlen($_GET["name"]) == 0)
			{
				$message3 = getLanguageValue("category_empty");
			}
			elseif(strlen($_GET["position"])>2)
			{
				$message1 = getLanguageValue("category_exist");
			}
			elseif(!(preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß\s]+$/", $_GET["name"])))
			{
				$message4 = getLanguageValue("category_name_wrong");
				$nameconflict = true;	
			}
			elseif(strlen($_GET["name"])>64)
			{
				$message4 = getLanguageValue("name_too_long");
				$nameconflict = true;	
			}
			if(strlen($_GET["position"])<3 && strlen($_GET["name"]) != 0 && !$nameconflict)
			{
				createInhalt();
				$message2 = getLanguageValue("category_created_ok");
			}
		}
		
		$pagecontent = "<h2>".getLanguageValue("button_category_new")."</h2>";
		if( $message1!="")
		{
			$pagecontent .= returnMessage(false, $message1);
		}
		if( $message2!="")
		{
			$pagecontent .= returnMessage(true, $message2);
		}
		if( $message3!="")
		{
			$pagecontent .= returnMessage(false, $message3);
		}
		if( $message4!="")
		{
			$pagecontent .= returnMessage(false, $message4);
		}
		
		
		$pagecontent .= "<p>";
		$pagecontent .= getLanguageValue("category_new_text");
		$pagecontent .= "</p>";
		$pagecontent .= "<h3>".getLanguageValue("button_category_new")."</h3>";
		$pagecontent .= "<form action=\"index.php\" method=\"get\"><input type=\"hidden\" name=\"action\" value=\"".$action."\"><table class=\"data\">";
		// Zeile "NEUER NAME"
		$pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("choose_category_name")."</td>";
		if(!(isset($_GET["name"])))
		{
			$pagecontent .= "<td class=\"config_row2\"><input class=\"text1\" name=\"name\" /></td>";
		}
		else
		{
			if($message1 != "" && $message3 != "" && $message4 != "")
			{
				$fill = $_GET["name"];
			}
			else
			{
				$fill = "";
			}
			$pagecontent .= "<td class=\"config_row2\"><input class=\"text1\" name=\"name\" value=\"".$fill."\" /></td>";
		}
		$pagecontent .= "</tr>";
		// Zeile "NEUE POSITION"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("category_numbers", "category_number_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("choose_category_position")."</td>";
		$pagecontent .= "<td class=\"config_row2\">".show_dirs("../inhalt/")."</td>";
		$pagecontent .= "</tr>";
		// Zeile "SUBMIT"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
		$pagecontent .= "<td class=\"config_row1\"><input type=\"submit\" class=\"submit\"></td>";
		$pagecontent .= "</tr>";
		$pagecontent .= "</table>";
		$pagecontent .= "</form>";
		return array(getLanguageValue("button_category_new"), $pagecontent);
	}

	function editCategory() 
	{
		global $action;
		global $specialchars;
		$pagecontent .= "<h2>".getLanguageValue("button_category_edit")."</h2>";
		$goto = "";
		
		if(isset($_GET["submit"])) {
			// Position frei
			if (strlen($_GET["position"])<3) {
				rename("../inhalt/".$_GET["cat"],"../inhalt/".$_GET["position"]."_".$specialchars->deleteSpecialChars($_GET["newname"]));
				$pagecontent .= returnMessage(true,getLanguageValue("category_edited"));
			}
			// Position belegt, aber mit der gleichen Kategorie >> UMBENENNEN
			elseif (substr($_GET["position"],0,2)."_".$specialchars->deleteSpecialChars(substr($_GET["position"],3)) == $_GET["cat"]) {
				if (rename("../inhalt/".$_GET["cat"], "../inhalt/".substr($_GET["position"],0,2)."_".$specialchars->deleteSpecialChars($_GET["newname"]))) {
					$pagecontent .= returnMessage(true,getLanguageValue("category_edited"));
					$_GET["cat"] = substr($_GET["position"],0,2)."_".$specialchars->deleteSpecialChars($_GET["newname"]);
				}
			}
			// Position mit anderer Kategorie belegt
			else
			{
				$pagecontent .= returnMessage(false,getLanguageValue("position_in_use"));
				$goto = "->";
			}
		}
		
		if(!isset($_GET["cat"]) && $goto != "->")
		{
			// 1. Seite
			$pagecontent .= "<p>";
			$pagecontent .= getLanguageValue("category_edit_text");
			$pagecontent .= "</p>";
			$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
			$pagecontent .= "<form action=\"index.php\" method=\"GET\"><input type=\"hidden\" name=\"action\" value=\"".$action."\">";
			$pagecontent .= "<input type=\"hidden\" name=\"cat\" value=\"".$_GET["cat"]."\">";
			$pagecontent .= "<table class=\"data\">";
			$pagecontent .= "<tr>";	
			$pagecontent .= "<td class=\"config_row1\">";	
			$pagecontent .= getLanguageValue("choose_category");
			$pagecontent .= "</td>";	
			$pagecontent .= "<td class=\"config_row2\">";	
			$pagecontent .= getCatsAsSelect();
			$pagecontent .= "</td>";	
			$pagecontent .= "</tr>";	
			$pagecontent .= "<tr>";	
			$pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";	
			$pagecontent .= "<td class=\"config_row2\"><input value=\"".getLanguageValue("choose_category_button")."\" type=\"submit\" class=\"submit\"></td>";	
			$pagecontent .= "</tr>";	
			
			$pagecontent .= "</table>";
			$pagecontent .= "</form>";
		}
		if(isset($_GET["cat"]) || $goto == "->")
		{
			$pagecontent .= "<p>";
			$pagecontent .= getLanguageValue("category_choosed");
			$pagecontent .= "<b> ".$specialchars->rebuildSpecialChars(substr($_GET["cat"],3))."</b>";
			$pagecontent .= "</p>";
			
			$pagecontent .= "<form action=\"index.php\" method=\"GET\">";
			$pagecontent .= "<input type=\"hidden\" name=\"action\" value=\"".$action."\">";
			$pagecontent .= "<input type=\"hidden\" name=\"cat\" value=\"".$_GET["cat"]."\">";
			$pagecontent .= "<table class=\"data\">";
			// Zeile "NAME ÄNDERN"
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_category_name")."</td>";
			$pagecontent .= "<td class=\"config_row2\"><input class=\"Text1\" value=\"".$specialchars->rebuildSpecialChars( substr($_GET["cat"],3) )."\" type=\"text\" name=\"newname\"></td>";
			$pagecontent .= "</tr>";
			// Zeile "POSITION ÄNDERN"
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("current_category_position")."</td>";
			$pagecontent .= "<td class=\"config_row2\">";
			$pagecontent .= show_dirs("../inhalt");
			$pagecontent .= "</td>";
			$pagecontent .= "</tr>";
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\">&nbsp;</td>";
			$pagecontent .= "<td class=\"config_row2\">";
			$pagecontent .= "<input type=\"Submit\" name=\"submit\" />";
			$pagecontent .= "</td>";
			$pagecontent .= "</tr>";
			$pagecontent .= "</table>";
			$pagecontent .= "</form>";
		}
		return array(getLanguageValue("button_category_edit"), $pagecontent);
	}

	function deleteCategory() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_category_delete")."</h2>";
		// Löschen der Kategorie nach Auswertung der übergebenen Parameter
		if (isset($_GET['cat']) && file_exists("../inhalt/".$_GET['cat'])) {
			if (isset($_GET['confirm']) && ($_GET['confirm'] == "true")) {
				if (deleteDir("../inhalt/".$_GET['cat']))
					$pagecontent .= returnMessage(true, getLanguageValue("category_deleted"));
				else
					$pagecontent .= returnMessage(false, getLanguageValue("category_delete_error"));
			}
			else
				$pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($_GET['cat'], 3, strlen($_GET['cat']))).": ".getLanguageValue("category_delete_confirm")." <a href=\"index.php?action=deletecategory&amp;cat=".$_GET['cat']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletecategory\">".getLanguageValue("no")."</a>");
		}
		
		$pagecontent .= "<p>".getLanguageValue("category_delete_text")."</p>";
		$dirs = getDirs("../inhalt");
		$pagecontent .= "<table class=\"data\">";
		foreach ($dirs as $file) {
			$file = $file."_".specialNrDir("../inhalt", $file);
			if (($file <> ".") && ($file <> "..") && ($pageshandle = opendir("../inhalt/".$file)) && ($fileshandle = opendir("../inhalt/".$file."/dateien")) && ($galleryhandle = opendir("../inhalt/".$file."/galerie"))) {
				// Anzahl Inhaltsseiten auslesen
				$pagescount = 0;
				while (($currentpage = readdir($pageshandle))) {
					if (is_file("../inhalt/".$file."/".$currentpage))
						$pagescount++;
				}
				// Anzahl Dateien auslesen
				$filecount = 0;
				while (($filesdir = readdir($fileshandle))) {
					if (($filesdir <> ".") && ($filesdir <> ".."))
						$filecount++;
				}
				// Anzahl Galeriebilder auslesen
				$gallerycount = 0;
				while (($gallerydir = readdir($galleryhandle))) {
					if (($gallerydir <> ".") && ($gallerydir <> "..") && ($gallerydir <> "texte.conf"))
						$gallerycount++;
				}
				if ($pagescount == 1)
					$pagestext = getLanguageValue("single_page");
				else
					$pagestext = getLanguageValue("many_pages");
				if ($gallerycount == 1)
					$galleriestext = getLanguageValue("single_gallerypic");
				else
					$galleriestext = getLanguageValue("many_gallerypics");
				if ($filecount == 1)
					$filestext = getLanguageValue("single_file");
				else
					$filestext = getLanguageValue("many_files");
				$pagecontent .= "<tr><td class=\"config_row1\"><h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3))."</h3> ($pagescount $pagestext, $gallerycount $galleriestext, $filecount $filestext)</td>";
				$pagecontent .= "<td class=\"config_row2\"><a href=\"index.php?action=deletecategory&amp;cat=$file".""."\">".getLanguageValue("button_delete")."</a></td></tr>";
			}
		}
		$pagecontent .= "</table>";
		closedir($handle);
		return array(getLanguageValue("button_category_delete"), $pagecontent);
	}

	function site() {
		$pagecontent = "<h2>".getLanguageValue("button_site")."</h2>";
		$pagecontent .= "<p>".getLanguageValue("site_text")."</p>";
		$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
		$pagecontent .= "<ul>";
		$pagecontent .= "<li><a href=\"index.php?action=newsite\">".getLanguageValue("button_site_new")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=editsite\">".getLanguageValue("button_site_edit")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=deletesite\">".getLanguageValue("button_site_delete")."</a></li>";
		$pagecontent .= "</ul>";
		return array(getLanguageValue("button_site"), $pagecontent);
	}

	function newSite() {
		global $specialchars;

		// Wenn nach dem Editieren "Speichern" gedrückt wurde
		if (isset($_POST['save'])) {
			saveContentToPage($_POST['pagecontent'],"../inhalt/".$_POST['cat']."/".$_POST['page']);
			$pagecontent = returnMessage(true, getLanguageValue("changes_applied"));
		}
		
		// Wenn nach dem Editieren "Abbrechen" gedrückt wurde
		elseif (isset($_POST['cancel']))
			header("location:index.php?action=newsite");

		// Wenn die Kategorie schon gewählt wurde oder im nächsten Schritt ein Fehler war
		if ( isset($_POST['cat']) || 
				(
					isset($_POST['position']) && isset($_POST['name']) 
					&& (strlen($_POST['name']) == 0)
					&& (!preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß\s]+$/", $_POST['name']))
					&& (strlen($_POST['position'])>2)
				) 
			) {
			$pagecontent .= "<h2>".getLanguageValue("button_site_new")."</h2>";
			$pagecontent .= "<h3>".getLanguageValue("chosen_category")." ".$specialchars->rebuildSpecialChars(substr($_POST['cat'], 3, strlen($_POST['cat'])-3))."</h3>";
			if (isset($_POST['position']) && isset($_POST['name'])) {
				if (strlen($_POST['name']) == 0)
					$pagecontent .= returnMessage(false, getLanguageValue("page_empty"));
				elseif (!preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß\s]+$/", $_POST["name"]))
					$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
				elseif (strlen($_POST["position"])>2)
					$pagecontent .= returnMessage(false, getLanguageValue("page_exist"));	
			}
			$pagecontent .= "<form action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"newsite\"><input type=\"hidden\" name=\"cat\" value=\"".$_POST['cat']."\">";
			$pagecontent .= "<table class=\"data\">";
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_page_name")."</td>";
			$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" name=\"name\"></td>";
			$pagecontent .= "</tr>";
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("page_numbers", "page_number_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("choose_page_position")."</td>";
			$pagecontent .= "<td class=\"config_row2\">".show_files("../inhalt/".$_POST['cat'])."</td>";
			$pagecontent .= "</tr>";
			$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
			$pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("button_newpage_create")."\" /></td></tr>";
			$pagecontent .= "</table>";
			$pagecontent .= "</form>";
		}
		else {
			// Zuerst: Kategorie wählen
			$pagecontent = "<h2>".getLanguageValue("button_site_new")."</h2>";
			$pagecontent .= "<form action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"newsite\">";
			$pagecontent .= "<table class=\"data\">";
			$pagecontent .= "<tr>";
			$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_category_for_page")."</td>";
			$pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect()."</td></tr>";
			$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
			$pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_category_button")."\" /></td></tr>";
			$pagecontent .= "</table>";
			$pagecontent .= "</form>";
		}
		
		// Wenn Name und Position der Seite schon gewählt wurde und korrekt sind
		if (
				isset($_POST['position']) 
				&& isset($_POST['name']) 
				&& strlen($_POST['name']) > 0
				&& preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß\s]+$/", $_POST['name'])
				&& (strlen($_POST['position'])<=2)
				) {
			$pagecontent = "<h2>".getLanguageValue("button_site_new")."</h2>";
			$pagecontent .= "<form name=\"form\" method=\"post\" action=\"index.php\">";
			$pagecontent .= showEditPageForm($_POST['cat'], $_POST['position']."_".$_POST['name'].".txt", "newsite");
			$pagecontent .= "</form>";
		}
		return array(getLanguageValue("button_site_new"), $pagecontent);
	}

	function editSite() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_site_edit")."</h2>";
		// Wenn nach dem Editieren "Speichern" gedrückt wurde
		if (isset($_POST['save'])) {
			$newpagename = substr($_POST['page'],0,2)."_".$specialchars->deleteSpecialChars($_POST['newpage']).".txt";
			if ($newpagename <> $_POST['page'])
				unlink("../inhalt/".$_POST['cat']."/".$_POST['page']);
			saveContentToPage($_POST['pagecontent'],"../inhalt/".$_POST['cat']."/".$newpagename);
			$pagecontent = returnMessage(true, getLanguageValue("changes_applied")).$pagecontent;
		}
		// Wenn nach dem Editieren "Abbrechen" gedrückt wurde
		elseif (isset($_POST['cancel']))
			header("location:index.php?action=editsite");
		if (isset($_GET['file']) && isset($_GET['cat'])) {
			$pagecontent .= "<form name=\"form\" method=\"post\" action=\"index.php\">";
			$pagecontent .= "<p>".getLanguageValue("site_name").": "."<input class=\"text2\" type=\"text\" name=\"newpage\" value=\"".$specialchars->rebuildSpecialChars(substr($_GET['file'],3,strlen($_GET['file'])-7))."\" /></p>";
			$pagecontent .= showEditPageForm($_GET['cat'], $_GET['file'], "editsite");
			$pagecontent .= "</form>";
		}
		
		else {
			$dirs = getDirs("../inhalt");
			foreach ($dirs as $file)
			sort($dirs);
			$pagecontent .= "<p>".getLanguageValue("page_edit_text")."</p>";
			foreach ($dirs as $file) {
				$file = $file."_".specialNrDir("../inhalt", $file);
					if (($file <> ".") && ($file <> "..") && ($subhandle = opendir("../inhalt/".$file))) {
						$pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3))."</h3>";
						$hasdata = false;
						$pagecontent .= "<table class=\"data\">";
						$catcontent = array();
						while (($subfile = readdir($subhandle)))
							if (is_file("../inhalt/".$file."/".$subfile))
								array_push($catcontent, $subfile);
						sort($catcontent);
						foreach ($catcontent as $subfile) {
							$pagecontent .= "<tr><td class=\"config_row1\">".$specialchars->rebuildSpecialChars(substr($subfile, 3, strlen($subfile)-7))."</td><td class=\"config_row2\"><a href=\"index.php?action=editsite&amp;cat=".$file."&amp;file=".$subfile."\">".getLanguageValue("button_edit")."</a></td></tr>";
							$hasdata = true;
						}
						if (!$hasdata)
						$pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("page_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
						$pagecontent .= "</table>";
					}
			}
		}
		return array(getLanguageValue("button_site_edit"), $pagecontent);
	}

	function deleteSite() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_site_delete")."</h2>";
		// Löschen der Inhaltsseite nach Auswertung der übergebenen Parameter
		if (isset($_GET['cat']) && isset($_GET['file']) && file_exists("../inhalt/".$_GET['cat']) && file_exists("../inhalt/".$_GET['cat']."/".$_GET['file'])) {
			if (isset($_GET['confirm']) && ($_GET['confirm'] == "true")) {
				if (unlink("../inhalt/".$_GET['cat']."/".$_GET['file']))
					$pagecontent .= returnMessage(true, getLanguageValue("page_deleted"));
				else
					$pagecontent .= returnMessage(false, getLanguageValue("page_delete_error"));
			}
			else
				$pagecontent .= returnMessage(false, $specialchars->rebuildSpecialChars(substr($_GET['file'], 3, strlen($_GET['file'])-7)).": ".getLanguageValue("page_delete_confirm")." <a href=\"index.php?action=deletesite&amp;cat=".$_GET['cat']."&amp;file=".$_GET['file']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletesite\">".getLanguageValue("no")."</a>");
		}
		$pagecontent .= "<p>".getLanguageValue("page_delete_text")."</p>";
		$dirs = getDirs("../inhalt");
		foreach ($dirs as $file) {
			$file = $file."_".specialNrDir("../inhalt", $file);
			if (($file <> ".") && ($file <> "..") && ($subhandle = opendir("../inhalt/".$file))) {
				$pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3))."</h3>";
				$hasdata = false;
				$pagecontent .= "<table class=\"data\">";
				
				$catcontent = array();
				while (($subfile = readdir($subhandle)))
					if (is_file("../inhalt/".$file."/".$subfile))
						array_push($catcontent, $subfile);
				sort($catcontent);
				foreach ($catcontent as $subfile) {
					$pagecontent .= "<tr><td class=\"config_row1\">".$specialchars->rebuildSpecialChars(substr($subfile, 3, strlen($subfile)-7))."</td><td class=\"config_row2\"><a href=\"index.php?action=deletesite&amp;cat=".$file."&amp;file=".$subfile."\">".getLanguageValue("button_delete")."</a></td></tr>";
					$hasdata = true;
				}
				if (!$hasdata)
				$pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("page_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
				$pagecontent .= "</table>";
			}
		}
		return array(getLanguageValue("button_site_delete"), $pagecontent);
	}

	function gallery() {
		$pagecontent = "<h2>".getLanguageValue("button_gallery")."</h2>";
		$pagecontent .= "<p>".getLanguageValue("gallery_text")."</p>";
		$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
		$pagecontent .= "<ul>";
		$pagecontent .= "<li><a href=\"index.php?action=newgallery\">".getLanguageValue("button_gallery_new")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=editgallery\">".getLanguageValue("button_gallery_edit")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=deletegallery\">".getLanguageValue("button_gallery_delete")."</a></li>";
		$pagecontent .= "</ul>";
		return array(getLanguageValue("button_gallery"), $pagecontent);
	}

	function newGallery() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_gallery_new")."</h2>";
		// Galeriebild hochladen
		if ($_SERVER["REQUEST_METHOD"] == "POST"){
		  if (isset($_FILES['uploadfile']) and !$_FILES['uploadfile']['error']) {
		    if (!fileHasExtension($_FILES['uploadfile']['name'], array("jpg", "jpeg", "jpe", "gif", "png")))
		    	$pagecontent .= returnMessage(false, $_FILES['uploadfile']['name'].": ".getLanguageValue("gallery_uploadfile_wrongtype"));
		    elseif (file_exists("../inhalt/".$_POST['cat']."/galerie/".$_FILES['uploadfile']['name']))
		    	$pagecontent .= returnMessage(false, $_FILES['uploadfile']['name'].": ".getLanguageValue("gallery_uploadfile_exists"));
		    elseif (!preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß.]+$/", $_FILES['uploadfile']['name'])) {
		    	$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
		  	}
		  	else {
		    	move_uploaded_file($_FILES['uploadfile']['tmp_name'], "../inhalt/".$_POST['cat']."/galerie/".$_FILES['uploadfile']['name']);
					$galleryconf = new Properties("../inhalt/".$_POST['cat']."/galerie/texte.conf");
					$galleryconf->set($_FILES['uploadfile']['name'], htmlentities(stripslashes($_POST['comment'])));
		    	$pagecontent .= returnMessage(true, getLanguageValue("gallery_upload_success"));
			  }
			}
		}
		$pagecontent .= "<p>".getLanguageValue("gallery_new_text")."</p>";
		$pagecontent .= "<form method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"newgallery\" />";
		$pagecontent .= "<table>";
		// Zeile "KATEGORIE WÄHLEN"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_choose_category_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect()."</td>";
		$pagecontent .= "</tr>";
		// Zeile "BILDDATEI WÄHLEN"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_choose_file_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"file\" class=\"text1\" name=\"uploadfile\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "KOMMENTAR"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_add_comment_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"comment\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "UPLOADEN"
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("button_data_new")."\" /></td></tr>";
		$pagecontent .= "</table></form>";
		return array(getLanguageValue("button_gallery_new"), $pagecontent);
	}

	function editGallery() {
		// Zuerst: Kategorie wählen
		$pagecontent = "<h2>".getLanguageValue("button_gallery_edit")."</h2>";
		$pagecontent .= "<form action=\"index.php\" method=\"GET\"><input type=\"hidden\" name=\"action\" value=\"editgallery\">";
		$pagecontent .= "<table class=\"data\">";
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_cat_for_editgallery")."</td>";
		$pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect()."</td></tr>";
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_category_button")."\" /></td></tr>";
		$pagecontent .= "</table>";
		$pagecontent .= "</form>";

		// Wenn die Kategorie schon gewählt wurde
		if (isset($_GET['cat']) && file_exists("../inhalt/".$_GET['cat'])) {
			$galleryconf = new Properties("../inhalt/".$_GET['cat']."/galerie/texte.conf");
			$msg = "";
			$pagecontent = "<h2>".getLanguageValue("button_gallery_edit")."</h2>";
			// Wenn "Speichern" gedrückt wurde
			if (isset($_GET['save'])) {
				$galleryconf->set($_GET['image'], htmlentities(stripslashes($_GET['comment'])));
				$pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
			} 
			// Wenn "Löschen" gedrückt wurde
			elseif (isset($_GET['delete'])) {
				// nach Bestätigung: löschen
				if (isset($_GET['confirm'])) {
					if ($galleryconf->delete($_GET['image']) && unlink("../inhalt/".$_GET['cat']."/galerie/".$_GET['image']))
						$pagecontent .= returnMessage(true, getLanguageValue("gallery_image_deleted"));
					else
						$pagecontent .= returnMessage(false, getLanguageValue("data_file_delete_error"));
				}
				else
					$pagecontent .= returnMessage(false, $_GET['image'].": ".getLanguageValue("gallery_confirm_delete")." <a href=\"index.php?action=editgallery&amp;delete=true&amp;cat=".$_GET['cat']."&amp;image=".$_GET['image']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=editgallery&amp;cat=".$_GET['cat']."\">".getLanguageValue("no")."</a>");
			} 
			$pagecontent .= "<h3>".getLanguageValue("chosen_category")." ".substr($_GET['cat'], 3, strlen($_GET['cat'])-3)."</h3>";
			$pagecontent .= "<p>".getLanguageValue("gallery_edit_text")."</p>";
			$handle = opendir("../inhalt/".$_GET['cat']."/galerie");
			// alle Bilder der Galerie auflisten
			$counter = 0;
			while (($file = readdir($handle))) {
				if (is_file("../inhalt/".$_GET['cat']."/galerie/".$file) && ($file <> "texte.conf")) {
					$counter++;
					$pagecontent .= "<form action=\"index.php\" method=\"GET\"><input type=\"hidden\" name=\"action\" value=\"editgallery\"><input type=\"hidden\" name=\"cat\" value=\"".$_GET['cat']."\"><input type=\"hidden\" name=\"image\" value=\"".$file."\">";
					$pagecontent .= "<table class=\"data\">";
					$pagecontent .= "<tr>";
					$pagecontent .= "<td class=\"config_row1\"><img src=\"../inhalt/".$_GET['cat']."/galerie/".$file."\" alt=\"$file\" style=\"width:100px;\" /><br />".$file."</td>";
					$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"comment\" value=\"".$galleryconf->get($file)."\" /><input type=\"submit\" name=\"save\" value=\"".getLanguageValue("button_save")."\" class=\"submit\" /> <input type=\"submit\" name=\"delete\" value=\"".getLanguageValue("button_delete")."\" class=\"submit\" /></td>";
					$pagecontent .= "</tr>";
					$pagecontent .= "</table>";
					$pagecontent .= "</form>";
				}
			}
			if ($counter == 0)
				{
					$pagecontent .= "<table class=\"data\">";
					$pagecontent .= "<tr>";
					$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallery_no_data")."</td>";
					$pagecontent .= "<td class=\"config_row2\"></td>";
					$pagecontent .= "</tr>";
					$pagecontent .= "</table>";
				}
		}
		return array(getLanguageValue("button_gallery_edit"), $pagecontent);
	}

	function deleteGallery() {
		// Zuerst: Kategorie wählen
		$pagecontent = "<h2>".getLanguageValue("button_gallery_delete")."</h2>";
		// Wenn die Kategorie schon gewählt wurde
		if (isset($_GET['cat']) && file_exists("../inhalt/".$_GET['cat'])) {
			if (isset($_GET['confirm']) && ($_GET['confirm'] == "true")) {
				$success = true;
				$handle = opendir("../inhalt/".$_GET['cat']."/galerie");
				while ($file = readdir($handle)) {
					if (is_file("../inhalt/".$_GET['cat']."/galerie/".$file))
						if (!unlink("../inhalt/".$_GET['cat']."/galerie/".$file))
							$success = false;
				}
				@fclose(@fopen("../inhalt/".$_GET['cat']."/galerie/texte.conf", "w"));
				if ($success)
					$pagecontent .= returnMessage(true, getLanguageValue("gallery_delete_success"));
				else
					$pagecontent .= returnMessage(false, getLanguageValue("gallery_delete_error"));	
			}
			else {
				$pagecontent .= returnMessage(false, substr($_GET['cat'],3,strlen($_GET['cat'])-3).": ".getLanguageValue("gallery_confirm_deleteall")." <a href=\"index.php?action=deletegallery&amp;delete=true&amp;cat=".$_GET['cat']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletegallery\">".getLanguageValue("no")."</a>");
			}
		}
		$pagecontent .= "<p>".getLanguageValue("gallery_delete_text")."</p>";
		$pagecontent .= "<form action=\"index.php\" method=\"GET\"><input type=\"hidden\" name=\"action\" value=\"deletegallery\">";
		$pagecontent .= "<table class=\"data\">";
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("choose_cat_for_deletegallery")."</td>";
		$pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect()."</td></tr>";
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"submit\" name=\"chosen\" class=\"submit\" value=\"".getLanguageValue("choose_category_button")."\" /></td></tr>";
		$pagecontent .= "</table>";
		$pagecontent .= "</form>";
		return array(getLanguageValue("button_gallery_delete"), $pagecontent);
	}

	function files() {
		$pagecontent = "<h2>".getLanguageValue("button_data")."</h2>";
		$pagecontent .= "<p>".getLanguageValue("data_text")."</p>";
		$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
		$pagecontent .= "<ul>";
		$pagecontent .= "<li><a href=\"index.php?action=newfile\">".getLanguageValue("button_data_new")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=deletefile\">".getLanguageValue("button_data_delete")."</a></li>";
		$pagecontent .= "</ul>";
		return array(getLanguageValue("button_data"), $pagecontent);
	}

	function newFile() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_data_new")."</h2>";
		// Datei hochladen
		if ($_SERVER["REQUEST_METHOD"] == "POST")
		{
		  if (isset($_FILES['uploadfile']) and !$_FILES['uploadfile']['error']) 
		  {
		    if (file_exists("../inhalt/".$specialchars->deleteSpecialChars($_POST['cat'])."/dateien/".$_FILES['uploadfile']['name']))
		    {
		    	$pagecontent .= returnMessage(false, $_FILES['uploadfile']['name'].": ".getLanguageValue("data_uploadfile_exists"));
		    }
		    else 
		    {
		    	if(preg_match("/^[a-zA-Z0-9_\-äöüÄÖÜß.]+$/", $_FILES['uploadfile']['name']))
		    	{
			    	move_uploaded_file($_FILES['uploadfile']['tmp_name'], "../inhalt/".$_POST['cat']."/dateien/".$_FILES['uploadfile']['name']);
			    	$pagecontent .= returnMessage(true, getLanguageValue("data_upload_success"));
		    	}
			    else
			    {
			    	$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
			    }
			  }
			}
		}
		$pagecontent .= "<p>".getLanguageValue("data_new_text")."</p>";
		$pagecontent .= "<form method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"newfile\" />";
		$pagecontent .= "<table><tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("data_choose_category_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\">".getCatsAsSelect()."</td></tr>";
		// Datei auswählen
		$pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_choose_file_text")."</td><td class=\"config_row2\"><input type=\"file\" class=\"text1\" name=\"uploadfile\" /></td></tr>";
		// Button
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("button_data_new")."\" /></td></tr>";
		$pagecontent .= "</table></form>";
		return array(getLanguageValue("button_data_new"), $pagecontent);
	}

	function deleteFile() {
		global $specialchars;
		$pagecontent = "<h2>".getLanguageValue("button_data_delete")."</h2>";
		// Löschen der Datein nach Auswertung der übergebenen Parameter
		if (isset($_GET['cat']) && isset($_GET['file']) && file_exists("../inhalt/".$_GET['cat']) && file_exists("../inhalt/".$_GET['cat']."/dateien/".$_GET['file'])) {
			if (isset($_GET['confirm']) && ($_GET['confirm'] == "true")) {
				if (unlink("../inhalt/".$_GET['cat']."/dateien/".$_GET['file']))
					$pagecontent .= returnMessage(true, getLanguageValue("data_file_deleted"));
				else
					$pagecontent .= returnMessage(false, getLanguageValue("data_file_delete_error"));
			}
			else
				$pagecontent .= returnMessage(false, $_GET['file'].": ".getLanguageValue("data_file_delete_confirm")." <a href=\"index.php?action=deletefile&amp;cat=".$_GET['cat']."&amp;file=".$_GET['file']."&amp;confirm=true\">".getLanguageValue("yes")."</a> - <a href=\"index.php?action=deletefile\">".getLanguageValue("no")."</a>");
		}
		
		
		$pagecontent .= "<p>".getLanguageValue("data_delete_text")."</p>";
		$dirs = getDirs("../inhalt");
		foreach ($dirs as $file) {
			$file = $file."_".specialNrDir("../inhalt", $file);
			if (($file <> ".") && ($file <> "..") && ($subhandle = opendir("../inhalt/".$file."/dateien"))) {
				$pagecontent .= "<h3>".$specialchars->rebuildSpecialChars(substr($file, 3, strlen($file)-3))."</h3>";
				$hasdata = false;
				$pagecontent .= "<table class=\"data\">";
				while (($subfile = readdir($subhandle))) {
					if (($subfile <> ".") && ($subfile <> "..")) {
						$pagecontent .= "<tr><td class=\"config_row1\">$subfile</td><td class=\"config_row2\"><a href=\"../inhalt/".$file."/dateien/".$subfile."\" target=\"_blank\">".getLanguageValue("data_download")."</a> - <a href=\"index.php?action=deletefile&amp;cat=".$file."&amp;file=".$subfile."\">".getLanguageValue("button_delete")."</a></td></tr>";
						$hasdata = true;
					}
				}
				if (!$hasdata)
				$pagecontent .= "<tr><td class=\"config_row1\">".getLanguageValue("data_no_data")."</td><td class=\"config_row2\">&nbsp;</td></tr>";
				$pagecontent .= "</table>";
			}
		}
		return array(getLanguageValue("button_data_delete"), $pagecontent);
	}

	function config() {
		$pagecontent = "<h2>".getLanguageValue("button_config")."</h2>";
		$pagecontent .= "<p>".getLanguageValue("config_text")."</p>";
		$pagecontent .= "<h3>".getLanguageValue("choice_text")."</h3>";
		$pagecontent .= "<ul>";
		$pagecontent .= "<li><a href=\"index.php?action=displaycmsconfig\">".getLanguageValue("button_config_displaycms")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=displayadminconfig\">".getLanguageValue("button_config_displayadmin")."</a></li>";
		$pagecontent .= "<li><a href=\"index.php?action=loginadminconfig\">".getLanguageValue("button_config_loginadmin")."</a></li>";
		$pagecontent .= "</ul>";
		return array(getLanguageValue("button_config"), $pagecontent);
	}

	function configCMSDisplay() {
		global $CMS_CONF;
		global $specialchars;
		$pagecontent .= "<h2>".getLanguageValue("button_config_displaycms")."</h2>";
		// Änderungen speichern
		$changesmade = false;
		if (isset($_GET['apply']) && ($_GET['apply'] == "true")) {
			$changesapplied = false;
			if (isset($_GET['gmw']) && isset($_GET['gmh'])) {
				if(
					preg_match("/^[0-9]+$/", $_GET['gmw']) 
					&& preg_match("/^[0-9]+$/", $_GET['gmh']) 
					&& ($_GET['title'] <> "") 
					&& ($_GET['template'] <> "") 
					&& ($_GET['gallerytemplate'] <> "") 
					&& ($_GET['css'] <> "") 
					&& ($_GET['favicon'] <> "") 
					&& ($_GET['dcat'] <> "") 
					) {
					$CMS_CONF->set("gallerymaxwidth", $_GET['gmw']);
					$CMS_CONF->set("gallerymaxheight", $_GET['gmh']);
					$CMS_CONF->set("websitetitle", htmlentities($_GET['title']));
					$CMS_CONF->set("templatefile", $_GET['template']);
					$CMS_CONF->set("gallerytemplatefile", $_GET['gallerytemplate']);
					$CMS_CONF->set("cssfile", $_GET['css']);
					$CMS_CONF->set("faviconfile", $_GET['favicon']);
					$CMS_CONF->set("defaultcat", $specialchars->deleteSpecialChars($_GET['dcat']));
					if ($_GET['usesyntax'] == "on")
						$CMS_CONF->set("usecmssyntax", "true");
					else
						$CMS_CONF->set("usecmssyntax", "false");
					$pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
				}
				else
					$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
			}
		}
		$pagecontent .= "<p>".getLanguageValue("config_cmsdisplay_text")."</p>";
		$pagecontent .= "<form action=\"index.php\" method=\"get\"><input type=\"hidden\" name=\"action\" value=\"displaycmsconfig\"><input type=\"hidden\" name=\"apply\" value=\"true\">";
		$pagecontent .= "<table>";
		// Zeile "WEBSITE-TITEL"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("websitetitle_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"title\" value=\"".$CMS_CONF->get("websitetitle")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "HTML-TEMPLATE"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("template_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"template\" value=\"".$CMS_CONF->get("templatefile")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "GALERIE-TEMPLATE"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallerytemplate_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"gallerytemplate\" value=\"".$CMS_CONF->get("gallerytemplatefile")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "CSS-DATEI"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("css_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"css\" value=\"".$CMS_CONF->get("cssfile")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "FAVICON"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("favicon_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"favicon\" value=\"".$CMS_CONF->get("faviconfile")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "STANDARD-KATEGORIE"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("defaultcat_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\">";
		$dirs = array();
		$dirs = getDirs("../inhalt");
		$pagecontent .= "<select name=\"dcat\">";
		foreach ($dirs as $element) {
			$myfiles = getFiles("../inhalt/".$element."_".specialNrDir("../inhalt", $element));
			if (count($myfiles) == 0)
				continue;
			$selected = "";
			if ($element."_".$specialchars->rebuildSpecialChars(specialNrDir("../inhalt", $element)) == $CMS_CONF->get("defaultcat"))
				$selected = "selected ";
			$pagecontent .= "<option ".$selected."value=\"".$element."_".$specialchars->rebuildSpecialChars(specialNrDir("../inhalt", $element))."\">".$specialchars->rebuildSpecialChars(specialNrDir("../inhalt", $element))."</option>";
		}
		$pagecontent .= "</select></td>";
		$pagecontent .= "</tr>";
		// Zeile "MAXIMALE BILDBREIE GALERIE"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallerymaxwidth_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"gmw\" value=\"".$CMS_CONF->get("gallerymaxwidth")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "MAXIMALE BILDHÖHE GALERIE"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("gallerymaxheight_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"gmh\" value=\"".$CMS_CONF->get("gallerymaxheight")."\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "NUTZE CMS-SYNTAX"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("usesyntax_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"checkbox\" ";
		if ($CMS_CONF->get("usecmssyntax") == "true")
			$pagecontent .= "checked=checked";
		$pagecontent .= " name=\"usesyntax\">".getLanguageValue("usesyntax_text2")."</td>";
		$pagecontent .= "</tr>";
		// Zeile "ÜBERNEHMEN"
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>";
		$pagecontent .= "</table>";
		$pagecontent .= "</form>";
		return array(getLanguageValue("button_config_displaycms"), $pagecontent);
	}

	function configAdminDisplay() {
		global $ADMIN_CONF;
		// Änderungen speichern
		$changesmade = false;
		if (isset($_GET['apply']) && ($_GET['apply'] == "true")) {
			if (isset($_GET['tooltip']) && ($_GET['tooltip'] == "on")) {
				$ADMIN_CONF->set("showTooltips", "true");
				$changesmade = true;
			}
			else
				$ADMIN_CONF->set("showTooltips", "false");
			if (isset($_GET['lang'])) {
				$ADMIN_CONF->set("language", $_GET['lang']);
				$changesmade = true;
			}
		}
		$pagecontent .= "<h2>".getLanguageValue("button_config_displayadmin")."</h2>";
		if ($changesmade)
			$pagecontent .= returnMessage(true, getLanguageValue("changes_applied"));
		$pagecontent .= "<p>".getLanguageValue("config_admindisplay_text")."</p>";
		$pagecontent .= "<form action=\"index.php\" method=\"get\"><input type=\"hidden\" name=\"action\" value=\"displayadminconfig\"><input type=\"hidden\" name=\"apply\" value=\"true\">";
		$pagecontent .= "<table class=\"data\">";
		// Zeile "ZEIGE TOOLTIPS"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("showTooltips_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"checkbox\" ";
		if (showTooltips()=="true")
			$pagecontent .= "checked=checked";
		$pagecontent .= " name=\"tooltip\">".getLanguageValue("showTooltips_text2")."</td>";
		$pagecontent .= "</tr>";
		// Zeile "SPRACHAUSWAHL"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\"><a accesskey=\"".createNormalTooltip("languagechoose", "language_help", 150)."\"><img class=\"right\" src=\"gfx/information.gif\" alt=\"info\"></a>".getLanguageValue("selectLanguage_text")."</td><td class=\"config_row2\"><select name=\"lang\">";
		if ($handle = opendir('conf')){
			while ($file = readdir($handle)) {
				$selected = "";
				if ($file != "." && $file != "..") {
					if(substr($file,0,9) == "language_") {
						if (substr($file,9,4) == $ADMIN_CONF->get("language"))
							$selected = " selected";
						$pagecontent .= "<option".$selected.">";
						$pagecontent .= substr($file,9,4);
						$pagecontent .= "</option>";
					}
				}
			}
			closedir($handle);
		}
		$pagecontent .= "</select></td></tr>";
		// Zeile "ÜBERNEHMEN"
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>";
		$pagecontent .= "</table>";
		$pagecontent .= "</form>";
		return array(getLanguageValue("button_config"), $pagecontent);
	}

	function configAdminLogin() {
		$pagecontent .= "<h2>".getLanguageValue("button_config_loginadmin")."</h2>";
		$adminconf = new Properties("conf/logindata.conf");
		require_once("Crypt.php");
		$pwcrypt = new Crypt("send 'i cracked your silly code' to codecracked@azett.com");
		// Übergebene Werte prüfen
		if ($_POST['apply'] == "true") {
			if (
			// Alle Felder übergeben...
			isset($_POST['oldname']) && isset($_POST['oldpw']) && isset($_POST['newname']) && isset($_POST['newpw']) && isset($_POST['newpwrepeat'])
			// ...und keines leer?
			&& ($_POST['oldname'] <> "" ) && ($_POST['oldpw'] <> "" ) && ($_POST['newname'] <> "" ) && ($_POST['newpw'] <> "" ) && ($_POST['newpwrepeat'] <> "" )
			// Alte Zugangsdaten korrekt? 
			&& ($_POST['oldname'] == $adminconf->get("name")) && ($_POST['oldpw'] == $pwcrypt->decrypt($adminconf->get("pw")))
			// Neuer Name wenigstens 5 Zeichen lang?
			&& (strlen($_POST['newname']) >= 5)
			// Neues Paßwort zweimal exakt gleich eingegeben?
			&& ($_POST['newpw'] == $_POST['newpwrepeat'])
			// Neues Paßwort wenigstens sechs Zeichen lang und mindestens aus kleinen und großen Buchstaben sowie Zahlen bestehend?
			&& (strlen($_POST['newpw']) >= 6) && preg_match("/[0-9]/", $_POST['newpw']) && preg_match("/[a-z]/", $_POST['newpw']) && preg_match("/[A-Z]/", $_POST['newpw'])
			) {
			$adminconf->set("name", $_POST['newname']);
			$adminconf->set("pw", $pwcrypt->encrypt($_POST['newpw']));
			$adminconf->set("initialpw", "false");
			$pagecontent .= returnMessage(true, getLanguageValue("config_userdata_changed"));
			}
			else
				$pagecontent .= returnMessage(false, getLanguageValue("invalid_values"));
		}
		$pagecontent .= "<p>";
		$pagecontent .= getLanguageValue("config_adminlogin_text");
		$pagecontent .= "</p>";
		$pagecontent .= "<form action=\"index.php\" method=\"post\"><input type=\"hidden\" name=\"apply\" value=\"true\">";
		$pagecontent .= "<table class=\"data\">";
		// Zeile "ALTER NAME"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("config_oldname_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"oldname\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "ALTES PASSWORT"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("config_oldpw_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"oldpw\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "NEUER NAME"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("config_newname_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"text\" class=\"text1\" name=\"newname\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "NEUES PASSWORT"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("config_newpw_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"newpw\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "NEUES PASSWORT - WIEDERHOLUNG"
		$pagecontent .= "<tr>";
		$pagecontent .= "<td class=\"config_row1\">".getLanguageValue("config_newpwrepeat_text")."</td>";
		$pagecontent .= "<td class=\"config_row2\"><input type=\"password\" class=\"text1\" name=\"newpwrepeat\" /></td>";
		$pagecontent .= "</tr>";
		// Zeile "ÜBERNEHMEN"
		$pagecontent .= "<tr><td class=\"config_row1\">&nbsp;</td><td class=\"config_row2\"><input type=\"hidden\" name=\"action\" value=\"loginadminconfig\" /><input type=\"submit\" class=\"submit\" value=\"".getLanguageValue("config_submit")."\"/></td></tr>";

		$pagecontent .= "</table>";
		$pagecontent .= "</form>";
		return array(getLanguageValue("button_config_loginadmin"), $pagecontent);
	}
	
	// Anzeige der Editieransicht
	function showEditPageForm($cat, $page, $action)	{
		global $CMS_CONF;
		global $specialchars;
		$file = "../inhalt/".$cat."/".$page;
		if (file_exists($file)) {
			$handle=fopen($file, "r");
			$pagecontent = fread($handle, filesize($file));
			fclose($handle);
		}
		else
			$pagecontent = "[ueber1|".substr($page, 3,strlen($page)-7)."]";
		
		if ($CMS_CONF->get("usecmssyntax") == "true") {
			// Toolbar
			$content .="<p class=\"toolbar\"><img class=\"js\" title=\"[link| ... ]\" alt=\"Link\" src=\"gfx/jsToolbar/link.png\" onClick=\"insert('[link|', ']')\">";
	    $content .="<img class=\"js\" alt=\"eMail\" title=\"[mail| ... ]\" src=\"gfx/jsToolbar/mail.png\" onClick=\"insert('[mail|', ']')\">";
	    $content .="<img class=\"js\" alt=\"Seite\"	title=\"[seite| ... ]\" src=\"gfx/jsToolbar/seite.png\" onClick=\"insert('[seite|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Kategorie\"	title=\"[kategorie| ... ]\" src=\"gfx/jsToolbar/kategorie.png\" onClick=\"insert('[kategorie|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Datei\" title=\"[datei| ... ]\" src=\"gfx/jsToolbar/datei.png\" onClick=\"insert('[datei|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Galerie\"	title=\"[galerie| ... ]\" src=\"gfx/jsToolbar/galerie.png\" onClick=\"insert('[galerie|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Bild\" title=\"[bild| ... ]\" src=\"gfx/jsToolbar/bild.png\" onClick=\"insert('[bild|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Bildlinks\"	title=\"[bildlinks| ... ]\" src=\"gfx/jsToolbar/bildlinks.png\" onClick=\"insert('[bildlinks|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Bildrechts\" title=\"[bildrechts| ... ]\" src=\"gfx/jsToolbar/bildrechts.png\" onClick=\"insert('[bildrechts|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Fett\" title=\"[fett| ... ]\" src=\"gfx/jsToolbar/fett.png\" onClick=\"insert('[fett|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Kursiv\" title=\"[kursiv| ... ]\" src=\"gfx/jsToolbar/kursiv.png\" onClick=\"insert('[kursiv|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Fettkursiv\" title=\"[fettkursiv| ... ]\" src=\"gfx/jsToolbar/fettkursiv.png\" onClick=\"insert('[fettkursiv|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Überschrift1\" title=\"[ueber1| ... ]\" src=\"gfx/jsToolbar/ueber1.png\" onClick=\"insert('[ueber1|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Überschrift2\" title=\"[ueber2| ... ]\" src=\"gfx/jsToolbar/ueber2.png\" onClick=\"insert('[ueber2|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Überschrift3\" title=\"[ueber3| ... ]\" src=\"gfx/jsToolbar/ueber3.png\" onClick=\"insert('[ueber3|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Liste1\" title=\"[liste1| ... ]\" src=\"gfx/jsToolbar/liste1.png\" onClick=\"insert('[liste1|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Liste2\" title=\"[liste2| ... ]\" src=\"gfx/jsToolbar/liste2.png\" onClick=\"insert('[liste2|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Liste3\" title=\"[liste3| ... ]\" src=\"gfx/jsToolbar/liste3.png\" onClick=\"insert('[liste3|', ']')\">";
	  	$content .="<img class=\"js\" alt=\"Horizontale Linie\" title=\"[----]\" src=\"gfx/jsToolbar/linie.png\" onClick=\"insert('[----]', '')\"></p>";
	  }

		// Seiteninhalt
		$content .= "<textarea name=\"pagecontent\">".$pagecontent."</textarea>";
		$content .= "<input type=\"hidden\" name=\"page\" value=\"$page\" />";
		$content .= "<input type=\"hidden\" name=\"action\" value=\"$action\" />";
		$content .= "<input type=\"hidden\" name=\"cat\" value=\"$cat\" />";
		$content .= "<input type=\"submit\" name=\"save\" value=\"".getLanguageValue("button_save")."\" accesskey=\"s\" />&nbsp;";
		$content .= "<input type=\"submit\" name=\"cancel\" value=\"".getLanguageValue("button_cancel")."\" accesskey=\"a\" />";
		return $content;
	}
	
	function saveContentToPage($content, $page) {
		global $specialchars;
		$handle=fopen($specialchars->deleteSpecialChars($page), "w");
		if (get_magic_quotes_gpc())
			fputs($handle, trim(stripslashes($content)));
		else
			fputs($handle, trim($content));
		fclose($handle);
	}
	
		// Lösche ein Verzeichnis rekursiv
	function deleteDir($path) {
		$success = true;
		// Existenz prüfen
		if (!file_exists($path))
			return false;
		$handle = opendir($path);
		while ($currentelement = readdir($handle)) {
			if (($currentelement == ".") || ($currentelement == ".."))
				continue;
			// Verzeichnis: Rekursiver Funktionsaufruf
			if (is_dir($path."/".$currentelement))
				$success = deleteDir($path."/".$currentelement);
			// Datei: löschen
			else
				$success = unlink($path."/".$currentelement);				
		}
		closedir($handle);
		// Verzeichnis löschen
		$success = rmdir($path);
		return $success;
	}
	
	// Überprüfe, ob die gegebene Datei eine der übergebenen Endungen hat
	function fileHasExtension($filename, $extensions) {
		foreach ($extensions as $ext) {
			if (strtolower(substr($filename, strlen($filename)-(strlen($ext)+1), strlen($ext)+1)) == ".".strtolower($ext))
				return true;
		}
		return false;
	}
	
	// Gib Erfolgs- oder Fehlermeldung zurück
	function returnMessage($success, $message) {
		if ($success == true)
			return "<span class=\"erfolg\">".$message."</span>";
		else 
			return "<span class=\"fehler\">".$message."</span>";
	}
	
	function returnToolbar() {
		$js .= "<script type=\"text/javascript\">";
		$js .= "<!--";
		$js .= "function insert(aTag, eTag) {";
	  $js .= "var input = document.forms['form'].elements['pagecontent'];";
	  $js .= "input.focus();";
	  /* für Internet Explorer */
	  $js .= "if(typeof document.selection != 'undefined') {";
	  /* Einfügen des Formatierungscodes */
	  $js .=  "var range = document.selection.createRange();";
	  $js .=  "var insText = range.text;";
	  $js .=  "range.text = aTag + insText + eTag;";
	    /* Anpassen der Cursorposition */
	  $js .=  "range = document.selection.createRange();";
	  $js .=  "if (insText.length == 0) {";
	  $js .=  "range.move('character', -eTag.length);";
	  $js .=  "} else {";
	  $js .=  "range.moveStart('character', aTag.length + insText.length + eTag.length);";
	  $js .=  "}";
	  $js .=  "range.select();";
	  $js .=  "}";
	  /* für neuere auf Gecko basierende Browser */
	  $js .=  "else if(typeof input.selectionStart != 'undefined')";
	  $js .=  "{";
	  /* Einfügen des Formatierungscodes */
	  $js .=  "var start = input.selectionStart;";
	  $js .=  "var end = input.selectionEnd;";
	  $js .=  "var insText = input.value.substring(start, end);";
	  $js .=  "input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);";
	    /* Anpassen der Cursorposition */
	  $js .=  "var pos;";
	  $js .=  "if (insText.length == 0) {";
	  $js .=  "pos = start + aTag.length;";
	  $js .=  "} else {";
	  $js .=  "pos = start + aTag.length + insText.length + eTag.length;";
	  $js .=  "}";
	  $js .=  "input.selectionStart = pos;";
	  $js .=  "input.selectionEnd = pos;";
	  $js .=  "}";
	  /* für die übrigen Browser */
	  $js .=  "else";
	  $js .=  "{";
	  /* Abfrage der Einfügeposition */
	  $js .=  "var pos;";
	  $js .=  "var re = new RegExp('^[0-9]{0,3}$');";
	  $js .=  "while(!re.test(pos)) {";
	  $js .=  "pos = prompt(\"Einfügen an Position (0..\" + input.value.length + \"):\", \"0\");";
	  $js .=  "}";
	  $js .=  "if(pos > input.value.length) {";
	  $js .=  "pos = input.value.length;";
	  $js .=  "}";
	  /* Einfügen des Formatierungscodes */
	  $js .=  "var insText = prompt(\"Bitte geben Sie den zu formatierenden Text ein:\");";
	  $js .=  "input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);";
	  $js .=  "}";
		$js .=  "}";
		$js .=  "//-->";
	  $js .=  "</script>";
	  
	  return $js;
	}