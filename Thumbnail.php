<?php

/* 
* 
* $Revision: 186 $
* $LastChangedDate: 2009-04-06 20:42:30 +0200 (Mo, 06 Apr 2009) $
* $Author: arvid $
*
*/


/*
######
INHALT
######
		
		Projekt "Flatfile-basiertes CMS für Einsteiger"
		Thumbnail-Erzeugung für die Galerie
		Mai 2006
		Klasse ITF04-1
		Industrieschule Chemnitz

		Ronny Monser
		Arvid Zimmermann
		Oliver Lorenz
		-> mozilo

######
*/

class Thumbnail {
	
// ------------------------------------------------------------------------------    
// Konstruktor
// ------------------------------------------------------------------------------
	function Thumbnail(){
	}

	// ------------------------------------------------------------------------------
	// Thumbnail anlegen
	// ------------------------------------------------------------------------------
	function createThumb($pic, $dir_origin, $dir_target) {
		// Bilddaten feststellen 
		$size = getimagesize($dir_origin.$pic); 
		$width = 100; 
		$height = round($size[1]/$size[0]*$width); 

		// Mimetype herausfinden
		$image_typ = strtolower(str_replace('image/','',$size['mime']));
		if($image_typ == "gif" or $image_typ == "png" or $image_typ == "jpeg") {
			$image_erzeugen = "imagecreatefrom$image_typ";
			$originalpic = $image_erzeugen($dir_origin.$pic);
			// es ist ein ein Palette-Image
			if(!imageistruecolor($originalpic)) { 
				$transparentcolor = imagecolortransparent($originalpic);
				$resizedpic = imagecreate($width,$height);
				imagepalettecopy($resizedpic,$originalpic);
				if($transparentcolor >= 0) {
					imagefill($resizedpic,0,0,$transparentcolor);
					imagecolortransparent($resizedpic,$transparentcolor);
				}
			}
			// es ist ein TrueColor-Image 
			else {
				$trans = "nein";
				// PNG: Pixelweise auf Transparenz prüfen
				if($image_typ == "png") {
					$step_h = round($size[1] * 0.005); # kleiner Hack, daß nicht ganz so
					$step_w = round($size[0] * 0.005); # viele Pixel untersucht werden müssen
					for ($h = 0; $h < $size[1]; $h = $h + $step_h) {
						if($trans == "ja") break;
						for ($w = 0; $w < $size[0]; $w = $w + $step_w) {
							$alpha = imagecolorsforindex($originalpic,imagecolorat($originalpic,$w,$h));
							if($alpha['alpha'] > 0) { 
								$trans = "ja"; 
								break; 
							}
						}
					}
				}
				$resizedpic = imagecreatetruecolor($width,$height);
				// es ist ein TrueColor-Image mit Tranparenz
				if($trans == "ja") { 
					imagealphablending($resizedpic, false);
					$transparentcolor = imagecolorallocatealpha($resizedpic,0,0,0,127);
					imagefill($resizedpic,0,0,$transparentcolor);
					imagesavealpha($resizedpic,true);
				}
			}
			// Verkleinertes Bild erzeugen und abspeichern
			imagecopyresized($resizedpic, $originalpic, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
			$image_erzeugen = "image$image_typ";
			$image_erzeugen($resizedpic, $dir_target.$pic);
			// Aufräumen
			imagedestroy($originalpic);
			imagedestroy($resizedpic);
		}
		// sonstige Formate
		else {
			// einfach kopieren
			copy($dir_origin.$pic, $dir_target.$pic);
		}
	}
}
?>