<?php defined('SYSPATH') or die('No direct script access.');

class Twig extends Kohana_Twig {
	
	public static function factory($file = NULL, $data = NULL, $env = 'default')
	{

		$class = parent::factory($file, $data, $env);
		//Устанавливаем глобальные переменные
		$class->__application = Kohana::config('application')->as_array();
		$class->__system = array();
		//Массив ошибок
		$class->__errors = array();
		$class->__system['url'] = array(
			'controller' => url::site(Request::instance()->uri(array('action'=>'','id'=>'')),true),
			'current' => url::site(Request::instance()->uri(),true),
		);

		return $class;
	}
	
}
