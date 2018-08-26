<?php

namespace Core\Files;

use Core\Files\File;
use Core\Service;

class Image extends File
{	
	public function __construct()
	{
		parent::__construct('_images');
	}

	public function show ($format=null, $attrs=array()) 
	{
		$url = $this->get_src_url($format);

		$html = '<img src="'.$url.'"';
		foreach ($attrs as $attr => $value) {
			$html .= ' '.$attr.'="'.$value.'"';	
		}
		$html .= ' />';

		return $html;
	}

	public function apply_format () 
	{
		if (array_key_exists('formats', $this->get_param())) {
			
			$formats = $this->get_param('formats');
			$predefined_formats = _get('config.file_manager.predefined_image_formats');

			foreach ($formats as $key => $params) {
				
				$format = null;
				$format_name = '';

				if (is_int($key)) { // format prédéfini
					if (array_key_exists($params, $predefined_formats)) {
						$format = $predefined_formats[$params];
						$format_name = $params;
					}
				} else {
					$format = $params;
					$format_name = $key;
				}
				if ($format) {
					
					if (is_string($format)) { // format simple
						$dimensions = explode('x', $format);
						$width = $dimensions[0];
						$height = $dimensions[1];

					} else if (is_array($format)) {
						$width = $format['width'];
						$height = $format['height'];
					}

					$this->copy($this->get_url(null, false), $this->{$this->primary_key}.'_'.$format_name);
					$this->resize($this->get_url($format_name, false), $width, $height);
				}
			}
		}
	}


	public function resize ($source_url, $max_width, $max_height) 
	{
        $img_infos = getimagesize($source_url);
        $width = $img_infos[0];
        $height = $img_infos[1];

        switch($img_infos[2]) {

            case 1: $source_img = imagecreatefromgif($source_url); break;
            case 2: $source_img = imagecreatefromjpeg($source_url); break;
            case 3: $source_img = imagecreatefrompng($source_url); break;
			default: Service::error("Function resize : ce fichier n'est pas une image, ou ne peut être redimensionné."); break;
        }
		
		$crop = is_numeric($max_width) && is_numeric($max_height) ? true : false;
       
		// Si l'image ne doit pas être rognée, on calcule les nouvelles dimensions en fonction
		if (!$crop) {
		  	
			if (is_numeric($max_width)) {
				$new_width = $max_width;
				$new_height = round($height * ($max_width / $width)) ;

			} else if (is_numeric($max_height)) {
				$new_height = $max_height;
				$new_width = round($width * ($max_height / $height)) ;
			}
			  
		 	// création du cadre de l'image
		 	$new_image = imagecreatetruecolor($new_width, $new_height);
		}
		  
		  // Si l'image finale doit avoir des dimensions fixes
		else 
		{
		  	// création du cadre de l'image
			$new_image = imagecreatetruecolor($max_width, $max_height);
			
		  	// On véirfie dans quel sens l'image déborde du cadre
		  	  $h_ratio = $height/$max_height;
			  $h_ratio_width = $width/$h_ratio;
			  $h_perte_pixel = $max_width - $h_ratio_width;
			  
			  $w_ratio = $width/$max_width;
			  $w_ratio_height = $height/$w_ratio;
			  $w_perte_pixel = $max_height - $w_ratio_height;
			  
			  if ($w_perte_pixel > 0) // L'image est trop large, on crop en largeur
			  {
				$x_start = round(-$h_perte_pixel*$h_ratio/2);
				$new_width = round($h_ratio_width);
				$new_height = $max_height;
			  }
			  else if ($h_perte_pixel > 0) // L'image est trop haute, on crop en hauteur
			  {
				$y_start = round(-$w_perte_pixel*$w_ratio/2);
				$new_height = round($w_ratio_height);
				$new_width = $max_width;
			  }
			  
			  if ($w_perte_pixel == 0 && $h_perte_pixel == 0)
			  {
			  	$new_height = $max_height;
				$new_width = $max_width;
			  }
		}
		  
		  if (!isset($x_start)) $x_start = 0;
		  if (!isset($y_start)) $y_start = 0;

		  imagecopyresampled($new_image,$source_img,0,0,$x_start,$y_start,$new_width,$new_height,$width,$height);
			
		  switch($img_infos[2]) 
		  {
              case 1: $simg = imagegif($new_image,$source_url,100); break;
              case 2: $simg = imagejpeg($new_image,$source_url,100); break;
              case 3: $simg = imagepng($new_image,$source_url,9); break;
			  default: Service::error("Format d'image incorrect."); break;
          }
		  
		  imagedestroy($source_img);
		  imagedestroy($new_image);
    }

}

?>