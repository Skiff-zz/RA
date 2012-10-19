<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_UnitsGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_units_group', $items_model 	= 'glossary_units')
	{
		return parent::initialize($meta, $table_name,  $items_model);

	}
}

