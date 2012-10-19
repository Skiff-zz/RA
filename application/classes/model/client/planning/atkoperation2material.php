<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkOperation2Material extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkoperation2material')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),	
                
                'atk_operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk2operation',
                        'column'	=> 'client_planning_atk2operation_id',
                        'label'		=> 'АТК операция'
                )),	
				
				'material_model' => Jelly::field('String', array('label' => 'Модель материала')),
                'material_id' => Jelly::field('Integer', array('label' => 'ИД материала')),
				
				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
				'crop_lost' => Jelly::field('String', array('label' => 'Потеря урожайности')),
                
                'crop_norm' => Jelly::field('String', array('label' => 'Норма внесения')),
                'units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'units_id',
                        'label'		=> 'Единицы измерения'
                )),
                
                'count' => Jelly::field('String', array('label' => 'Необходимое количество')),
                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты')),
				
				'profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
				'rentability' => Jelly::field('String', array('label' => 'Рентабельность, %'))
		));
	}
    
    
    public function save_from_grid($materials, $atk_id, $is_version = false){
        $data = array();
        foreach($materials as $operation){ 
            if(!isset($operation['materials']) || !count($operation['materials']) || !isset($operation['materials'][0]['id']) || !$operation['materials'][0]['id'])continue;
            
            for($i=0; $i<count($operation['materials']); $i++)
			{
                $price = is_array($operation['price'][$i]) ? $operation['price'][$i]['value'] : $operation['price'][$i];
                $data[] = array(
                    //'id' => $material['rowId'],
                    'atk' => $atk_id,
                    'atk_operation' => $operation['atk_operation'],
                    'material_model' => $operation['materials'][$i]['model'],
                    'material_id' => $operation['materials'][$i]['id'],
					'checked' => $operation['materials'][$i]['checked'],
					'crop_lost' => $operation['crop_lost_koef'][$i],
                    'crop_norm' => (float)$operation['crop_norm'][$i]>0 ? (float)$operation['crop_norm'][$i] : 0,
                    'units' => (int)$operation['units'][$i],
                    'count' => (float)$operation['count'][$i]>0 ? (float)$operation['count'][$i] : 0,
                    'price' => $price>0 ? $price : 0,
                    'total' => (float)$operation['total'][$i]>0 ? (float)$operation['total'][$i] : 0,
					'profit' => ((float)$operation['profit'][$i])+0,
					'rentability' => ((float)$operation['rentability'][$i])+0
                );
                
                /** Обратная связь **/
                $op = Jelly::select('client_planning_atk2operation', (int)$operation['atk_operation']);
                
                if($op instanceof Jelly_Model and $op->loaded() and $op->operation->id())
                {
                	$op_test = Jelly::select('client_operations2materials')
								->where('operation', '=', $op->operation->id())
								->where('material_model', '=', $operation['materials'][$i]['model'])
								->where('material_id', '=', $operation['materials'][$i]['id'])
					->load();
                	
                	if(!($op_test instanceof Jelly_Model) or !$op_test->loaded())
                	{
               			$n = Jelly::factory('client_operations2materials');
               			
               			$n->operation 		= $op->operation->id();
               			$n->material_model 	= $operation['materials'][$i]['model'];
               			$n->material_id 	= $operation['materials'][$i]['id'];
               			$n->crop_norm	 	= (float)$operation['crop_norm'][$i] >0 ? (float)$operation['crop_norm'][$i] : 0;
               			$n->crop_norm_units	= (int)$operation['units'][$i];
               			
               			$n->save();
               			
               			unset($n);
 					}
                }
            }
        }
        
        Jelly::delete('Client_Planning_AtkOperation2Material')->where('atk', '=', $atk_id)->execute();
        
        foreach($data as $item){
            $model = Jelly::factory('Client_Planning_AtkOperation2Material');
            $model->set($item);
            $model->save();
        }
    }

}

