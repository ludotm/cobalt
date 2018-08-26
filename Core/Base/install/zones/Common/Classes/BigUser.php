<?php 

namespace Common\Classes;

use \Core\Service;
use \Core\Entity;

class BigUser extends Entity
{
	public $spothit_account;
	public $stripe_account;
	public $current_pack; // pack en cours, applicable
	public $active_modules; // Modules disponibles pour le client d'après son abonnement
	public $all_modules; // Tous les modules 
	public $current_abonnement; // current_pack, sous forme d'entity SQL complète
	public $abonnement_status; // statut précis de l'abonnement

	/* ----------------------------- SPOTHIT ------------------------------ */

	public function have_spothit_account() 
	{
		if (!$this->spothit_account && $this->spothit_account !== false) {
			$account = $this->db->select_one('_spothit_accounts')->where(array('id_big_user'=>$this->id))->execute();
			$this->spothit_account = $account ? $account->api_key : false;
		}
		
		return $this->spothit_account;
	}

	public function can_use_spothit() 
	{
		return $this->have_abonnement() && $this->have_spothit_account() ? true : false ;
	}	

	/* ----------------------------- STRIPE ------------------------------ */

	public function get_stripe()
	{
		if (!$this->stripe_account && $this->stripe_account !== false) {
			$account = $this->db->select_one('_stripe_accounts')->where(array('id_big_user'=>$this->id))->execute();
			$this->stripe_account = $account ? $account : false;
		}
		return $this->stripe_account;
	}

	public function have_stripe_account() 
	{
		$this->get_stripe();
		return $this->stripe_account ? true : false;
	}

	public function get_stripe_card_status() 
	{
		return $this->have_stripe_account() ? $this->stripe_account->card_status : null;
	}

	public function get_stripe_card_status_label() 
	{
		$options = _get('models._stripe_accounts.fields.card_status.options');
		$card_status = $this->get_stripe_card_status();
		$card_status = !$card_status ? 0 : $card_status ;
		return $options[$card_status];
	}

	public function have_valid_card() 
	{
		$card_status = $this->get_stripe_card_status();
		return $card_status == 1 ? true : ($card_status === null ? null : false );
	}

	public function mark_card_as_invalid() 
	{
		$account = $this->get_stripe();
		if ($account) {
			$account->card_status = 2;
			$account->save();
		}
	}

	/* ----------------------------- ABONNEMENT, PACKS, PERIODE ESSAI, CONTRAT ------------------------------ */

	public function is_in_trial() 
	{
		return $this->get_abonnement_status() == 2 ? true : false;
	}

	public function have_abonnement()  
	{
		return !$this->get_current_pack() ? false : true;
	}	

	public function create_contract($pack, $options, $date=null) 
	{
		$date = !$date ? date('Y-m-d') : $date ;
		$this->date_contract = $date;
		$this->save();

		// pack et options
		$price = 0;
		$options = !is_array($options) ? explode(',',$options) : $options;
		$formule = $this->get_pack_info($pack, 'shortname');
		$price += $this->get_pack_info($pack, 'price');
		if (!empty($options) && $options[0] != '') {
			$formule .= " + option(s)";
			foreach ($options as $option_key => $option) {
				$formule .= ", ".$this->get_option_info($option, 'shortname');
				$price += $this->get_option_info($option, 'price');
			}
		} 
		
		$pdf = new \Common\Pdf\Contrat();
		$pdf->add_vars(array(
			'society' => $this->society,
			'type_society' => $this->type_society,
			'address' => $this->address,
			'cp' => $this->cp,
			'town' => $this->town,
			'rcs_town' => $this->rcs_town,
			'rcs' => $this->rcs,
			'contact_prename' => $this->contact_prename,
			'contact_name' => $this->contact_name,
			'date_start' => $date,
			'formule' => $formule,
			'price' => $price,
		));
		$contrat_link = $this->get_contrat_link(false);
		$pdf->save($contrat_link);
	}

	public function get_contrat_link($check_file_exist=true) 
	{
		if ($this->date_contract != '0000-00-00') {
			$date = date('Y_m_d', strtotime($this->date_contract));
			$file = "files/contrats/".$this->id."/contrat_wifid_".$date.".pdf";

			if ($check_file_exist) {
				if (file_exists($file)) {
					return $file;
				}
			} else {
				return $file;
			}
		} 
		return null;
	}

	public function get_facture_link($ref, $check_file_exist=true) 
	{
		$file = "files/factures/".$this->id."/".$ref.".pdf";

		if ($check_file_exist) {
			if (file_exists($file)) {
				return $file;
			}
		} else {
			return $file;
		}
		return null;
	}

