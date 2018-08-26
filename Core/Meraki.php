<?php 

namespace Core;

class Meraki
{	
	/* --------------------- WIFI CODE ----------------------------- */

	static function generate_wifi_code($numStr = 6) {
			
		//  500 000 codes différents peuvent être générés avec 4 lettres majuscules
	    srand( (double)microtime()*rand(1000000,9999999) ); // Genere un nombre aléatoire  
	    $arrChar = array(); // Nouveau tableau  
	  	$uId = '';

	    for( $i=65; $i<90; $i++ ) {  
	        array_push( $arrChar, chr($i) ); // Ajoute A-Z au tableau  
	    }  
	  
	    for( $i=0; $i< $numStr; $i++ ) {  
	        $uId .= $arrChar[rand( 0, (count( $arrChar ) -1) )]; // Ecrit un aléatoire  
	    }  
	    return $uId;  
	}
}