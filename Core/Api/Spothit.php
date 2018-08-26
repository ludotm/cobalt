<?php 

namespace Core\Api;

use Core\Service;
use Core\Curl;

/*
	DOCUMENTATION URL : http://www.spot-hit.fr/documentation-api
*/

class Spothit
{
	use \Core\Singleton;

	protected $db;
	protected $request;
	protected $session;

	protected $api_key; 
	protected $ref_client; 
	protected $curl;

	public $base_url = 'https://www.spot-hit.fr/';

	public function __construct($id_big_user=0) 
	{
		$this->db = Service::Db();
		$this->request = Service::Request();
		$this->session = Service::Session();

		$this->curl = new Curl();

		if ($id_big_user) {
			$this->get_api_key($id_big_user);
		} else {
			$this->api_key = _config('spothit.api_key', null);
		}
		$this->ref_client = _config('spothit.ref_client', null);
	}

	/* ---------------------------------- FONCTION REQUTE --------------------------------------------- */

	public function get_api_key($id_big_user)
	{
		$account = $this->db->select_one('_spothit_accounts')->where(array('id_big_user'=>$id_big_user))->execute();
		
		if (!$account) {
			Service::error("Aucune clé API n'est enregistrée pour l'utilisateur : ".$id_big_user);
		}
		$this->set_api_key($account->api_key);
	}

	public function set_api_key($api_key)
	{
		$this->api_key = $api_key;
	}

	public function call ($request_type, $endpoint, $data=array()) 
	{
		$url = $this->base_url . $endpoint;

		$data['key'] = $this->api_key;

		if ($request_type == 'post' || $request_type == 'put') {
			$response = $this->curl->{$request_type}($url, $data);
		} else {
			$response = $this->curl->{$request_type}($url);
		}

		$response = json_decode($response, true);

		if (!isset($response['resultat'])) {
			return $response;
		} else if($response['resultat']) {
		    return $response;
		} else {
			if (is_array($response['erreurs'])) {
				$response['erreurs'] = implode(',',$response['erreurs']);
			}
			Service::error('Spot Hit errors : ' . $response['erreurs']. '(Endpoint: '.$endpoint.')');
		}
	}

	/* ---------------------------------- SMS --------------------------------------------- */

	public function send_quick_sms($message, $dest, $type, $expeditor=false, $campain_name=false) 
	{
		$data = array(
			'destinataires' => $dest,
		    'type' => $type,
		    'message' => $message,
		    'expediteur' => $expeditor,
		);
		if ($expeditor) {
			$data['expediteur'] = $expeditor;
		}
		if ($campain_name) {
			$data['nom'] = $campain_name;
		}
		return $this->send_sms($data);
	}

	public function send_sms($data) 
	{
		/*
		CHAMPS POSSIBLES 
		type : lowcost ou premium (obligatoire)
		message : obligatoire
		destinataires : numéro de téléphone des destinataires (string séparé par ,l ou array)
		expediteur: premium uniquement, 11 caractères esapces inclus (chaine de caractère ou numéro de tel) (non obligatoire)
		date : (timestamp) date d'envoi programmée, instantané si vide
		smslong : mis auomatiquement à 1 si besoin, 1 ou 0, seulement pour premium, autorise ou non l'envoi de sms de plus de 153 caractères, maximum 5 sms concaténés (soit 765 caractères), 160 car + 153 par nouveau sms
		 	caractères "|", "^", "€", "}", "{", "[", "~", "]" et "\" comptent doubles
		smslongnbr : int, nombre de sms concaténés calculé en interne, message rejeté si calcul côté spot hit non identique
		tronque : 1 ou 0, tronque le sms si plus de 160 caractères
		encodage : si "auto" encode le message en UTF8 coté spot hit, mais vaut mieux encoder en interne
		nom : Nom de la campagne (non visible pour l'utilisateur')
		url : url pour les retours push 
		*/
		if (!array_key_exists('destinataires', $data)) {
			Service::error('Fonction send_sms : champs destinataire manquant');
		}
		if (!array_key_exists('type', $data)) {
			Service::error('Fonction send_sms : champs type manquant');
		}
		if (!array_key_exists('message', $data)) {
			Service::error('Fonction send_sms : champs message manquant');
		}

		if ($data['destinataires'] == '' || $data['destinataires'] == array() || !$data['destinataires']) {
			Service::error('Fonction send_sms : champs destinataire vide');
		}
		if ($data['type'] != 'lowcost' && $data['type'] != 'premium') {
			Service::error('Fonction send_sms : champs type non valide (lowcost ou premium)');
		}
		if ($data['message'] == '') {
			Service::error('Fonction send_sms : champs type non valide (lowcost ou premium)');
		}

		$nb_sms = $data['smslongnbr'];

		if ($data['type'] != 'premium' && $nb_sms > 1) {
			Service::error('Fonction send_sms : Impossible d\'envoyer un SMS de plus de 153 caractères en mode low cost');
		
		} else if ($nb_sms > 1) {
			if (!array_key_exists('tronque', $data)) {
				$data['smslong'] = 1;
			} else if ($data['tronque'] = 1) {
				$data['smslong'] = 0;
			}
		}

		if ($nb_sms > 5) {
			Service::error('Fonction send_sms : Impossible d\'envoyer un SMS de plus de 765 caractères');
		}
		
		if (is_array($data['destinataires'])) {
			$data['destinataires'] = implode(',', $data['destinataires']);
		} else {
			str_replace(';',',',$data['destinataires']);
		}

		if (array_key_exists('expediteur', $data)) {
			if ($data['type'] != 'premium') {
				unset($data['expediteur']);
			} else if (strlen($data['expediteur']) > 11) {
				Service::error('Fonction send_sms : le champs "expediteur" ne doit pas dépasser 11 caractères');
			}
		}

		//$data['url']

		$response = $this->call('post', 'api/envoyer/sms', $data);
		return $response;
	}

	
	/* ---------------------------------- MAIL --------------------------------------------- */

