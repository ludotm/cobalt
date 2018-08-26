<?php
namespace Core\Highcharts;

use Core\Highcharts\Chart;
use Core\Highcharts\Lib\Serie;

class AgePyramid extends Chart 
{
	  public $series;
	  public $extreme;

	  public function __construct($id)
	  {
	  	$this->id = $id;
		$this->title = '';
		$this->tooltip = true;

		parent::__construct();
	  }

	  public function add_serie($label, $data, $color=null) 
	  {
	    $serie = new Serie();
	    $serie->label = $label;
	    $serie->data = $data;
	    $serie->color = $color;

	    $this->series[] = $serie;
	    return $this;
	  }

	  public function tooltip($bool)
	  {
	  	$this->tooltip = $bool;
	  	return $this;
	  }

	  public function title($title)
	  {
	  	$this->title = $title;
	  	return $this;
	  }

	  public function extreme($int) // défini la valeur la plus élévée pour faire en sorte que le zéro reste au centre
	  {
	  	$extreme = round($int/100*30) + $int;
	  	$this->extreme = $extreme;
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

	    var ages = ['- 18',
            '19-24', '25-29', '30-34', '35-39', '40-44',
            '45-49', '50-54', '55-59', '60-64', '65-69',
            '70-74', '75-79', '80 +'];

	    $('#<?= $div_id ?>').highcharts({

	    	chart: {
                type: 'bar'
            },
            title: {
                text: '<?= $this->title ?>'
            },
            xAxis: [{
                categories: ages,
                reversed: false,

                

                labels: {
                    step: 1
                }
            }, { // mirror axis on right side
                opposite: true,
                reversed: false,
                categories: ages,
                linkedTo: 0,
                labels: {
                    step: 1
                }
            }],
            yAxis: {
                title: {
                    text: null
                },
                
                <?php if ($this->extreme) : ?>
                min: -<?= $this->extreme ?>,
        		max: <?= $this->extreme ?>,
        		<?php endif; ?>

                labels: {
                    formatter: function () {
                        return (Math.abs(this.value));
                    }
                },
            
            },

            plotOptions: {
                series: {
                    stacking: 'normal'
                }
            },
            <?php if ($this->tooltip) : ?>
            tooltip: {
                formatter: function () {
                    return '<b>' + this.series.name + ', ' + this.point.category + ' ans</b><br/>' +
                        'Nombre: ' + Highcharts.numberFormat(Math.abs(this.point.y), 0);
                }
            },
            <?php endif; ?>

            series: [{
                name: '<?= $this->series[0]->label ?>',
                color: '<?= $this->series[0]->color ?>',
                data: <?= json_encode($this->series[0]->data); ?>,
            }, {
                name: '<?= $this->series[1]->label ?>',
                color: '<?= $this->series[1]->color ?>',
                data: <?= json_encode($this->series[1]->data); ?>,
            }]
	    });
	    </script>
	    <?php
	    return ob_get_contents();
	  }
}
?>