	/* ------------------------------------- RECUPERE LE PACK EN COURS APPLICABLE -------------------------------------------- */

	public function get_current_pack ($return_entry=false)  
	{
		if ($this->current_pack !== null) {
			return $return_entry ? $this->current_abonnement : $this->current_pack;
		}

		$this->current_abonnement = $this->get_abonnement (true, null, date('Y-m-d'), true);

		if ($this->current_abonnement) { // si il y a un pack en cours, on vérifie si le moyen de paiement est valide

			$this->current_abonnement->options = explode(',', $this->current_abonnement->options);
			$this->current_pack = $this->current_abonnement->value;

			if (!$this->have_valid_card()) { // Si pas de carte valide, on poursuit l'abonnement pour 10 jours 

				$after_invalid_card = _get('config.abonnement_params.after_invalid_card');

				if (!$this->stripe_account || \Core\Tools::days_left($this->stripe_account->date_update, strtotime("-".$after_invalid_card)) >= 0) { // si 10 jours dépassé on ferme
					
					$this->current_pack = false;
					$this->current_abonnement = false;
				} 
			} 
		} else {
			$this->current_pack = null;
		} 

		return $return_entry ? $this->current_abonnement : $this->current_pack;
	}

	public function get_active_modules ()  
	{
		if ($this->active_modules !== null) {
			return $this->active_modules;
		}

		$packs = _config('packs');
		$options = _config('packs_options');
		$full_modules = $this->get_all_modules();

		$current_pack = $this->get_current_pack(true);

    	if ($current_pack) {

    		// si abonné
        	if (array_key_exists($current_pack->value, $packs)) { 	

        		$this->active_modules = $packs[$current_pack->value]['modules'];

        		if (!empty($current_pack->options) && $current_pack->options[0] != '') {
        			$current_options = explode(',', $current_pack->options);	
        			$this->active_modules = array_merge($this->active_modules, $current_options);
        		}
        		return $this->active_modules;
        	} 

        // si période d'essai
    	} else if (date('Y-m-d') >= $this->start_trial && date('Y-m-d') <= $this->end_trial) { 
        		
    		$this->active_modules = $full_modules;
    		return $this->active_modules;
    	}

    	$this->active_modules = array();

    	return $this->active_modules;
	}


	/* ------------------------------------- INFOS SUR LE PACK EN COURS -------------------------------------------- */

	public function get_pack_info($id_pack=null, $var=null)  
	{
		$id_pack = !$id_pack ? $this->get_current_pack() : $id_pack;

		if ($id_pack) {

			$packs = _config('packs');
			$packs[$id_pack]['id_pack'] = $packs[$id_pack]['id'] = $id_pack;
			$packs[$id_pack]['complete_price'] = str_replace('.',',', $packs[$id_pack]['price'] ).'0 € HT/mois';
			
			return $var ? $packs[$id_pack][$var] : $packs[$id_pack];
		}
		return null;
	}

	public function get_option_info($id_option, $var=null)  
	{
		if ($id_option) {

			$options = _config('packs_options');
			$options[$id_option]['id_option'] = $options[$id_option]['id'] = $id_option;
			$options[$id_option]['complete_price'] = str_replace('.',',', $options[$id_option]['price'] ).'0 € HT/mois';
			
			return $var ? $options[$id_option][$var] : $options[$id_option];
		}
		return null;
	}

	public function get_all_modules()  
	{
		if ($this->all_modules !== null) {
			return $this->all_modules;
		}

		$packs = _config('packs');
		$options = _config('packs_options');
		
		$full_modules = array();

		foreach ($packs as $key => $pack) {
			$full_modules = count($pack['modules']) > count($full_modules) ? $pack['modules'] : $full_modules ;
		}
		if ($options) {
			foreach ($options as $key => $option) {
				$full_modules []= $key;
			}
		}

		$this->all_modules = $full_modules;
		return $this->all_modules;
	}

	/* ------------------------------------- ENREGSITRE UN ABONNEMENT -------------------------------------------- */

