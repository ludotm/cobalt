<?php 

namespace Core\Files;

use Core\Service;
use Core\Files\Image;
use Core\Files\Document;
use Core\Files\Video;
use Core\Files\Sound;

class FileManager
{
	use \Core\Singleton;

	protected $upload_accepted_extensions;
	protected $max_file_size;
	protected $upload_directory;
	protected $file_types;
	protected $directory_chmod;
	protected $file_chmod;

	protected $db;
	protected $request;
	protected $session;
	
	public $files = array();
	
	public function __construct() 
	{
		$this->upload_accepted_extensions = _get('config.file_manager.upload_accepted_extensions', null);
		$this->max_file_size = _get('config.file_manager.max_file_size', null);
		$this->upload_directory = _get('config.file_manager.upload_directory', '');
		$this->file_types = _get('config.file_manager.file_types', null);

		$this->directory_chmod = _get('config.file_manager.directory_chmod', 0755);;
		$this->file_chmod = _get('config.file_manager.file_chmod', 0666);;

		$this->db = Service::Db();
		$this->request = Service::Request();
		$this->session = Service::Session();
	}

	public function download ($url, $file_type_or_destination_path=null, $filename=null, $save_file=true)
	{
		$parsed = parse_url($url);
		$temp_filename = basename($parsed['path']);
		$extension = pathinfo($temp_filename, PATHINFO_EXTENSION);

		$filename = $filename ? $filename.'.'.$extension : $temp_filename;

		if (array_key_exists($file_type_or_destination_path, $this->file_types)) { // si format prédéfini dans config indiqué, on download dans le folder défini dans config
			$file_type = $file_type_or_destination_path;
			$destination_path = $this->upload_directory . '/' .($this->session->defined('id_big_user') ? $this->session->get('id_big_user') : 0) . '/' . $this->file_types[$file_type_or_destination_path]['folder'] .'/';
		
		} else if ($file_type_or_destination_path) { // sinon on download dans le dossier passé en paramètre
			$destination_path = $file_type_or_destination_path;
		
		} else { // Et si rien n'est indiqué, on download dans le dossier upload de base défini dans config
			
			if (!$this->upload_directory) {
				Service::error("Le dossier de destination n'a pas été défini");
			} else {
				$destination_path = $this->upload_directory;
			}
		}

		$accepted_extensions = _get('config.file_manager.upload_accepted_extensions');

		foreach ($accepted_extensions as $type => $extensions) {
			if (in_array($extension, $extensions)) {
				$type_of_file = $type;
			}
		}
		if (!isset($type_of_file)) {
			Service::error('Extension de fichier ('.$extension.') inconnue ou non autorisée pour le download');
		}
		switch ($type_of_file) {
			case 'image': $file = new Image(); $file->setModel('_images'); break;
			case 'document': $file = new Document(); $file->setModel('_documents');break;
			case 'video': $file = new Video(); $file->setModel('_videos'); break;
			case 'sound': $file = new Sound(); break;
		}

		$full_path = $destination_path . $filename ;

		if (!is_dir($destination_path)) {
			mkdir($destination_path,0777, true);
		}

		$curl = new \Core\Curl();
		$curl->opt(CURLOPT_BINARYTRANSFER, 1);
		$file_content = $curl->get($url);
		$fp = fopen($full_path, "w[b]"); 

		if (!$fp) {
			Service::error("La création du fichier ".$full_path." a échoué");
		}

		fseek($fp, 0); // On remet le curseur au début du fichier
		fputs($fp, $file_content); 
		fclose($fp);

		// Complète les information du fichier

		$file->id_big_user = $this->session->defined('id_big_user') ? $this->session->get('id_big_user') : 0;
		$file->name = str_replace($extension, '', $filename);
		$file->slug = $file->name;
		$file->extension = $extension; 
		$file->file_type = isset($file_type) ? $file_type : '';

		if ($type_of_file == 'image') {
			
			$size = getimagesize ($full_path);
			$file->original_width = $size[0];
			$file->original_height = $size[1];
		}

		if ($save_file) {

			if (isset($file_type)) {
				$new_id = $file->save();
				$file->rename($full_path, $new_id);
				$file->apply_format();

			} else {
				$file->save();
			}
				
		}

		return $file;		
	}

