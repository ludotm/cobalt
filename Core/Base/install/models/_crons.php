<?php 
 return array( 'models' => array( '_crons' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_crons',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'type' => array(
						'type' => 'VARCHAR',
						'max' => 30,
						'label' => 'Nom du cron (type)',
						'required' => true,
					),
					'timestamp' => array(
						'type' => 'INT',
					),
					'status' => array(
						'type' => 'INT',
					),
					'ip' => array(
						'type' => 'VARCHAR',
						'max' => 20,
					),
					
				),
			),
		),
	);	
?>