<?php 

namespace Core\Rest;

use Core\Rest\Token;

class Rest
{
	protected $code;
	protected $request;

	public $token;
	protected $token_validated;

	public function __construct($request) 
	{
		$this->cors();
		$this->set_response_code (200);

		if ($request->method == "POST" || $request->method == "PUT") {
			$request->post = json_decode(file_get_contents('php://input'));
		}
	}

	protected function cors() {
		//cguide d'utilisation de CORS : http://blog.inovia-conseil.fr/?p=202

    	// Allow from any origin
	    if (isset($_SERVER['HTTP_ORIGIN'])) {
	        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
	        // you want to allow, and if so:
	        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	        header('Access-Control-Allow-Credentials: true');
	        header('Access-Control-Max-Age: 10');  // cache de 1 jour pour les requetes de type OPTION
	    }

	    // Access-Control headers are received during OPTIONS requests
	    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

	        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
	            // may also be using PUT, PATCH, HEAD etc
	            header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");         

	        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
	            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

	        header("Content-Type: text/plain");
	        exit(0);
	    }
	}

	public function valid_token($token_string) 
	{
		$this->token = new Token($token_string);
		if ($this->token->is_valid()) {
			$this->token_validated = true;
			return true;
		}		
	}

	public function valid_rank($ranks) 
	{
		if ($this->token_validated) {
			$user_rank = $this->token->get_data('rank');
			$validated_rank = false;

			if (is_array($ranks) && in_array($user_rank, $ranks)) {
				$validated_rank = true;
				
			} else if (is_string($ranks) && $user_rank == $ranks) {
				 $validated_rank = true;
			}

			if (!$validated_rank) {
				$this->rest_error (401, 'Action non autorisée pour ce rang');
			}

		} else {
			throw new \Exception('Function valid_rank : token pas encore validé');
		}
	}

	public function valid_params($required=array('user_id','username')) 
	{
		if ($this->token_validated) {

			if (is_array($required)) {

				foreach ($required as $param) {
					$temp = $this->token->get_data($param);
					if ($temp==null && $temp==false && $temp=='') {
						$this->rest_error (406, 'Paramètre "'.$param.'" manquant dans le token');
					}
				}
			
			} else if (is_string($required)) {

				if ($required==null && $required==false && $required=='') {
					$this->rest_error (406, 'Paramètre "'.$param.'" manquant dans le token');
				}
			}

		} else {
			throw new \Exception('Function valid_params : token pas encore validé');
		}
	}

	public function get_token_data($params=array()) 
	{

		if ($this->token_validated) {

			if (is_array($params)) {

				$data = array();

				foreach ($params as $param) {
					$data[$param] = $this->token->get_data($param);
				}
				return $data;

			} else if (is_string($params)) {

				return $this->token->get_data($params);
			}

		} else {
			throw new \Exception('Function get_token_data : token pas encore validé');
		}
	}

	public function set_header($name, $value) 
	{
		header($name.': '.$value);
	}

	public function rest_error ($code, $message) {
		$this->set_response_code ($code, $message);
		exit();
	}

	public function set_response_code ($code, $message=null) {
		$this->code = $code;
		$message = !$message ? $this->get_status_message($code) : $message ;
		header('HTTP/1.0 '.$code.' '.$message); 
	}

	protected function get_status_message($code){
		$status = array(
			100 => 'Continue',  
			101 => 'Switching Protocols',  
			200 => 'OK',
			201 => 'Created',  
			202 => 'Accepted',  
			203 => 'Non-Authoritative Information',  
			204 => 'No Content',  
			205 => 'Reset Content',  
			206 => 'Partial Content',  
			300 => 'Multiple Choices',  
			301 => 'Moved Permanently',  
			302 => 'Found',  
			303 => 'See Other',  
			304 => 'Not Modified',  
			305 => 'Use Proxy',  
			306 => '(Unused)',  
			307 => 'Temporary Redirect',  
			400 => 'Bad Request',  
			401 => 'Unauthorized',  
			402 => 'Payment Required',  
			403 => 'Forbidden',  
			404 => 'Not Found',  
			405 => 'Method Not Allowed',  
			406 => 'Not Acceptable',  
			407 => 'Proxy Authentication Required',  
			408 => 'Request Timeout',  
			409 => 'Conflict',  
			410 => 'Gone',  
			411 => 'Length Required',  
			412 => 'Precondition Failed',  
			413 => 'Request Entity Too Large',  
			414 => 'Request-URI Too Long',  
			415 => 'Unsupported Media Type',  
			416 => 'Requested Range Not Satisfiable',  
			417 => 'Expectation Failed',  
			500 => 'Internal Server Error',  
			501 => 'Not Implemented',  
			502 => 'Bad Gateway',  
			503 => 'Service Unavailable',  
			504 => 'Gateway Timeout',  
			505 => 'HTTP Version Not Supported');
		return ($status[$code])?$status[$code]:$status[500];
	}

}

?>