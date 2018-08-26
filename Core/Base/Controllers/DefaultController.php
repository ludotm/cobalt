<?php 

namespace Core\Base\Controllers;

use Core\Page;
use Core\Service;

class DefaultController extends BaseController
{	 
	public function onDispatch() 
	{
		$this->set_layout('layout_default');
		//$this->set_base_title('Page');
	}

	public function json_remote_validation() {

		$post = $this->request->post;
		$id = $post->id;
		$table = $post->table;
		$field = $post->field;
		$value = $post->{$field};

		_model($table);
			
		$field_model = _get('models.'.$table.'.fields.'.$field);
		$primary_key = _get('models.'.$table.'.params.primary');

		$return = '';
		if (is_array($field_model) && array_key_exists('remote_validation', $field_model) && $primary_key) {
			$result = $this->db->query_one('SELECT * FROM '.$table.' WHERE '.$field.'=:'.$field.' ', array($field => $value));
			$return = $result && $result->{$primary_key} != $id ? 'false' : 'true';
		} 
		exit($return);
	}

	public function page_unsuscribe_email() 
	{
		$mail = $this->request->fromRoute('mail', null);
		$code = $this->request->fromRoute('code', null);

		$mailer = Service::Email();

		if (!$mail || !$code) {
			$this->launch_message_page ("L'opération n'a pu aboutir, paramètres manquants.", 'warning');
		} else {
			if ($mailer->unsuscribe_validation($mail, $code)) {
				
				$client = $this->db->select_one('clients')->where('email=:email', array('email'=>$mail))->execute();
				
				if ($client) {
					$client->stop_contact = 1;
					$success = $client->save();

					$this->db->insert('historique')->values(array(
						'id_client' => $client->id,
						'date' => date('Y-m-d'),
						'type' => 8,
						'id_user' => 0,
					))->execute();

				} else {

					$client = $this->db->select_one('_big_users')->where('email=:email', array('email'=>$mail))->execute();
					
					if ($client) {
						$client->stop_contact = 1;
						$success = $client->save();
					} else {
						$success = false;
					}
				}

				if ($success) {
					$this->launch_message_page ("Votre demande a bien été prise en compte.", 'success');		
				} else {
					$this->launch_message_page ("Erreur lors du traitement de vote demande, l'opération n'a pas été prise en compte.", 'error');
				}
			} else {
				$this->launch_message_page ("Erreur lors du traitement de vote demande, l'opération n'a pas été prise en compte.", 'error');
			}
		}
	}

	public function widget_form_email() 
	{
		$email = $this->request->fromRoute('email', null);

		$show_tarif = true;

		if (!$email && !$this->request->isPost()) {
			Service::error('Aucune adresse email passée en paramètre');
		}

		if ($this->session->get('id_big_user') != 0) {
			$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();
			$can_use_spothit = $big_user->can_use_spothit();
		} else {
			$can_use_spothit = true;
		}

		$model = array(
			'send_to' => array(
				'type' => 'HIDDEN',
				'value' => $email,
			),
			'from_name' => array(
				'type' => 'VARCHAR',
				'label' => 'Nom de l\'expéditeur *',
				'required' => true,
			),
			'from_mail' => array(
				'type' => 'EMAIL',
				'label' => 'Email de l\'expéditeur *',
				'required' => true,
			),
			'subject' => array(
				'type' => 'VARCHAR',
				'max' => 255,
				'label' => 'Sujet *',
				'required' => true,
			),
			'message' => array(
				'type' => 'TEXT',
				'label' => 'Message *',
				'required' => true,
			),
			'provider' => array(
				'type' => 'RADIO',
				'label' => 'Mode d\'envoi',
				'required' => true,
				'skin' => 'smooth',
				'options' => array(
					'self' => 'Basic',
				),
				'value' => 'self',
			),
		);

		if ($can_use_spothit) {
			$model['provider']['options']['spothit'] = 'Premium';
		}

		$form = new \Core\Form($model);
		$form->action('/form_email')->method('POST')->ajax('none');
		$form->factorize();
		$form->add_submit('Envoyer');

		$mail_sent = false;

		if ($this->request->isPost()) {

			$email = $this->request->post->send_to;

			$form->bind($this->request->post);

			if ($form->validate()) {

				$data = $form->get_data();
				$data['response_mail'] = $data['from_mail'];

				if ($this->is_superadmin() || $this->request->zone == 'superadmin' ) {
					$data['id_big_user'] = 0;	
				}

				$mailer = Service::Email($data);
				$mailer->send();
				$mail_sent = true;
			}
		}

		$this->render(array(
			'mail_sent' => $mail_sent,
			'email' => $email,
			'form' => $form,
			'show_tarif' => $show_tarif,
			'can_use_spothit' => $can_use_spothit,
        ));
	}

