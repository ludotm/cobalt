<?php
namespace Core\Highcharts;

use Core\Highcharts\Lib\Axis;
use Core\Highcharts\Lib\Serie;

class Chart {

	public $template;
	public $big_title;
	public $cols;

	public function __construct()
	{
		$this->set_default_template();
	}

	protected function set_default_template()
	{
		ob_start();
		?>
		<div class="col-lg-{cols}">
		    <div class="block">
		        <div class="block-header">{title}</div>
		        <div class="block-body" id="{id}">{content}</div>
		    </div>
		</div>
		<?php 
		$this->template = ob_get_contents();
		ob_end_clean();
	}

	public function big_title($big_title)
	{
		$this->big_title = $big_title;
		return $this;
	}

	public function template($html)
	{
		$this->template = $html;
		return $this;
	}

	public function set($object, $property, $value)
	{
		$this->{$object}->{$property} = $value;
		return $this;
	}

	public function render($cols)
	{
		$content = $this->render_content($cols);
		$html = str_replace(array('{id}','{title}','{cols}','{content}'), array($this->id, $this->big_title, $cols, $content), $this->template);
		$html .= $this->render_js($this->id);

		return $html;
	}
}
?>