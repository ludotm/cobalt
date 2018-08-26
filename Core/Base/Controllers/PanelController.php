<?php 

namespace Core\Base\Controllers;

use Core\Page;
use Core\Service;
use Core\Menu;
use Core\Table;
use Core\Form;

class PanelController extends BaseController
{	 
	public function onDispatch() 
	{
		if ($this->session->get('id_rank') != 1 && $this->request->action != 'login') {
			Service::redirect('/panel/login');
			exit();
		} else if ($this->session->get('id_rank') == 1 && $this->request->action == 'login') {
			Service::redirect('/panel/clients');
			exit();
		}

		if (isset($this->request->get->logout_panel)) {
			$this->session->restart_session();
			Service::redirect('/panel/login');
		}

		$this->set_layout('layout_panel');
		$this->set_base_title('Framework Panel - ');
		$this->add_stylesheet('panel.css');
	}

	public function menu_panel() 
	{
		$menu = new Menu('menu-panel');

		//$menu->add_item()->label('Dashboard')->url('/panel/home');
		$menu->add_item()->label('Clients')->url('/panel/clients')->attr('class', ($this->request->action == 'clients' ? 'active':''));
		//$menu->add_item()->label('Bornes wifi')->url('/panel/hotspots');
		//$menu->add_item()->label('APIs')->url('/panel/apis');
		//$menu->add_item()->label('Réseaux sociaux')->url('/panel/reseaux');
		//$menu->add_item()->label('Config')->url('/panel/config');
		$menu->add_item()->label('Crons')->url('/panel/crons')->attr('class', ($this->request->action == 'crons' ? 'active':''));
		$menu->add_item()->label('Logs')->url('/panel/logs')->attr('class', ($this->request->action == 'logs' ? 'active':''));
		if (IS_LOCAL) {
			$menu->add_item()->label('Créer zone')->url('/panel/zone')->attr('class', ($this->request->action == 'zone' ? 'active':''));
			$menu->add_item()->label('Créer page')->url('/panel/page')->attr('class', ($this->request->action == 'page' ? 'active':''));
			$menu->add_item()->label('Créer widget')->url('/panel/widget')->attr('class', ($this->request->action == 'widget' ? 'active':''));
		}
		$menu->add_item()->label('PHP infos')->url('/panel/phpinfos')->attr('class', ($this->request->action == 'phpinfos' ? 'active':''));
		$menu->add_item()->label('<i class="fa fa-power-off"></i> logout')->url('/panel?logout_panel=1');

		return $menu;
	}

	public function page_home() 
	{
		$title = "Dashboard";
		$this->set_title($title);
		
		$this->render(array(
			'title' => $title,
        ));
	}

	public function page_clients() 
	{
		$url = '/panel/clients';

		$this->set_title('Gestion des clients');
		
		$id_client = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		}

