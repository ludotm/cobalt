<?php 

namespace Core;

use Core\Service;

class Session
{
    use \Core\Singleton;

    protected $db;
    protected $request;
    protected $crypto;
    protected $permissions;
    protected $login_page;
    protected $redirect_page;
    protected $duration;
    protected $is_in_secure_zone;

	protected function __construct()
    {
        $this->request = Service::Request();
        $this->db = Service::Db();

        $config = _get('config.session');
        $this->duration = $config['duration'];
        $this->permissions = _get('config.permissions');
        
        $this->login_page = null;
        $this->redirect_page = null;
        $this->is_in_secure_zone = false;

        ini_set("session.gc_probability", 1);
        ini_set("session.gc_divisor", 1);
        ini_set("session.gc_maxlifetime", ($config['duration']+15));

        session_start();

        if (empty($_SESSION)) {
            $this->restart_session();
        }

        if (isset($_SESSION['flash'])) {
            if (array_key_exists('for_next_page', $_SESSION['flash'])) {
               if (!$_SESSION['flash']['for_next_page']) {
                   $_SESSION['flash'] = array();
                }
            }
        }

        // SI ZONE SECURISEE
        if (is_array($this->permissions)) {
            if (array_key_exists('auth', $this->permissions)) {
                if (array_key_exists('login_page', $this->permissions['auth'])) {
                    $this->login_page = $this->request->url($this->permissions['auth']['login_page']);
                    $this->is_in_secure_zone = true;
                } 
                if (array_key_exists('redirect_page', $this->permissions['auth'])) {
                    $this->redirect_page = $this->request->url($this->permissions['auth']['redirect_page']);
                }
            }
        }

        // SI CONNECTE
        if ($this->get('id_user') != 0) {

            // SI CONNECTE AVEC SESSION EXPIRE
            if (time() > $this->get('expire')) {

                // SI ON EST TOUJOURS EN ZONE SECURISEE
                if ($this->is_in_secure_zone) {
                    // SI reuqête AJAX
                    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        $expired_message = translate('Votre session a expiré').'<br><a href="'.$this->login_page.'">'. translate('Se reconnecter').'</a>';
                        http_response_code (401); // unauthorized
                        echo "<script>document.location.href='".$this->login_page."';</script>";
                        exit($expired_message);
                    } else {
                        $this->session_expired();
                    }
                } else {
                    $this->restart_session();
                }

            // SI CONNECTE AVEC SESSION NON EXPIRE
            } else {

                // ON REVERIFIE SI LA PERSONNE A LE DROIT D ACCEDER A LA ZONE
                if ($this->is_in_secure_zone) {
                    $this->restricted_area();
                }
                 // ON UPDATE LE TEMPS D EXPIRATION DE LA SESSION
                $this->set('expire', time()+$this->duration);
            }  
        } 
        // PAS CONNECTE
        else {
            // SI LA ZONE EST SECURISEE ET QU ON EST PAS SUR LA PAGE DE LOGIN, ON REDIRIGE
            if ($this->is_in_secure_zone && $this->request->get_route() != $this->permissions['auth']['login_page']) {
                $this->redirectLogin('not_allowed');
            }
        }

        if (isset($this->request->post->connexion) && isset($this->request->post->username) && isset($this->request->post->password)) {
            $this->restart_session();
            $this->create_session($this->request->post->username, $this->request->post->password);
            $this->restricted_area();
        }

