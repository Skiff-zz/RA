<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_OperationTechnicAggregateBlock extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operationtechnic_aggregate')
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
				
				'technic_mobile'	=> Jelly::field('Integer',array('label'	=> 'Подвижной состав состав')),
                'technic_trailer'	=> Jelly::field('Integer',array('label'	=> 'Прицепной состав состав')),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				'color' => Jelly::field('String', array('label' => 'Цвет')),
				
				
				'fuel_work' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'fuel_work_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'cons_norm_units_id',
                        'label'		=> 'Единицы измерения'
                )),
                                
				'gsm' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_gsm',
                        'column'	=> 'glossary_gsm_id',
                        'label'		=> 'ГСМ'
                )),
				
				//для второй таблицы
				'fuel_work_secondary' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'fuel_work_units_secondary'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'cons_norm_units_secondary_id',
                        'label'		=> 'Единицы измерения'
                )),
				
				'in_main' => Jelly::field('Boolean', array('label' => 'Присутствует также в основном окне')),
                'checked' => Jelly::field('Boolean', array('label' => 'Отмечено в основном окне'))
		));
	}
	
	
	public function save_aggregates($second_grid_items, $main_grid_items, $operation_id, $operation_technics_id){
		$do_not_delete = array();
		
		foreach($second_grid_items as $second_grid_item){
			$ids = explode('-', $second_grid_item['id']);
			$mobile_id = (int)substr($ids[0], 1);
			$trailer_id = (int)substr($ids[1], 1);
			
			$item = Jelly::select('client_operationtechnicaggregateblock')->where('operation_technic', '=', $operation_technics_id)
																		  ->and_where('technic_mobile', '=', $mobile_id)
																		  ->and_where('technic_trailer', '=', $trailer_id)->limit(1)->load();
			if(!$item instanceof Jelly_Model || !$item->loaded()){
				$item = Jelly::factory('client_operationtechnicaggregateblock');
			}
			
			$item->operation = $operation_id;
			$item->operation_technic = $operation_technics_id;
			$item->technic_mobile = $mobile_id;
			$item->technic_trailer = $trailer_id;
			$item->title = htmlspecialchars_decode(isset($second_grid_item['clear_title']) ? strip_tags($second_grid_item['clear_title']) : strip_tags($second_grid_item['title']));
			$item->color = $second_grid_item['color'];
			
			$in_main = $this->is_in_main($main_grid_items, $mobile_id, $trailer_id);
			
			$item->fuel_work = $in_main ? $in_main['gsm_norm'] : 0;
			$item->fuel_work_units = $in_main ? $in_main['gsm_norm_units'] : 0;
			$item->gsm = $in_main && isset($in_main['gsm_id']) ? $in_main['gsm_id'] : 0;
			$item->fuel_work_secondary = $second_grid_item['extras']['gsm_norm'];
			$item->fuel_work_units_secondary = $second_grid_item['extras']['gsm_norm_units'];
			
			
			$item->in_main = !!$in_main;
			$item->checked = $in_main ? $in_main['checked'] : false;
			$item->save();
			
			$do_not_delete[] = $item->id();
		}
		
		if(count($do_not_delete)) Jelly::delete('client_operationtechnicaggregateblock')->where('operation_technic', '=', $operation_technics_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
		else					  Jelly::delete('client_operationtechnicaggregateblock')->where('operation_technic', '=', $operation_technics_id)->execute();
	}
	
	
	public function is_in_main($main_grid_items, $mobile_id, $trailer_id){
		$in_main = false;
		foreach($main_grid_items as $main_grid_item){
			if($main_grid_item['id']=='n'.$mobile_id.'-n'.$trailer_id) $in_main = $main_grid_item;
		}
		return $in_main;
	}

}