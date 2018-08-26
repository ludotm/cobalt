<?php

namespace Superadmin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Table;
use \Core\Tools;

use \Core\Highcharts;


class StatsController extends BaseController
{

	public function onDispatch()
	{
		if (!$this->is_superadmin() || $this->is_commercial() || $this->is_support()) {
			Service::error("Vos droits ne vous permettent pas d'accéder à cette zone");
		}

		if ($this->request->isPost()) {

			if (isset($this->request->post->date1)) {
				$this->session->set('daterange1', implode('-',array_reverse(explode('/', $this->request->post->date1))));
			}
			if (isset($this->request->post->date2)) {
				$this->session->set('daterange2', implode('-',array_reverse(explode('/', $this->request->post->date2))));
			}
		}
	}

	/* ----------------------------------------- STATISTIQUES------------------------------------------------ */

	public function page_stats()
	{
		$this->set_title('Statistiques');
		$this->add_plugin('highcharts');
		$this->add_plugin('date_picker');

		$form_date_model = array(
			'date1' => array('type' => 'DATE'),
			'date2' => array('type' => 'DATE'),
		);

		$form_date = new Form($form_date_model);
		$form_date->action('')->id('datepicker-form');
		$form_date->factorize();
		$form_date->get('date1')->value($this->session->defined('daterange1') ? $this->session->get('daterange1') : null)->options(array('endDate'=> '0'));
		$form_date->get('date2')->value($this->session->defined('daterange2') ? $this->session->get('daterange2') : null)->options(array('endDate'=> '0'));

		$this->render(array(
			'form_date' => $form_date,
		));
	}

	public function widget_stats()
	{
		$action = $this->request->fromRoute('action', 'clients');

		switch ($action) {

			case 'clients':

				$this->render(array(
		            'action' => $action,
		            //'statsblock_clients' => $this->get_stats('statsblock_clients'),
		            //'pie_packs' => $this->get_stats('pie_packs'),
		            //'spline_convertions' => $this->get_stats('spline_convertions'),
		        ));
				break;
		}
	}

