<?php 

namespace Core;

use Core\Page;
use Core\Table;
use Core\Service;
use Core\Tools;

class Controller 
{	 
	protected $request;
	protected $db;
	protected $session;
	protected $page;

	protected $loading_widget;
	protected $loading_layout;
	protected $rendered = false;

	public function __construct() 
	{
		$this->request = Service::Request();
		if ($this->request->load_db) {
			$this->db = Service::Db();
		}
		$this->session = Service::Session();
		$this->page = Service::Page();

		$this->set_default_params();

		if ($this->request->type_action == 'page') {
			$this->less_autocompile();
			//$this->less_compile(); // force compilation
		}
	}

	/* -------------------- VIEW FUNCTIONS --------------------  */

	protected function url ($route, $vars=array()) {
		return $this->request->router($route, $vars);
	}
	protected function full_url ($route, $vars=array()) {
		return 'https://'.DOMAIN.$this->url($route, $vars);
	}
	public function img ($name) {
		return '/' . $this->request->zone . '/img/' . $name ;
	}
	public function fm_img ($path) {
		return '/plugins/cobalt/UI/' . $path ;
	}
	public function flag ($country) {
		return $this->fm_img('flags/'.strtoupper($country).'.gif');
	}
	public function no_avatar ($gender=1) {
		return $this->fm_img('no-avatar'.($gender==2?'-woman':'').'.png');
	}
	
	public function gi_icon ($name, $classes='', $color='', $style='') {
		return $this->icon($name, $classes, $color, $style, 'gi');
	}
	public function fa_icon ($name, $classes='', $color='', $style='') {
		return $this->icon($name, $classes, $color, $style, 'fa');
	}
	public function icon ($name, $classes='', $color='', $style='',$type='') {
			 
		$type = $type == '' ? $this->page->icon_type : $type ;
		
		switch ($type) {
			case 'fa': $prefix = 'fa fa-'; break;
			case 'gi': default : $prefix = 'glyphicon glyphicon-';  break;
		}

		$html = '<i class="'.$prefix.$name.' '.$classes.'" style="'.($color!=''?'color:'.$color.'; ':'').$style.'" aria-hidden="true"></i>';
		return $html ;
	}
	public function tooltip ($text, $placement, $html) {
		return '<a href="#" class="cursor-default" data-toggle="tooltip" data-placement="'.$placement.'" title="'.$text.'">'.$html.'</a>';
	}

	/* -------------------- GET FORM MODEL --------------------  */

	public function get_form($name) {
		$form_file = $this->request->zone.'/forms/'.$name.'.php';
		return require_once($form_file);
	}

	/* -------------------- ALLIAS FUNCTION --------------------  */

	public function permission ($permission, $entity_id=null) 
	{
		return $this->session->permission($permission, $entity_id);
	}

	public function is_superadmin () 
	{
		return $this->session->get('id_rank') == 1 ? true : false ;
	}
	public function is_commercial () 
	{
		return $this->session->get('username') == 'commercial' ;
	}
	public function is_support () 
	{
		return $this->session->get('username') == 'support' ;
	}
	public function is_admin () 
	{
		return $this->session->get('id_rank') == 2 ? true : false ;
	}

	public function flash ($message, $type='warning') 
	{
		return $this->session->flash($message, $type);
	}

	public function menu ($name, $subitems_position="left") 
	{		
		$menu = $this->{'menu_'.$name}();
		$menu->draw($subitems_position);
	}

	public function is_route ($route_key) 
	{	
		return $this->request->route == $route_key ;
	}

	public function get_image ($id) 
	{
		return $this->db->select('_images')->id($id)->execute();
	}

	public function get_document ($id) 
	{
		return $this->db->select('_documents')->id($id)->execute();
	}

	/* -------------------- FONCTIONS DE REDEFINITION DES PARAMETRES AUTOMATIQUES --------------------  */
	
