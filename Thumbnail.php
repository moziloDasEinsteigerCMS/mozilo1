<?php

/* 
* 
* $Revision: 19 $
* $LastChangedDate: 2008-03-12 18:06:54 +0100 (Mi, 12 Mrz 2008) $
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
	  $width = $size[0]; 
	  $height = $size[1]; 
	  $newheight = 100;
	  $newwidth = intval($width*$newheight/$height); 
	
	  // GIF 
	  if($size[2] == 1) { 
		  $originalpic = ImageCreateFromGIF($dir_origin.$pic); 
		  $resizedpic = ImageCreateTrueColor($newwidth,$newheight); 
		  ImageCopyResized($resizedpic,$originalpic,0,0,0,0,$newwidth,$newheight,$width,$height); 
		  ImageGIF($resizedpic, $dir_target.$pic); 
	  } 
	
	  // JPG 
	  elseif($size[2] == 2) { 
		  $originalpic=ImageCreateFromJPEG($dir_origin.$pic); 
		  $resizedpic=ImageCreateTrueColor($newwidth,$newheight); 
		  ImageCopyResized($resizedpic,$originalpic,0,0,0,0,$newwidth,$newheight,$width,$height); 
		  ImageJPEG($resizedpic, $dir_target.$pic); 
	  } 
	
	  // PNG 
	  elseif($size[2] == 3) { 
		  $originalpic = ImageCreateFromPNG($dir_origin.$pic); 
		  $resizedpic = ImageCreateTrueColor($newwidth,$newheight); 
		  ImageCopyResized($resizedpic,$originalpic,0,0,0,0,$newwidth,$newheight,$width,$height); 
		  ImagePNG($resizedpic, $dir_target.$pic); 
	  } 
	  
	  // sonstige Formate
	  else {
	  	// einfach kopieren
	  	copy($dir_origin.$pic, $dir_target.$pic);
	  }
	}
}
?>