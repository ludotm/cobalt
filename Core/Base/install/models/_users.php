<?php 
 return array( 'models' => array( '_users' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_users',
					'primary' => 'id',
					'entity' => '',
					/*
					'delete_cascade' => array(
						'_users_macs' => 'id_user',
					),
					*/
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'id_big_user' => array(
						'type' => 'ID_BIG_USER',
					),
					'prename' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Prénom',
						'required' => true,
						'placeholder' => 'Prénom',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Nom',
						'required' => true,
						'placeholder' => 'Nom',
					),
					'username' => array(
						'type' => 'VARCHAR',
						'max' => 75,
						'label' => 'Nom d\'utilisateur (login)',
						'required' => true,
						//'placeholder' => 'Nom utilisateur',
						'remote_validation' => 'Ce nom d\'utilisateur existe déjà',
					),
					'email' => array(
						'type' => 'EMAIL',
						'max' => 75,
						'label' => 'Email',
						'required' => false,
						//'placeholder' => 'Email',
						'confirmation' => true,
					),
					'password' => array(
						'type' => 'PASSWORD',
						'max' => 75,
						'min' => 6,
						'label' => 'Mot de passe',
						'required' => true,
						'confirmation' => true,
					),
					'id_rank' => array(
						'type' => 'SELECT_ID',
						'label' => 'Rang',
						'required' => true,
					),
					'data' => array(
						'type' => 'DATA',
						'display' => false,
					),
					'date_create' => array(
						'type' => 'DATE_CREATE',
						'display' => false,
					),
					'date_connexion' => array(
						'type' => 'TIMESTAMP',
						'default' => 0,
						'display' => false,
					),
					'ip' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'display' => false,
					),
					'connexion_tries_time' => array(
						'type' => 'TIMESTAMP',
						'default' => 0,
						'display' => false,
					),
					'connexion_tries_count' => array(
						'type' => 'INT',
						'default' => 0,
						'display' => false,
					),
					'active' => array(
						'type' => 'BOOL',
						'default' => 1,
						'skin' => 'switch',
						'label' => 'Utilisateur actif',
					),
					'deleted' => array(
						'type' => 'DELETED',
					),
					
				),
			),
		),
	);	
?>