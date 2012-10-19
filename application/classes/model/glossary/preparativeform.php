<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_PreparativeForm extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_preparative_forms')
			->fields(array(
				'_id'	  => Jelly::field('Primary'),
				'name'	=> Jelly::field('String', array('label' => 'Название')),
				'short_name' => Jelly::field('String', array('label' => 'Сокращённое название')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			));
	}
}

