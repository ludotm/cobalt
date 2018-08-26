<?php

namespace Core;

use Core\Service;
use Core\Tools;

class Router
{
	use \Core\Singleton;

	protected $routes;
	public $url; // URL appellé par l'utilisateur
	public $controller;
	public $action;
	public $zone;
	public $load_db;
	public $active_menu;
	public $rewrite_get;
	public $route;

	protected function __construct()
	{
		$this->url = $_SERVER['REQUEST_URI']; 
		$this->routes = _get('routes');
		$this->rewrite_get = array();
		$this->load_db = true;
	}

	public function parseUrl()
	{
		$parsed_url = parse_url($this->url);
		$route = $parsed_url['path'];

		/* -------------------- CHECK ROUTES SPECIALES -------------------------- */

		// check si c'est une requête provenant d'une borne cisco
		if (preg_match('#^/meraki-remote/([0-9]+)$#i', $route, $matches)) {
			$id = $matches[1];
			$this->setMerakiRemotePage($id);
			return;
		}

		// check si c'est une requête provenant d'une borne cisco
		if (preg_match('#^/mini-manager/(.*)/(.*)/(.*)/(.*)/([0-9]+)$#i', $route, $matches)) {
			$zone = $matches[1];
			$controller = $matches[2];
			$name = $matches[3];
			$action = $matches[4];
			$id = $matches[5];
			$this->setMiniManagerPage($zone, $controller, $name, $action, $id);
			return;
		}

		// check si c'est une requête de vérification de valeur dans la BDD
		if (preg_match('#^'.REMOTE_VALIDATION_URL.'$#i', $route, $matches)) {

			$this->setRemoteValidationPage();
			return;
		}

		// vérifie si ce n'est pas une page d'erreur
		if (preg_match('#^/error/([0-9]+)$#i', $route, $matches)) {
			
			$code = $matches[1];
			$this->setErrorPage($code);
			return;
		}

		// vérifie si c'est une page de zone base (zone privée)'
		if (preg_match('#^/base(/.*)?$#i', $route, $matches)) {
			
			$action = isset($matches[1]) ? substr($matches[1],1) : null;	
			$this->setBasePage($action);
			return;
		}

		// vérifie si c'est une page de zone base (zone privée)'
		if (preg_match('#^/install(/.*)?$#i', $route, $matches)) {
			
			$this->load_db = false;

			$action = isset($matches[1]) ? substr($matches[1],1) : null;	
			$this->setInstallPage($action);
			return;
		}

		// vérifie si c'est un formulaire mail libre
		if (preg_match('#^/form_email(/.*)?$#i', $route, $matches)) {

			$this->setEmailFormPage();
			return;
		}
		// vérifie si c'est un formulaire SMS libre
		if (preg_match('#^/form_sms(/.*)?$#i', $route, $matches)) {

			$this->setSmsFormPage();
			return;
		}
		// vérifie si c'est un lien pour ne plus recevoir de mail
		if (preg_match('#^/unsuscribe_email(/.*)?$#i', $route, $matches)) {

			$this->setUnsuscribePage();
			return;
		}

		// vérifie si c'est un accès au panel superadmin (zone privée)'
		if (preg_match('#^/panel(/.*)?$#i', $route, $matches)) {

			$action = isset($matches[1]) ? substr($matches[1],1) : null;	
			$this->setPanelPage($action);
			return;
		}

		// vérifie si ce n'est pas une page de download
		//if (preg_match('#^/download(/.*)?/([0-9]+)$#i', $route, $matches)) {
		if (preg_match('#^/download$#i', $route, $matches)) {
			$this->setDownloadPage();
			return;
		}

		// vérifie si ce n'est pas une page de fichier
		if (preg_match('#^/secure/file(.*)?$#i', $route, $matches)) {
			$this->setFilePage();
			return;
		}

		foreach ($this->routes as $key => $schema) {

			$pattern = $schema['route'];
			$pattern = str_replace('[','(@', $pattern); // le @ sera rempalcé plus tard par ?: pour que la parenthèse ne soit pas capturante
			$pattern = str_replace(']',')?', $pattern);

			$pattern_keys = array();

			$pattern = preg_replace_callback("#:\w+#i", function($matches) use ($schema, &$pattern_keys) {

				$contraint_key = str_replace(':','',$matches[0]);
				
				$pattern_keys[] = $contraint_key;

				if (isset($schema['constraints'])) {

					if (isset($schema['constraints'][$contraint_key])) {

						switch($schema['constraints'][$contraint_key]) {
							
							case 'numeric':
							$constraint = '[0-9]+';
							break;

							case 'alphanumeric':
							$constraint = '\w+';
							break;

							default:
							$constraint = $schema['constraints'][$contraint_key];
							break;

						}
					}
				}

				if (!isset($constraint)) {
					$constraint = '[^/]+';
				}

				return '('.$constraint.')';

			}, $pattern);

			$pattern = str_replace('@','?:',$pattern); // retransforme les repères @ en ?: pour parenthèse non capturante

			// route trouvée
			if (preg_match('#^'.$pattern.'$#i', $route, $matches)) {

				$this->route = $key;

				$vars = array();
				$j = 0;

				foreach ($matches as $i => $value) {
					if ($i != 0) {
						$this->rewrite_get[$pattern_keys[$j]] = $value;
						$j++;
					}
				}

				/* ATTRIBUTION DES ROUTERS ET DES ZONE */
				$route_infos = explode('-', $this->route);

				if (count($route_infos) != 3) {
					Service::error("Route trouvée mais identifiant de route incomplet");
				}
				
				$this->zone = ucfirst ($route_infos[0]);
				$this->type_action = $route_infos[1];
				$this->action = $route_infos[2];				
				$this->active_menu = array_key_exists('active_menu', $schema) ? $schema['active_menu'] : $key;

				if ($this->type_action == 'json') {
					header('Content-Type: application/json');	
				}
				
				if (array_key_exists('redirection', $schema)) {
					Service::redirect($schema['redirection']);
				}

				if (array_key_exists('controller', $schema)) {
					
					$this->controller = $schema['controller'];
					
				} else {
					Service::error("Route trouvée mais absence de controller par défaut");
				}

				return;
			}
		}

		// SINON 404 
		$this->setErrorPage('404');
	}