	protected function set_default_params() {

		$this->page->scripts = $this->page->stylesheets = $this->page->plugins = array();

		$this->set_html_base('plugins/cobalt/html5-starter.phtml');
		$this->set_layout('layout');
		$this->set_template($this->request->action);
		$this->set_base_title(_get('config.page.base_title'),'');
		$this->set_title(_get('config.page.title', ''));
		$this->add_meta('description', _get('config.page.description', ''));
		
		$favicon = _get('config.favicon');
		$this->set_favicon(!$favicon ? 'favicon.png' : $favicon);

		$google_font = _get('config.google_font');
		if ($google_font) {
			if (is_string($google_font)) {
				$this->google_font($google_font);
			} else if (is_array($google_font)) {
				foreach ($google_font as $font) {
					$this->google_font($font);
				}
			}
		}

		$icons = _get('config.icons');
		if ($icons) {
			$this->set_type_icons ($icons);
		}

		$metas = _get('config.metas');
		if ($metas)
		foreach ($metas as $key => $value) {
			$this->add_meta($key, $value);
		}

		$plugins = _get('config.plugins');
		if ($plugins)
		foreach ($plugins as $plugin) {
			$this->add_plugin($plugin);
		}

		$scripts = _get('config.scripts');
		if ($scripts)
		foreach ($scripts as $script) {
			$this->add_script($script);
		}

		$stylesheets = _get('config.stylesheets');
		if ($stylesheets)
		foreach ($stylesheets as $stylesheet) {
			$this->add_stylesheet($stylesheet);
		}

		$less = _get('config.less');
		if ($less)
		foreach ($less as $less_sheet) {
			$this->add_less($less_sheet);
		}
	}

	protected function compile_params() {

		$plugins_list = _get('plugins');
		
		// PLUGINS DEPENDENCIES
		$plugins_dependencies = array();

		foreach ($this->page->plugins as $plugin) {

			if (array_key_exists($plugin, $plugins_list)) {

				if (array_key_exists('dependencies', $plugins_list[$plugin])) {
					foreach ($plugins_list[$plugin]['dependencies'] as $dependency) {
						$plugins_dependencies []= $dependency;
					}
				}
			}
		}

		$this->page->plugins = array_merge($this->page->plugins, $plugins_dependencies);
		
		// DOUBLONS
		$this->page->plugins = array_unique($this->page->plugins);
		$this->page->scripts = array_unique($this->page->scripts);
		$this->page->stylesheets = array_unique($this->page->stylesheets);
		$this->page->less = array_unique($this->page->less);

		// LESS
		$less_compilation_type = _get('config.less_params.compilation_type');

		if (!empty($this->page->less) && $less_compilation_type == 'js') {
			$this->add_plugin('less');
		}

		// URL LESS
		foreach ($this->page->less as $key => $value) {
			if (!Tools::is_url($this->page->less[$key])) {
				if ($less_compilation_type == 'js') {
					$this->page->less[$key] = '/'.$this->request->zone.'/css/'.$value;
				} else {
					$this->page->stylesheets []= str_replace('.less','.css',$value);
					unset($this->page->less[$key]);
				}
			}
		}

		// URL SCRIPTS 
		foreach ($this->page->scripts as $key => $value) {
			if (!Tools::is_url($this->page->scripts[$key])) {
				$this->page->scripts[$key] = '/'.$this->request->zone.'/js/'.$value;
			}
		}

		// URL STYLESHEETS
		foreach ($this->page->stylesheets as $key => $value) {
			if (!Tools::is_url($this->page->stylesheets[$key])) {
				$this->page->stylesheets[$key] = '/'.$this->request->zone.'/css/'.$value;
			}
		}

		// COMPILE PLUGINS
		$plugins_scripts = array();
		$plugins_stylesheets = array();

		foreach ($this->page->plugins as $key => $value) {

			$plugin = is_int($key) ? $value : $key ;
			$extensions = is_int($key) ? array() : $value;

			if (array_key_exists($plugin, $plugins_list)) {
				if (array_key_exists('scripts', $plugins_list[$plugin])) {
					foreach ($plugins_list[$plugin]['scripts'] as $script) {
						$plugins_scripts []= '/plugins/'.$plugins_list[$plugin]['folder'].'/'.$script;
					}
				}
				if (array_key_exists('locales', $plugins_list[$plugin])) {

					$locale = Service::get_lang();
					$plugins_scripts []= '/plugins/'.$plugins_list[$plugin]['folder'].'/'.str_replace(array('[LOCALE]', '[LOCALE_MAJ]'), array(strtolower($locale),strtoupper($locale)), $plugins_list[$plugin]['locales']);
				}
				if (array_key_exists('stylesheets', $plugins_list[$plugin])) {
					foreach ($plugins_list[$plugin]['stylesheets'] as $stylesheet) {
						$plugins_styelsheets []= '/plugins/'.$plugins_list[$plugin]['folder'].'/'.$stylesheet;
					}
				}
				// Merge des stylesheets de telle sorte que les stylesheets des plugins soient loadées en premier
			}

			// Extensions
			if (!empty($extensions)) {
				foreach ($extensions as $key => $value) {
					$ext = pathinfo($value, PATHINFO_EXTENSION);
					if ($ext == 'js') {
						$this->page->scripts []= '/plugins/'.$plugins_list[$plugin]['folder'].'/'.$value;
					} else if ($ext == 'css') {
						$this->page->stylesheets []= '/plugins/'.$plugins_list[$plugin]['folder'].'/'.$value;
					}
				}
			}
		}

		$this->page->stylesheets = array_merge($plugins_styelsheets, $this->page->stylesheets); 
		$this->page->scripts = array_merge($plugins_scripts, $this->page->scripts); 

		// METAS DOUBLONS
		$metas = array();
		foreach ($this->page->metas as $key => $value) {
			if (!in_array($key, $metas)) {
				$metas[$key] = $value;
			}
		}
		$this->page->metas = $metas;

	}

