<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Operations2Technics extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operations2technics')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),	
                
                'mobile_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operationtechnicmobileblock',
                    'label'	=> 'Блок подвижного состава',
                )),
				
				'trailer_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operationtechnictrailerblock',
                    'label'	=> 'Блок прицепного состава',
                )),
				
				'aggregates_block' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operationtechnicaggregateblock',
                    'label'	=> 'Блок агрегатов',
                )),
				
				'title' => Jelly::field('String', array('label' => 'Название')),
				
				'gsm' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_gsm',
                        'column'	=> 'glossary_gsm_id',
                        'label'		=> 'ГСМ'
                )),
                
                'cons_norm' =>  Jelly::field('String', array('label' => 'Норма расхода')),
                'cons_norm_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'cons_norm_units_id',
                        'label'		=> 'Единицы измерения'
                ))

		));
	}
    
    
    public function save_from_grid($technics, $operation_id){
		$do_not_delete = array(); //ид записей, которые не надо удалять
        $data = array();
		
        foreach($technics as $tech){ //$tech - одна строка из таблицы в интерфейсе
			
			if(UTF8::strpos($tech['rowId'], 'new_')===false){
				$technic_row = Jelly::factory('client_operations2technics');
			}else{
				$technic_row = Jelly::select('client_operations2technics', (int)$tech['rowId']);
				if(!$technic_row instanceof Jelly_Model || !$technic_row->loaded()) $technic_row = Jelly::factory('client_operations2technics');
			}

			$technic_row->operation = $operation_id;
			$technic_row->title = $tech['technic']['value'];
			$technic_row->gsm = isset($tech['gsm']['id']) ? (int)$tech['gsm']['id'] : 0;
			$technic_row->cons_norm = $tech['gsm_norm'];
			$technic_row->cons_norm_units = isset($tech['gsm_norm_units']['id']) ? (int)$tech['gsm_norm_units']['id'] : 21;
			$technic_row->save();
			
			$do_not_delete[] = $technic_row->id();
			
			$this->save_technics($tech['technic']['all2']['mobile'], $tech['technic']['all']['mobile'], $operation_id, $technic_row->id(), 'mobile');
			$this->save_technics($tech['technic']['all2']['trailer'], $tech['technic']['all']['trailer'], $operation_id, $technic_row->id(), 'trailer');
			Jelly::factory('client_operationtechnicaggregateblock')->save_aggregates($tech['technic']['all2']['aggregates'], $tech['technic']['all']['aggregates'], $operation_id, $technic_row->id());
        }
        
        if(count($do_not_delete)){
			Jelly::delete('client_operations2technics')->where('operation', '=', $operation_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_operationtechnicmobileblock')->where('operation', '=', $operation_id)->and_where('operation_technic', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_operationtechnictrailerblock')->where('operation', '=', $operation_id)->and_where('operation_technic', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_operationtechnicaggregateblock')->where('operation', '=', $operation_id)->and_where('operation_technic', 'NOT IN', $do_not_delete)->execute();
		}else{
			Jelly::delete('client_operations2technics')->where('operation', '=', $operation_id)->execute();
			Jelly::delete('client_operationtechnicmobileblock')->where('operation', '=', $operation_id)->execute();
			Jelly::delete('client_operationtechnictrailerblock')->where('operation', '=', $operation_id)->execute();
			Jelly::delete('client_operationtechnicaggregateblock')->where('operation', '=', $operation_id)->execute();
		}
    }
	
	
	public function save_technics($second_grid_items, $main_grid_items, $operation_id, $operation_technics_id, $type){
		$do_not_delete = array();
		
		foreach($second_grid_items as $second_grid_item){
			if(substr($second_grid_item['id'], 0, 1)=='g')continue;
			$tech_id = substr($second_grid_item['id'], 1);
			
			$item = Jelly::select('client_operationtechnic'.$type.'block')->where('operation_technic', '=', $operation_technics_id)->and_where('technic_'.$type, '=', $tech_id)->limit(1)->load();
			if(!$item instanceof Jelly_Model || !$item->loaded()){
				$item = Jelly::factory('client_operationtechnic'.$type.'block');
			}
			
			$item->operation = $operation_id;
			$item->operation_technic = $operation_technics_id;
			if($type=='mobile')$item->technic_mobile = $tech_id;
			else			   $item->technic_trailer = $tech_id;
			$item->in_main = $this->is_in_main($main_grid_items, $tech_id);
			$item->save();
			
			$do_not_delete[] = $item->id();
		}
		
		if(count($do_not_delete)) Jelly::delete('client_operationtechnic'.$type.'block')->where('operation_technic', '=', $operation_technics_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
		else					  Jelly::delete('client_operationtechnic'.$type.'block')->where('operation_technic', '=', $operation_technics_id)->execute();
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
			
			$mobiles    = Jelly::select('client_operationtechnicmobileblock')->with('technic_mobile')->where('operation_technic', '=', $technic['_id'])->execute()->as_array();
			$trailers   = Jelly::select('client_operationtechnictrailerblock')->with('technic_trailer')->where('operation_technic', '=', $technic['_id'])->execute()->as_array();
			$aggregates = Jelly::select('client_operationtechnicaggregateblock')->with('gsm')->where('operation_technic', '=', $technic['_id'])->execute()->as_array();
			
			$all['mobile']     = $this->build_tree($mobiles, 'mobile', true);
			$all['trailer']    = $this->build_tree($trailers, 'trailer', true);
			$all['aggregates'] = $this->build_main_aggregates($aggregates);
			
			$all2['mobile']     = $this->build_tree($mobiles, 'mobile', false);
			$all2['trailer']    = $this->build_tree($trailers, 'trailer', false);
			$all2['aggregates'] = $this->build_secondary_aggregates($aggregates);
			
			$technic['all'] = json_encode($all);
			$technic['all2'] = json_encode($all2);
			
			$gsm = Jelly::select('glossary_gsm', (int)$technic['gsm']);
			if($gsm instanceof Jelly_Model && $gsm->loaded()) $gsm = array('id'=>$gsm->id(), 'title'=>$gsm->name, '_id'=>$gsm->id(), 'name'=>$gsm->name);
			else $gsm = array('id'=>0, 'title'=>'');
			$technic['gsm'] = $gsm;
		}

		return $technics;
	}
	
	
	
	public function build_tree($technics, $type, $in_main){
		$user = Auth::instance()->get_user();
		$do_not_delete = array();
		$deleted = array();//массив удалённых. нужен чтоб потом их убарать из чаилдов
		$groups_tree =	Jelly::factory('client_handbook_technique'.$type.'group')->get_tree($user->license->id());
		$prekey = ':technic_'.$type.':';
		
		for($i=count($technics)-1; $i>=0; $i--){
			if($in_main && !$technics[$i]['in_main']){
				array_splice($technics, $i, 1);
				continue;
			}
			if(array_search('g'.$technics[$i][$prekey.'group'], $do_not_delete)===false)$do_not_delete[] = 'g'.$technics[$i][$prekey.'group'];
		}
		
		for($i=count($groups_tree)-1; $i>=0; $i--){
			if(array_search($groups_tree[$i]['id'], $do_not_delete)===false){
				$deleted[] = $groups_tree[$i]['id'];
				array_splice($groups_tree, $i, 1);
			}else{
				if($groups_tree[$i]['parent']) $do_not_delete[] = $groups_tree[$i]['parent'];
				$groups_tree[$i]['children_n'] = array();
				for($k=count($groups_tree[$i]['children_g'])-1; $k>=0; $k--){
					if(array_search($groups_tree[$i]['children_g'][$k], $deleted)!==false){
						array_splice($groups_tree[$i]['children_g'], $k, 1);
					}
				}
				
				for($j=count($technics)-1; $j>=0; $j--){
					if('g'.$technics[$j][$prekey.'group']==$groups_tree[$i]['id']){
						$groups_tree[$i]['children_g'][] = 'n'.$technics[$j][$prekey.'_id'];
						array_splice($groups_tree, $i+1, 0, array(array(
							'id' => 'n'.$technics[$j][$prekey.'_id'],
							'title' => htmlspecialchars($technics[$j][$prekey.'name']),
							'clear_title' => htmlspecialchars($technics[$j][$prekey.'name']),
							'is_group' => true,
							'is_group_realy' => false,
							'children_g' => array(),
							'children_n' => array(),
							'parent' => $groups_tree[$i]['id'],
							'level' => $groups_tree[$i]['level']+1,
							'color' => $technics[$j][$prekey.'color'],
							'parent_color' => $groups_tree[$i]['color'],
							'extras' => $in_main ? array() : Jelly::factory('client_transaction')->get_extras('client_handbook_technique'.$type, $technics[$j][$prekey.'_id'])
						)));
					}
				}
			}
		}
		
		//вставляем вначало те что были без парента
		for($i=count($technics)-1; $i>=0; $i--){
			if(!trim($technics[$i][$prekey.'group']))
				array_splice($groups_tree, 0, 0, array(array(
					'id' => 'n'.$technics[$i][$prekey.'_id'],
					'title' => htmlspecialchars($technics[$i][$prekey.'name']),
					'clear_title' => htmlspecialchars($technics[$i][$prekey.'name']),
					'is_group' => true,
					'is_group_realy' => false,
					'children_g' => array(),
					'children_n' => array(),
					'parent' => '',
					'level' => 0,
					'color' => $technics[$i][$prekey.'color'],
					'parent_color' => $technics[$i][$prekey.'color'],
					'extras' => $in_main ? array() : Jelly::factory('client_transaction')->get_extras('client_handbook_technique'.$type, $technics[$i][$prekey.'_id'])
				)));
		}
		
		return $groups_tree;
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
				'checked' => $aggregate['checked']
			);
		}
		return $result;
	}
	
	
    public function build_secondary_aggregates($aggregates){
		$result = array();
		foreach($aggregates as $aggregate){
			$mobile_title = explode('+', $aggregate['title']);
			$mobile_title = trim($mobile_title[0]);
			
			$secondary_gsm_norm = (float)$aggregate['fuel_work_secondary'] > 0 ? $aggregate['fuel_work_secondary'].' л/га' : '';
			
			$result[] = array(
				'id' => 'n'.((int)$aggregate['technic_mobile']).'-n'.((int)$aggregate['technic_trailer']),
				'title' => htmlspecialchars($aggregate['title']).'</div><div style=\"color: #666666; height: 28px; font-size:15px; padding-top:4px;\">'.$secondary_gsm_norm.'</div><div>',
				'clear_title' => htmlspecialchars($aggregate['title']),
				'is_group' => false,
				'is_group_realy' => false,
				'children_g' => array(),
				'children_n' => array(),
				'parent' => '',
				'level' => 0,
				'color' => $aggregate['color'],
				'parent_color' => $aggregate['color'],
				'mobile_title' => htmlspecialchars($mobile_title),
				'checked' => $aggregate['in_main'],
				'extras' => array('gsm_norm'=>$aggregate['fuel_work_secondary'], 'gsm_norm_units'=>$aggregate['fuel_work_units_secondary'], 'gsm_id'=>$aggregate[':gsm:_id'], 'gsm_name'=>$aggregate[':gsm:name'])
			);
		}
		return $result;
	}
    

}

