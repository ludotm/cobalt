<?php

namespace Core;

use Core\Router;
use Core\Service;

use stdClass;

class Request extends Router
{
	use \Core\Singleton;

	public $page = 1; 			// pour la pagination 
	public $get; 		// Données envoyé en GET
	public $post;		// Données envoyé en POST
	public $put;

	protected function __construct()
	{
		$this->get = new stdClass();
		$this->post = new stdClass();
		
		parent::__construct();
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->parseUrl();	
		$this->setGetAndPosts();
	}

	protected function setGetAndPosts()
	{
		// Si des données ont été postées ont les entre dans data
		if(!empty($_GET)){

			$this->secure_array($_GET, 'get');
		}

		if(!empty($this->rewrite_get)){

			$this->secure_array($this->rewrite_get, 'get');
		}

		if ($this->isPost()) {

			// Si des données ont été postées ont les entre dans data
			if(!empty($_POST)){
				
				$this->secure_array($_POST, 'post');
			}
		}

		if ($this->isPut()) {
			
			$this->put = json_decode(file_get_contents('php://input'));

			$this->secure_array($this->put, 'put');
		}
		

		// Si on a une page dans l'url on la rentre dans $this->page
		if(isset($this->get->page)){

			if(is_numeric($this->get->page)){

				$this->page = $this->get->page; 
			}
		}

		
	}

	public function secure($key, $value, $container) {
		//addslashes()
		$this->{$container}->{$key} = $value;
	}

	protected function secure_array($source, $container) 
	{
		foreach($source as $key => $value){
					
			if (!is_array($value)) {
				
				$this->secure($key, $value, $container);

			} else {
				$this->secure($key, $value, $container);
			}
		}
	}

	public function fromPost($key=null, $default=null) 
	{

		if (!$key) {
			return $this->post;
		}

		if (isset($this->post->{$key})) {
			return $this->post->{$key};
		} else {
			return $default;
		}
	}

	public function fromRoute($key=null, $default=null) 
	{

		if (!$key) {
			return $this->get;
		}

		if (isset($this->get->{$key})) {
			return $this->get->{$key};
		} else {
			return $default;
		}
	}

	public function getMethod() 
	{
		return $this->method;
	}

	public function isPost() 
	{
		return $this->method == 'POST' ? true : false ;
	}

	public function isPut() 
	{
		return $this->method == 'PUT' ? true : false ;
	}

	public function isGet() 
	{
		return $this->method == 'GET' ? true : false ;
	}

	public function isDelete() 
	{
		return $this->method == 'DELETE' ? true : false ;
	}
} 