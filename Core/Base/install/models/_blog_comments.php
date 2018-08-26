<?php 
 return array( 'models' => array( '_blog_comments' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_blog_comments',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_user' => array(
						'type' => 'INT',
						'display' => false,
					),
					'entity_table' => array(
						'type' => 'VARCHAR',
						'max' => 45,
						'display' => false,
					),
					'entity_id' => array(
						'type' => 'INT',
						'display' => false,
					),
					'text' => array(
						'type' => 'TEXT',
						'placeholder' => 'Ecrire un commentaire',
					),
					'timestamp' => array(
						'type' => 'INT',
						'display' => false,
					),
				),
			),
		),
	);	
?>