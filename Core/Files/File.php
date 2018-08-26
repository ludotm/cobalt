<?php

namespace Core\Files;

use Core\Service;
use Core\Entity;

class File extends Entity
{	
	public $uploaded_file;
	public $moved_file;
	public $upload_directory;

	public $file_params;
	public $file_type;
	//public $folder;
	//public $type;

	public function __construct($table=null)
	{
		parent::__construct($table);

		$this->uploaded_file = null;
		$this->moved_file = null;

		$file_params = null;
		$file_type = null;

		$this->upload_directory = _get('config.file_manager.upload_directory', '');
	}

	public function get_param($param=null)
	{
		if (!$this->file_params) {
			if (!$this->file_type){
				Service::error("File : file_type non défini");
			} else {
				$this->file_type_params = _get('config.file_manager.file_types.'.$this->file_type);
			}
		}
		if (!$param) {
			return $this->file_type_params;
		}
		if (array_key_exists($param, $this->file_type_params)) {
			return $this->file_type_params[$param];
		} else {
			Service::error("File - get_file_param : paramètre introuvable");
		}
	}

	public function save_and_format () 
	{	
		$this->save();
		
		if ($this->moved_file) {
			$this->rename($this->moved_file, $this->{$this->primary_key});
		}
		if ($this->get_param('type') == 'image') {
			$this->apply_format();
		}
	}

	public function rename($path, $new_name) 
	{
		$path_parts = pathinfo($path);
		$new_path = $path_parts['dirname'].'/'.$new_name.'.'.$path_parts['extension'];
		rename($path, $new_path);
	}

	public function copy($base_path, $new_name) 
	{
		$path_parts = pathinfo($base_path);
		
		$new_path = $path_parts['dirname'].'/'.$new_name.'.'.$path_parts['extension'];
		if (!copy($base_path, $new_path)) {

			Service::error("Erreur lors de la copie du fichier");
		}
		$chmod = fileperms($base_path);
		chmod($new_path, $chmod);
	}

	public function get_url ($format=null, $encoded=true) 
	{
		if ($format) {
			$url = $this->upload_directory.$this->id_big_user.$this->get_param('folder').'/'.$this->{$this->primary_key}.'_'.$format.'.'.$this->extension;
		} else {
			$url = $this->upload_directory.$this->id_big_user.$this->get_param('folder').'/'.$this->{$this->primary_key}.'.'.$this->extension;
		}
		if ($encoded) {
			return Service::secure_file_url_encode($url, false);
		} else {
			return $url;
		}
		
	}

	public function get_src_url ($format=null) 
	{
		return $this->get_url($format);
	}

	public function get_src_absolute_url ($format=null) 
	{
		return SITE_URL.$this->get_url($format);
	}

	public function chmod ($chmod, $format=null) 
	{
		chmod($this->get_url($format), $chmod);
	}
	
	public function destroy() // supprime fichier + base de donnée 
	{	
		if ($this->get_param('type') == 'image') {

			if (array_key_exists('formats', $this->get_param())) {
				$formats = $this->get_param('formats');

				foreach ($formats as $key => $value) {

					$format = is_int($key) ? $value : $key;
					$file_path = $this->get_url($format);

					if (file_exists($file_path)) {

						chmod ($file_path,0777);
						unlink ($file_path);
					}
				}
			}
		}

		$original_file_path = $this->get_url();

		if (file_exists($original_file_path)) {

			chmod ($original_file_path,0777);
			unlink ($original_file_path);
		}
		$this->delete();
	}
}

?>