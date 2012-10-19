<?php defined('SYSPATH') or die('No direct script access.');
return array
(
	'media' 				=> url::site('/media',true),
	'site'	 				=> UTF8::rtrim(url::site('/',true), '/'),
	'root_url'              => 'http://realty-assistant.com/'
);

