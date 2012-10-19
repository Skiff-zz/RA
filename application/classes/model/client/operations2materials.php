<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Operations2Materials extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operations2materials')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),	
                
                'material_model' => Jelly::field('String', array('label' => 'Модель материала')),
                'material_id' => Jelly::field('Integer', array('label' => 'ИД материала')),
                
                
                'crop_norm' =>  Jelly::field('String', array('label' => 'Норма внесения')),
                'crop_norm_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'crop_norm_units_id',
                        'label'		=> 'Единицы измерения'
                ))

		));
	}
       
    
    public function save_from_grid($materials, $operation_id){
        $data = array();
        foreach($materials as $material){
            $data[] = array(
                'operation' => $operation_id,
                'material_model' => $material['name']['model'],
                'material_id' => $material['name']['id'],
                'crop_norm' => $material['crop_norm'],
                'crop_norm_units' => $material['crop_norm_units']
            );
        }
        
        Jelly::delete('client_operations2materials')->where('operation', '=', $operation_id)->execute();
        
        foreach($data as $item){
            $model = Jelly::factory('client_operations2materials');
            $model->set($item);
            $model->save();
        }
    }
    
    
    public function prepare_materials($materials){
        foreach($materials as &$material){
            $m = Jelly::select('glossary_'.$material['material_model'], (int)$material['material_id']);
            if($m instanceof Jelly_Model && $m->loaded()){
                $material['name'] = $m->name;
                $material['color'] = $m->color;
                if($material['material_model']=='productionclass'){
                    $material['name'] = $m->group->name.' '.$material['name'];
                }
            }
        }
        return $materials;
    }
   

}

