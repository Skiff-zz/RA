<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_PlannedOrderFieldTechnicTrailerBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_plannedorderfieldtechnic_trailer')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'planned_order' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_plannedorder',
                        'column'	=> 'client_work_plannedorder_id',
                        'label'		=> 'Плановый наряд'
                )),	
                
                'planned_order_field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_plannedorder2field',
                        'column'	=> 'client_work_plannedorder2field_id',
                        'label'		=> 'Поле наряда'
                )),
				
				'planned_order_field_technic'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_work_plannedorderfield2technic',
                        'column'	=> 'client_work_plannedorderfield2technic_id',
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

