<?php

namespace Admin\Controllers;

use \Core\Service;
use \Core\Form;
use \Core\Tools;
use \Core\Table;

class ClientsController extends BaseController
{

	public function onDispatch()
	{

	}

	/* ----------------------------------------- PARAMS------------------------------------------------ */

	public function page_clients()
	{
		$this->set_title('Clients');

		$this->render(array(
        ));
	}

	/* ----------------------------------------- ADMINISTRATION ------------------------------------------------ */

	public function widget_clients()
	{

		$this->render(array(

        ));
	}

}
