<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Production extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_production', $items_model 	= 'glossary_productionclass')
	{
		parent::initialize($meta, $table_name,  $items_model);

		$meta->table($table_name)
			->fields(array(
				'units'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'units_id',
							'label'		=> 'Единицы измерения'
					)),

				'cultures'      => Jelly::field('ManyToMany',array(
						'foreign'	=> 'glossary_culture',
						'label'	=> 'Культуры',
						'through'   => 'glossary_production_production2cultures'
					)),
			 ));
	}
    
    
    public function update_tree_with_cultures(&$data){
        foreach($data as &$item){
            if(substr($item['id'], 0, 1)=='g'){
                $record = Jelly::select('glossary_production', (int)substr($item['id'], 1));
            }else{
                $record = Jelly::select('glossary_production', (int)substr($item['parent'], 1));
            }
            
            $item['cultures'] = array();
            
            if($record instanceof Jelly_Model && $record->loaded()){
                //$item['cultures'] = $record->cultures->as_array();
                $cultures = $record->cultures->as_array();
                foreach($cultures as $c){
                    $item['cultures'][] = $c['_id'];
                }
            }
        }
    }
}

