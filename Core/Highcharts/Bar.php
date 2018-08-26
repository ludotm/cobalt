<?php
namespace Core\Highcharts;

use Core\Highcharts\Chart;
use Core\Highcharts\Lib\Serie;
use Core\Highcharts\Lib\Axis;

class Bar extends Chart 
{
	  public $series = array();
	  public $subseries = array();
	  public $xAxis;
	  public $yAxis;
	  public $legend;

	  public function __construct($id)
	  {
	  	$this->id = $id;
		$this->title = '';
		$this->tooltip = true;
		$this->tooltip_format = '<span style="color:{point.color}">{point.name}</span>: <b>{point.y}{point.perc}</b><br/>';
		/*
		$this->labels = true;
		$this->labels_format = '<b>{point.name}</b>: {point.percentage:.1f} %';
		$this->labels_color = 'black';
		*/
		parent::__construct();
	  }

	  public function add_serie($label, $data, $colors=null) 
	  {
	    $serie = new Serie();
	    $serie->label = $label;
	    $serie->data = $data;
	    $serie->colors = $colors;

	    $this->series []= $serie;
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

	  protected function convert_data ($serie_data)
	  {
	  	$new_data = array();
	    foreach ($serie_data as $data) {
	        $new_obj = new \stdClass();
	        $new_obj->name = explode('_',$data[0])[0];
	        $new_obj->y = $data[1];
	        $new_obj->perc = isset($data[2]) ? '<br>'.$data[2].(is_numeric($data[2])?'%':'') : '';

	        $id = \Core\Tools::clean_string($data[0]);

	        if (array_key_exists($id, $this->subseries)) {
	        	$new_obj->drilldown = $id;
	        }

	        $new_data []= $new_obj;
	    }
	    return $new_data;
	  }

	  public function get_x_axis() 
	  {
	    if (!$this->xAxis) {
	      $this->xAxis = new Axis();
	    }
	    return $this->xAxis;
	  }

	  public function get_y_axis() 
	  {
	    if (!$this->yAxis) {
	      $this->yAxis = new Axis();
	    }
	    return $this->yAxis;
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

	  public function legend($x, $y) 
	  {
	    $this->legend = true;
	    $this->legend_x = $x;
	    $this->legend_y = $y;
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
	            type: 'column'
	        },
	        title: {
	            text: '<?= $this->title ?>'
	        },
	        xAxis: {
	            <?php if (isset($this->xAxis->type)) : ?>
	            type: "category",
	            <?php endif; ?>
	        },
	        yAxis: {
	            title: {
	                text: "<?= $this->yAxis->title == '' ? ' ' : $this->yAxis->title ?>"
	            }
	        },
	        
	        <?php if ($this->legend && count($this->series)>1) : ?>
	        legend: {
	            layout: 'vertical',
	            align: 'left',
	            verticalAlign: 'top',
	            x: <?= $this->legend_x ?>,
	            y: <?= $this->legend_y ?>,
	            floating: true,
	            borderWidth: 1,
	            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
	        },
	        <?php endif; ?>

	        plotOptions: {
	            series: {
	                borderWidth: 0,
	                dataLabels: {
	                    enabled: true,
	                    format: '{point.y}'
	                }
	            }
	        },

	        <?php if ($this->tooltip) : ?>
	        tooltip: {
	            headerFormat: '{series.name}<br>',
	            pointFormat: '<?= $this->tooltip_format ?>'
	        },
	        <?php endif; ?>

	        series: [

	        <?php foreach ($this->series as $serie) : ?>

	        	<?php $serie->data = $this->convert_data($serie->data); ?>

	        	{
	        		name: "<?= $serie->label ?>",

	        		<?php if (is_array($serie->colors)) : ?>
	        		colorByPoint: true,
					colors:<?= json_encode($serie->colors) ?>,
	        		<?php elseif(is_string($serie->colors)) : ?>
	        		//colorByPoint: true,
	        		//colors : highcharts_color_set('<?= $serie->colors ?>'),
	        		color:'<?= $serie->colors ?>',
	        		<?php else: ?>
	        		colorByPoint: true,
	        		colors : highcharts_color_set(Highcharts.getOptions().colors[0]),
	        		<?php endif; ?>

	        		data: <?= json_encode($serie->data) ?>,
	        	},

	        <?php endforeach; ?>

	        ],

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

	            <?php $subseries->data = $this->convert_data($subseries->data); ?>

	            {
	                name: '<?= $subseries->label ?>',
	                id: '<?= $subseries->id ?>',

	                <?php if (is_array($subseries->colors)) : ?>
	        		colorByPoint: true,
					colors:<?= json_encode($subseries->colors) ?>,
	        		<?php elseif(is_string($subseries->colors)) : ?>
	        		//colorByPoint: true,
	        		//colors : highcharts_color_set('<?= $subseries->colors ?>'),
	        		color:'<?= $subseries->colors ?>',
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