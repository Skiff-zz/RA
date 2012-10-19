<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkStatus extends Jelly_Model{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('planning_atk_statuses')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'name' => Jelly::field('String', array('label' => 'Название')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			 ));
	}
}