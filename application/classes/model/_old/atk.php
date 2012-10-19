<?php

class Model_Atk extends AC_APIModel
{
    public $is_dictionary = true;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = true)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('atks')
		    ->fields(array(
			// Первичный ключ
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'products'      => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'product',
			    'label'	=> 'Продукты АТК',
			    'through'   => 'atks_products'
			)),
			'cultures'      => Jelly::field('ManyToMany',array(
			    'label'	=> 'Культуры АТК',
			    'foreign'	=> 'culture',
			    'through'   => 'atks_cultures'
			))
		    ));
	}

}