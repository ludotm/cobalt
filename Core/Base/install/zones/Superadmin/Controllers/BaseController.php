<?php 

namespace Superadmin\Controllers;

use Core\Controller;
use Core\Menu;

class BaseController extends Controller 
{

	public function onDispatchZone() 
	{
		$this->session->set('id_big_user', 0);
	}

	/* --------------------- MENUS ------------------------------*/

	public function menu_header() 
	{
		$menu = new Menu('menu-header');

		$menu->add_item()->label('Déconnexion')->icon($this->icon('power-off'))->url($this->session->logout_url()); 

		$menu->add_item()->label('Clients')->icon($this->icon('user'))->route('superadmin-page-clients');

		if (!$this->is_commercial() && !$this->is_support()) {
			$menu->add_item()->label('Factures')->icon($this->icon('list-alt'))->route('superadmin-page-factures');
			$menu->add_item()->label('Statistiques')->icon($this->icon('bar-chart'))->route('superadmin-page-stats');
			$menu->add_item()->label('Campagnes')->icon($this->icon('bullhorn'))->route('superadmin-page-campain'); 
			$menu->add_item()->label('Console dev')->icon($this->icon('terminal'))->url('/panel/clients'); 
			//$menu->add_submenu('params')->label('Paramètres')->icon($this->icon('cog'))->route('admin-page-users'); 
			//$menu->add_subitem('params')->label('Réseaux sociaux')->route('admin-page-social');
		}

		return $menu;
	}

	public function menu_footer() 
	{

	}

	/* --------------------- LAYOUTS ------------------------------*/

	public function layout() 
	{
		$timestamp = time() - (3600*24*10);
		$bad_crons = $this->db->select('_crons')->where('timestamp>:timestamp AND status="0"', array('timestamp'=>$timestamp))->execute();
		
		if ($bad_crons) {
			$message = "Un des script automatiques n'a pas pu s'executer correctement lors des 10 derniers jours, prevenir un technicien";
			$type_message = "warning";
			$color = 'red';	
		} else {
			$message = '';
			$type_message = '';
			$color = '';
		}

		$this->render(array(
			'message' => $message,
			'type_message' => $type_message,
			'color' => $color,
		));
	}	

	public function layout_modal() 
	{
		$this->render(array(

		));
	}

	public function layout_login() 
	{
		$this->render();
	}


	/* --------------------- WIDGETS ------------------------------*/


	public function comments()
	{
                
	}
}