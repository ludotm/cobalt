<?php 

namespace Core;

class Tools
{	
	/* --------------------- VALIDATORS ----------------------------- */

	static function is_email ($email) {
		return preg_match('~^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$~i', $email);
	}

	static function is_url ($url) {
		return preg_match('~^(http|ftp)(s)?\:\/\/((([a-z0-9\-]{1,25})(\.)?){2,7})($|/.*$)~i', $url);
	}

	/* --------------------- STRING FUNCTIONS ----------------------------- */

	static function clean_string ($str, $delimiter='-') 
	{
		// special cases
		$str = str_replace(array("'", '"', "&", "%"), array(' ',' ',' and ', '%25'), $str);

		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;

		/* ANCIENNE VERSION 
		$url = str_replace("&"," and ",$url);		
		$url = preg_replace("`\[.*\]%`U","",$url);
		$url = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$url);
		$url = str_replace("%","%25",$url);
		
		$url = htmlentities($url, ENT_COMPAT,'UTF-8');
		$url = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i","\\1", $url );		
		
		$url_temp = preg_replace( array("`[ :/;?^$\='\"()#&]`i","`[-]+`") , "-", $url);
		$url_temp = ( $url_temp == "" ) ? $type : strtolower(trim($url_temp, '-'));

		if($url_temp != "")	
		return $url_temp;
		else
		return $url;
		*/
	}

	static function truncate($string, $length, $endpoint='...')
	{
		if (mb_strlen($string) > $length) {

			$string = substr($string, 0, $length);
			$espace = strrpos($string, " ");
			$string = substr($string, 0, $espace);
			return $string.$endpoint;
		}
		else
		return $string;
	}

	static function toUpperCase($string)
	{
		return strtoupper(strtr($string, "äâàáåãéèëêòóôõöøìíîïùúûüýñçþÿæœðø", "AAAAAAEEEEOOOOOØIIIIUUUUYNCÞYAOOØ"));
	}

	static function plurialize($word, $count)
	{
		return $count>1 ? $word.'s' : $word;
	}

	/* --------------------- MATH FUNCTIONS ----------------------------- */

	static function perc($base, $number, $round=1) 
	{
		return round($base != 0 ? $number*100 / $base : 0, $round);
	}

	static function display_price($price) 
	{
		return number_format ( $price, 2,"," ," " );
	}

	/* --------------------- MOT DE PASSE ----------------------------- */

	static function generate_password($nb_chars, $special_chars=false, $uppercase=false) 
	{
		$password = "";
       
        $string = "abcdefghjkmnopqrstuvwxyz0123456789";
        if ($uppercase) {
        	$string .= "ABCDEFGHJKLMNOPQRSTUVWXYZ";
        }
        if ($special_chars) {
        	$string .= "+@!$%?&";
        }
        $string_lenght = strlen($string);
       
        for($i = 1; $i <= $nb_chars; $i++)
        {
            $random = mt_rand(0,($string_lenght-1));
            $password .= $string[$random];
        }

        return $password;  
	}

	/* --------------------- DATE & TIME FUNCTIONS ----------------------------- */

	static function convert_date ($date, $format='d F Y')
	{
		/* FORMATS :
		D : jour semaine 3 lettres
		l : jour semaine complet
		d : jour du mois
		m : mois numérique
		F : mois textuel
		Y : année complète
		y : année 2 chiffes
		H : heure
		i : minute
		s : secondes
		*/

		if ($date == '0000-00-00' || $date == '0' || !$date)
		return '-';
		
		if (is_numeric($date) || 1 === preg_match( '~^[1-9][0-9]*$~', $date )) { // is timestamp?
			$new_date = date($format, $date);
		} else {
			$new_date = date($format, strtotime($date));
		}

		if (strpos($format, 'F') !== false) {
			$new_date = str_replace(
				array('January','February','March','April','May','June','July','August','September','October','November','December'), 
				array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'), 
				$new_date);
		} 
		if (strpos($format, 'M') !== false) {
			$new_date = str_replace(
				array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'), 
				array('Jan','Fév','Mar','Avr','Mai','Jun','Jui','Aoû','Sep','Oct','Nov','Déc'), 
				$new_date);
		} 
		
		if (strpos($format, 'D') !== false) {
			$new_date = str_replace(
				array('Mon','Tue','Wed','Thu','Fri','Sat','Sun'), 
				array('Lun','Mar','Mer','Jeu','Ven','Sam','Dim'), 
				$new_date);
		} 
		if (strpos($format, 'l') !== false) {
			$new_date = str_replace(
				array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), 
				array('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'), 
				$new_date);
		} 

