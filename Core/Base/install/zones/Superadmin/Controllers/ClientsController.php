<?php 

namespace Superadmin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Tools;
use \Core\Table;
use \Superadmin\Pdf;
use \Common\Classes\NotificationMail;

class ClientsController extends BaseController 
{
	public $status;

	public function onDispatch()
	{
		if (!$this->is_superadmin()) {
			Service::error("Vos droits ne vous permettent pas d'accéder à cette zone");
		}
	}

	/* ----------------------------------------- DIVERS------------------------------------------------ */

	/* ----------------------------------------- PAGE / WIDGET CLIENTS------------------------------------------------ */

	public function page_clients()
	{
		$this->set_title('Gestion des clients');
		$this->add_plugin('date_picker');
		$this->add_script('clients.js');

        $this->render(array(
        ));
	}
	public function widget_clients()
	{
		$id_client = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		}

		switch ($action) {
			
			case 'view':
				$active_tab = $this->request->fromRoute('active_tab','A');
				
				$this->render(array(
		            'client' => $client,
		            'action' => $action,
		            'active_tab' => $active_tab,
		        ));
				break;

			case 'list':

				$page = $this->request->fromRoute('page', 1);
				
				$model = _model('_big_users');

				/* --------------------------- SQL DE BASE ET FILTRES  --------------------------------- */

				$data = array();
				$data['search'] = $this->request->fromRoute('search', '');
				$data['order'] = $this->request->fromRoute('order', '');

				$sql = "SELECT bu.* FROM _big_users bu";
				
				$wheres = array();
				$wheres []= "bu.deleted='0'";

				if ($data['search'] != '') {
					if (is_numeric($data['search'])) {
						$wheres []= "id='".$data['search']."'";
					} else {
						$wheres []= "(name LIKE '%".$data['search']."%' OR contact_name = '".$data['search']."')";
					}
				}

				$sql .= ' WHERE ' . implode(' AND ', $wheres);

				if ($data['order'] != '') {

					switch ($data['order']) {
						case 'first_contact':
							$sql .= " ORDER BY bu.date_create DESC";
							break;
						case 'name':
							$sql .= " ORDER BY name ASC";
							break;
					}
				} else {
					$sql .= " ORDER BY bu.date_create DESC";
				}

				/* -------------------------------------------------------------------------------------- */

				$client_callback = function ($name, $object) {

					$html = '<a href="#" data-ajax-modal="'.$this->url('superadmin-widget-clients', array('action'=>'view')).'/'.$object->id.'?active_tab=A">'.$name.'</a> (Ref '.$object->id.')';
					$html .= '<br>';
					$html .= '<span>'.$object->address.'</span><br>';
					$html .= '<span>'.$object->cp.($object->town!='' && $object->cp!='' ? ', ' : '').$object->town.'</span>';

					return $html;
				};
				$contact_callback = function ($contact_name, $object) {

					$html = '<strong>'.$object->contact_prename.' '.$object->contact_name.'</strong>';
					$html .= '<br>';
					$html .= '<span style="display:inline-block; width:18px;text-align:left;">'.$this->tooltip ('Email', 'top', $this->icon('envelope-o', '', '#555')).'</span> <span class="client-mail '.($object->stop_contact?'strike':'').'">'.($object->email != '' ? $object->email : '-').'</span><br>';
					$html .= '<span style="display:inline-block; width:18px;text-align:left;">'.$this->tooltip ('Mobile', 'top', $this->icon('mobile-phone', 'lg', '#555')).'</span> <span class="client-mobile '.($object->stop_contact?'strike':'').'">'.\Core\Tools::convert_phone($object->mobile).'</span><br>'; 
					$html .= '<span style="display:inline-block; width:18px;text-align:left;">'.$this->tooltip ('Téléphone', 'top', $this->icon('phone', 'lg', '#555')).'</span> <span class="client-phone '.($object->stop_contact?'strike':'').'">'.\Core\Tools::convert_phone($object->phone).'</span>';
					
					return $html;
				};
				$access_callback = function ($value, $object) {
					return $value ? '<a href="">'.$this->icon('check-square-o', '', '#555').'</a>' : '<a href="">'.$this->icon('square-o', '', '#555').'</a>';
				};
				$date_create_callback = function ($value, $object) {
					return \Core\Tools::convert_date($object->date_create); 
				};
				$last_activity_callback = function ($value, $object) {
					
					$last_co = $this->db->query_one('SELECT date_connexion FROM _users WHERE id_big_user="'.$object->id.'" ORDER BY date_connexion DESC');
					$html = 'Dernière connexion : '.($last_co ? \Core\Tools::convert_date($last_co->date_connexion) : '-') .'<br>';
					return $html;	
				};


				$table = new Table('_big_users');
				$table->sql($sql, 'id');

				$table->add_col('name', 'Client')->callback($client_callback)->filter('bold');
				$table->add_col('contact_name', 'Contact')->callback($contact_callback);
				$table->add_col('', 'date création')->callback($date_create_callback);
				$table->add_col('', 'Dernière activité')->callback($last_activity_callback);
				//$table->add_col('date_create', 'Infos')->filter('date', 'd F Y')->filter('italic')->callback($infos_callback);
				
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-ajax' => $this->url('superadmin-widget-clients', array('action'=>'form')).'/[ID]',
					'data-transition' => 'slide-left',
					'class' => 'client-form-link',
					'data-scroll' => 'top',
					'title' => 'Infos du contact',
					'icon' => $this->icon('pencil', 'circle'),
				));

				$table->add_col('', '')->url(array(
					'data-ajax' => $this->url('superadmin-widget-clients', array('action'=>'delete')).'/[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer ce client ?',
					'title' => 'Supprimer le contact',
					'icon' => $this->icon('remove', 'circle'),
				));

				$table->add_col('', '')->url(array(
					'href' => '/panel/clients?action=redirect_admin_client&id=[ID]',
					'title' => 'Se connecter à l\'admin',
					'icon' => $this->icon('arrow-right', 'circle'),
				));
				

				$table->pager(array(
					'page' => $page,
					'url' => $this->url('superadmin-widget-clients', array('action' => 'list')) .'/page/[PAGE]',
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
					'show_count' => true,
					'items_attrs' => array('data-data'=>'get_search_filters'), // attributs affectés à chaque bloc
				));

				$this->render(array(
					'data' => $data,
					'table' => $table,
		            'action' => $action,
		        ));
				break;


			case 'form':

				if ($this->is_support()) { 
					exit();
				}

				$form = new Form('_big_users', $id_client);
				$form->id('client-form')->action($this->url('superadmin-widget-clients', array('id'=> $id_client, 'action' => 'form')))->ajax('slide-right')->attr('data-scroll','top');
				$form->factorize();
				
				$form->remove_all_except(array('name','address','cp','town','country','contact_prename','contact_name','email','phone','mobile','comment'));

				$form->add_html_before('name', '<h2>Nouveau client</h2><hr>');

				$form->add_submit('Enregistrer');

				if ($this->request->isPost()) {

					$form->bind($this->request->post);
					
					if ($form->validate()) {

						$client = $form->get_entity();

						$is_add = !$client->id ? true : false; 

						if ($id_client = $client->save()) {

							if ($is_add) {
								//$this->add_historique($id_client, 1);
							} 
							
							Service::flash('Le client a bien été enregistré', 'success', true);
							Service::redirect($this->url('superadmin-widget-clients', array('action'=>'list')));

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
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'delete':

				$client->trash();
				Service::flash('Le client '.$client->name.' a bien été supprimé', 'warning', true);
				Service::redirect($this->url('superadmin-widget-clients', array('action'=>'list')));
				break;
		}
	}

	/* ----------------------------------------- ACTIONS CLIENTS------------------------------------------------ */

	public function json_clients()
	{
		$id_client = $this->request->fromRoute('id');
		$action = $this->request->fromRoute('action', '');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function widget_relance : aucun ID client transmit');
		}

		switch ($action) {

			case 'stop-contact':
				$client->stop_contact = '1';
				$client->save();

				$this->render(array(
					'id_client' => $client->id,
					'success' => 1,
					'msg' => 'Client marqué comme non-contactable',
				));
				break;

			case 'cancel-stop-contact':
				$client->stop_contact = '0';
				$client->save();

				$this->render(array(
					'id_client' => $client->id,
					'success' => 1,
					'msg' => 'Client marqué comme contactable',
				));
				break;

			case 'cancel-alert':

				$client->alert = '0000-00-00';
				$client->alert_comment = '';
				$client->save();

				$this->render(array(
					'url' => $this->url('superadmin-json-clients', array('id'=>$client->id, 'action'=>'cancel-alert-done' )),
					'id_client' => $client->id,
					'success' => 1,
					'msg' => 'Relance effectuée',
				));
				break;

		}

	}


	/* ----------------------------------------- GETTERS ------------------------------------------------ */

	public function json_get_status_infos()
	{
		$id_client = $this->request->fromRoute('id');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function widget_relance : aucun ID client transmit');
		}
		
		$primary_status = $this->get_client_primary_status ($client);
		$primary_status_label = $this->get_client_status_label ($client);
		$secondary_status = $this->get_client_secondary_status ($client);

		$status_details = $this->get_client_status_details($client);
		$popup_status = $this->get_client_popup_status ($client, $primary_status, $primary_status_label);

		$this->render(array(
			'primary_status' => $primary_status,
			'primary_status_label' => $primary_status_label,
			'secondary_status' => $secondary_status,
			'status_details' => $status_details,
			'popup_status' => $popup_status,
			'process_step' => $this->get_client_process_step ($client),
			'count_cyan' => $this->get_count_client_status ('cyan'),
			'count_orange' => $this->get_count_client_status ('orange'),
			'count_blue' => $this->get_count_client_status ('blue'),
			'count_red' => $this->get_count_client_status ('red'),
			'count_pink' => $this->get_count_client_status ('pink'),
			'count_purple' => $this->get_count_client_status ('purple'),
		));
	}

	public function get_count_client_status ($color) 
	{
		$count = 0;

		$sql_no_abonnement = '(SELECT COUNT(*) FROM historique_abonnements WHERE id_big_user=bu.id AND date<="'.date('Y-m-d').'" AND action="1" AND value!="pack0" ORDER BY date DESC)="0"';

		switch ($color) {
			
			case 'cyan':
				$count = $this->db->query_count('SELECT id FROM _big_users WHERE alert=:today AND deleted="0"', array('today'=>date('Y-m-d') ) );
				break;

			case 'orange':
				$count = $this->db->query_count('SELECT id FROM _big_users bu WHERE '.$sql_no_abonnement.' AND bu.start_trial!="0000-00-00" AND bu.end_trial>=:today AND bu.motif="0" AND bu.deleted="0"', array('today'=>date('Y-m-d') ));
				break;

			case 'pink':
				$count = $this->db->query_count('SELECT id FROM _big_users bu WHERE (SELECT value FROM historique_abonnements WHERE id_big_user=bu.id AND date<=:today AND action="1" ORDER BY date DESC LIMIT 1)!="pack0" AND (SELECT card_status FROM _stripe_accounts WHERE id_big_user=bu.id LIMIT 1)="1" AND deleted="0"', array('today'=>date('Y-m-d')) );
				break;

			case 'purple':
				$count = $this->db->query_count('SELECT id FROM _big_users WHERE motif>"0" AND deleted="0"');
				break;

			case 'red':
				$count = $this->db->query_count('SELECT id FROM _big_users bu WHERE '.$sql_no_abonnement.' AND end_trial<"'.date('Y-m-d').'" AND end_trial!="0000-00-00" AND bu.motif="0" AND deleted="0"');
				break;

			case 'blue':
				$count = $this->db->query_count('SELECT id FROM _big_users bu WHERE '.$sql_no_abonnement.' AND start_trial="0000-00-00" AND bu.motif="0" AND deleted="0"');
				break;
		}

		return $count;
	}

	protected function get_client_primary_status ($client) 
	{
		// status :
		/*
			bleu : à configurer
			rouge : en attente du client (attente de déclenchement d'abonnement)
			orange : période essai 
			rose : abonné 
			pourpre : archivé non abonné ou ancien abonné
			cyan : Alerte
			
		*/
		$last_pack = $client->get_abonnement(true, null, date('Y-m-d'));

		if ($last_pack) { // abonné
			// si abonné mais carte devenu invalide, on met en rouge au lieu de rose 
			$status = $client->have_valid_card() ? 'pink' : 'red';

		} else if ($client->motif > 0) {
			$status = 'purple';

		} else if ($client->start_trial != '0000-00-00' && date('Y-m-d') <= $client->end_trial && !$last_pack) { // période essai
			$status = 'orange';
		
		}  else if (date('Y-m-d') > $client->end_trial && $client->end_trial != '0000-00-00' && !$last_pack) {
			$status = 'red';
		
		} else if (!$last_pack && $client->start_trial == '0000-00-00') {
			$status = 'blue';
		}

		return $status;
	}

	protected function get_client_status_label ($client) 
	{
		/* LABEL DU BADGE */
		$label = '';
		
		$last_pack = $client->get_abonnement(true, null, date('Y-m-d'));

		if ($last_pack) { // abonné
			
			$label = $client->have_valid_card() ? 'Abonné' : 'Abonné en suspens';

		} else if ($client->motif > 0) { // présence de motif de non abonnement	
			$label = 'Archive';

		} else if ($client->start_trial != '0000-00-00' && date('Y-m-d') <= $client->end_trial && !$last_pack) { // période essai
			$label = 'Période essai';
		
		} else if (date('Y-m-d') > $client->end_trial && $client->end_trial != '0000-00-00' && !$last_pack) { // période essai finie (ET absence de motif)
			$label = 'Attente abo';	

		}  else if (!$last_pack && $client->start_trial == '0000-00-00') {
			$label = 'Configurer';
		}

		return $label;
	}

	protected function get_client_secondary_status ($client) 
	{
		/* STATUT SECONDAIRE */		
		$status2 = 0;
		
		if ($client->alert == date('Y-m-d')) {
			$status2 = 'cyan';
		}

		return $status2;
	}

	protected function get_client_popup_status ($client, $status, $status_label) 
	{
		$html = '';

		if ($client->alert == date('Y-m-d')) {

			$html .= '<a href="#" id="popup-alert" ';
			$html .= 'data-ajax-callback="'.($client->alert == '0000-00-00' ? 'cancel_alert_done_callback' : 'alert_done_callback').'" ';
			$html .= 'data-ajax-json="'.($this->url('superadmin-json-clients', array('id'=>$client->id, 'action'=>($client->alert == '0000-00-00' ? 'cancel-alert-done' : 'alert-done') ))).'" ';
			$html .= 'class="'.($client->alert == '0000-00-00' ? 'done' : '').'">';
			$html .= $this->icon('bullhorn','x4');
			$html .= '<br><span>Alerte</span></a>';

		} elseif ($status !== 0) {
			$html .= '<div id="state" class="'.$status.'">';
			$html .= $this->icon('folder-open-o','x4');
			$html .= '<br><span>'.$status_label.'</span></div>';
		}

		return $html;
	}

	public function get_client_status_details($client) 
	{
		$html = '';

		$status = $this->get_client_primary_status($client);
		$status2 = $this->get_client_secondary_status($client);
		$label = $this->get_client_status_label($client);

		if ($status != false) {

			$active_tab = 'A';
			$html .= '<a href="#" data-ajax-modal="'.$this->url('superadmin-widget-clients', array('action'=>'view')).'/'.$client->id.'?active_tab='.$active_tab.'"><span class="badge badge-'.$status.'">'.$label.'</span></a> ';
		}

		if ($status2 != false) {

			if ($status2 == 'cyan') {
				$url_cancel = $this->url('superadmin-json-clients', array('id'=>$client->id, 'action'=>'cancel-alert-done' ));
				$url_done = $this->url('superadmin-json-clients', array('id'=>$client->id, 'action'=>'alert-done' ));
				$html .= '<span class="badge badge-'.$status2.' '.($client->alert == '0000-00-00' ? 'done' : '').'" data-ajax-modal="'.$this->url('superadmin-widget-clients', array('action'=>'view')).'/'.$client->id.'?active_tab=E">Alerte</span> ';
			} 
		}

		$status_infos = $client->get_abonnement_status_infos(); 
		$html .= '<br>'.$status_infos['status_label'];

		if ($status == 'orange') {


		} else if ($status == 'purple' && $client->motif != 0) {
			
			$html .= '<br> Motif: '.$client->get_option_value('motif');
		}

		return $html;
	}

	public function get_client_process_step ($client) 
	{
		$last_pack = $client->get_abonnement(true, null, date('Y-m-d'));
		$valid_card = $client->have_valid_card();

		if ( ($last_pack && $valid_card) || $client->motif > 0) {
			$step = 6;
		}  else if ($last_pack && !$valid_card) {
			$step = 5;
		}  else if (date('Y-m-d') > $client->end_trial && $client->end_trial != '0000-00-00') {
			$step = 5;
		}  else if (date('Y-m-d') >= $client->start_trial && date('Y-m-d') <= $client->end_trial) {
			$step = 3;
		} else {
			$step = 1;
		}

		return $step;
	}



	/* ----------------------------------------- WIDGETS ------------------------------------------------ */

	public function widget_account()
	{
		$id_client = $this->request->fromRoute('id');
		$section = $this->request->fromRoute('section', 'dossier');
		$action = $this->request->fromRoute('action', null);

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function widget_relance : aucun ID client transmit');
		}

		$update_status = false;

		$nb_admins = $this->db->query_count('SELECT id FROM _users WHERE id_big_user=:id_big_user AND id_rank="2"', array('id_big_user' => $id_client));

		$active_buttons = array(
			'dossier' => true,
			'param' => $client->auto_params == 1 ? true : false ,
			'access' => true,
			'admin-account' => $nb_admins > 0 ? true : false ,
			'trial' => $client->start_trial != '0000-00-00' && $client->end_trial != '0000-00-00' ? true : false ,
		);

		switch ($section) {

			case "dossier":

				$this->render(array(
					'client' => $client,
					'section' => $section,
					'active_buttons' => $active_buttons,
					'update_status' => $update_status,
				));

				break;

			case "param":

				$action = $this->request->fromRoute('action', null);
				$errors = array();

				if ($action == 'launch') {
					$errors = $this->param_new_big_user ($client->id);

					if (empty($errors)) {
						$client->auto_params = 1;
						$client->save();

						$active_buttons['param'] = true;
					
						$update_status = true;
					}
				}

				$this->render(array(
					'auto_params' => $client->auto_params,
					'client' => $client,
					'section' => $section,
					'errors' => $errors, 
					'active_buttons' => $active_buttons,
					'update_status' => $update_status,
				));
				break;

			case "access":

				$change_access_admin = $this->request->fromRoute('action', null);

				if ($change_access_admin != null) {
					$client->access_admin = $change_access_admin;
					$client->save();

					Service::flash('Accès à l\'application modifié', 'success');

					$update_status = true;
				}

				$this->render(array(
					'access_admin' => $client->access_admin,
					'client' => $client,
					'section' => $section,
					'active_buttons' => $active_buttons,
					'update_status' => $update_status,
				));
				break;

			case "admin-account":

				$model = array(
					'username' => array(
						'type' => 'VARCHAR',
						'max' => 75,
						'placeholder' => 'Login',
						'label' => 'Login',
						'required' => true,
						'remote_validation' => 'Ce nom d\'utilisateur existe déjà',
					),
					'password' => array(
						'type' => 'PASSWORD',
						'max' => 75,
						'placeholder' => 'Password',
						'label' => 'Mot de passe',
						'required' => true,
					),
				);

				$form = new Form('_users', 0);
				$form->setModel ($model);
				$form->id('')->action($this->url('superadmin-widget-account', array('id'=>$id_client, 'section'=>"admin-account")) )->ajax('none');
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

						$post->password = trim($post->password);
						
						$crypto = Service::Crypto();
						$password = $crypto->hash($post->password);
						
						$fullname = $client->contact_prename.' '.$client->contact_name;

						$result = $this->db->insert('_users')->values(array('id_big_user'=> $id_client, 'name' => $fullname, 'email' => $client->email, 'id_rank' => '2', 'username' => $post->username, 'password' => $password, 'date_create' => date('Y-m-d')))->execute();

						if ($result) {

							$form->reset();
							
							$nb_admins++;
							$active_buttons['admin-account'] = true;

							$url_login = SITE_URL.$this->url('admin-page-login');
							NotificationMail::admin_account_created($client->email, $post->username, $post->password, $url_login);

							Service::flash('Le compte admin a bien été enregistré, un email a été envoyé à l\'utilisateur', 'success', true);

							$update_status = true;

						} else {
							Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}
				}

				$random_password1 = \Core\Tools::generate_password(8); 
				$random_password2 = \Core\Tools::generate_password(8); 
				$random_password3 = \Core\Tools::generate_password(8); 

				$this->render(array(
					'form' => $form,
					'client' => $client,
					'section' => $section,
					'nb_admins' => $nb_admins,
					'active_buttons' => $active_buttons,
					'update_status' => $update_status,
					'random_password1' => $random_password1,
					'random_password2' => $random_password2,
					'random_password3' => $random_password3,
				));
				break;

			case "trial":

				$form_trial = new Form('_big_users', $client->id);
				$form_trial->id('trial-form')->action($this->url('superadmin-widget-account', array('id'=> $client->id, 'section'=>'trial')))->ajax('none');
				$form_trial->factorize();
				$form_trial->remove_all_except(array('start_trial','end_trial'));
				$form_trial->get('start_trial')->label = 'Débute le';
				$form_trial->get('end_trial')->label = 'Expire le';
				$form_trial->add_submit('Enregistrer');

				if ($this->request->isPost()) {

					if (isset($this->request->post->start_trial)) {

						$form_trial->bind($this->request->post);

						if ($form_trial->validate()) {
							$entity = $form_trial->get_entity();

							if ($success = $entity->save()) {

								$update_status = true;

								$active_buttons['trial'] = true;

								$url_login = SITE_URL.$this->url('admin-page-login');
								NotificationMail::date_trial($client->email, $entity->start_trial, $entity->end_trial, $url_login);

								Service::flash('Les dates de période d\'essai ont été mises à jour, un email a été envoyé à l\'utilisateur', 'success', true);

							} else {
								Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
							}

						} else {
							Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
						}

					} 

				} else {
					$form_trial->bind($client->get_data());
				}

				$this->render(array(
					'client' => $client,
					'section' => $section,
					'active_buttons' => $active_buttons,
					'form_trial' => $form_trial,
					'update_status' => $update_status,
				));
				break;
		}
	}

	public function widget_archive()
	{
		$id_client = $this->request->fromRoute('id');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function widget_relance : aucun ID client transmit');
		}

		$form_archive = new Form('_big_users', $id_client);
		$form_archive->id('archive-form')->action($this->url('superadmin-widget-archive', array('id'=> $id_client)))->ajax('none')->attr('data-ajax-callback','seance_saved');
		$form_archive->factorize();
		$form_archive->remove_all_except(array('motif'));
		
		$form_archive->add_submit('Enregistrer');

		if ($this->request->isPost()) {

			$form_archive->bind($this->request->post);

			if ($form_archive->validate()) {
				
				$old_value = $client->motif;

				$client = $form_archive->get_entity();
				$client->motif = $client->motif == '' ? 0 : $client->motif;

				if ($success = $client->save()) {

					Service::flash('Le client bien a été archivé', 'success', true);

				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}

				$client = $this->db->select('_big_users')->id($id_client)->execute();

			} else {
				Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
			}

		} else {
			$form_archive->bind($client->get_data());
		}

		$this->render(array(
			'form_archive' => $form_archive,
			'client' => $client,
		));
	}

	public function widget_alert()
	{
		$id_client = $this->request->fromRoute('id');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function widget_relance : aucun ID client transmit');
		}

		$form_relance = new Form('_big_users', $id_client);
		$form_relance->id('alert-form')->action($this->url('superadmin-widget-alert', array('id'=> $id_client)))->ajax('none')->attr('data-ajax-callback','seance_saved');
		$form_relance->factorize();
		$form_relance->remove_all_except(array('alert','alert_comment'));
		
		$form_relance->add_submit('Enregistrer');

		if ($this->request->isPost()) {

			$form_relance->bind($this->request->post);

			if ($form_relance->validate()) {
				$client = $form_relance->get_entity();

				if ($success = $client->save()) {

					Service::flash('L\'alerte a bien été enregistrée', 'success', true);

				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}

				$client = $this->db->select('_big_users')->id($id_client)->execute();

			} else {
				Service::flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
			}

		} else {
			$form_relance->bind($client->get_data());
		}

		$btn_cancel_active = false;
		$btn_done_active = false;
		$after_alert_done = false;

		if ($client->alert != '0000-00-00') {

			$form_relance->disable();

			$btn_cancel_active = true;
		} 

		$this->render(array(
			'form_relance' => $form_relance,
			'client' => $client,
			'btn_cancel_active' => $btn_cancel_active,
			'btn_done_active' => $btn_done_active,
			'after_alert_done' => $after_alert_done,
		));
	}

	public function widget_historique()
	{
		$id_client = $this->request->fromRoute('id');

		if (!$id_client) {
			Service::error('Function widget_relance : aucun ID client transmit');
		} 
		$client = $this->db->select('_big_users')->id($id_client)->execute();
		$historique = $this->db->query('SELECT h.* FROM historique_big_users h WHERE h.id_big_user=:id_client ORDER BY h.id ASC, h.date ASC ', array('id_client'=>$id_client), 'historique_big_users');

		$this->render(array(
			'historique' => $historique,
			'client' => $client,
		));
	}


	/* ----------------------------------------- QUICK COMMENT BOX ------------------------------------------------ */

	public function json_save_comment()
	{
		$id_client = $this->request->fromRoute('id');

		if ($id_client) {
			$client = $this->db->select('_big_users')->id($id_client)->execute();
		} else {
			Service::error('Function function : ID transmit inexistant ou incorrect');
		}

		if ($this->request->isPost()) {

			$client->comment = $this->request->post->comment;	
			$success = $client->save();

			$this->render(array(
				'success' => $success,
			));
		}

		$this->render(array(
			'success' => false,
		));
	}

	/* */

	protected function param_new_big_user ($id_big_user) 
	{
		if (!isset($id_big_user)) {
			Service::error('function param_new_big_user : paramètre manquant');
			exit();
		}
		$errors = array();

		// PERMISSIONS
		$default_permissions = array(
			array('id_rank'=>2, 'permission'=>'access_zone', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manage_permissions', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manage_variables', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manager_user_admin', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manage_params', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'view_stats', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manage_clients', 'value'=>'YES'),
			array('id_rank'=>2, 'permission'=>'manager_campain', 'value'=>'YES'),
		);
		$already_done = $this->db->query('SELECT * FROM _rank_has_permission WHERE id_big_user="'.$id_big_user.'"');

		if (!$already_done) {
			foreach ($default_permissions as $permission) {
				$permission['id_big_user'] = $id_big_user;
				$this->db->insert('_rank_has_permission')->values($permission)->execute();
			}
		} else {
			//$errors []= 'Table _rank_has_permission : Enregistrements déjà existants pour ce client';
		}

		// PARAMETRES (WIFI, POTRAIL ...)
		$default_params = array(
			'id_big_user' => $id_big_user,
			'cgu' => 0,
		);
		$already_done = $this->db->query('SELECT * FROM _params WHERE id_big_user="'.$id_big_user.'"');

		if (!$already_done) {
			$this->db->insert('_params')->values($default_params)->execute();
		} else {
			//$errors []= 'Table _rank_has_permission : Enregistrements déjà existants pour ce client';
		}
		
		/*
		// ACTIVITES
		$default_activites = array(
			'YAKO Integral',
			'YAKO Pump',
			'YAKO Training',
			'YAKO Jump',
			'YAKO Détente',
			'YAKO Attitude',
			'Yako Combat',
			'Yako Baila',
			'Abdos Cuisses Fessiers',
			'Gym douce',
			'Cardio',
			'Musculation',
			'Sauna',
			'Step',
			'Aéro',
			'Multi-activité',
			'Autre',
		);
		$already_done = $this->db->query('SELECT * FROM activities WHERE id_big_user="'.$id_big_user.'"');

		if (!$already_done) {
			foreach ($default_activites as $activity) {
				$success = $this->db->insert('activities')->values(array('id_big_user'=>$id_big_user,'name'=>$activity))->execute();
				if ($success) {
					
				} else {
					$errors []= 'Table activities : Erreur lors de l\'enregistrement';
				}
			}
		} else {
			//$errors []= 'Table activities : Enregistrements déjà existants pour ce client';
		}

		// MOTIFS
		$default_motifs = array(
			'Trop cher',
			'Mauvais matériel',
			'Pas assez de machines',
			'Trop éloigné du domicile',
			'Manque de temps',
			'Manque de motivation',
			'Autre',
		);

		$already_done = $this->db->query('SELECT * FROM motifs WHERE id_big_user="'.$id_big_user.'"');

		if (!$already_done) {
			foreach ($default_motifs as $motif) {
				$this->db->insert('motifs')->values(array('id_big_user'=>$id_big_user,'name'=>$motif))->execute();
				if ($success) {
					
				} else {
					$errors []= 'Table motifs : Erreur lors de l\'enregistrement';
				}
			}
		} else {
			//$errors []= 'Table motifs : Enregistrements déjà existants pour ce client';
		}

		// PARAMETRE TEMPLATE CAMPAGNE MAIL
		$already_done = $this->db->query('SELECT * FROM _emails_templates WHERE id_big_user="'.$id_big_user.'"');

		if (!$already_done) {
			$default_template = array(
				'name' => 'Template standard OB avec bannière',
				'id_big_user' => $id_big_user,
				'font_size' => '14',
				'title_font_size' => '18',
				'font_color' => '#505050',
				'link_color' => '#f66c21',
				'title_color' => '#1678b7',
				'id_image' => 61,
				'id_user_create' => 1,
			);

			$new_id = $this->db->insert('_emails_templates')->values($default_template)->execute();
			
			$default_template['id_image'] = 0;
			$default_template['name'] = 'Template standard OB sans bannière';

			$new_id2 = $this->db->insert('_emails_templates')->values($default_template)->execute();
			
			if ($new_id && $new_id2) {

			} else {
				$errors []= 'Table _emails_templates : Erreur lors de l\'enregistrement';
			}
		}


		// SPOTHIT SOUS COMPTE
		if (!IS_LOCAL) {
			$client = $this->db->select('_big_users')->id($id_big_user)->execute();
			$spothit = Service::Api('spothit');
			$spothit->add_subaccount($client);
		}
		*/
		return $errors;
	}

}