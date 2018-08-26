<?php 
 return array( 'models' => array( '_mms' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_mms',
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
					'subject' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Sujet du MMS',
					),
					'message' => array(
						'type' => 'TEXT',
						'label' => 'Contenu du MMS',
					),
					'to' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'expeditor' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'label' => 'Email de l\'expéditeur',
					),
					'count_sms' => array(
						'type' => 'INT',
						'display' => false,
					),
					'sms_concat_size' => array(
						'type' => 'INT',
						'display' => false,
					),
					'timestamp' => array(
						'type' => 'INT',
						'display' => false,
					),
					'provider' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'display' => false,
					),
					'id_provider' => array(
						'type' => 'INT',
						'display' => false,
					),
					'status' => array(
						'type' => 'INT',
						'display' => false,
					),

					

				),
			),
		),
	);	
?>