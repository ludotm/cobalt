<?php

namespace Core;

use Core\Service;
use Core\Pager;
use Core\HtmlAttributes;

class Table extends HtmlTag
{	
	protected $db;
	protected $width;
	protected $sql;
	protected $hide_thead;
	protected $model;
	protected $table;

	public $cols = array();
	public $filters = array();
	public $pager;

	public function __construct($model='')
	{
		if ($model != '') {
			$this->setModel($model);
			$this->attr('id', $model.'_table');
		}
		$this->hide_thead = false;
		$this->width = '100%';
	}

	public function hide_thead($bool=true) {
		$this->hide_thead = $bool;
		return $this;
	}

	public function setModel ($modelOrArray) {

		if (is_array($modelOrArray)) {
			$this->model = array();	
			if (!array_key_exists('fields', $modelOrArray)) {
				$this->model['params'] = array();
				$this->model['fields'] = $modelOrArray;
			} else {
				$this->model = $modelOrArray;
			}
			
		} else {

			$model = _model($modelOrArray);
			if ($model) {
				$this->table = $modelOrArray;
				$this->model = $model;
			}
		}	
		return $this;
	}

	/* --------------------- COLS ------------------------------ */

	public function add_col ($name, $label=null) 
	{
		$merge_col = explode(' ', $name);

		if (count($merge_col)>1) {
			$name = implode('_', $merge_col);
		}

		$col = new TableCol($name, $label);
		if ($this->model) {
			$col->set_model_name($this->model['params']['table']);
		}
		

		if (count($merge_col)>1) {
			$col->merge_values($merge_col);
		}

		if ($name != '') {
			$this->cols[$name] = $col;
		} else {
			$this->cols []= $col;
		}
		
		return $col;
	}

	public function get($name) 
	{
		return $this->cols[$name];
	}

	/* --------------------- SQL ------------------------------ */

	public function sql ($sql, $id_field='id') 
	{
		$this->sql = $sql;
		$this->id_field = $id_field;
	}

	public function add_filter ($filter) 
	{
		$this->filters []= $filter;
		return $this;
	}

	public function entity_id ($id_field) 
	{
		$this->id_field = $id_field;
	}

	protected function get_db() {
		if (!$this->db) {
			$this->db = Service::Db();
		}
		return $this->db;
	}

	/* --------------------- PAGER ------------------------------ */

	public function pager($params) 
	{	
		if (!array_key_exists('count', $params) && $this->sql) {
			$params['sql'] = $this->sql;
		}
		$this->pager = new Pager($params);
		return $this->pager;
	}

	public function get_pager() {
		return $this->pager;
	}

	/* --------------------- DRAW ------------------------------ */

	public function draw ($width=null) 
	{
		/*
		filter_select_sql('_rank', 'id', 'name', 'id!=1');
		filter_select_array($array);
		filter_search();

		filter('id_rank', 'u.id_rank="[VAL]"', );


		foreach ($this->filters as $filter) {
			$this->sql = str_replace ('WHERE ', 'WHERE ', $this->sql);
		}
		*/
		$this->db = $this->get_db();
		if ($this->pager) {
			$this->sql .= $this->pager->limit();
		}

		if ($this->table) {
			$results = $this->db->query($this->sql, array(), $this->table);
		} else {
			$results = $this->db->query($this->sql);
		}
		
		if (!$width) {
			$width = $this->width;
			$width = str_replace ('px', '', $width);
		}

		ob_start();
		?>

			<?php if(!empty($this->filters)) : ?>



			<?php endif; ?>

			<table class="table" border="0"<?= $this->get_attrs() ?>>

				<?php if (!$this->hide_thead) : ?>
	                
	                <thead>
	                <tr>
	                	<?php foreach ($this->cols as $name => $col) : ?>
	                		
	                		<td<?= $col->get_attrs() ?>><?= $col->label; ?></td>

	                	<?php endforeach; ?>

	                </tr>
	                </thead>

            	<?php endif; ?>

            	<?php if ($results) : ?>
	                <?php foreach($results as $item) : ?>

	                    <tr data-id="<?= isset($item->id) ? $item->id : '' ?>">

	                    	<?php foreach($this->cols as $name => $col) : ?>

	                    		<?php 
	                    			
	                    			$value = '';

	                    			if (!empty($col->merge_values)) {
	                    				foreach ($col->merge_values as $one_name) {
	                    					$value .= $item->{$one_name}.' ';
	                    				}
	                    			} else {
	                    				$value = !is_int($name) ? $item->{$name} : '' ; 
	                    			}
	                    		?>

	                        	<td<?= $col->get_attrs() ?>><?= $col->compile($value, $item->{$this->id_field}, $item) ?></td> 

	                        <?php endforeach; ?>

	                    </tr>

	                <?php endforeach; ?>
	            <?php else : ?>

	            	<tr><td colspan="<?= (count($this->cols)-1) ?>">
	            		<?= translate('Aucun résultat'); ?>
	            	<td></tr>

	            <?php endif; ?>
            </table>
           	
            <? if ($this->pager) : echo $this->pager->draw(); endif; ?>

        <?php 
        ob_end_flush();
	}
}

