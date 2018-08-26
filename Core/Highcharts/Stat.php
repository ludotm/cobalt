<?php
namespace Core\Highcharts;

class Stat
{
	public $value;
	public $icon;
	public $label;
	public $color;

	public $template;
	public $cols;

	public function __construct()
	{
		$this->color = '';
		$this->set_default_template();
	}

	protected function set_default_template()
	{
		ob_start();
		?>
		<div class="col-lg-{cols}">
		    <div class="one-stat-block"{color}>
		    	<div class="stat-icon">{icon}</div>
		    	<div class="stat-value">{value}</div>
		    	<div class="stat-label">{label}</div>
		    </div>
		</div>
		<?php 
		$this->template = ob_get_contents();
		ob_end_clean();
	}

	public function value($value)
	{
  		$this->value = $value;
	  	return $this;
	}
	
	public function icon($icon)
	{
  		$this->icon = $icon;
	  	return $this;
	}

	public function label($label)
	{
		$this->label = $label;
	  	return $this;
	}

	public function color($color)
	{
	  	$this->color = $color;
	  	return $this;
	}

	function render($cols)
	{
		$color = $this->color != '' ? ' style="color:'.$this->color.'"' : '' ;
	  	$html = str_replace(array('{label}','{icon}','{value}','{color}','{cols}'), array($this->label, $this->icon, $this->value, $color, $cols), $this->template);
	  	return $html;
	}
}
?>