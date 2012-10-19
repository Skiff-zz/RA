<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_AtkOperationTechnicAggregateBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkcloneoperationtechnic_aggregate')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk',
                        'column'	=> 'client_planning_atkclone_atk_id',
                        'label'		=> 'АТК'
                )),	
                
                'atk_operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk2operation',
                        'column'	=> 'client_planning_atkclone_atk2operation_id',
                        'label'		=> 'АТК операция'
                )),
				
				'atk_operation_technic'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atkoperation2technic',
                        'column'	=> 'client_planning_atkclone_atkoperation2technic_id',
                        'label'		=> 'АТК операция - блок техники'
                )),	
				
				'technic_mobile'	=> Jelly::field('Integer',array('label'	=> 'Подвижной состав состав')),
                'technic_trailer'	=> Jelly::field('Integer',array('label'	=> 'Прицепной состав состав')),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				'color' => Jelly::field('String', array('label' => 'Цвет')),
				
				
				'fuel_work' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'fuel_work_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'cons_norm_units_id',
                        'label'		=> 'Единицы измерения'
                )),
                                
				'gsm' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_gsm',
                        'column'	=> 'glossary_gsm_id',
                        'label'		=> 'ГСМ'
                )),
				
				//для второй таблицы
				'fuel_work_secondary' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'fuel_work_units_secondary'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'cons_norm_units_secondary_id',
                        'label'		=> 'Единицы измерения'
                )),
				
				'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты')),
				
				'in_main' => Jelly::field('Boolean', array('label' => 'Присутствует также в основном окне')),
                'checked' => Jelly::field('Boolean', array('label' => 'Отмечено в основном окне'))
		));
	}
}