        if ($this->get('id_user') != 0 && isset($this->request->get->logout)) {
            $this->logout();
        }
	}

	public function set($name, $value){
		$_SESSION[$name] = $value;
	}

	public function get($name){
		if (isset($_SESSION[$name])) {
			return $_SESSION[$name];
		} else {
			Service::warning('Session : variable "'.$name.'" non définie');
		}
	}
    
    public function defined($name){
        return isset($_SESSION[$name]);
    }

	public function flash($message, $type = 'success', $for_next_page = false){
		$_SESSION['flash'] = array(
			'message' => $message,
			'type'	=> $type,
            'for_next_page' => $for_next_page,
		); 
	}

	public function flash_message(){
		if(isset($_SESSION['flash']['message'])){
            switch ($_SESSION['flash']['type']) {
                case 'error':
                $type = 'danger';
                $icon = 'times-circle';
                break;
                case 'warning':
                $type = $_SESSION['flash']['type'];
                $icon = 'warning';
                break;
                case 'success':
                $type = $_SESSION['flash']['type'];
                $icon = 'check';
                break;
            }
            $html = '<script id="flash-messenger-script">$("document").ready(function(){flash_messenger("'.$_SESSION['flash']['message'].'", "'.$type.'"); $("#flash-messenger-script").remove();});</script>';
			//$html = '<div><div class="alert alert-'.$type.'"  style="display:none;"><p><i class="fa fa-'.$icon.' fa-lg"></i> '.$_SESSION['flash']['message'].'</p></div>';
            //$html .= '<script>$(\'.alert\').slideDown(600, function (){ $(this).delay(2300).slideUp(600, function(){$(this).parent().remove(); }); });</script></div>'; 
			$_SESSION['flash'] = array(); 
			return $html; 
		}
	}

    // le paramètre $return_user_only, si défini à true, ne créer pas de session et renvoi simplement l'utilisateur valide
    // (pour la création d'un token pour api par exemple)
    public function create_session ($username, $password, $return_user_only=false) {

        $this->db = Service::Db();
        $tries_duration = _get('config.session.tries_duration');
        $connexion_tries = _get('config.session.connexion_tries');

        $user = $this->db->query_one ('SELECT * FROM _users WHERE username=:username LIMIT 1', array('username'=>$username), '_users');
        
        if (!$user) {
            if ($return_user_only) {
                return 'wrong_username';
            } else {
                $this->redirectLogin('wrong_username');
            }
        }

        if ($user->connexion_tries_time + $tries_duration < time() ) {
        	$user->connexion_tries_count = 0;
        }

        if ($user->connexion_tries_count >= $connexion_tries) {

            if ($return_user_only) {
                return 'too_many_connexions';
            } else {
                $this->redirectLogin('too_many_connexions');
            }
        }

        $user->connexion_tries_count = $user->connexion_tries_count+1;
        $user->connexion_tries_time = time();
        $user->ip = $_SERVER["REMOTE_ADDR"];         
        $user->save();
     
        if (!$this->crypto) {
            $this->crypto = Service::Crypto();
        }

        if ($this->crypto->hash($password) != $user->password) {

            if ($return_user_only) {
                return 'wrong_password';
            } else {
                $this->redirectLogin('wrong_password');
            }      	
		
		} else {
			// SET SESSION
			$user->date_connexion = time();
            $user->connexion_tries_count = 0;
       		$user->save();

            if ($return_user_only) {
                return $user;
            }

            $this->set('is_connected', 1);
            $this->set('id_user', $user->id);
            $this->set('username', $user->username);
            $this->set('expire', time()+$this->duration);

            $ignore_vars = array('id', 'password', 'connexion_tries_time', 'connexion_tries_count', 'deleted');

            foreach ($user as $key => $var) {
                if (!in_array($key, $ignore_vars)) {
                    $this->set($key, $var);    
                }
            }

            // Si superadmin on redirige vers page de séléction client
            if ($this->get('id_rank') == 1) {
               
                $superadmin_redirection = _get('config.superadmin_redirection');

                if (!$superadmin_redirection) {
                    Service::error('Redirection superadmin non définie en configuration');
                }

                $results = $this->db->query('SELECT * FROM _big_users');
                if (!$results) {
                    Service::redirect($superadmin_redirection);
                } else {
                    if ($results->count() > 1 || $results->count() == 0) {
                        Service::redirect($superadmin_redirection);
                    } else if ($results->count() == 1) {
                        $big_user = $results->get_entity(0);
                        $this->set('id_big_user', $big_user->id);
                    }
                }
            }

            // Blocage si pas de big user défini ou que le big user a un accès bloqué à l'admin
            if ($this->get('id_big_user') == 0) {
                $this->restart_session();
                $this->redirectLogin ('no_big_user');
            }

            if ($this->redirect_page) {
                Service::redirect($this->redirect_page);
            }
		}
	}


    protected function session_expired () {
        $this->restart_session();
        http_response_code (401); // unauthorized
        $this->redirectLogin('session_expired');
    }

    public function restart_session() {
        $_SESSION = array();
        session_destroy();
        session_start();
        $this->set('ip', $_SERVER["REMOTE_ADDR"]);
        $this->set('id_user', 0);
        $this->set('id_rank', 0);
        $this->set('username', translate('Invité'));
        $this->set('is_connected', 0);
    }

    public function temp_closed () {
        $this->restart_session();
        $this->redirectLogin ('temp_closed');
    }

    public function no_abonnement () {
        $this->restart_session();
        $this->redirectLogin ('no_abonnement');
    }

	public function logout () {
		$this->restart_session();
		$this->redirectLogin ('logout');
	}

    public function logout_url () {
        return $this->login_page . '?logout';
    }

    public function is_connected() {
        return $this->get('is_connected');
    }

	public function permission ($permission, $entity_id=null) {
		
        if ($this->get('id_rank') == 1) {
            return true;
        }

        $have_permission = $this->db->query_one('SELECT * FROM _rank_has_permission WHERE id_rank=:id_rank AND permission=:permission AND id_big_user=:id_big_user', array(
            'id_rank' => $this->get('id_rank'),
            'permission' => $permission,
            'id_big_user' => $this->get('id_big_user'),
        ));

        if (!$have_permission) {
            return false;
        } else {
            switch ($have_permission->value) {
                case 'YES': return true;
                case 'NO': return false;
                case 'OWN':
                if (!$entity_id) {
                    Service::error('function permission : entity_id nulle');
                }
                $table = $this->permissions['composed'][$permission];
                $owner = $this->db->query_one('SELECT id_user_create FROM '.$table.' WHERE id=:id', array('id'=>_get('models.'.$table.'.params.primary')), $table);
                if ($this->get('id_user') == $owner->id_user_create) {
                    return true;
                } else {
                    return false;
                }
                break;
            }
        }
	}  

    public function restricted_area() 
    {
        if ($this->request->url != $this->login_page) {
            
            $user = $this->db->select('_users')->id($this->get('id_user'))->execute();

            if (!$this->permission('access_zone') || $user->active == 0) {
                $this->redirectLogin('not_allowed');
            }
        }
    }

	public function redirectLogin ($error='') {

        if (!$this->login_page) {
            Service::error('page de login non d&eacute;finie');
        }

		switch ($error) {
			case 'wrong_username':
			$this->flash(translate("Ce nom d'utilisateur n'existe pas"), 'warning', true);
			break;
			case 'wrong_password':
			$this->flash(translate("Mot de passe incorrect"), 'warning', true);
			break;
			case 'too_many_connexions':
			$this->flash(translate("Trop de tentatives de connexion, réessayez plus tard"), 'warning', true);
			break;
            case 'session_expired':
            $this->flash(translate("Votre session a expiré"), 'warning', true);
            break;
			case 'temp_closed':
            $this->flash(translate("Cet espace est temporairement cl&ocirc;t"), 'warning', true);
            break;
            case 'no_abonnement':
            $this->flash(translate("Ce compte n'est pas abonné"), 'warning', true);
            break;
            case 'no_big_user':
            $this->flash(translate("Cet utilisateur n\'est affili&eacute; à aucun compte"), 'warning', true);
            break;
            case 'not_allowed':
			$this->flash(translate("Vous n'avez pas accès à cet espace"), 'warning', true);
			break;
			case 'logout':
			$this->flash(translate("Vous avez été déconnecté"), 'warning', true);
			break;
		}

        Service::redirect($this->login_page);
	}
}

?>