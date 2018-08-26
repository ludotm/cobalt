<?php

namespace Core\Files;

use Core\Files\File;
use Core\Service;

class Document extends File
{	
	public function __construct()
	{
		parent::__construct('_documents');
	}

}

?>