	public function send_mail($data) 
	{
		/*
		CHAMPS POSSIBLES 
		sujet : obligatoire sujet
		message : obligatoire format html
		destinataires : emails des destinataires (string séparé par , ou ; ou array)
		expediteur: email de l'expediteur, choisir domaine en votreentreprise@sh-mail.fr pour une meilleure delivrabilité
		nom_expediteur : Nom complet de l'expediteur
		email_reponse : optionnel, email de redirection des reponses
		date : timestamp, date d'envoi, immédiat si non précisé
		nom : Nom de la campagne (non visible pour l'utilisateur)
		*/
		if (!array_key_exists('destinataires', $data)) {
			Service::error('Fonction send_mail : champs destinataire manquant');
		}
		if (!array_key_exists('sujet', $data)) {
			Service::error('Fonction send_mail : champs sujet manquant');
		}
		if (!array_key_exists('message', $data)) {
			Service::error('Fonction send_mail : champs message manquant');
		}
		if (!array_key_exists('expediteur', $data)) {
			Service::error('Fonction send_mail : champs expediteur manquant');
		}
		if (!array_key_exists('nom_expediteur', $data)) {
			Service::error('Fonction send_mail : champs nom_expediteur manquant');
		}

		if ($data['destinataires'] == '' || $data['destinataires'] == array() || !$data['destinataires']) {
			Service::error('Fonction send_mail : champs destinataire vide');
		}
		if ($data['sujet'] == '') {
			Service::error('Fonction send_mail : champs sujet vide');
		}
		if ($data['message'] == '') {
			Service::error('Fonction send_mail : champs message vide');
		}
		if ($data['expediteur'] == '') {
			Service::error('Fonction send_mail : champs expediteur vide');
		}
		if ($data['nom_expediteur'] == '') {
			Service::error('Fonction send_mail : champs nom_expediteur vide');
		}

		if (!\Core\Tools::is_email($data['expediteur'])) {
			Service::error('Fonction send_mail : le champs expediteur n\'est pas une adresse email valide');
		}
		if (array_key_exists('email_reponse', $data)) {
			if (!\Core\Tools::is_email($data['email_reponse'])) {
				Service::error('Fonction send_mail : le champs email_reponse n\'est pas une adresse email valide');
			}
		}
		
		if (is_array($data['destinataires'])) {
			$data['destinataires'] = implode(',', $data['destinataires']);
		} else {
			str_replace(';',',',$data['destinataires']);
		}

		$response = $this->call('post', 'api/envoyer/e-mail', $data);
		return $response;
	}


	/* ---------------------------------- MESSAGES VOCAUX --------------------------------------------- */

