<?php 
 return array( 'models' => array( '_vocal_messages' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_vocal_messages',
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
					'type' => array(
						'type' => 'SELECT',
						'label' => 'Type de message',
						'options' => array(
							'direct_repondeur' => 'Direct répondeur',
							'appels' => 'Appel',
						), 
					),
					'to' => array(
						'type' => 'TEXT',
						'display' => false,
					),
					'count_vocal_messages' => array(
						'type' => 'INT',
						'display' => false,
					),
					'id_audio_provider' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'display' => false,
					),
					'texte' => array(
						'type' => 'TEXT',
						'label' => 'Message à vocaliser',
					),
					'voice_type' => array(
						'type' => 'SELECT',
						'label' => 'Type de voix',
						'options' => array(
							'masculine' => 'Masculine',
							'feminine' => 'Féminine',
						), 
					),
					'expeditor' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'Label' => 'Numéro de l\'expéditeur',
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