<?php

namespace Core;

use \Core\Service;

class Pager extends HtmlTag
{
    
    protected $ajax;
    protected $url;
    protected $max_items;
    protected $align;
    protected $template;
    protected $show_count;
    protected $scroll_to_top;
    protected $items_attrs;
    protected $sql;
    protected $db;
    
    public $count;
    public $items_per_page;
    public $current_page;
    public $count_page;

    public function __construct($params=array()) 
    {
        // count et SQL non obligatoires si on passe par la classe Table
        $this->count = array_key_exists('count', $params) ? $params['count'] : null ;
        $this->sql = array_key_exists('sql', $params) ? $params['sql'] : null ;

    	$this->current_page = array_key_exists('page', $params) ? $params['page'] : 1 ;
    	$this->ajax = array_key_exists('ajax', $params) ? $params['ajax'] : false ;
    	$this->url = array_key_exists('url', $params) ? $params['url'] : '' ;
    	$this->items_per_page = array_key_exists('items_per_page', $params) ? $params['items_per_page'] : 10 ;
    	$this->max_items = array_key_exists('max_items', $params) ? $params['max_items'] : 15 ;
        $this->align = array_key_exists('align', $params) ? $params['align'] : 'center' ;
        $this->scroll_to_top = array_key_exists('scroll_to_top', $params) ? $params['scroll_to_top'] : true ;
        $this->items_attrs = array_key_exists('items_attrs', $params) ? $params['items_attrs'] : null ;
        $this->template = array_key_exists('template', $params) ? $params['template'] : 'number' ;
        $this->show_count = array_key_exists('show_count', $params) ? $params['show_count'] : false ;

        if (array_key_exists('id', $params)) {
            $this->attr('id', $params['id']);
        }
        if (array_key_exists('style', $params)) {
            $this->attr('style', $params['style']);
        }
        if (array_key_exists('class', $params)) {
            $this->attr('class', $params['class']);
        }

        if ($this->sql && !$this->count) {
            $this->db = Service::Db();
            $this->count = $this->db->query_count ($this->sql);
        }        

        $this->count_page = ceil($this->count/$this->items_per_page);
    }

    /* ---------------------- SETTERS ----------------------------- */

    public function page($page) {
    	$this->current_page = $page;
    	return $this;
    }
    public function ajax($bool=true) {
    	$this->ajax = $bool;
    	return $this;
    }
    public function count($count) {
    	$this->count = $count;
    	return $this;
    }
    public function url($url) {
    	$this->url = $url;
    	return $this;
    }
    public function max_items($max_items) {
    	$this->max_items = $max_items;
    	return $this;
    }
    public function items_per_page($items_per_page) {
    	$this->items_per_page = $items_per_page;
    	return $this;
    }
    public function align($align) {
    	$this->align = $align;
    	return $this;
    }
    public function show_count($bool) {
        $this->show_count = $bool;
        return $this;
    }

    /* ---------------------- GET SQL LIMIT ----------------------------- */

    public function limit()
	{
		return ' LIMIT '.($this->items_per_page * $this->current_page - $this->items_per_page).','.$this->items_per_page;
	}	

    public function get_count()
    {
        return ' LIMIT '.($this->items_per_page * $this->current_page - $this->items_per_page).','.$this->items_per_page;
    }

    /* ---------------------- DRAWING ----------------------------- */

    public function draw($previous_label="&laquo;", $next_label="&raquo;")
	{
        if ($this->template == 'number') {

            if ($this->count_page <= 1) {
                return '';
            }

            $this->attr('style','text-align:'.$this->align.';', false);

            $html = '';

            if ($this->show_count) {
                $html .= '<div class="pagination-count pull-'.($this->align == 'left' ? 'right' : 'left' ).'">'.$this->count.' entrée'.($this->count > 1 ? 's' : '').'</div>';
            }

            $html .= '<nav '.$this->get_attrs().'><ul class="pagination '.$this->template.'">';
            
            $truncated = $this->max_items < $this->count_page;

            $html .= $this->get_item('previous', $previous_label);

            if(!$truncated) {
                
                for ($i=1; $i<=$this->count_page; $i++) {
                    $html .= $this->get_item($i);
                }

            } else {

                $gap = ($this->max_items-3)/2;

                for ($i=1; $i<=$this->count_page; $i++)
                {
                    if ($i>1 && $i < ($this->current_page-$gap)) {
                        $html .= " <li><a href='#' >...</a></li> ";
                        $i = ($this->current_page-$gap) < 1 ? 1 : ($this->current_page-$gap);
                    
                    } else if($i < $this->count_page && $i > ($this->current_page+$gap) ){
                        $html .= " <li><a href='#' >...</a></li> ";
                        $i=$this->count_page-1;
                    
                    } else {
                        $html .= $this->get_item($i);
                    }
                }
            }
            
            $html .= $this->get_item('next', $next_label);
            
            $html .= "</ul></nav>";
        

        } else if ($this->template == 'simple') {

            if ($this->count_page <= 1) {
                return '';
            }

            $this->attr('style','text-align:'.$this->align.';', false);

            $html = '';

            if ($this->show_count) {
                $html .= '<div class="pagination-count pull-'.($this->align == 'left' ? 'right' : 'left' ).'">'.$this->count.' entrée'.($this->count > 1 ? 's' : '').'</div>';
            }

            $html .= '<nav '.$this->get_attrs().'><ul class="pagination '.$this->template.'">';

            $html .= $this->get_item('previous', $previous_label);

            $html .= $this->get_item('next', $next_label);
            
            $html .= "</ul></nav>";
        }
		
        if ($this->scroll_to_top) {
            $html .= '<script>$(".pagination li a").click(function(){$("html, body").animate({scrollTop:0}, "slow");});</script>';
        }
        return $html;
	}

    protected function get_item ($page, $special_label=null) {

        $label = $page;

        if (!is_int($page)) {
            if ($page == 'previous') {
                $class = $this->current_page > 1 ? '' : 'disabled';
                $label = '<span aria-hidden="true">'.$special_label.'</span>';
                $page = $this->current_page - 1 ;
            } elseif ($page == 'next') {
                $class = $this->current_page < $this->count_page ? '' : 'disabled';
                $label = '<span aria-hidden="true">'.$special_label.'</span>';
                $page = $this->current_page + 1 ;
            }
        }
        if ($this->current_page == $page) {
            $class = 'active';
        }

        $params = '';

        if ($this->items_attrs) {
            foreach ($this->items_attrs as $key => $value) {
                $params .= ' ' . $key . '="'.$value.'"';
            }
        }

        return '<li '.(isset($class) ? 'class="'.$class.'"' : '' ).'><a '.($this->ajax ? 'href="#" data-ajax' : 'href').'="'.$this->get_url($page).'" '.$params.'>'.$label.'</a></li>';
    }

    protected function get_url($page) {
        $page = $page <= 0 ?  1 : ($page >= $this->count_page ? $this->count_page : $page) ;
        return str_replace('[PAGE]', $page, $this->url);
    }
}