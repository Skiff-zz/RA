<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_FertilizerCulture extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_fertilizer2culture')
			->fields(array(
					'_id'			=> Jelly::field('Primary'),
					'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
					'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
						'rules' => array(
							'not_empty' => NULL
						))
					),

					'deployment_type' => Jelly::field('ManyToMany',array(
							'foreign'	=>'glossary_fertilizer_deploymenttype',
							'label'		=> 'Способ внесения',
							'through'   => array('model'=>'fertc2dt','columns'=>array('fertc_id','dept_id'))
					)),

					'fertilizer_crop_norm_min' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР мин.')),
					'fertilizer_crop_norm_mid' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР средн.')),
					'fertilizer_crop_norm_max' =>  Jelly::field('String', array('label' => 'Норма внесения СЗР макс.')),
					'fertilizer_units' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fertilizer_units_id',
							'label'		=> 'Единицы измерения'
					)),

					'mixture_norm_min' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси мин.')),
					'mixture_norm_mid' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси средн.')),
					'mixture_norm_max' =>  Jelly::field('String', array('label' => 'Норма внесения рабочей смеси макс.')),
					'mixture_units' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'mixture_units_id',
							'label'		=> 'Единицы измерения'
					)),
					'fertilizer_max_kratn_obrab' =>  Jelly::field('String', array('label' => 'Макс. кратн. обраб.')),
					'fertilizer_processing_deadline' =>  Jelly::field('String', array('label' => 'Последний срок обработки (дней до сборки урожая)')),

					'culture' => Jelly::field('ManyToMany',array(
							'foreign'	=> 'glossary_culture',
							'label'		=> 'Культура',
							'through'   => array('model'=>'cult2fertcult','columns'=>array('fertculture_id','culture_id'))
					)),

					'fertilizer'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_fertilizer',
							'column'	=> 'glossary_fertilizer_id',
							'label'		=> 'Удобрение'
					))
			));
	}

	public function updateRecords($fertilizer_id, $cultures, $license_id){
		$updated_ids = array();
		foreach ($cultures as $culture) {
			if(isset($culture['id'])){
				$model = Jelly::select('glossary_fertilizerculture', (int)$culture['id']);
			}else{
				$model = Jelly::factory('glossary_fertilizerculture');
//				$model->culture = (int)$culture['culture'];
			}
			$model->glossary_fertilizer = (int)$fertilizer_id;
			$model->fertilizer = (int)$fertilizer_id;
			$dep_types = $culture['deployment_types'] ? explode(',',$culture['deployment_types']) : array();
			for($i=0;$i<count($dep_types);$i++){
				$dep_types[$i] = (int)$dep_types[$i];
			}
			$change = array(
				'deleted' => 0,
				'update_date' => time(),
				'deployment_type' => $dep_types,
				'fertilizer_crop_norm_min' => $culture['fertilizer_crop_norm_min'],
				'fertilizer_crop_norm_mid' => $culture['fertilizer_crop_norm_mid'],
				'fertilizer_crop_norm_max' => $culture['fertilizer_crop_norm_max'],
				'fertilizer_units' => (int)$culture['fertilizer_units'],
				'fertilizer_max_kratn_obrab' => $culture['fertilizer_max_kratn_obrab'],
				'fertilizer_processing_deadline' => $culture['fertilizer_processing_deadline'],
				'mixture_norm_min' => 0,
				'mixture_norm_mid' => 0,
				'mixture_norm_max' => 0,
				'mixture_units' => 5,
				'culture' => $culture['cultures']->selectedUnits ? explode(',',$culture['cultures']->selectedUnits) : NULL,
				'fertilizer' => (int)$fertilizer_id

			);

			$model->set($change)->save();

			$updated_ids[] = (int)($model->id());
		}
		if($updated_ids)Jelly::delete('glossary_fertilizerculture')->where('fertilizer', '=', (int)$fertilizer_id)->and_where('_id', 'NOT IN', $updated_ids)->execute();
		else Jelly::delete('glossary_fertilizerculture')			->where('fertilizer', '=', (int)$fertilizer_id)->execute();
	}


}

