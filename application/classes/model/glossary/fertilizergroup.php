<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_FertilizerGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_fertilizer_group', $items_model 	= 'glossary_fertilizer')
	{
		parent::initialize($meta, $table_name,  $items_model);
        
        
       	$meta->table($table_name)
		->fields(array(
			
         'dv_fertilizers'  => Jelly::field('HasMany',array(
	                            			    'foreign'	=> 'glossary_fertilizer_dv.group',
	                         		 		    'label'		=> 'Элементы группы',
                              )),
         'target_fertilizers'  => Jelly::field('HasMany',array(
	                            			    'foreign'	=> 'glossary_fertilizer_target.group',
	                         		 		    'label'		=> 'Элементы группы',
                              )),                     	
		 ));
	}
}

