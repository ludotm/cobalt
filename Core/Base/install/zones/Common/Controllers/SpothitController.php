<?php 

namespace Common\Controllers;

use \Core\Service;
use \Core\Api\Spothit;

class SpothitController extends BaseController 
{
	protected $secret_status;
	protected $secret_stop;

	public function onDispatch()
	{
		$this->secret_status = 'x7je9zm0za1';
		$this->secret_stop = 'o7g5due8z4d';
	}

	public function json_push_status()
	{	
		// protection par mot de passe
		$secret = $this->request->fromRoute('code', null);
		if (!$secret || $secret != $this->secret_status) {
			$this->render(array(
				'response' => 'Mauvais mot de passe',
			));
			exit();
		}

		/*
		id_accuse	Identifiant unique du message pour ce numéro.
		id_message	Identifiant commun en cas d'envoi d'un message groupé.
		numero	Optionnel, numéro de téléphone du destinataire (uniquement si message de type SMS, Vocal, FAX).
		email	Optionnel, e-mail du destinataire (uniquement si message de type e-mail).
		statut	Statut du message SMS :
		0 = En attente
		1 = Livré
		2 = Envoyé
		3 = En cours
		4 = Echec
		5 = Expiré
		(Les statuts 1 et 4 sont définitifs.)

		Statut du message E-mail :
		0 = En attente
		2 = Envoyé
		3 = Cliqué
		4 = Erreur
		5 = Bloqué
		6 = Spam
		7 = Desabonné
		8 = Ouvert
		date_envoi	Date d'envoi du message (timestamp).
		date_update	Date de dernière mise à jour du statut (timestamp).
		statut_code	Statut détaillé de 0 à 9999 (détails à demander à votre gestionnaire de compte).
		nom	Le nom ou l'identifiant personnel de votre message

		*/
		$data = array();
		$data['id_common_provider'] = $this->request->fromRoute('id_message', 0);
		$data['id_unique_provider'] = $this->request->fromRoute('id_accuse', 0);
		
		$numero = $this->request->fromRoute('numero', '');
		$email = $this->request->fromRoute('email', '');
		if ($numero != '') {
			$data['type'] = 'SMS';
		} else if ($email != '') {
			$data['type'] = 'MAIL';
		}

		$data['status'] = $this->request->fromRoute('statut', 0);
		$data['timestamp'] = $this->request->fromRoute('date_envoi', 0);

		// inutilisé pour l'instant
		$date_update = $this->request->fromRoute('date_update', 0);
		$statut_code = $this->request->fromRoute('statut_code', 0);
		$nom = $this->request->fromRoute('nom', 0);

		if (!empty($data)) {

			if (is_numeric($data['id_unique_provider']) && $data['id_unique_provider'] != 0) {
				$check_exist = $this->db->query('SELECT * FROM _spothit_status WHERE id_unique_provider="'.$data['id_unique_provider'].'"');
			} else {
				$check_exist = false;
			}
			
			if ($check_exist) {

				$success = $this->db->update('_spothit_status')->values($data)->where(array('id_unique_provider'=>$data['id_unique_provider']))->execute();
			} else {
				$success = $this->db->insert('_spothit_status')->values($data)->execute();	
			}
			switch ($data['type']) {
				case 'MAIL':
					if (is_numeric($data['timestamp']) && is_numeric($data['id_common_provider']) && $data['id_common_provider'] != 0) {
						$this->db->query('UPDATE _emails SET timestamp="'.$data['timestamp'].'" WHERE id_common_provider="'.$data['id_common_provider'].'" AND timestamp="1"');
					}
					// Mauvais Email
					if ($data['status'] == 4) {
						$client = $this->db->select_one('clients')->where('email=:email', array('email'=>$email))->execute();
						if ($client) {
							$client->bad_email = 1;
							$client->save();	
						}
					}
					break;
				case 'SMS':
					if (is_numeric($data['timestamp']) && is_numeric($data['id_common_provider']) && $data['id_common_provider'] != 0) {
						$this->db->query('UPDATE _sms SET timestamp="'.$data['timestamp'].'" WHERE id_common_provider="'.$data['id_common_provider'].'" AND timestamp="1"');
					}
					// Mauvais NUméro
					if ($data['status'] == 4) {
						$client = $this->db->select_one('clients')->where('phone=:phone', array('phone'=>$numero))->execute();
						if ($client) {
							$client->bad_phone = 1;
							$client->save();	
						}
					}
					break;
				case 'MMS':
					break;
				case 'VOCAL':
					break;
			}
		}
		$this->render(array(
			'response' => ( $success > 0 ? true : false ),
		));
	}

	public function json_push_stop()
	{
		// protection par mot de passe
		$secret = $this->request->fromRoute('code', null);
		if (!$secret || $secret != $this->secret_stop) {
			$this->render(array(
				'response' => 'Mauvais mot de passe',
			));
			exit();
		}

		/*
		id	Identifiant unique
		numero	Numéro de téléphone de l'émetteur
		date_envoi	Timestamp date d'envoi de la réponse
		source_id	Identifiant unique du message source
		*/
		$success = 0;

		$numero = $this->request->fromRoute('numero', '');
		$email = $this->request->fromRoute('email', '');
		if ($numero != '') {

			$numero = str_replace(array(' ','+33'), array('','0'), $numero );

			$client = $this->db->select_one('clients')->where('phone=:phone', array('phone'=>$numero))->execute();
			if ($client) {
				
				$client->stop_contact = 1;
				$client->save();

				$this->db->insert('historique')->values(array(
					'id_client' => $client->id,
					'date' => date('Y-m-d'),
					'type' => 8,
					'id_user' => 0,
				))->execute();
			} else {

				$client = $this->db->select_one('clients')->where('phone=:phone', array('phone'=>$numero))->execute();
				if ($client) {
				
					$client->stop_contact = 1;
					$client->save();
				} else {
					$success = false;
				}
			}
			
		} else if ($email != '') {

			$client = $this->db->select_one('clients')->where('email=:email', array('email'=>$email))->execute();
			if ($client) {
				
				$client->stop_contact = 1;
				$client->save();

				$this->db->insert('historique')->values(array(
					'id_client' => $client->id,
					'date' => date('Y-m-d'),
					'type' => 8,
					'id_user' => 0,
				))->execute();
			} else {

				$client = $this->db->select_one('_big_users')->where('email=:email', array('email'=>$email))->execute();
				
				if ($client) {
					$client->stop_contact = 1;
					$client->save();
				} else {
					$success = false;	
				}
			}
		}

		$this->render(array(
			'response' => ( $success > 0 ? true : false ),
		));
	}
}