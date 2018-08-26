<?php 

namespace Superadmin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Table;	
use \Core\Tools;

use \Core\Mailer;

class CampainController extends BaseController 
{
	public $sms_stop_message;

	public function onDispatch()
	{
		$this->sms_stop_message = 'STOP au 36184';
	}

	public function get_status ($campain) 
	{
		$status = array();

		// statuts template uniquement pour les emails
		if ($campain->get_table() == '_emails') {
			$status['template'] = $campain->id_template != 0 ? true : false ;
		} else {
			$status['template'] = true;
		}
		
		$status['target'] = $campain->target != '' ? true : false ;

		if ($campain->get_table() == '_emails') {
			if ($campain->subject != '' && $campain->from_mail != '' && $campain->from_name != '' && trim(strip_tags($campain->message)) != '') {
				$status['content'] = true;
			} else {
				$status['content'] = false;
			}
		} else  if ($campain->get_table() == '_sms') {
			if (trim(strip_tags($campain->message)) != '' && trim(strip_tags($campain->message)) != $this->sms_stop_message) {
				$status['content'] = true;
			} else {
				$status['content'] = false;
			}
		}
		

		if (intval($campain->timestamp) > 1) {
			$status['sent'] = 2; // envoyé
		} else if (intval($campain->timestamp) == 1) { 
			$status['sent'] = 3; // envoyé au provider, en attente d'une réponse
		} else if ($status['template'] && $status['target'] && $status['content']) {
			$status['sent'] = 1; // prêt à être envoyé
		} else {
			$status['sent'] = 0; // paramétrage manquant avant envoi
		}

		switch ($status['sent']) {
			case 0:
				$status['color'] = 'red';
				$status['label'] = 'Paramètres manquants';
				break;
			case 1:
				$status['color'] = 'orange';
				$status['label'] = 'Prêt à être envoyé';
				break;
			case 2:
				$status['color'] = 'green';
				$status['label'] = 'Campagne envoyée';
				break;
			case 3:
				$status['color'] = 'orange';
				$status['label'] = 'En cours d\'envoi';
				break;
		}
		
		return $status;
	}

	public function json_get_status()
	{
		$id_campain = $this->request->fromRoute('id');
		$type = $this->request->fromRoute('type', null);

		switch (strtoupper($type)) {
			case 'MAIL': $table = '_emails'; break;
			case 'SMS': $table = '_sms'; break;
			case 'MMS': $table = '_mms'; break;
			case 'VOCAL': $table = '_vocal_messages'; break;
			default: Service::error('Paramètre "type" manquant');
		}
		$campain = $this->db->select($table)->id($id_campain)->execute();

		$status = $this->get_status($campain);

		if ($campain->timestamp > 1) {
			$date_launch = \Core\Tools::convert_date($campain->timestamp, 'd F Y');
		} else {
			$date_launch = '-';
		}
		
		$this->render(array(
			'status' => $status,
			'id' => $id_campain, 
			'date_launch' => $date_launch,
		));
	}

	/* ----------------------------------------- CAMPAIN------------------------------------------------ */

