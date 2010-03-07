<?php

/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/


/*
######
		
	Ein Sonderzeichenkonverter für moziloCMS

######
*/
	require_once("../cms/SpecialChars.php");
	$specialchars = new SpecialChars();
	
	$htmlstart = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
		."<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"de\" lang=\"de\">\n"
		."<head>\n"
		."<meta http-equiv=\"Content-Type\" content=\"text/html;charset=iso-8859-1\" />\n"
		."<title>moziloCMS-Sonderzeichenkonverter / moziloCMS special character converter</title>\n"
		."<script type=\"text/javascript\"><!--\n"
		."function setInputFocus() {\n"
		."if (document.form) {\n"
  	."document.form.input.focus();\n"
  	."document.form.input.select();\n"
  	."}\n"
		."}\n"
		."--></script>\n"
		."<style type=\"text/css\"> @import \"converter.css\"; </style>\n"
		."</head>\n"
		."<body onload=\"setInputFocus();\">\n"
		."<h1>moziloCMS-Sonderzeichenkonverter / moziloCMS special character converter</h1>\n"
		."<div class=\"content\">\n";
	$htmlend = "</div>\n"
	."<a href=\"http://cms.mozilo.de\" target=\"_blank\">cms.mozilo.de</a>\n"
	."</body>\n"
	."</html>\n";
	
	$input = "";
	$type = 0;
	if (isset($_GET['input']))
		$input = stripslashes($_GET['input']);
	if (isset($_GET['type']))
		$type = $_GET['type'];
		

	$html = $htmlstart
	."<table summary=\"\">\n"
	."<tr>\n"
	."<td>\n"
	."<h2>Eingabe / Input</h2>\n"
	.getInputForm($input, $type)."\n"
	."</td>\n"
	."<td>\n"
	."<h2>Ausgabe / Output</h2>\n"
	."<span class=\"output\">".getConvertedText($input, $type)."</span>\n"
	."</td>\n"
	."</tr>\n"
	."<tr>\n"
/*	."<td colspan=\"2\">\n"
	."<h2>Unterstützte Zeichen / Supported characters</h2>\n"
	.$specialchars->getSpecialCharsString(" ", 52)."\n"
	."</td>\n"
	."</tr>\n"*/
	."</table>\n"
	.$htmlend;
	
	echo $html;
	
	function getInputForm($input, $type){
		global $language;

		$checked0 = "";
		$checked1 = "";
		if ($type == 0)
			$checked0 = " checked=\"checked\"";
		elseif ($type == 1)
			$checked1 = " checked=\"checked\"";

		$form = "<form accept-charset=\"ISO-8859-1\" method=\"get\" action=\"index.php\" name=\"form\" >\n"
		."<input type=\"text\" id=\"input\" name=\"input\" value=\""
		.htmlentities($input,ENT_COMPAT,'ISO-8859-1')
		."\" /> \n"
		."<input type=\"submit\" id=\"ok\" value=\" OK \" /><br />\n"
		."<input type=\"radio\" name=\"type\" value=\"0\"$checked0 />Sonderzeichen ersetzen / replace special characters <br />\n"
		."<input type=\"radio\" name=\"type\" value=\"1\"$checked1 />Sonderzeichen wiederherstellen / restore special characters <br /><br />\n"
		."</form>\n";
		return $form;
	}
	
	function getConvertedText($text, $type) {
		global $specialchars;
		if ($text == "") {
			return "";
		}
		switch ($type) {
			// ersetzen
			case 0:
				return $specialchars->replaceSpecialChars($text, false);
				break;
			// wiederherstellen
			case 1:
				return $specialchars->rebuildSpecialChars($text, false, false);
				break;
		}


	}
	
?>