<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkOperationTechnicMobileBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkoperationtechnic_mobile')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),	
                
                'atk_operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk2operation',
                        'column'	=> 'client_planning_atk2operation_id',
                        'label'		=> 'АТК операция'
                )),
				
				'atk_operation_technic'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkoperation2technic',
                        'column'	=> 'client_planning_atkoperation2technic_id',
                        'label'		=> 'АТК операция - блок техники'
                )),	
                
                'technic_mobile'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_techniquemobile',
                        'column'	=> 'technic_mobile_id',
                        'label'		=> 'Подвижной состав состав'
                )),

				'in_main' => Jelly::field('Boolean', array('label' => 'Присутствует также в основном окне'))
		));
	}
	

}

