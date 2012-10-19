<?php

class Model_Plan extends AC_APIModel
{
    public $is_dictionary = false;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = false)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('plans')
		    ->fields(array(
			// Первичный ключ
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'type'	      => Jelly::field('String', array('label' => 'Тип')),
			'desc'	      => Jelly::field('Text', array('label' => 'Описание')),
			'date_from'   => Jelly::field('String', array('label' => 'Начало периода')),
			'date_to'     => Jelly::field('String', array('label' => 'Конец периода')),
			'status'      => Jelly::field('Integer', array('label' => 'Статус')),
			'tasks'       => Jelly::field('HasMany',array(
			    'foreign'	=> 'task',
			    'label'	=> 'Планы поля',
			))
		    ));
	}

}