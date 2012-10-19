<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_TechTrailer extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_techtrailer', $group_model = 'glossary_techtrailergroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
				'producer' => Jelly::field('ManyToMany',array(
						'foreign'	=>'client_producer',
						'label'		=> 'Производитель',
						'column'	=> 'producer',
						'through'   => array('model'=>'tr2producer','columns'=>array('ttrail_id','producer_id'))
				)),
				'gsm'	    => Jelly::field('ManyToMany',array(
							'foreign'	=> 'glossary_gsm',
							'label'		=> 'ГСМ',
							'through'   => array('model'=>'gsm2trgl','columns'=>array('ttrail_id','glossary_gsm_id'))
				)),
				'grasp_width' => Jelly::field('String', array('label' => 'Ширина захвата')),
				'grasp_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'grasp_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'productivity' => Jelly::field('String', array('label' => 'Производительность')),
				'productivity_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'productivity_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'fuel_work' => Jelly::field('String', array('label' => 'Расход топлива (работа)')),
				'fuel_work_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fuel_work_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'fuel_idle' => Jelly::field('String', array('label' => 'Расход топлива (холостой ход)')),
				'fuel_idle_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fuel_idle_units_id',
							'label'		=> 'Единицы измерения'
					)),


			 ));
	}

}

