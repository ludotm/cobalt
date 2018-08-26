<?php 

namespace Core\Api;

use Core\Service;

class Facebook
{
	use \Core\Singleton;

	protected $db;
	protected $request;
	protected $session;

	public $api;
	protected $helper;
	protected $admin_access_token; // access token associé à l'application (compte de chez nous, si il a été activé)
	protected $user_access_token;
	protected $page_access_token;

	protected $app_id;

	public $base_url = 'https://www.facebook.com/';

	protected function __construct($app_id=null) 
	{
		$this->db = Service::Db();
		$this->request = Service::Request();
		$this->session = Service::Session();
		$this->get_api();

		$this->app_id = $app_id ? $app_id : _config('social.facebook_default_app_id', null);
	}

	public function get_api() 
	{
		if ($this->api) {
			return $this->api;

		} else {

			$app = $this->db->query_one("SELECT * FROM _platforms_applications WHERE platform='facebook' AND id_big_user='".$this->session->get('id_big_user')."'");

			if (!$app) {
				Service::error('Aucune application facebook enregistr&eacute;e pour ce client');
			}

			$this->admin_access_token = $app->admin_access_token != '' ? $app->admin_access_token : null;
			$this->user_access_token = $app->admin_access_token; // on met par défaut en user_token le token de l'admin, remplacé plus par le token utilisateur si celui-ci est connecté
			$this->page_access_token = null;

			require_once  "Facebook/autoload.php";

			$this->api = new \Facebook\Facebook([
			  'app_id'                => $app->api_key,
			  'app_secret'            => $app->api_secret,
			  'default_graph_version' => 'v2.5',
			]);

			return $this->api;
		}
	}

	/* ----------------------------------- JAVASCRIPT SDK & MODULES -------------------------------------- */

	public function get_javascript_sdk($lang='fr_FR') 
	{	
		if (!$this->app_id) {
			Service::error('function get_javascript_sdk : facebook app id manquante');
		}

		ob_start();
 		?>
 			<div id="fb-root"></div>

			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/<?= $lang ?>/sdk.js#xfbml=1&version=v2.6&appId=<?= $this->app_id ?>";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>
 		<?php
		ob_end_flush();
	}

	public function get_like_button($page_id, $new_params=array()) 
	{	
		$default_params = array(
			'data-width' => 250,
			'data-layout' => 'box_count',
			'data-action' => 'like',
			'data-show-faces' => 'false',
			'data-share' => 'false',
		);
		$params = array_merge($default_params, $new_params);

		$url = $this->base_url . $page_id;
		
		$html = '<div class="fb-like" data-href="'.$url.'"';

		foreach ($params as $key => $value) {
			$html .= ' '.$key.'="'.$value.'"';
 		}

		$html .= '></div>';
 		
 		return $html;
	}

	public function get_like_box($page_id, $new_params=array()) 
	{	
		$url = $this->base_url . $page_id;

		$default_params = array(
			'data-tabs' => 'timeline',
			'data-width' => 300,
			'data-height' => 350,
			'data-small_header' => 'true',
			'data-adapt-container-width' => 'true',
			'data-hide-cover' => 'true',
			'data-show-facepile' => 'true',
		);

		$params = array_merge($default_params, $new_params);

		$html = '<div class="fb-page" data-href="'.$url.'" ';

		foreach ($params as $key => $value) {
			$html .= ' '.$key.'="'.$value.'"';
 		}

 		$html .= '><blockquote cite="https://www.facebook.com/facebook" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/facebook">Facebook</a></blockquote></div>';

		return $html;
	}

	/* ------------------------------------------------------------------------- */

	public function get_helper ($type = 'login') {
		
		switch ($type) {
			case 'login': $this->helper = $this->api->getRedirectLoginHelper(); break;
			case 'javascript': $this->helper = $this->api->getJavaScriptHelper(); break;
			case 'canvas': $this->helper = $this->api->getCanvasHelper(); break;
			case 'page_tab': $this->helper = $this->api->getPageTabHelper(); break;
		}
		
		return $this->helper;
	}

