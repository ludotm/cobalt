<?php 
 return array( 'models' => array( '_blog_sections' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_blog_sections',
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
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'label' => 'Nom',
						'required' => true,
						'placeholder' => 'Nom de la section',
					),
					
				),
			),
		),
	);	
?>