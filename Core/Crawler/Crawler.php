<?php 

namespace Core\Crawler;

use Core\Service;

class Crawler 
{
	protected $use_proxy;

	public function __construct() 
	{
		$this->use_proxy = false;
	} 

	public function use_proxy($bool)
	{
		$this->use_proxy = $bool;
	}

	public function crawl ($url, $page_pattern) {

		$curl = Service::Curl();
		if ($this->use_proxy) {
			/*    */
		}
		$content = $curl->get($url);

		$results = array();

		foreach($page_pattern as $name => $pattern)
		{
			if (is_string($pattern)) {
				preg_match("~$pattern~siU", $content, $results[$name]);
				if (!is_null($results[$name])) {
					if (isset($results[$name][1])) {
						//$results[$name] = utf8_decode(trim($results[$name][1]));
						$results[$name] = trim($results[$name][1]);
					} else {
						$results[$name] = NULL;
					}
				}
			
			} else if (is_array($pattern)) {

				$matches = array();
				$schema = $pattern['items'];
				$r = preg_match_all("~$schema~siU", $content, $matches, PREG_PATTERN_ORDER);

				for ($i=1; $i<count($matches[0]); $i++) {

					$temp = array();
					for ($y=0; $y<count($pattern['vars']);$y++) {
						if ($pattern['vars'][$y] != 'trash') {
							$temp[$pattern['vars'][$y]] = utf8_decode(trim($matches[$y+1][$i]));
							//$temp[$pattern['vars'][$y]] = trim($matches[$y+1][$i]);
						}
					}

					$results[$name] []= $temp;
				}
			}
		}

		return $results;
	}

	public function normalize_list($result)
	{
		$fields = array('title', 'author_id', 'author', 'original_id', 'time', 'thumb', 'description', 'url', 'date', 'note');

		for ($i=0; $i<count($result['video_list']); $i++) {

			for ($y=0; $y<count($fields); $y++) {

				if (!array_key_exists($fields[$y], $result['video_list'][$i])) {

					$result['video_list'][$i][$fields[$y]] = null;
				}
			}
		}
		return $result;
	}

	public function normalize_page($result)
	{
		$fields = array('title', 'author_id', 'author', 'original_id', 'time', 'thumb', 'description', 'url', 'date', 'content', 'views', 'avatar', 'note');


			for ($y=0; $y<count($fields); $y++) {

				if (!array_key_exists($fields[$y], $result)) {

					$result[$fields[$y]] = null;
				}
			}
		
		return $result;
	}

}

?>