	public function router ($name, $vars=array()) {

		if (!array_key_exists($name, $this->routes)) {
			Service::error("La route ".$name." n'existe pas");

		} else {

			$route = $this->routes[$name]['route'];
			$defaults = array_key_exists('defaults', $this->routes[$name]) ? $this->routes[$name]['defaults'] : array();
			$constraints = array_key_exists('constraints', $this->routes[$name]) ? $this->routes[$name]['constraints'] : array();

			// merge with defaults values
			//$vars = array_merge($defaults, $vars);

			if (!empty($vars)) {
				foreach ($vars as $key => $value) {
					$vars[$key] = Tools::clean_string($value);
				}
			}
			
			$replace_vars = function ($matches) use ($vars, $constraints) {

				preg_match('#:(\w+)#s', $matches[1], $m);
				$key = isset($m[1]) ? $m[1] : '';

				if (array_key_exists($key, $vars)) {

					if (array_key_exists($key, $constraints)) {

						switch ($constraints[$key]) {
								
							case 'numeric': 
							if (!preg_match('#^\d+$#s', $vars[$key])) {
								Service::error("Route : contrainte non réspectée pour la variable ".$key.":".$constraints[$key]);
							}
							break;

							case 'alphanumeric':
							if (!preg_match('#^\w+$#s', $vars[$key])) {
								Service::error("Route : contrainte non réspectée pour la variable ".$key.":".$constraints[$key]);
							}
							break;
								
							default:
							if (!preg_match('#^'.$constraints[$key].'$#s', $vars[$key])) {
								Service::error("Route : contrainte non réspectée pour la variable ".$key.":".$constraints[$key]." (valeur:".$vars[$key].")");
							}
							break;
						}
					}
					return preg_replace('#:'.$key.'#i', $vars[$key], $matches[1]);
				
				} else {

					if (!preg_match('#^\[.*\]$#s', $matches[0])) {
						Service::error("Route : variable obligatoire ".$key." non trouvée");
					}
					return '';
				}
			};
			
			// variables optionnelles
			$route = preg_replace_callback("#\[(.*?)\]#i", $replace_vars, $route);

			// variables obligatoires
			$route = preg_replace_callback("#(:[a-zA-Z_]+)#i", $replace_vars, $route);

			return $route;
		}
	}

