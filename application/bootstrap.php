<?php defined('SYSPATH') or die('No direct script access.');

//-- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Europe/Kiev');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'ru_RU');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

//-- Configuration and initialization -----------------------------------------

/**
 * Set Kohana::$environment if $_ENV['KOHANA_ENV'] has been supplied.
 * 
 */
if (isset($_ENV['KOHANA_ENV']))
{
	Kohana::$environment = $_ENV['KOHANA_ENV'];
}else{
	Kohana::$environment = Kohana::DEVELOPMENT;
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
Kohana::init(array(
		'base_url'	=> 	'/',
		'profile'	=> 	(Kohana::$environment != Kohana::PRODUCTION),
		'index_file'=>	'/',
		'caching'	=>	(Kohana::$environment == Kohana::PRODUCTION),
		'errors'	=> (Kohana::$environment != Kohana::PRODUCTION)
));
/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Kohana_Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
       		        'unittest'  => MODPATH.'unittest',
					'patches'   => MODPATH.'patches',
					'image'   => MODPATH.'image',
					'twig'   => MODPATH.'twig',
					'jelly-auth'   => MODPATH.'jelly-auth',
					'auth'   => MODPATH.'auth',
					'jelly'   => MODPATH.'jelly',
					'migration'   => MODPATH.'migration',
					'dbforge'   => MODPATH.'dbforge',
					'database'   => MODPATH.'database',
					'email'   => MODPATH.'email',
));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */

Route::set('auth.login', 'login')
		->defaults(array(
			'controller' => 'client',
			'action'     => 'login',
		));
		
Route::set('auth.logout', 'logout')
		->defaults(array(
			'controller' => 'client',
			'action'     => 'logout',
		));

Route::set('auth.admin.login', '/auth/login')
		->defaults(array(
			'controller' => 'auth',
			'action'     => 'login',
		));

Route::set('auth.admin.logout', '/auth/logout')
		->defaults(array(
			'controller' => 'auth',
			'action'     => 'logout',
		));


if(Kohana::$environment != Kohana::PRODUCTION) 
{
	Route::set('system', 'sys(/<action>(/<id>))')
		->defaults(array(
			'controller' => 'system',
			'action'     => 'index',
		));
}


Route::set('api', 'api(/<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'api_system',
		'directory'  => '/api/',
		'action'     => 'index',
	));

Route::set('clientpc', 'clientpc(/<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'client_license',
		'directory'  => '/client/',
		'action'     => 'index',
	));

Route::set('glossary', 'glossary(/<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'glossary_culture',
		'directory'  => '/glossary/',
		'action'     => 'index',
	));
Route::set('client', '(client(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'client',
		'action'     => 'index',
));
/*
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'agroclever',
		'action'     => 'index',
));
*/

Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'client',
		'action'     => 'index',
));


Route::set('admin', 'admin(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'agroclever',
		'action'     => 'index',
));


if ( ! defined('SUPPRESS_REQUEST'))
{
	/**
	 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
	 * If no source is specified, the URI will be automatically detected.
	 */
	echo Request::instance()
		->execute()
		->send_headers()
		->response;
}
