<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrderFieldTechnicTrailerBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_handorderfieldtechnic_trailer')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'hand_order' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorder',
                        'column'	=> 'client_work_handorder_id',
                        'label'		=> 'Плановый наряд'
                )),	
                
                'hand_order_field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorder2field',
                        'column'	=> 'client_work_handorder2field_id',
                        'label'		=> 'Поле наряда'
                )),
				
				'hand_order_field_technic'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_handorderfield2technic',
                        'column'	=> 'client_work_handorderfield2technic_id',
                        'label'		=> 'Техника наряда в поле'
                )),
                
                'technic_trailer'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_techniquetrailer',
                        'column'	=> 'technic_trailer_id',
                        'label'		=> 'Прицепной состав состав'
                )),

				
				'in_main' => Jelly::field('Boolean', array('label' => 'Присутствует также в основном окне'))
		));
	}
	

}

