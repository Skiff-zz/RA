<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_SZRGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_szr_group', $items_model 	= 'glossary_szr')
	{
		parent::initialize($meta, $table_name,  $items_model);
		
		$meta->table($table_name)
		->fields(array(
			
         'dv_szr'  => Jelly::field('HasMany',array(
	                            			    'foreign'	=> 'glossary_szr_dv.group',
	                         		 		    'label'		=> 'Элементы группы',
                              )),
         'target_szr'  => Jelly::field('HasMany',array(
	                            			    'foreign'	=> 'glossary_szr_target.group',
	                         		 		    'label'		=> 'Элементы группы',
                              )),                     	
		 ));	 
	}
}

