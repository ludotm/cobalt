<?php 

namespace Core\Rss;

use Core\Service;
use Core\Rss\RssItem;

use SimpleXmlElement;
use IteratorAggregate;
use ArrayIterator;

class RssReader implements IteratorAggregate
{
	public $title;
	public $url;	
	public $description;
	public $language;
	public $date;
	public $category;
	public $image;
	public $count;

	public $accept_html;
	private $items;

	public function __construct($url=null, $accept_html=false) 
	{	
		if ($url) {
			$this->parse($url, $accept_html);
		}
	}
	
	public function parse ($url, $accept_html=false)
	{
		$this->accept_html = $accept_html;

		$curl = Service::Curl();
		$content = $curl->get($url);

		$xml = new SimpleXmlElement($content);

		if(isset($xml->channel))
		{
		    $this->parseRSS($xml);
		}
		if(isset($xml->entry))
		{
		    $this->parseAtom($xml);
		}
	}

	protected function parseRSS($xml)
	{
		$this->title = utf8_decode((string) $xml->channel->title);
		$this->url = utf8_decode((string) $xml->channel->link);
		$this->description = !$this->accept_html ? strip_tags(utf8_decode((string) $xml->channel->description)) : utf8_decode((string) $xml->channel->description) ; // avec ou sans tags HTML
		$this->language = utf8_decode((string) $xml->channel->language);
		$this->date = strtotime((string) $xml->channel->pubDate);
		$this->category = utf8_decode((string) $xml->channel->category);
		$this->image = (array) $xml->channel->image;
		$this->count = count($xml->channel->item);

		foreach ($xml->channel->item as $entry) {
			$item = new RssItem();
			$item->url = utf8_decode((string)$entry->link);
			$item->title = utf8_decode((string)$entry->title);
			$item->description = !$this->accept_html ? strip_tags(utf8_decode((string)$entry->description)) : utf8_decode((string)$entry->description) ; // avec ou sans tags HTML
			$item->date = strtotime((string)$entry->pubDate);
			$item->author = utf8_decode((string)$entry->author);
			$item->source = utf8_decode((string)$entry->source);
			$item->category = utf8_decode((string)$entry->category);
			$item->guid = utf8_decode((string)$entry->guid);

			switch ($entry->enclosure['type']) {
				case 'image/jpeg':
				case 'image/gif':
				case 'image/png':
				$item->image = $entry->enclosure;
				$item->media = '';
				break;
				default:
				$item->media = $entry->enclosure;
				$item->image = '';
				break;
			}
			//$item->display();

			$this->items []= $item;
		}
	}

	protected function parseAtom($xml)
	{
		$this->count = count($xml->entry);

		$urlAttr = $xml->link->attributes();

		$this->title = utf8_decode((string) $xml->title);
		$this->url = utf8_decode((string) $urlAttr['href']);
		$this->date = strtotime((string) $xml->updated);

		for ($i=0; $i<$this->count; $i++) {
			
			$item = new RssItem();
			
			$urlAttr = $xml->entry[$i]->link->attributes();

			$item->url = $urlAttr['href'];
			$item->title = utf8_decode((string)$xml->entry[$i]->title);
			$item->description = !$this->accept_html ? strip_tags(utf8_decode((string)$xml->entry[$i]->content)) : utf8_decode((string)$xml->entry[$i]->content);
			$item->date = strtotime((string)$xml->entry[$i]->updated);
			$item->author = utf8_decode((string)$xml->entry[$i]->author->name);
			$item->guid = utf8_decode((string)$xml->entry[$i]->id);
			
			$this->items []= $item;
		}
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

	public function toJSON ()
	{

	}

	public function getIterator() {
        return new ArrayIterator( $this->items );
    }

    public function exchangeArray(array $array)
    {
        foreach ($array as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function toArray()
    {
        return array_merge($this->joinData, $this->data);
    }
}

?>