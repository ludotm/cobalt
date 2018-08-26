<?php

return array( 'plugins' => array(

    /* --------------------------- FRAMEWORK-------------------------- */

        'cobalt' => array(
            'folder' => 'cobalt',
            'scripts' => array(
                'cobalt.js',
            ),
            'stylesheets' => array(
                'cobalt.css',
            ),
            'dependencies' => array(
                'jquery',
            ),
        ),

    /* --------------------------- JQUERY & PLUGINS-------------------------- */

        'jquery' => array(
            'folder' => 'jquery',
            'scripts' => array(
                'jquery-1.10.1.min.js',
                //'jquery.form.js',
            ),
        ),

        'jquery_ui_interactions' => array(
            'folder' => 'jquery/jquery-ui-1.11.4-core-interactions',
            'scripts' => array(
                'jquery-ui.min.js',
                'jquery.ui.touch-punch.min.js' // adapte les fonctions pour mobile et tablette
            ),
            'stylesheets' => array(
                //'jquery-ui.css',
            ),
            'dependencies' => array(
                'jquery',
            ),
        ),

        'jquery_mobile' => array(
            'folder' => 'jquery/jquery-mobile',
            'scripts' => array(
                'jquery.mobile-1.4.5.min.js',
            ),
            'stylesheets' => array(
                'jquery.mobile-1.4.5.min.css',
            ),
            'dependencies' => array(
                'jquery',
            ),
        ),

    /* --------------------------- BOOTSRAP & PLUGINS-------------------------- */

        'bootstrap' => array(
            'folder' => 'bootstrap',
            'scripts' => array(
                'js/bootstrap.min.js',
            ),
            'stylesheets' => array(
                'css/bootstrap.min.css',
                //'css/themes/flatly/bootstrap.min.css',
                //'css/bootstrap-responsive.min.css',
                //'css/bootstrap-theme.css',
            ),
            'dependencies' => array(
                'jquery',
            ),
        ),

        'date_picker' => array(
            'folder' => 'bootstrap/plugins/datepicker',
            'scripts' => array(
                'js/bootstrap-datepicker.js',
            ),
            'locales' => 'js/locales/bootstrap-datepicker.[LOCALE].js',
            'stylesheets' => array(
                'css/datepicker.css',
            ),
            'dependencies' => array(
                'bootstrap',
            ),
        ),

        'date_range_picker' => array(
            'folder' => 'bootstrap/plugins/daterangepicker',
            'scripts' => array(
                'daterangepicker.js',
            ),
            'stylesheets' => array(
                'daterangepicker-bs3.css',
            ),
            'dependencies' => array(
                'bootstrap',
            ),
        ),

        'text_editor' => array(
            'folder' => 'bootstrap/plugins/simple-text-editor',
            'scripts' => array(
                //'summernote.min.js',
                'summernote.js',
                'plugin/summernote-ext-video.js',
            ),
            'locales' => 'locales/summernote-[LOCALE]-[LOCALE_MAJ].js',
            'stylesheets' => array(
                'summernote.css',
            ),
            'dependencies' => array(
                'bootstrap',
                'font_awesome',
            ),
        ),

        'color_picker' => array(
            'folder' => 'bootstrap/plugins/colorpicker',
            'scripts' => array(
                'jquery.minicolors.min.js',
            ),
            'stylesheets' => array(
                'jquery.minicolors.css',
            ),
            'dependencies' => array(
                'bootstrap',
            ),
        ),
    /* --------------------------- FONTS -------------------------- */

        'font_awesome' => array(
            'folder' => 'font-awesome',
            'stylesheets' => array(
                'css/font-awesome.min.css',
            ),
        ), 

        'fontello' => array(
            'folder' => 'fontello',
            'stylesheets' => array(
                'css/fontello.css',
            ),
        ), 

    /* --------------------------- LESS-------------------------- */

        'less' => array(
            'folder' => 'less',
            'scripts' => array(
              'less.min.js',
            ),
        ),

    /* --------------------------- DRAG DROP -------------------------- */

        'dragdrop' => array(
            'folder' => 'dragdrop',
            'scripts' => array(
              'dragdrop.js',
            ),
        ),

    /* --------------------------- FORM VALIDATION-------------------------- */

        'form_validate' => array(
            'folder' => 'form_validate',
            'scripts' => array(
                'dist/jquery.validate.js',
            ),
            'locales' => 'dist/localization/messages_[LOCALE].js',
        ),

    /* --------------------------- BAKCBONE & UNDERSCORE -------------------------- */

        'backbone' => array(
            'folder' => 'backbone',
            'scripts' => array(
                'backbone-min.js',
            ),
            'dependencies' => array(
                'jquery',
                'underscrore',
            ),
        ),

        'underscore' => array(
            'folder' => 'underscore',
            'scripts' => array(
                'underscore-min.js',
            ),
        ),

    /* --------------------------- MODERNIZR -------------------------- */

        'modernizr' => array(
            'folder' => 'modernizr',
            'scripts' => array(
                'modernizr-2.6.2-respond-1.1.0.min.js',
            ),
        ),


    /* --------------------------- HIGHCHART -------------------------- */

        'highcharts' => array(
            'folder' => 'highcharts',
            'scripts' => array(
                'js/highcharts.js',
                'js/modules/drilldown.js',
                'js/highcharts-more.js',
            ),
            'extensions' => array(
                'drilldown' => 'js/modules/drilldown.js',
            ),
            'locales' => 'js/lang/[LOCALE].js',
            'dependencies' => array(
                'jquery',
            ),
        ),


    ),
);