	public function facebook_connect_processing ($required_scopes=array('public_profile', 'email'), $redirect_url='') 
	{
		/*
			Avaible scopes : 
			'public_profile',
			'user_friends',
			'email',
			'user_birthday',
			'user_location',
			'user_website',
			'user_likes',
			'user_posts',

			'publish_actions',
			'manage_pages',
		*/

		if (isset($this->request->get->facebook_logout)) { // Tentative de déconnexion (après redirection de facebook)
			unset($_SESSION['facebook_connect']);

		} else if (isset($this->request->get->code) && isset($this->request->get->state)) { // Tentative de connexion, réponse de facebook

			$helper = $this->get_helper();

			try {
			  $accessToken = $helper->getAccessToken();
			} catch(\Facebook\Exceptions\FacebookResponseException $e) {
			  // When Graph returns an error
			  echo 'Erreur Open Graph : ' . $e->getMessage();
			  exit;
			} catch(\Facebook\Exceptions\FacebookSDKException $e) {
			  // When validation fails or other local issues
			  echo 'Erreur Facebook SDK : ' . $e->getMessage();
			  exit;
			}

			if (!isset($accessToken)) {
			  if ($helper->getError()) {
			    header('HTTP/1.0 401 Unauthorized');
			    echo "Error: " . $helper->getError() . "\n";
			    echo "Error Code: " . $helper->getErrorCode() . "\n";
			    echo "Error Reason: " . $helper->getErrorReason() . "\n";
			    echo "Error Description: " . $helper->getErrorDescription() . "\n";
			  } else {
			    header('HTTP/1.0 400 Bad Request');
			    echo 'Bad request';
			  }
			  exit;
			}

			$oAuth2Client = $this->api->getOAuth2Client();
			$tokenMetadata = $oAuth2Client->debugToken($accessToken);
			$user_id = $tokenMetadata->getUserId();
			$user_info = $this->get_user_info($user_id);

			// Validation (these will throw FacebookSDKException's when they fail)
			//$tokenMetadata->validateAppId({app-id}); // Replace {app-id} with your app id
			// If you know the user ID this access token belongs to, you can validate it here
			//$tokenMetadata->validateUserId('123');
			//$tokenMetadata->validateExpiration();

			if (!$accessToken->isLongLived()) {
			  // Exchanges a short-lived access token for a long-lived one
			  try {
			    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			  } catch (\Facebook\Exceptions\FacebookSDKException $e) {
			    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
			    exit;
			  }
			}

			$this->user_access_token = (string) $accessToken;

			$_SESSION['facebook_connect'] = array_merge(array(
				'access_token' => (string) $accessToken,
			), $user_info);

			/* TODO 
				
				> Faire uen requete pour vérifier si ce platform user id existe déjà en base (table platform accounts)
				> soit faire un insert si il existe pas, sinon un update avec date d'expiration du token
				> Enregistrer un maximum d'infos à ce niveau là  
				> Retourner le nom du mec 

				> peut-être créer un cookie pour la prochaine visite du gars, pour qu'il n'ait pas beoin de se reco
			*/
			//$this->db->update('_platform_accounts')->values(array('access_token'=>$access_token))->id()->execute(); 

		}
	}

	public function is_connected () 
	{
		return isset($_SESSION['facebook_connect']);
	}

	public function get_login_url ($required_scopes, $redirect_url='') 
	{
		$redirect_url = $redirect_url != '' ? $redirect_url : strtok(SITE_URL.$_SERVER['REQUEST_URI'], '?') ;
		$helper = $this->get_helper();

		return $helper->getLoginUrl($redirect_url, $required_scopes);	
	}

	public function get_login_button ($required_scopes, $redirect_url='') 
	{
		return '<a class="btn btn-primary" href="' . $this->get_login_url($required_scopes, $redirect_url) . '" style="color:white"><i class="fa fa-facebook"></i> &nbsp; Connectez-vous via Facebook</a>';		
	}

	public function get_logout_url ($redirect_url='') 
	{
		//https://www.facebook.com/?stype=lo&jlou=AfchpLGpPOGq9tTo9fUi7moamLr1fDEvesgNMT5FfrK3w54Kn6GLwgCOxWalVe4gvZVKJKN9k4jM_PZZhkExB2HOFl73-YwEYlS7FDhA3QiboQ&smuh=61810&lh=Ac-4xsnY0GBTx813
		$redirect_url = $redirect_url != '' ? $redirect_url . '?facebook_logout=1' : strtok(SITE_URL.$_SERVER['REQUEST_URI'], '?') . '?facebook_logout=1';
		$redirect_url = $this->base_url.'logout.php';
		$helper = $this->get_helper();

		return $helper->getLogoutUrl($_SESSION['facebook_connect']['access_token'], $redirect_url);
	}

	public function get_logout_button ($redirect_url='') 
	{
		return '<a href="'.$this->get_logout_url().'">Se déconnecter</a>';
	}

	/* ---------------------------------- FONCTION REQUTE --------------------------------------------- */

