<?php 
 return array( 'models' => array( '_big_users' => array(
				
				'params' => array(
					'db' => 'default',
					'table' => '_big_users',
					'primary' => 'id',
					'entity' => '\Common\Classes\BigUser',
				),
				
				'fields' => array(
				
					'id' => array(
						'type' => 'ID',
					),
					'name' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Nom du client',
						'remote_validation' => 'Ce nom d\'utilisateur existe déjà',
						'required' => true,
					),
					'society' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Nom de la société',
						'required' => true,
					),
					'type_society' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'label' => 'Forme juridique',
						'required' => true,
					),
					'rcs' => array(
						'type' => 'VARCHAR',
						'max' => 50,
						'label' => 'N° RCS',
						'required' => true,
					),
					'rcs_town' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Ville RCS',
						'required' => true,
					),
					'contact_prename' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Prénom du contact',
						'required' => true,
					),
					'contact_name' => array(
						'type' => 'VARCHAR',
						'max' => 100,
						'label' => 'Nom du contact',
						'required' => true,
					),
					'address' => array(
						'type' => 'VARCHAR',
						'max' => 255,
						'label' => 'Adresse',
					),
					'cp' => array(
						'type' => 'VARCHAR',
						'max' => 10,
						'label' => 'Code postal',
						'filter' => 'delete_whitespace',
					),
					'town' => array(
						'type' => 'VARCHAR',
						'max' => 75,
						'label' => 'Ville',
					),
					'country' => array(
						'type' => 'SELECT',
						'label' => 'Pays',
						'options' => array(
							'France' => 'France',
							'Angleterre' => 'Angleterre',
							'Belgique' => 'Belgique', 
						),
					),
					'email' => array(
						'type' => 'EMAIL',
						'max' => 100,
						'required' => true,
						'label' => 'Email',
					),
					'phone' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'label' => 'Téléphone',
						'filter' => 'delete_whitespace',
					),
					'mobile' => array(
						'type' => 'VARCHAR',
						'max' => 20,
						'label' => 'Téléphone mobile',
						'filter' => 'delete_whitespace',
					),
					'access_admin' => array(
						'type' => 'BOOL',
						'display' => false,
					),
					'date_create' => array(
						'type' => 'DATE_CREATE',
						'display' => false,
					),
					'start_trial' => array(
						'type' => 'DATE'
					),
					'end_trial' => array(
						'type' => 'DATE'
					),
					'date_contract' => array(
						'type' => 'DATE'
					),
					'engagement' => array(
						'type' => 'DATE',
						'display' => false,
					),
					'auto_params' => array(
						'type' => 'BOOL',
						'display' => false,
					),
					'alert' => array(
						'type' => 'DATE',
						'label' => 'Date',
					),
					'alert_comment' => array(
						'type' => 'TEXT',
						'label' => 'Commentaire',
					),
					'comment' => array(
						'type' => 'TEXT',
						'label' => 'Commentaire',
					),
					'motif' => array(
						'type' => 'SELECT',
						'label' => 'Motif',
						'options' => array(
							1 => 'Non précisé',  // ne pas toucher à cette ID
							2 => 'Fonctionalités manquantes',
							3 => 'Trop cher',
							4 => 'Pas convaincu',
							5 => 'Trop compliqué',
							6 => 'Trop peu utilisé',
							7 => 'Salle pleine',
							8 => 'Salle fermée',
							9 => 'Mauvais SAP',
							10 => 'Autre',
						),
					),
					'stop_contact' => array(
						'type' => 'BOOL',
					),
					'stop_facturation' => array(
						'type' => 'BOOL',
					),
					'cgu' => array(
						'type' => 'BOOL',
					),
					'parrain' => array(
						'type' => 'EMAIL',
						'label' => 'Email de votre parrain',
						'placeholder' => 'Email de votre parrain',
						'max' => 100,
					),
					'deleted' => array(
						'type' => 'DELETED',
					),
					
				),
			),
		),
	);	
?>