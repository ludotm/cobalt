<?php 
 return array( 'models' => array( '_departements' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_departements',
					'primary' => 'num_departement',
					'entity' => '',
				),
				
				'fields' => array(
				
					'num_departement' => array(
						'type' => 'ID',
					),
					'num_region' => array(
						'type' => 'SELECT_ID',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 255,
					),
				),
			),
		),
	);	
?>