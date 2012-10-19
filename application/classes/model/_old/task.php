<?php

class Model_Task extends AC_APIModel
{
    public $is_dictionary = false;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = false)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('areas_plans')
		    ->fields(array(
			'name'	      => Jelly::field('String', array('label' => 'Название')),
			'type'	      => Jelly::field('String', array('label' => 'Тип')),
			'plan'     => Jelly::field('BelongsTo',array(
			    'foreign'	=> 'plan',
			    'column'	=> 'plan_id',
			    'label'	=> 'План'
			)),
			'area'     => Jelly::field('BelongsTo',array(
			    'foreign'	=> 'area',
			    'column'	=> 'area_id',
			    'label'	=> 'Поле'
			)),
			'atk'     => Jelly::field('BelongsTo',array(
			    'foreign'	=> 'atk',
			    'column'	=> 'atk_id',
			    'label'	=> 'АТК'
			)),
			'products'      => Jelly::field('ManyToMany',array(
			    'foreign'	=> 'product',
			    'label'	=> 'Продукты плана по полю',
			    'through'   => 'tasks_products'
			)),
			'cultures'      => Jelly::field('ManyToMany',array(
			    'label'	=> 'Культуры плана по полю',
			    'foreign'	=> 'culture',
			    'through'   => 'tasks_cultures'
			)),
			'status'      => Jelly::field('Integer', array('label' => 'Статус'))
		    ));
	}

}