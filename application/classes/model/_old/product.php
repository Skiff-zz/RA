<?php

class Model_Product extends AC_APIModel
{
    public $is_dictionary = true;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = true)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('products')
		    ->fields(array(
			// Первичный ключ
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'atks'        => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'atk',
			    'label'	=> 'АТК продукта',
			    'through'   => 'atks_products'
			)),
			'tasks'      => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'task',
			    'label'	=> 'Планы по полю продукта',
			    'through'   => 'tasks_products'
			))
		    ));
	}

}