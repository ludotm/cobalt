<?php

$fm_params = array();


function _set($fileOrArrayOrFolder) {
	
	global $fm_params;

		if (is_array($fileOrArrayOrFolder)) {
			
			// CASE ARRAY
			$fm_params = _merge_config($fm_params, $fileOrArrayOrFolder);
		
		} else if (is_file($fileOrArrayOrFolder)) {
			
			// CASE FILE
			$ext = pathinfo($fileOrArrayOrFolder, PATHINFO_EXTENSION);

			if ($ext == 'php') {

				$fm_params = _merge_config($fm_params, require_once($fileOrArrayOrFolder));

			} else if ($ext == 'json') {
				$json = file_get_contents($fileOrArrayOrFolder);
				$fm_params = _merge_config($fm_params, json_decode($json, true));
			}
			
		} else if (is_dir($fileOrArrayOrFolder)) {

			// CASE FOLDER
			if ($handle = opendir($_SERVER['DOCUMENT_ROOT'].'/'.$fileOrArrayOrFolder)) { // Si le dossier includes/fichiers/ est accessible
         		
		        while (false !== ($file = readdir($handle))) { //tant qu'il y a des fichiers dans le dossier
		            if ($file != "." && $file != "..") {
		                _set($level.'.'.pathinfo($file, PATHINFO_FILENAME), $fileOrArrayOrFolder.'/'.$file);
		            }
		        }
		        closedir($handle); //on ferme le dossier
		    }
		} else {
			//\Core\Service::warning('function _set, le paramètre n\'est pas pas valide : '.$fileOrArrayOrFolder);
			return null;
		}
}

function _get($path, $return=null)
{
	global $fm_params;
	
	if (strcspn($path,  ';') && strcspn($path,  '$') ) { // protection pour l'utilisation sécurisée d'eval
		$path = '[\'' . implode('\'][\'', explode('.', $path)) . '\']';
		eval('$return = (isset($fm_params'.$path.') ? $fm_params'.$path.' : $return);');
	}
	return $return;
}

function _config($path, $return=null)
{
	return _get('config.'.$path, $return);
}

function _model($name) {
	$model = _get('models.'.$name);
	if (!$model) {
		_set('config/models/'.$name.'.php');
		$model = _get('models.'.$name);
	}
	if (!$model) {
		return null;
	} else {
		return $model;
	}
}

function _merge_config($table1, $table2)
{
	$output = array();
	foreach ($table2 as $key => $value) {
		if (is_array($value) && array_key_exists($key, $table1) ) {
			$output[$key] = _merge_config($value, $table1[$key]);
		} else if (is_int($key)) { 
			$output []= $value;
		} else {
			$output[$key] = $value;	
		}	
	}
	foreach ($table1 as $key => $value) {
		if ( !array_key_exists($key, $output) || is_int($key)) {
			if (is_int($key)) { 
				$output []= $value;
			} else {
				$output[$key] = $value;	
			}	
		}
	}
	return $output;
}
