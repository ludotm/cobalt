<?php 

namespace Core\Base\Controllers;

use Core\Page;
use Core\Service;

class InstallController extends BaseController
{	 
	protected $db;
	protected $sql;
	protected $modules;
	protected $base_tables;

	public function onDispatch() 
	{

		if (!is_dir('Core/Base/install')) {
			exit('Le framework est d&eacute;j&agrave; install&eacute;');
		}

		$this->set_layout('layout_install');
		$this->set_base_title('Installer Cobalt');
		$this->set_title('');
		$this->sql = '';

		$this->module_file = 'Core/Base/install/modules.txt';
		$this->config_file = 'config/config.php';
		$this->route_file = 'config/routes.php';
		$this->local_config_file = 'config/local_config.php';
		$this->distant_config_file = 'config/distant_config.php';

		$this->base_tables = array(
			'_big_users',
			'_images',
			'_documents',
			'_users',
			'_ranks',
			'_rank_has_permission',
			'_params',
			'_metas',
			'_crons',
		);

		$this->modules = array(
			'front' => array(
				'label' => 'Zone Front de base',
				'tables' => array(),
				'zone' => 'Front',
			),
			'admin' => array(
				'label' => 'Zone admin avec accès sécurisé',
				'tables' => array(),
				'zone' => 'Admin',
			),
			'superadmin' => array(
				'label' => 'Zone superadmin',
				'tables' => array('_factures'),
				'zone' => 'Superadmin',
			),
			'blog' => array(
				'label' => 'Blog',
				'tables' => array('_blog_categories', '_blog_comments', '_blog_page_has_category', '_blog_page_has_section','_blog_pages','_blog_sections'),
				'zone' => null,
			),
			'email' => array(
				'label' => 'Stockage des emails en BDD',
				'tables' => array('_emails', '_emails_templates'),
				'zone' => null,
			),
			'spothit' => array(
				'label' => 'Spothit (emails, SMS, messages vocaux)',
				'tables' => array('_sms','_mms', '_spothit_accounts', '_spothit_status', '_vocal_messages'),
				'zone' => null,
			),
			'stripe' => array(
				'label' => 'Stripe (solution de paiement en ligne)',
				'tables' => array('_stripe_accounts', '_factures'),
				'zone' => null,
			),
			'issues' => array(
				'label' => 'Tickets de report',
				'tables' => array('_issues', '_issues_comments'),
				'zone' => null,
			),
			'social' => array(
				'label' => 'API réseaux sociaux',
				'tables' => array('_platforms_accounts', '_platforms_applications', '_platforms_pages'),
				'zone' => null,
			),
			'meraki' => array(
				'label' => 'Borne wifi Meraki',
				'tables' => array('_meraki_data_storage', '_meraki_hotspots', '_meraki_portal_connexions', '_meraki_portal_likes', '_meraki_portals', '_meraki_users_macs'),
				'zone' => null,
			),
			'countries' => array(
				'label' => 'Base de donnée des pays',
				'tables' => array('_countries'),
				'zone' => null,
			),
			'towns' => array(
				'label' => 'Base de donnée des villes françaises',
				'tables' => array('_towns'),
				'zone' => null,
			),
			'departements' => array(
				'label' => 'Base de donnée des départements françaises',
				'tables' => array('_departements'),
				'zone' => null,
			),
			'regions' => array(
				'label' => 'Base de donnée des régions françaises',
				'tables' => array('_regions'),
				'zone' => null,
			),
		);
		$this->no_model = array(
			'_blog_page_has_category',
			'_blog_page_has_section',
			'_meraki_data_storage',
			'_meraki_hotspots',
			'_meraki_portal_connexions',
			'_meraki_portal_likes',
			'_meraki_portals',
			'_meraki_users_macs',
			'_rank_has_permission',
		);

		$_SESSION['install'] = isset($_SESSION['install']) ? $_SESSION['install'] : array();
	}

