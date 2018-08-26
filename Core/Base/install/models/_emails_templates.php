<?php 
 return array( 'models' => array( '_emails_templates' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_emails_templates',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'required' => true,
						'label' => 'Titre du template',
					),
					'id_big_user' => array(
						'type' => 'ID_BIG_USER',
					),
					'font_size' => array(
						'type' => 'VARCHAR',
						'max' => 5,
						'required' => true,
						'label' => 'Taille de police',
						'default' => '14',
					),
					'title_font_size' => array(
						'type' => 'VARCHAR',
						'max' => 5,
						'required' => true,
						'label' => 'Taille de police des titres',
						'default' => '18',
					),
					'font_color' => array(
						'type' => 'COLOR',
						'label' => 'Couleur des textes',
					),
					'link_color' => array(
						'type' => 'COLOR',
						'label' => 'Couleur des liens',
					),
					'title_color' => array(
						'type' => 'COLOR',
						'label' => 'Couleur des titres',
					),
					'id_image' => array(
						'type' => 'IMAGE',
						'file_type' => 'email_banner',
						'label' => 'Bannière',
					),
					'id_user_create' => array(
						'type' => 'USER_CREATE',
					),
				),
			),
		),
	);	
?>