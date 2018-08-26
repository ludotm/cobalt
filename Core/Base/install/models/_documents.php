<?php 
 return array( 'models' => array( '_documents' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_documents',
					'primary' => 'id',
					'entity' => 'Core\Files\Document',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_big_user' => array(
						'type' => 'ID_BIG_USER',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Nom',
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
					),
					'extension' => array(
						'type' => 'VARCHAR',
						'max' => 5,
						'display' => false,
					),
					'file_type' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'display' => false,
					),
					'file_size' => array(
						'type' => 'INT',
						'display' => false,
					),
					
					'id_user_create' => array(
						'type' => 'USER_CREATE',
					),
					'date_create' => array(
						'type' => 'DATE_CREATE',
					),
					'date_update' => array(
						'type' => 'DATE_UPDATE',
					),
					'deleted' => array(
						'type' => 'DELETED',
					),
					
				),
			),
		),
	);	
?>