class TableCol extends HtmlTag
{
	public $name;
	public $label;
	public $url;
	public $filters;
	public $merge_values;
	public $callback_function;
	public $options;
	public $model_name;

	public function __construct($name, $label=null)
	{
		$this->name = $name;
		$this->label = $label;
		$this->merge_values = array();
	}

	public function set_model_name ($model_name) 
	{
		$this->model_name = $model_name;
	}

	/* --------------------- ELEMENT FUNCTIONS ------------------------------ */

	// for double value cols
	public function merge_values ($merge_values) 
	{
		$this->merge_values = $merge_values;
		return $this;
	}
	public function label ($label) 
	{
		$this->label = $label;
		return $this;
	}
	public function url ($url) // array or basic url
	{
		$this->url = $url;
		return $this;
	}
	public function callback ($callback_function) 
	{
		$this->callback_function = $callback_function;
		return $this;
	}
	
	/* --------------------- RESPONSIVE ------------------------------ */

	public function hide_on ($size) {
		$this->attr('class', 'hide-on-'.$size, false);
	}

	/* --------------------- CONVERT ID TO VALUE WITH MODEL ------------------------------ */

	public function id_to_value ($options=null) 
	{
		if (is_array($options)) {
			$this->options = $options;

		} else if (!$options) {
			$this->options = _get('models.'.$this->model_name.'.fields.'.$this->name.'.options');
		}
		return $this;
	}

	/* --------------------- FILTRES ------------------------------ */

	public function filter ($filter, $param=null) 
	{
		$this->filters []= array($filter, $param);
		return $this;
	}
	public function width ($width) 
	{
		$this->attr('width', $width);
		return $this;
	}

	/* --------------------- RETURN VALUES WIDTH FILTERS ------------------------------ */

	public function compile ($value, $id, $object) {

		if ($this->filters) {
			foreach ($this->filters as $filter) {
				switch ($filter[0]) {
					
					case 'bold':
						$value = '<b>'.$value.'</b>';
						break;

					case 'italic':
						$value = '<i>'.$value.'</i>';
						break;

					case 'style':
						$value = '<span style="'.$filter[1].'">'.$value.'</span>';
						break;

					case 'class':
						$value = '<span class="'.$filter[1].'">'.$value.'</span>';
						break;

					case 'truncate':
						$value = \Core\Tools::truncate($value, $filter[1]);
						break;

					case 'date':
						$format = $filter[1] ? $filter[1] : 'd F Y'; 
						$value = \Core\Tools::convert_date($value, $filter[1]);
						break;

					case 'html':
					case 'icon':
						$value = $filter[1].' '.$value;
						break;

					case 'tooltip':
						break;
				}
			}
		}

		if ($this->callback_function) {
			$value = $this->callback_function->__invoke($value, $object);
        } 

        if ($this->options) {
        	if (array_key_exists($value, $this->options)) {
        		$value = $this->options[$value];
        	}
        } 

		if ($this->url) {
			if (is_array($this->url)) {

				if (!array_key_exists('href', $this->url)) {
					$this->url['href'] = '#';
				}

				$tag = '<a';
				foreach ($this->url as $attr => $attr_value) {
					if ($attr != 'icon') {
						$tag .= ' '.$attr.'="'.str_replace('[ID]',$id,$attr_value).'"';
					}
				}
				$tag .= '>';

				if (array_key_exists('icon', $this->url)) {
					$tag .= $this->url['icon'];
				}

				$tag .= $value;
				$tag .= '</a>';

				$value = $tag;
			} else {
				$value = '<a href="'.$this->url.'">'.$value.'</a>';
			}
		}

        return $value;
	}
}

?>