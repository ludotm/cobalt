<?php 
 return array( 'models' => array( '_sms' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_sms',
					'primary' => 'id',
					'entity' => 'Core\Com\Sms',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'campain_title' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'required' => true,
						'label' => 'Titre de la campagne',
					),
					'id_big_user' => array(
						'type' => 'INT',
						'display' => false,
					),
					'message' => array(
						'type' => 'SMS',
						'label' => 'Contenu du sms',
					),
					'send_to' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'from_num' => array(
						'type' => 'VARCHAR',
						'max' => 11,
						'label' => 'Nom de l\'expéditeur (optionnel)',
					),
					'target' => array(
						'type' => 'TEXT',
						'display' => false,
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
					'id_common_provider' => array(
						'type' => 'INT',
						'display' => false,
					),
					'is_campain' => array(
						'type' => 'BOOL',
						'display' => false,
					),
					'id_user_create' => array(
						'type' => 'USER_CREATE',
					),
					'deleted' => array(
						'type' => 'DELETED',
					),

				),
			),
		),
	);	
?>