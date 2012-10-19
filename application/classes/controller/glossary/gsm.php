<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Gsm extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_gsm';
	protected $model_group_name = 'glossary_gsmgroup';

	public function inner_edit(&$view){
		$view->units = Jelly::factory('glossary_units')->getUnits('gsm');
	}
}
