<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Fertilizer_Target extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_fertilizer_target', $group_model = 'glossary_fertilizergroup')
	{
		return parent::initialize($meta,  $table_name, $group_model );
        
	}
}

