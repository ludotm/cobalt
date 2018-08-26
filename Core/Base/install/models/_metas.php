<?php 
 return array( 'models' => array( '_metas' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_metas',
					'primary' => 'id',
					'entity' => '',
				),

				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_user' => array(
						'type' => 'INT',
						'required' => true,
					),
					'entity_table' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'required' => true,
					),
					'entity_id' => array(
						'type' => 'INT',
						'required' => true,
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'required' => true,
					),
					'value' => array(
						'type' => 'TEXT',
					),
					'timestamp' => array(
						'type' => 'INT',
					),

				),
			),
		),
	);	
?>