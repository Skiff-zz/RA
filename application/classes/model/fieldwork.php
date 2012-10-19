<?php defined('SYSPATH') or die ('No direct script access.');

class Model_FieldWork extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('field_works')
			->fields(array(
				
				'_id' => new Field_Primary,
				'field'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'field',
					'column'	=> 'field_id',
					'label'		=> 'Поле',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				
				'work_number'	=>  Jelly::field('String', array('label' => 'Номер наряда')),
				'work_color'	=>  Jelly::field('String', array('label' => 'Цвет наряда')),
				'work_date'	=>  Jelly::field('Integer', array('label' => 'Дата наряда')),
				
				'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),
				
				'processed' =>  Jelly::field('Float', array('label' => 'Обработано')),
				
				'technic_mobile'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_techniquemobile',
                        'column'	=> 'technic_mobile_id',
                        'label'		=> 'Подвижной состав'
                )),
				
				'technic_trailer'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_techniquetrailer',
                        'column'	=> 'technic_trailer_id',
                        'label'		=> 'Прицепной состав'
                )),

				'personals' => Jelly::field('HasMany',array(
					'foreign'	=> 'fieldworkpersonal',
					'label'	=> 'Персонал',
				)),
				
				'inputs'	=>  Jelly::field('Float', array('label' => 'Затраты')),
				'inputs_data'	=>  Jelly::field('String', array('label' => 'Затраты (данные)'))
		));
	}
	
	
	public function save_from_grid($grid_data, $field_id){
		$ids = array();

		foreach($grid_data as $item){
			
			if(UTF8::strpos($item['rowId'], 'new_') !== false){
				$work_row = Jelly::factory('fieldwork');
			}else{
				$work_row = Jelly::select('fieldwork', (int)str_replace('work_', '', $item['rowId']));
				if(!$work_row instanceof Jelly_Model || !$work_row->loaded()) $work_row = Jelly::factory('fieldwork');
			}
			
			$processed = explode(' ', $item['processed']['value']);
			$processed = count($processed)==2 ? trim($processed[0], 'га') : 0;
			
			$work_row->field = (int)$field_id;
			$work_row->work_number = $item['work_number']['value'];
			$work_row->work_color  = $item['work_number']['color'];
			$work_row->work_date   = strtotime($item['work_date']);
			$work_row->operation   = (int)$item['operation']['id'];
			$work_row->processed   = (float)$processed;
			$work_row->technic_mobile = (int)$item['technic_mobile']['id'];
			$work_row->technic_trailer = (int)$item['technic_trailer']['id'];
			$work_row->inputs = $item['inputs']['value'];
			$work_row->inputs_data = $item['inputs']['inputs_data'];
			
			$work_row->save();
			$ids[] = $work_row->id();
			
			Jelly::factory('fieldworkpersonal')->save_from_grid($item['personal'], $field_id, (int)$work_row->id());
		}
		
		if(count($ids)){
			Jelly::delete('fieldwork')->where('field', '=', $field_id)->where('_id', 'NOT IN', $ids)->execute();
			Jelly::delete('fieldworkpersonal')->where('field', '=', $field_id)->where('field_work', 'NOT IN', $ids)->execute();
		}else{
			Jelly::delete('fieldwork')->where('field', '=', $field_id)->execute();
			Jelly::delete('fieldworkpersonal')->where('field', '=', $field_id)->execute();
		}
	}

	
}

