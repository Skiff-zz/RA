<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_CultureType extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_culture_types')
			->fields(array(
				// Первичный ключ
				'_id'			=> Jelly::field('Primary'),
				'name'			=> Jelly::field('String', array('label' => 'Название')))
			);
	}


	public function saveCultureTypes($ctypes = array()){
		$ids = array();
		foreach($ctypes as $ctype){

			if(UTF8::strpos($ctype['id'], 'new_') !== false){
				$ctype_model = Jelly::factory('glossary_culturetype');
			}else{
				$ctype_model = Jelly::select('glossary_culturetype', (int)$ctype['id']);
			}

			$ctype_model->name = $ctype['name'];
			$ctype_model->save();
			$ids[] = $ctype_model->id();
		}

		if($ids){
			Jelly::delete('glossary_culturetype')->where('_id', 'NOT IN', $ids)->execute();
		}
		else {
			Jelly::delete('glossary_culturetype')->execute();
		}
	}
}

