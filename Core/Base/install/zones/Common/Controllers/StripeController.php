<?php 

namespace Common\Controllers;

use \Core\Service;
use \Core\Api\Stripe;

class StripeController extends BaseController 
{
	public function onDispatch()
	{
		$this->secret = 'oI7dhT5encYdMsZ';
	}

	public function json_push_stripe_event()
	{	
		// protection par mot de passe
		$secret = $this->request->fromRoute('code', null);
		if (!$secret || $secret != $this->secret) {
			$this->render(array(
				'response' => 'Mauvais mot de passe',
			));
			exit();
		}

		$stripe = new Stripe();

		$input = @file_get_contents("php://input");
		$event = json_decode($input);

		// mesure de sécurité supplémentaire : une fois reçu l'event, on utilise l'ID envoyée pour 
		// récupérer l'event directement chez stripe, afin d'éviter un appel frauduleux à la page 
		$event = $stripe->get_event($event->id);

		/* 
		EVENTS 
		
			charge.pending (en attente)
			charge.failed (echec)
			charge.captured (saisie des frais)
			charge.succeeded (réussi)

		CHARGE
			
			status 
			- charge.succeeded
			- charge.pending
			- charge.captured
			- charge.failed
			- charge.updated 
			- charge.refunded
			- charge.dispute.created
			- charge.dispute.closed
			- charge.dispute.updated
			- charge.dispute.funds_reinstated
			- charge.dispute.funds_withdrawn

			outcome network_status 
			- approved_by_network : paiement approuvé par la banque
			- declined_by_network : paiement refusé
			- not_sent_to_network : non envoyé à la banque (exemple : en cas d'erreur api (type invalid))
			- reversed_after_approval : accepté par la banque mais bloqué par stripe (engendre statuts "pending")
			
			outcome type 
			- authorized
			- issuer_declined : (en cas de carte expiré par exemple)
			- blocked : bloqué car détécté comme une fraude potentielle
			- invalid : erreur API ou code

			outcome reason (version plus détaillée de la variable type)
			reasons relatifs à une mauvaise carte 
			call_issuer
			card_not_supported
			currency_not_supported
			invalid_account
			lost_card
			pickup_card
			restricted_card
			stolen_card
			expired_card

		*/

		$invalid_card_reasons = array(
			'expired_card',
			'call_issuer',
			'card_not_supported',
			'currency_not_supported',
			'invalid_account',
			'lost_card',
			'pickup_card',
			'stolen_card',
			'restricted_card',
		);

		if ($event->data->object->object == "charge") {

			$facture = $this->db->select_one('_factures')->where('id_transaction=:id_transaction', array('id_transaction'=>$event->data->object->id))->execute();

			if ($facture) {

				switch ($event->type) {
					
					case "charge.succeeded":
						if ($event->data->object->outcome->type == "approved_by_network" || $event->data->object->outcome->type == "authorized") {
							$facture->payment_status = 1;
						}
						break;
					
					case "charge.failed":
						if ($event->data->object->outcome->type ==  'blocked') {
							$facture->payment_status = 3;
						} else {
							$facture->payment_status = 2;
						}

						$facture->reason = $event->data->object->outcome->reason;

						if (in_array($facture->reason, $invalid_card_reasons)) {
							$big_user = $this->db->select('_big_users')->id($facture->id_big_user)->execute();
							if ($big_user) {
								$big_user->mark_card_as_invalid();
							}
						}
						break;
					
					case "charge.refunded":
						if ($event->data->object->outcome->type == "approved_by_network" || $event->data->object->outcome->type == "authorized") {
							$facture->payment_status = 5;
						}
						break;
					case "charge.pending":
						$facture->payment_status = 6;
						break;

					case "charge.updated":
						break;
					case "charge.captured":
						break;
				}

				$facture->save();
			}

		} else if ($event->data->object->object == "review") {

			$facture = $this->db->select_one('_factures')->where('id_transaction=:id_transaction', array('id_transaction'=>$event->data->object->charge))->execute();
			
			if ($facture && $event->type == 'review.opened') {
				$facture->payment_status = 4;	
				$facture->save();
			
			} else if ($facture && $event->type == 'review.closed') {

				if ($event->data->object->reason == 'refunded') {
					$facture->payment_status = 5;
				} else if ($event->data->object->reason == 'approved') {
					$facture->payment_status = 1;
				}
				$facture->save();
			}

		} else if ($event->data->object->object == "dispute") {
			
			$facture = $this->db->select_one('_factures')->where('id_transaction=:id_transaction', array('id_transaction'=>$event->data->object->charge))->execute();

			if ($facture) {

				switch ($event->type) {
					case "charge.dispute.created":
						$facture->payment_status = 7;
						break;
						
					case "charge.dispute.closed":
						if ($event->data->object->status == 'won') {
							$facture->payment_status = 1;
						} else {
							$facture->payment_status = 5;
						}
						break;
				}
				$facture->save();
			}

		}

		$this->render(array(
			
		));
	}
}