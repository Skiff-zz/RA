<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_HandOrder extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('work_hand_order')
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
                    'foreign'	=> 'client_work_handorder2field',
                    'label'	=> 'Поля'
                ))

			 ));

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
						$orders = Jelly::select('client_work_handorder')->with('order_status')
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
							
							$fields = Jelly::select('client_work_handorder2field')->where('hand_order', '=', $order['_id'])->execute();
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
		
		Jelly::factory('client_work_plannedorder')->prepare_table_data($farms);
		
		return $farms;
	}
	
	
	
	public function get_fields_data($hand_order_id){
		$result = array(); $it = 0;
		
		$order_fields = Jelly::select('client_work_handorder2field')->where('hand_order', '=', $hand_order_id)->execute();
		foreach($order_fields as $order_field){
			$result[$it] = $order_field->as_array();
			$result[$it]['technics'] = Jelly::factory('client_work_handorderfield2technic')->prepare_technics($order_field->technics->as_array());
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
	
	
	
	public function delete($key = NULL){
        $id = $this->id();
		
		Jelly::delete('Client_Work_handOrder2Field')->where('hand_order', '=', $id)->execute();
		
		Jelly::delete('client_work_handorderfield2material')->where('hand_order', '=', $id)->execute();
		Jelly::delete('client_work_handorderfield2technic')->where('hand_order', '=', $id)->execute();
		Jelly::delete('client_work_handorderfield2personal')->where('hand_order', '=', $id)->execute();
		
		Jelly::delete('client_work_handorderfieldtechnicmobileblock')->where('hand_order', '=', $id)->execute();
		Jelly::delete('client_work_handorderfieldtechnictrailerblock')->where('hand_order', '=', $id)->execute();
		Jelly::delete('client_work_handorderfieldtechnicaggregateblock')->where('hand_order', '=', $id)->execute();
		
		return parent::delete($key);
    }
	
}

