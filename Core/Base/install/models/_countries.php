<?php 
 return array( 'models' => array( '_countries' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_countries',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Nom',
						'required' => true,
						'placeholder' => 'Nom du pays',
					),
					'iso' => array(
						'type' => 'VARCHAR',
						'max' => 2,
						'label' => 'Code ISO',
						'required' => true,
						'placeholder' => 'Code ISO du pays',
					),
					
				),
			),
		),
	);	
?>