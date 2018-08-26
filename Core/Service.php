<?php 

namespace Core;

class Service
{
	/* SINGLETON */
	
	static function Cache() 
	{
		return \Core\Cache::getInstance();
	}

	static function Request() 
	{
		return \Core\Request::getInstance();
	}

	static function Db() 
	{
		return \Core\Db::getInstance();
	}

	static function Session() 
	{
		return \Core\Session::getInstance();
	}

	static function Page() 
	{
		return \Core\Page::getInstance();
	}

	static function FileManager() 
	{
		return \Core\Files\FileManager::getInstance();
	}

	static function Cookie() 
	{
		return \Core\Cookie::getInstance();
	}

	static function Crypto() 
	{
		return \Core\Crypto::getInstance();
	}

	static function Api($type) 
	{	
		switch($type) {
			case 'facebook': return \Core\Api\Facebook::getInstance(); break;
			case 'twitter': return \Core\Api\Twitter::getInstance(); break;
			case 'spothit': return \Core\Api\Spothit::getInstance(); break;
			case 'stripe': return \Core\Api\Stripe::getInstance(); break;
		}
		
	}


	/* MODEL AND CONFIG ALLIAS */

	static function Config($var, $return=null) 
	{
		return _config($var, $return);
	}

	static function Model($model) 
	{
		return _model($model);
	}


	/* NEW OBJECTS  */

	static function Curl() 
	{
		return new \Core\Curl();
	}

	static function Form() 
	{
		return new \Core\Form();
	}

	static function Entity($table=null) 
	{
		return new \Core\Entity($table);
	}

	/* EMAIL, SMS, MMS, VOCAL  */

	static function Email($data=array()) 
	{
		return new \Core\Com\Email($data);
	}
	static function send_mail($data)
	{
		if (!array_key_exists('save', $data)) {
			$data['save'] = 0;
		}
		if (!array_key_exists('response_mail', $data)) {
			$data['response_mail'] = $data['from'];
		}
		if (!array_key_exists('provider', $data)) {
			$data['provider'] = 'self';
		}
		if (!array_key_exists('id_big_user', $data)) {
			$data['id_big_user'] = 0;
		}
		$email = new \Core\Com\Email($data);
		$email->send();
		return $email;
	}

	static function Sms($data=array()) 
	{
		return new \Core\Com\Sms($data);
	}
	static function send_sms($data)
	{
		if (!array_key_exists('save', $data)) {
			$data['save'] = 0;
		}
		if (!array_key_exists('id_big_user', $data)) {
			$data['id_big_user'] = 0;
		}
		$sms = new \Core\Com\Sms($data);
		$sms->send();
		return $email;
	}


	/* AUTOLOAD */
	static function add_autoload_directory($directory) 
	{	
		spl_autoload_register(function ($class) use ($directory) {
		    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

			if (is_readable($directory .  $path . '.php')) {
				require_once($directory .  $path . '.php');
			}
		});
	}

	/* LANG */

	static function get_lang() 
	{	
		$session = self::Session();
		return isset($session->lang) ? $this->session->lang : LOCALE ;
	}

	static function get_locale() 
	{	
		return self::get_lang();
	}

	/* ERROR, MESSAGES & REDIRECTIONS */

	static function error($msg)
	{
		throw new \Exception($msg);
	}
	static function warning($msg)
	{
		try {
			throw new \Exception($msg);
		} catch (\Exception $e) {
			ExceptionManager($e, E_USER_WARNING);
		}
	}
	static function flash($message, $type = 'success', $for_next_page = false)
	{
		$session = self::Session();
		$session->flash($message, $type, $for_next_page);
	}

	// modifie le code du header
	static function header_code($code)
	{		
		http_response_code($code);
	}

	// redirections 404 ou 401
	static function redirectError($code = '404')
	{		
		header('Location:/error/'.$code);
		exit();
	}

	// redirections classiques ou 301
	static function redirect($url, $code = null )
	{
		if($code == 301) {
			http_response_code($code);
		}
		header('Location: ' . $url);
		exit();
	}

	// Url de fichiers sécurisés
	static function secure_file_url_encode($url, $full_url=false, $download=false)
	{
		$crypto = self::Crypto();
		$crypto->setSecret('oI7dhT5encYdMsZ');
		if ($full_url) {
			return SITE_URL.'/secure/file?u='.$crypto->encode($url);
		} else {
			return '/secure/file?u='.$crypto->encode($url);
		}
	}
	static function secure_file_url_decode($crypted)
	{
		$crypto = self::Crypto();
		$crypto->setSecret('oI7dhT5encYdMsZ');

		return $crypto->decode($crypted);
	}

}

?>