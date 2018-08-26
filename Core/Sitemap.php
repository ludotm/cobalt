<?php 

class Sitemap
{
	protected $table;
	protected $name;
	protected $elements;
	protected $template;
	
	public function __construct($table, $name) 
	{
		$this->table = $table;
		$this->name = $name;
	}
	

}

?>