<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_FieldShare extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('field_shares')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'field'			=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'field',
					'column'	=> 'field_id',
					'label'	=> 'Поле'
				)),


				'name'				 => Jelly::field('String', array('label' => 'ФИО')),
				'number'			=> Jelly::field('String', array('label' => 'Номер акта')),
				'order_type'		=> Jelly::field('String', array('label' => 'Тип договора')),
				'square'			  => Jelly::field('String', array('label' => 'Площадь пая')),
				'order_date_start' => Jelly::field('Integer', array('label' => 'Срок действия договора с')),
				'order_date_end'  => Jelly::field('Integer', array('label' => 'Срок действия договора по'))
		));
	}


	public function save_shares($field_id, $shares){
		$field_shares_ids = array();

		foreach($shares as $share) {
			if(UTF8::strpos($share['id'], 'new') !== false){
				$sh = Jelly::factory('client_fieldshare');
			}else{
				$sh = Jelly::select('client_fieldshare', (int)$share['id']);
				if(!$sh instanceof Jelly_Model || !$sh->loaded()){
					$sh = Jelly::factory('client_fieldshare');
				}
			}

			$sh->field = $field_id;
			$sh->name = $share['name'];
			$sh->number = $share['number'];
			$sh->order_type = $share['order_type'];
			$sh->square = (float)$share['square'];
			$sh->order_date_start = $share['order_date_start'];
			$sh->order_date_end = $share['order_date_end'];
			$sh->save();

			$field_shares_ids[] = $sh->id();
		}

		if(!count($field_shares_ids)) $field_shares_ids = array(-1);
		Jelly::delete('client_fieldshare')->where('_id', 'NOT IN', $field_shares_ids)->execute();
	}

}

