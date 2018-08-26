<?php 
 return array( 'models' => array( '_regions' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_regions',
					'primary' => 'num_region',
					'entity' => '',
				),
				
				'fields' => array(
				
					'num_region' => array(
						'type' => 'ID',
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