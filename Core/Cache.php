<?php 

namespace Core;

use Core\Service;

class Cache
{
	use \Core\Singleton;

	protected $path;
	protected $defaultExpiration;

	protected function __construct() 
	{
		$config = Service::Config();
		$this->path = realpath($config->get('cache/cache_path'));

		$defaultExpiration = $config->get('cache/cache_expiration');
		$this->defaultExpiration = !$defaultExpiration ? 3600 : $defaultExpiration ;
	}

	public function add ($filename, $content) 
	{
		$info = pathinfo($filename);
		$dirs = explode('/', $info['dirname']);

		$temp_dir = $this->path;

		for ($i=0; $i<count($dirs); $i++) {

			$temp_dir .= '/'.$dirs[$i];

			if (!is_dir($temp_dir)) {
				mkdir($temp_dir, 0777);
			}
		}

		file_put_contents($this->filename($filename), $content);
	}

	public function get ($filename, $expiration=null) 
	{
		$file = $this->filename($filename);
		$expire = time() - (!$expiration ? $this->defaultExpiration : $expiration);

		if (file_exists($file) && filemtime($file) > $expire) {
			return file_get_contents($file);

		} else {
			return false;
		}
	}

	public function delete ($filename) 
	{
		$file = $this->filename($filename);
		if (file_exists($file)) {
			unlink($file);
		} else {
			Service::error("Le fichier de cache ".$file." n'existe pas, impossible de supprimer");
		}
	}

	protected function filename ($filename) 
	{
		return $this->path.'/'.$filename.'.cache';
	}
}

?>