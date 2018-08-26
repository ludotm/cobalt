<?php 

namespace Core\Base\Controllers;

use Core\Page;
use Core\Service;

class ErrorController extends BaseController
{	 
	public function onDispatch() 
	{
		$this->set_layout('layout_error');
	}

	public function page_error() {

		$code = $this->request->fromRoute('code');

		$this->set_title(translate('Erreur '.$code));
		
		$this->render(array(
			'code' => $code,
        ));
	}

	public function widget_error(){

		$code = $this->request->fromRoute('code');
		http_response_code($code);

		switch($code) {
			
			case '401':
			default:
				$message = translate('Vous n\'êtes pas autorisé à accéder à ce contenu.');
				break;

			case '404':
			default:
				$message = translate('La page que vous recherchez n\'existe pas ou n\'est plus disponible.');
				break;
		}	
		
		$this->render(array(
			'message' => $message,
			'code' => $code,
        ));
	}
}
?>