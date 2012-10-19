<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_AtkOperation2Technic extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkcloneoperation2technic')
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
                
				'mobile_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperationtechnicmobileblock',
                    'label'	=> 'Блок подвижного состава',
                )),
				
				'trailer_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperationtechnictrailerblock',
                    'label'	=> 'Блок прицепного состава',
                )),
				
				'aggregates_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperationtechnicaggregateblock',
                    'label'	=> 'Блок агрегатов',
                )),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
                          
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

                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты'))
		));
	}
  
}