	public function widget_form_sms() 
	{
		$num = $this->request->fromRoute('num', null);

		$show_tarif = true;

		if (!$num && !$this->request->isPost()) {
			Service::error('Aucun numéro passé en paramètre');
		}

		if ($this->session->get('id_big_user') != 0) {
			$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();
			$can_use_spothit = $big_user->can_use_spothit();
		} else {
			$can_use_spothit = true;
		}
		
		if (!$can_use_spothit) {
			Service::error('Vous devez être abonné pour envoyer des SMS');
		}

		$model = array(
			'send_to' => array(
				'type' => 'HIDDEN',
				'value' => $num,
			),
			'message' => array(
				'type' => 'SMS',
				'label' => 'Message *',
				'required' => true,
			),
			'from_num' => array(
				'type' => 'VARCHAR',
				'label' => 'Numéro de l\'expéditeur (optionnel)',
			),
			'provider' => array(
				'type' => 'RADIO',
				'label' => 'Mode d\'envoi',
				'required' => true,
				'skin' => 'smooth',
				'options' => array(
					'spothit_lowcost' => 'Basic',
					'spothit_prenium' => 'Premium',
				),
				'value' => 'spothit_lowcost',
			),
		);

		$form = new \Core\Form($model);
		$form->action('/form_sms')->method('POST')->ajax('none');
		$form->factorize();
		$form->add_submit('Envoyer');

		$sms_sent = false;
		$too_many_chars = false;

		if ($this->request->isPost()) {

			$num = $this->request->post->send_to;

			$form->bind($this->request->post);

			if ($form->validate()) {

				$data = $form->get_data();
				
				if ($this->is_superadmin() || $this->request->zone == 'superadmin') {
					$data['id_big_user'] = 0;	
				}

				$smser = Service::Sms($data);


				$nb_sms = $smser->get_sms_nb_concat($smser->get_sms_nb_chars($data['message']));

				if ($data['provider'] == 'spothit_lowcost') {
					$too_many_chars = $nb_sms > 1 ? true : false ;
					
					if ($too_many_chars) {
						Service::flash('Le mode Basic ne permet pas d\'utiliser plus de 160 caractères', 'error', true);
					}

				} else if ($data['provider'] == 'spothit_premium') {
					$too_many_chars = $nb_sms > 5 ? true : false ;

					if ($too_many_chars) {
						Service::flash('Le nombre de caractère est limité à 785', 'error', true);
					}

				}	

				if (!$too_many_chars) {
					//$smser->send();
					$sms_sent = true;
				}
			}
		}

		$this->render(array(
			'sms_sent' => $sms_sent,
			'num' => $num,
			'form' => $form,
			'show_tarif' => $show_tarif,
			'too_many_chars' => $too_many_chars,
        ));
	}

	protected function launch_message_page ($msg, $type_msg)
	{
		$this->set_layout('layout_message');
		$this->set_template('message');

		$this->render(array(
			'msg' => $msg,
			'type_msg' => $type_msg,
        ));
	}

	public function page_default() {
		
		$this->render(array(

        ));
	}

	public function widget_default(){

		$this->render(array(
		
        ));
	}
}
?>