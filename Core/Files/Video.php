<?php

namespace Core\Files;

use Core\Files\File;
use Core\Service;

class Video extends File
{	
	public $type;

	public function __construct()
	{
		$this->type = 'video';
	}

}

?>