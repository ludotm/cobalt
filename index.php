<?php

define('MAJ', '2017-08-01');
define('IS_LOCAL', ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' ? true : false) );
define('PROTOCOL', 'http' );
//define('PROTOCOL', (IS_LOCAL ? 'http' : 'https') );

define('ROOT', dirname(__FILE__)); 
define('DS', DIRECTORY_SEPARATOR);
define('IDS', '\\');
define('PREFIX', '');
define('CORE_FOLDER', 'Core');
define('CORE_PATH', ROOT.DS.CORE_FOLDER);
define('BASE_URL', dirname(dirname($_SERVER['SCRIPT_NAME']))); 
define('DOMAIN', $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null);
define('SITE_URL', PROTOCOL.'://'.DOMAIN);
define('REMOTE_VALIDATION_URL', '/check-value');
if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
	define('LOCALE', substr(locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2));
} else {
	define('LOCALE', 'fr-FR');
}

define('PHP_EXT', 'php'); // pour bcompiler 


function autoload($class)
{
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

	if (is_readable(ROOT . DS . $path . '.php')) {
		require_once(ROOT . DS . $path . '.php');
	}
}
spl_autoload_register('autoload');

require_once(CORE_PATH.DS."functions_error.php");
require_once(CORE_PATH.DS."functions_params.php");
require_once(CORE_PATH.DS."functions_language.php");

if (!file_exists('config/config.php')) {

	
	mkdir('config', 0775, true);
	mkdir('config/models', 0775, true);
	mkdir('files/upload', 0775, true);
	mkdir('files/logs', 0775, true);
	mkdir('files/cache', 0775, true);
	mkdir('files/cookies', 0775, true);
	file_put_contents('files/.htaccess', 'deny from all');
	file_put_contents('files/logs/error_logs.txt', '');

	$base_config = "return array( 'config' => array('plugins' => array('jquery','bootstrap','cobalt','form_validate','font_awesome'),),);";
	file_put_contents('config/config.php', '<?php '.$base_config.'?>');
}
if (!file_exists('config/routes.php')) {

	if (stripos($_SERVER['REQUEST_URI'], 'install') === false) {
		header('location:/install/');
	} 
}

_set('config/config.php');				
_set('config/routes.php');
_set('plugins/plugins.php');

if (IS_LOCAL) {
	_set('config/local_config.php');
} else {
	_set('config/distant_config.php');
}

use Core\Launcher;
new Launcher();

?>
