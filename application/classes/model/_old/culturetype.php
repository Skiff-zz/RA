<?php

class Model_CultureType extends AC_APIModel
{
    public $is_dictionary = true;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = true)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('culture_types')
		    ->fields(array(
			// Первичный ключ
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'parent'      => Jelly::field('BelongsTo',array(
			    'foreign'	=> 'culturetype',
			    'column'	=> 'parent_id',
			    'label'	=> 'Родительский тип',
			)),
			'cultures'    => Jelly::field('HasMany',array(
			    'foreign'	=> 'culture',
			    'label'	=> 'Культуры данного типа',
			))
		    ));
	}

}