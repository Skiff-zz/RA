<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_ChemicalCompositionGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_chemicalcompositiongroup', $items_model 	= 'glossary_chemicalcomposition')
	{
		return parent::initialize($meta, $table_name,  $items_model);
        
	}
}