		switch ($action) {
			
			case 'view':

				if (!$id_client) {
					Service::error('Paramètre manquant, id du client attendue.');
				}

				$this->render(array(
					'url' => $url,
		            'client' => $client,
		            'action' => $action,
		        ));
				break;

			case 'list':

				$callback_access = function ($access_admin, $object) use ($url) {
					return '<a href="'.$url.'?action=access_admin&value='.($access_admin?'0':'1').'&id='.$object->id.'"><i class="fa fa-'.($access_admin?'check-':'').'square-o"> </i></a>';
				};
				$callback_phone = function ($value, $object)  {
					return $object->mobile != '' ? $object->mobile : $object->phone ;
				};

				$sql = "SELECT * FROM _big_users WHERE deleted='0'";

				$page = $this->request->fromRoute('page', 1);

				$table = new Table('clients_list');
				$table->sql($sql, 'id');
				$table->add_col('id', 'ID');
				$table->add_col('name', 'Client')->filter('bold');
				$table->add_col('contact_name', 'Contact');
				$table->add_col('email', 'Email');
				$table->add_col('phone', 'Téléphone')->callback($callback_phone);
				$table->add_col('date_create', 'Date de création')->filter('date', 'd F Y')->filter('italic');

				$table->add_col('access_admin', 'Accès admin')->callback($callback_access);

				$table->add_col('', 'Créer admin')->url(array( 
					'href' => $url . '?action=create_admin&id=[ID]',
					'icon' => $this->icon('user', '', 'black'),
				));

				$table->add_col('', 'Fiche')->url(array( 
					'href' => $url . '?action=view&id=[ID]',
					'icon' => $this->icon('file-text', '', 'black'),
				));
				$table->add_col('', 'Modif.')->url(array(
					'href' => $url . '?action=form&id=[ID]',
					'icon' => $this->icon('pencil', '', 'black'),
				));
				$table->add_col('', 'Suppr.')->url(array(
					'href' => $url . '?action=delete&id=[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer ce client ?',
					'icon' => $this->icon('remove', '', 'black'),
				));
				$table->add_col('', 'Connexion à l\'admin')->url(array(
					'href' => $url . '?action=redirect_admin_client&id=[ID]',
					'icon' => $this->icon('arrow-right', '', 'black'),
				));

				
				$table->pager(array(
					'page' => $page,
					'url' => $url . '?action=list&page=[PAGE]',
					'items_per_page' => 20,
					'align' => 'left',
				));

				$this->render(array(
					'url' => $url,
					'table' => $table,
		            'action' => $action,
		        ));
				break;
			
			case 'redirect_admin_client':

				$admin_url = $this->url(_get('config.admin_route'), null);

				if ($id_client && $admin_url) {
					$this->session->set('id_big_user', $id_client);
					Service::redirect($admin_url);
				} else {
					Service::error('Redirection vers l\'admin impossible, ID ou url manquantes.');
				}

			case 'access_admin':

				$value = $this->request->fromRoute('value');
				$client->access_admin = $value;
				$client->save();

				Service::flash('Les acc&egrave;s client ont bien &eacute;t&eacute; modifi&eacute;', 'warning', true);
				Service::redirect($url . '?action=list');
				break;

			case 'form':

				$form = new Form('_big_users', $id_client);
				$form->id('')->action($url . '?action=form&id='.$id_client);
				$form->factorize();
				$form->add_submit('Enregistrer');

				$form->attr('class', 'form-horizontal', false);
				$form->set_template(array(
					'header' => '<div class="row">'."\n\t",
					'body' => '<div class="form-group"><label for="prename" class="col-sm-3 control-label">[label]</label><div class="col-sm-9">[element]</div></div>'."\n\t",
					'footer' => '</div>'."\n\t"
				));



				if ($this->request->isPost()) {

					$form->bind($this->request->post);
					
					if ($form->validate()) {

						$client = $form->get_entity();

						$is_add = !$client->id ? true : false; 

						if ($id_client = $client->save()) {

							Service::flash('Le client a bien été enregistré', 'success', true);
							Service::redirect($url . '?action=list');

						} else {
							Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_client) {
				
					$form->bind($client->get_data());
				}

		        $this->render(array(
		        	'url' => $url,
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'create_admin':

				$model = array(
					'username' => array(
						'type' => 'VARCHAR',
						'max' => 75,
						'placeholder' => 'Login',
						'required' => true,
						'remote_validation' => 'Ce nom d\'utilisateur existe déjà',
					),
					'password' => array(
						'type' => 'PASSWORD',
						'max' => 75,
						'placeholder' => 'Password',
						'required' => true,
					),
				);

				$form = new Form('_users', 0);
				$form->setModel ($model);
				$form->id('')->action($url . '?action=create_admin&id='.$id_client);
				$form->factorize();
				$form->add_submit('Enregistrer');

				$form->attr('class', 'form-horizontal', false);
				$form->set_template(array(
					'header' => '<div class="row">'."\n\t",
					'body' => '<div class="form-group"><label for="prename" class="col-sm-3 control-label">[label]</label><div class="col-sm-9">[element]</div></div>'."\n\t",
					'footer' => '</div>'."\n\t"
				));

				if ($this->request->isPost()) {

					$post = $this->request->post;
					$form->bind($post);
					
					if ($form->validate()) {

						$crypto = Service::Crypto();
						$password = $crypto->hash($post->password);

						$result = $this->db->insert('_users')->values(array('id_big_user'=> $id_client, 'name' => 'Admin', 'email' => '', 'id_rank' => '2', 'username' => $post->username, 'password' => $password, 'date_create' => date('Y-m-d')))->execute();

						if ($result) {

							Service::flash('Le compte admin a bien été enregistré', 'success', true);
							Service::redirect($url . '?action=list');

						} else {
							Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}
		        } 

		        $this->render(array(
		        	'url' => $url,
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'delete':

				$name = $client->name;
				$client->delete();
				Service::flash('Le client '.$name.' a bien été supprimé', 'warning', true);
				Service::redirect($url . '?action=list');
				break;
		}
	}


	public function page_hotspots() 
	{
		$url = '/panel/hotspots';

		$this->set_title('Gestion des bornes wifi');
		
		$id_hotspot = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');

		if ($id_hotspot) {
			$hotspot = $this->db->select('_hotspots')->id($id_hotspot)->execute();
		}

		switch ($action) {
			
			case 'view':

				if (!$id_hotspot) {
					Service::error('Paramètre manquant, id de la borne attendue.');
				}

				$this->render(array(
					'url' => $url,
		            'hotspot' => $hotspot,
		            'action' => $action,
		        ));
				break;

			case 'list':

				$callback_url_data = function ($value, $object) {
					
					$url = 'http://'.DOMAIN.'/meraki-remote/'.$object->id;
					return $url. ' (<a href="#" data-modal="" data-test="'.$url.'" data-mac="'.$object->mac.'" data-secret="'.$object->secret.'">Tester l\'url</a>)';
				};

				$callback_url_meraki = function ($value, $object) {
					
					return '<a href="https://n145.meraki.com/'.$object->id_meraki.'/n/xshhHarc/manage/" target="_blank"><i class="fa fa-arrow-right" style="color:black;"></i></a>';
				};

				$sql = "SELECT b.name, h.* FROM _hotspots h LEFT JOIN _big_users b ON h.id_big_user=b.id ORDER BY b.name";

				$page = $this->request->fromRoute('page', 1);

				$table = new Table('hotspots_list');
				$table->sql($sql, 'id');
				$table->add_col('id', 'ID');
				$table->add_col('name', 'Client')->filter('bold');
				$table->add_col('mac', 'Adresse mac');
				$table->add_col('id_meraki', 'ID meraki');
				//$table->add_col('validator', 'Validateur');
				$table->add_col('secret', 'Secret');
				$table->add_col('', 'Url d\'envoi des données')->callback($callback_url_data);

				$table->add_col('', 'Sync.')->url(array( 
					'href' => $url . '?action=sync&id=[ID]',
					'icon' => $this->icon('refresh', '', 'black'),
				));
				$table->add_col('', 'Modif.')->url(array(
					'href' => $url . '?action=form&id=[ID]',
					'icon' => $this->icon('pencil', '', 'black'),
				));
				$table->add_col('', 'Suppr.')->url(array(
					'href' => $url . '?action=delete&id=[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer cette borne ?',
					'icon' => $this->icon('remove', '', 'black'),
				));
				$table->add_col('', 'Admin meraki')->callback($callback_url_meraki);

				
				$table->pager(array(
					'page' => $page,
					'url' => $url . '?action=list&page=[PAGE]',
					'items_per_page' => 20,
					'align' => 'left',
				));

				$this->render(array(
					'url' => $url,
					'table' => $table,
		            'action' => $action,
		        ));
				break;

			case 'form':

				$form = new Form('_hotspots', $id_hotspot);
				$form->id('')->action($url . '?action=form&id='.$id_hotspot);
				$form->factorize();
				$form->add_submit('Enregistrer');

				$form->get('id_big_user')->options_table('_big_users','id','name');

				$form->attr('class', 'form-horizontal', false);
				$form->set_template(array(
					'header' => '<div class="row">'."\n\t",
					'body' => '<div class="form-group"><label for="prename" class="col-sm-3 control-label">[label]</label><div class="col-sm-9">[element]</div></div>'."\n\t",
					'footer' => '</div>'."\n\t"
				));

				if ($this->request->isPost()) {

					$form->bind($this->request->post);
					
					if ($form->validate()) {

						$hotspot = $form->get_entity();

						$is_add = !$hotspot->id ? true : false; 

						if ($id_hotspot = $hotspot->save()) {

							Service::flash('La borne a bien été enregistrée', 'success', true);
							Service::redirect($url . '?action=list');

						} else {
							Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_hotspot) {
				
					$form->bind($hotspot->get_data());
				}

		        $this->render(array(
		        	'url' => $url,
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'delete':

				$mac = $hotspot->mac;
				$hotspot->delete();
				Service::flash('La borne '.$mac.' a bien été supprimée', 'warning', true);
				Service::redirect($url . '?action=list');
				break;

			case 'sync':

				$hotspot->synchronisation_time = time()+300;
				$hotspot->save();
				Service::flash('La borne '.$hotspot->mac.' est ouverte à la synchronisation pour 5 minutes', 'warning', true);
				Service::redirect($url . '?action=list');
				break;
		}
	}

	public function page_apis() 
	{
		$url = '/panel/apis';

		$this->set_title('Gestion des APIs');

		$id_app = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');

		if ($id_hotspot) {
			$hotspot = $this->db->select('_hotspots')->id($id_hotspot)->execute();
		}

		switch ($action) {
			
			case 'view':

				if (!$id_hotspot) {
					Service::error('Paramètre manquant, id de la borne attendue.');
				}

				$this->render(array(
					'url' => $url,
		            'hotspot' => $hotspot,
		            'action' => $action,
		        ));
				break;

			case 'list':

				$callback_url_data = function ($value, $object) {
					
					$url = 'http://'.DOMAIN.'/meraki-remote/'.$object->id;
					return $url. ' (<a href="#" data-modal="" data-test="'.$url.'" data-mac="'.$object->mac.'" data-secret="'.$object->secret.'">Tester l\'url</a>)';
				};

				$callback_url_meraki = function ($value, $object) {
					
					return '<a href="https://n145.meraki.com/'.$object->id_meraki.'/n/xshhHarc/manage/" target="_blank"><i class="fa fa-arrow-right" style="color:black;"></i></a>';
				};

				$sql = "SELECT b.name, h.* FROM _hotspots h LEFT JOIN _big_users b ON h.id_big_user=b.id ORDER BY b.name";

				$page = $this->request->fromRoute('page', 1);

				$table = new Table('hotspots_list');
				$table->sql($sql, 'id');
				$table->add_col('id', 'ID');
				$table->add_col('name', 'Client')->filter('bold');
				$table->add_col('mac', 'Adresse mac');
				$table->add_col('id_meraki', 'ID meraki');
				//$table->add_col('validator', 'Validateur');
				$table->add_col('secret', 'Secret');
				$table->add_col('', 'Url d\'envoi des données')->callback($callback_url_data);

				$table->add_col('', 'Sync.')->url(array( 
					'href' => $url . '?action=sync&id=[ID]',
					'icon' => $this->icon('refresh', '', 'black'),
				));
				$table->add_col('', 'Modif.')->url(array(
					'href' => $url . '?action=form&id=[ID]',
					'icon' => $this->icon('pencil', '', 'black'),
				));
				$table->add_col('', 'Suppr.')->url(array(
					'href' => $url . '?action=delete&id=[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer cette borne ?',
					'icon' => $this->icon('remove', '', 'black'),
				));
				$table->add_col('', 'Admin meraki')->callback($callback_url_meraki);

				
				$table->pager(array(
					'page' => $page,
					'url' => $url . '?action=list&page=[PAGE]',
					'items_per_page' => 20,
					'align' => 'left',
				));

				$this->render(array(
					'url' => $url,
					'table' => $table,
		            'action' => $action,
		        ));
				break;

			case 'form':

				$form = new Form('_hotspots', $id_hotspot);
				$form->id('')->action($url . '?action=form&id='.$id_hotspot);
				$form->factorize();
				$form->add_submit('Enregistrer');

				$form->get('id_big_user')->options_table('_big_users','id','name');

				$form->attr('class', 'form-horizontal', false);
				$form->set_template(array(
					'header' => '<div class="row">'."\n\t",
					'body' => '<div class="form-group"><label for="prename" class="col-sm-3 control-label">[label]</label><div class="col-sm-9">[element]</div></div>'."\n\t",
					'footer' => '</div>'."\n\t"
				));

				if ($this->request->isPost()) {

					$form->bind($this->request->post);
					
					if ($form->validate()) {

						$hotspot = $form->get_entity();

						$is_add = !$hotspot->id ? true : false; 

						if ($id_hotspot = $hotspot->save()) {

							Service::flash('La borne a bien été enregistrée', 'success', true);
							Service::redirect($url . '?action=list');

						} else {
							Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_hotspot) {
				
					$form->bind($hotspot->get_data());
				}

		        $this->render(array(
		        	'url' => $url,
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'delete':

				$mac = $hotspot->mac;
				$hotspot->delete();
				Service::flash('La borne '.$mac.' a bien été supprimée', 'warning', true);
				Service::redirect($url . '?action=list');
				break;

			case 'sync':

				$hotspot->synchronisation_time = time()+300;
				$hotspot->save();
				Service::flash('La borne '.$hotspot->mac.' est ouverte à la synchronisation pour 5 minutes', 'warning', true);
				Service::redirect($url . '?action=list');
				break;
		}
	}

	public function page_scripts() 
	{
		$title = "Executer un script";
		$this->set_title($title);
		
		$dir    = ROOT.'/Cron';
		$scripts = array_diff(scandir($dir), array('..', '.'));

		if ($this->request->isPost()) {

			$selected_script = $this->request->post->selected_script;
			$this->console_line = 1;
			$variables = explode('&', $this->request->post->vars);
			$vars = array();

			foreach ($variables as $variable) {
				if ($variable != '') {
					$temp = explode('=', $variable);
					$vars[$temp[0]] = $temp[1];
				}
			}
			extract($vars);

			echo "<style>body{font-family:verdana; color:#f6f6e7; font-size:14px;}.row{margin-bottom:5px;}</style>";
			
			if ($selected_script == '') {
				$this->log('Aucun script séléctionné');
				exit();	
			}

			require_once($dir.'/'.$selected_script);
			exit();	
		}

		$this->render(array(
			'title' => $title,
			'scripts' => $scripts,
        ));
	}
	public function log($txt, $color="") 
	{
		echo '<div class="row" '.($color!=''?'style="color:'.$color.';"':'').'><div style="float:left; width:30px; color:gray;">'.($this->console_line <10?'0'.$this->console_line:$this->console_line).'. </div>'.$txt.'</div>';
		$this->console_line++;
	}

	public function page_reseaux() 
	{
		$title = "Gestion des réseaux sociaux";
		$this->set_title($title);
		
		$this->render(array(
			'title' => $title,
        ));
	}

	public function page_config() 
	{
		$title = "Configuration";
		$this->set_title($title);
		
		$this->render(array(
			'title' => $title,
        ));
	}

	public function page_crons() 
	{
		$title = "Crons";
		$this->set_title($title);
		
		$callback_status = function ($value, $object)  {
			return '<span class="'.(!$value ? 'text-red':'').'">'.($value ? 'Executé' : 'Erreur').'</span>';
		};

		$sql = "SELECT * FROM _crons ORDER BY timestamp DESC LIMIT 100";

		$table = new Table('crons_list');
		$table->sql($sql, 'id');
		$table->add_col('id', 'ID');
		$table->add_col('type', 'Type')->filter('bold');
		$table->add_col('timestamp', 'date')->filter('date', 'd F Y H:i:s')->filter('italic');
		$table->add_col('status', 'Statut')->callback($callback_status);
		$table->add_col('ip', 'IP');

		$this->render(array(
			'table' => $table,
            'title' => $title,
        ));
	}

	public function page_logs() 
	{
		$title = "Logs";
		$this->set_title($title);
		
		$path = _config('error_logs_file');
		$ptr = fopen($path, "r");
		$content = explode(PHP_EOL, fread($ptr, filesize($path)));

		$this->render(array(
            'title' => $title,
            'content' => $content,
        ));
	}

	public function page_install() 
	{	
		$title = "Installation du Framework";
		$this->set_title($title);
		
		$this->render(array(
			'title' => $title,
        ));
	}


	public function page_phpinfos() 
	{
		$title = "PHP infos";
		$this->set_title($title);
		
		$this->render(array(
			'title' => $title,
        ));
	}

	public function widget_phpinfos() 
	{
		phpinfo();
		exit();
	}














	public function page_zone() 
	{
		$title = "Créer une nouvelle zone";
		$this->set_title($title);
		
		$template_dir = scandir(ROOT.'/plugins/cobalt/templates/');
		$templates = array();
		foreach ($template_dir as $key => $value) {
			if (strpos($value, 'less') !== false && strpos($value, 'config') === false) {
				$value = str_replace('.less', '', $value);
 				$templates[$value] = $value;
			}
		}
		$model = array(
			'zone_name' => array(
				'type' => 'VARCHAR',
				'label' => 'Nom de la zone',
				'placeholder' => 'Nom de la zone',
			),
			'controller_name' => array(
				'type' => 'VARCHAR',
				'label' => 'Nom du controlleur principal',
				'placeholder' => 'Nom du controlleur principal',
			),
			'zone_title' => array(
				'type' => 'VARCHAR',
				'label' => 'Titre HTML des pages',
				'placeholder' => 'Titre HTML',
			),

			'template' => array(
				'type' => 'SELECT',
				'label' => 'template CSS',
				'placeholder' => 'template CSS',
				'options' => $templates,
			),
			'is_secure_zone' => array(
				'type' => 'RADIO',
				'label' => 'Zone sécurisé?',
				'options' => array(
					0 => 'Non',
					1 => 'Oui',
				),
			),
			'logo' => array(
				'type' => 'RADIO',
				'label' => 'Logo',
				'options' => array(
					0 => 'Non',
					1 => 'Oui',
				),
			),
			'menus' => array(
				'type' => 'CHECKBOX',
				'label' => 'Menus',
				'options' => array(
					'side' => 'Menu Side',
					'top' => 'Menu Top',
					'footer' => 'Menu footer',
				),
			),
		);

		$form = new Form($model);
		$form->id('')->action('/panel/zone');
		$form->factorize();
		$form->add_submit('Enregistrer');
/*
		$form->set_template(array(
			'header' => '<div class="row">'."\n\t",
			'body' => '<div class="form-group"><label for="prename" class="col-sm-3 control-label">[label]</label><div class="col-sm-9">[element]</div></div>'."\n\t",
			'footer' => '</div>'."\n\t"
		));
*/
		$new_route = null;
		
		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			$zone_name = ucfirst(strtolower($this->request->post->zone_name)); 
			$controller_name = ucfirst(strtolower($this->request->post->controller_name)); 
			$zone_title = $this->request->post->zone_title; 
			$template = $this->request->post->template; 
			$template = $template == '' ? null : $template;
			$is_secure_zone = $this->request->post->is_secure_zone; 
			$logo = $this->request->post->logo; 

			$this->request->post->menus = !isset($this->request->post->menus) ? array() : $this->request->post->menus;
			$menus = array(
				'side' => (in_array('side', $this->request->post->menus) ? true : false),
				'top' => (in_array('top', $this->request->post->menus) ? true : false),
				'footer' => (in_array('footer', $this->request->post->menus) ? true : false),
			);

			$result = $this->create_zone($zone_name, $controller_name, $zone_title, $template, $is_secure_zone, $logo, $menus);

			ob_start(); ?>
			<?php if ($is_secure_zone) : ?>
			'<?= strtolower($zone_name) ?>-page-login' => array(
                'route' => '/<?= strtolower($zone_name) ?>/login',
                'controller' => 'Base',
            ),
        	<?php endif; ?>
			'<?= strtolower($zone_name) ?>-page-<?= strtolower($controller_name) ?>' => array(
                'route' => '/<?= strtolower($zone_name) ?>/<?= strtolower($controller_name) ?>',
                'controller' => '<?= $controller_name ?>',
            ),
            '<?= strtolower($zone_name) ?>-widget-<?= strtolower($controller_name) ?>' => array(
                'route' => '/<?= strtolower($zone_name) ?>/widget-<?= strtolower($controller_name) ?>',
                'controller' => '<?= $controller_name ?>',
            ),

			<?php 
			$new_route = ob_get_contents();
			ob_end_clean();

			if ($result) {
				Service::flash('La zone a été créée avec succès', 'success', true);
			} else {
				Service::flash('Erreur(s) lors de la création de la zone', 'success', true);
			}
		}

        $this->render(array(
            'form' => $form,
            'title' => $title,
            'new_route' => $new_route,
        ));
	}

	public function page_page() 
	{
		$title = "Créer une nouvelle page";
		$this->set_title($title);
		
		$root = scandir(ROOT);
		$options = array();
		$zones = array();
		foreach($root as $elem) {
			if (strpos($elem, '.') === false && !in_array($elem, array('config', 'Core', 'plugins', 'files'))) {
				$zones []= $elem;
			}
		}

		foreach($zones as $zone) {
			$zone_dir = scandir(ROOT.'/'.$zone.'/Controllers/');
			foreach ($zone_dir as $controller) {
				$controller = str_replace('.php','',$controller);
				if ($controller != '.' && $controller != '..') {
					$options[$zone.'_'.$controller] = $zone.' > '.$controller;
				}
			}
		}


		$model = array(
			'page_name' => array(
				'type' => 'VARCHAR',
				'label' => 'Nom de la page',
				'placeholder' => 'Nom de la page',
			),
			'zone_controller' => array(
				'type' => 'SELECT',
				'label' => 'Controlleur et zone',
				'options' => $options,
			),
		);

		$form = new Form($model);
		$form->id('')->action('/panel/page');
		$form->factorize();
		$form->add_submit('Enregistrer');

		$new_route = $new_function = null;
		
		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			$page_name = str_replace(' ', '_', strtolower($this->request->post->page_name));
			$page_name = str_replace('-', '_', $page_name);
			$zone_controller = $this->request->post->zone_controller;
			$zone_controller = explode('_', $zone_controller);
			$zone = $zone_controller[0];
			$controller = str_replace('Controller', '', $zone_controller[1]);

			$result = $this->create_page_widget($zone, $controller, $page_name);

			ob_start(); ?>

			'<?= strtolower($zone) ?>-page-<?= strtolower($page_name) ?>' => array(
                'route' => '/<?= strtolower($zone) ?>/<?= str_replace('_','-', $page_name) ?>',
                'controller' => '<?= $controller ?>',
            ),
            '<?= strtolower($zone) ?>-widget-<?= strtolower($page_name) ?>' => array(
                'route' => '/<?= strtolower($zone) ?>/widget-<?= str_replace('_','-', $page_name) ?>',
                'controller' => '<?= $controller ?>',
            ),

			<?php 
			$new_route = ob_get_contents();
			ob_end_clean();

			ob_start(); ?>

			function page_<?= $page_name ?> () {

				$this->render(array(

				));
			}

			function widget_<?= $page_name ?> () {

				$this->render(array(

				));
			}

			<?php 
			$new_function = ob_get_contents();
			ob_end_clean();

			if ($result) {
				Service::flash('La page a été créée avec succès', 'success', true);
			} else {
				Service::flash('Erreur(s) lors de la création de la page', 'success', true);
			}
		}

        $this->render(array(
            'form' => $form,
            'title' => $title,
            'new_route' => $new_route,
            'new_function' => $new_function,
        ));
	}

	public function page_widget() 
	{
		$title = "Créer un nouveau widget";
		$this->set_title($title);
		
		$root = scandir(ROOT);
		$options = array();
		$zones = array();
		foreach($root as $elem) {
			if (strpos($elem, '.') === false && !in_array($elem, array('config', 'Core', 'plugins', 'files'))) {
				$zones []= $elem;
			}
		}

		foreach($zones as $zone) {
			$zone_dir = scandir(ROOT.'/'.$zone.'/Controllers/');
			foreach ($zone_dir as $controller) {
				$controller = str_replace('.php','',$controller);
				if ($controller != '.' && $controller != '..') {
					$options[$zone.'_'.$controller] = $zone.' > '.$controller;
				}
			}
		}


		$model = array(
			'page_name' => array(
				'type' => 'VARCHAR',
				'label' => 'Nom du widget',
				'placeholder' => 'Nom du widget',
			),
			'zone_controller' => array(
				'type' => 'SELECT',
				'label' => 'Controlleur et zone',
				'options' => $options,
			),
		);

		$form = new Form($model);
		$form->id('')->action('/panel/widget');
		$form->factorize();
		$form->add_submit('Enregistrer');

		$new_route = $new_function = null;
		
		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			$page_name = str_replace(' ', '_', strtolower($this->request->post->page_name));
			$page_name = str_replace('-', '_', $page_name);
			$zone_controller = $this->request->post->zone_controller;
			$zone_controller = explode('_', $zone_controller);
			$zone = $zone_controller[0];
			$controller = str_replace('Controller', '', $zone_controller[1]);

			$result = $this->create_widget($zone, $controller, $page_name);

			ob_start(); ?>

            '<?= strtolower($zone) ?>-widget-<?= strtolower($page_name) ?>' => array(
                'route' => '/<?= strtolower($zone) ?>/<?= str_replace('_','-', $page_name) ?>',
                'controller' => '<?= $controller ?>',
            ),

			<?php 
			$new_route = ob_get_contents();
			ob_end_clean();

			ob_start(); ?>

			function widget_<?= $page_name ?> () {

				$this->render(array(

				));
			}

			<?php 
			$new_function = ob_get_contents();
			ob_end_clean();

			if ($result) {
				Service::flash('Le widget a été créée avec succès', 'success', true);
			} else {
				Service::flash('Erreur(s) lors de la création du widget', 'success', true);
			}
		}

        $this->render(array(
            'form' => $form,
            'title' => $title,
            'new_route' => $new_route,
            'new_function' => $new_function,
        ));
	}












	/* ------------------------------------- CREATION ZONE PAGE WIDGET ------------------------------------------- */


	protected function create_zone($zone_name, $controller_name, $zone_title, $template, $is_secure_zone, $logo, $menus) 
	{
		$success = array();

		$zone_name = ucfirst($zone_name);

		mkdir($zone_name.'/Classes', 755, true);
		mkdir($zone_name.'/Controllers', 755, true);
		mkdir($zone_name.'/css', 755, true);
		mkdir($zone_name.'/forms', 755, true);
		mkdir($zone_name.'/img', 755, true);
		mkdir($zone_name.'/js', 755, true);
		mkdir($zone_name.'/views', 755, true);
		mkdir($zone_name.'/views/base', 755, true);
		mkdir($zone_name.'/views/'.strtolower($controller_name), 755, true);
		copy ('Core/Base/img/favicon.png', $zone_name.'/img/favicon.png');
		copy ('Core/Base/img/logo.png', $zone_name.'/img/logo.png');

		$success []= $this->create_base_controller($zone_name, $menus);
		$success []= $this->create_controller($zone_name, $controller_name); 
		$success []= $this->create_less($zone_name, $template); 
		$success []= $this->create_js($zone_name); 
		$success []= $this->create_layout($zone_name, $logo, $menus); 
		$success []= $this->create_layout_modal($zone_name); 
		$success []= $this->create_layout_login($zone_name); 
		$success []= $this->create_page_widget($zone_name, $controller_name); 
		$success []= $this->create_page_login($zone_name);
		$success []= $this->create_config($zone_name, $controller_name, $zone_title, $is_secure_zone); 

		$success = in_array(false, $success) ? false : true ;
		return $success;
	}
	protected function create_base_controller($zone_name, $menus) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/Controllers/BaseController.php';;

		ob_start();
		?>

		namespace <?= $zone ?>\Controllers;

		use Core\Controller;
		use Core\Menu;
		use Core\Service;

		class BaseController extends Controller 
		{

			public function onDispatchZone() 
			{

			}	

			/* --------------------- MENUS ------------------------------*/
			<?php foreach ($menus as $menu_name => $active) : ?>
			<?php if ($active) : ?>
			
			public function menu_<?= $menu_name ?>() 
			{
				$menu = new Menu('menu-<?= $menu_name ?>');

				/*
				$menu->add_item()->label('Déconnexion')->icon($this->icon('power-off'))->url($this->session->logout_url()); 
				$menu->add_item()->label('Prospects')->icon($this->icon('user'))->route('admin-page-clients');
				
				if ($this->is_superadmin()) {
					$menu->add_item()->label('Panel Superadmin')->url('/superadmin/clients')->blank();
					$menu->add_item()->label('Panel Dev')->url('/panel')->blank();  
				}
				*/
				return $menu;
			}
			<?php endif; ?>
			<?php endforeach; ?>
			
			/* --------------------- LAYOUTS ------------------------------*/

			public function layout() 
			{
				$this->render(array(
					
				));
			}	

			public function layout_modal() 
			{
				$this->render(array(

				));
			}

			public function layout_login() 
			{
				$this->render();
			}
		}

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, '<?php '.$content.'?>');
		
		return true;
	}
	protected function create_controller($zone_name, $controller_name) 
	{	
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));
		$controller_name = str_replace('Controller', '', $controller_name);
		$controller_name = str_replace(' ','', ucfirst(strtolower($controller_name)));
		$page_name = str_replace(' ','', strtolower($controller_name));

		$dest_file = $zone.'/Controllers/'.$controller_name.'Controller.php';

		ob_start();
		?>

		namespace <?= $zone ?>\Controllers;

		use \Core\Service;
		use \Core\Form;
		use \Core\Table;

		class <?= $controller_name ?>Controller extends BaseController 
		{
			public function onDispatch()
			{

			}

			public function page_<?= $page_name ?>()
			{
				$this->set_title('Gestion des prospects');

		        $this->render(array(

		        ));
			}
			public function widget_<?= $page_name ?>()
			{
				$this->render(array(

		        ));
			}
		}

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, '<?php '.$content.'?>');

		return true;
	}
	protected function create_less($zone_name, $template) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$template = $template && $template != '' ? $template : false;

		$dest_file = $zone.'/css/main.less';

		ob_start();
		?>

		/* TEMPLATE */
		@base-text-color:#737579;
		@background-color:#FFF;
		@color1:#d94561; 
		@color2:#d94561; 
		@color3:#147ba8;
		@dark-color: #1a2430; 
		@dark-color2: #272c38;
		@light-color: #D3D3D3; 
		@import "/plugins/less/framework.less";
		<?php if ($template) : ?>
		@import "/plugins/cobalt/templates/<?= $template ?>.less";
		<?php endif; ?>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);

		return true;
	}
	protected function create_js($zone_name) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/js/main.js';
		file_put_contents($dest_file, '');

		return true;
	}
	protected function create_layout($zone_name, $logo, $menus) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/views/layout.phtml';

		ob_start();

		?>

		<header>

			<?php if ($logo) : ?>

            <div id="logo">
                <img src="<?= "<?= \$this->img('logo.png'); ?>" ?>" border="0" />
            </div>

        	<?php endif; ?>

        	<?php if ($menus['side']) : ?>
            <?= "<?= \$this->menu('side') ?>" ?>
            <?php endif; ?>

        </header>

        <div id="top-bar">
        	<?php if ($menus['top']) : ?>
            <?= "<?= \$this->menu('top') ?>" ?>
            <?php endif; ?>
        </div>

        <section id="main-container">

            <div class="container-fluid"> 
            
                <div class="row">
                    <div class="col-xs-12">
                    
                   <?= "<?php \$this->load_page(); ?>" ?>
                    
                    </div>
                </div>

                <div class="clear"></div>

                <footer>
                	<?php if ($menus['footer']) : ?>
                    <?= "<?= \$this->menu('footer') ?>" ?>
                    <?php endif; ?>
                </footer>
            
            </div>

        </section>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);

		return true;
	}
	protected function create_layout_modal($zone_name) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/views/layout_modal.phtml';

		ob_start();

		?>

		<!DOCTYPE html>
			<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
			<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
			<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
			<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
			    <head>
			        
			        <meta charset="utf-8">
			        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
			        <title></title>
			        <meta name="description" content="">
			        <meta name="viewport" content="width=device-width">

			        <link rel="stylesheet" href="css/bootstrap.min.css">
			        <link rel="stylesheet" href="css/bootstrap-responsive.min.css">
			        <link rel="stylesheet" href="css/main.css">

			        <script src="js/vendor/modernizr-2.6.2-respond-1.1.0.min.js"></script>

			    </head> 
			    <body>


			        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
			        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.1.min.js"><\/script>')</script>
			        <script src="js/vendor/bootstrap.min.js"></script>

			        <script src="js/plugins.js"></script>
			        <script src="js/main.js"></script>

			        <?= "<?php if (\$this->googleAnalyticsId) : ?>" ?>

			            <script>
			                var _gaq=[['_setAccount','<?= "<?= \$this->googleAnalyticsId ?>" ?>'],['_trackPageview']];
			                (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
			                g.src='//www.google-analytics.com/ga.js';
			                s.parentNode.insertBefore(g,s)}(document,'script'));
			            </script>
			        
