<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrderField2Material extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_handorderfield2material')
			->fields(array(
				'_id' 			=> new Field_Primary,
				
				'native' 		=> Jelly::field('Boolean', array('label' => 'Из атк или добавлен вручную')),
                
                'hand_order' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorder',
                        'column'	=> 'client_work_handorder_id',
                        'label'		=> 'Плановый наряд'
                )),	
                
                'hand_order_field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorder2field',
                        'column'	=> 'client_work_handorder2field_id',
                        'label'		=> 'Поле наряда'
                )),	
				
				'material_model' => Jelly::field('String', array('label' => 'Модель материала')),
                'material_id' => Jelly::field('Integer', array('label' => 'ИД материала')),
				
                
                'planned_crop_norm' => Jelly::field('String', array('label' => 'Норма внесения (план)')),
                'planned_crop_norm_units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'planned_crop_norm_units_id',
                        'label'		=> 'Единицы измерения (план)'
                )),
				
				'actual_crop_norm' => Jelly::field('String', array('label' => 'Норма внесения (факт)')),
                'actual_crop_norm_units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'actual_crop_norm_units_id',
                        'label'		=> 'Единицы измерения (факт)'
                )),
                
				
				
              'planned_debit' => Jelly::field('String', array('label' => 'К списанию (план)')),
                'actual_debit' => Jelly::field('String', array('label' => 'Списано (факт)')),
//				'debit_diff' => Jelly::field('String', array('label' => 'Разница')),
				
				'planned_price' => Jelly::field('String', array('label' => 'Цена')),
				'actual_price' => Jelly::field('String', array('label' => 'Цена')),
				
              'planned_total' => Jelly::field('String', array('label' => 'Затраты (план)')),
				'actual_total' => Jelly::field('String', array('label' => 'Затраты (факт)'))
//				'total_diff' => Jelly::field('String', array('label' => 'Затраты (разница)'))
		));
	}
	
	
	public function save_from_grid($materials, $order_id, $field_id){
		$data = array();
		
		foreach($materials as $material){
			$data[] = array(
				'hand_order' => (int)$order_id,
				'hand_order_field' => (int)$field_id,
				'material_model' => $material['material']['model'],
				'material_id' => (int)$material['material']['id'],
				'planned_crop_norm' => (float)$material['planned_crop_norm'],
				'planned_crop_norm_units' => (int)$material['planned_crop_norm_units'],
				'actual_crop_norm' => (float)$material['actual_crop_norm'],
				'actual_crop_norm_units' => (int)$material['actual_crop_norm_units'],
				'planned_debit' => (float)$material['planned_debit'],
				'actual_debit' => (float)$material['actual_debit'],
				'planned_price' => (float)$material['planned_price'],
				'actual_price' => (float)$material['actual_price'],
				'planned_total' => (float)$material['planned_total'],
				'actual_total' => (float)$material['actual_total'],
				'native' => (bool)$material['material']['isNative']
				
			);
		}
		
		Jelly::delete('Client_Work_handOrderField2Material')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
        
        foreach($data as $item){
            $model = Jelly::factory('Client_Work_handOrderField2Material');
            $model->set($item);
            $model->save();
        }
	}

}

