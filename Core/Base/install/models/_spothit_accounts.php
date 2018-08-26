<?php 
 return array( 'models' => array( '_spothit_accounts' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_spothit_accounts',
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
					'id_provider' => array(
						'type' => 'VARCHAR',
						'max' => 50,
					),
					'api_key' => array(
						'type' => 'VARCHAR',
						'max' => 50,
					),

				),
			),
		),
	);	
?>