	public function send_vocal_message($data) 
	{
		/*
		CHAMPS POSSIBLES 
		type : "direct_repondeur" ou "appels"
		message : Identifiant du message audio délivré grâce à l'API d'importation de fichier audio
		destinataires : numéro de téléphone des destinataires (string séparé par ,l ou array)
		expediteur: optionnel, numéro de téléphone valide de l'expediteur, numéro inconnu si vide
		date : timestamp, date d'envoi, immédiat si non précisé
		texte : message à vaocaliser à partir d'un texte (si renseigner, le champs message doit etre vide)
		voix : voix à utiliser pour la vocalisation ("Philippe", "Agnes", "Damien", "Eva", "Helene", "John", "Loic" )
		nom : nom de la campagne (non visible par l'utilisateur)
		url : url push à appeler lors des changement de statut

		// options avancées
		fixe : Optionnel (uniquement pour Appels) Si égal à "0", ignore les numéros de téléphones fixes.
		detection_repondeur : Optionnel (uniquement pour Appels) Si égal à "1" et le cas où l'appel est décroché par un répondeur, ce paramètre permet d'activer le dépot sur répondeur.
		reecoute : Optionnel (uniquement pour Appels) Si égal à "1" rajoute à la fin de votre message un choix permettant au destinataire de réécouter votre message.
		mise_relation : Optionnel (uniquement pour Appels) Numéro de téléphone valide. Permet au destinataire d'être mis en relation avec le numéro spécifié.
		boite_vocale : Optionnel (uniquement pour Appels) Si égal à "1", rajoute à la fin de votre message une boîte vocale permettant au destinataire de vous laisser un message.
		stop : Optionnel (uniquement pour Appels) Si égal à "1", rajoute à la fin de votre message un choix permettant au destinataire de s'opposer à la réception de messages de votre part.
		*/
		if (!array_key_exists('destinataires', $data)) {
			Service::error('Fonction send_vocal_message : champs destinataire manquant');
		}
		if (!array_key_exists('type', $data)) {
			Service::error('Fonction send_vocal_message : champs type manquant');
		}
		if (!array_key_exists('message', $data)) {
			Service::error('Fonction send_vocal_message : champs message manquant');
		}

		if ($data['type'] != 'direct_repondeur' && $data['type'] != 'appels') {
			Service::error('Fonction send_vocal_message : champs type doit prendre les valeurs "direct_repondeur" ou "appels"');
		}
		if ($data['destinataires'] == '' || $data['destinataires'] == array() || !$data['destinataires']) {
			Service::error('Fonction send_vocal_message : champs destinataire vide');
		}
		if ($data['message'] == '' && !array_key_exists('texte', $data)) {
			Service::error('Fonction send_vocal_message : le champs message ne peut être vide que si le champs texte est renseigné');
		}
		
		if (is_array($data['destinataires'])) {
			$data['destinataires'] = implode(',', $data['destinataires']);
		} else {
			str_replace(';',',',$data['destinataires']);
		}

		$response = $this->call('post', 'api/envoyer/vocal', $data);
		return $response;
	}

	public function import_vocal_message($file) 
	{
		$response = $this->call('post', 'api/vocal/upload', array('fichier'=>$file));
		return $response;
	}

	/* ---------------------------------- MMS --------------------------------------------- */

	public function send_mms($data) 
	{
		/*
		CHAMPS POSSIBLES 
		fichier : Identifiant du visuel délivré grâce à l'API d'importation de visuel MMS
		destinataires : numéro de téléphone des destinataires (string séparé par ,l ou array)
		sujet : optionnel, sujet du message
		message : optionnel, limité à 10 000 caractères
		date : (timestamp) date d'envoi programmée, instantané si vide
		nom : Nom de la campagne (non visible pour l'utilisateur')
		*/
		if (!array_key_exists('destinataires', $data)) {
			Service::error('Fonction send_mms : champs destinataire manquant');
		}
		if (!array_key_exists('fichier', $data)) {
			Service::error('Fonction send_mms : champs fichier manquant');
		}

		if ($data['destinataires'] == '' || $data['destinataires'] == array() || !$data['destinataires']) {
			Service::error('Fonction send_mms : champs destinataire vide');
		}
		if ($data['fichier'] == '' || !$data['fichier']) {
			Service::error('Fonction send_mms : champs fichier non valide');
		}

		if (array_key_exists('message', $data)) {
			$msg_nb_chars = $this->get_sms_nb_chars($data['message']);
			if ($msg_nb_chars >= 10000) {
				Service::error('Fonction send_mms : Le message ne peut contenir plus de 10 000 caractères');
			}
		}

		if (is_array($data['destinataires'])) {
			$data['destinataires'] = implode(',', $data['destinataires']);
		} else {
			str_replace(';',',',$data['destinataires']);
		}

		$response = $this->call('post', 'api/envoyer/mms', $data);
		return $response;
	}

	public function import_visuel_mms($file) 
	{
		$response = $this->call('post', 'api/mms/upload', array('fichier'=>$file));
		return $response;
	}

	/* ------------------------ ACCUSES DE RECEPTION -------------------------------------- */

