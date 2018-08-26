<?php 
namespace Core;

use Core\Service;
use Core\Tools;

class Curl 
{
	protected $ch;
	protected $is_init;
	protected $infos;
	protected $keep_session;

	protected $upload_path;
	protected $cookie_file;

	protected $headers;
	protected $cookies;
	protected $referers;
	protected $proxies;
	protected $files = array();


	/* ----------------------- BASE --------------------------- */

	public function __construct() 
	{
		$this->upload_path = realpath(_config('file_manager.upload_directory'));
		$this->cookie_file = realpath(_config('cookie.cookie_path')) . '/cookies.txt'; 

		$this->setRandomUserAgent();
		$this->init();

		/*
		voir à créer des connexion encryptées https lors des communication
		*/
	}


	protected function init()
	{
		$this->ch = curl_init();
		$this->is_init = true;

		$this->setDefaultsOptions();

		if ($this->keep_session) {
			$this->opt(CURLOPT_COOKIEFILE, realpath($this->cookie_file));
		}
	}


	protected function setDefaultsOptions()
	{
		$this->opt(CURLOPT_RETURNTRANSFER, 1); //permet d'indiquer que nous voulons recevoir le résultat du transfert au lieu de l'afficher.
		$this->opt(CURLOPT_SSL_VERIFYPEER, 0);
		$this->opt(CURLOPT_VERBOSE, 1); // TRUE pour afficher tous les événements. Écrit la sortie sur STDERR
		$this->opt(CURLOPT_COOKIESESSION, 1); // Cette option permet de dire à cURL de démarrer un nouveau cookie session. Cela lui force alors à ignorer tous les cookies provenant de sessions antérieures.

		$this->opt(CURLINFO_HEADER_OUT, true); // TRUE pour suivre la chaîne de requête handle. 
        $this->opt(CURLOPT_HEADER, 0); //TRUE pour inclure l'en-tête dans la valeur de retour. 

        $this->opt(CURLOPT_FRESH_CONNECT, true); //TRUE pour forcer à utiliser une nouvelle connexion au lieu de celle en cache. 
        $this->opt(CURLOPT_FOLLOWLOCATION, true); //TRUE pour suivre toutes les en-têtes "Location: " que le serveur envoie dans les en-têtes HTTP

        if (!isset($this->options[CURLOPT_CONNECTTIMEOUT])) $this->option(CURLOPT_CONNECTTIMEOUT, 10); // timeout de la connexion
        if (!isset($this->options[CURLOPT_TIMEOUT])) $this->option(CURLOPT_TIMEOUT, 30); // timeout d'execution d'une fonction Curl
        if (!isset($this->options[CURLOPT_AUTOREFERER])) $this->option(CURLOPT_AUTOREFERER, 1); // auto referer suite à des header:location
        if (!isset($this->options[CURLINFO_HEADER_OUT])) $this->option(CURLINFO_HEADER_OUT, 1); // inclut le header de la dernière requete 
		if (!isset($this->options[CURLOPT_MAXREDIRS])) $this->option(CURLOPT_MAXREDIRS, 10); // maximum de redirections possibles
	}

	public function opt($name, $value)
	{
		if (!$this->is_init) {
			$this->init();
		}

		curl_setopt($this->ch, $name, $value);
	}

	public function option($name, $value) {
		$this->opt($name, $value);
	}
    

