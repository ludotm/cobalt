<?php 

namespace Core;

class Social
{	
	/* --------------------- URLS ----------------------------- */

	static function facebook_user_url ($username) {
		return 'https://www.facebook.com/' . $username;
	}

	static function facebook_page_url ($page_name) {
		return 'https://www.facebook.com/' . $page_name;
	}

}