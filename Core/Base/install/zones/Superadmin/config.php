<?php

 return array( 'config' => array(

    /* PERMISSION */
    'permissions' => array(
        
        'auth' => array(
            'login_page' => 'superadmin-page-login',
            'redirect_page' =>'superadmin-page-clients',
        ),
        
        'single' => array(
            'access_zone' => 'Accès à l\'application',
        ),
        'composed' => array(
        ),
    ),

    /* TITRE ET DESCRIPTION */
    'page' => array(
        'title' => translate('SUPERADMIN WIFID'),
        'description' => translate('SUPERADMIN WIFID'),
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

