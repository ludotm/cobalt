<?php
namespace Core\Highcharts;

use Core\Highcharts\Chart;
use Core\Highcharts\Lib\Serie;

class Pie extends Chart 
{
	  public $serie;
	  public $subseries = array();

	  public function __construct($id)
	  {
	  	$this->id = $id;
		$this->title = '';
		$this->tooltip = true;
		$this->tooltip_format = '<b>{point.y}</b><br>{point.percentage:.1f}%';
		$this->labels = true;
		$this->labels_format = '<span style="font-weight:300">{point.name}</span><br><b>{point.y}</b><br>{point.percentage:.0f} %';
		$this->labels_color = 'black';

		parent::__construct();
	  }

	  public function add_serie($label, $data, $colors=null) 
	  {
	    $serie = new Serie();
	    $serie->label = $label;
	    $serie->data = $data;
	    $serie->colors = $colors;

	    $this->serie = $serie;
	    return $this;
	  }

	  public function add_subserie ($label, $data, $colors=null) 
	  {
			$subserie = new Serie();
			$subserie->label = explode('_',$label)[0];
			$subserie->id = \Core\Tools::clean_string($label);
		    $subserie->data = $data;
		    $subserie->colors = $colors;

			$this->subseries[$subserie->id] = $subserie;

		    return $this;
	  }

	  protected function convert_data ()
	  {
	  	$new_data = array();
	    foreach ($this->serie->data as $key => $data) {
	        $new_obj = new \stdClass();
	        $new_obj->name = explode('_',$data[0])[0];
	        $new_obj->y = $data[1];

	        $id = \Core\Tools::clean_string($data[0]);

	        if (array_key_exists($id, $this->subseries)) {
	        	$new_obj->drilldown = $id;
	        }

	        $new_data []= $new_obj;
	    }
	    return $new_data;
	  }



	  public function tooltip($bool)
	  {
	  	$this->tooltip = $bool;
	  	return $this;
	  }

	  public function tooltip_format($tooltip_format)
	  {
	  	$this->tooltip_format = $tooltip_format;
	  	return $this;
	  }

	  public function labels($bool)
	  {
	  	$this->labels = $bool;
	  	return $this;
	  }

	  public function labels_format($labels_format)
	  {
	  	$this->labels_format = $labels_format;
	  	return $this;
	  }

	  public function labels_color($labels_color)
	  {
	  	$this->labels_color = $labels_color;
	  	return $this;
	  }

	  public function title($title)
	  {
	  	$this->title = $title;
	  	return $this;
	  }

	  public function render_content($cols)
	  {
	  	return '';
	  }

	  public function render_js($div_id)
	  {
	  	ob_start();
	    ?>
	    <script>
	    $('#<?= $div_id ?>').highcharts({

			chart: {
	            plotBackgroundColor: null,
	            plotBorderWidth: null,
	            plotShadow: false
	        },
	        
	        title: {
	            text: '<?= $this->title ?>'
	        },
	        <?php if ($this->tooltip) : ?>
	        tooltip: {
	            pointFormat: '<?= $this->tooltip_format ?>'
	        },
	        <?php endif; ?>

	        plotOptions: {
	            pie: {
	                allowPointSelect: true,
	                cursor: 'pointer',
	                dataLabels: {
	                	<?php if ($this->labels) : ?>
	                    enabled: true,
	                    format: '<?= $this->labels_format ?>',
	                    style: {
	                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || '<?= $this->labels_color ?>'
	                    }
	                    <?php endif; ?>
	                }
	            },

	        },
	        series: [{
	            type: 'pie',
	            name: '<?= $this->serie->label; ?>',
	            
	            <?php if (is_array($this->serie->colors)) : ?>
	            colors : <?= json_encode($this->serie->colors); ?>,

	            <?php elseif (is_string($this->serie->colors)) : ?>
	            colors : highcharts_color_set('<?= $this->serie->colors ?>'),
	            
	            <?php else : ?>
	            colors : highcharts_color_set(Highcharts.getOptions().colors[0]),
	            <?php endif; ?>

	            <?php $this->serie->data = $this->convert_data($this->serie->data) ?>

	            data: <?= json_encode($this->serie->data); ?>
	        }],

	        <?php if (!empty($this->subseries)) : ?>

	        drilldown: {
	        	activeAxisLabelStyle: {textDecoration: 'none',},
		        activeDataLabelStyle: {textDecoration: 'none',},
		        drillUpButton: {
					position: { 
						align: 'right',
						x: 5,
						y: -10
					}
				},
	            series: [

	            <?php foreach ($this->subseries as $subseries) : ?>

	            
	            {type: 'pie',
	            	colors:['#FF0000'],
	                name: '<?= $subseries->label ?>',
	                id: '<?= $subseries->id ?>',

	                <?php if (is_array($subseries->colors)) : ?>
	        		colorByPoint: true,
					colors:<?= json_encode($subseries->colors) ?>,
	        		<?php elseif(is_string($subseries->colors)) : ?>
	        		colorByPoint: true,
	        		colors : highcharts_color_set('<?= $subseries->colors ?>'),
	        		<?php else: ?>
	        		colorByPoint: true,
	        		colors : highcharts_color_set(Highcharts.getOptions().colors[0]),
	        		<?php endif; ?>

	                data: <?= json_encode($subseries->data) ?>,
	            }, 

	            <?php endforeach; ?>
	            ]
	        }
	        <?php endif; ?>

	    });
	    </script>
	    <?php
	    return ob_get_contents();
	  }
}
?>