	/* -------------------- PARAMS SETTERS INDIVIDUELS --------------------  */

	public function favicon() {

		if ($this->page->favicon != '' && $this->page->favicon) {
		
			$ext = pathinfo($this->page->favicon, PATHINFO_EXTENSION);
			$type = $ext == 'png' ? 'image/png' : 'image/x-icon';
			
			return '<link rel="icon" type="'.$type.'" href="/'.$this->request->zone.'/img/'.$this->page->favicon.'?maj='.MAJ.'" />';
		}	
	}

	public function set_html_base($html_base) {
		$this->page->html_base = $html_base;
	}

	public function set_layout ($layout) {
		$this->page->layout = $layout;
	}

	public function set_template ($template) 
	{
		$this->page->template = $template;
	}

	public function set_base_title ($title)
	{
		$this->page->base_title = $title;
	}

	public function set_title ($title)
	{
		$this->page->title = $title;
	}

	public function set_type_action ($type_action) {
		$this->request->type_action = $type_action;
	}

	public function set_favicon ($favicon)
	{
		$this->page->favicon = $favicon;
	}

	public function set_type_icons ($type)
	{
		$this->page->icon_type = $type;
	}

	public function google_font ($font)
	{
		$this->page->google_font []= $font;
	}

	public function add_meta($name, $value)
	{
		$this->page->metas[$name] = $value;
	}

	public function add_plugin($plugin, $extensions=array())
	{
		if(!empty($extensions)){
			//$this->page->plugins[$plugin] = $extensions; 
		} else {
			$this->page->plugins []= $plugin;
		}
	}
	public function add_script($scripts)
	{
		$this->page->scripts []= $scripts;
	}
	public function add_stylesheet($stylesheets)
	{
		$this->page->stylesheets []= $stylesheets;
	}
	public function add_less($less)
	{
		$this->page->less []= $less;
	}

	public function insert_script($script)
	{
		return '<script src="/'.$this->request->zone.'/js/'.$script.'"></script>';
	}

	/* -------------------- LIST STORAGE --------------------  */
	/* stocke en mémoire des listes de données récupérées en BDD qui servent à plusieurs endroit (pour éviter de multiplier les requetes) */

	public function store_list ($name, $sql) {
		$this->lists_storage[$name] = $sql;
	}

	public function get_list ($name) {

		if (!array_key_exists($name, $this->lists_storage)) {
			Service::error('Function get_list : aucune liste "'.$name.'" n\'a été définie');
		}

		if (!is_array($this->lists_storage[$name])) {

			$query = $this->db->query($this->lists_storage[$name]);
			$this->lists_storage[$name] = array();

			if ($query) {
				foreach ($query as $item) {
					$array = $item->toArray();
					$keys = array_keys($array);
					$this->lists_storage[$name][$array[$keys[0]]] = $array[$keys[1]];
				}
			}
		}
		return $this->lists_storage[$name];
	}

	public function get_from_list ($name, $id) {
		$list = $this->get_list($name);
		return array_key_exists($id, $list) ? $list[$id] : '';
	}