	public function call ($request_type, $endpoint, $acces_token, $params=array()) 
	{
		try {

			if ($request_type == 'post') {
				$response = $this->api->{$request_type}($endpoint, $params, $acces_token);
			} else {
				$response = $this->api->{$request_type}($endpoint, $acces_token);
			}

		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
		  
			Service::error('Erreur Facebook Graph : ' . $e->getMessage());

		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
		  
			Service::error('Erreur Facebook SDK : ' . $e->getMessage());
		}
		return $response;
	}


	/* ---------------------------------- REQUTES PREDEFINIES --------------------------------------------- */

	// Récupérer les infos d'un utilisateur, par défaut de l'utilisateur connecté
	public function get_user_info($user_id='me') 
	{
		$response = $this->call('get', '/'.$user_id.'/?fields=email,name,likes', $this->user_access_token);
		return $response->getDecodedBody();
	}

	// Récupérer la liste d'ami de l'utilisateur
	public function get_user_friends($user_id='me') 
	{
		$response = $this->call('get', '/'.$user_id.'/friends', $this->user_access_token);
		return $response->getDecodedBody();
	}

	// Récupérer les infos d'une page lambda (dont les likes)
	public function get_page_info($page_id) 
	{
		$response = $this->call('get', '/'.$page_id.'/?fields=name,likes,about,attire,bio,location,parking,hours,emails,website', $this->user_access_token);
		return $response->getDecodedBody();
	}

	// Récupérer l'url d'un avatar
	public function get_avatar_url ($user_or_page_id='me', $width='70', $height='70') 
	{
		$response = $this->call('get', '/'.$user_or_page_id.'/picture?redirect=false&height='.$height.'&width='.$width, $this->user_access_token);
		$data = $response->getDecodedBody();
		return $data['data']['url'];
	}

	public function is_liking_page($page_id, $user_id='me') 
	{
		if (!$this->have_permission('user_likes')) {
			exit('Permission non accordée de voir les likes');
		}

		$response = $this->call('get', '/'.$user_id.'/likes', $this->user_access_token);
		$response = $response->getGraphEdge()->asArray();
		
		$pages = array();
		
		foreach ($response as $page) {
			$pages []= $page['id'];
		}
		
		return in_array($page_id, $pages);	
	}

	// get all permissions
	public function have_permission($permission, $user_id='me') 
	{
		$permissions = $this->get_user_permissions($user_id);
		if (array_key_exists($permission, $permissions)) {
			if ($permissions[$permission] == 'granted') {
				return true;
			}
		}
		return false;
	}

	// get all permissions
	public function get_user_permissions($user_id='me') 
	{
		$response = $this->call('get', '/'.$user_id.'/permissions', $this->user_access_token);
		$data = $response->getDecodedBody();

		$permissions = array();
		foreach ($data['data'] as $permission) {
			$permissions[$permission['permission']] = $permission ['status'];						
		}
		return $permissions;
	}

	// delete one permission
	public function delete_user_permission($permission) 
	{
		$response = $this->call('delete', '/me/permissions/'.$permission, $this->user_access_token);
		return $response->getDecodedBody();
	}

	// delete all permissions
	public function delete_user_permissions() 
	{
		$response = $this->call('delete', '/me/permissions', $this->user_access_token);
		return $response->getDecodedBody();
	}

	// Poster un message sur une page en tant que visiteur
	public function post_page_as_user($page_id, $message, $link='') 
	{
		$params = array('message' => $message);

		if ($link != '') {
			$params['link'] = $link;
		}
		$response = $this->call('post', '/'.$page_id.'/feed', $this->user_access_token, $params);
		return $response->getDecodedBody();
	}

	// Poster un message sur une page en tant que page
	public function post_as_page($page_id, $message, $link='') 
	{
		$params = array('message' => $message);

		if ($link != '') {
			$params['link'] = $link;
		}
		$response = $this->call('post', '/'.$page_id.'/feed', $this->page_access_token, $params);
		return $response->getDecodedBody();
	}

	public function check_is_admin_page ($page_id) 
	{
		if (!is_numeric($page_id)) {
			$page = $this->get_page_info($page_id);
			$page_id = $page['id'];
		}

		$response = $this->call('get', '/me/accounts', $this->user_access_token);
		$data = $response->getDecodedBody();

		foreach ($data['data'] as $account) {
			if ($account['id'] == $page_id) {

				//$account['access_token'] >>>> sans doute le bon endroit pour enregistrer le page access token
				return true;
			}
		}
		return false;
	}	

}

?>