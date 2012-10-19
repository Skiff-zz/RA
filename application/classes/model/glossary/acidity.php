<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Acidity extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_acidity')
			->fields(array(
				'_id'	  => Jelly::field('Primary'),
				'name'	=> Jelly::field('String', array('label' => 'Название')),
				'acidity_from'	=> Jelly::field('Float', array('label' => 'Начало диапазона')),
				'acidity_to'	=> Jelly::field('Float', array('label' => 'Конец диапазона')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			));
	}
}