	public function upload ($index, $file_type)
	{
		
		if (isset($_FILES[$index.'_file'])) {
			
			if ($_FILES[$index.'_file']['name'] != '') {

				if (array_key_exists($file_type, $this->file_types)) {
					$file_params = $this->file_types[$file_type];
				} else {
					Service::error("File Manager : file_type non trouvé dans le fichier config");
				}

				switch ($file_params['type']) {
				
					case "image":
						$file = new Image();
						$file->setModel('_images');
						break;

					case "document":
						$file = new Document();
						$file->setModel('_documents');
						break;

					case "video":
						$file = new Video();
						$file->setModel('_videos');
						break;

					case "sound":
						$file = new Sound();
						$file->setModel('_sounds');
						break;
					
					case null:
					default:
						$ext = \Core\Tools::get_extension($_FILES[$index.'_file']['name']);
						if (in_array($ext, $this->upload_accepted_extensions['image'])) {
							$file = new Image();
							$file->setModel('_images');
						} else if (in_array($ext, $this->upload_accepted_extensions['document'])) {
							$file = new Document();
							$file->setModel('_documents');
						} else if (in_array($ext, $this->upload_accepted_extensions['video'])) {
							$file = new Video();
							$file->setModel('_videos');
						} else if (in_array($ext, $this->upload_accepted_extensions['sound'])) {
							$file = new Sound();
							$file->setModel('_sounds');
						} else {
							Service::error('Fichier uploadé non reconnu');
						}
						break;
				}

				$file->uploaded_file = $_FILES[$index.'_file'];
				$file->file_type = $file_type;
				$file->extension = \Core\Tools::get_extension($file->uploaded_file['name']);

				$is_valid = $this->is_valid($file);

				if ($is_valid === true) {

					$file->name = !isset($this->request->post->{$index.'_name'}) ? basename($file->uploaded_file['name'], $file->extension) : $this->request->post->{$index.'_name'} ;
					$file->slug = \Core\Tools::clean_string($file->name);
					$file->description = !isset($this->request->post->{$index.'_description'}) ? $file->name : $this->request->post->{$index.'_description'} ;
					$file->file_size = $file->uploaded_file['size'];
					$file->id_big_user = $this->session->get('id_big_user');

					if ($file->get_param('type') == 'image') {
						$size = getimagesize ($file->uploaded_file['tmp_name']);
						$file->original_width = $size[0];
						$file->original_height = $size[1];
					}

					$full_path = $this->upload_directory.$file->id_big_user.$file->get_param('folder');
					
					if (!is_dir($full_path)) {
						mkdir($full_path, $this->directory_chmod, true);
					}

		     		$file->moved_file = $full_path.'/'.uniqid().'.'.$file->extension;

				    if(move_uploaded_file($file->uploaded_file['tmp_name'], $file->moved_file )) {

				    	chmod ($file->moved_file, $this->file_chmod);
				     	$this->files []= $file;
				     	return $file;
				     
				    } else {
				     	return 'Erreur inconnue lors de l\'upload du fichier';
				    }
				     
				} else if (is_string($is_valid)) {
					return $is_valid;

				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}


	public function is_valid ($file)
	{
		// si le fichier est trop gros
		if ($this->max_file_size && $file->uploaded_file['size'] > $this->max_file_size) {
			return 'Le fichier dépasse la limite autorisée';
		}

		// Vérifie qu'il n'y a pas d'erreur lors de l'upload
		if ($file->uploaded_file['error'] > 0) {

			switch ($file->uploaded_file['error']) {

				case 1: // UPLOAD_ERR_INI_SIZE
					return 'Le fichier dépasse la limite autorisée';
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					return 'Le fichier dépasse la limite autorisée dans le formulaire';
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					return 'L\'envoi du fichier a été interrompu pendant le transfert';
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					return 'Le fichier que vous avez envoyé a une taille nulle';
					break;
				default:
					return 'Erreur inconnue';
			  }
		}

		if ($file->get_param('type') == "image") {

			// Vérifie le type du fichier et l'extension
			if (function_exists('exif_imagetype')) {

				if (!in_array(exif_imagetype($file->uploaded_file['tmp_name']), array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG))) {

					return 'Format de fichier incorrect';
				}
			}
		} 

		if (!in_array($file->extension, $this->upload_accepted_extensions[$file->get_param('type')])) {
			return 'Format de fichier incorrect';
		}

		return true;
	}

}


?>