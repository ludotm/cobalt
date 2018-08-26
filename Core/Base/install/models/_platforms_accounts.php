<?php 
return array( 'models' => array( '_platforms_accounts' => array(

					'params' => array(
						'db' => 'default',
						'table' => '_platforms_accounts',
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
						'platform_user_id' => array(
							'type' => 'VARCHAR',
							'max' => 255,
						),
						'platform_username' => array(
							'type' => 'VARCHAR',
							'max' => 45,
						),
						'access_token' => array(
							'type' => 'VARCHAR',
							'max' => 255,
						),
						'expire' => array(
							'type' => 'INT',
						),

			)
		)
	)
);
?>