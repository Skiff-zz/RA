<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkOperation2Technic extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkoperation2technic')
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
                
				'mobile_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperationtechnicmobileblock',
                    'label'	=> 'Блок подвижного состава',
                )),
				
				'trailer_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperationtechnictrailerblock',
                    'label'	=> 'Блок прицепного состава',
                )),
				
				'aggregates_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperationtechnicaggregateblock',
                    'label'	=> 'Блок агрегатов',
                )),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
                          
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

                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты'))
		));
	}
    
    public function save_from_grid($technics, $atk_id, $is_version = false){
//        $data = array();
//        foreach($technics as $operation){ 
//            if(!isset($operation['tech_mobile']) || !count($operation['tech_mobile']) || !isset($operation['tech_mobile'][0]['id']) || !$operation['tech_mobile'][0]['id'])continue;
//            
//            for($i=0; $i<count($operation['tech_mobile']); $i++){
//                $data[] = array(
//                    //'id' => $material['rowId'],
//                    'atk' => $atk_id,
//                    'atk_operation' => $operation['atk_operation'],
//                    'technic_trailer' => (int)(isset($operation['tech_trailer'][$i]['id']) ? $operation['tech_trailer'][$i]['id'] : 0),
//                    'technic_mobile' => (int)(isset($operation['tech_mobile'][$i]['id']) ? $operation['tech_mobile'][$i]['id'] : 0),
//                    'technic_trailer_count' => (int)$operation['tech_trailer_count'][$i],
//                    'technic_mobile_count' => (int)$operation['tech_mobile_count'][$i],
//                    'fuel_work' => (float)$operation['fuel_work'][$i],
//                    'fuel_work_units' => (int)$operation['fuel_work_units'][$i],
//                    'gsm' => (int)(isset($operation['tech_mobile'][$i]['id']) ? $operation['tech_mobile'][$i]['id'] : 0),
//                    'price' => (float)$operation['price'][$i]>0 ? (float)$operation['price'][$i] : 0,
//                    'total' => (float)$operation['total'][$i]>0 ? (float)$operation['total'][$i] : 0
//                );
//                
//                /** Обратная связь **/
//                $op = Jelly::select('client_planning_atk2operation', (int)$operation['atk_operation']);
//                
//                if($op instanceof Jelly_Model and $op->loaded() and $op->operation->id())
//                {
//                	$op_test = Jelly::select('client_operations2technics')
//								->where('operation', '=', $op->operation->id())
//								->where('technic_trailer', '=', (int)(isset($operation['tech_trailer'][$i]['id']) ? $operation['tech_trailer'][$i]['id'] : 0))
//								->where('technic_mobile', '=', (int)(isset($operation['tech_mobile'][$i]['id']) ? $operation['tech_mobile'][$i]['id'] : 0))
//					->load();
//                	
//                	if(!($op_test instanceof Jelly_Model) or !$op_test->loaded())
//                	{
//               				$n = Jelly::factory('client_operations2technics');
//	               			
//	               			$n->operation 		= $op->operation->id();
//	               			$n->technic_trailer	= (int)(isset($operation['tech_trailer'][$i]['id']) ? $operation['tech_trailer'][$i]['id'] : 0);
//	               			$n->technic_mobile	= (int)(isset($operation['tech_mobile'][$i]['id']) ? $operation['tech_mobile'][$i]['id'] : 0);
//	               			
//	               			$n->save();
//	               			
//	               			unset($n);
// 					}
//                }
//            }
//        }
//        
//        Jelly::delete('Client_Planning_AtkOperation2Technic')->where('atk', '=', $atk_id)->execute();
//        
//        foreach($data as $item){
//            $model = Jelly::factory('Client_Planning_AtkOperation2Technic');
//            $model->set($item);
//            $model->save();
//        }
		
		$do_not_delete = array(); //ид записей, которые не надо удалять
        $data = array();

        foreach($technics as $operation){ //$tech - одна строка из таблицы в интерфейсе
			
			if(isset($operation['technic'])){
				for($i=0; $i<count($operation['technic']); $i++){
					$tech = $operation['technic'][$i];

					if($tech['id']=='new' || $is_version){
						$technic_row = Jelly::factory('client_planning_atkoperation2technic');
					}else{
						$technic_row = Jelly::select('client_planning_atkoperation2technic', (int)$tech['id']);
						if(!$technic_row instanceof Jelly_Model || !$technic_row->loaded()) $technic_row = Jelly::factory('client_planning_atkoperation2technic');
					}

					$technic_row->atk = $atk_id;
					$technic_row->atk_operation = $operation['atk_operation'];
					$technic_row->title = $tech['value'];
					$technic_row->checked = $tech['checked'];
					$technic_row->gsm = isset($operation['gsm'][$i]['id']) ? (int)$operation['gsm'][$i]['id'] : 0;
					$technic_row->fuel_work = $operation['gsm_norm'][$i];
					$technic_row->fuel_work_units = isset($operation['gsm_norm_units'][$i]['id']) ? (int)$operation['gsm_norm_units'][$i]['id'] : 21;
					$technic_row->price = ((float)$operation['price'][$i])+0;
					$technic_row->total = ((float)$operation['total'][$i])+0;
					$technic_row->save();

					$do_not_delete[] = $technic_row->id();

					$this->save_technics($tech['all2']['mobile'], $tech['all']['mobile'], $atk_id, $operation['atk_operation'], $technic_row->id(), 'mobile');
					$this->save_technics($tech['all2']['trailer'], $tech['all']['trailer'], $atk_id, $operation['atk_operation'], $technic_row->id(), 'trailer');
					Jelly::factory('client_planning_atkoperationtechnicaggregateblock')->save_aggregates($tech['all2']['aggregates'], $tech['all']['aggregates'], $atk_id, $operation['atk_operation'], $technic_row->id());
				}
			}
			
			if(count($do_not_delete)){
				Jelly::delete('client_planning_atkoperation2technic')->where('atk', '=', $atk_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
				Jelly::delete('client_planning_atkoperationtechnicmobileblock')->where('atk', '=', $atk_id)->and_where('atk_operation_technic', 'NOT IN', $do_not_delete)->execute();
				Jelly::delete('client_planning_atkoperationtechnictrailerblock')->where('atk', '=', $atk_id)->and_where('atk_operation_technic', 'NOT IN', $do_not_delete)->execute();
				Jelly::delete('client_planning_atkoperationtechnicaggregateblock')->where('atk', '=', $atk_id)->and_where('atk_operation_technic', 'NOT IN', $do_not_delete)->execute();
			}else{
				Jelly::delete('client_planning_atkoperation2technic')->where('atk', '=', $atk_id)->execute();
				Jelly::delete('client_planning_atkoperationtechnicmobileblock')->where('atk', '=', $atk_id)->execute();
				Jelly::delete('client_planning_atkoperationtechnictrailerblock')->where('atk', '=', $atk_id)->execute();
				Jelly::delete('client_planning_atkoperationtechnicaggregateblock')->where('atk', '=', $atk_id)->execute();
			}
        }

    }
	
	
	public function save_technics($second_grid_items, $main_grid_items, $atk_id, $atk_operation_id, $atk_operation_technic_id, $type){
		$do_not_delete = array();
		
		foreach($second_grid_items as $second_grid_item){
			if(substr($second_grid_item['id'], 0, 1)=='g')continue;
			$tech_id = substr($second_grid_item['id'], 1);
			
			$item = Jelly::select('client_planning_atkoperationtechnic'.$type.'block')->where('atk_operation_technic', '=', $atk_operation_technic_id)->and_where('technic_'.$type, '=', $tech_id)->limit(1)->load();
			if(!$item instanceof Jelly_Model || !$item->loaded()){
				$item = Jelly::factory('client_planning_atkoperationtechnic'.$type.'block');
			}
			
			$item->atk = $atk_id;
			$item->atk_operation = $atk_operation_id;
			$item->atk_operation_technic = $atk_operation_technic_id;
			if($type=='mobile')$item->technic_mobile = $tech_id;
			else			   $item->technic_trailer = $tech_id;
			$item->in_main = $this->is_in_main($main_grid_items, $tech_id);
			$item->save();
			
			$do_not_delete[] = $item->id();
		}
		
		if(count($do_not_delete)) Jelly::delete('client_planning_atkoperationtechnic'.$type.'block')->where('atk_operation_technic', '=', $atk_operation_technic_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
		else					  Jelly::delete('client_planning_atkoperationtechnic'.$type.'block')->where('atk_operation_technic', '=', $atk_operation_technic_id)->execute();
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
			
			$mobiles    = Jelly::select('client_planning_atkoperationtechnicmobileblock')->with('technic_mobile')->where('atk_operation_technic', '=', $technic['_id'])->execute()->as_array();
			$trailers   = Jelly::select('client_planning_atkoperationtechnictrailerblock')->with('technic_trailer')->where('atk_operation_technic', '=', $technic['_id'])->execute()->as_array();
			$aggregates = Jelly::select('client_planning_atkoperationtechnicaggregateblock')->with('gsm')->where('atk_operation_technic', '=', $technic['_id'])->execute()->as_array();
			
			$all['mobile']     = Jelly::factory('client_operations2technics')->build_tree($mobiles, 'mobile', true);
			$all['trailer']    = Jelly::factory('client_operations2technics')->build_tree($trailers, 'trailer', true);
			$all['aggregates'] = $this->build_main_aggregates($aggregates);
			
			$all2['mobile']     = Jelly::factory('client_operations2technics')->build_tree($mobiles, 'mobile', false);
			$all2['trailer']    = Jelly::factory('client_operations2technics')->build_tree($trailers, 'trailer', false);
			$all2['aggregates'] = Jelly::factory('client_operations2technics')->build_secondary_aggregates($aggregates);
			
			$technic['all'] = json_encode($all);
			$technic['all2'] = json_encode($all2);
			
			$gsm = Jelly::select('glossary_gsm', (int)$technic['gsm']);
			if($gsm instanceof Jelly_Model && $gsm->loaded()){
				$technic['gsm'] = array('_id'=>$gsm->id(), 'name'=>$gsm->name);
			}else{
				$technic['gsm'] = array('_id'=>0, 'name'=>'');
			}
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

