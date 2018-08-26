<?php 
 return array( 'models' => array( '_ranks' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_ranks',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'label' => 'Nom',
						'required' => true,
						'placeholder' => 'Nom du rang',
					),
					
				),
			),
		),
	);	
?>