	public function page_install() 
	{
		$step = $this->request->fromRoute('step', (isset($_SESSION['install']['step']) ? $_SESSION['install']['step'] : 1) );	
		$_SESSION['install']['step'] = $step;

		$post = $this->request->post;
		$step == isset($post->step) ? $post->step : $step ;


		$module_file = $this->module_file;
		$config_file = $this->config_file;
		$route_file = $this->route_file;

		if ($step == 1) { // modules

			$options = array();
			foreach ($this->modules as $key => $value) {
				$options[$key] = $value['label'];
			}

			if (!file_exists ($module_file)) {
				file_put_contents($module_file, '');
			} 
			$selected_modules = file_get_contents($module_file);
			$selected_modules = explode(';', $selected_modules);

			$model = array(
				'modules' => array(
					'type' => 'CHECKBOX',
					'label' => 'Modules',
					'options' => $options,
					'inline' => false,
					'value' => $selected_modules,
				),
			);

			$form = new \Core\Form($model);
			$form->action('')->method('post');
			$form->factorize();
			$form->add_submit('Envoyer');

			if ($this->request->isPost()) {
				
				$post = $this->request->post;

				$active_modules = '';
				foreach ($post->modules as $module) {					
					$active_modules .= $module.';';
				}

				file_put_contents($module_file, $active_modules);
				$_SESSION['install']['step'] = 2;
				Service::redirect('/install');
			}

			$this->render(array(
				'step' => $step,
				'form' => $form,
	        ));

		} else if ($step == 2) {

			if (file_exists ($module_file)) {
				$selected_modules = file_get_contents($module_file);
				$selected_modules = explode(';', $selected_modules);
			}

			$model = array(
				'site_title' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Titre du site',
					'label' => 'Titre du site',
				),
				'plugins' => array(
					'type' => 'CHECKBOX',
					'label' => 'Plugins à charger sur toutes les pages',
					'options' => array(
						'jquery' => 'Jquery',
						'bootstrap' => 'Bootstrap',
						'cobalt' => 'Cobalt',
						'form_validate' => 'Form validation',
						'font_awesome' => 'Font awesome',
						'fontello' => 'Fontello',
						'jquery_mobile' => 'Jquery Mobile',
						'jquery_ui_interactions' => 'Jquery UI interactions',
						'backbone' => 'Backbone',
						'underscore' => 'Underscore',
						'modernizr' => 'Modernizr',
						'date_picker' => 'Date Picker',
						'date_range_picker' => 'Date Range Picker',
						'highcharts' => 'Highcharts',
					),
					'value' => array(
						'jquery', 'bootstrap', 'cobalt', 'form_validate', 'font_awesome', 
					),
					'inline' => false,
				),

				'google_analytics' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Google Analytics Tag',
					'label' => 'Google Analytics Tag',
				),


				'session_time' => array(
					'type' => 'INT',
					'label' => 'Durée de la session (en sec)',
					'value' => '3600',
				),

			);
			
