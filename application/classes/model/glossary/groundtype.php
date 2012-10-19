<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_GroundType extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_ground_type')
			->fields(array(
				'_id'	  => Jelly::field('Primary'),
				'name'	=> Jelly::field('String', array('label' => 'Название')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			));
	}
}
