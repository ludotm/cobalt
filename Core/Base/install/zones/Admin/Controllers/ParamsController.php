<?php

namespace Admin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Tools;
use \Core\Table;

class ParamsController extends BaseController
{

	public function onDispatch()
	{

	}

	/* ----------------------------------------- PARAMS------------------------------------------------ */

	public function page_params()
	{
		if (!$this->permission('manage_params')) {
			exit();
		}

		$this->set_title('Paramètres');

		$this->render(array(
			'manage_params' => $this->permission('manage_params'),
			'manage_users' => $this->permission('manage_users'),
			'manage_permissions' => $this->permission('manage_permissions'),
			'manage_variables' => $this->permission('manage_variables'),
        ));
	}
	
	public function widget_params()
	{
		$this->render(array(
	   ));
	}

	/* ----------------------------------------- USERS------------------------------------------------ */

	public function page_users()
	{
		if (!$this->permission('manager_user_admin') && !$this->permission('manager_user_coach')) {
			exit();
		}

		$this->set_title('Gestion des utilisateurs');
        $this->render();
	}
	public function widget_users()
	{
		$id_user = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', 'list');

		if ($id_user) {
			$user = $this->db->select('_users')->id($id_user)->execute();
		}

		switch ($action) {

			case 'list':

				$page = $this->request->fromRoute('page', 1);

	            $rank_list = array();
	            $rank_list []= 2;

 				$active_user_callback = function ($value, $object) {

	            	$icon = $value == 0 ? $this->icon('user', '', '#CCC') : $this->icon('user') ;
	            	$url = $this->url('admin-json-activeuser', array('id' => $object->id));
	            	return '<a href="#" data-activeid="'.$object->id.'" data-ajax-json="'.$url.'" data-ajax-callback="active_user_callback">'.$icon.'</a>';
	            };

				$delete_url_callback = function ($value, $object) {
	            	if ($value > 0) {
	            		return $this->icon('remove', '', '#BBB');
	            	}
	            	$url = $this->url('admin-widget-users', array('action'=>'delete', 'id' => $object->id));
	            	return '<a href="#" data-confirm="Etes-vous certain de vouloir supprimer cet utilisateur?" data-ajax="'.$url.'">'.$this->icon('remove').'</a>';
	            };

				$table = new Table('users_list');
				$table->sql(
					"SELECT u.*, r.name as rank, b.name as big_user 
					FROM _users u LEFT JOIN _ranks r ON u.id_rank = r.id LEFT JOIN _big_users b ON b.id = u.id_big_user
					WHERE u.deleted='0' AND u.username!='demo' AND u.id_rank IN (".implode(',', $rank_list).") AND b.id='".$this->session->get('id_big_user')."'
					GROUP BY u.id
					", 'id');

				$table->entity_id('id');
				$table->add_col('name', 'Nom d\'utilisateur')->filter('bold');
				$table->add_col('rank', 'Rang');
				$table->add_col('date_create', 'Date de création')->filter('date', 'd F Y')->hide_on('sm');
				$table->add_col('date_connexion', 'Dernière connexion')->filter('date', 'd F Y')->hide_on('xs');
				$table->add_col('active', 'Actif/passif')->callback($active_user_callback);;
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-ajax' => $this->url('admin-widget-users', array('action'=>'form')).'/[ID]',
					//'data-ajax-modal' => $this->url('admin-widget-users', array('action'=>'form')).'/[ID]',
					//'data-modal-title' => 'Test',
					//'data-modal-width' => '700px',
					'data-transition' => 'slide-left',
					'icon' => $this->icon('pencil'),
				));

				$table->pager(array(
					'page' => $page,
					'url' => $this->url('admin-widget-users', array('action' => 'list')) .'/page/[PAGE]',
					//'count' => $this->db->query_count('SELECT * FROM _users'),
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
				));

				$this->render(array(
					'table' => $table,
		            'action' => $action,
		        ));
				break;


			case 'form':

				$form = new Form('_users', $id_user);
				$form->id('user-form')->action($this->url('admin-widget-users', array('id'=> $id_user, 'action' => 'form')))->ajax('slide-right');
				$form->factorize();
				$form->get('id_rank')->options_table('_ranks', 'id', 'name');
				$form->add_submit('Enregistrer');
				//$form->js_validation(false);

				unset($form->get('id_rank')->options[1]); // superadmin
				if ($this->session->get('id_rank') > 2) { // si rang inférieur à admin (directeur ou commercial)
					unset($form->get('id_rank')->options[2]); // admin
					unset($form->get('id_rank')->options[4]); // directeur

					$form->get('id_agency')->type = 'HIDDEN';
					$form->get('id_agency')->value = $this->session->get('id_agency');
				}

				if ($this->request->isPost()) {

					$form->bind($this->request->post);

					if ($form->validate()) {

						if ($this->session->get('username') == 'demo') {
							$this->session->flash('La version de démo ne permet pas d\'ajouter des utilisateurs', 'success', true);
							Service::redirect($this->url('admin-widget-users', array('action'=>'list')));
						}

						$user = $form->get_entity();

						if ($user->password != '') {
							$crypto = Service::Crypto();
							$user->password = $crypto->hash($user->password);
						} else {
							unset($user->password);
						}

						if ($user->save()) {
							//$form->reset();
							$this->session->flash('L\'utilisateur a bien été enregistré', 'success', true);
							Service::redirect($this->url('admin-widget-users', array('action'=>'list')));

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

		        $this->render(array(
		            'form' => $form,
		            'action' => $action,
		        ));
				break;


			case 'delete':

				$username = $user->username;
				$user->trash();
				$this->session->flash('L\'utilisateur '.$username.' a bien été supprimé', 'warning', true);
				Service::redirect($this->url('admin-widget-users', array('action'=>'list')));
				break;
		}
	}

	public function json_activeuser() 
	{
		$id = $this->request->fromRoute('id', 0);
		$user = $this->db->select('_users')->id($id)->execute();

		if ($user->active == 0) {
			$user->active = 1;
		} else {
			$user->active = 0;
		}
		$user->save();

		$this->render(array(
			'id' => $id,
			'status' => $user->active,
		));
	}

	/* ----------------------------------------- VARIABLES------------------------------------------------ */

	public function widget_variables()
	{

		$this->render(array(
        ));
	}
/*
	public function mini_manager_activities () 
	{
		return array(
			'table' => 'activities',
	        'fields' => array(
	        	'name' => array(
	        		'type' => 'TEXT',
	        	),
	        	'id_big_user' => array(
	        		'type' => 'HIDDEN',
	        		'value' => $this->session->get('id_big_user'),
	        	),
	        ),      
	        'permission' => 'manage_variables',
	        'sql' => 'SELECT * FROM activities WHERE id_big_user="'.$this->session->get('id_big_user').'"',
		);
	}
*/

	/* ----------------------------------------- PERMISSIONS------------------------------------------------ */

	public function widget_permissions()
	{
		if (!$this->permission('manage_permissions')) {
			exit();
		}

		$id_rank = $this->request->fromRoute('id_rank',0);
		if ($this->request->isPost()) {
			$id_rank = $this->request->post->id_rank;
		}

		$form_rank_model = array(
			'id_rank' => array(
				'type' => 'SELECT_ID',
				'label' => 'Permissions pour le rang : ',
				'required' => true,
				'empty_option' => array('', 'Séléctionnez un rang'),
				'value' => $id_rank,
			),
		);

		$form_rank = new Form($form_rank_model);
		$form_rank->id('rank_form')->method('GET')->action($this->url('admin-widget-permissions'))->ajax('none');
		$form_rank->factorize();
		$form_rank->get('id_rank')->options_table('_ranks', 'id', 'name');
		unset($form_rank->get('id_rank')->options[1]);

		if (!$id_rank) {
			$form = null;

		} else {

			if ($this->request->isPost()) {

				foreach ($this->request->post as $key => $value) {
					
					if ($key != 'id' && $key != 'id_rank') {
						$values = array(
							'id_rank' => $id_rank,
							'id_big_user' => $this->session->get('id_big_user'),
							'permission' => $key,
							'value' => $value,
						);

						$data_exists = $this->db->query('SELECT * FROM _rank_has_permission WHERE id_big_user=:id_big_user AND id_rank=:id_rank AND permission=:permission', array('id_rank' => $id_rank, 'permission' => $key, 'id_big_user'=>$this->session->get('id_big_user')));

						if (!$data_exists) {
							$this->db->insert('_rank_has_permission')->values($values)->execute();
						} else {
							$this->db->update('_rank_has_permission')->values($values)->where('id_big_user=:id_big_user AND id_rank=:id_rank AND permission=:permission', $values)->execute();
						}
					}
				}
				$this->session->flash('Les permissions ont bien été mises à jour', 'success', true);
			}

			$permissions_list = _get('config.permissions');
			$rank_has_permission = $this->db->query('SELECT * FROM _rank_has_permission WHERE id_big_user=:id_big_user AND id_rank=:id_rank', array('id_rank'=>$id_rank, 'id_big_user'=>$this->session->get('id_big_user')));

			$permissions = array();
			if ($rank_has_permission) {
				foreach ($rank_has_permission as $perm) {
					$permissions [$perm->permission] = $perm->value;
				}
			}

			$model = array();

			$model['id_rank'] = array(
				'type' => 'HIDDEN',
				'value' => $id_rank,
			);

			foreach ($permissions_list['single'] as $name => $label) {
				$model[$name] = array(
					'type' => 'RADIO',
					'skin' => 'badge',
					'label' => $label,
					'required' => true,
					'options' => array('NO' => 'Non', 'YES' => 'Oui'),
					'value' => (array_key_exists($name, $permissions) ? $permissions[$name] : 'NO'),
				);
			}

			foreach ($permissions_list['composed'] as $name => $params) {
				$model[$name] = array(
					'type' => 'RADIO',
					'skin' => 'badge',
					'label' => $params[1],
					'required' => true,
					'options' => array('NO' => 'Non', 'YES' => 'Oui', 'OWN' => 'Oui pour le créateur'),
					'value' => (array_key_exists($name, $permissions) ? $permissions[$name] : 'NO'),
					'group' => true,
				);
			}

			$form = new Form($model);
			$form->id('permissions-form')->action($this->url('admin-widget-permissions'))->ajax('none');
			$form->factorize();
			//$form->add_item('Enregistrer');
			$form->add_submit('Enregistrer');
		}

		$this->render(array(
			'id_rank' => $id_rank,
            'form_rank' => $form_rank,
            'form' => $form,
        ));
	}


	/* ----------------------------------------- CONTACT / SUPPORT------------------------------------------------ */

	public function page_contact()
	{
		if (!$this->is_admin() && !$this->is_superadmin()) {
			exit();
		}

		$this->set_title('Contact & Support');
        $this->render(array(
        ));
	}
	public function widget_contact()
	{
		$success = false; 

		$model = array(
			'subject' => array(
				'type' => 'VARCHAR',
				'max' => 255,
				'required' => true,
				'placeholder' => 'Sujet',
			),
			'message' => array(
				'type' => 'TEXT',
				'required' => true,
				'placeholder' => 'Message',
			),
		);
		$form = new Form($model);
		$form->factorize();
		$form->action($this->url('admin-widget-contact'))->method('POST')->ajax('none');
		$form->add_submit('Envoyer');

		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			if ($form->validate()) {

				$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();

				$email = Service::Email(array(
					'to' =>  _get('config.contact.email_contact'),
					'subject' => $this->request->post->subject,
					'message' => nl2br($this->request->post->message),
					'from_mail' => 'orangebleue@sh-mail.fr',
					'from_name' => $big_user->name.' - '.$big_user->contact_prename.' '.$big_user->contact_name,
					'response_mail' => $big_user->email,
					'provider' => 'self',
					'id_big_user' => $big_user->id,
					'save' => 0,
 				)); 

 				$success = $email->send();
 				Service::flash('Votre message a bien été envoyé');
			}
		}

		$this->render(array(
        	'form' => $form,
        	'success' => $success,
        ));
	}


	/* ----------------------------------------- CGU------------------------------------------------ */

	public function page_cgu()
	{
		$cgu_file = PREFIX . $this->request->zone . DS . 'views' . DS . 'params' . DS . 'cgu.phtml';
		ob_start();
		require_once $cgu_file;
		$content = ob_get_contents();
        ob_end_clean();

		$this->render(array(
			'content' => $content,
        ));
	}

	public function page_acceptcgu()
	{
		$this->set_title('Conditions générales d\'utilisation');

		$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();

		$model = array(
			'accept' => array(
				'type' => 'CHECKBOX',
				'label' => "",
				'skin' => 'smooth',
				'required' => true,
				'options' => array(
					'1' => "J'accepte les conditions générales d'utilisation",
				)
			),
		);
		$form = new \Core\Form($model);
		$form->action($this->url('admin-page-acceptcgu'));
		$form->factorize();

		if ($this->request->isPost()) {
			if (isset($this->request->post->accept)) {

				$big_user->cgu = 1;
				$big_user->save();
				Service::redirect($this->url('admin-page-clients'));
			}
		}

		$cgu_file = PREFIX . $this->request->zone . DS . 'views' . DS . 'params' . DS . 'cgu.phtml';
		ob_start();
		require_once $cgu_file;
		$content = ob_get_contents();
        ob_end_clean();

        $this->render(array(
        	'form' => $form,
        	'content' => $content,
        ));
	}


}
