<?php

namespace Core;

class HtmlTag
{	
	public $attrs = array();
	public $url_attrs = array();

	public function attr ($attr, $value, $replace=true) 
	{
		if ($replace || !array_key_exists($attr, $this->attrs)) {	
			$this->attrs [$attr] = $value;
		} else {

			$this->attrs [$attr] .= ' '.$value;
		}
		return $this;
	}

	public function attrs ($attrs=array()) 
	{
		foreach ($attrs as $key => $value) {
			$this->attr($key, $value);
		}
		return $this;
	}

	public function remove_attr ($attr) 
	{
		if (array_key_exists($attr, $this->attrs)) {
			unset($this->attrs[$attr]);
		}
		return $this;
	}

	public function get_attr ($attr) 
	{
		if (array_key_exists($attr, $this->attrs)) {
			return $this->attrs[$attr];
		} else {
			return null;
		}
	}

	public function get_attrs () 
	{
		$out = '';
		foreach ($this->attrs as $key => $value) {
			$out .= ' '.$key.'="'.$value.'"';
		}
		return $out;
	}

	public function style ($css) 
	{
		$this->attr('style', $css, false);
		return $this;
	}
}
?>