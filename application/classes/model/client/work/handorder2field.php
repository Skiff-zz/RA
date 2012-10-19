<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrder2Field extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_handorder2field')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'hand_order' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorder',
                        'column'	=> 'client_work_handorder_id',
                        'label'		=> 'Плановый наряд'
                )),	
                
                'field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'field',
                        'column'	=> 'field_id',
                        'label'		=> 'Поле'
                )),
				
				'order_status' => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_work_orderstatus',
					'column'	=> 'status_id',
					'label'		=> 'Статус Наряда'
                )),
				
				'order_date' =>  Jelly::field('Integer', array('label' => 'Дата наряда')),
				
				'planned_from_date' =>  Jelly::field('Integer', array('label' => 'Плановая дата начала')),
				'planned_to_date' =>  Jelly::field('Integer', array('label' => 'Плановая дата конца')),
				
				'actual_from_date' =>  Jelly::field('Integer', array('label' => 'Фактическая дата начала')),
                'actual_to_date' =>  Jelly::field('Integer', array('label' => 'Фактическая дата конца')),
				
				'executor'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personal',
                        'column'	=> 'personal_id',
                        'label'		=> 'Исполнитель'
                )),

                

				'process_square' => Jelly::field('String', array('label' => 'Площадь к обработке, га')),
				'processed_square' => Jelly::field('String', array('label' => 'Обработанно, га')),

				'plan_inputs' => Jelly::field('String', array('label' => 'Плановые затраты, грн')),
                'actual_inputs' => Jelly::field('String', array('label' => 'Фактические затраты, грн')),
				
				'materials' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfield2material',
                    'label'	=> 'Материалы',
                )),

                'technics' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfield2technic',
                    'label'	=> 'Техника',
                )),

                'personal' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfield2personal',
                    'label'	=> 'Персонал',
                ))
		));
	}

}