		return $new_date;
	}	
	
	static function convert_time ($time)
	{
		if ($time == '00:00:00')
		return '-';
		
		$split = split(":",$time); 
		$hour = $split[0] == '00' ? "" : $split[0];
		$minute = $split[1] == '00' ? '00' : $split[1];
		$second = $split[2] == '00' ? '00' : $split[2];
		
		switch ($format) 
		{
			case "clock":
			$hour = $hour == "" ? "00" : $hour;
			return $hour."h".$minute;
			break;
			
			case "duree":
			if ($hour == "")
			return $minute.":".$second;
			else
			return $hour.":".$minute.":".$second;
			break;
		}
	}	

	static function days_left ($date, $date_reference=null)
	{
		$date_reference = !$date_reference ?  date('Y-m-d')	: (strtotime($date_reference) ? $date_reference : date('Y-m-d' ,$date_reference)) ;
		$date = !strtotime($date) ? $date : strtotime($date) ;
		return ceil(($date - (strtotime($date_reference.' 23:59:00')) ) /3600/24) ;
	}	

	/* --------------------- ARRAYS FUNCTIONS ----------------------------- */

	//tri un tableau en fonction d'un élément
	static function msort($array, $id="id", $sort_ascending=true) {
        
        $temp_array = array();
        
        while(count($array)>0) {

            $lowest_id = 0;
            $index=0;

            foreach ($array as $item) {
                
                if (isset($item[$id])) {
                    
                    if ($array[$lowest_id][$id]) {
                    	
                    	if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
                        	$lowest_id = $index;
                    	}
                    }
                }
                $index++;
            }
            $temp_array[] = $array[$lowest_id];
            $array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
        }
        
        if ($sort_ascending) {
        	return $temp_array;

        } else {
            return array_reverse($temp_array);
        }
    }

    static function toArray($obj) 
    {
    	return get_object_vars ($obj);
    }
    
    /* ---------- JSON ENCODE WITH JAVASCRIPT EXECUTION ---------------- */

    static function json_encode_js ($obj)
    {
    	$json_string = json_encode($obj);
    	$json_string = str_replace('"@@','',$json_string);
		$json_string = str_replace('@@"','',$json_string);
		return $json_string;
    }

    /* --------------------- FILES FUNCTIONS ----------------------------- */

	static function get_extension ($filename) 
    {
    	return substr(strrchr($filename,'.'),1);
    }

    /* --------------------- COLOR FUNCTIONS ----------------------------- */

    static function change_color_ligh($color, $variance) 
    {
    	$color=substr($color,1,6);
          $cl=explode('x',wordwrap($color,2,'x',3));
          $color='';
          for($i=0;$i<=2;$i++){
           $cl[$i]=hexdec($cl[$i]);
           $cl[$i]=$cl[$i]+$variance;
           if($cl[$i]<0) $cl[$i]=0;
           if($cl[$i]>255) $cl[$i]=255;
           $color.=StrToUpper(substr('0'.dechex($cl[$i]),-2));
          }
          return '#'.$color; 
    }	
    static function lighten($color, $variance) 
    {
    	$variance = abs($variance);
    	return self::change_color_ligh($color, $variance);
    }
    static function darken($color, $variance) 
    {
    	$variance = -abs($variance);
    	return self::change_color_ligh($color, $variance);
    }
    static function hex2rgb($hex, $return="string") 
    {
	   $hex = str_replace("#", "", $hex);

	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);
	   if ($return == "string") {
	   		return 'rgb('.implode(",", $rgb).')';
	   }
	   return $rgb; // returns an array with the rgb values
	}
	static function hex2rgba($hex, $opacity) 
    {
    	$rgb = self::hex2rgb($hex, "array");
    	return 'rgba('.implode(",", $rgb).','.$opacity.')';
    }

    /* --------------------- TRACKING FUNCTIONS ----------------------------- */

    static function get_browser() 
    {
    	$agent = $_SERVER['HTTP_USER_AGENT'];

		if(preg_match('/Firefox/i',$agent)) $browser = 'Firefox'; 
		elseif(preg_match('/Mac/i',$agent)) $browser = 'Mac';
		elseif(preg_match('/Chrome/i',$agent)) $browser = 'Chrome'; 
		elseif(preg_match('/Opera/i',$agent)) $browser = 'Opera';
		elseif(preg_match('/Safari/i',$agent)) $browser = 'Safari'; 
		elseif(preg_match('/MSIE/i',$agent)) $browser = 'IE'; 
		else $browser = 'Unknown';

		return $browser;
    }

    static function get_os() 
    {
    	$agent = $_SERVER['HTTP_USER_AGENT'];

		if(preg_match('/Linux/i',$agent)) $os = 'Linux';
		elseif(preg_match('/Mac/i',$agent)) $os = 'Mac'; 
		elseif(preg_match('/iPhone/i',$agent)) $os = 'iPhone'; 
		elseif(preg_match('/iPad/i',$agent)) $os = 'iPad'; 
		elseif(preg_match('/Droid/i',$agent)) $os = 'Droid'; 
		elseif(preg_match('/Unix/i',$agent)) $os = 'Unix'; 
		elseif(preg_match('/Windows/i',$agent)) $os = 'Windows';
		else $os = 'Unknown';
    	
    	return $os;
    }

    /* --------------------- CONVERT PHONE NUMBER ----------------------------- */
    // convertir un numéro de téléphone sans espaces en numéro avec espace pour plus de lisibilité
    static function convert_phone($phone) 
    {
    	return implode(" ", str_split($phone, 2));
    }

	/* --------------------- NOTE FUNCTIONS ----------------------------- */

	static function convert_note($note,$css='') 
	{
		if ($note != 0)
		{
			$tableNote = "<div style='width:86px; height:16px;".$css."'>";
			for ($i=1; $i<=5; $i++)
			{
				$id_star = ($note >= ($i*2)) ? 1 : ($id_star = ($note == ($i*2)-1) ? 2 : 3);
				$tableNote .= "<img src='".REP_IMG."star".$id_star.".gif'>";
			}
			$tableNote .= "</div>";
			return $tableNote;
		}
		else
		return "";
	}



	/* --------------------- WORKING DAYS FUNCTIONS ----------------------------- */

	static function is_working_day ($date, $feries=null)
	{	
		$timestamp = strtotime($date);
		if (!is_array($feries)) {
			$feries = self::get_feries_days(date('Y', $timestamp));
		}
		// si samedi ou dimanche, ou si ferié
		if (!in_array(date('w', $timestamp), array(0, 6)) && !in_array(date('d-m-Y',$timestamp), $feries)) {
			return true;
		} else {
			return false;
		}
	}

	static function next_working_day ($date, $feries=null, $j_plus=1, $weekend_day1=0, $weekend_day2=6)
	{	
		$timestamp = strtotime($date);
		$one_day = ($j_plus>0) ? 86400:-86400;
		if (!is_array($feries)) {
			$feries = self::get_feries_days(date('Y', $timestamp));
		}
		
		$i = 1;
		$current_testing_date = $timestamp;

		do {
			$current_testing_date += $one_day;
			if (!in_array(date('w', $current_testing_date), array(0, 6)) && !in_array(date('d-m-Y',$current_testing_date), $feries)) {
	        	$i++;
	        } 
		} while ($i<=$j_plus);

		return date('Y-m-d', $current_testing_date);
	}

	static function get_feries_days ($year) 
	{
		$feries=array();
	    $feries[]=date("01-01-".$year);
	    $feries[]=date("01-05-".$year);
	    $feries[]=date("08-05-".$year);
	    $feries[]=date("14-07-".$year);
	    $feries[]=date("15-08-".$year);
	    $feries[]=date("01-11-".$year);
	    $feries[]=date("11-11-".$year);
	    $feries[]=date("25-12-".$year);

	    // Paques et autres fêtes religieuses
	    $a=$year%19; $b=$year%4; $c=$year%7;
	    $d=(19*$a+24)%30; $e=(2*$b+4*$c+6*$d+5)%7;
	    $j=22+$d+$e; $m=3;
	    if($j>31){ $m+=1; $j-=31;}
	    
	    $paques=strtotime(sprintf("%02d-%02d-%04d",$j,$m,$year));
	    $feries[]=date('d-m-Y',strtotime('+1 day',$paques));
	    $feries[]=date('d-m-Y',strtotime('+39 days',$paques));
	    $feries[]=date('d-m-Y',strtotime('+50 days',$paques));
	    
	    return $feries;
	}

	/* --------------------- GEOLOC ADDRESS ----------------------------- */

	static function geoloc_address($address, $var=null)
	{
		// $address : adresse complète complète sur une seule ligne avec des esapces. 
		// ex : 12, place de la république, 75001 Paris ou 75018 Paris  
		$address = urlencode($address);
		$curl = Service::Curl();
		$response = $curl->get(PROTOCOL.'://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');
		$object = json_decode($response);

		if (!$object || empty($object->results)) {
			return null;

		} else if ($var) {

			$address_components = array();
			foreach ($object->results[0]->address_components as $data) {
				switch ($data->types[0]) {
					case 'locality': $address_components['town'] = $data->long_name; break;
					case 'administrative_area_level_2': $address_components['departement'] = $data->long_name; break;
					case 'administrative_area_level_1': $address_components['region'] = $data->long_name; break;
					case 'country': $address_components['country'] = $data->long_name; $address_components['country_code'] = $data->short_name; break;
					case 'postal_code': $address_components['cp'] = $data->long_name; break;
					case 'street_number': $address_components['number'] = $data->long_name; break;
					case 'route': $address_components['street'] = $data->long_name; break;
				}
			}

			switch ($var) {
				case 'latlng':
					return array($object->results[0]->geometry->location->lat, $object->results[0]->geometry->location->lng);
					break;
				case 'formatted_address':
					return $object->results[0]->formatted_address;
					break;
				case 'address_components':
					return $address_components;
					break;
				default: 
					if (array_key_exists($var, $address_components)) {
						return $address_components[$var];
					} else {
						return $object;	
					}
					break;
			}
		} 
		return $object;
	}


	/* --------------------- CHECK COUNTRY OF VISITOR ----------------------------- */

	static function get_country_by_ip($ip=null)
	{

		if (!$ip) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		// create a new cURL resource
		$ch = curl_init();
		$url='http://ipinfodb.com/ip_query_country.php?ip='.$ip;
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

		// grab URL and pass it to the browser
		$xml=curl_exec($ch);

		// close cURL resource, and free up system resources
		curl_close($ch);
		if($xml===false) return false;
		else{
			preg_match("@<countrycode>(.+)</countrycode>@i",$xml,$matches);
			return strtoupper($matches[0]);
		}
	}
}