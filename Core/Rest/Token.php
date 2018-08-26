<?php 

namespace Core\Rest;

class Token
{
	protected $secret;

	protected $header;
	protected $encoded_header;
	protected $payload;
	protected $encoded_payload;
	protected $signature;
	
	protected $value;

	public function __construct($value=null) 
	{
		$this->set_secret('tjIs9aM3!f$A');

		$this->set_value($value);
	}

	public function set_secret($secret) 
	{
		$this->secret = $secret;	
	}

	public function set_value($value) 
	{
		$this->value = $value;
		if ($value) {

			$token_parts = explode('.',$this->value);

			if (count($token_parts) != 3) {
				throw new \Exception('Token mal formé');
			}
			$this->header = json_decode(base64_decode($token_parts[0]));
			$this->encoded_header = $token_parts[0];
			$this->payload = json_decode(base64_decode($token_parts[1]));
			$this->encoded_payload = $token_parts[1];
			$this->signature = $token_parts[2];	
		} else {
			$this->header = null;
			$this->encoded_header = null;
			$this->payload = null;
			$this->encoded_payload = null;
			$this->signature = null;
		}
	}

	public function get_value() 
	{
		return $this->value;
	}
	

	public function create ($lifetime=604800, $id_user=0, $username='Guest', $role='guest', $email='', $custom=array())
	{
		// $lifetime = 0 pour créer un token permanent 

		// typ : JWT (json web token), 
		// alg : algorythme d'encodage 
		$this->header = array(
			"typ" => "JWT",
			"alg" => "HS256" 
		);
		$this->encoded_header = base64_encode(json_encode($this->header));

		// iss : domaine qui émet le token "https://codeheroes.fr"
		// iat : date de génération du token (time)
		// exp : date d'expiration du token (time)
		// aud : application ou site qui recoit le token
		// sub (subject) : ID de l'utilisateur connecté
		// role (optionnel) : rang de l'utilisatuer 
		//  email (optionnel) : email de l'utilisateur
		$this->payload = array(
			"iss" => DOMAIN,
			"iat" => time(), 
			"exp" => $lifetime == 0 ? 0 : (time()+$lifetime), 
			"aud" => $_SERVER['HTTP_REFERER'], 
			"sub" => $id_user, 
			"role" => $role,
			"username" => $username,
			"email" => $email,
		);
		foreach($custom as $key => $value) {
			$this->payload[$key] = $value;
		}

		$this->encoded_payload = base64_encode(json_encode($this->payload));
		
		$token_signature = $this->generate_signature();

		$this->value = $this->encoded_header.'.'.$this->encoded_payload.'.'.$token_signature;

		return $this->value;
	}

	protected function generate_signature() 
	{
		$this->signature = hash_hmac('sha256', $this->encoded_header.'.'.$this->encoded_payload, $this->secret);
		return $this->signature;
	}

	protected function is_set() 
	{
		if (!$this->value) {
			throw new \Exception('Token vide');
		}
	}

	public function is_valid ()
	{
		$this->is_set();

		$expected_signature = $this->generate_signature();

		if ($this->signature != $expected_signature) {
			throw new \Exception('Signature du token erronée');
		}
		if ($this->get_data('iss') != DOMAIN) {
			throw new \Exception('Ce token ne provient pas du bon domaine');
		}
		if ($this->get_data('exp') != 0 && $this->get_data('exp') < time()) {
			throw new \Exception('Token expiré');
		}
		return true;
	}

	public function get_data ($type=null)
	{
		$this->is_set();

		$data = $this->payload;

		switch ($type) {

			case null:
				return $data;
				break;

			case 'domain': case 'iss':
				return array_key_exists('iss',$data) ? $data['iss'] : null;
				break;

			case 'expiration': case 'exp':
				return array_key_exists('exp',$data) ? $data['exp'] : null;
				break;

			case 'iat': case 'created':
				return array_key_exists('iat',$data) ? $data['iat'] : null;
				break;

			case 'aud': case 'origin':
				return array_key_exists('aud',$data) ? $data['aud'] : null;
				break;

			case 'sub': case 'user_id': case 'id_user':
				return array_key_exists('sub',$data) ? $data['sub'] : null;
				break;

			case 'role': case 'rank':
				return array_key_exists('role',$data) ? $data['role'] : null;
				break;

			case 'email':
				return array_key_exists('email',$data) ? $data['email'] : null;
				break;

			case 'username': 
				return array_key_exists('username',$data) ? $data['username'] : null;
				break;

			default : return null; break;
		}
	}

}

?>