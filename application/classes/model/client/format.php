<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Format extends Jelly_Model
{

	private $models = array('area' => array('field_name', 'crop_rotation_n', 'field_n', 'sector_n'),
							'road' => array('road_name', 'road_n'),
							'note' => array('note_name', 'note_n'),
							'period' => array('start', 'finish')
					  );

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('formats')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'model'			=> Jelly::field('String', array('label' => 'Модель')),
				'name'			=> Jelly::field('String', array('label' => 'Название')),
				'value'			=> Jelly::field('Integer', array('label' => 'Значение')),

				'license'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'license',
					'column'	=> 'license_id',
					'label'	=> 'Лицензия'
				))
		));
	}


	public function get_formats($license_id)
	{
		if(!$license_id) { return false; }
		$formats = Jelly::select('client_format')->where('license', '=', $license_id)->execute()->as_array();

		$result = array();
		foreach ($formats as $format) {
			$result[$format['name']] = $format['value']?$format['value']:0;
		}

		foreach($this->models as $model => $fields) {
			foreach($fields as $field) {
				if(!isset($result[$field])) $result[$field] = false;
			}
		}

		return $result;
	}


	public function saveValue($license_id, $name, $value){

		$item = Jelly::select('client_format')->where('license', '=', $license_id)->and_where('name', '=', $name)->limit(1)->execute();
		if(!($item instanceof Jelly_Model) or !$item->loaded()){
			$item = Jelly::factory('client_format');
		}

		$m = '';
		foreach($this->models as $model => $fields) {
			if(array_key_exists($name, $fields)) $m = $model;
		}


		$item->name = $name;
		$item->value = $value;
		$item->license = $license_id;
		$item->model = $m;
		$item->save();
	}

}

