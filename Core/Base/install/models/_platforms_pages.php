<?php 
return array( 'models' => array( '_platforms_pages' => array(

					'params' => array(
						'db' => 'default',
						'table' => '_platforms_pages',
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
						'title' => array(
							'type' => 'VARCHAR',
							'max' => 100,
							'label' => 'Titre',
						),
						'url' => array(
							'type' => 'URL',
							'max' => 255,
							'required' => true,
							'label' => 'Url',
						),


						'platform_page_id' => array(
							'type' => 'VARCHAR',
							'max' => 100,
						),
						'platform_page_name' => array(
							'type' => 'VARCHAR',
							'max' => 100,
						),
						'page_access_token' => array(
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