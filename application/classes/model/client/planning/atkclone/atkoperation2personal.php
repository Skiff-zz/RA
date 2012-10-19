<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_AtkOperation2Personal extends Jelly_Model
{


    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkcloneoperation2personal')
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

                'personal'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personalgroup',
                        'column'	=> 'personal_id',
                        'label'		=> 'Персонал'
                )),

				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
                'personal_count' => Jelly::field('Integer', array('label' => 'К-во персонала')),

                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты'))
		));
	}
}

