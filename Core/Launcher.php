<?php

namespace Core;

use Core\Service;
use Core\Controller;

/**
* Dispatcher
* Permet de charger le controller en fonction de la requête utilisateur
**/

class Launcher 
{	
	protected $request;
	protected $controller;

	public function __construct()
	{
		$this->request = Service::Request();
		$this->launchControler();
	}
	

	protected function launchControler()
	{	

		_set($this->request->zone.'/config.php');

		// find controller
		$controllerClass =  str_replace('/','\\',$this->request->zone) .IDS. 'Controllers' .IDS. $this->request->controller .'Controller';

		$baseControllerClass =  str_replace('/','\\',$this->request->zone) .IDS. 'Controllers' .IDS .'BaseController';

		if (!in_array($this->request->type_action.'_'.$this->request->action , array_merge( get_class_methods($controllerClass), get_class_methods($baseControllerClass), get_class_methods('Core\Controller'))) ){

			Service::error('Le controller '.$this->request->controller.' n\'a pas de méthode '.$this->request->type_action.'_'.$this->request->action.''); 
		}

		// launch controller
		$controller = new $controllerClass();

		// launch Dispatch zone
		if (in_array('onDispatchZone', get_class_methods($baseControllerClass))) {
			$controller->onDispatchZone();
		}
		
		// launch Dispatch
		if (in_array('onDispatch', get_class_methods($controllerClass))) {
			$controller->onDispatch();
		}
		

		// launch Action
		$controller->{$this->request->type_action.'_'.$this->request->action}();
	}

}