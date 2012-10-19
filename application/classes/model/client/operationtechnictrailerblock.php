<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_OperationTechnicTrailerBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operationtechnic_trailer')
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
                
                'technic_trailer'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_techniquetrailer',
                        'column'	=> 'technic_trailer_id',
                        'label'		=> 'Подвижной состав состав'
                )),
				
				
				'in_main' => Jelly::field('Boolean', array('label' => 'Присутствует также в основном окне'))
		));
	}
	

}