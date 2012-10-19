<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_Atk2Operation extends Jelly_Model
{


    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkclone2operation')
			->fields(array(
				'_id' 			=> new Field_Primary,

                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk',
                        'column'	=> 'client_planning_atkclone_atk_id',
                        'label'		=> 'АТК'
                )),

                'number' => Jelly::field('String', array('label' => 'Номер')),

				'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),

                'checked' => Jelly::field('Boolean', array('label' => 'Включен')),

                'from_date' =>  Jelly::field('Integer', array('label' => 'Дата начала')),
                'to_date' =>  Jelly::field('Integer', array('label' => 'Дата конца')),

                'processing_koef' => Jelly::field('String', array('label' => 'Коэф. обработки')),
                'crop_lost' => Jelly::field('String', array('label' => 'Потеря урожайности')),

                'materials_costs' => Jelly::field('String', array('label' => 'Затраты на материалы')),
                'technics_costs' => Jelly::field('String', array('label' => 'Затраты на технику')),
                'personal_costs' => Jelly::field('String', array('label' => 'Затраты на персонал')),
                'total_costs' => Jelly::field('String', array('label' => 'Затраты полные')),
				
				'profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
				'rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),

                'materials' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperation2material',
                    'label'	=> 'Материалы',
                )),

                'technics' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperation2technic',
                    'label'	=> 'Техника',
                )),

                'personal' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkoperation2personal',
                    'label'	=> 'Персонал',
                ))
		));
	}

}

