<?php 
 return array( 'models' => array( '_factures' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_factures',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_big_user' => array(
						'type' => 'ID_BIG_USER',
					),
					'ref' => array(
						'type' => 'VARCHAR',
						'max' => 20,
					),
					'id_transaction' => array(
						'type' => 'VARCHAR',
						'max' => 40,
					),
					'start_date' => array(
						'type' => 'DATE'
					),
					'end_date' => array(
						'type' => 'DATE'
					),
					'products' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'total_ht' => array(
						'type' => 'INT',
						'label' => 'Total HT',
					),
					'total_ttc' => array(
						'type' => 'INT',
						'label' => 'Total TTC',
					),
					'total_consumables_ttc' => array(
						'type' => 'INT',
						'label' => 'Total consommables TTC',
					),
					'spothit_cost' => array(
						'type' => 'INT',
						'label' => 'Coût Spothit',
					),
					'charged' => array(
						'type' => 'INT',
						'options' => array(
							0 => '-',
							1 => 'Ordre envoyé',
							2 => 'Ordre invalide, erreur API',
						),
					),
					'payment_status' => array(
						'type' => 'INT',
						'options' => array(
							0 => '-',
							1 => 'Payée',
							2 => 'Refusé par la banque',
							3 => 'Bloqué par Stripe, souspon de fraude',
							4 => 'Payée mais à valider chez Stripe',
							5 => 'Somme remboursée au client',
							6 => 'En attente, à valider chez Stripe',
							7 => 'Paiement contesté',
						),
					),
					'reason' => array(
						'type' => 'VARCHAR',
						'max' => 30,
					),
					
				),
			),
		),
	);	
?>