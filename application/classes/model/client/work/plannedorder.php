<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_PlannedOrder extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('work_planned_order')
			->fields(array(
				'_id' 			=> new Field_Primary,

				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),

				'license'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'license',
					'column'	=> 'license_id',
					'label'	=> 'Лицензия',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'farm'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm_id',
					'label'	=> 'Хозяйство',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'period'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'period_id',
					'label'		=> 'Период',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'name' => Jelly::field('String', array('label' => 'Название')),
				'color' => Jelly::field('String', array('label' => 'Цвет')),
				
				'order_status' => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_work_orderstatus',
					'column'	=> 'status_id',
					'label'		=> 'Статус Наряда'
                )),
				
				'order_date' =>  Jelly::field('Integer', array('label' => 'Дата наряда')),
				
				'planned_from_date' =>  Jelly::field('Integer', array('label' => 'Плановая дата начала')),
				'planned_to_date' =>  Jelly::field('Integer', array('label' => 'Плановая дата конца')),
				
				'actual_from_date' =>  Jelly::field('Integer', array('label' => 'Фактическая дата начала')),
                'actual_to_date' =>  Jelly::field('Integer', array('label' => 'Фактическая дата конца')),
				
				'executor'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personal',
                        'column'	=> 'personal_id',
                        'label'		=> 'Исполнитель'
                )),
				
				'fields_square' => Jelly::field('String', array('label' => 'Площадь полей, га')),

				'process_square' => Jelly::field('String', array('label' => 'Площадь к обработке, га')),
				'processed_square' => Jelly::field('String', array('label' => 'Обработанно, га')),

				'plan_inputs' => Jelly::field('String', array('label' => 'Плановые затраты, грн')),
                'actual_inputs' => Jelly::field('String', array('label' => 'Фактические затраты, грн')),

				
				'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),
				
				'plan'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_plan',
                        'column'	=> 'client_planning_plan_id',
                        'label'		=> 'План'
                )),
				
				'culture'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_culture',
                        'column'	=> 'culture_id',
                        'label'		=> 'Культура'
                )),
				
				'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),	
				
				'fields' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_work_plannedorder2field',
                    'label'	=> 'Поля'
                ))

			 ));

	}
	
	
	public function get_status_tree(){
		$list = array();
		
		$statuses = Jelly::select('client_work_orderstatus')->execute()->as_array();
		
		foreach($statuses as $status){
			$list[] = array(
				'id'	   => 'g'.$status['_id'],
				'title'    => $status['name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => 0,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => '',
				'color'    => $status['color'],
				'parent_color' => $status['color']
			);
		}
		
		return $list;
	}
	
	
	public function get_tree(){
		
		$user = Auth::instance()->get_user();
		$license_id = $user->license->id();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods =array(-1);
		
		$approved_plans = Jelly::select('client_planning_plan')->with('farm')
															   ->where('deleted', '=', false)
															   ->and_where('license', '=', $license_id)
															   ->and_where('farm', 'IN', $farms)
															   ->and_where('period', '=', $periods[0])
															   ->and_where('plan_status', '=', 3)->order_by('name', 'asc')->execute();
		
		$res = array();
		foreach($approved_plans as $plan){
			$fi = $plan->farm->id();
			
			if(!isset($res[$fi])) $res[$fi] = array(
				'farm_id' => $fi,
				'farm_name' => $plan->farm->name,
				'farm_color' => $plan->farm->color,
				'cultures_ids' => array(),
				'cultures' => array()
			);
			
			foreach($plan->cultures as $culture){
				$ci = $culture->culture->id();
				if(!isset($res[$fi]['cultures']['c_'.$ci])) $res[$fi]['cultures']['c_'.$ci] = array(
					'culture_id' => $ci,
					'culture_name' => $culture->culture->title,
					'culture_color' => $culture->culture->color,
					'fields_ids' => array(),
					'fields' => array()
				);
				$res[$fi]['cultures_ids'][] = 'gc'.$ci;
				
				foreach($culture->fields as $field){
					$fldi = $field->field->id();
					if(!isset($res[$fi]['cultures']['c_'.$ci]['fields']['fld_'.$fldi])) $res[$fi]['cultures']['c_'.$ci]['fields']['fld_'.$fldi] = array(
						'field_id' => $fldi,
						'field_name' => $field->field->title,
						'field_color' => $culture->culture->color
					);
					$res[$fi]['cultures']['c_'.$ci]['fields_ids'][] = 'n'.$fldi;
				}
				
			}
		}
		$res = array_merge($res, array());
		
		$list = Jelly::factory('farm')->get_full_tree($license_id, 0, false, true);
		
		for($i=count($list)-1; $i>=0; $i--){
			
			$list[$i]['id'] = 'gf'.substr($list[$i]['id'], 1);
			$list[$i]['is_group'] = $list[$i]['is_group_realy'] = true;
			$list[$i]['children_n'] = array();
			if($list[$i]['parent']) $list[$i]['parent'] = 'gf'.substr($list[$i]['parent'], 1);
			for($j=0; $j<count($list[$i]['children_g']); $j++){
				$list[$i]['children_g'][$j] = 'gf'.substr($list[$i]['children_g'][$j], 1);
			}
			
			for($j=count($res)-1; $j>=0; $j--){
				if($res[$j]['farm_id'] != substr($list[$i]['id'], 2))continue;
				
				//нашли что вставлять (не сарказм)
				$list[$i]['children_g'] = array_merge($list[$i]['children_g'], $res[$j]['cultures_ids']);
				
				$counter = 1;
				foreach($res[$j]['cultures'] as $cult){
					array_splice($list, $i+$counter, 0, array(array(
						'id'	   => 'gc'.$cult['culture_id'],
						'title'    => $cult['culture_name'],
						'is_group' => true,
						'is_group_realy' => true,
						'level'	   => $list[$i]['level']+1,
						'children_g' => $cult['fields_ids'],
						'children_n' => $cult['fields_ids'],
						'parent'   => $list[$i]['id'],
						'color'    => $cult['culture_color'],
						'parent_color' => $list[$i]['color']
					)));
					$counter++;
					
					foreach($cult['fields'] as $fld){
						array_splice($list, $i+$counter, 0, array(array(
							'id'	   => 'n'.$fld['field_id'],
							'title'    => $fld['field_name'],
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $list[$i]['level']+2,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => 'gc'.$cult['culture_id'],
							'color'    => $fld['field_color'],
							'parent_color' => $cult['culture_color']
						)));
						$counter++;
					}
				}
			}
			
		}
		
		
		return $list;
	}
	
	
	
	public function get_table_data($filter){
		
		$user = Auth::instance()->get_user();
		$license_id = $user->license->id();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		
		$approved_plans = Jelly::select('client_planning_plan')->with('farm')
															   ->where('deleted', '=', false)
															   ->and_where('license', '=', $license_id)
															   ->and_where('farm', 'IN', $farms)
															   ->and_where('period', '=', $periods[0])
															   ->and_where('plan_status', '=', 3)->order_by('name', 'asc')->execute();
		
		$res = array();
		foreach($approved_plans as $plan){
			$fi = $plan->farm->id();
			
			//хозяйство
			if(!isset($res[$fi])) $res[$fi] = array(
				'farm_id' => $fi,
				'plan_inputs' => 0,
				'actual_inputs' => 0,
				'cultures' => array()
			);
			
			foreach($plan->cultures as $culture){
				$cult_id = $culture->culture->id();
				
				if(count($filter['culture']) && !in_array($cult_id, $filter['culture'])) continue;
				
				foreach($culture->fields as $field){
					
					if(count($filter['field']) && !in_array($field->field->id(), $filter['field'])) continue;
					
					$atk_id = $field->atk->id();
					if(!$atk_id) continue;
					
					//а в хозяйстве "культуро-атк"
					if(!isset($res[$fi]['cultures'][$cult_id.'_'.$atk_id])) $res[$fi]['cultures'][$cult_id.'_'.$atk_id] = array(
						'item_id' => $cult_id.'_'.$atk_id,
						'culture_id' => $cult_id,
						'atk_id' => $atk_id,
						'atk_name' => $field->atk->name,
						'culture_name' => $culture->culture->title,
						'culture_color' => $culture->culture->color,
						'plan_inputs' => 0,
						'actual_inputs' => 0,
						'operations' => array()
					);
					$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['fields'][] = array('id'=>$field->field->id(), 'name'=>$field->field->name);
					
					foreach($field->atk->operations as $operation){
						$oi = $operation->id();
						
						if(count($filter['operation']) && !in_array($operation->operation->id(), $filter['operation'])) continue;
						
						//а в "культуро-атк" операции
						$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi] = array(
							'atk_operation_id' => $oi,
							'operation_id' => $operation->operation->id(),
							'plan_id' => $plan->id(),
							'atk_id' => $atk_id,
							'culture_id' => $cult_id,
							'operation_name' => $operation->operation->name,
							'operation_color' => $operation->operation->color,
							'fields' => array(),
							'source' => '',
							'actual_from_date' => 0,
							'actual_to_date' => 0,
							'process_square' => 0,
							'processed_square' => 0,
							'plan_inputs' => 0,
							'actual_inputs' => 0,
							'orders' => array()
						);
						
						
						//а в операции наряды
						$orders = Jelly::select('client_work_plannedorder')->with('order_status')
																		   ->where('deleted', '=', false)
																		   ->where('license', '=', $license_id)
																		   ->where('farm', '=', $fi)
																		   ->where('period', '=', $periods[0])
																		   ->where('culture', '=', $cult_id)
																		   ->where('atk', '=', $atk_id)
																		   ->where('operation', '=', $operation->operation->id())
																		   ->order_by('name')->execute()->as_array();
						foreach($orders as $order){
							
							if(count($filter['status']) && !in_array($order[':order_status:_id'], $filter['status'])) continue;
							
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['orders'][$order['_id']] = array(
								'order_id' => $order['_id'],
								'order_name' => $order['name'],
								'order_color' => $order['color'],
								'order_status' => $order[':order_status:name'],
								'fields' => array(),
								'source' => '',
								'process_square' => $order['process_square'],
								'processed_square' => $order['processed_square'],
								'actual_from_date' => date('d.m.Y', $order['actual_from_date']),
								'actual_to_date' => date('d.m.Y', $order['actual_to_date']),
								'plan_inputs' => $order['plan_inputs'],
								'actual_inputs' => $order['actual_inputs'],
							);
							
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['plan_inputs'] += $order['plan_inputs'];
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['actual_inputs'] += $order['actual_inputs'];
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['process_square'] += $order['process_square'];
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['processed_square'] += $order['processed_square'];
							
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['plan_inputs'] += $order['plan_inputs'];
							$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['actual_inputs'] += $order['actual_inputs'];
							
							$res[$fi]['plan_inputs'] += $order['plan_inputs'];
							$res[$fi]['actual_inputs'] += $order['actual_inputs'];
							
							$fields = Jelly::select('client_work_plannedorder2field')->where('planned_order', '=', $order['_id'])->execute();
							foreach($fields as $field){
								$fld_id = $field->field->id();
								$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['orders'][$order['_id']]['fields'][$fld_id] = array('field_id'=>$fld_id, 'field_name' => $field->field->title);
								if(!isset($res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['fields'][$fld_id]))
									$res[$fi]['cultures'][$cult_id.'_'.$atk_id]['operations'][$oi]['fields'][$fld_id] = array('field_id'=>$fld_id, 'field_name' => $field->field->title);
							}
						}
						
					}
				}
			}
		}
		$res = array_merge($res, array());
		
		
		
		$farms = Jelly::factory('farm')->get_full_tree($license_id, 0, false, true);
		foreach($farms as &$farm){
			$farm['id'] = substr($farm['id'], 1);
			if($farm['parent']) $farm['parent'] = substr($farm['parent'], 1);
			foreach($res as $r){
				if($farm['id']==$r['farm_id']){
					$farm = array_merge($farm, $r);
				}
			}
			if(!isset($farm['plan_inputs'])) $farm['plan_inputs'] = 0;
			if(!isset($farm['actual_inputs'])) $farm['actual_inputs'] = 0;
		}
		
		$this->prepare_table_data($farms);
		
		return $farms;
	}
	
	
	
	public function prepare_table_data(&$farms){
		$inputs = array();
		
		for($i=count($farms)-1; $i>=0; $i--){
			if(isset($inputs['farm_'.$farms[$i]['id']])){
				$farms[$i]['plan_inputs'] += $inputs['farm_'.$farms[$i]['id']]['plan_inputs'];
				$farms[$i]['actual_inputs'] += $inputs['farm_'.$farms[$i]['id']]['actual_inputs'];
			}
			if($farms[$i]['parent']){
				if(!isset($inputs['farm_'.$farms[$i]['parent']])) $inputs['farm_'.$farms[$i]['parent']] = array('plan_inputs'=>0, 'actual_inputs'=>0);
				$inputs['farm_'.$farms[$i]['parent']]['plan_inputs'] += $farms[$i]['plan_inputs'];
				$inputs['farm_'.$farms[$i]['parent']]['actual_inputs'] += $farms[$i]['actual_inputs'];
			}
		}
	}
	
	
	
	
	public function get_plan_fields($plan_id, $atk_id){
		$result = array();
		$fields = Jelly::select('client_planning_planculture2field')->where('plan', '=', (int)$plan_id)->where('atk', '=', (int)$atk_id)->execute();
		foreach ($fields as $field){
			$result[] = $field->as_array();
		}
		return $result;
	}
	
	
	
	public function get_fields_data($planned_order_id){
		$result = array(); $it = 0;
		
		$order_fields = Jelly::select('client_work_plannedorder2field')->where('planned_order', '=', $planned_order_id)->execute();
		foreach($order_fields as $order_field){
			$result[$it] = $order_field->as_array();
			$result[$it]['technics'] = Jelly::factory('client_work_plannedorderfield2technic')->prepare_technics($order_field->technics->as_array());
			$result[$it]['materials'] = array();
			
			$j = 0;
			foreach($order_field->materials as $material){
				$model = 'glossary_'.$material->material_model;
				$m = Jelly::select($model, (int)$material->material_id);
				
				if($m instanceof Jelly_Model && $m->loaded()){
					$result[$it]['materials'][$j] = $material->as_array();
					$result[$it]['materials'][$j]['material'] = $m;
					$j++;
				}
			}
			
			$it++;
		}
		
		return $result;
	}
	
	
	
	public function get_materials($atk_clone, $operation_id, $field){
		$result = array();
		$field_area = $field['field']->area;
		$it=0;
		
		$atk_clone_operation = Jelly::select('client_planning_atkclone_atk2operation')->where('atk', '=', $atk_clone->id())->and_where('operation', '=', $operation_id)->limit(1)->load();
		
		if($atk_clone_operation instanceof Jelly_Model && $atk_clone_operation->loaded()){
			$materials = Jelly::select('client_planning_atkclone_atkoperation2material')->where('atk', '=', $atk_clone->id())
																						->where('atk_operation', '=', $atk_clone_operation->id())
																						->where('checked', '=', true)->execute();
			foreach($materials as $material){
				$model = 'glossary_'.$material->material_model;
				$m = Jelly::select($model, (int)$material->material_id);
				
				if($m instanceof Jelly_Model && $m->loaded()){
					$result[$it] = $material->as_array();
					
					$result[$it]['planned_crop_norm'] = $result[$it]['crop_norm'];
					$result[$it]['planned_crop_norm_units'] = $result[$it]['units'];
					$result[$it]['actual_crop_norm'] = $result[$it]['crop_norm'];
					$result[$it]['actual_crop_norm_units'] = $result[$it]['units'];
					
					$result[$it]['planned_debit'] = $result[$it]['planned_crop_norm'] * $field_area;
					$result[$it]['actual_debit'] = $result[$it]['actual_crop_norm'] * $field_area;
					
					$result[$it]['planned_price'] = $result[$it]['price'];
					$result[$it]['actual_price'] = $result[$it]['price'];
					
					$result[$it]['planned_total'] = $result[$it]['planned_debit'] * $result[$it]['planned_price'];
					$result[$it]['actual_total'] = $result[$it]['actual_debit'] * $result[$it]['actual_price'];
					$result[$it]['material'] = $m;
					$result[$it]['native'] = true; //значит что взят из операции, а не вручную.
					$it++;
				}
			}
		}
		
		return $result;
	}
	
	
	
	public function get_technics($atk_clone, $operation_id, $field){
		$result = array();
		$field_area = $field['field']->area;
		$it=0;
		
		$atk_clone_operation = Jelly::select('client_planning_atkclone_atk2operation')->where('atk', '=', $atk_clone->id())->and_where('operation', '=', $operation_id)->limit(1)->load();
		
		if($atk_clone_operation instanceof Jelly_Model && $atk_clone_operation->loaded()){
			$technics = Jelly::select('client_planning_atkclone_atkoperation2technic')->where('atk', '=', $atk_clone->id())
																					  ->where('atk_operation', '=', $atk_clone_operation->id())
																					  ->where('checked', '=', true)->execute();
			foreach($technics as $technic){
				$result[$it] = $technic->as_array();
				
				$result[$it]['planned_fuel_work'] = $result[$it]['fuel_work'];
				$result[$it]['planned_fuel_work_units'] = $result[$it]['fuel_work_units'];
				$result[$it]['actual_fuel_work'] = $result[$it]['fuel_work'];
				$result[$it]['actual_fuel_work_units'] = $result[$it]['fuel_work_units'];

				$result[$it]['planned_debit'] = $result[$it]['planned_fuel_work'] * $field_area;
				$result[$it]['actual_debit'] = $result[$it]['actual_fuel_work'] * $field_area;

				$result[$it]['planned_price'] = $result[$it]['price'];
				$result[$it]['actual_price'] = $result[$it]['price'];

				$result[$it]['planned_total'] = $result[$it]['planned_debit'] * $result[$it]['planned_price'];
				$result[$it]['actual_total'] = $result[$it]['actual_debit'] * $result[$it]['actual_price'];

				$result[$it]['native'] = true; //значит что взят из операции, а не вручную.
				$result[$it]['gsm'] = $result[$it]['gsm']->id();
				
				$it++;
			}
		}
		
		return $result;
	}
	
	
	
	public function get_personals($atk_clone, $operation_id, $field){
		
		$result = array();
		$field_area = $field['field']->area;
		$it=0;
		
		$atk_clone_operation = Jelly::select('client_planning_atkclone_atk2operation')->where('atk', '=', $atk_clone->id())->and_where('operation', '=', $operation_id)->limit(1)->load();
		
		if($atk_clone_operation instanceof Jelly_Model && $atk_clone_operation->loaded()){
			$personals = Jelly::select('client_planning_atkclone_atkoperation2personal')->where('atk', '=', $atk_clone->id())
																					  ->where('atk_operation', '=', $atk_clone_operation->id())
																					  ->where('checked', '=', true)->execute();
			
			foreach($personals as $personal){
				$result[$it] = $personal->as_array();
				
				$result[$it]['person'] = array('_id'=>0, 'name'=>'', 'color'=> 'FFF');
				
				$result[$it]['planned_count'] = $result[$it]['personal_count'];
				$result[$it]['actual_count'] = $result[$it]['personal_count'];
				
				$result[$it]['planned_salary'] = $result[$it]['price'];
				$result[$it]['actual_salary'] = $result[$it]['price'];
				
				$result[$it]['planned_total'] = $result[$it]['planned_count'] * $result[$it]['planned_salary'] * $field_area;
				$result[$it]['actual_total'] = $result[$it]['actual_count'] * $result[$it]['actual_salary'] * $field_area;
				
				$result[$it]['native'] = true; //значит что взят из операции, а не вручную.
				
				$it++;
			}
		}
		
		return $result;
	}
	
	
	
	public function get_culture($atk_id){
		$atk = Jelly::select('client_planning_atk', (int)$atk_id);
		if(!$atk instanceof Jelly_Model || !$atk->loaded()){
			return array();
		}
		
		return $atk->culture->as_array();
	}

	
	
	public function delete($key = NULL){
        $id = $this->id();
		
		Jelly::delete('Client_Work_PlannedOrder2Field')->where('planned_order', '=', $id)->execute();
		
		Jelly::delete('client_work_plannedorderfield2material')->where('planned_order', '=', $id)->execute();
		Jelly::delete('client_work_plannedorderfield2technic')->where('planned_order', '=', $id)->execute();
		Jelly::delete('client_work_plannedorderfield2personal')->where('planned_order', '=', $id)->execute();
		
		Jelly::delete('client_work_plannedorderfieldtechnicmobileblock')->where('planned_order', '=', $id)->execute();
		Jelly::delete('client_work_plannedorderfieldtechnictrailerblock')->where('planned_order', '=', $id)->execute();
		Jelly::delete('client_work_plannedorderfieldtechnicaggregateblock')->where('planned_order', '=', $id)->execute();
		
		return parent::delete($key);
    }
	
	
	
	public function get_fields_tree($license_id, $filter = true, $sort = 'culture'){
		$session = Session::instance();
		$periods = $session->get('periods');
		if(!is_array($periods) || !count($periods)) $periods = array(-1);

        $elements = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('period', 'IN', $periods);


        $exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $license_id);
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $license_id);
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
		$cultures_tree = Jelly::factory('glossary_culturegroup')->get_tree($license_id, true, $exclude, 'items');
		$cultures_tree[] = array('id' => 'n0');
		$cultures_tree = array_reverse($cultures_tree);

		$farms = Jelly::factory('farm')->get_full_tree($license_id, 0, false, $filter);
		$formats = Jelly::factory('client_format')->get_formats($license_id);
        
        $elements =  $elements->execute()->as_array();
		for($i=0; $i<count($elements); $i++){
			$elements[$i]['title'] = $this->get_field_title($elements[$i], $formats);
		}
		foreach ($elements as $key => $row) {
			$title[$key]  = $row['title'];
		}
		if(count($elements))array_multisort($title, SORT_DESC, $elements);

		for($i=count($farms)-1; $i>=0; $i--){
			$farms[$i]['is_group_realy'] = true;
			$total_area = 0;

			foreach($cultures_tree as $clt){


				for($j=0; $j<count($elements); $j++){
					if($elements[$j]['farm']==substr($farms[$i]['id'], 1) && $elements[$j][$sort]==substr($clt['id'], 1) && substr($clt['id'], 0, 1)=='n'){
						$farms[$i]['is_group'] = true;
						$farms[$i]['children_g'][] = 'f'.$elements[$j]['_id'];
						$farms[$i]['children_n'][] = 'f'.$elements[$j]['_id'];
						$culture = Jelly::select('glossary_culture', $elements[$j]['culture']);
						$predecessor = Jelly::select('glossary_culture', $elements[$j]['culture_before']);
						$total_area += $elements[$j]['area'];
						array_splice($farms, $i+1, 0, array(array(
							'id'	   => 'f'.$elements[$j]['_id'],
							'title'    => $elements[$j]['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.$elements[$j]['area'].' га</div><div>',
							'is_group' => false,
							'is_group_realy' => false,
							'level'	   => $farms[$i]['level']+3,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => $farms[$i]['id'],
							'color'    => $culture->color ? $culture->color : 'transparent',
							'parent_color' => $culture->color
						)));
					}
				}

			}

			if($total_area>0)$farms[$i]['title'] = $farms[$i]['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.str_replace (',', '.', $total_area).' га</div><div>';
			$farms[$i]['square'] = str_replace (',', '.', $total_area);
		}
		return $farms;
	}
	
	public function get_field_title($field, $formats, $use_name=true){
		$arr = array();
		if(trim($field['crop_rotation_number']) && $formats['crop_rotation_n']) $arr[] = trim($field['crop_rotation_number']);
		if(trim($field['number']) && $formats['field_n']) $arr[] = trim($field['number']);
		if(trim($field['sector_number']) && $formats['sector_n']) $arr[] = trim($field['sector_number']);	
		$title = implode('.', $arr);
		
		if(trim($field['name']) && $formats['field_name']) $title .= ' '.trim($field['name']);
		return $title;
	}
}

