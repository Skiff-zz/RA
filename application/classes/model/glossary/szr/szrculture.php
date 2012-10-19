<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Szr_SzrCulture extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_szr2culture')
			->fields(array(
					'_id'			=> Jelly::field('Primary'),
					'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
					'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
						'rules' => array(
							'not_empty' => NULL
						))
					),
					'deployment_type' => Jelly::field('ManyToMany',array(
							'foreign'	=>'glossary_szr_deploymenttype',
							'label'		=> 'Способ внесения',
							'through'   => array('model'=>'dept2szrcult','columns'=>array('szrculture_id','dept_id'))
					)),

					'szr_crop_norm_min' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР мин.')),
					'szr_crop_norm_mid' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР средн.')),
					'szr_crop_norm_max' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР макс.')),
					'szr_units' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'szr_units_id',
							'label'		=> 'Единицы измерения'
					)),
					'szr_processing_deadline' =>  Jelly::field('String', array('label' => 'Последний срок обработки (дней до сборки урожая)')),
					'szr_max_kratn_obrab' =>  Jelly::field('String', array('label' => 'Макс. кратн. обраб.')),
					'mixture_norm_min' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси мин.')),
					'mixture_norm_mid' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси средн.')),
					'mixture_norm_max' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси макс.')),
					'mixture_units' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'mixture_units_id',
							'label'		=> 'Единицы измерения'
					)),

					'culture' => Jelly::field('ManyToMany',array(
							'foreign'	=> 'glossary_culture',
							'label'		=> 'Культура',
							'through'   => array('model'=>'cult2szrcult','columns'=>array('szrculture_id','culture_id'))
					)),

					'targets'      => Jelly::field('ManyToMany',array(
						'foreign'	=> 'glossary_szr_target',
						'label'	=> 'Целевые объекты',
						'through'   => 'szr2cult_target'
					)),

					'szr'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_szr',
							'column'	=> 'glossary_szr_id',
							'label'		=> 'СЗР'
					))
			));
	}


	public function updateRecords($szr_id, $cultures, $license_id){
		$updated_ids = array();
		foreach ($cultures as $culture) {
			if(isset($culture['id'])){
				$model = Jelly::select('glossary_szr_szrculture', (int)$culture['id']);
			}else{
				$model = Jelly::factory('glossary_szr_szrculture');
//				$model->culture = (int)$culture['culture'];
			}
			$model->glossary_szr = (int)$szr_id;
			$model->szr = (int)$szr_id;
			$dep_types = $culture['deployment_types'] ? explode(',',$culture['deployment_types']) : array();
			for($i=0;$i<count($dep_types);$i++){
				$dep_types[$i] = (int)$dep_types[$i];
			}
			$change = array(
				'deleted' => 0,
				'update_date' => time(),
				'deployment_type' => $dep_types,
				'szr_crop_norm_min' => $culture['szr_crop_norm_min'],
				'szr_crop_norm_mid' => $culture['szr_crop_norm_mid'],
				'szr_crop_norm_max' => $culture['szr_crop_norm_max'],
				'szr_units' => (int)$culture['szr_units'],
				'szr_max_kratn_obrab' => $culture['szr_max_kratn_obrab'],
				'szr_processing_deadline' => $culture['szr_processing_deadline'],
				'mixture_norm_min' => 0,
				'mixture_norm_mid' => 0,
				'mixture_norm_max' => 0,
				'mixture_units' => 5,
				'culture' => $culture['cultures']->selectedUnits ? explode(',',$culture['cultures']->selectedUnits) : array(),
				'targets' => $culture['targets']->selectedUnits ? explode(',',$culture['targets']->selectedUnits) : array(),
				'szr' => (int)$szr_id

			);

			$model->set($change)->save();

			$updated_ids[] = (int)($model->id());
		}
		if($updated_ids)Jelly::delete('glossary_szr_szrculture')->where('szr', '=', (int)$szr_id)->and_where('_id', 'NOT IN', $updated_ids)->execute();
		else Jelly::delete('glossary_szr_szrculture')			->where('szr', '=', (int)$szr_id)->execute();
	}


}

