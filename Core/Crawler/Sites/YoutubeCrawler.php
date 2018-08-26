<?php 

namespace Core\Crawler\Sites;

use Core\Crawler\Crawler;
use Core\Service;

class YoutubeCrawler extends Crawler
{
	const VIDEO_URL = 'https://www.youtube.com/watch?v=';

	public function __construct() 
	{
		parent::__construct();
	} 

	public function getYoutubeChannel($user_id)
	{	
		$url = 'http://www.youtube.com/channel/'.$user_id.'/videos';

		$pattern = array(
			'video_list' => array(
				'items' => '<li class="channels-content-item.*<span class="yt-thumb-clip.*src="(.*)".*<span class="video-time">(.*)</span>.*<h3 class="yt-lockup-title.*<a.*href="/watch\?v=(.*)".*>(.*)</a>',
				'vars' => array(0 => 'thumb', 1 => 'time', 2 => 'original_id', 3 => 'title'),
			),
		);	

		$results = $this->crawl($url, $pattern);

		for ($i=0;$i<count($results['video_list']);$i++) {
			$results['video_list'][$i]['url'] = self::VIDEO_URL . $results['video_list'][$i]['original_id'];
			$results['video_list'][$i]['date'] = date('Y-m-d');
		}

		return $this->normalize_list($results);
	}
	

	public function getYoutubeSearch($keywords, $page=1, $sort_by='pertinence')
	{
		$base_url_pertinence = 'https://www.youtube.com/results?search_query='; //Par petinence 
		$base_url_date = 'https://www.youtube.com/results?search_sort=video_date_uploaded&search_query='; // Par date de mise en ligne

		$url = ($sort_by == 'date' ? $base_url_date : $base_url_pertinence) . urlencode($keywords) . '&page=' . $page;

		$pattern = array(
			'count' => '<p class="num-results.*<strong>(.*)</strong>',
			'video_list' => array(
				'items' => '<li class="yt-lockup .*<span class="yt-thumb-clip.*[src|data-thumb]="(.*)".*<span class="video-time">(.*)</span>.*<h3 class="yt-lockup-title">.*href="/watch\?v=(.*)".*>(.*)</a>.*<a.*data-ytid="(.*)".*>(.*)</a>.*(<div class="yt-lockup-description.*>(.*)</div>|()clearfix)',
				'vars' => array(0 => 'thumb', 1 => 'time', 2 => 'original_id', 3 => 'title', 4 => 'author_id', 5 => 'auhtor', 6 => 'trash', 7 => 'description'),
			),
		);

		$results = $this->crawl($url, $pattern);

		for ($i=0;$i<count($results['video_list']);$i++) {
			$results['video_list'][$i]['url'] = self::VIDEO_URL . $results['video_list'][$i]['original_id'];
			$results['video_list'][$i]['date'] = date('Y-m-d');
		}

		return $this->normalize_list($results);
	}

	public function getYoutubeVideo($urlOrId, $width=null, $height=null, $autostart=null)
	{
		$original_id_pattern = '[a-zA-Z0-9\-]+'; 
		$url_pattern = str_replace('?','\?',self::VIDEO_URL) . '('.$original_id_pattern.')';
		
		if (preg_match("~^$url_pattern$~siU", $urlOrId, $matche)) {
			$original_id = $matche[1];
			$url = $matche[0];

		} else if (preg_match("~$original_id_pattern~siU", $urlOrId)) {
			$original_id = $urlOrId;
			$url = self::VIDEO_URL . $original_id;
		} else {
			Serive::error("Crawler Youtube : l'url en paramètre n'est pas au bon format ou n'est pas une original ID");
		}

		$pattern = array(
			'title' => '<span id="eow-title".*>(.*)</span>',
			'author_id' => 'data-ytid="(.*)"',
		    'author' => '<span class="yt-thumb-square">.*alt="(.*)".*</span>',
		    'avatar' => '<span class="yt-thumb-square">.*<img.*src="(.*)".*width="48".*</span>',
		    'views' => '<span class="watch-view-count">(.*)</span>',
			'likes' => '<span class="likes-count">(.*)</span>',
			'dislikes' => '<span class="dislikes-count">(.*)</span>',
			'time' => '"length_seconds": (.*),',
			'description' => '<p id="eow-description" >(.*)</p>',
			'related_videos' => array(
				"items" => '<li class="video-list-item related-list-item">.*href="/watch\?v=([^&]+)".*<img.*data-thumb="(.*)".*<span class="video-time">(.*)</span>.*<span.*class="title" title="(.*)">.*de <b><span.*data-ytid="(.*)">(.*)</span></b>',	
				"vars" => array(0 => 'orignial_id', 1 => 'thumb', 2 => 'time', 3 => 'title', 4 => 'author_id', 5 => 'author'),
			),
		);

		$results = $this->crawl($url, $pattern);

		$width = !$width ? 560 : $width ;
		$height = !$height ? 315 : $height ;

		$results['content'] = self::getPlayerCode($original_id, $width, $height, $autostart);
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

	static function getPlayerCode($original_id, $width=null, $height=null, $autostart=null)
	{
		$width = !$width ? 560 : $width ;
		$height = !$height ? 315 : $height ;

		$query = '';

		if ($autostart) {
			$query .= '?autoplay=1';
		}

		$code = '<iframe width="'.$width.'" height="'.$height.'" src="//www.youtube-nocookie.com/embed/'.$original_id.$query.'" frameborder="0" allowfullscreen></iframe>';

		return $code;
	}
}

?>