<?php 

namespace Superadmin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Table;

class FacturesController extends BaseController 
{
	public $status;

	public function onDispatch()
	{
		if (!$this->is_superadmin() || $this->is_commercial() || $this->is_support()) {
			Service::error("Vos droits ne vous permettent pas d'accéder à cette zone");
		}
	}

	/* ----------------------------------------- PAGE / WIDGET CLIENTS------------------------------------------------ */

	public function page_factures()
	{
		$this->set_title('Gestion des factures');

		$ca = $this->db->query_one('SELECT SUM(total_ttc) as ca FROM _factures WHERE payment_status="1" AND id_big_user!="1"');
		$ca_total = $ca ? $ca->ca : 0;

		$date1 = date('d') >= '05' ? date('Y-m').'-05' : date('Y-m', strtotime('-1 month') ).'-05';
		$date2 = date('Y-m-d', strtotime($date1.' +1month'));

		$ca = $this->db->query_one('SELECT SUM(total_ttc) as ca FROM _factures WHERE payment_status="1" AND id_big_user!="1" AND start_date>="'.$date1.'" AND start_date<"'.$date2.'"');
		$ca_last_month = $ca ? $ca->ca : 0;

        $this->render(array(
        	'ca_total' => number_format($ca_total,2) ,
			'ca_last_month' => number_format($ca_last_month, 2),
        ));
	}
	public function widget_factures_list()
	{
		$page = $this->request->fromRoute('page', 1);

		if ($this->request->isPost()) {
			$month = $this->request->post->month;
			$year = $this->request->post->year;
			$id_big_user = $this->request->post->biguser;
			$status = $this->request->post->status;
		} else {
			$month = $this->request->fromRoute('month', 0);
			$year = $this->request->fromRoute('year', 0);
			$id_big_user = $this->request->fromRoute('id', 0);
			$status = $this->request->fromRoute('status', 0);
		}

		$all_big_users = $this->db->select('_big_users')->where('deleted="0"')->execute();

		$client_callback = function ($value, $object) {
			return  '<span class="bold">'.$object->name.'</span><br>'.\Core\Tools::convert_date($object->start_date, 'M Y');
		};
		$ref_callback = function ($value, $object) {
			return  '<b>'.$object->ref.'</b><br>'.$object->id_transaction;
		};
		$periode_callback = function ($value, $object) {
			return  'Du '.\Core\Tools::convert_date($object->start_date).'<br> au '.\Core\Tools::convert_date($object->end_date);
		};
		$price_callback = function ($value, $object) {
			return  $object->total_ht.' € HT<br>'.$object->total_ttc.' € TTC';
		};
		$paid_callback = function ($value, $object) {
			$html = '';
			switch ($object->charged) {
				case 0: $color1 = 'gray'; break;
				case 1: $color1 = 'green'; break;
				case 2:default: $color1 = 'red'; break;
			}
			switch ($object->payment_status) {
				case 0: $color2 = 'gray'; break;
				case 1: $color2 = 'green'; break;
				case 4:case 6:case 7: $color2 = 'orange'; break;
				default : $color2 = 'red'; break;
			}
			$html .= '<span class="badge badge-'.$color1.'">' .$object->get_option_value('charged').'</span><br>';
			$html .= '<span class="badge badge-'.$color2.'">' .$object->get_option_value('payment_status').'</span>';
			if ($object->charged == 2 || !in_array($object->payment_status, array(0,4,6,7,1))) {
				$html .= '<br><br><a href="#" data-ajax-json="'.$this->url('common-json-charge_facture', array('id'=>$object->id)).'" class="button button-xs">Retenter le paiement</a>';	
			}
			return $html;
		};
		$download_callback = function ($value, $object) {
			$client = $this->db->select('_big_users')->id($object->id_big_user)->execute();
			$html = '<a href="/'.$client->get_facture_link($object->ref, false).'" target="_blank">'.$this->icon('download').'</a>';
			return  $html;
		};

		$sql_cond = '';

		if ($id_big_user != 0) {
			$sql_cond .= ' AND bu.id="'.$id_big_user.'" ';
		}
		if ($status != 0) {
			$sql_cond .= ' AND payment_status!="1" ';
		} 
		if ($month != 0 && $year != 0) {
			$date1 = $year.'-'.$month.'-01';
			$date2 = date('Y-m-d', strtotime($date1.' +1 month'));
			$sql_cond .= ' AND start_date >="'.$date1.'" AND start_date <="'.$date2.'" ';			
		}

		$sql = 'SELECT f.*, bu.name FROM _factures f LEFT JOIN _big_users bu ON f.id_big_user=bu.id WHERE bu.deleted="0" '.$sql_cond.' ORDER BY start_date DESC';

		$table = new Table('_factures');
		$table->sql($sql, 'id');

		$table->add_col('name', 'Client')->callback($client_callback);
		$table->add_col('ref', 'Référence')->callback($ref_callback);
		$table->add_col('', 'Période')->callback($periode_callback);
		$table->add_col('total_ttc', 'Prix')->callback($price_callback);
		$table->add_col('', 'Statut')->callback($paid_callback);
		$table->add_col('', 'Télécharger')->callback($download_callback);

		$table->pager(array(
			'page' => $page,
			'url' => $this->url('superadmin-widget-factures_list', array('year'=>$year,'month'=>$month,'status'=>$status,'id'=>$id_big_user, 'page'=>'')).'[PAGE]' ,
			'items_per_page' => 15,
			'ajax' => true,
			'align' => 'left',
			'show_count' => true,
		));

		$this->render(array(
			'table' => $table,
			'month' => $month,
			'year' => $year,
			'status' => $status,
			'id_big_user' => $id_big_user,
			'all_big_users' => $all_big_users,
		));
	}

}