	public function url ($controller, $vars=array()) {
		return $this->router($controller, $vars);
	}

	public function setErrorPage($code) {

		if($this->is_ajax()) {
			$this->type_action = 'widget';
		} else {
			$this->type_action = 'page';
		}
		$this->zone = _get('config.error_zone');
		$this->controller = 'Error';
		$this->action = 'error';
		$this->secure('code', $code, 'get');
	}

	public function setMerakiRemotePage($id) {

		$this->get->id = $id;
		$this->type_action = 'page';
		$this->zone = 'Core/Base';
		$this->controller = 'Meraki';
		$this->action = 'remote_data_storage';
	}

	public function setMiniManagerPage($zone, $controller, $name, $action, $id) {

		$this->type_action = 'json';
		$this->zone = ucfirst ( $zone );
		$this->controller = ucfirst ( $controller );
		$this->action = 'mini_manager';
		$this->secure('name', $name, 'get');
		$this->secure('action', $action, 'get');
		$this->secure('id', $id, 'get');
	}

	public function setBasePage($action) {

		if($this->is_ajax()) {
			$this->type_action = 'widget';
		} else {
			$this->type_action = 'page';
		}

		$this->zone = 'Core/Base';
		$this->controller = 'Default';
		$this->action = !$action ? 'default' : $action;
	}

	public function setUnsuscribePage() {
		$this->type_action = 'page';
		$this->zone = 'Core/Base';
		$this->controller = 'Default';
		$this->action = 'unsuscribe_email'; 
	}

	public function setEmailFormPage() {
		$this->type_action = 'widget';
		$this->zone = 'Core/Base';
		$this->controller = 'Default';
		$this->action = 'form_email'; 
	}

	public function setSmsFormPage() {
		$this->type_action = 'widget';
		$this->zone = 'Core/Base';
		$this->controller = 'Default';
		$this->action = 'form_sms';
	}

	public function setRemoteValidationPage() {

		$this->type_action = 'json';
		$this->zone = 'Core/Base';
		$this->controller = 'Default';
		$this->action = 'remote_validation'; 
	}

	public function setPanelPage($action) {

		if($this->is_ajax()) {
			$this->type_action = 'widget';
		} else {
			$this->type_action = 'page';
		}

		$this->zone = 'Core/Base';
		$this->controller = 'Panel';
		$this->action = !$action ? 'home' : $action;
	}

	public function setInstallPage($action) {

		if($this->is_ajax()) {
			$this->type_action = 'widget';
		} else {
			$this->type_action = 'page';
		}

		$this->zone = 'Core/Base';
		$this->controller = 'Install';
		$this->action = !$action ? 'install' : $action;
	}

	protected function setDownloadPage() 
	{
		$this->zone = 'Core';
		$this->controller = 'Files';
		$this->action = 'download';
	}

	protected function setFilePage() 
	{
		$this->zone = 'Core/Base';
		$this->controller = 'Files';
		$this->action = 'read';
		$this->type_action = 'page';
	}

	protected function is_ajax() 
	{
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false; 
	}

	public function get_route() 
	{
		return $this->route;
	}
}