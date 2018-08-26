<?php 
 return array( 'models' => array( '_issues' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_issues',
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
					'title' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Sujet',
						'required' => true,
					),
					'status' => array(
						'type' => 'INT',
						'display' => false,
						'options' => array(
							0 => 'En cours',
							1 => 'Fermée',
							2 => 'Résolue',
						),
					),
					'date_create' => array(
						'type' => 'DATE_CREATE',
					),

				),
			),
		),
	);	
?>