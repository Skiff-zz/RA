<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_OrderStatus extends Jelly_Model{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('work_order_statuses')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'name' => Jelly::field('String', array('label' => 'Название')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			 ));
	}
}