	/* -------------------- GOOGLE ANALYTICS --------------------  */

	protected function google_analytics()
	{
		$analytics_id = _get('config.google_analytics');

		if (!$analytics_id) {
			return;
		}

		ob_start(); 
		?>

		<script>
	         var _gaq=[['_setAccount','<?= $analytics_id ?>'],['_trackPageview']];
	         (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
	         g.src='//www.google-analytics.com/ga.js';
	         s.parentNode.insertBefore(g,s)}(document,'script'));
	    </script>
		
		<?php 
		ob_end_flush();
	}

	/* -------------------- SOCIAL SHARING --------------------  */

	public function social_sharing ($url, $title) 
    {
        $html = '<div class="share-module">';
        $html .= '<ul><li>';
        $html .= '<a href="#"><i class="fa fa-share-alt lg pull-right margin4" style="" aria-hidden="true"></i></a>';
        $html .= '<ul>';

        // TWITTER 
        $html .= '<li><a target="_blank" title="Twitter" href="https://twitter.com/share?url='.$url.'&text='.$title.'" rel="nofollow" onclick="javascript:window.open(this.href, \'\', \'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700\');return false;"><img src="'.$this->fm_img('twitter_icon.png').'" alt="Twitter" /></a></li>';

        // FACEBOOK
        $html .= '<li><a target="_blank" title="Facebook" href="https://www.facebook.com/sharer.php?u='.$url.'&t='.$title.'" rel="nofollow" onclick="javascript:window.open(this.href, \'\', \'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=700\');return false;"><img src="'.$this->fm_img('facebook_icon.png').'" alt="Facebook" /></a></li>';

        // GOOGLE +
        $html .= '<li><a target="_blank" title="Google +" href="https://plus.google.com/share?url='.$url.'&hl=fr" rel="nofollow" onclick="javascript:window.open(this.href, \'\', \'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=450,width=650\');return false;"><img src="'.$this->fm_img('gplus_icon.png').'" alt="Google Plus" /></a></li>';

        // LINKED IN 
        $html .= '<li><a target="_blank" title="Linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.$title.'" rel="nofollow" onclick="javascript:window.open(this.href, \'\',\'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=450,width=650\');return false;"><img src="'.$this->fm_img('linkedin_icon.png').'" alt="Linkedin" /></a></li>';

        // MAIL
        $html .= '<li><a target="_blank" title="Envoyer par mail" href="mailto:?subject='.$title.'&body='.$url.'" rel="nofollow"><img src="'.$this->fm_img('email_icon.png').'" alt="email" /></a></li>';

        $html .= '</ul></li></ul></div>';

        return $html;
    }

	/* -------------------- RENDER FUNCTIONS --------------------  */
	
	public function load_page() {
		$url = PREFIX . $this->request->zone .DS. 'views' .DS. strtolower($this->request->controller) .DS. 'page_' . $this->page->template . '.phtml';
		$this->load_view($url, $this->page->vars);
	}

	public function load_layout() {

		$this->loading_layout = true;
		$vars = $this->{$this->page->layout}();
	}

	public function widget($name, $vars=array())
	{
		$function = 'widget_'.$name;
		$this->loading_widget = $function;
		
		foreach ($vars as $key => $value) {
			$this->request->get->{$key} = $value;
		}
		
		$this->$function();
	}

	public function mini_manager($name) {

		// Vérifier l'existence de la function  'mini_manager_'.$name

		$options = $this->{'mini_manager_'.$name}();
		$options['zone'] = strToLower($this->request->zone);
		$options['controller'] = strToLower($this->request->controller);
		$options['name'] = $name;

		$mini_manager = new \Core\MiniManager($options);
		$mini_manager->draw();
	}

	public function load_view($view_url, $vars=null) 
	{	
		if (is_array($vars)) {
			extract($vars); 
		}

		ob_start(); 
		require($view_url);
		ob_end_flush();
	}

	public function render($vars=array()) {

		if ($this->loading_widget) {

			$url = PREFIX . $this->request->zone . DS . 'views' .DS. strtolower($this->request->controller) . DS . $this->loading_widget . '.phtml';
			$this->load_view($url, $vars);
			$this->loading_widget = false;
			return;
		}

		if ($this->loading_layout) {
			
			$url = PREFIX . $this->request->zone . DS . 'views' . DS . $this->page->layout . '.phtml';
			$this->load_view($url, $vars);
			$this->loading_layout = false;
			return;
		}

		switch ($this->request->type_action) {

			case 'page':

				$this->compile_params();
				$this->page->vars = $vars;

				if($this->rendered) { 
					return false; 
				}
				$this->load_view($this->page->html_base, null);
				$this->rendered = true; 
				break;

			case 'widget':
				
				$url = PREFIX. $this->request->zone .DS. 'views' .DS. strtolower($this->request->controller) .DS. 'widget_' . $this->request->action . '.phtml';
				$this->load_view($url, $vars);
				echo $this->session->flash_message();
				break;

			case 'json':

				header('Content-Type: application/json');
		  		echo json_encode($vars);
		  		exit();
				break;

			default:
				$url = PREFIX. $this->request->zone .DS. 'views' .DS. strtolower($this->request->controller) .DS. $this->request->action . '.phtml';
				$this->load_view($url, $vars);
				break;
		}
	}

	/* -------------------- FUNCTIONS AJAX --------------------  */

	public function ajax_success($msg) {
		exit('{"success":1, "msg":"'.$msg.'"}');
	}
	public function ajax_error($msg) {
		Service::header_code('500');
		exit($msg);
	}

	/* -------------------- PAGES PREDEFINIES --------------------  */


	public function json_mini_manager()
	{
		// Véirfier l'existance de $this->{'mini_manager_'.$name}();

		$name = $this->request->fromRoute('name', null);
		$action = $this->request->fromRoute('action', null);
		$id = $this->request->fromRoute('id', null);
		
		$options = $this->{'mini_manager_'.$name}();
		$options['primary_key'] = array_key_exists('primary_key', $options) ? $options['primary_key'] : 'id' ;

		if (array_key_exists('permission', $options)) {
			if (!$this->permission($options['permission'])) {
				Service::redirectError(403);
			}
		}

		if ($action == 'delete') {

			$this->db->delete($options['table'])->id($id, $options['primary_key'])->execute();

		} else if ($this->request->isPost()) {
			
			$post = $this->request->post;
			$values = array();

			foreach($options['fields'] as $key => $params) {
				$values[$key] = $post->{$key};
			}

			if ($action == 'add') {
				$id = $this->db->insert($options['table'])->values($values)->execute();

			} else if ($action == 'edit') {
				$this->db->update($options['table'])->values($values)->id($id, $options['primary_key'])->execute();
			}

			$this->render(array_merge(array(
				'id' => $id,
			),$values));
		}

		$this->render(array(
			'id' => $id,
		));
	}



	function widget_autoform() 
	{
		/*
			A TRAVAILLER, ESSAYER DE PASSER UN MAXIMUM D'INFO DANS LE MODEL 
			OU BIEN REFLECHIR A UN SYSTEME DE HOOK A LA WORDPRESS QUI PERMET D'INCRUSTER DU CODE AUX ENDROITS VOULU
			OU A UNE CLASSE AUTOFORM QU ON PUISSE MANIPULER AISEMENT DANS EL CONTROLLER
		*/
		$id = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');
		$table = $this->request->fromRoute('table', null);
		$sql = ''; // << trouver une solution pour passer une requête
		$fields_to_show = array(
			'name' => 'Nom',
			'rank' => 'Rang',
			//...
		);
		$identifiant_url = $this->request->zone.'-widget-autoform';


		if ($id && $table) {
			$object = $this->db->select($table)->id($id)->execute();
		}

		switch ($action) {
			
			case 'list':

				$page = $this->request->fromRoute('page', 1);

				$table = new Table('objects_list');
				$table->sql(
					"SELECT u.*, r.name as rank, a.name as agency, COUNT(DISTINCT c.id) as count_clients 
					FROM _users u LEFT JOIN clients c ON c.id_user_create=u.id LEFT JOIN _ranks r ON u.id_rank = r.id LEFT JOIN agency a ON a.id = u.id_agency 
					WHERE u.deleted='0'
					GROUP BY u.id
					", 'id');
				
				$table->entity_id('id');
				$table->add_col('name', 'Nom d\'utilisateur')->filter('bold');
				$table->add_col('rank', 'Rang');
				$table->add_col('agency', 'Agence')->attr('align', 'left');
				$table->add_col('date_create', 'Date de création')->filter('date', 'd F Y');
				$table->add_col('date_connexion', 'Dernière connexion')->filter('date', 'd F Y');
				$table->add_col('', 'Modifier')->url(array(
					'href' => '#',
					'data-ajax' => $this->url($identifiant_url, array('action'=>'form')).'/[ID]',
					//'data-ajax-modal' => $this->url('front-widget-users', array('action'=>'form')).'/[ID]',
					//'data-modal-title' => 'Test',
					//'data-modal-width' => '700px',
					'data-transition' => 'slide-left',
					'icon' => $this->icon('pencil', '', 'black'),
				));

				$table->pager(array(
					'page' => $page,
					'url' => $this->url($identifiant_url, array('action' => 'list')) .'/page/[PAGE]',
					//'count' => $this->db->query_count('SELECT * FROM _users'),
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
				));

				/*
				$this->render(array(
					'table' => $table,
		            'action' => $action,
		        ));
		        */
				echo $table->draw();
				break;


			case 'form':

				$form = new Form($table, $id);
				$form->id('object-form')->action($this->url($identifiant_url, array('id'=> $id, 'action' => 'form')))->ajax('slide-right');
				$form->factorize();
				//$form->get('id_rank')->options_table('_ranks', 'id', 'name');
				//$form->get('id_agency')->options_table('agency', 'id', 'name');
				$form->add_submit('Enregistrer');

				if ($this->request->isPost()) {
			
					$form->bind($this->request->post);
					
					if ($form->validate()) {

						$object = $form->get_entity();

						if ($object->save()) {
							//$form->reset();
							$this->session->flash('L\'utilisateur a bien été enregistré', 'success', true);
							Service::redirect($this->url($identifiant_url, array('action'=>'list')));

						} else {
							$this->session->flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						$this->session->flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_user) {
				
					$form->bind($user->get_data());
					$form->get('email_confirmation')->value = $form->get('email')->value;
				}

				/*
		        $this->render(array(
		            'form' => $form,
		            'action' => $action,
		        ));
		        */
				echo $form->draw();
				break;


			case 'delete':

				$object->delete();
				$this->session->flash('L\'enregistrement a bien été supprimé', 'warning', true);
				Service::redirect($this->url($identifiant_url, array('action'=>'list')));
				break;
		}

		ob_start(); 
		
		ob_end_flush();
	}

	public function page_login()
	{
        $this->set_layout('layout_login');
        $this->set_title('Login');

		$model = array(
			'connexion' => array(
				'type' => 'HIDDEN',
				'value' => 1,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'max' => 75,
				'placeholder' => translate('Utilisateur'),
				'required' => true,
			),
			'password' => array(
				'type' => 'PASSWORD',
				'max' => 75,
				'placeholder' => translate('Password'),
				'required' => true,
			),
		);

		$form = new Form($model);
		$form->set_template('table');
		$form->id('login-form');
		$form->factorize();
		$form->add_submit('Connexion');
		$form->js_validation(false);

        $this->render(array(
            'form' => $form,
        ));
	}

	public function page_superadmin()
	{

        $this->render(array(
           
        ));
	}

	/* -------------------- LESS COMPILATION --------------------  */
	
	public function less_compile ($compressed=true, $preserve_comments=false, $force_compilation=true) 
	{
		include_once('/Core/LessPHP/lessc.inc.php');
		
		$less = new \lessc;
		$less->setPreserveComments($preserve_comments);

		if ($compressed) {
			$less->setFormatter("compressed");
		}

		$files = _get('config.less', array());

		foreach ($files as $file) {

			$url = $this->request->zone . '/css/' . $file ;

			if ($force_compilation) {
				$less->compileFile($url, str_replace('.less', '.css', $url));
			} else {
				$less->checkedCompile($url, str_replace('.less', '.css', $url));
			}
		}
	}
	public function less_autocompile () {
		
		$autocompile = _get('config.less_params.autocompile', false);

		if ($autocompile) {
			$compressed = _get('config.less_params.compressed', true);
			$preserve_comments = _get('config.less_params.preserve_comments', false);
			$this->less_compile ($compressed, $preserve_comments, false);
		}
	}
}
?>