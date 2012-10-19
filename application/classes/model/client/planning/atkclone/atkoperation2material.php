<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_AtkOperation2Material extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkcloneoperation2material')
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
				
				'material_model' => Jelly::field('String', array('label' => 'Модель материала')),
                'material_id' => Jelly::field('Integer', array('label' => 'ИД материала')),
				
				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
				'crop_lost' => Jelly::field('String', array('label' => 'Потеря урожайности')),
                
                'crop_norm' => Jelly::field('String', array('label' => 'Норма внесения')),
                'units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'units_id',
                        'label'		=> 'Единицы измерения'
                )),
                
                'count' => Jelly::field('String', array('label' => 'Необходимое количество')),
                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты')),
				
				'profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
				'rentability' => Jelly::field('String', array('label' => 'Рентабельность, %'))
		));
	}

}

