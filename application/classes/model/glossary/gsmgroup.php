<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_GSMGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_gsm_group', $items_model 	= 'glossary_gsm')
	{
		return parent::initialize($meta, $table_name,  $items_model);
	}
}

