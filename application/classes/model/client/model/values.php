<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Model_Values extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta )
	{
		$meta->table('client_model_values')
			->fields(array(
				// Первичный ключ
				'_id'			=> Jelly::field('Primary'),
				'item_id'		=> Jelly::field('Integer'),
				'property'	    => Jelly::field('BelongsTo',array(
										'foreign'	=> 'client_model_properties',
										'column'	=> 'property_id',
										'label'		=> 'Свойства'
								)),
				'value'			=> Jelly::field('String', array('label' => 'Значение')),
                'order'			=> Jelly::field('String', array('label' => 'Порядок'))
			 ));
	}

}