	public function page_campain()
	{
		$this->add_plugin('color_picker');
		$this->add_plugin('text_editor');
		$this->add_plugin('highcharts');
		$this->add_script('campains.js');

		$this->set_title('Gestion des campagnes');
  
        $this->render(array(
        	
        ));    
	}
	public function widget_campain_mail()
	{	
		$id_campain = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', null);		
		$type = 'MAIL';

		if ($id_campain) {
			$campain = $this->db->select('_emails')->id($id_campain)->execute(); 
		}

		switch ($action) {
			
			case 'duplicate':

				$campain->id = 0;

				if (preg_match('#duplicata#',$campain->campain_title)) {

					$campain->campain_title = preg_replace_callback(
				        '#\(duplicata ?([0-9]+)?\)#',
				        function ($matches) {
				        	if (isset($matches[1])) {
				        		return '(duplicata '.($matches[1]+1).')';
				        	} else {
				        		return '(duplicata 2)';
				        	}
				        },
				        $campain->campain_title
				    );

				} else {
					$campain->campain_title = $campain->campain_title.' (duplicata)';
				}
				$campain->timestamp = 0;
				$campain->status = 0;
				$campain->id_common_provider = 0;
				$campain->id_user_create = $this->session->get('id_user');
				$success = $campain->save();

				if ($success) {
					$this->session->flash('La campagne a bien été dupliquée', 'success', true);
				} else {
					$this->session->flash('Une erreur s\'est produite lors de la duplication' , 'error', true);
				}
				Service::redirect($this->url('superadmin-widget-campain_mail', array('action'=>'list')));
				exit();
				break;

			case 'view':
				$active_tab = $this->request->fromRoute('active_tab','A');

				$this->render(array(
					'status' => $this->get_status($campain),
		            'campain' => $campain,
		            'action' => $action,
		            'active_tab' => $active_tab,
		            'type' => $type,
		        ));
				break;

			case 'list':
				$page = $this->request->fromRoute('page', 1);

				$table = new Table('_emails');
				$table->sql('SELECT c.*, u.name FROM _emails c LEFT JOIN _users u ON c.id_user_create=u.id WHERE c.id_big_user="'.$this->session->get('id_big_user').'" AND c.is_campain="1" AND c.deleted="0" ORDER BY id DESC', 'id');

				$title_callback = function ($value, $object) {
					return '<a href="#" data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_mail', array('id'=>$object->id, 'action'=>'view')).'?active_tab=A">'.$value.'</a>';
	            };

				$status_callback = function ($value, $object) {
					$status = $this->get_status($object);
					//$date = $status['sent'] == 2 ? '<br>Le '.\Core\Tools::convert_date($object->date_launch) : ''; 
	            	return '<span class="campain_status '.$status['color'].'">'.$this->icon('envelope').' <span>'.$status['label'].'</span></span>';
	            };
	            $content_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_mail', array('id'=>$object->id, 'action'=>'view')).'?active_tab=C" class="badge-content badge '.($status['content'] ? 'badge-green':'badge-red').'">Contenu</a>';
	            };
	            $template_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_mail', array('id'=>$object->id, 'action'=>'view')).'?active_tab=A" class="badge-template badge '.($status['template'] ? 'badge-green':'badge-red').'">Template</a>';
	            };
	            $target_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_mail', array('id'=>$object->id, 'action'=>'view')).'?active_tab=B" class="badge-target badge '.($status['target'] ? 'badge-green':'badge-red').'">Ciblage</a>';
	            };
	            $action_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	switch ($status['sent']) {
	            		case '0': $badge = 'badge-gray'; $label = "Envoyer"; break;
	            		case '1': $badge = 'badge-orange';  $label = "Envoyer"; break;
	            		case '2': $badge = 'badge-green';  $label = "Envoyé"; break; 
	            		case '3': $badge = 'badge-orange';  $label = "En cours d'envoi"; break; 
	            	}
	            	
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_mail', array('id'=>$object->id, 'action'=>'view')).'?active_tab=E" class="badge-action badge '.$badge.'">'.$label.'</a>';
	            
	            };
	            $stats_callback = function ($value, $object) {

	            	if ($object->timestamp>1 && $object->provider == 'spothit') {
	            		return '<a href="#" data-toggle="tooltip" data-original-title="Statistiques" data-modal-title="Statistiques pour cette campagne" data-ajax-modal="'.$this->url('admin-widget-one_stat', array('name'=>'pie_emails', 'var'=>$object->id)).'">'.$this->icon('bar-chart', 'lg').'</a>';
	            	}
	            };
	            $date_callback = function ($value, $object) {

	            	return '<span class="date_launch">'.($value > 1 ? \Core\Tools::convert_date($value) : '-').'</span>';
	            };
	            $mode_callback = function ($value, $object) {

	            	if ($object->timestamp>1) {
	            		if ($object->provider == 'spothit') {
	            			return 'Premium';	
	            		} else if ($object->provider == 'self') {
	            			return 'Basic';
	            		}
	            	}
	            	return '';
	            };

				$table->entity_id('id');
				$table->add_col('campain_title', 'Titre')->callback($title_callback);
				$table->add_col('name', 'Créé par')->hide_on('sm');
				//$table->add_col('date_launch', 'Date')->filter('date', 'd F Y');
				
				$table->add_col('', 'Template')->callback($template_callback);
				$table->add_col('', 'Ciblage')->callback($target_callback);
				$table->add_col('', 'Contenu')->callback($content_callback);
				$table->add_col('', 'Action')->callback($action_callback);
				$table->add_col('', 'Statut')->callback($status_callback);
				$table->add_col('timestamp', 'Date d\'envoi')->callback($date_callback);
				$table->add_col('', 'Mode')->callback($mode_callback);
				$table->add_col('', 'Stats')->callback($stats_callback);

				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Aperçu',
					'data-modal-width' => '700px',
					'data-ajax-modal' => $this->url('superadmin-widget-campain_mail', array('action'=>'view')).'/[ID]?active_tab=D',
					'icon' => $this->icon('eye', 'circle'),
				));
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Dupliquer',
					'data-confirm' => 'Etes-vous sûrs de vouloir dupliquer cette campagne?',
					'data-ajax' => $this->url('superadmin-widget-campain_mail', array('action'=>'duplicate')).'/[ID]',
					'icon' => $this->icon('files-o', 'circle'),
				));
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Modifier',
					'data-ajax' => $this->url('superadmin-widget-campain_mail', array('action'=>'form')).'/[ID]',
					'data-transition' => 'slide-left',
					'icon' => $this->icon('pencil', 'circle'),
				));
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Supprimer',
					'data-ajax' => $this->url('superadmin-widget-campain_mail', array('action'=>'delete')).'/[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer cette campagne ?',
					'icon' => $this->icon('remove', 'circle'),
				));

				$table->pager(array(
					'page' => $page,
					'url' => $this->url('superadmin-widget-campain_mail', array('action' => 'list')) .'/page/[PAGE]',
					//'count' => $this->db->query_count('SELECT * FROM _users'),
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
				));

				$this->render(array(
					'table' => $table,
		            'action' => $action,
		        ));
				break;

			case 'form':
				$form = new Form('_emails', isset($campain) ? $campain->id : null );
				
				$form->id('campain-form')->action($this->url('superadmin-widget-campain_mail', array('id'=> $id_campain, 'action' => 'form')))->ajax('slide-right');
				$form->factorize();
				$form->get('id_template')->options_query('SELECT id, name FROM _emails_templates WHERE id_big_user="'.$this->session->get('id_big_user').'"');
				
				$form->add_submit('Enregistrer');

				if ($this->request->isPost()) {

					$form->bind($this->request->post);

					if ($form->validate()) {

						$campain = $form->get_entity();
						$campain->is_campain = 1;
						$campain->id_big_user = $this->session->get('id_big_user'); // non automatique pour cette table

						if ($campain->save()) {
							//$form->reset();
							$this->session->flash('La campagne a bien été enregistrée', 'success', true);
							Service::redirect($this->url('superadmin-widget-campain_mail', array('action'=>'list')));

						} else {
							$this->session->flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						$this->session->flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_campain) {

					$form->bind($campain->get_data());
				}

				$editor_css = '';

				if ($id_campain != 0) {
					if ($campain->id_template != 0) {
						$template = $this->db->select('_emails_templates')->id($campain->id_template)->execute();
						$editor_css = $campain->get_template_css($template, ".note-editor .note-editing-area");	
					}
					if ($campain->timestamp > 1) {
						$form->disable();
					}
				}

		        $this->render(array(
		            'form' => $form,
		            'action' => $action,
		            'editor_css' => $editor_css,
		        ));
				break;

			case 'delete':
				$campain->trash();
				$this->session->flash('La campagne a bien été supprimé', 'warning', true);
				Service::redirect($this->url('superadmin-widget-campain_mail', array('action'=>'list')));
				break;
		}
	}

	/* SMS */

	public function widget_campain_sms()
	{	
		$id_campain = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', null);		
		$type = 'SMS';

		if ($id_campain) {
			$campain = $this->db->select('_sms')->id($id_campain)->execute(); 
		}

		switch ($action) {
			
			case 'duplicate':

				$campain->id = 0;

				if (preg_match('#duplicata#',$campain->campain_title)) {

					$campain->campain_title = preg_replace_callback(
				        '#\(duplicata ?([0-9]+)?\)#',
				        function ($matches) {
				        	if (isset($matches[1])) {
				        		return '(duplicata '.($matches[1]+1).')';
				        	} else {
				        		return '(duplicata 2)';
				        	}
				        },
				        $campain->campain_title
				    );

				} else {
					$campain->campain_title = $campain->campain_title.' (duplicata)';
				}
				$campain->timestamp = 0;
				$campain->id_common_provider = 0;
				$campain->provider = '';
				$campain->id_user_create = $this->session->get('id_user');
				$success = $campain->save();

				if ($success) {
					$this->session->flash('La campagne a bien été dupliquée', 'success', true);
				} else {
					$this->session->flash('Une erreur s\'est produite lors de la duplication' , 'error', true);
				}
				Service::redirect($this->url('superadmin-widget-campain_sms', array('action'=>'list')));
				exit();
				break;

			case 'view':
				$active_tab = $this->request->fromRoute('active_tab','A');

				$this->render(array(
					'status' => $this->get_status($campain),
		            'campain' => $campain,
		            'action' => $action,
		            'active_tab' => $active_tab,
		            'type' => $type,
		        ));
				break;

			case 'list':
				$page = $this->request->fromRoute('page', 1);

				$table = new Table('_sms');
				$table->sql('SELECT c.*, u.name FROM _sms c LEFT JOIN _users u ON c.id_user_create=u.id WHERE c.id_big_user="'.$this->session->get('id_big_user').'" AND c.is_campain="1" AND c.deleted="0" ORDER BY id DESC', 'id');

				$title_callback = function ($value, $object) {
					return '<a href="#" data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_sms', array('id'=>$object->id, 'action'=>'view')).'?active_tab=A">'.$value.'</a>';
	            };

				$status_callback = function ($value, $object) {
					$status = $this->get_status($object);
					//$date = $status['sent'] == 2 ? '<br>Le '.\Core\Tools::convert_date($object->date_launch) : ''; 
	            	return '<span class="campain_status '.$status['color'].'">'.$this->icon('commenting').' <span>'.$status['label'].'</span></span>';
	            };
	            $content_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_sms', array('id'=>$object->id, 'action'=>'view')).'?active_tab=B" class="badge-content badge '.($status['content'] ? 'badge-green':'badge-red').'">Contenu</a>';
	            };
	            $target_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_sms', array('id'=>$object->id, 'action'=>'view')).'?active_tab=A" class="badge-target badge '.($status['target'] ? 'badge-green':'badge-red').'">Ciblage</a>';
	            };
	            $date_callback = function ($value, $object) {
					if ($value>1) {
						return '<span class="date_launch">'.\Core\Tools::convert_date($value).'</span>';
					} else {
						return '-';
					}					
	            };
	            $action_callback = function ($value, $object) {
	            	$status = $this->get_status ($object);
	            	switch ($status['sent']) {
	            		case '0': $badge = 'badge-gray'; $label = "Envoyer"; break;
	            		case '1': $badge = 'badge-orange';  $label = "Envoyer"; break;
	            		case '2': $badge = 'badge-green';  $label = "Envoyé"; break; 
	            		case '3': $badge = 'badge-orange';  $label = "En cours d'envoi"; break; 
	            	}
	            	
	            	return '<a data-modal-width="700px" data-ajax-modal="'.$this->url('superadmin-widget-campain_sms', array('id'=>$object->id, 'action'=>'view')).'?active_tab=C" class="badge-action badge '.$badge.'">'.$label.'</a>';
	            };
	            $stats_callback = function ($value, $object) {

	            	if ($object->timestamp>1 && $object->provider == 'spothit_premium') {
	            		return '<a href="#" data-toggle="tooltip" data-original-title="Statistiques" data-modal-title="Statistiques pour cette campagne" data-ajax-modal="'.$this->url('admin-widget-one_stat', array('name'=>'pie_sms', 'var'=>$object->id)).'">'.$this->icon('bar-chart', 'lg').'</a>';
	            	}
	            };
	            $mode_callback = function ($value, $object) {

	            	if ($object->timestamp>1) {
	            		if ($object->provider == 'spothit_premium') {
	            			return 'Premium';	
	            		} else if ($object->provider == 'spothit_lowcost') {
	            			return 'Basic';
	            		}
	            	}
	            	return '';
	            };

				$table->entity_id('id');
				$table->add_col('campain_title', 'Titre')->callback($title_callback);
				$table->add_col('name', 'Créé par')->hide_on('sm');;
				$table->add_col('', 'Ciblage')->callback($target_callback);
				$table->add_col('', 'Contenu')->callback($content_callback);
				$table->add_col('', 'Action')->callback($action_callback);
				$table->add_col('', 'Statut')->callback($status_callback);
				$table->add_col('timestamp', 'Date d\'envoi')->callback($date_callback);
				$table->add_col('', 'Mode')->callback($mode_callback);
				$table->add_col('', 'Stats')->callback($stats_callback);
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Dupliquer',
					'data-confirm' => 'Etes-vous sûrs de vouloir dupliquer cette campagne?',
					'data-ajax' => $this->url('superadmin-widget-campain_sms', array('action'=>'duplicate')).'/[ID]',
					'icon' => $this->icon('files-o', 'circle'),
				));
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Modifier',
					'data-ajax' => $this->url('superadmin-widget-campain_sms', array('action'=>'form')).'/[ID]',
					'data-transition' => 'slide-left',
					'icon' => $this->icon('pencil', 'circle'),
				));
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Supprimer',
					'data-ajax' => $this->url('superadmin-widget-campain_sms', array('action'=>'delete')).'/[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer cette campagne ?',
					'icon' => $this->icon('remove', 'circle'),
				));

				$table->pager(array(
					'page' => $page,
					'url' => $this->url('superadmin-widget-campain_sms', array('action' => 'list')) .'/page/[PAGE]',
					//'count' => $this->db->query_count('SELECT * FROM _users'),
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
				));

				$this->render(array(
					'table' => $table,
		            'action' => $action,
		        ));
				break;

			case 'form':
				$form = new Form('_sms', isset($campain) ? $campain->id : null );
				
				$form->id('campain-form')->action($this->url('superadmin-widget-campain_sms', array('id'=> $id_campain, 'action' => 'form')))->ajax('slide-right');
				$form->factorize();
				$form->add_submit('Enregistrer');

				if ($this->request->isPost()) {

					$form->bind($this->request->post);

					if ($form->validate()) {

						$campain = $form->get_entity();
						$campain->is_campain = 1;
						$campain->id_big_user = $this->session->get('id_big_user'); // non automatique pour cette table

						if ($campain->save()) {
							//$form->reset();
							$this->session->flash('La campagne a bien été enregistrée', 'success', true);
							Service::redirect($this->url('superadmin-widget-campain_sms', array('action'=>'list')));

						} else {
							$this->session->flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						$this->session->flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_campain) {

					$form->bind($campain->get_data());
				}

				$editor_css = '';

				if ($id_campain != 0) {
					if ($campain->timestamp != 0) {
						$form->disable();
					}
				}

				if (!$form->get('message')->value || $form->get('message')->value == '') {
					$form->get('message')->value = $this->sms_stop_message;
				}

		        $this->render(array(
		            'form' => $form,
		            'action' => $action,
		            'editor_css' => $editor_css,
		        ));
				break;

			case 'delete':
				$campain->trash();
				$this->session->flash('La campagne a bien été supprimé', 'warning', true);
				Service::redirect($this->url('superadmin-widget-campain_sms', array('action'=>'list')));
				break;
		}
	}

	/* ----------------------------------------- SELECT TEMPLATE------------------------------------------------ */

	public function widget_select_template() 
	{
		$id_campain = $this->request->fromRoute('id', 0);

		if ($id_campain) {
			$campain = $this->db->select('_emails')->id($id_campain)->execute(); 
		}

		$form = new Form('_emails');
		$form->action($this->url('superadmin-widget-select_template', array('id'=>$id_campain)))->ajax('none')->attr('data-ajax-callback','update_status');
		$form->factorize();
		$form->remove_all_except(array('id_template'));
		$form->get('id_template')->options_query('SELECT id, name FROM _emails_templates WHERE id_big_user="'.$this->session->get('id_big_user').'"');
		
		$form->add_submit('Séléctionner');

		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			if ($form->validate()) {

				$campain = $form->get_entity();
				
				if ($success = $campain->save()) {
					
					$campain->bind_from_db();

					Service::flash('Le template a bien été séléctionné', 'success', true);
				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}
				$form->bind($campain);
			}

		} else {
			$form->bind($campain);
		}

		if ($campain->timestamp != 0) {
			$form->disable();
		}

		$this->render(array(
            'form' => $form,
        ));
	}

	/* ----------------------------------------- CIBLAGE------------------------------------------------ */

	public function widget_target_campain()
	{
		$id_campain = $this->request->fromRoute('id', 0);
		$type = $this->request->fromRoute('type', null);

		switch (strtoupper($type)) {
			case 'MAIL': $table = '_emails'; break;
			case 'SMS': $table = '_sms'; break;
			case 'MMS': $table = '_mms'; break;
			case 'VOCAL': $table = '_vocal_messages'; break;
			default: Service::error('Paramètre "type" manquant');
		}
		$campain = $this->db->select($table)->id($id_campain)->execute(); 

		$nb_users = '';
		$user_list = '';

		if ($campain->target != '') {

			if ($campain->timestamp != 0) {
				$user_list = $campain->send_to;

			} else {

				switch (strtoupper($type)) {
					case 'MAIL': 
						$user_list = $this->get_emails ($campain->target); 
						break;

					case 'SMS':
					case 'MMS':
					case 'VOCAL': 
						$user_list = $this->get_phones ($campain->target);
						break;
				}	
			}
					
			$count_users = explode(';', $user_list);
			$nb_users = count($count_users) > 0 ? count($count_users)-1 : 0;
		}

		$model = array(
			'status' => array(
				'type' => 'SELECT',
				'options' => array(
					1 => 'A configurer',
					2 => 'En attente',
					3 => 'En période d\'essai',
					4 => 'Abonnés',
					5 => 'Archivés',
				),
				'empty_option' => array('', 'Indifférent'),
				'label' => 'Statut',
			), 
			'motif' => array(
				'type' => 'SELECT',
				'empty_option' => array('', 'Indifférent'),
				'label' => 'Motif de non inscription',
			),
		);
		
		$motifs = _model('_big_users');
		$motifs = $motifs['fields']['motif']['options'];

		$form = new Form($model);
		$form->action($this->url('superadmin-widget-target_campain', array('id'=>$id_campain, 'type'=> $type)))->ajax('none')->attr('data-ajax-callback','update_status');
		$form->factorize();
		$form->get('motif')->options($motifs);
		$form->add_submit('Filtrer');

		if ($this->request->isPost()) {
			
			$post = $this->request->post;
			$form->bind($this->request->post);

			if ($form->validate()) {

				$target = json_encode($post);

				switch (strtoupper($type)) {
					case 'MAIL': 
						$user_list = $this->get_emails ($target); 
						break;

					case 'SMS':
					case 'MMS':
					case 'VOCAL': 
						$user_list = $this->get_phones ($target);
						break;
				}	
				$count_users = explode(';', $user_list);
				$nb_users = count($count_users) > 0 ? count($count_users)-1 : 0;

				$success = $this->db->update($table)->values(array('target'=>$target, 'send_to'=>$user_list))->id($id_campain)->execute();

				if ($success) {
					Service::flash('Le ciblage a bien été défini', 'success', true);
				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}
				
			}

		} else if ($campain->target != '') {
			$form->bind(json_decode($campain->target));
		}

		if ($campain->timestamp != 0) {
			$form->disable();
		}

		$this->render(array(
            'form' => $form,
       		'nb_users' => $nb_users,
        ));
	}

	/* ----------------------------------------- SET CONTENT ------------------------------------------------ */

	public function widget_campain_content_mail() 
	{
		$id_campain = $this->request->fromRoute('id', 0);

		$campain = $this->db->select('_emails')->id($id_campain)->execute();

		if ($campain->id_template != 0) {
			$template = $this->db->select('_emails_templates')->id($campain->id_template)->execute();
			$editor_css = $campain->get_template_css($template, ".note-editor .note-editing-area");	
		} else {
			$editor_css = '';
		}

		$sent = $campain->timestamp != 0 ? true : false;

		$form = new Form('_emails', $id_campain);
		$form->set_template(array(
			'header' => ''."\n\t",
			'body' => '<div class="row"><div class="form-group"><label for="" class="col-md-12 control-label">[label]</label><div class="col-md-12">[element]</div><div class="clear"></div></div></div>'."\n\t",
			'footer' => ''."\n\t"
		));
		$form->action($this->url('superadmin-widget-campain_content_mail', array('id'=>$id_campain)))->ajax('none')->attr('data-ajax-callback','update_status'); 
		$form->factorize();
		$form->get('message')->no_popup = true;
		$form->remove_all_except(array('from_mail', 'from_name', 'subject', 'message', 'id_image'));
		$form->add_submit('Enregistrer');

		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			if ($form->validate()) {

				$campain = $form->get_entity();

				if ($success = $campain->save()) {
					Service::flash('Le contenu du mail a bien été enregistré', 'success', true);
				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}
				$form->bind($campain);
			}

		} else {
			$form->bind($campain);
		}

		if ($sent) {
			$form->disable();
		}

		$this->render(array(
            'form' => $form,
            'editor_css' => $editor_css,
        ));
	}


	public function widget_campain_content_sms() 
	{
		$id_campain = $this->request->fromRoute('id', 0);

		$campain = $this->db->select('_sms')->id($id_campain)->execute();

		$sent = $campain->timestamp != 0 ? true : false;

		$form = new Form('_sms', $id_campain);
		$form->action($this->url('superadmin-widget-campain_content_sms', array('id'=>$id_campain)))->ajax('none')->attr('data-ajax-callback','update_status'); 
		$form->factorize();
		$form->remove_all_except(array('from_num', 'message'));
		$form->add_submit('Enregistrer');

		if ($this->request->isPost()) {

			$form->bind($this->request->post);

			if ($form->validate()) {

				$campain = $form->get_entity();

				if ($success = $campain->save()) {
					Service::flash('Le contenu du SMS a bien été enregistré', 'success', true);
				} else {
					Service::flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
				}
				$form->bind($campain);
			}

		} else {
			$form->bind($campain);
		}

		if ($sent) {
			$form->disable();
		}

		if (!$form->get('message')->value || $form->get('message')->value == '') {
			$form->get('message')->value = $this->sms_stop_message;
		}

		$this->render(array(
            'form' => $form,
        ));
	}

	/* ----------------------------------------- RENDER------------------------------------------------ */

	public function widget_render_mail()
	{
		$id_campain = $this->request->fromRoute('id', 0);
		$campain = $this->db->select('_emails')->id($id_campain)->execute();

		$this->render(array(
            'id_template' => $campain->id_template,
            'id_campain' => $id_campain,
        ));
	}

	public function page_mail() 
	{
		$id_campain = $this->request->fromRoute('id', 0);
		
		$campain = $this->db->select('_emails')->id($id_campain)->execute();
		$campain->render_mail($campain->id_template);
	}


	/* ----------------------------------------- SEND------------------------------------------------ */

	public function widget_launch_campain() 
	{
		$id_campain = $this->request->fromRoute('id', 0);
		$type = $this->request->fromRoute('type', null);

		//$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();
		$can_use_spothit = true;

		$show_tarif = true;
		$show_cost = true;

		switch (strtoupper($type)) {
			case 'MAIL': $table = '_emails'; break;
			case 'SMS': $table = '_sms'; break;
			case 'MMS': $table = '_mms'; break;
			case 'VOCAL': $table = '_vocal_messages'; break;
			default: Service::error('Paramètre "type" manquant');
		}
		$campain = $this->db->select($table)->id($id_campain)->execute(); 

		$status = $this->get_status($campain);

		$sendable = $status['sent'] > 0 ? true : false;
		$sent = $status['sent'] == 2 || $status['sent'] == 3 ? true : false ;

		if (!$sent) {
			if ($status['target']) {
				switch (strtoupper($type)) {
					case 'MAIL': 
						$campain->send_to = $this->get_emails ($campain->target); 
						break;
					case 'SMS': 
					case 'MMS': 
					case 'VOCAL': 
						$campain->send_to = $this->get_phones ($campain->target);
						break;
				}
				
			} else {
				$campain->send_to = '';
			}
			$this->db->query('UPDATE '.$table.' SET send_to="'.$campain->send_to.'" WHERE id="'.$id_campain.'"');
		}

		
		switch (strtoupper($type)) {
			
			case 'MAIL': 
				$max_emails = 100;
				$mail_list = explode (';', $campain->send_to);
				$count_mails = count($mail_list) - 1;

				$email_premium_price = _config('consumables.email_premium.spothit_price');
				$price_premium = $count_mails*$email_premium_price;

				$this->render(array(
					'count_mails' => $count_mails,
					'max_emails' => $max_emails,
					'user_list' => $campain->send_to,
					'campain' => $campain,
		            'sendable' => $sendable,
		            'sent' => $sent,
		            'type' => $type,
		            'email_premium_price' => $email_premium_price,
		            'price_premium' => $price_premium,
		            'can_use_spothit' => $can_use_spothit,
		            'show_tarif' => $show_tarif,
		            'show_cost' => $show_cost,
		        ));
				break;

			case 'SMS':  
				$sms_basic_price = _config('consumables.sms_basic.spothit_price');
				$sms_premium_price = _config('consumables.sms_premium.spothit_price');

				$phone_list = explode (';', $campain->send_to);
				$count_phone = count($phone_list) - 1;
				$nb_sms = $campain->get_sms_nb_concat($campain->get_sms_nb_chars($campain->message));

				// Si trop de caractères, pas de mode low cost possible
				$sendable_sms_lowcost = $nb_sms > 1 ? false : true ;

				$price_lowcost = $sendable_sms_lowcost ? $count_phone*$sms_basic_price : 0 ;
				$price_premium = $count_phone*$nb_sms*$sms_premium_price;

				$this->render(array(
					'count_phone' => $count_phone,
					'sendable_sms_lowcost' => $sendable_sms_lowcost,
					'user_list' => $campain->send_to,
					'campain' => $campain,
		            'sendable' => $sendable,
		            'sent' => $sent,
		            'type' => $type,
		            'sms_basic_price' => $sms_basic_price,
		            'sms_premium_price' => $sms_premium_price,
		            'price_lowcost' => $price_lowcost,
		            'price_premium' => $price_premium,
		            'can_use_spothit' => $can_use_spothit,
		            'show_tarif' => $show_tarif,
		            'show_cost' => $show_cost,
		        ));
				break;

			case 'MMS': 
			case 'VOCAL':
				break;
		}
	}

	public function json_send_campain() 
	{
		$id_campain = $this->request->fromRoute('id', 0);
		$type = $this->request->fromRoute('type', null);
		$provider = $this->request->fromRoute('provider', null);
		
		if (!$provider) {
			Service::error('Provider non reconnu');
		}
/*
		switch ($provider) {
			case 'spothit':
			case 'spothit_lowcost':
			case 'spothit_premium':
			$big_user = $this->db->select('_big_users')->id($this->session->get('id_big_user'))->execute();
			if (!$big_user->can_use_spothit()) {
				Service::error('Vous devez être abonné et avoir un compte Spothit pour utiliser les emails premium, SMS Basic ou Premium');
			}
			break;
		}
*/
		switch (strtoupper($type)) {
			case 'MAIL': $table = '_emails'; break;
			case 'SMS': $table = '_sms'; break;
			case 'MMS': $table = '_mms'; break;
			case 'VOCAL': $table = '_vocal_messages'; break;
			default: Service::error('Paramètre "type" manquant');
		}
		$campain = $this->db->select($table)->id($id_campain)->execute(); 
		
		$status = $this->get_status($campain);

		if ($status['sent'] == 1) {
			
			if (strtoupper($type) == 'MAIL') {

				$spothit_from_mail = _config('contact.email_spothit');

				if ($provider == 'spothit') {
					if ($campain->from_mail != $spothit_from_mail) {
						$campain->response_mail = $campain->from_mail;
						$campain->from_mail = $spothit_from_mail;
					}
				}

			} else if (strtoupper($type) == 'SMS') {

			}
			
			$campain->provider = $provider;
			$campain->save();

			$campain->set_params(array('unsuscribe_link'=>true));

			$response = $campain->send();
		}

		$errors = array();

		if ($status['sent'] == 0) {
			$msg = "La campagne n'est pas prête à être envoyée";
			$type_msg = 'warning';
		} else {
			if ($response === true) {
				$campain_sent = true;
				$msg = 'La campagne a été envoyée avec succès';
				$type_msg = 'success';
			} else {
				$errors = $response;
				$msg = "Erreur lors de l'envoi des emails, aucun email n'a été envoyé";
				$type_msg = 'error';
			}
		}

		$this->render(array(
			'campain_sent' => $campain_sent,
			'errors' => $errors,
			'msg' => $msg,
			'type_msg' => $type_msg,
        ));
	}


	protected function get_emails ($json_target) 
	{
		$mail_list = '';

		$target = json_decode($json_target);

		$sql_cond = $this->get_target_sql ($target);

		$to = $this->db->query('SELECT bu.id, bu.contact_prename, bu.contact_name, bu.name, bu.email FROM _big_users bu WHERE bu.email!="" AND bu.deleted="0" AND bu.stop_contact="0" '.$sql_cond);
		if ($to) {
			foreach ($to as $client) {
				$mail_list .= $client->email.'; ';
			}
		}

		return $mail_list;
	}

	protected function get_phones ($json_target) 
	{
		$num_list = '';

		$target = json_decode($json_target);

		$sql_cond = $this->get_target_sql ($target);

		$to = $this->db->query('SELECT bu.id, bu.contact_prename, bu.contact_name, bu.name, bu.mobile FROM _big_users bu WHERE bu.mobile!="" AND bu.deleted="0" AND bu.stop_contact="0" '.$sql_cond);
		if ($to) {
			foreach ($to as $client) {
				$num_list .= $client->mobile.'; ';
			}
		}

		return $num_list;
	}

	protected function get_target_sql ($target) 
	{
		$sql_cond = '';
		$sql_cond .= $target->motif != '' ? ' AND motif="'.$target->motif.'"' : '';			

		$sql_no_abonnement = ' AND (SELECT COUNT(ha.id) FROM historique_abonnements ha WHERE ha.id_big_user=bu.id AND ha.date<="'.date('Y-m-d').'" AND ha.action="1" AND ha.value!="pack0" ORDER BY ha.date DESC)="0"';
			
		if ($target->status != '') {
			switch ($target->status) {
				case '1': // A configurer
					$sql_cond .= $sql_no_abonnement.' AND bu.motif="0" AND bu.start_trial="0000-00-00" ';
					break;
				case '2': // En attente
					$sql_cond .= $sql_no_abonnement.' AND bu.motif="0" AND bu.end_trial<"'.date('Y-m-d').'" AND bu.end_trial!="0000-00-00"';
					break;
				case '3': // En période d\'essai
					$sql_cond .= $sql_no_abonnement.' AND bu.motif="0" AND bu.start_trial!="0000-00-00" AND bu.end_trial>="'.date('Y-m-d').'"';
					break;
				case '4': // Abonnés
					$sql_cond .= ' AND (SELECT ha.value FROM historique_abonnements ha WHERE ha.id_big_user=bu.id AND ha.date<="'.date('Y-m-d').'" AND ha.action="1" ORDER BY ha.date DESC LIMIT 1)!="pack0" AND (SELECT sa.card_status FROM _stripe_accounts sa WHERE sa.id_big_user=bu.id LIMIT 1)="1"';
					break;
				case '5': // Archivés
					$sql_cond .= ' AND bu.motif>"0"';
					break;
			}
		}
		return $sql_cond;
	}

	/* ----------------------------------------- TEMPLATE------------------------------------------------ */

	public function widget_template()
	{	
		if (!$this->permission('manager_campain')) {
			exit();
		}

		$id_template = $this->request->fromRoute('id', 0);
		$action = $this->request->fromRoute('action', null);		

		if ($id_template) {
			$template = $this->db->select('_emails_templates')->id($id_template)->execute();
		}

		switch ($action) {
			
			case 'list':
				$page = $this->request->fromRoute('page', 1);

				$table = new Table('template_list');
				$table->sql('SELECT t.*, u.name as username FROM _emails_templates t LEFT JOIN _users u ON t.id_user_create=u.id WHERE t.id_big_user="'.$this->session->get('id_big_user').'" ORDER BY id DESC', 'id');

				$table->entity_id('id');
				$table->add_col('name', 'Titre')->filter('bold');
				$table->add_col('username', 'Créé par')->attr('width','240px');
				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Aperçu',

					'data-ajax-modal' => $this->url('superadmin-widget-template', array('action'=>'view')).'/[ID]',
					'data-modal-title' => 'Aperçu',
					'data-modal-width' => '700px',

					'data-transition' => 'slide-left',
					'icon' => $this->icon('eye', 'circle'),
				))->attr('width','50px');

				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Modifier',
					'data-ajax' => $this->url('superadmin-widget-template', array('action'=>'form')).'/[ID]',
					'data-transition' => 'slide-left',
					'icon' => $this->icon('pencil', 'circle'),
				))->attr('width','50px');

				$table->add_col('', '')->url(array(
					'href' => '#',
					'data-toggle' => 'tooltip',
					'data-original-title' => 'Supprimer',
					'data-ajax' => $this->url('superadmin-widget-template', array('action'=>'delete')).'/[ID]',
					'data-confirm' => 'Etes-vous certain de vouloir supprimer ce template ?',
					'icon' => $this->icon('remove', 'circle'),
				))->attr('width','50px');

				$table->pager(array(
					'page' => $page,
					'url' => $this->url('superadmin-widget-template', array('action' => 'list')) .'/page/[PAGE]',
					//'count' => $this->db->query_count('SELECT * FROM _users'),
					'items_per_page' => 10,
					'ajax' => true,
					'align' => 'left',
				));

				$this->render(array(
					'table' => $table,
		            'action' => $action,
		        ));
				break;

			case 'form':
				$form = new Form('_emails_templates', $id_template);
				$form->id('template-form')->action($this->url('superadmin-widget-template', array('id'=> $id_template, 'action' => 'form')))->ajax('slide-right');
				$form->factorize();
				//$form->get('id_rank')->options_table('_ranks', 'id', 'name');
				$form->add_submit('Enregistrer');

				if ($this->request->isPost()) {

					$form->bind($this->request->post);

					if ($form->validate()) {

						$template = $form->get_entity();

						if ($template->save()) {
							//$form->reset();
							$this->session->flash('Le template a bien été enregistrée', 'success', true);
							Service::redirect($this->url('superadmin-widget-template', array('action'=>'list')));

						} else {
							$this->session->flash('Une erreur s\'est produite lors de l\'enregistrement', 'error');
						}
					} else {
						$this->session->flash('Le formulaire contient une ou plusieurs erreurs', 'warning');
					}

		        } else if ($id_template) {

					$form->bind($template->get_data());
				}

		        $this->render(array(
		            'form' => $form,
		            'action' => $action,
		        ));
				break;

			case 'view':

				$this->render(array(
					'action' => $action,
		            'template' => $template,
		        ));
				break;

			case 'delete':
				$template->delete();
				$this->session->flash('Le template a bien été supprimé', 'warning', true);
				Service::redirect($this->url('superadmin-widget-template', array('action'=>'list')));
				break;
		}
	}

	public function page_template_preview() 
	{
		$id_template = $this->request->fromRoute('id', 0);
		$mail = Service::Email();
		$mail->render_mail($id_template);
	}

}