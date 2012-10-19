<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Szr_SzrDv extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_szr2dv')
			->fields(array(
					'_id'			=> Jelly::field('Primary'),
					'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
					'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения')),
//					'license'	      	=> Jelly::field('BelongsTo',array('label'		=> 'Лицензия',
//						'foreign'	=> 'license',
//						'column'	=> 'license_id',
//						'rules' => array(
//								'not_empty' => NULL
//						)
//					)),
					'dv'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_szr_dv',
							'column'	=> 'dv_id',
							'label'		=> 'ДВ'
					)),
					'units'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'units_id',
							'label'		=> 'Единицы измерения'
					)),
					'value'		=>  Jelly::field('String', array('label' => 'Значение')),
					'szr'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_szr',
							'column'	=> 'glossary_szr_id',
							'label'		=> 'СЗР'
					)),
			));
	}



	public function updateRecords($szr_id, $dvs, $license_id){
		$updated_ids = array();
		foreach ($dvs as $dv) {
			if(isset($dv['id'])){
				$model = Jelly::select('glossary_szr_szrdv', (int)$dv['id']);
			}else{
				$model = Jelly::factory('glossary_szr_szrdv');
				$model->dv = (int)$dv['dv'];
			}

			$model->value = $dv['value'];
			$model->units = $dv['units'];
			$model->szr = $szr_id;
			$model->license = $license_id;
			$model->update_date = time();
			$model->save();

			$updated_ids[] = $model->id();
		}

		if($updated_ids)Jelly::delete('glossary_szr_szrdv')->where('szr', '=', $szr_id)->and_where('_id', 'NOT IN', $updated_ids)->execute();
		else Jelly::delete('glossary_szr_szrdv')->where('szr', '=', $szr_id)->execute();
	}

}