	public function save_abonnement($id_pack, $options='', $date=null)  
	{
		$packs = _config('packs');

		// Parrainage si premier abonnement
		if ($id_pack != 'pack0') {
			$is_first_pack = $this->get_abonnement(true, null, date('Y-m-d')) ? false : true;
			if ($is_first_pack) {
				if ($this->parrain != '') {
					$parrain = $this->db->select_one('_big_users')->where('email="'.$this->parrain.'"')->execute();
					if ($parrain) {
						$this->db->insert('historique_abonnements')->values(array(
							'id_big_user' => $parrain->id,
							'action' => 2,
							'value' => '',
							'date' => date('Y-m-d'),
						));
					}
				}
			}
		}
		
		if (array_key_exists($id_pack, $packs) || $id_pack == 'pack0') {

			if ($id_pack != 'pack0' && !$this->have_valid_card()) {
				Service::error("Impossible de séléctionner un pack car le moyen de paiement est invalide");
			}

			$date = !$date ? date('Y-m-d') : $date ;
			
			$options = is_array($options) ? implode(',',$options) : $options;

			$values = array(
				'id_big_user' => $this->id,
				'action' => 1,
				'value' => $id_pack,
				'options' => $options,
				'date' => $date,
			);

			// Si l'abonnement a déjà été changé dans la journée, on update au lieu de créer un insert
			$check_today_update = $this->db->select_one('historique_abonnements')->where('id_big_user="'.$this->id.'" AND action="1" AND date="'.$date.'"')->execute();
			if ($check_today_update) {
				$new_id = $check_today_update->id;
				$this->db->update('historique_abonnements')->values($values)->id($new_id)->execute();
			} else {
				$new_id = $this->db->insert('historique_abonnements')->values($values)->execute();
			}

			// HISTORIQUE
			if ($id_pack == 'pack0') {
				$type_historique = 13;
			} else if ($is_first_pack) {
				$type_historique = 11;
			} else {
				$type_historique = 12;
			}
			$this->db->insert('historique_big_users')->values(array(
				'id_big_user' => $this->id,
				'date' => $date,
				'type' => $type_historique,
			))->execute();

			// engagement d'un an pour toute nouvelle séléction de pack
			if ($id_pack != 'pack0') {

				$this->set_engagement($date);
			}

			return $new_id;
		
		} else {
			Service::error("Le pack séléctionné n'existe pas");
		}
	}

	public function set_engagement($date_start)
	{
		if ($date_start != '0000-00-00') {
			$engagement_time = _get('config.abonnement_params.engagement_time');
			$this->engagement = date("Y-m-d", strtotime($date_start." +".$engagement_time));
		} else {
			$this->engagement = '0000-00-00';
		}
		$this->save();
	}

	/* ------------------------------------- RESILIE UN ABONNEMENT -------------------------------------------- */

	public function cancel_abonnement()  
	{	
		// si période d'engagement, prends en compte
		if ($this->engagement != '0000-00-00') {
			return $this->save_abonnement('pack0', '', $this->engagement);
		} else {
			return $this->save_abonnement('pack0', '');
		}
	}

	/* -------------------------- RECUPERE TOUT OU PARTIE DE L'HISTORIQUE D'ABONNEMENT   ---------------------------------- */


	public function get_abonnement ($one_result=true, $date_start=null, $date_end=null, $return_entry=false)
	{
		// dernier pack : one_result=true, date_start=null, date_end=today 
		// dernier pack avant telle date: one_result=true, date_start=null, date_end=date1 
		// tous les packs séléctionnés sur un mois : one_result=false, date_start=date1, date_end=date2 

		$sql = 'SELECT * FROM historique_abonnements WHERE id_big_user="'.$this->id.'" AND action="1" '.( $date_start ? 'AND date>="'.$date_start.'"' : '').' '.( $date_end ? 'AND date<="'.$date_end.'"' : '').' ORDER BY date DESC, id DESC';

		$abonnements = $one_result ? $this->db->query_one($sql) : $this->db->query($sql);
		
		if ($abonnements) {
			$packs = _config('packs');

			if ($one_result) {
				if (array_key_exists($abonnements->value, $packs)) {
					return $return_entry ? $abonnements :  $abonnements->value;
				} else if ($abonnements->value == 'pack0') {
					return $return_entry ? $abonnements : false;
				} else {
					return $return_entry ? $abonnements : null;
				}
			
			} else {
				$abonnements_list = array();

				foreach ($abonnements as $abonnement) {

					if (array_key_exists($abonnement->value, $packs) || $abonnement->value == 'pack0') {
						$abonnements_list []= array('pack'=>$abonnement->value, 'options'=>$abonnement->options, 'date'=>$abonnement->date, 'price'=>(array_key_exists($abonnement->value, $packs) ? $packs[$abonnement->value]['price'] : 0) );
					}
				}
				return $abonnements_list;
			}
		}
		return null;
	}


	/* ------------------------------------ RECUPERE LE STATUS PRECIS D'UN ABONNEMENT   ---------------------------------- */

