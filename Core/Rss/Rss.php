<?php 

namespace Core\Rss;

use Core\Service;
use Core\RssItem;

class Rss
{
	public $title;
	public $url;	
	public $description;
	public $language;
	public $date;
	public $category;
	public $image;
	public $count;
	protected $dates;
	private $items;
	
	public function __construct($data=array()) 
	{
		$this->setdata($data);
		$this->dates = array();
		$this->count = 0;
	}
	
	public function setdata($data)
	{
		if (array_key_exists('title',$data)) {
			$this->title = $data['title'];
		}
		if (array_key_exists('url',$data)) {
			$this->url = $data['url'];
		}
		if (array_key_exists('description',$data)) {
			$this->description = $data['description'];
		}
		if (array_key_exists('language',$data)) {
			$this->language = $data['language'];
		}
		if (array_key_exists('category',$data)) {
			$this->category = $data['category'];
		}
		if (array_key_exists('image',$data)) {

			if (is_array($data['image']) && isset($data['image']['url']))
			{
				$this->image = $data['image'];
			}
		}
	}

	public function addItem($data=array()) 
	{
		if (!array_key_exists('title', $data) || !array_key_exists('url', $data)) {
			Service::error("Titre et Url obligatoire pour construire l'item du flux RSS");
		} else {
			$item = new RssItem();

			$item->title = $data['title'];
			$item->url = $data['url'];

			if (array_key_exists('description', $data)) {
				$item->description = $data['description'];
			}
			if (array_key_exists('date', $data)) {
				$item->date = $data['date'];
				$this->dates []= strtotime($data['date']);
			}
			if (array_key_exists('category', $data)) {
				$item->category = $data['category'];
			}
			if (array_key_exists('author', $data)) {
				$item->author = $data['author'];
			}
			if (array_key_exists('source', $data)) {
				$item->source = $data['source'];
			}
			if (array_key_exists('guid', $data)) {
				$item->guid = $data['guid'];
			}
			if (array_key_exists('source', $data)) {
				$item->source = $data['source'];
			}

			if (array_key_exists('image', $data)) {
				if (array_key_exists('url', $data['image'])) {
					$item->image = $data['image'];
				}
			}

			if (array_key_exists('media', $data)) {
				if (array_key_exists('url', $data['media'])) {
					$item->image = $data['media'];
				}
			}
			
			$this->items []= $item;
			$this->count++;
		}
	}

	protected function getLastDate()
	{	
		if (!empty($this->dates)) {
			return date("D, d M Y H:i:s O", max($this->dates));
		} else {
			return null;
		}
	}

	public function render($return=false) 
	{
		if (!$this->title || !$this->url) {
			Service::error("Titre et Url obligatoire pour construire le flux RSS");
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<rss version="2.0">';
		$xml .= '<channel>';
		$xml .= ' <title>'.stripcslashes($this->title).'</title>';
		$xml .= ' <link>'.$this->url.'</link>';
		if ($this->description) {
			$xml .= ' <description>'.stripcslashes($this->description).'</description>';
		}
		if ($pubDate = $this->getLastDate()) {
			$xml .= ' <pubDate>'.$pubDate.'</pubDate>';
		}
		if ($this->language) {
			$xml .= ' <language>'.$this->language.'</language>';
		}
		if ($this->category) {
			$xml .= ' <category>'.$this->category.'</category>';
		}
		

		if ($this->image) {
			$xml .= ' <image>';
			$xml .= '   <url>'.$this->image['url'].'</url> ';
			
			if (array_key_exists('title', $this->image)) {
				$xml .= '   <title>'.stripcslashes($this->image['title']).'</title>';
			}
			if (array_key_exists('link', $this->image)) {
				$xml .= '   <link>'.$this->image['link'].'</link>';
			}
			if (array_key_exists('description', $this->image)) {
				$xml .= '   <description>'.stripcslashes($this->image['description']).'</description>';
			}
			if (array_key_exists('width', $this->image)) {
				$xml .= '   <width>'.$this->image['width'].'</width>';
			}
			if (array_key_exists('height', $this->image)) {
				$xml .= '   <height>'.$this->image['height'].'</height>';
			}
			$xml .= ' </image>';
		}
		
		//$xml .= ' <copyright>Craym.eu</copyright>';
		//$xml .= ' <managingEditor>rss@monsite-craym.eu</manadgingEditor>';
		//$xml .= ' <generator>PHP/MySQL</generator>';
		//$xml .= ' <docs>http://www.rssboard.org</docs>';
 
		foreach ($this->items as $item) {
			$xml .= '<item>';
		 	$xml .= '<title>'.stripcslashes($item->title).'</title>';
		 	$xml .= '<link>'.$item->url.'</link>';
		 	
		 	if ($item->date) {
		 		$xml .= '<pubDate>'.(date("D, d M Y H:i:s O", strtotime($item->date))).'</pubDate>';
		 	}
		 	if ($item->description) {
		 		$xml .= '<description>'.stripcslashes($item->description).'</description>';
		 	}
		 	if ($item->category) {
		 		$xml .= '<category>'.$item->category.'</category>';
		 	}
		 	if ($item->guid) {
		 		$xml .= '<guid isPermaLink="true">'.$item->guid.'</guid>';
		 	}
		 	if ($item->author) {
		 		$xml .= '<author>'.$item->author.'</author>';
		 	}
		 	if ($item->source) {
		 		$xml .= '<source url="'.$item->source.'">'.parse_url($item->source, PHP_URL_HOST).'</source>';
		 	}
		 	if ($item->image) {
		 		$xml .= '<enclosure url="'.$item->image['url'].'"';
		 		$xml .= array_key_exists('length', $item->image) ? ' length="'.$item->image['length'].'"' : '' ;
		 		$xml .= array_key_exists('type', $item->image) ? ' type="'.$item->image['type'].'"' : '' ;
		 		$xml .= ' />';
		 	}

		 	$xml .= '</item>'; 
		}

		$xml .= '</channel>';
		$xml .= '</rss>';

		if ($return) {
			return $xml;
		}

		header("Content-type: application/xml; charset=utf-8");
		echo $xml;
		exit();
	}
}

?>