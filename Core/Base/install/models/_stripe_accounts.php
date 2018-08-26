<?php 
 return array( 'models' => array( '_stripe_accounts' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_stripe_accounts',
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
					'user_token' => array(
						'type' => 'VARCHAR',
						'max' => 100,
					),
					'card_status' => array(
						'type' => 'INT',
						'display' => false,
						'options' => array(
							0 => 'Aucun',
							1 => 'Valide',
							2 => 'Invalide',
						),
					),
					'date_update' => array(
						'type' => 'DATE',
					),

				),
			),
		),
	);	
?>