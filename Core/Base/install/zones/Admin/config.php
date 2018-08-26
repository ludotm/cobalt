<?php

 return array( 'config' => array(

    /* PERMISSION */
    'permissions' => array(
        
        'auth' => array(
            'login_page' => 'admin-page-login',
            'redirect_page' =>'admin-page-clients',
        ),
        
        'single' => array(
            'access_zone' => 'Accès à l\'application',
            'manage_permissions' => 'Gérer les permissions',
            'manage_variables' => 'Paramétrage des variables',
            'manage_users' => 'Gérer les utilisateurs de type coach',
            'manage_params' => 'Gérer les paramètres de l\'application',
        ),
        'composed' => array(
            
            //'manage_client' => array('clients', 'Créer/modifier un client'),
        ),
    ),

    /* TITRE ET DESCRIPTION */
    'page' => array(
        'title' => translate('WIFID'),
        'description' => translate('WIFID'),
    ),

    /* METAS DE BASE */
    'metas' => array( // title, description, robots
        'viewport' => 'width=device-width, initial-scale=1',
        'robots' => 'noindex, nofollow',
    ),

    'favicon' => 'favicon.png',

    //'google_font' => 'Ubuntu:300,400,500,300italic,400italic',

    'google_font' => array(
        //'Ubuntu:300,400,500,300italic,400italic',
        'Asap:400,400italic,700,700italic',
        'Oxygen:400,300,700',
    ),
    'icons' => 'fa',

    /* METAS DE BASE */
    'metas' => array( // title, description, robots
        'viewport' => 'width=device-width, initial-scale=1',
        'robots' => 'noindex, nofollow',
    ),

    /* PLUGINS */
    'plugins' => array(
        //'windows_icons',
    ),

    /* CSS  */
    'stylesheets' => array(

    ),

    /* CSS LESS  */
    'less' => array(
        'main.less',
    ),

    /* SCRIPTS */
    'scripts' => array(
        'main.js',
    ),

));