			if (in_array('stripe', $selected_modules)) {

				$model = array_merge($model, 
					array( 
						'stripe_api_key_test' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Clé API test',
							'label' => 'Clé API test',
						),
						'stripe_api_key' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Clé API live',
							'label' => 'Clé API live',
						),
						'stripe_publishable_key_test' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Clé API publiable test',
							'label' => 'Clé API publiable test',
						),
						'stripe_publishable_key' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Clé API publiable',
							'label' => 'Clé API publiable',
						),
					)
				);
			}

			if (in_array('spothit', $selected_modules)) {

				$model = array_merge($model, 
					array( 
						'spothit_ref_client' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Référence client',
							'label' => 'Référence client',
						),
						'spothit_api_key' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'Clé API',
							'label' => 'Clé API',
						),
						'spothit_from_mail' => array(
							'type' => 'VARCHAR',
							'placeholder' => 'xxxx@sh-mail.com',
							'label' => 'Mail expéditeur par défaut',
						),
					)
				);
			}

			$form = new \Core\Form($model);
			$form->action('')->method('post');
			$form->factorize();

			$form->add_html_before('site_title', '<br><h2>Base</h2>');

			if (in_array('stripe', $selected_modules)) {
				$form->add_html_before('stripe_api_key_test', '<br><h2>Stripe</h2>');
			}
			if (in_array('spothit', $selected_modules)) {
				$form->add_html_before('spothit_ref_client', '<br><h2>Spothit</h2>');
			}
			$form->add_submit('Envoyer');

			if ($this->request->isPost()) {
				
				$post = $this->request->post;

				// COPY DES ZONES SUPERADMIN, ADMIN, COMMON, ET FRONT
				if (in_array('front', $selected_modules)) {
					$this->copy_dir('Core/Base/install/zones/Front/', 'Front/');
				}
				if (in_array('admin', $selected_modules)) {
					$this->copy_dir('Core/Base/install/zones/Admin/', 'Admin/');
				}
				if (in_array('superadmin', $selected_modules)) {
					$this->copy_dir('Core/Base/install/zones/Superadmin/', 'Superadmin/');
				}
				if (in_array('superadmin', $selected_modules) || in_array('spothit', $selected_modules) || in_array('stripe', $selected_modules)) {
					$this->copy_dir('Core/Base/install/zones/Common/', 'Common/');
				}

				ob_start(); ?>

    return array( 'config' => array(

    		<?php if (in_array('superadmin', $selected_modules)) : ?>
			/* REDIRECTION DU SUPERADMIN LORS DE LA CONNEXION */
			'superadmin_redirection' => '/superadmin/clients',
			<?php elseif (in_array('admin', $selected_modules)) : ?>
			/* REDIRECTION DU SUPERADMIN LORS DE LA CONNEXION */
			'superadmin_redirection' => '/admin/clients',
			<?php endif; ?>

			<?php if (in_array('admin', $selected_modules)) : ?>
			/* ROUTE VERS L'ADMIN */
			'admin_route' => 'admin-page-clients',
			<?php endif; ?>

			/* TITRE ET DESCRIPTION */
			'page' => array(
				'base_title' => translate('<?= $post->site_title ?> - '),
				'title' => translate(''),
				'description' => translate('<?= $post->site_title ?>'),
			),

			/* METAS DE BASE */
			'metas' => array( // title, description, robots
				'viewport' => 'width=device-width, initial-scale=1.0',
				//'robots' => 'noindex, nofollow, noarchive',
			),

			/* PLUGINS */
			'plugins' => array(
				'<?= implode('\',\'', $post->plugins) ?>'
			),
			
			<?php if (in_array('stripe', $selected_modules)) : ?>
			
			/* PAIEMENT EN LIGNE - STRIPE */
			'stripe' => array(
				'mode' => 'test', // "test" ou "live"
				'api_key_test' => '<?= $post->stripe_api_key_test ?>',
				'api_key' => '<?= $post->stripe_api_key ?>',
				'publishable_key_test' => '<?= $post->stripe_publishable_key_test ?>',
				'publishable_key' => '<?= $post->stripe_publishable_key ?>',
			),

			<?php endif; ?>

			/* PROVIDER MAIL, SMS, MMS, VOCAL */
			
			<?php if (in_array('stripe', $selected_modules)) : ?>
			
			'default_providers' => array(
				'mail' => 'self' ,
				'sms' => 'spothit_lowcost' ,
				'mms' => 'spothit' ,
				'vocal' => 'spothit' ,
			),
			'spothit' => array(
				'ref_client' => '<?= $post->spothit_ref_client ?>',
				'api_key' => '<?= $post->spothit_api_key ?>',
				'from_mail' => '<?= $post->spothit_from_mail ?>',
			),
			'consumables' => array(
				'email_basic' => array(
					'name' => 'Emails Basic',
					'price' => 0,
				),
				'email_premium' => array(
					'name' => 'Emails Premium',
					'price' => 0.01,
					'spothit_price' => 0.005,
				),
				'sms_basic' => array(
					'name' => 'SMS Basic',
					'price' => 0.09,
					'spothit_price' => 0.03,
				),
				'sms_premium' => array(
					'name' => 'SMS Premium',
					'price' => 0.12,
					'spothit_price' => 0.05,
				),
			),
			<?php elseif (in_array('email', $selected_modules)) : ?>
			
			'default_providers' => array(
				'mail' => 'self' ,
			),
			
			<?php endif; ?>

			/* GOOGLE ANALYTICS CODE */
			'google_analytics' => <?= $post->google_analytics != '' ? "'".$post->google_analytics."'" : 'null' ?>,

			'language' => array(
				'active_translation' => false,
				'default_language' => 'fr',
			),

			/* SESSIONS */			
			'session' => array(
				'active' => true,
				'duration' => <?= is_numeric($post->session_time) ? $post->session_time : 3600 ?>, 
				'login_page' => '/login',
				'connexion_tries' => '5',
				'tries_duration' => 600,
			),

			/* COOKIES */
			'cookie' => array(
				'cookie_path' => ROOT.'/files/cookies',
				'cookie_authentification' => true,
				'cookie_steal_protection' => false,
				'cookie_auth_expiration' => (3600*24*30),
				'cookie_expiration' => (3600*24*365),
			),

			/* CACHE */
			'cache' => array(
				'cache_path' => ROOT."/files/cache/",
				'cache_expiration' => 3600,
			),

			/* PROXIES */
			'proxies' => array(
				//array('ip' => '127.0.0.1', 'host'=>80, 'username'=>'root', 'password'=>''),
				//array('ip' => '127.0.0.1', 'host'=>80, 'username'=>'root', 'password'=>''),
			),

			/* ZONE VERS LAQUELLE POINTER POUR LES ERREURS 404 & co */
			'error_zone' => 'Core/Base',
			'error_logs_file' => ROOT."/files/logs/error_logs.txt",

			/* FILE MANAGER */
			'file_manager' => array(

				'max_file_size' => '8000000', // 8 Mo
				'upload_directory' => 'files/upload/',
				'directory_chmod' => 0755,
				'file_chmod' => 0666,
				'upload_accepted_extensions' =>  array(
					/* EXTENSION DE FICHIERS ACEPTEES A L'UPLOAD */
					'image' => array ('jpg', 'jpeg', 'png', 'gif'),
					'document' => array ('html', 'pdf', 'csv', 'psd', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'rtf'),
					'video' => array ('flv', 'wmv', 'mpg', 'mp4'),
					'sound' => array ('mp3', 'wav', 'ogg'),
				),

				'predefined_image_formats' => array(

					'media_manager_thumb' => array( // Pour le media manager
						'crop' => true,
						'width' => 130,
						'height' => 130,
						//'copyright' => false,
					),
				),

				'file_types' => array(

					'email_banner' => array(
						'type' => 'image',
						'label' => 'Banières des emails',
						'folder' => '/campagnes',	
						'formats' => array(
							'media_manager_thumb',
						),
					),

					'blog_thumb' => array(
						'type' => 'image',
						'label' => 'Images principales des pages et news',
						'folder' => '/portal',
						'formats' => array(
							'media_manager_thumb',
							'medium' => '150x*',
						),
					),
				),

			),
			
		),
	);

				    <?php

			    $content = ob_get_contents();
			    ob_end_clean();
			    file_put_contents($config_file, '<?php '.$content.'?>');


			    ob_start(); ?>

   return array( 'routes' => array(

   		<?php if (in_array('front', $selected_modules)) : ?>
   		/* -------------------- ZONE FRONT -------------------------- */

   			'front-page-home' => array(
                'route' => '/',
                'controller' => 'Front',
            ),

   		<?php endif; ?>

   		<?php if (in_array('superadmin', $selected_modules)) : ?>
        /* -------------------- ZONE SUPERADMIN -------------------------- */
        
            'superadmin-page-login' => array(
                'route' => '/superadmin/login',
                'controller' => 'Base',
            ), 
            'superadmin-page-factures' => array(
                'route' => '/superadmin/factures',
                'controller' => 'Factures',
            ),
            'superadmin-widget-factures_list' => array(
                'route' => '/superadmin/factures_list[/m:month][/y:year][/s:status][/id:id][/p:page]',
                'controller' => 'Factures',
            ),

            'superadmin-page-clients' => array(
                'route' => '/superadmin/clients',
                'controller' => 'Clients',
            ),
            'superadmin-widget-clients' => array(
                'route' => '/superadmin/clients[/:action][/:id][/page/:page]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'action' => 'view|list|form|follow|delete',
                ),
                'defaults' => array(
                    'action' => 'list'
                ),
            ),

            'superadmin-widget-account' => array(
                'route' => '/superadmin/clients/account[/:id][/:section][/:action]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
                'default' => array(
                    'section' => 'dossier',
                    'action' => null,
                ),
            ),
            'superadmin-widget-coordonnees' => array(
                'route' => '/superadmin/clients/coordonnees[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-archive' => array(
                'route' => '/superadmin/clients/archive[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-abonnement' => array(
                'route' => '/superadmin/clients/abonnement[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-factures' => array(
                'route' => '/superadmin/clients/factures[/:id][/:delete_addon]/page[/:page]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'delete_addon' => '[0-9]+',
                ),
                'default' => array(
                    'page' => 1,
                ),
            ),
            'superadmin-widget-archive' => array(
                'route' => '/superadmin/clients/archive[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-alert' => array(
                'route' => '/superadmin/clients/alert[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-historique' => array(
                'route' => '/superadmin/clients/historique[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            'superadmin-json-cancel_abonnement' => array(
                'route' => '/superadmin/cancel_abonnement[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-json-clients' => array(
                'route' => '/superadmin/clients/action[/:action][/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-json-save_comment' => array(
                'route' => '/superadmin/clients/save-comment[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            'superadmin-json-get_status_infos' => array(
                'route' => '/superadmin/clients/status-info[/:id]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            /* ----------------------------- STATS ---------------------------------- */
            'superadmin-page-stats' => array(
                'route' => '/superadmin/stats',
                'controller' => 'Stats',
            ),
            'superadmin-widget-stats' => array(
                'route' => '/superadmin/stats[/:action][/:id]',
                'controller' => 'Stats',
                'defaults' => array(
                    'action' => 'clients'
                ),
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            /* ----------------------------- CAMPAINS ---------------------------------- */

            'superadmin-page-campain' => array(
                'route' => '/superadmin/campain',
                'controller' => 'Campain',
            ),
            'superadmin-widget-campain_mail' => array(
                'route' => '/superadmin/campain_mail[/:action][/:id][/page/:page]',
                'controller' => 'Campain',
                'defaults' => array(
                    'action' => 'list'
                ),
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-campain_sms' => array(
                'route' => '/superadmin/campain_sms[/:action][/:id][/page/:page]',
                'controller' => 'Campain',
                'defaults' => array(
                    'action' => 'list'
                ),
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-select_template' => array(
                'route' => '/superadmin/select_template/:id',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-target_campain' => array(
                'route' => '/superadmin/targetcampain[/:id][/:type]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-campain_content_mail' => array(
                'route' => '/superadmin/campaincontent[/:id]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-campain_content_sms' => array(
                'route' => '/superadmin/campaincontentsms[/:id]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-render_mail' => array(
                'route' => '/superadmin/rendermail[/:id]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-widget-launch_campain' => array(
                'route' => '/superadmin/launch_campain[/:id][/:type]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-json-send_campain' => array(
                'route' => '/superadmin/send_campain[/:id][/:type][/:provider]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            'superadmin-widget-template' => array(
                'route' => '/superadmin/template[/:action][/:id][/page/:page]',
                'controller' => 'Campain',
                'defaults' => array(
                    'action' => 'list'
                ),
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-page-template_preview' => array(
                'route' => '/superadmin/template_preview/:id',
                'controller' => 'Campain',
                'defaults' => array(
                    'action' => 'list'
                ),
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-page-mail' => array(
                'route' => '/superadmin/mail[/:id]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
            'superadmin-json-get_status' => array(
                'route' => '/superadmin/get_status[/:id][/:type]',
                'controller' => 'Campain',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),
           
           <?php endif; ?>

           <?php if (in_array('admin', $selected_modules)) : ?>


        /* ----------------------------- ZONE ADMIN ---------------------------------- */

            /* ----------------------------- LOGIN ---------------------------------- */

            'admin-redirect-login' => array(
                'route' => '/',
                'redirection' => '/admin/login',
            ),

            'admin-page-login' => array(
                'route' => '/admin/login',
                'controller' => 'Base',
            ),
            
            'admin-page-acceptcgu' => array(
                'route' => '/admin/acceptcgu',
                'controller' => 'Params',
            ),
            'admin-page-cgu' => array(
                'route' => '/admin/cgu',
                'controller' => 'Params',
            ),

            /* ----------------------------- CLIENTS ---------------------------------- */

            'admin-page-clients' => array(
                'route' => '/admin/clients',
                'controller' => 'Clients',
            ),
            'admin-widget-clients' => array(
                'route' => '/admin/clients[/:action][/:id][/page/:page]',
                'controller' => 'Clients',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'action' => 'view|list|form|follow|delete',
                ),
                'defaults' => array(
                    'action' => 'list'
                ),
            ),
            
            /* ----------------------------- CONTACT / SUPPORT  ---------------------------------- */
            'admin-page-contact' => array(
                'route' => '/admin/contact-support',
                'controller' => 'Params',
            ),
            'admin-widget-contact' => array(
                'route' => '/admin/contact',
                'controller' => 'Params',
            ),

            /* ----------------------------- PARAMS ---------------------------------- */

            'admin-page-params' => array(
                'route' => '/admin/params',
                'controller' => 'Params',
            ),

            'admin-widget-users' => array(
                'route' => '/admin/params/users[/:action][/:id][/page/:page]',
                'controller' => 'Params',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'action' => 'view|list|form|delete',
                ),
                'defaults' => array(
                    'action' => 'list'
                ),
            ),
            'admin-widget-permissions' => array(
                'route' => '/admin/params/permissions',
                'controller' => 'Params',
            ),
            'admin-widget-variables' => array(
                'route' => '/admin/params/variables',
                'controller' => 'Params',
            ),
            'admin-widget-wifi' => array(
                'route' => '/admin/wifi',
                'controller' => 'Params',
            ),
            'admin-widget-social' => array(
                'route' => '/admin/params/social[/:action][/:id]',
                'controller' => 'Params',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'action' => 'view|list|form|delete',
                ),
                'defaults' => array(
                    'action' => 'list'
                ),
            ),
            'admin-widget-params' => array(
                'route' => '/admin/params/params',
                'controller' => 'Params',
            ),
            
            'admin-json-activeuser' => array(
                'route' => '/admin/params/activeuser[/:id]',
                'controller' => 'Params',
                'constraints' => array(
                    'id' => '[0-9]+',
                ),
            ),

            'admin-page-permissions' => array(
                'route' => '/admin/params/permissions[/:id][/:delete]',
                'controller' => 'Params',
                'constraints' => array(
                    'id' => '[0-9]+',
                    'delete' => 'delete',
                )
            ),


        <?php endif; ?>


    ),
);


				    <?php


			    $content = ob_get_contents();
			    ob_end_clean();
			    file_put_contents($route_file, '<?php '.$content.'?>');

				Service::redirect('/install?step=3');
			}

			$this->render(array(
				'step' => $step,
				'form' => $form,
	        ));

		} else if ($step == 3) {

			$model = array(
				'local_host' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Host',
					'label' => 'Host (local)',
					'value' => 'localhost',
				),
				'local_user' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Utilisateur',
					'label' => 'Utilisateur (local)',
					'value' => 'root',
				),
				'local_password' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Mot de passe',
					'label' => 'Password (local)',
					'value' => '',
				),
				'local_db' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Nom de la base de donnée',
					'label' => 'Nom BDD (local)',
					'value' => '',
				),

				'distant_host' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Host',
					'label' => 'Host (distant)',
					'value' => 'localhost',
				),
				'distant_user' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Utilisateur',
					'label' => 'Utilisateur (distant)',
					'value' => '',
				),
				'distant_password' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Mot de passe',
					'label' => 'Password (distant)',
					'value' => '',
				),
				'distant_db' => array(
					'type' => 'VARCHAR',
					'placeholder' => 'Nom de la base de donnée',
					'label' => 'Nom BDD (distant)',
					'value' => '',
				),
			);

			$form = new \Core\Form($model);
			$form->action('')->method('post');
			$form->factorize();
			$form->add_submit('Envoyer');
			$form->add_html_before ('local_host', '<h2>Base de donnée locale</h2>');
			$form->add_html_before ('distant_host', '<h2>Base de donnée distante</h2>');

			if ($this->request->isPost()) {
				
				$post = $this->request->post;

				if ($post->local_host != '' && $post->local_user != '' && $post->local_db != '') {
					ob_start(); ?>

    return array( 'config' => array(
                
            /* DATABASES */
            'db' => array(
                'host' => '<?= $post->local_host ?>',
                'user' => '<?= $post->local_user ?>',
                'password' => '<?= $post->local_password ?>',
                'dbs' => array(
                    'default' => '<?= $post->local_db ?>',
                ),
            ),

            /* DEBUG */
            'debug' => array(
				'show_details' => array(
					'public' => true,
					'superadmin' => true,
					'true_for_ip' => '',
				),
				'show_backtrace' => array(
					'public' => true,
					'superadmin' => true,
					'true_for_ip' => '',
				),
			),

			/* LESS PARAMS */
			'less_params' => array(
				'compilation_type' => 'php', // js or php
				'autocompile' => true,
				'compressed' => false,
				'preserve_comments' => false,
			),
        ),
    );

				    <?php

				    $content = ob_get_contents();
				    ob_end_clean();
				    file_put_contents($this->local_config_file, '<?php '.$content.'?>');
				}

				if ($post->distant_host != '' && $post->distant_user != '' && $post->distant_password != '' && $post->distant_db != '') {
					ob_start(); ?>

    return array( 'config' => array(
                
            /* DATABASES */
			'db' => array(
				'host' => '<?= $post->distant_host ?>',
				'user' => '<?= $post->distant_user ?>',
				'password' => '<?= $post->distant_password ?>',
				'dbs' => array(
					'default' => '<?= $post->distant_db ?>',
				),
			),

			/* DEBUG */
			'debug' => array(
				'show_details' => array(
					'public' => false,
					'superadmin' => true,
					'true_for_ip' => '',
				),
				'show_backtrace' => array(
					'public' => false,
					'superadmin' => true,
					'true_for_ip' => '',
				),
			),

			/* LESS PARAMS */
			'less_params' => array(
				'compilation_type' => 'php', // js or php
				'autocompile' => false,
				'compressed' => true,
				'preserve_comments' => false,
			),
        ),
    );

				    <?php

				    $content = ob_get_contents();
				    ob_end_clean();
				    file_put_contents($this->distant_config_file, '<?php '.$content.'?>');			
				}

				try {
					if (IS_LOCAL) {
						_set('config/local_config.php');
					} else {
						_set('config/distant_config.php');
					}
					$this->force_db_connexion();
				
				} catch (\Exception $e) {
					
					Service::flash('Les données sont érronées ou incomplètes', 'error', true);
					Service::redirect('/install?step=3');
				}
				
				Service::flash('Les fichiers BDD ont bien été créés', 'success', true);
				$_SESSION['install']['step'] = 4;
				Service::redirect('/install');
			}

			$this->render(array(
				'step' => $step,
				'form' => $form,
	        ));

		} else if ($step == 4) {

			try {
				$this->force_db_connexion();
			
			} catch (\Exception $e) {

				Service::flash('Les données sont érronées ou incomplètes', 'error', true);
				Service::redirect('/install?step=3');
			}

			if (file_exists ($module_file)) {
				$selected_modules = file_get_contents($module_file);
				$selected_modules = explode(';', $selected_modules);
			}

			$this->render(array(
				'step' => $step,
				'have_front' => in_array('front', $selected_modules),
				'have_admin' => in_array('admin', $selected_modules),
				'have_superadmin' => in_array('superadmin', $selected_modules),
				'have_towns' => in_array('towns', $selected_modules),
	        ));

		}  else if ($step == 5) {

			// SUPRESSOIN DES FICHIERS INSTALL
			$this->delete_dir('Core/Base/install');

			$this->render(array(
				'step' => $step,
	        ));

		} 
	}

	protected function full_install() 
	{
		$tables = $this->base_tables;
		$zones = array();
		$output = '';

		$selected_modules = file_get_contents($this->module_file);
		$selected_modules = explode(';', $selected_modules);
		array_pop($selected_modules);

		foreach ($selected_modules as $module) {
			$tables = array_merge($tables, $this->modules[$module]['tables']);

			if (array_key_exists('zone', $this->modules[$module]) && $this->modules[$module]['zone'] != '' && $this->modules[$module]['zone']) {
				$zones []= $this->modules[$module]['zone'];
			}
		}

		foreach ($tables as $table) {
			if ($table != '_towns') {
				set_time_limit(120);
				$output .= $this->install_table($table);
				$output .= $this->install_model($table);	
			}
		}

		return $output;
	}

	protected function install_table($table) {

		if (!$this->db) {
			$this->force_db_connexion();
		}

		$file = 'Core/Base/install/tables/'.$table.'.sql';

		if (file_exists($file)) {
			$sql = file_get_contents($file);
			$this->db->classic_query($sql);

			return '';

		} else {
			return "Le fichier table ".$file." est introuvable<br>";
		}
	}

	protected function install_model($table) 
	{
		$file = 'Core/Base/install/models/'.$table.'.php';
		$newfile = 'config/models/'.$table.'.php';

		if (file_exists($file)) {

			if (!copy($file, $newfile)) {
			    return "La copie du fichier model ".$file." a échoué<br>";
			}
			return '';

		} else if (!in_array($table, $this->no_model)) {	
			return "Le fichier model ".$file." est introuvable<br>";

		} else {
			return '';
		}
	}

	protected function force_db_connexion () 
	{	
		$this->db = Service::Db();
		$this->db->setDatabase();
		$this->db->setConnexion();
	}

	protected function copy_dir($dir2copy, $dir_paste) {
	  // On vérifie si $dir2copy est un dossier
	  if (is_dir($dir2copy)) {
	 
	    // Si oui, on l'ouvre
	    if ($dh = opendir($dir2copy)) {     
	      // On liste les dossiers et fichiers de $dir2copy
	      while (($file = readdir($dh)) !== false) {
	        // Si le dossier dans lequel on veut coller n'existe pas, on le créé
	        if (!is_dir($dir_paste)) mkdir ($dir_paste, 0755);
	 
	          // S'il s'agit d'un dossier, on relance la fonction rÃ©cursive
	          if(is_dir($dir2copy.$file) && $file != '..'  && $file != '.') $this->copy_dir ( $dir2copy.$file.'/' , $dir_paste.$file.'/' );     
	            // S'il sagit d'un fichier, on le copue simplement
	            elseif($file != '..'  && $file != '.') copy ( $dir2copy.$file , $dir_paste.$file );                                       
	         }
	 
	      // On ferme $dir2copy
	      closedir($dh);
	 
	    }
	  }
	}

	protected function delete_dir($strDirectory)
	{
	    $handle = opendir($strDirectory);
	    while(false !== ($entry = readdir($handle))){
	        if($entry != '.' && $entry != '..'){
	            if(is_dir($strDirectory.'/'.$entry)){
	                $this->delete_dir($strDirectory.'/'.$entry);
	            }
	            elseif(is_file($strDirectory.'/'.$entry)){
	                unlink($strDirectory.'/'.$entry);
	            }
	        }
	    }
	    rmdir($strDirectory.'/'.$entry);
	    closedir($handle);
	}

}
?>