	public function accuse_reception($id_message)
	{
		$response = $this->call('post', 'manager/inc/actions/liste_accuses.php', array('id'=>$id_message));
		return $response;
	}
	public function maj_accuse_reception($id_message)
	{
		/*
		STATUTS
		1 = Envoyé et bien reçu
		2 = Envoyé et non reçu
		3 = En cours
		4 = Echec
		5 = Expiré
		(Les statuts 1, 4 et 5 sont définitifs.)
		*/
		$response = $this->accuse_reception($id_message);

		foreach ($response['resultat'] as $result) {
			$phone = $result[0];
			$status = $result[1];
			$this->db->update('_sms')->values(array('status'=>$status))->where(array('id'=>$id_message, 'phone'=>$phone))->execute();
		}
	}

	/* ------------------------ LISTING DES MESSAGES -------------------------------------- */

	public function get_messages($date_debut=false, $date_fin=false, $start=false, $limit=false)
	{
		// $date_debut et $date_fin sous forme de timestamp
		$data = array();
		if ($date_debut) {
			$date_debut = !strtotime($date_debut) ? $date_debut : strtotime($date_debut);
			$data['date_debut'] = $date_debut;
		}
		if ($date_fin) {
			$date_fin = !strtotime($date_fin) ? $date_fin : strtotime($date_fin);
			$data['date_fin'] = $date_fin;
		}
		if ($limit) {
			$data['limit'] = $limit;
		}
		if ($start) {
			$data['start'] = $start;
		}

		$response = $this->call('post', 'manager/inc/actions/liste_messages.php', $data);
		return $response;
	}
	public function get_one_message($id)
	{
		$response = $this->call('post', 'manager/inc/actions/liste_messages.php', array('id'=>$id));
		return $response;
	}

	/* ------------------------ STATISTIQUES -------------------------------------- */

	public function get_stats($date_debut=false, $date_fin=false, $subaccount=1)
	{
		// $date_debut et $date_fin sous forme de timestamp
		$data = array(
			'sous_compte' => $subaccount,
		);

		if ($date_debut) {
			$date_debut = !strtotime($date_debut) ? $date_debut : strtotime($date_debut);
			$data['date_debut'] = $date_debut;
		}
		if ($date_fin) {
			$date_fin = !strtotime($date_fin) ? $date_fin : strtotime($date_fin);
			$data['date_fin'] = $date_fin;
		}

		$response = $this->call('post', 'api/statistiques', $data);

		$stats = array(
			'email_premium' => $response['statistiques']['html'],
			'sms_basic' => $response['statistiques']['lowcost'],
			'sms_premium' => $response['statistiques']['premium'],
		); 
		return $stats;
	}

	/* ------------------------ SOUS COMPTES -------------------------------------- */

	public function add_subaccount($client)
	{
		$checkExist = $this->db->select_one('_spothit_accounts')->where(array('id_big_user'=>$client->id))->execute();

		if ($checkExist) {
			return $checkExist;
		}
		$data = array(
			'commercial' => $this->ref_client, // reférence spothit du compte client principal
			'nom' => $client->contact_name,
			'prenom' => $client->contact_prename,
			'email' => $client->email,
			'nom_entreprise' => $client->name,
			'motdepasse' => 'f7u3elsa9',
		);

		$response = $this->call('post', 'manager/inc/actions/inscription.php', $data);

		$new_entry = array(
			'id_big_user' => $client->id,
			'id_provider' => $response['id'],
			'api_key' => $response['key'],
		);

		$new_id = $this->db->insert('_spothit_accounts')->values($new_entry)->execute();

		$account = $this->db->select('_spothit_accounts')->id($new_id)->execute();
		return $account;
	}
	public function update_subaccount($client_id_provider, $key, $value)
	{
		$data = array(
			'client' => $client_id_provider,
			'element' => $key,
			'valeur' => $value,
		);

		$response = $this->call('post', 'api/client/set', $data);
	}
	/* ------------------------ CHANGER LES URL PUSH -------------------------------------- */

	public function change_push_url($type, $url)
	{
		if ($type != 'accuses' && $type != 'stops' && $type != 'reponses') {
			Service::error('Fonction change_push_url : champs type doit prendre les valeurs "accuses" ou "stops" ou "reponses"');
		}

		$data = array();
		$data[$type] = $url;

		$response = $this->call('post', 'manager/inc/actions/modifier_urls.php', $data);
		return $response;
	}

	/* ------------------------ REQUETES PUSH -------------------------------------- */

	public function push_status () 
	{

	}

	public function push_stop () 
	{
		
	}
}

?>