<?php
namespace Core\Highcharts;

use Core\Highcharts\Chart;

use Core\Highcharts\Lib\Axis;
use Core\Highcharts\Lib\Serie;

class Spiderweb extends Chart 
{

	  public $xAxis;
	  public $yAxis;
	  public $series = array();
	  public $legend;
	  public $tooltip;

	  public function __construct($id)
	  {
	  	$this->id = $id;
	  	$this->get_x_axis();
	  	$this->get_y_axis();
	  	$this->line_width = 2;

	  	parent::__construct();
	  }

	  public function zoom($zoomtype) // x, y, xy
	  {
	    $this->zoom = $zoomtype;
	    return $this;
	  }

	  public function line_width($int) 
	  {
	    $this->line_width = $int;
	    return $this;
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

	  public function legend($x, $y) 
	  {
	    $this->legend = true;
	    $this->legend_x = $x;
	    $this->legend_y = $y;
	    return $this;
	  }

	  public function tooltip($text, $shared=true) 
	  {
	    $this->tooltip = $text;
	    $this->tooltip_shared = $shared;
	    return $this;
	  }

	  public function add_serie($label, $data, $color, $opacity=1) 
	  {
	    $serie = new Serie();
	    $serie->label = $label;
	    $serie->data = $data;
	    $serie->color = \Core\Tools::hex2rgba($color, $opacity);

	    $this->series []= $serie;
	    return $this;
	  }
	  public function add_serie_date($label, $data, $color, $opacity=1) 
	  {
	  	$count = count($data);
	  	for ($i=0; $i<$count; $i++) {
	  		$date = explode('-', $data[$i][0]);
	  		$data[$i][0] = $date[0].','.($date[1]-1).','.$date[2];

	  		$data[$i][0] = "@@Date.UTC(".$data[$i][0].")@@";
	  	}
	  	$this->add_serie($label, $data, $color, $opacity);
	  	$this->get_x_axis()->type('datetime');
	  }





	  /** enable auto-step calc for xAxis labels for very large data sets.
	   * @return void
	   */
	  public function enableAutoStep()
	  {
	    if(is_array($this->series)) {
	      $count = count($this->series[0]->data);
	      $step = number_format(sqrt($count));
	      if($count > 1000){
	        $step = number_format(sqrt($count/$step));
	      }

	      $this->xAxis->labels->step = $step;
	    }

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
	            type: 'line',
	            polar: true,
	        },
	        title: {
	            text: ''
	        },
	        pane: {
	            size: '65%'
	        },

	        <?php if ($this->legend) : ?>
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
	        
	        xAxis: {
	            title: {
	                text: "<?= $this->xAxis->title == '' ? ' ' : $this->xAxis->title ?>"   
	            },
	            tickmarkPlacement: 'on',
            	lineWidth: 0,

	            <?php if (isset($this->xAxis->categories)) : ?>
	            categories: <?= \Core\Tools::json_encode_js($this->xAxis->categories) ?>,
	            <?php endif; ?>

	        },
	        yAxis: {
	            
	            title: {
	                text: "<?= $this->yAxis->title == '' ? ' ' : $this->yAxis->title ?>"   
	            },
	            gridLineInterpolation: 'polygon',
	            lineWidth: 0,
	            
	            <?php if (isset($this->yAxis->min)) : ?>
	            min: <?= $this->yAxis->min ?>,
	            <?php endif; ?>

	            <?php if (isset($this->yAxis->max)) : ?>
	            max: <?= $this->yAxis->max ?>,
	            <?php endif; ?>

	            <?php if (isset($this->yAxis->categories)) : ?>
	            categories: <?= \Core\Tools::json_encode_js($this->yAxis->categories) ?>,
	            <?php endif; ?>
	        },
	        
	        <?php if ($this->tooltip) : ?>
	        tooltip: {
	            shared: <?= $this->tooltip_shared ?>,
	            valueSuffix: "<?= $this->tooltip ?>",
	        },
	        <?php endif; ?>

	        series: [
	        <?php foreach ($this->series as $serie) : ?>
	        {
	            name: "<?= $serie->label ?>",
	            data: <?= \Core\Tools::json_encode_js($serie->data) ?>,
	            pointPlacement: 'on',
	            <?php if ($serie->color) : ?>
	            color: '<?= $serie->color ?>',
	            <?php endif; ?>
	        },
	        <?php endforeach; ?>
	        ]
	    });
	    </script>
	    <?php
	    return ob_get_contents();
	  }
}
?>