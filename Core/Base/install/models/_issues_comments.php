<?php 
 return array( 'models' => array( '_issues_comments' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_issues_comments',
					'primary' => 'id',
					'entity' => '',
				),

				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_issue' => array(
						'type' => 'INT',
						'display' => false,
					),
					'id_user_create' => array(
						'type' => 'USER_CREATE',
					),
					'comment' => array(
						'type' => 'TEXT',
						'label' => 'Message',
						'required' => true,
					),
					'timestamp' => array(
						'type' => 'INT',
						'display' => false,
					),
					'is_dev' => array(
						'type' => 'BOOL',
						'display' => false,
					),
					'status' => array(
						'type' => 'INT',
						'options' => array(
							0 => 'Non lu',
							1 => 'Lu',
							2 => 'Répondu',
						),
						'display' => false,
					),
				),
			),
		),
	);	
?>