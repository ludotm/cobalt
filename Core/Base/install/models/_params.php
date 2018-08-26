<?php 

return array( 'models' => array( '_params' => array(

					'params' => array(
						'db' => 'default',
						'table' => '_params',
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
						'cgu' => array(
							'type' => 'TEXT',
							'label' => 'Conditions générales d\'utilisation',
						),

			)
		)
	)
);
?>