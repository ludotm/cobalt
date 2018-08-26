<?php
namespace Core\Highcharts;

use Core\Highcharts\Chart;

use Core\Highcharts\Lib\Axis;
use Core\Highcharts\Lib\Serie;

class Spline extends Chart 
{

	  public $xAxis;
	  public $yAxis;
	  public $series = array();
	  public $legend;
	  public $tooltip;
	  public $area;
	  public $zoom;

	  public function __construct($id)
	  {
	  	$this->id = $id;
	  	$this->get_x_axis();
	  	$this->get_y_axis();
	  	$this->area = true; // courbes avec fond coloré ou sans fond
	  	$this->line_width = 2;

	  	parent::__construct();
	  }

	  public function zoom($zoomtype) // x, y, xy
	  {
	    $this->zoom = $zoomtype;
	    return $this;
	  }

	  public function area($bool) 
	  {
	    $this->area = $bool;
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
	  // paramètre compress pour concaténer les données sous forme de semaine, mois ou année ("week","month","year")
	  public function add_serie_date($label, $data, $color, $opacity=1, $compress=false) 
	  {
	  	switch($compress) {
	  		case false: break;
	  		default: $data = $this->compress_data($data, $compress); break;
	  	}

	  	$count = count($data);
	  	for ($i=0; $i<$count; $i++) {
	  		$date = explode('-', $data[$i][0]);
	  		$data[$i][0] = $date[0].','.($date[1]-1).','.$date[2];

	  		$data[$i][0] = "@@Date.UTC(".$data[$i][0].")@@";
	  	}
	  	$this->add_serie($label, $data, $color, $opacity);
	  	$this->get_x_axis()->type('datetime');
	  }

	  public function compress_data($data, $compress_type)
	  {
	  	if (!$compress_type) {
	  		return $data;
	  	}

	  	$count = count($data);
	  	$new_data = array();

	  	$date_temp = false;
	  	$data_temp = 0;

	  	switch ($compress_type) {
	  		case "week": $laps = '+ 7 days'; break;
	  		case "month": $laps = '+ 1 month'; break;
	  		case "year": $laps = '+ 1 year'; break;
	  	}

	  	for ($i=0; $i<$count; $i++) {

	  		if ($date_temp == false) {
		  		switch ($compress_type) {
			  		case "week":
				  		$day = date('w', strtotime($data[$i][0]));
						switch ($day) {
							case 1: $date_temp = $data[$i][0]; break;
							case 2: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -1 day') ); break;
							case 3: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -2 day') ); break;
							case 4: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -3 day') ); break;
							case 5: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -4 day') ); break;
							case 6: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -5 day') ); break;
							case 0: $date_temp = date('Y-m-d', strtotime($data[$i][0].' -6 day') ); break;
						} 
			  			break;
			  		case "month": 
			  			$date_temp = date('Y-m', strtotime($data[$i][0]) ).'-01';
			  			break;
			  		case "year":
			  			$date_temp = date('Y', strtotime($data[$i][0]) ).'-01-01';
			  			break;
			  	}
			}
	  		

  			$data_temp += $data[$i][1];

  			if ($data[$i][0] >= date('Y-m-d', strtotime($date_temp.' '.$laps) )) {
  				$new_data []= array($date_temp, $data_temp);
  				$date_temp = false;
  				$data_temp = 0;
  			}
	  	}
	  	return $new_data;
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
	            type: '<?= $this->area ? "areaspline" : "spline" ?>',
	            <?php if ($this->zoom) : ?>
	            zoomType: '<?= $this->zoom ?>',
	            <?php endif; ?>

	        },
	        title: {
	            text: ''
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
	            
	            <?php if (isset($this->xAxis->type)) : ?>
	            type: "<?= $this->xAxis->type ?>",
	            <?php endif; ?>

	            <?php if (isset($this->xAxis->min) && isset($this->xAxis->max)) : ?>
	            min: <?= $this->xAxis->min ?>,
	            max: <?= $this->xAxis->max ?>,
	            <?php endif; ?>
	            
	            <?php if (isset($this->xAxis->categories)) : ?>
	            categories: <?= \Core\Tools::json_encode_js($this->xAxis->categories) ?>,
	            <?php endif; ?>
	        },
	        yAxis: {
	            
	            title: {
	                text: "<?= $this->yAxis->title == '' ? ' ' : $this->yAxis->title ?>"   
	            },

	            <?php if (isset($this->yAxis->type)) : ?>
	            type: "<?= $this->yAxis->type ?>",
	            <?php endif; ?>

	            <?php if (isset($this->yAxis->min) && isset($this->yAxis->max)) : ?>
	            min: <?= $this->yAxis->min ?>,
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

	        plotOptions: {
	            areaspline: {
	                fillOpacity: 0.5
	            }
	        },
	        series: [
	        <?php foreach ($this->series as $serie) : ?>
	        {
	            name: "<?= $serie->label ?>",
	            data: <?= \Core\Tools::json_encode_js($serie->data) ?>,
	            lineWidth: <?= $this->line_width ?>,
	            <?php if ($serie->color) : ?>
	            color: "<?= $serie->color ?>",
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