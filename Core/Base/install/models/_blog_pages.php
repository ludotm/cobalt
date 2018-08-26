<?php 
 return array( 'models' => array( '_blog_pages' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_blog_pages',
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
						'type' => 'HIDDEN',
					),
					'title' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Titre',
						'required' => true,
					),
					'slug' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'display' => false,
					),
					'description' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Description',
						'display' => false,
					),

					'id_image' => array(
						'type' => 'IMAGE',
						'label' => 'image principale',
						'file_type' => 'portal_thumb',
					),

					'content' => array(
						'type' => 'TEXT_EDITOR',
						'label' => 'Contenu',
					),
					'status' => array(
						'type' => 'BOOL',
						'label' => 'Statut',
						'skin' => 'switch',
					),
					'id_user_create' => array(
						'type' => 'USER_CREATE',
					),
					'date_create' => array(
						'type' => 'DATE_CREATE',
						'display' => false,
					),
					'date_update' => array(
						'type' => 'DATE_UPDATE',
						'display' => false,
					),
					'date_update' => array(
						'type' => 'INT',
						'display' => false,
					),
					'deleted' => array(
						'type' => 'DELETED',
					),

				),
			),
		),
	);	
?>