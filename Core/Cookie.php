<?php 

namespace Core;

use Core\Service;

class Cookie 
{
	use \Core\Singleton;

	protected $defaultExpiration;
	protected $defaultAuthExpiration;
	protected $stealProtection;
	protected $authCookieName;

	protected function __construct() 
	{
		$this->authCookieName = 'remember_me';

		$config = Service::Config();
		$cookieConfig = $config->get('cookie');

		$this->defaultExpiration = !array_key_exists('cookie_expiration', $cookieConfig) ? (365*24*3600) : $cookieConfig['cookie_expiration'] ;
		$this->defaultAuthExpiration = !array_key_exists('cookie_auth_expiration', $cookieConfig) ? (60*24*3600) : $cookieConfig['cookie_auth_expiration'] ;
		$this->stealProtection = !array_key_exists('cookie_steal_protection', $cookieConfig) ? false : true ;
	}


	public function set($name, $value, $expiration=null) 
	{
		$expiration = !$expiration ? $this->defaultExpiration : $expiration;
		setcookie($name, $value, time() + $expiration, '/', DOMAIN, false, true);
	}

	public function get($name) 
	{
		if(isset($_COOKIE[$name])) {
			// sécurité, supprime les carractères spéciaux tels que les guillemets
			return htmlspecialchars ($_COOKIE[$name], ENT_QUOTES);

		} else {
			return null;
		}
	}

	public function delete($name) 
	{
		$this->set($name, '', -3600);
	}


	public function createAuthCookie ($user_id, $expiration=null) 
	{
		/*
    	- Ajouter $_SERVER["SSL_SESSION_ID"] à la variable $data pour contrer le vol de cookie, mais implique une invalidité du cookie quand le client et le serveur renégocient une nouvelle id de session 
		- Vérifier si la var $_SERVER["SSL_SESSION_ID"] est bien accéssible, voir à remplacer par session_id()
		*/
        $expiration = !$expiration ? $this->defaultAuthExpiration : $expiration;
        
        $value = $this->getAuthCookieValue ($user_id, (time()+$expiration));

        $this->set($this->authCookieName, $value, $expiration);
	}

	public function verifyAuthCookie()
	{
		if (!$cookie_value = $this->get($this->authCookieName)) {
			return false;	
		}
		
		list( $user_id, $expiration, $hash ) = explode( '|', $cookie_value );

		if ($expiration < time()) {
			return false;
		}

		$value = $this->getAuthCookieValue ($user_id, $expiration, true);

		return $value != $hash ? false : $user_id ;	
	}

	protected function getAuthCookieValue ($user_id, $expiration, $getHash=false) 
	{
		$crypto = Service::Crypto();
        $data = $user_id . $expiration . ($this->stealProtection ? (isset($_SERVER["SSL_SESSION_ID"])?$_SERVER["SSL_SESSION_ID"]:'') : '');

        if ($getHash) {
        	return $crypto->hash($data);
        }

        $value = $user_id . '|' . $expiration . '|' . $crypto->hash($data);
        return $value;
	}
}

?>