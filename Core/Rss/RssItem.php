<?php 
namespace Core\Rss;

class RssItem
{
	public $url;
	public $title;
	public $description;
	public $date;
	public $author;
	public $source;
	public $category;
	public $guid;
	public $image;
	public $media;
	
	public function __construct() 
	{

	}

	public function get($key, $schema=null) 
	{
		$var = (!$this->{$key} || $this->{$key} == '') ? null : $this->{$key};

		if ($var) {
			if (!$schema) {
				return $var;
			} else {
				return sprintf($schema, $var);
			}
		} else {
			return '';
		}		
	}
	
	public function getImage($width=null, $height=null) 
	{	
		if (empty($this->image)) {
			return '';
		} else {
			$alt = array_key_exists('title', $this->image) ? ' alt="'.$this->image['title'].'"' : (array_key_exists('description', $this->image) ? ' alt="'.$this->image['description'].'"' : '' );
			$width = $width ? ' width="'.$width.'"' : (array_key_exists('width', $this->image) ? ' width="'.$this->image['width'].'"' : '' );
			$height = $height ? ' height="'.$height.'"' : (array_key_exists('height', $this->image) ? ' height="'.$this->image['height'].'"' : '' );
			return '<img src="'.$this->image['url'].'" border="0" '.$alt.$width.$height.' />';
		}
	}

	public function getSource()
	{
		$source = $this->get('source');
		if ($source != '') {
			return '<a href="'.$source.'" target="_blank">'.parse_url($source, PHP_URL_HOST).'</a>';
		} else {
			return '';
		}
		
	}	

	public function display($return=false)
	{
		$html = '<article class="rss_item">';
		$html .= '<h1><a href="'.$this->url.'" target="_blank">'.$this->title.'</a></h1>';

		$html .= '<h5>'.date('d/m/Y H:i:s', $this->date);
		$html .= $this->get('category', ' - %s');
		$html .= $this->get('author', ' - %s');
		$html .= $this->getSource();
		$html .= '</h5>';
		
		$html .= $this->getImage(300);
		$html .= $this->get('description', '<p>%s</p>');

		$html .= '</article>';

		if (!$return) {
			echo $html;
		} else {
			return $html;
		}
	}
}

?>