<?php 

namespace Common\Controllers;

use Core\Controller;
use Core\Service;

class BaseController extends Controller 
{

	public function onDispatchZone() 
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

}