	protected function get_stats ($type, $var=null)
	{
		$date1 = $this->session->defined('daterange1') ? $this->session->get('daterange1') : null;
		$date2 = $this->session->defined('daterange2') ? $this->session->get('daterange2') : null;

		$color1 = '#d94561';
		$color2 = '#2d87a4';
		$color2 = '#31b478';
		$color2 = '#119fce'; //
		//$color2 = '#119fce'; //blue //good
		//$color2 = '#a760a5'; // purple

		$color2 = '#e41c1c';
		$color3 = '#da6238';
		$color4 = '#f1962b';
		$color5 = '#e2c036';
		$color6 = '#aec92f';
		$color7 = '#49b94b';
		$color8 = '#49b9a3';
		$color9 = '#119fce';
		$color10 = '#5b5eda';

		$color_men = '#119fce';
		$color_women = '#d94561';

		$colors = array($color1, $color2, $color3, $color4, $color5, $color6, $color7, $color8, $color9, $color10);
		$colors = array('#f78c01', '#dd4d1b', '#c52846', '#95346a', '#7f3596', '#147ba8', '#629900',    '#f78c01', '#dd4d1b', '#c52846', '#95346a', '#7f3596', '#147ba8', '#629900' );
		/*
		'#95346a' purple
		'#7f3596' violet
		'#b92211' rouge
		'#dd4d1b' orange
		'#f79a01' jaune
		'#f78c01' jaune orangé
		'#629900' vert
		'#147ba8' bleu
		*/
		$chart = null;

		switch ($type) {

			/* ---------------------------- PROSPECTS --------------------------------------- */	
			/*
			case 'statsblock_clients':

				$sql_date = ($date1 && $date2) ? ' AND DATE(date_create)>="'.$date1.'" AND DATE(date_create)<="'.$date2.'"' : '';
				//$clients = $this->db->query('SELECT id, date_free_lesson_1, date_free_lesson_2, lesson_done_1, lesson_done_2, inscription FROM clients WHERE id_big_user="'.$this->session->get('id_big_user').'" AND deleted="0" '.$sql_date);

				$clients = $this->db->query_count('SELECT id FROM _big_users WHERE deleted="0" '.$sql_date);
				$clients_abonnes = $this->db->query_count('SELECT id FROM _big_users WHERE deleted="0" AND engagement>="'.date('Y-m-d').'" '.$sql_date);
				$convertion_rate = \Core\Tools::perc($clients,$clients_abonnes,0);

				$chart = new Highcharts\StatBlock('statsblock_prospects');
				$chart->big_title('En bref');
				$chart->add_stat()->label('Prospects(s)')->icon($this->icon('user','x4'))->value($clients);
				$chart->add_stat()->label('Abonné(s)')->icon($this->icon('pencil','x4'))->value($clients_abonnes);
				$chart->add_stat()->label('Taux de conversion <br>prospects / abonnés')->icon($this->icon('pie-chart','x4'))->value($convertion_rate.'%');
				break;

			case 'pie_packs':
				//Total Contacts avec prise de coordonnées mais sans RDV
				$sql_date = ($date1 && $date2) ? ' AND date>="'.$date1.'" AND date<="'.$date2.'"' : '';

				$packs = $this->db->query('SELECT DISTINCT(id_big_user), value FROM historique_abonnements WHERE action="1" '.$sql_date.' GROUP BY id_big_user ORDER BY date DESC');

				$selected_packs = array();
				$data = array();

				if ($packs) {
					foreach($packs as $pack) {
						if ($pack->value != 'pack0') {
							if (array_key_exists($pack->value, $selected_packs)) {
								$selected_packs[$pack->value]++;
							} else {
								$selected_packs[$pack->value] = 1;
							}
						}
					}
				}

				foreach ($selected_packs as $key => $value) {
					$data []= array($key, $value);
				}

				$chart = new Highcharts\Pie($type);
				$chart->big_title('Répartition des packs');
				$chart->add_serie('Packs', $data, $colors );
				break;

			case 'spline_convertions':

				$sql_date = ($date1 && $date2) ? ' AND DATE(date_create)>="'.$date1.'" AND DATE(date_create)<="'.$date2.'"' : '';
				$sql_date2 = ($date1 && $date2) ? ' AND DATE(date_contract)>="'.$date1.'" AND DATE(date_contract)<="'.$date2.'"' : '';
				$data_prospects = $data_inscriptions = array();

				$prospects = $this->db->query('SELECT DATE(date_create) as date, COUNT(id) as count FROM _big_users WHERE deleted="0" '.$sql_date.' GROUP BY DATE(date_create) ORDER BY date_create ASC');

				if ($prospects)
				foreach ($prospects as $prospect) {
					$current_date = date('Y-m', strtotime($prospect->date)).'-01';
					if (!empty($data_prospects) && $current_date == $data_prospects[(count($data_prospects)-1)][0] ) {
						$data_prospects[(count($data_prospects)-1)][1] += intval($prospect->count);
					} else {
						$data_prospects []= array($current_date, intval($prospect->count));
					}
				}

				$inscriptions = $this->db->query('SELECT DATE(date_contract) as date, COUNT(id) as count FROM _big_users WHERE deleted="0" AND date_contract!="0000-00-00" '.$sql_date.' GROUP BY DATE(date_contract) ORDER BY date_contract ASC');

				if ($inscriptions)
				foreach ($inscriptions as $inscription) {
					$current_date = date('Y-m', strtotime($inscription->date)).'-01';
					if (!empty($data_inscriptions) && $current_date == $data_inscriptions[(count($data_inscriptions)-1)][0] ) {
						$data_inscriptions[(count($data_inscriptions)-1)][1] += intval($inscription->count);
					} else {
						$data_inscriptions []= array($current_date, intval($inscription->count));
					}
				}

				$chart = new Highcharts\Spline($type);
				$chart->big_title('Taux de conversion dans le temps');
				$chart->legend(50,0)->zoom('x')->tooltip(' ')->area(false)->line_width(3);
				$chart->get_x_axis()->title('');
				$chart->get_y_axis()->title('');
				$chart->add_serie_date('Prospects', $data_prospects, $color1, '.7');
				$chart->add_serie_date('Abonnés', $data_inscriptions, $color9, '.7');
				break;
			*/
		}

		return $chart;
	}

}