	/* ----------------------- HEADERS --------------------------- */

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $key . ': ' . $value;
        $this->opt(CURLOPT_HTTPHEADER, array_values($this->headers));
    }

    public function deleteHeader($key)
    {
    	unset($this->headers[$key]);
        $this->opt(CURLOPT_HTTPHEADER, array_values($this->headers));
    }


    /* ----------------------- COOKIES --------------------------- */

    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        $this->opt(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
    }

    public function deleteCookie($key)
    {
    	unlink($this->cookies[$key]);
    	$this->opt(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));	
    }


    /* ----------------------- REFERER --------------------------- */

	public function setReferer($referer)
	{
		$this->opt(CURLOPT_REFERER, $referer);
	}

	public function setGoogleReferer($keyword)
	{
		$url = 'https://www.google.fr/?gws_rd=ssl#q='.Tools::cleanString($keyword);
		$this->setReferer($url);
	}

	protected function setRandomReferer()
	{
		if (sizeof($this->referers)>0) {
			$this->setReferer($this->referers[rand(0, (sizeof($this->referers)-1))]);
		} else {
			return null;
		}
	}

	/* ----------------------- USER AGENT --------------------------- */

	public function setUserAgent($UA)
	{
		$this->opt(CURLOPT_USERAGENT, $UA);
	}

	protected function setRandomUserAgent()
	{
        $version = rand(9, 10);
        $major_version = 6;
        $minor_version = $version == 8 || $version == 9 ? rand(0, 2) : 2;
        $extras = rand(0, 3);

        $user_agent =  'Mozilla/5.0 (compatible; MSIE ' . $version . '.0; Windows NT ' . $major_version . '.' . $minor_version . ($extras == 1 ? '; WOW64' : ($extras == 2 ? '; Win64; IA64' : ($extras == 3 ? '; Win64; x64' : ''))) . ')';
		$this->setUserAgent($user_agent);
	}

	/* ----------------------- PROXY --------------------------- */

	public function setProxyList($list=null)
	{
		if (!$list) {
			$config = Service::Config();
			$list = $config->get('proxies');
		}
		
		if (is_array($list)) {
			$this->proxies = $list;
		}
	}

	public function setProxy($proxy, $port = 80, $username = '', $password = '')
    {
        // mettre à null pour anuuler le proxy
        if ($proxy) {

            $this->opt(CURLOPT_HTTPPROXYTUNNEL, 1);
            $this->opt(CURLOPT_PROXY, $proxy);
            $this->opt(CURLOPT_PROXYPORT, $port);

            if ($username != '') {
            	$this->opt(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
            }

        } else {

        	$this->opt(CURLOPT_HTTPPROXYTUNNEL, null);
            $this->opt(CURLOPT_PROXY, null);
            $this->opt(CURLOPT_PROXYPORT, null);
        }
    }
	
	protected function setRandomProxy()
	{
		if (sizeof($this->proxies)>0) {

			$current_proxy = $this->proxies[rand(0, (sizeof($this->proxies)-1))];
			$this->setProxy($current_proxy['id'], $current_proxy['port'], $current_proxy['username'], $current_proxy['password']);

		} else {
			$this->setProxy(null);
		}
	}

	/* ----------------------- CHANGE IDENTITY --------------------------- */

	public function changeIdentity()
	{
		$this->setRandomReferer();
		$this->setRandomUserAgent();
		$this->setRandomProxy();
	}

	/* ----------------------- FILES --------------------------- */

	public function addFile($name, $file_path)
	{
		$this->files[$name] = '@' . realpath($file_path);
	}

	protected function mergeFilesAndVars($vars)
	{
		if (sizeof($this->files)>0) {
			return array_merge($vars, $this->files);
		} else {
			return $vars;
		}
	}

	/* ----------------------- AUTH & SSL --------------------------- */

	public function setAuth($username, $password = '', $type = CURLAUTH_ANY)
    {
        $this->opt(CURLOPT_HTTPAUTH, $type);
        $this->opt(CURLOPT_USERPWD, $username . ':' . $password);
    }




	/* ----------------------- CALLS --------------------------- */

	public function get($url)
	{
		$this->opt(CURLOPT_URL, $url);
		$this->opt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->opt(CURLOPT_HTTPGET, 1);
		$this->is_ssl($url);

		return $this->exec();
	}

	public function post($url, $vars=array())
	{
		$this->opt(CURLOPT_URL, $url);
		$this->opt(CURLOPT_CUSTOMREQUEST, 'POST');
		$this->opt(CURLOPT_POST, 1);
		$this->opt(CURLOPT_POSTFIELDS, $this->mergeFilesAndVars($vars));
		$this->is_ssl($url);

		return $this->exec();
	}

	public function put($url, $vars=array())
	{
		$this->opt(CURLOPT_URL, $url);
		$this->opt(CURLOPT_CUSTOMREQUEST, "PUT");
		$this->opt(CURLOPT_POSTFIELDS, $this->mergeFilesAndVars($vars));
		$this->is_ssl($url);

		return $this->exec();
	}

	public function delete($url)
	{
		$this->opt(CURLOPT_URL, $url);
		$this->opt(CURLOPT_CUSTOMREQUEST, "DELETE");
		$this->is_ssl($url);

		return $this->exec();
	}

	public function login ($url, $vars=array())
	{
		$this->keep_session = true;

		if (!file_exists(realpath($this->cookie_file))) {
			touch($this->cookie_file);
		}

		$this->opt(CURLOPT_COOKIESESSION, 1); // Force à utiliser un nouveau cookie de session
		$this->opt(CURLOPT_COOKIEJAR, realpath($this->cookie_file)); // Fichier dans lequel cURL va écrire les cookies, (pour y stocker les cookies de session) 

		$this->opt(CURLOPT_URL, $url);
		return $this->post($url, $vars);
	}

	public function logout($url=null)
	{
		$this->keep_session = false;
		
		if ($url) {
			$this->get($url);	
		}
	}

	public function download($url, $filename=null, $destination_path=null)
	{
		if (!$filename) {
			$parsed = parse_url($url);
			$filename = basename($parsed['path']);
		}

		if (!$destination_path) {
			if (!$this->upload_path) {
				Service::error("Le dossier de destination n'a pas été défini");
			} else {
				$destination_path = $this->upload_path;
			}
		}

		$destination_path = substr($destination_path, -1) != '/' ? realpath($destination_path . '/') : realpath($destination_path) ;

		$has_dir = explode('/', $filename);

		if(count($has_dir)>1) {

			$filename = $has_dir[(count($has_dir)-1)];
			unset($has_dir[(count($has_dir)-1)]);

			$destination_path = $destination_path . implode('/', $has_dir) . '/';

			if (!is_dir($destination_path)) {
				mkdir($destination_path,0777, true);
			}
		}

		$filepath = $destination_path.'\\'.$filename;

		$this->opt(CURLOPT_BINARYTRANSFER, 1);

		$file_content = $this->get($url);

		$fp = fopen($filepath, "w[b]"); 

		if (!$fp) {
			Service::error("La création du fichier ".$filepath." a échoué");
		}

		fseek($fp, 0); // On remet le curseur au début du fichier
		fputs($fp, $file_content); 
		fclose($fp);
	}

	public function ftp_download($url, $username = '', $password = '', $filename=null, $destination_path=null)
	{
		if ($username != '') {
			$this->opt(CURLOPT_USERPWD, $username . ':' . $password);
		}

		$this->download($url, $filename, $destination_path);
	}

	protected function is_ssl($url)
	{
		if (preg_match('`^https://`i', $url))
		{
			$this->opt(CURLOPT_SSL_VERIFYPEER, 0);
			$this->opt(CURLOPT_SSL_VERIFYHOST, 0);
		} 
	}

	protected function exec()
	{	
		$return = curl_exec($this->ch);

		if ($return === false) {
			Service::error('Curl : ' . curl_error($this->ch));
		}

		$this->infos = curl_getinfo($this->ch);

		curl_close($this->ch);

		$this->is_init = false;

		return $return;
	}

	public function get_info($key=null) {
		if (!$this->is_init) {
			if (!$key) {
				return $this->infos;
			} else {
				return $this->infos[$key];
			}
		} else {
			return null;
		}
	}

	public function dumb_info($key=null) {
		var_dump($this->get_info($key));
		exit();
	}
}


?>