<?php

namespace Front\Controllers;

use \Core\Service;

class FrontController extends BaseController
{

	public function onDispatch()
	{

	}

	/* ----------------------------------------- HOME------------------------------------------------ */

	public function page_home()
	{
		$this->set_title('Front');

		$this->render(array(
        ));
	}



}
