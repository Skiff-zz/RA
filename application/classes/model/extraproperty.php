<?php
class Model_ExtraProperty extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('extra_properties')
			->fields(array(
					// Первичный ключ
					'_id'	 => Jelly::field('Primary'),
					'name'	 => Jelly::field('String', array('label' => 'Название свойства')),
					'value'	 => Jelly::field('String', array('label' => 'Значение свойства')),
					'order'	 => Jelly::field('Integer', array('label' => 'Порядок вывода')),
					'block'  => Jelly::field('String', array('label' => 'Блок')),
					'object' => Jelly::field('Integer', array('label' => 'ID связанного объекта'))
			 ));
	}

	public function updateOldFields($object_id, $block, $fields = array()){
		//удаляем старые
		$old = Jelly::select('extraproperty')->where('object', '=', $object_id)->and_where('block', '=', $block)->execute();
		foreach($old as $o){
			foreach($fields as $f){
				if(isset($f['_id']) && $f['_id']==$o->id()){ continue 2; }
			}
			$o->delete();
		}

		//апдэйтим новые
		foreach($fields as $field){
			if(isset($field['_id'])){
				$item = Jelly::select('extraproperty')->load($field['_id']);
				if(!($item instanceof Jelly_Model) or !$item->loaded()){
					$item = Jelly::factory('extraproperty');
				}
			}else{
				$item = Jelly::factory('extraproperty');
			}

			if(trim($field['name'])===""){ continue; }

			$item->name = trim($field['name']);
			$item->value = trim($field['value']);
			$item->object = $object_id;
			$item->block = $block;
			$item->save();
		}
	}
}