	public function get_abonnement_status()  
	{
		if ($this->abonnement_status) {
			return $this->abonnement_status;
		}

		// trial non programmé
		if ($this->start_trial == '0000-00-00' && $this->end_trial == '0000-00-00') {
			$this->abonnement_status = 0;
		}
		// trial à venir
		elseif ($this->start_trial != '0000-00-00' && $this->end_trial != '0000-00-00' && $this->start_trial > date('Y-m-d')) {
			$this->abonnement_status = 1;
		}
		// trial en cours
		elseif ($this->start_trial != '0000-00-00' && $this->end_trial != '0000-00-00' && $this->start_trial <= date('Y-m-d') && $this->end_trial >= date('Y-m-d')) {
			$this->abonnement_status = 2;
		}
		// trial passé
		elseif ($this->start_trial != '0000-00-00' && $this->end_trial != '0000-00-00' && $this->end_trial < date('Y-m-d')) {
			$this->abonnement_status = 3;
		}


		if ($this->get_current_pack()) {
			$this->abonnement_status = 4; // abonné

			if (!$this->have_valid_card()) {
				$this->abonnement_status = 5; // abonné mais moyen de paiement invalide, 10 jours de délai en cours
			}

		} else {

			$last_pack = $this->get_abonnement (true, null, date('Y-m-d'));

			if ($last_pack) {
				$this->abonnement_status = 6; // abo suspendu, moyen de paiement invalide, 10 jours de délai passés
			
			} else if ($last_pack === false) {
				$this->abonnement_status = 7; // abo résilié
			}
		}

		return $this->abonnement_status;
	}

	/* ---------------------------------- RECUPERE LES INFOS SUR LE STATUT D'UN ABONNEMENT   ---------------------------------- */

	public function get_abonnement_status_infos($var=null) 
	{
		if (!$this->abonnement_status) {
			$this->get_abonnement_status();
		}
		$info = array();
		$info['status'] = $this->abonnement_status;

		switch ($this->abonnement_status) {
			case 0: 
				$info['status_label'] = "Non abonné, période d'essai possible";  
				$info['status_label_short'] = "Non abonné";
				$info['status_color'] = "red";
				break;
			case 1: 
				$info['trial_days_left'] = \Core\Tools::days_left($this->start_trial);
				if ($info['trial_days_left'] == 1) {
					$info['trial_days_left_label'] = "Débute demain";
				} else if ($info['trial_days_left'] > 1) {
					$info['trial_days_left_label'] = "Débute dans ".$info['trial_days_left']." jours";
				}
				$info['status_label'] = "Période d'essai à venir - ".$info['trial_days_left_label']; 
				$info['status_label_short'] = "Période essai";
				$info['status_color'] = "green";
				break;
			case 2: 
				$info['trial_days_left'] = \Core\Tools::days_left($this->end_trial);
				if ($info['trial_days_left'] == 0) {
					$info['trial_days_left_label'] = "Termine aujourd'hui";
				} else if ($info['trial_days_left'] == 1) {
					$info['trial_days_left_label'] = "Termine demain";
				} else if ($info['trial_days_left'] > 1) {
					$info['trial_days_left_label'] = "Termine dans ".$info['trial_days_left']." jours";
				}
				$info['status_label'] = "Période d'essai en cours - ".$info['trial_days_left_label']; 
				$info['status_label_short'] = "Période essai"; 
				$info['status_color'] = "green"; 
				break;
			case 3: 
				$info['status_label'] = "Non abonné, période d'essai terminée"; 
				$info['status_label_short'] = "Non abonné"; 
				$info['status_color'] = "red";
				break;
			case 4: 
				$info['status_label'] = "Abonné"; 
				$info['status_label_short'] = "Abonné";
				$info['status_color'] = "green";
				break;
			case 5: 
				$info['status_label'] = "Abonné mais moyen de paiement invalide, suspendu dans ".$info['days_left_before_close']." jour(s)"; 
				$info['status_label_short'] = "Non abonné"; 
				$info['status_color'] = "red"; 
				break;
			case 6: 
				$info['status_label'] = "Abonnement suspendu jusqu'à mise à jour du moyen de paiement"; 
				$info['status_label_short'] = "Non abonné";  
				$info['status_color'] = "red";
				break;
			case 7: 
				$info['status_label'] = "Abonnement Résilié"; 
				$info['status_label_short'] = "Non abonné";  
				$info['status_color'] = "red";
				break;
		}

		return $var ? $info[$var] : $info;
	}
}