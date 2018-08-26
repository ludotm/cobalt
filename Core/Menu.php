<?php

namespace Core;

use Core\Service;

class Menu extends HtmlTag
{	
	public $items = array();

	public function __construct($id='')
	{
		if ($id != '') {
			$this->id($id);
		}
	}

	public function id($id) 
	{
		$this->attr('id',$id);
		return $this;
	}

	/* --------------------- ITEMS ------------------------------ */

	public function add_item ($name='', $label=null, $url='#') 
	{
		$item = new MenuItem($name, $label, $url);

		if ($name != '') {
			$this->items[$name] = $item;
		} else {
			$this->items []= $item;
		}
		
		return $item;
	}

	public function add_submenu ($name='', $label=null, $url='#') 
	{
		$item = new MenuItem($name, $label, $url);
		$this->items[$name] = array($item, 'subitems' => array());
		return $item;
	}

	public function add_subitem ($submenu, $name='', $label=null, $url='#') 
	{
		$item = new MenuItem($name, $label, $url);
		
		if ($name != '') {
			$this->items[$submenu]['subitems'][$name] = $item;
		} else {
			$this->items[$submenu]['subitems'] []= $item;
		}
		return $item;
	}

	public function get($name) 
	{
		return $this->items[$name];
	}

	/* --------------------- DRAWING ------------------------------ */

	public function draw ($subitems_position="left") 
	{
		foreach ($this->items as $name => $item) {
			if (is_array($item)) {
				foreach ($item['subitems'] as $subitem) {
					if ($subitem->active) {
						$this->items[$name][0]->active();
					}
				}
			}
	
		}

		ob_start();
		?>
			<nav<?= $this->get_attrs() ?>>   
          		<ul>
	                <?php foreach($this->items as $name => $item) : ?>

	                	<?php if (is_array($item)) : ?>
	                		
	                		<?= $item[0]->open_tag() ?>

	                			<?= $item[0]->compile(); ?>
		                    
		                    	<ul class="<?= $subitems_position ?>"> 

			                    	<?php foreach($item['subitems'] as $subitem_name => $subitem) : ?>

			                    		<?= $subitem->open_tag() ?>

			                    			<?= $subitem->compile(); ?>
					                    
					                    <?= $subitem->close_tag() ?>

			                        <?php endforeach; ?>

		                    	</ul>

		                    <?= $item[0]->close_tag() ?>

	                	<?php else: ?>

		                    <?= $item->open_tag() ?>

		                    	<?= $item->compile(); ?>
		                    	
		                    <?= $item->close_tag() ?>

		                <?php endif; ?>

	                <?php endforeach; ?>
	            </ul>

            </nav>

        <?php 
        ob_end_flush();
	}
}

class MenuItem extends HtmlTag
{
	public $name;
	public $label;
	public $url;
	public $blank;
	public $icon;
	public $active = false;
	public $subitems = array();
	protected $request = null;

	public function __construct($name, $label=null, $url='#', $icon='')
	{
		$this->name = $name;
		$this->label($label);
		$this->url($url);
		$this->icon = $icon;
	}

	/* --------------------- ELEMENT BASE --------------------------- */

	public function name ($name) 
	{
		$this->name = $name;
		return $this;
	}
	public function label ($label) 
	{
		$this->label = $label;
		return $this;
	}
	public function icon ($icon) 
	{
		$this->icon = $icon;
		return $this;
	}
	public function active ($active=true) 
	{
		$this->active = $active;
		return $this;
	}

	/* --------------------- URL FUNCTIONS --------------------------- */
	
	protected function get_request() {		
		return !$this->request ? Service::Request() : $this->request;
	}

	public function route ($route, $vars=array()) 
	{
		$this->url = $this->get_request()->router($route, $vars);

		if ($route == $this->get_request()->active_menu) {
			$this->active();
		}
		return $this;
	}

	public function url ($url) // array of attributes or basic url
	{
		$this->url = $url;
		return $this;
	}

	public function _blank () 
	{
		$this->blank = true;
		return $this;
	}
	public function blank () //ALLIAS
	{
		$this->blank = true;
		return $this;
	}

	/* --------------------- RETURN VALUES WIDTH FILTERS ------------------------------ */

	public function open_tag() {
		
		foreach($this->subitems as $it) {

		}

		if ($this->active){
			$this->attr('class', 'active', false);
		}
		return '<li'.$this->get_attrs().'>';
	}
	
	public function close_tag() {
		return '</li>';
	}

	public function compile() {

		if ($this->url) {
			if (is_array($this->url)) {

				if (!array_key_exists('href', $this->url)) {
					$this->url['href'] = '#';
				}
				if (!array_key_exists('title', $this->url)) {
					$this->url['title'] = strip_tags($this->label);
				}
				if (!array_key_exists('target', $this->url) && $this->blank) {
					$this->url['target'] = '_blank';
				}

				$tag = '<a';
				foreach ($this->url as $attr => $attr_value) {
					$tag .= ' '.$attr.'="'.$attr_value.'"';
				}
				$tag .= '>';

				$tag .= $this->icon.'<span>'.$this->label.'</span>';
				$tag .= '</a>';

			} else {
				$tag = '<a href="'.$this->url.'" title="'.strip_tags($this->label).'"'.($this->blank?' target="_blank"':'').'>'.$this->icon.'<span>'.$this->label.'</span></a>';
			}
		}

        return $tag;
	}
}

?>