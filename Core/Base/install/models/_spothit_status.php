<?php 
 return array( 'models' => array( '_spothit_status' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_spothit_status',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_common_provider' => array(
						'type' => 'INT',
						'display' => false,
					),
					'id_unique_provider' => array(
						'type' => 'INT',
						'display' => false,
					),
					'type' => array(
						'type' => 'VARCHAR',
						'max' => 5,
						'display' => false,
					),
					'timestamp' => array(
						'type' => 'INT',
						'display' => false,
					),
					'status' => array(
						'type' => 'INT',
						'display' => false,
					),

				),
			),
		),
	);	
?>