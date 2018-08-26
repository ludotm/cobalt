<?php
namespace Core\Highcharts;

use Core\Highcharts\Stat;

class StatBlock extends Chart 
{
	public $stats = array();
	public $id;

	public function __construct($id)
	{
		$this->id = $id;
		parent::__construct();
	}

	public function add_stat($stat=null)
	{
		if (!$stat) {
			$this->stats []= new Stat();
		} else {
			$this->stats []= $stat;
		}
  		
	  	return $this->stats[(count($this->stats)-1)];
	}

	public function render_content($cols)
	{
		$html = '';
		$inner_cols = $cols == 12 ? 3 : 6 ;
		foreach ($this->stats as $stat) {
			$html .= $stat->render($inner_cols);
		}
		return $html;
	}

	public function render_js($div_id)
	{
		return '';
	}
}
?>