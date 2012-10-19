<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_AtkSeed2Production extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkcloneseed2production')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk',
                        'column'	=> 'client_planning_atkclone_atk_id',
                        'label'		=> 'АТК'
                )),	
				
				'atk_seed'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk2seed',
                        'column'	=> 'client_planning_atkclone_atk2seed_id',
                        'label'		=> 'АТК Семена'
                )),

                
                'bio_crop' => Jelly::field('String', array('label' => 'Биологическая урожайность')),
				'bio_crop_units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'units_id',
                        'label'		=> 'Единицы измерения'
                )),
				
                'calc_crop_percent' => Jelly::field('String', array('label' => 'Расчётная урожайность, %')),
                'calc_crop' => Jelly::field('String', array('label' => 'Расчётная урожайность')),
                
                'production'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_production',
                        'column'	=> 'glossary_production_id',
                        'label'		=> 'Продукция'
                )),
                
                'productionclass'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_productionclass',
                        'column'	=> 'glossary_productionclass_id',
                        'label'		=> 'Класс продукции'
                )),

                'price' => Jelly::field('String', array('label' => 'Цена'))
		));
	}
    
}

