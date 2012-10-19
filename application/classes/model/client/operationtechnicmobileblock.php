<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_OperationTechnicMobileBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operationtechnic_mobile')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),	

				'operation_technic'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operations2technics',
                        'column'	=> 'client_operations2technics_id',
                        'label'		=> 'Блок техники'
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

