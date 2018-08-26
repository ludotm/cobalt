<?php 
 return array( 'models' => array( '_emails' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_emails',
					'primary' => 'id',
					'entity' => 'Core\Com\Email',
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
					'id_template' => array(
						'type' => 'SELECT_ID',
						'label' => 'Choisir un template',
					),
					'from_mail' => array(
						'type' => 'EMAIL',
						'max' => 50,
						'label' => 'Email de l\'expéditeur',
					),
					'from_name' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'label' => 'Nom de l\'expéditeur',
					),
					'response_mail' => array(
						'type' => 'EMAIL',
						'max' => 50,
						'label' => 'Email de réponse',
						'display' => false,
					),
					'subject' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'required' => true,
						'label' => 'Sujet',
					),
					'message' => array(
						'type' => 'TEXT_EDITOR',
						'label' => 'Contenu de l\'email',
					),
					'id_image' => array(
						'type' => 'IMAGE',
						'file_type' => 'email_banner',
						'label' => 'Image',
					),
					'attachments' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'target' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'send_to' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'cc' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'cci' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'count_mails' => array(
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