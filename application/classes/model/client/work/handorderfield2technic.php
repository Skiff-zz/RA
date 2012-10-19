<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrderField2Technic extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('work_handorderfield2technic')
			->fields(array(
				'_id' 			=> new Field_Primary,
				
				'native' 		=> Jelly::field('Boolean', array('label' => 'Из атк или добавлен вручную')),
                
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
                
				'mobile_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfieldtechnicmobileblock',
                    'label'	=> 'Блок подвижного состава',
                )),
				
				'trailer_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfieldtechnictrailerblock',
                    'label'	=> 'Блок прицепного состава',
                )),
				
				'aggregates_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_handorderfieldtechnicaggregateblock',
                    'label'	=> 'Блок агрегатов',
                )),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				
				'planned_fuel_work' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'planned_fuel_work_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'planned_fuel_work_units_id',
                        'label'		=> 'Единицы измерения'
                )),
				
				'actual_fuel_work' =>  Jelly::field('String', array('label' => 'Расход топлива')),
                'actual_fuel_work_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'actual_fuel_work_units_id',
                        'label'		=> 'Единицы измерения'
                )),
				
				'planned_debit' => Jelly::field('String', array('label' => 'К списанию (план)')),
                'actual_debit' => Jelly::field('String', array('label' => 'Списано (факт)')),
//				'debit_diff' => Jelly::field('String', array('label' => 'Разница')),
				
				'planned_price' => Jelly::field('String', array('label' => 'Цена')),
				'actual_price' => Jelly::field('String', array('label' => 'Цена')),
				
                'planned_total' => Jelly::field('String', array('label' => 'Затраты (план)')),
				'actual_total' => Jelly::field('String', array('label' => 'Затраты (факт)'))
