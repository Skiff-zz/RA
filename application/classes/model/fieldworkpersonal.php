<?php defined('SYSPATH') or die ('No direct script access.');

class Model_FieldWorkPersonal extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('field_work_personals')
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
				
				'field_work'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'fieldwork',
					'column'	=> 'fieldwork_id',
					'label'		=> 'Работа',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				
				'personal'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personal',
                        'column'	=> 'personal_id',
                        'label'		=> 'Персонал'
                )),
		));
	}
	
	
	public function save_from_grid($grid_data, $field_id, $work_id){
		$ids = array();
		
		foreach($grid_data as $item){
			if(!isset($item['id']) || !(int)$item['id']) continue;
			
			$personal_row = Jelly::select('fieldworkpersonal')->where('field', '=', $field_id)->where('field_work', '=', $work_id)->where('personal', '=', $item['id'])->limit(1)->execute();
			if(!$personal_row instanceof Jelly_Model || !$personal_row->loaded()){
				$personal_row = Jelly::factory('fieldworkpersonal');
				$personal_row->field = (int)$field_id;
				$personal_row->field_work = (int)$work_id;
				$personal_row->personal = (int)$item['id'];
				$personal_row->save();
			}
			
			$ids[] = (int)$item['id'];
		}
		
		
		
		if(count($ids)){
			Jelly::delete('fieldworkpersonal')->where('field', '=', $field_id)->where('field_work', '=', $work_id)->where('personal', 'NOT IN', $ids)->execute();
		}else{
			Jelly::delete('fieldworkpersonal')->where('field', '=', $field_id)->where('field_work', '=', $work_id)->execute();
		}
	}

}

