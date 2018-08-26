<?php 
 return array( 'models' => array( '_towns' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_towns',
					'primary' => 'id',
					'entity' => '',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'departement' => array(
						'type' => 'VARCHAR',
						'max' => 3,
					),
					'slug' => array(
						'type' => 'VARCHAR',
						'max' => 255,
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 45,
					),
					'short_name' => array(
						'type' => 'VARCHAR',
						'max' => 45,
					),
					'real_name' => array(
						'type' => 'VARCHAR',
						'max' => 45,
					),
					'cp' => array(
						'type' => 'VARCHAR',
						'max' => 45,
					),
					'town_code' => array(
						'type' => 'VARCHAR',
						'max' => 45,
					),
					'arrondissement' => array(
						'type' => 'INT',
					),
					'canton' => array(
						'type' => 'VARCHAR',
						'max' => 4,
					),

					'surface' => array(
						'type' => 'FLOAT',
					),
					'longitude_deg' => array(
						'type' => 'FLOAT',
					),
					'latitude_deg' => array(
						'type' => 'FLOAT',
					),
					'longitude_grd' => array(
						'type' => 'FLOAT',
					),
					'latitude_grd' => array(
						'type' => 'FLOAT',
					),
					'longitude_dms' => array(
						'type' => 'FLOAT',
					),
					'latitude_dms' => array(
						'type' => 'FLOAT',
					),

					
				),
			),
		),
	);	
?>