<?php 
return array( 'models' => array( '_platforms_applications' => array(

					'params' => array(
						'db' => 'default',
						'table' => '_platforms_applications',
						'primary' => 'id',
						'entity' => '',
					),

					'fields' => array(
						'id' => array(
							'type' => 'ID',
						),
						'id_big_user' => array(
							'type' => 'SELECT_ID',
							'required' => true,
							'label' => 'Client',
						),
						'platform' => array(
							'type' => 'SELECT',
							'required' => true,
							'label' => 'Plate-forme',
							'options' => array(
								'facebook' => 'Facebook',
								'twitter' => 'Twitter',
								'google_plus' => 'Google +',
								'linkedin' => 'LinkedIn',
								'instagram' => 'Instagram',
								'pinterest' => 'Pinterest',
							),
						),
						'api_key' => array(
							'type' => 'VARCHAR',
							'max' => 255,
							'required' => true,
							'label' => 'Clé API',
						),
						'api_secret' => array(
							'type' => 'VARCHAR',
							'max' => 255,
							'required' => true,
							'label' => 'API secret',
						),

						'admin_platform_user_id' => array(
							'type' => 'VARCHAR',
							'max' => 100,
							'label' => 'ID utilisateur de l\'admin sur le réseau social',
						),
						'admin_access_token' => array(
							'type' => 'VARCHAR',
							'max' => 255,
							'label' => 'Access Token de l\'admin',
						),
						'expire' => array(
							'type' => 'INT',
							'display' => false,
						),
			)
		)
	)
);
?>