//				'total_diff' => Jelly::field('String', array('label' => 'Затраты (разница)'))
				
		));
	}
	
	
	
	public function save_from_grid($technics_data, $order_id, $field_id){
		$do_not_delete = array(); //ид записей, которые не надо удалять
        $data = array();

		for($i=0; $i<count($technics_data); $i++){
			$tech = $technics_data[$i];

			if($tech['technic']['id']=='new'){
				$technic_row = Jelly::factory('client_work_handorderfield2technic');
			}else{
				$technic_row = Jelly::select('client_work_handorderfield2technic', (int)$tech['technic']['id']);
				if(!$technic_row instanceof Jelly_Model || !$technic_row->loaded()) $technic_row = Jelly::factory('client_work_handorderfield2technic');
			}

			$technic_row->hand_order = $order_id;
			$technic_row->hand_order_field = $field_id;
			$technic_row->title = $tech['technic']['value']['value'];
			$technic_row->planned_fuel_work = (float)$tech['planned_fuel_work'];
			$technic_row->planned_fuel_work_units = (int)$tech['planned_fuel_work_units'];
			$technic_row->actual_fuel_work = (float)$tech['actual_fuel_work'];
			$technic_row->actual_fuel_work_units = (int)$tech['actual_fuel_work_units'];
			$technic_row->planned_price = (float)$tech['planned_price'];
			$technic_row->actual_price = (float)$tech['actual_price'];
			$technic_row->planned_debit = (float)$tech['planned_debit'];
			$technic_row->actual_debit = (float)$tech['actual_debit'];
			$technic_row->planned_total = (float)$tech['planned_total'];
			$technic_row->actual_total = (float)$tech['actual_total'];
			$technic_row->native = (bool)$tech['technic']['isNative'];
			$technic_row->save();

			$do_not_delete[] = $technic_row->id();

			$this->save_technics($tech['technic']['all2']['mobile'], $tech['technic']['all']['mobile'], $order_id, $field_id, $technic_row->id(), 'mobile');
			$this->save_technics($tech['technic']['all2']['trailer'], $tech['technic']['all']['trailer'], $order_id, $field_id, $technic_row->id(), 'trailer');
			Jelly::factory('client_work_handorderfieldtechnicaggregateblock')->save_aggregates($tech['technic']['all2']['aggregates'], $tech['technic']['all']['aggregates'], $order_id, $field_id, $technic_row->id());
		}


		if(count($do_not_delete)){
			Jelly::delete('client_work_handorderfield2technic')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_work_handorderfieldtechnicmobileblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->and_where('hand_order_field_technic', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_work_handorderfieldtechnictrailerblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->and_where('hand_order_field_technic', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_work_handorderfieldtechnicaggregateblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->and_where('hand_order_field_technic', 'NOT IN', $do_not_delete)->execute();
		}else{
			Jelly::delete('client_work_handorderfield2technic')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
			Jelly::delete('client_work_handorderfieldtechnicmobileblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
			Jelly::delete('client_work_handorderfieldtechnictrailerblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
			Jelly::delete('client_work_handorderfieldtechnicaggregateblock')->where('hand_order', '=', $order_id)->where('hand_order_field', '=', $field_id)->execute();
		}

    }
	
	
	
	public function save_technics($second_grid_items, $main_grid_items, $order_id, $field_id, $hand_order_field_technic_id, $type){
		$do_not_delete = array();
		
		foreach($second_grid_items as $second_grid_item){
			if(substr($second_grid_item['id'], 0, 1)=='g')continue;
			$tech_id = substr($second_grid_item['id'], 1);
			
			$item = Jelly::select('client_work_handorderfieldtechnic'.$type.'block')->where('hand_order_field_technic', '=', $hand_order_field_technic_id)->and_where('technic_'.$type, '=', $tech_id)->limit(1)->load();
			if(!$item instanceof Jelly_Model || !$item->loaded()){
				$item = Jelly::factory('client_work_handorderfieldtechnic'.$type.'block');
			}
			
			$item->hand_order = $order_id;
			$item->hand_order_field = $field_id;
			$item->hand_order_field_technic = $hand_order_field_technic_id;
			if($type=='mobile')$item->technic_mobile = $tech_id;
			else			   $item->technic_trailer = $tech_id;
			$item->in_main = $this->is_in_main($main_grid_items, $tech_id);
			$item->save();
			
			$do_not_delete[] = $item->id();
		}
		
		if(count($do_not_delete)) Jelly::delete('client_work_handorderfieldtechnic'.$type.'block')->where('hand_order_field_technic', '=', $hand_order_field_technic_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
		else					  Jelly::delete('client_work_handorderfieldtechnic'.$type.'block')->where('hand_order_field_technic', '=', $hand_order_field_technic_id)->execute();
	}
	
	
	public function is_in_main($main_grid_items, $tech_id){
		$in_main = false;
		foreach($main_grid_items as $main_grid_item){
			if(substr($main_grid_item['id'], 0, 1)=='g')continue;
			if(substr($main_grid_item['id'], 1)==$tech_id) $in_main = true;
		}
		return $in_main;
	}
	
	
	
////////////////////////////////////////////////////////////////////////////////////ФОРММИРОВАНИЕ МАССИВА ДЛЯ ИНТЕРФЕЙСА///////////////////////////////////////////////////////////////////////////////////
	public function prepare_technics($technics){

		foreach($technics as &$technic){
			$all = array('mobile'=>array(), 'trailer'=>array(), 'aggregates'=>array());  //основная таблица
			$all2 = array('mobile'=>array(), 'trailer'=>array(), 'aggregates'=>array()); //второстепенная таблица
			
			$mobiles    = Jelly::select('client_work_handorderfieldtechnicmobileblock')->with('technic_mobile')->where('hand_order_field_technic', '=', $technic['_id'])->execute()->as_array();
			$trailers   = Jelly::select('client_work_handorderfieldtechnictrailerblock')->with('technic_trailer')->where('hand_order_field_technic', '=', $technic['_id'])->execute()->as_array();
			$aggregates = Jelly::select('client_work_handorderfieldtechnicaggregateblock')->with('gsm')->where('hand_order_field_technic', '=', $technic['_id'])->execute()->as_array();
			
			
			$all['mobile']     = Jelly::factory('client_operations2technics')->build_tree($mobiles, 'mobile', true);
			$all['trailer']    = Jelly::factory('client_operations2technics')->build_tree($trailers, 'trailer', true);
			$all['aggregates'] = $this->build_main_aggregates($aggregates);
			
			$all2['mobile']     = Jelly::factory('client_operations2technics')->build_tree($mobiles, 'mobile', false);
			$all2['trailer']    = Jelly::factory('client_operations2technics')->build_tree($trailers, 'trailer', false);
			$all2['aggregates'] = Jelly::factory('client_operations2technics')->build_secondary_aggregates($aggregates);
			
			
			$technic['all'] = json_encode($all);
			$technic['all2'] = json_encode($all2);
			
			$technic['planned_fuel_work_units'] = Jelly::select('glossary_units', (int)$technic['planned_fuel_work_units']);
		}

		return $technics;
	}
	

	public function build_main_aggregates($aggregates){
		$result = array();
		foreach($aggregates as $aggregate){
			if(!$aggregate['in_main'])continue;
			$mobile_title = explode('+', $aggregate['title']);
			$mobile_title = trim($mobile_title[0]);
			
			$result[] = array(
				'id' => 'n'.((int)$aggregate['technic_mobile']).'-n'.((int)$aggregate['technic_trailer']),
				'title' => htmlspecialchars($aggregate['title']),
				'color' => $aggregate['color'],
				'mobile_title' => htmlspecialchars($mobile_title),
				'gsm_id' => $aggregate[':gsm:_id'],
				'gsm_name' => $aggregate[':gsm:name'],
				'gsm_norm' => $aggregate['fuel_work'],
				'gsm_norm_units' => $aggregate['fuel_work_units'],
				'price' => $aggregate['price'],
				'total' => $aggregate['total'],
				'checked' => $aggregate['checked']
			);
		}
		return $result;
	}
	

}

