<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrderField2Personal extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_handorderfield2personal')
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
                
                'personal'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personalgroup',
                        'column'	=> 'personal_id',
                        'label'		=> 'Персонал'
                )),
				
				'person'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personal',
                        'column'	=> 'person_id',
                        'label'		=> 'ФИО'
                )),
             
                'planned_count'  => Jelly::field('Integer', array('label' => 'Количество (план)')),
                'planned_salary' => Jelly::field('String', array('label' => 'З/П (план), грн/га')),
				
				'actual_count'  => Jelly::field('Integer', array('label' => 'Количество (факт)')),
                'actual_salary' => Jelly::field('String', array('label' => 'З/П (факт), грн/га')),
				
                'planned_total' => Jelly::field('String', array('label' => 'Затраты (план)')),
				'actual_total' => Jelly::field('String', array('label' => 'Затраты (факт)'))
//				'total_diff' => Jelly::field('String', array('label' => 'Затраты (разница)'))
		));
	}
	
	public function save_from_grid($personals, $order_id, $field_id){
		$data = array();
		foreach($personals as $personal){
			$data[] = array(
				'hand_order' => (int)$order_id,
				'hand_order_field' => (int)$field_id,
				'personal' => (int)$personal['personal']['id'],
				'person' => (int)$personal['person']['id'],
				'planned_count' => (int)$personal['planned_count'],
				'planned_salary' => (float)$personal['planned_salary'],
				'actual_count' => (int)$personal['actual_count'],
				'actual_salary' => (float)$personal['actual_salary'],
				'planned_total' => (float)$personal['planned_total'],
				'actual_total' => (float)$personal['actual_total'],
				'native' => (bool)$personal['personal']['isNative']
			);
		}
		
		Jelly::delete('Client_Work_handOrderField2Personal')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
        
        foreach($data as $item){
            $model = Jelly::factory('Client_Work_handOrderField2Personal');
            $model->set($item);
            $model->save();
        }
	}
    

}

