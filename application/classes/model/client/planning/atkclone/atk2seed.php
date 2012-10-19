<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_Atk2Seed extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkclone2seed')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk',
                        'column'	=> 'client_planning_atkclone_atk_id',
                        'label'		=> 'АТК'
                )),	
				
				'seed'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_seed',
                        'column'	=> 'glossary_seed_id',
                        'label'		=> 'Семена'
                )),
				
				'productions' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atkseed2production',
                    'label'	=> 'Продукция',
                )),
				
				
				//ИННОВАЦИЯ
				'bio_crop' => Jelly::field('String', array('label' => 'Биологическая урожайность')),
				'bio_crop_units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'units_id',
                        'label'		=> 'Единицы измерения'
                )),
				
                'calc_crop_percent' => Jelly::field('String', array('label' => 'Расчётная урожайность, %')),
                'calc_crop' => Jelly::field('String', array('label' => 'Расчётная урожайность')),
				'price' => Jelly::field('String', array('label' => 'Цена')),
				
				'disabled' => Jelly::field('Boolean', array('label' => 'Включен')),
				'operation_id' => Jelly::field('Integer', array('label' => 'ИД операции'))
                
		));
	}

}

