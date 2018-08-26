<?php 

namespace Core\Base\Controllers;

use Core\Controller;

class BaseController extends Controller 
{

	public function onDispatchZone() 
	{
		
	}
	
	/* --------------------- LAYOUTS ------------------------------*/

	public function layout_login() 
	{
		$this->render(array(
			
		));
	}

	public function layout_default() 
	{
		$this->render(array(
			
		));
	}

	public function layout_message() 
	{
		$this->render(array(
			
		));
	}

	public function layout_error() 
	{
		$this->render(array(
			
		));
	}

	public function layout_panel() 
	{
		$this->render(array(
			
		));
	}

	public function layout_install() 
	{
		$this->render(array(
			
		));
	}	
}