<?php

class Model_Area extends AC_APIModel
{
    public $is_dictionary = false;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = false)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('areas')
		    ->fields(array(
			// Первичный ключ
			'number'      => Jelly::field('Integer', array('label' => 'Номер')),
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'kadastr_size'=> Jelly::field('Float', array('label' => 'Кадастровая площадь')),
			'size'        => Jelly::field('Float', array('label' => 'Площадь')),
			'perimeter'   => Jelly::field('Float', array('label' => 'Периметр')),
			'tasks'       => Jelly::field('HasMany',array(
			    'foreign'	=> 'task',
			    'label'	=> 'Планы поля',
			))
		    ));
	}

}