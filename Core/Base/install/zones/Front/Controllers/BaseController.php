<?php 

namespace Front\Controllers;

use Core\Controller;
use Core\Menu;
use Core\Service;

class BaseController extends Controller 
{

	public function onDispatchZone() 
	{

	}

	/* --------------------- MENUS ------------------------------*/

	public function menu_header() 
	{
		$menu = new Menu('menu-header');

		$menu->add_item()->label('Accueil')->icon($this->icon('home'))->route('front-page-home'); 
		$menu->add_item()->label('Rubrique 1')->icon($this->icon('cog'))->url('#'); 
		$menu->add_item()->label('Rubrique 2')->icon($this->icon('cog'))->url('#'); 
		$menu->add_item()->label('Rubrique 3')->icon($this->icon('cog'))->url('#'); 

		return $menu;
	}

	public function menu_footer() 
	{

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