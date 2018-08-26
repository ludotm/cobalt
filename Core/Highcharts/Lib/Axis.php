<?php
namespace Core\Highcharts\Lib;

class Axis {

	public $title;

	public function __construct()
	{
		$this->title = '';
	}

	public function title($title)
	{
		$this->title = $title;
		return $this;
	}

	public function type($type)
	{
		$this->type = $type;
		return $this;
	}

	public function categories($categories)
	{
		$this->categories = $categories;
		return $this;
	}

	public function range($min, $max)
	{
		$this->min = $min;
		$this->max = $max;
		return $this;
	}
}
?>