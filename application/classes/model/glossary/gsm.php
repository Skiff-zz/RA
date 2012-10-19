<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_GSM extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_gsm', $group_model = 'glossary_gsmgroup')
	{
		parent::initialize($meta, $table_name, $group_model);
		
		$meta->table($table_name)
			->fields(array(
				'units'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'units_id',
							'label'		=> 'Единицы измерения'
					)),
			 ));
	}

}

