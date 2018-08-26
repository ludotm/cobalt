<?php 

namespace Core;

use Core\Service;

class Crypto
{
	use \Core\Singleton;

	protected $secret;
	protected $defaultMethod;
	protected $methods;

	protected function __construct() 
	{
		$this->methods = array('md5', 'sha256');
		//print_r(hash_algos()); // Liste (array) des méthodes de Hash supportées
		$this->defaultMethod = 'sha256';
		$this->secret = 'tjIs9aM3';
	}

	public function setMethod($method)
	{
		$this->defaultMethod = $method;
	}

	public function setSecret($secret)
	{
		$this->secret = $secret;
	}

	public  function safe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }
 
    public function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }


	// Crypte dans un seul sens
	public function hash ($string, $method=null, $secret=null) 
	{
		$method = !$method ? $this->defaultMethod : $method;
		$secret = !$secret ? $this->secret : $secret;

		if (!in_array($method, $this->methods)) {
			Service::error("Méthode de hash inconnue");
		}

		$key = hash_hmac( $method, $string, $secret );
        $hash = hash_hmac( $method, $string, $key );

        return $hash;
	}

 	// Crypte avec possibilité de décryptage
    public  function encode($value){ 
        if(!$value){return '';}
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->secret, $text, MCRYPT_MODE_ECB, $iv);
        return trim($this->safe_b64encode($crypttext)); 
    }
 
    public function decode($value){
        if(!$value){return '';}
        $crypttext = $this->safe_b64decode($value); 
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->secret, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }
}

?>