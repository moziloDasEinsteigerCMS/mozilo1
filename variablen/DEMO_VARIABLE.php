<?php

/***************************************************************
* Die Funktion muß exakt "get[NAME_DER_VARIABLE]" heißen.
* Sie bekommt einen String-Parameter übergeben.
***************************************************************/

function getDEMO_VARIABLE($value) {
    
    
    /***************************************************************
    * Es kann auf sämtliche Variablen und Funktionen der index.php 
    * zugegriffen werden.
    * 
    * Der Wert, mit dem die Variable ersetzt werden soll, muß per
    * "return" zurückgegeben werden.
    * 
    * Der String-Parameter $value ist der Wert bei erweiterten
    * Variablen: {VARIABLE|wert}
    * Ist die Variable nicht erweitert ( {VARIABLE} ), wird $value
    * als Leerstring ("") übergeben.
    * Man kann den $value-Parameter nutzen, muß es aber nicht.
    * 
    * Beispiele:
    ***************************************************************/
    
    
    
    // Nutzung des Parameters mit mehreren kommaseparierten Werten
    // (werden in das Array $values gepackt)
    // - Nutzung: {DEMO_VARIABLE|Wert1,Wert2,Wert3,...}
    // - Ausgabe: Der erste Wert ist Wert1
    $values = explode(",", $value);
    // return ("Der erste Wert ist ".$values[0]); // zum Testen entkommentieren!
    
    
    
    // Nutzung des Parameters mit CMS-Variablen - Namen aktueller 
    // Inhaltsseite in Großbuchstaben zurückgeben:
    // - Nutzung: {DEMO_VARIABLE|{PAGE_NAME}}
    // return (strtoupper($value)); // zum Testen entkommentieren!
    
    
    
    // Auslesen des Website-Titels aus der CMS-Konfiguration:
    global $mainconfig;
    $titelderseite = $mainconfig->get("websitetitle");
    // return $titelderseite; // zum Testen entkommentieren!
    
    
    
    // Aufruf der Funktion, die das Hauptmenü erstellt:
    $hauptmenue = getMainMenu();
    // return $hauptmenue; // zum Testen entkommentieren!
    
    
    
    // Sicheres Auslesen eines übergebenen POST- bzw. GET-Parameters:
    $anfrage = getRequestParam("parameter", true);
    // return $anfrage; // zum Testen entkommentieren!
    
    
    
    // Tageszeitabhängige Begrüßung:
    $stunde = date("H");
    if ($stunde <= 10) {
        $begruessung ="Guten Morgen!";
    }
    else if ($stunde <= 16) {
        $begruessung ="Guten Tag!";
    }
    else if ($stunde <= 23) {
        $begruessung ="Guten Abend!";
    }
    else {
        $begruessung ="Hallo!";
    }
    return $begruessung; // zum Testen entkommentieren!
    
}

?>