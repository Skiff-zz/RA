<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_ShareAlertPeriod extends Jelly_Model
{
	public static $default_id = 2;
	public static $default_period = array('_id' => 2, 'color' => '98cc6b', 'name' => 'За 7 дней',  'seconds' => 604800);
	
	public static function initialize(Jelly_Meta $meta){
		$meta->table('share_alert_periods')
			->fields(array(
				'_id' => new Field_Primary,
				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
				))),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'seconds'	=>  Jelly::field('Integer', array('label' => 'Секунд до')),
				'order'	=>  Jelly::field('Integer', array('label' => 'Порядок'))

		));
	}

	public function get_name_by_id($id){
		$share_alert_period = Jelly::select('client_sharealertperiod', $id);
		if(!($share_alert_period instanceof Jelly_Model) || !$share_alert_period->loaded())
			$share_alert_period = Jelly::select('client_sharealertperiod', Model_Client_ShareAlertPeriod::$default_id);
		
		if(!($share_alert_period instanceof Jelly_Model) || !$share_alert_period->loaded()) return '';
		
		return $share_alert_period->get('name');
	}
	
	
	public function get_period($license_id){
		if(!$license_id) 
			return Model_Client_ShareAlertPeriod::$default_period;
		
		$format_alert_period = Jelly::select('client_format')->where('license', '=', $license_id)->where('name', '=', 'share_alert_period')->limit(1)->execute();
		if(!($format_alert_period instanceof Jelly_Model) || !$format_alert_period->loaded())
			return Model_Client_ShareAlertPeriod::$default_period;
		
		$share_alert_period = Jelly::select('client_sharealertperiod', (int)$format_alert_period->get('value'));
		if(!($share_alert_period instanceof Jelly_Model) || !$share_alert_period->loaded())
			return Model_Client_ShareAlertPeriod::$default_period;;
		
		return array('_id' => $share_alert_period->id(), 'color' => $share_alert_period->get('color'), 'name' => $share_alert_period->get('name'),  'seconds' => $share_alert_period->get('seconds'));
	}
}

