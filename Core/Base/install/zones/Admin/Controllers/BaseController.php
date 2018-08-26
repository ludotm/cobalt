<?php 

namespace Admin\Controllers;

use Core\Controller;
use Core\Menu;
use Core\Service;

class BaseController extends Controller 
{
	public $active_modules;

	public function onDispatchZone() 
	{
		/*------------ NOTE D'INFORMATION ---------------*/
		if (!$this->session->defined('note_info')) {
			$message = false;
			$this->session->set('note_info', $message);
		}
	}


	/* --------------------- REDIRECTION DE L'URL '/' ------------------------------*/

	public function json_redirect_login()
	{
		Service::redirect($this->url('admin-page-login'));
		exit();
	}

	/* --------------------- MENUS ------------------------------*/

	public function menu_header() 
	{
		$menu = new Menu('menu-header');

		$menu->add_item()->label('Clients')->icon($this->icon('user'))->route('admin-page-clients'); 

		if ($this->is_admin() || $this->is_superadmin() || $this->permission('manage_params')) {
			$menu->add_item()->label('Paramètres')->icon($this->icon('cog'))->route('admin-page-params'); 
		}
		if ($this->is_superadmin()) {
			$menu->add_item()->label('Panel Superadmin')->url('/superadmin/clients')->blank();
			$menu->add_item()->label('Panel Dev')->url('/panel')->blank();  
		}

		return $menu;
	}

	public function menu_top() 
	{
		$menu = new Menu('menu-top');

		$menu->add_item()->label('&nbsp; Nous contacter')->icon($this->icon('envelope-o'))->url('#')->attr('data-ajax-modal', $this->url('admin-widget-contact'))->attr('data-modal-title','Contactez-nous');

		$menu->add_submenu('params')->label('&nbsp;'.$this->icon('cog', 'lg').'&nbsp; Mon compte')->url('#'); 

		$menu->add_subitem('params')->label('CGU')->icon($this->icon('check-square'))->url($this->url('admin-page-cgu'));

		$menu->add_subitem('params')->label('Déconnexion')->icon($this->icon('power-off'))->url($this->session->logout_url()); 
		
		return $menu;
	}

	public function menu_footer() 
	{

	}

	/* --------------------- RECUPERER DES PARAMETRES DE L'APPLI ------------------------------*/

	protected function get_params ()
	{
		$this->app_params = $this->db->query_one('SELECT * FROM _params WHERE id_big_user=:id_big_user', array('id_big_user'=>$this->session->get('id_big_user')));
		return $this->app_params;
	}

	/* --------------------- LAYOUTS ------------------------------*/

	public function layout() 
	{
		$this->render(array(
			
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