			        <?= "<?php endif; ?>" ?>

			    </body>
			</html>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);

		return true;
	}
	protected function create_layout_login($zone_name) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/views/layout_login.phtml';

		ob_start();

		?>

		<div id="background-login">
		    <?= "<?php \$this->load_page(); ?>" ?>
		</div>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);

		return true;
	}
	protected function create_page_widget($zone_name, $subzone_name, $page_name=null) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));
		$subzone_name = str_replace(' ','', strtolower($subzone_name));
		$page_name = !$page_name ? $subzone_name : $page_name;
		$page_name = str_replace('-','_',$page_name);

		$dest_file = $zone.'/views/'.$subzone_name.'/page_'.$page_name.'.phtml';
		$dest_file2 = $zone.'/views/'.$subzone_name.'/widget_'.$page_name.'.phtml';

		ob_start();

		?>

		<?= "<?= \$this->widget('".$page_name."') ?>" ?>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);
		file_put_contents($dest_file2, '');

		return true;
	}
	protected function create_widget($zone_name, $subzone_name, $page_name=null) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));
		$subzone_name = str_replace(' ','', strtolower($subzone_name));
		$page_name = !$page_name ? $subzone_name : $page_name;
		$page_name = str_replace('-','_',$page_name);

		$dest_file2 = $zone.'/views/'.$subzone_name.'/widget_'.$page_name.'.phtml';
		file_put_contents($dest_file2, '');

		return true;
	}
	protected function create_page_login($zone_name) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/views/base/page_login.phtml';

		ob_start();

		?>

		<div id="background-login-cell">
		<img class="logo" src="<?= "<?= \$this->img('logo.png') ?>" ?>" /><br><br>
		<div id="login-form-container">
			
		        <?= "<?= \$form->draw(); ?>" ?>
		</div>
		</div>

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, $content);

		return true;
	}
	protected function create_config($zone_name, $controller_name, $zone_title, $is_secure_zone=false) 
	{
		$zone = str_replace(' ','', ucfirst(strtolower($zone_name)));

		$dest_file = $zone.'/config.php';

		ob_start();

		?>

		return array( 'config' => array(

		    /* PERMISSION */
		    'permissions' => array(
		        
		        <?php if ($is_secure_zone) : ?>
				'auth' => array(
		            'login_page' => '<?= strtolower($zone) ?>-page-login',
		            'redirect_page' =>'<?= strtolower($zone) ?>-page-<?= strtolower($controller_name) ?>',
		        ),
		        
		        'single' => array(
		            'access_zone' => 'Accès à l\'application',
		            //'manage_permissions' => 'Gérer les permissions',
		            //'manager_user_admin' => 'Gérer les utilisateurs de type admin',
		        ),
		        'composed' => array(
		            //'manage_client' => array('clients', 'Créer/modifier un client'),
		        ),
		    	<?php endif; ?>
		    ),

		    /* TITRE ET DESCRIPTION */
		    'page' => array(
		        'title' => translate("<?= $zone_title ?>"),
		        'description' => translate("<?= $zone_title ?>"),
		    ),

		    /* METAS DE BASE */
		    'metas' => array( // title, description, robots
		        'viewport' => 'width=device-width, initial-scale=1',
		        //'robots' => 'noindex, nofollow',
		    ),

		    'favicon' => 'favicon.png',

		    'google_font' => array(
		        //'Ubuntu:300,400,500,300italic,400italic',
		        //'Asap:400,400italic,700,700italic',
		        //'Oxygen:400,300,700',
		    ),
		    'icons' => 'fa',

		    /* PLUGINS */
		    'plugins' => array(

		    ),

		    /* CSS  */
		    'stylesheets' => array(

		    ),

		    /* CSS LESS  */
		    'less' => array(
		        'main.less',
		    ),

		    /* SCRIPTS */
		    'scripts' => array(
		        'main.js',
		    ),

		));

		<?php 

		$content = ob_get_contents();
		ob_end_clean();
		file_put_contents($dest_file, '<?php '.$content.' ?>');

		return true;
	}

}
?>