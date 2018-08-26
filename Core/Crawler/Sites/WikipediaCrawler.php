<?php 

namespace Core\Crawler\Sites;

use Core\Crawler\Crawler;
use Core\Service;

class WikipediaCrawler extends Crawler
{
	const MOBILE_SEARCH_URL = 'http://fr.m.wikipedia.org/w/index.php?search=';
	const MOBILE_PAGE_URL = 'http://fr.m.wikipedia.org/wiki/';
	const SEARCH_URL = 'http://fr.wikipedia.org/w/index.php?search=';
	const PAGE_URL = 'http://fr.wikipedia.org/wiki/';

	public function __construct() 
	{
		parent::__construct();
	}

	public function getWikipediaSearch($keywords, $mobile=true)
	{
		$base_url = $mobile ? self::MOBILE_SEARCH_URL : self::SEARCH_URL;
		$url = $base_url . urlencode($keywords);

		$pattern = array(
			'title' => '<h1.*>(.*)</h1>',
		    'content' => 'id="content".*>(.*)<div id="page-secondary-actions">',
		);

		$results = $this->crawl($url, $pattern);
		$results['content'] = '<div>' . $results['content'];
		$results['date'] = date("Y-m-d");
		
		return $this->normalize_page($results);
	}

	public function getWikipediaPage($id, $mobile=true)
	{
		$base_url = $mobile ? self::MOBILE_PAGE_URL : self::PAGE_URL;
		$url = $base_url . $id;

		$pattern = array(
			'title' => '<h1.*>(.*)</h1>',
		    'content' => 'id="content".*>(.*)<div id="page-secondary-actions">',
		);

		$results = $this->crawl($url, $pattern);
		$results['content'] = '<div>' . $results['content'];
		$results['date'] = date("Y-m-d");
		
		return $this->normalize_page($results);
	}
}

?>