<?php 

namespace Core\Crawler\Sites;

use Core\Crawler\Crawler;
use Core\Service;

class DailymotionCrawler extends Crawler
{
	const VIDEO_URL = 'http://www.dailymotion.com/video/';
	const USER_URL = 'http://www.dailymotion.com/user/';

	public function __construct() 
	{
		parent::__construct();
	} 

	public function getDailymotionChannel($user_id, $page=1)
	{	
		$url = self::USER_URL.$user_id.'/'.$page;

		$pattern = array(
			'video_list' => array(
				'items' => '<div class="sd_video_griditem.*data-owner="(.*)".*<div class="sd_video_previewtwig.*data-id="(.*)".*<div class="badge badge-duration">(.*)</div>.*src="(.*)".*<div class="title.*title="(.*)"',
				'vars' => array(0 => 'author_id',  1 => 'original_id', 2 => 'time', 3 => 'thumb', 4 => 'title'),
			),
		);	

		$results = $this->crawl($url, $pattern);

		for ($i=0;$i<count($results['video_list']);$i++) {
			$results['video_list'][$i]['url'] = self::VIDEO_URL . $results['video_list'][$i]['original_id'];
			$results['video_list'][$i]['date'] = date('Y-m-d');
		}

		return $this->normalize_list($results);
	}
	

	public function getDailymotionSearch($keywords, $page=1, $sort_by='pertinence')
	{
		$base_url_pertinence = "http://www.dailymotion.com/fr/relevance/search/"; //Par petinence 
		$base_url_date = "http://www.dailymotion.com/fr/search/"; // Par date de mise en ligne

		$url = ($sort_by == 'date' ? $base_url_date : $base_url_pertinence) . urlencode($keywords) . '/' . $page;

		$pattern = array(
			'count' => '<ul class=" mo_tabs">.*<a.*>(.*)</a>',
			'video_list' => array(
				'items' => '<div class="sd_video_listitem.*<a.*data-xid="(.*)".*<div class="badge badge-duration">(.*)</div>.*src="(.*)".*<div class="media-block.*<div class="title.*<a.*title="(.*)".*<a.*data-user-uri="/(.*)".*>(.*)</a>',
				'vars' => array(0 => 'original_id', 1 => 'time', 2 => 'thumb', 3 => 'title', 4 => 'author_id', 5 => 'auhtor'),
			),
		);

		$results = $this->crawl($url, $pattern);

		for ($i=0;$i<count($results['video_list']);$i++) {
			$results['video_list'][$i]['url'] = self::VIDEO_URL . $results['video_list'][$i]['original_id'];
			$results['video_list'][$i]['date'] = date('Y-m-d');
		}

		return $this->normalize_list($results);
	}

	public function getDailymotionVideo($urlOrId, $width=null, $height=null, $autostart=null, $start=null)
	{	
		$original_id_pattern = '[a-zA-Z0-9]+'; 
		$url_pattern = self::VIDEO_URL . '('.$original_id_pattern.')(_.*)?';
		
		if (preg_match("~^$url_pattern$~siU", $urlOrId, $matche)) {
			$original_id = $matche[1];
			$url = $matche[0];

		} else if (preg_match("~$original_id_pattern~siU", $urlOrId)) {
			$original_id = $urlOrId;
			$url = self::VIDEO_URL . $original_id;
		} else {
			Service::error("Crawler Dailymotion : l'url en paramètre n'est pas au bon format ou n'est pas une original ID : ".$urlOrId);
		}

		$pattern = array(
			'title' => '<span.*id="video_title".*>(.*)</span>',
			'author_id' => '<h3 class="author.*<a.*href="/(.*)"',
		    'author' => '<h3 class="author.*<a.*>(.*)</a>',
		    'avatar' => 'owner-avatar.*<img.*src="(.*)"',
		    'views' => '<div.*id="video_views_count".*>(.*) vues.*</div>',
		    'time' => '<meta property="video:duration" content="(.*)" />',
			'description' => '<meta property="og:description" content="(.*)" />',
			/*
			'related_videos' => array(
				"items" => '<div class="sd_video_itemrelated.*data-id="(.*)".*<div class="badge badge-duration">(.*)</div>.*src="(.*)".*<h3 class="sd_video_title.*<span.*>(.*)</span>.*<span.*data-owner-url="/(.*)".*>(.*)</span>',	
				"vars" => array(0 => 'original_id', 1 => 'time', 2 => 'thumb', 3 => 'title', 4 => 'author_id', 5 => 'author'),
			),
			*/
		);

		$results = $this->crawl($url, $pattern);

		$width = !$width ? 560 : $width ;
		$height = !$height ? 315 : $height ;

		$results['content'] = self::getPlayerCode($original_id, $width, $height, $autostart, $start);
		$results['original_id'] = $original_id;

		if (array_key_exists('time', $results)) {
			$time = $results['time'];
			$hour = floor($time/3600);
			$hour = $hour < 10 ? '0'.$hour : $hour;
			$minutes = floor(($time-($hour*3600))/60);
			$minutes = $minutes < 10 ? '0'.$minutes : $minutes;
			$seconds = $time-($hour*3600)-($minutes*60);
			$seconds = $seconds < 10 ? '0'.$seconds : $seconds;
			$results['time'] = $hour.':'.$minutes.':'.$seconds;
		}

		return $this->normalize_page($results);
	}

	static function getPlayerCode($original_id, $width=null, $height=null, $autostart=null, $start=null)
	{
		$width = !$width ? 560 : $width ;
		$height = !$height ? 315 : $height ;

		$query = '';

		if ($autostart) {
			$query .= $query == '' ? '?' : '&';
			$query .= 'autoPlay=1';
		}
		if ($start) {
			$query .= $query == '' ? '?' : '&';
			$query .= 'start='.$start;
		}

		$code = '<iframe frameborder="0" width="'.$width.'" height="'.$height.'" src="//www.dailymotion.com/embed/video/'.$original_id.$query.'" allowfullscreen></iframe>';
		return $code;
	}
}

?>