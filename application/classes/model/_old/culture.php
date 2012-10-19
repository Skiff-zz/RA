<?php

class Model_Culture extends AC_APIModel
{
    public $is_dictionary = true;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = true)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('cultures')
		    ->fields(array(
			// Первичный ключ
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'atks'        => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'atk',
			    'label'	=> 'АТК культуры',
			    'through'   => 'atks_cultures'
			)),
			'tasks'      => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'task',
			    'label'	=> 'Планы по полю культуры',
			    'through'  => 'tasks_cultures'
			)),
			'type'     => Jelly::field('BelongsTo',array(
			    'foreign'	=> 'culturetype',
			    'column'	=> 'culturetype_id',
			    'label'	=> 'Тип культуры'
			))
		    ));
	}

}