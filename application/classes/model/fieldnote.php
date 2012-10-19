<?php defined('SYSPATH') or die ('No direct script access.');

class Model_FieldNote extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('field_notes')
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
				
				'txt' => Jelly::field('String', array('label' => 'Текст')),
				
				'create_date' =>  Jelly::field('Integer', array('label' => 'Дата создания')),
				'execute_date' =>  Jelly::field('Integer', array('label' => 'Дата выполнения')),
				
				'executed' => Jelly::field('Boolean', array('label' => 'Выполнено')),
				
				
				'order' =>  Jelly::field('Integer', array('label' => 'Порядок'))
		));
	}
	
	
	public function save_from_grid($grid_data, $field_id){
		$ids = array();
		
		foreach($grid_data as $item){
			
			if(UTF8::strpos($item['rowId'], 'new_') !== false){
				$note_row = Jelly::factory('fieldnote');
			}else{
				$note_row = Jelly::select('fieldnote', (int)$item['rowId']);
				if(!$note_row instanceof Jelly_Model || !$note_row->loaded()) $note_row = Jelly::factory('fieldnote');
			}
			
			$note_row->field = $field_id;
			$note_row->txt = $item['txt']['value'];
			$note_row->create_date = strtotime(trim($item['create_date']));
			$note_row->execute_date = strtotime(trim($item['execute_date']));
			$note_row->executed = $item['executed']['executed'];
			$note_row->save();
			
			$ids[] = $note_row->id();
		}
		
		if(count($ids)){
			Jelly::delete('fieldnote')->where('field', '=', $field_id)->where('_id', 'NOT IN', $ids)->execute();
		}else{
			Jelly::delete('fieldnote')->where('field', '=', $field_id)->execute();
		}
	}

	
}

