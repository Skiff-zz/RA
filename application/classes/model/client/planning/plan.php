<?php

class Model_Client_Planning_Plan extends Jelly_Model
{
    
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_plan')
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
                'color'	=> Jelly::field('String', array('label' => 'Цвет')),

                'plan_status' => Jelly::field('BelongsTo',array(
                    'foreign'	=> 'client_planning_planstatus',
                    'column'	=> 'status_id',
                    'label'		=> 'Статус плана'
                )),
                
                
                'handbook_version'	=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_handbookversionname',
					'column'	=> 'handbook_version_id',
					'label'		=> 'Версия справочника'
                )),


                'planned_square' => Jelly::field('String', array('label' => 'Плановые площади, га')),
                'choosen_fields_count' => Jelly::field('Integer', array('label' => 'Подобрано полей, шт')),
                'choosen_fields_square' => Jelly::field('String', array('label' => 'Подобрано полей, га')),


                'plan_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                'actual_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                
                'plan_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                'actual_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                
                'plan_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                'actual_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                
                'plan_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),
                'actual_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),

                'cultures' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_plan2culture',
                    'label'	=> 'Культуры'
                )),
				
				'copied_from_farm' => Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'copied_farm_id',
					'label'	=> 'Хозяйство, с которого скопировано'
				)),

                'copied_from_period' => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'copied_period_id',
					'label'	=> 'Период, с которого скопировано'
				))
        ));
	}
	
    
    public function get_tree($license_id){
        
        $farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);
        
        $periods = Session::instance()->get('periods');
        if(!count($periods)) $periods = array(-1);
        
        $names = Jelly::select('client_planning_plan')->with('plan_status')->with('handbook_version')->with('farm')
                                                     ->where('deleted', '=', false)
                                                     ->and_where('license', '=', $license_id)
                                                     ->and_where('farm', 'IN', $farms)
                                                     ->and_where('period', '=', $periods[0])
                                                     ->order_by('name', 'asc')->execute()->as_array();
		
		$res = array(); $farms = array();
		
		foreach($names as $name){
			$hi = (int)$name[':handbook_version:_id'];
			$fi = (int)$name[':farm:_id'];
				
			if(!isset($res[$fi.'_'.$hi])){
				$farm_path = Jelly::factory('farm')->getBreadCrumbs($fi);
				$farm_path = implode(', ', $farm_path);
				
				$res[$fi.'_'.$hi] = array(
					'id'	   => 'g'.$fi.'_'.$hi,
					'title'    => ($name[':handbook_version:name'] ? $name[':handbook_version:name'] : 'Без справочника'),
					'is_group' => true,
					'is_group_realy' => true,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => '',
					'color'    => ($name[':handbook_version:color'] ? $name[':handbook_version:color'] : '92b7e9'),
					'parent_color' => ($name[':handbook_version:color'] ? $name[':handbook_version:color'] : '92b7e9'),
					'farm' => $fi,
					'farm_path' => $farm_path,
					'handbook_version' => $hi
				);
			}

			$res[$fi.'_'.$hi]['children_g'][] = 'n'.$name['_id'];
		}
		
		usort($res, function($a, $b){
			$cmp = strcmp($a['farm'], $b['farm']);
			return $cmp!=0 ? $cmp : strcmp($a['title'], $b['title']);
		});

        for($i=count($res)-1; $i>=0; $i--){
			for($j=count($names)-1; $j>=0; $j--){
				if($names[$j][':handbook_version:_id']==$res[$i]['handbook_version'] && $names[$j][':farm:_id']==$res[$i]['farm']){
					array_splice($res, $i+1, 0, array(array(
						'id'	   => 'n'.$names[$j]['_id'],
						'title'    => $names[$j]['name'].'</div>'.
									  '<div style="color: #666666; width: auto; height: 28px; font-size:13px; margin-right:-3px; display:-webkit-box; -webkit-box-orient: horizontal;">'.
											(((int)$names[$j]['copied_from_period']) ? '<div class="zamo4ek">&nbsp;</div>' : '').
											($names[$j][':plan_status:_id'] ? '<div style="padding:6px 2px;">'.$names[$j][':plan_status:name'].'</div>' : '').
									  '</div><div>',
						'clear_title' => $names[$j]['name'],
						'is_group' => true,
						'is_group_realy' => false,
						'level'	   => 1,
						'children_g' => array(),
						'children_n' => array(),
						'parent'   => $res[$i]['id'],
						'color'    => $names[$j]['color'],
						'parent_color' => $res[$i]['color'],
						'copied_from' => array('period'=>(int)$names[$j]['copied_from_period']),
						'farm' => $res[$i]['farm'],
						'farm_path' => $res[$i]['farm_path']
					)));
				}
			}
		}
        
        return $res;
    }
	
	
	
	public function get_table_plans($license_id){
		
		$farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);
        
        $periods = Session::instance()->get('periods');
        if(!count($periods)) $periods = array(-1);
		
		$plans = Jelly::select('client_planning_plan')->with('plan_status')->with('farm')->with('handbook_version')
													  ->where('deleted', '=', false)
                                                      ->and_where('license', '=', $license_id)
                                                      ->and_where('farm', 'IN', $farms)
                                                      ->and_where('period', '=', $periods[0])
                                                      ->order_by('name', 'asc')->execute()->as_array();
		
		$result = array();
		foreach($plans as $plan){
			$fi = (int)$plan[':farm:_id'];
			
			if(!isset($result['farm_'.$fi])) $result['farm_'.$fi] = array(
				'farm_id' => $fi,
				'farm_name' => $plan[':farm:name'],
				'farm_color' => $plan[':farm:color'],
				'plan_square' => 0,
				'checked_fields_count' => 0,
				'checked_fields_square' => 0,
				'outputs_plan' => 0,
				'outputs_actual' => 0,
				'income_plan' => 0,
				'income_actual' => 0,
				'profit_plan' => 0,
				'profit_actual' => 0,
				'rentablilty_plan' => 0,
				'rentablilty_actual' => 0,
				'plans' => array()
			);
			
			$result['farm_'.$fi]['plan_square'] += $plan['planned_square'];
			$result['farm_'.$fi]['checked_fields_count'] += $plan['choosen_fields_count'];
			$result['farm_'.$fi]['checked_fields_square'] += $plan['choosen_fields_square'];
			$result['farm_'.$fi]['outputs_plan'] += $plan['plan_inputs'];
			$result['farm_'.$fi]['outputs_actual'] += $plan['actual_inputs'];
			$result['farm_'.$fi]['income_plan'] += $plan['plan_income'];
			$result['farm_'.$fi]['income_actual'] += $plan['actual_income'];
			
			
			$result['farm_'.$fi]['plans']['plan_'.$plan['_id']] = array(
				'plan_id' => $plan['_id'],
				'plan_name' => $plan['name'],
				'plan_color' => $plan['color'],
				'plan_status' => $plan[':plan_status:name'],
				'plan_handbook' => $plan[':handbook_version:name'],
				'plan_square' => $plan['planned_square'],
				'checked_fields_count' => $plan['choosen_fields_count'],
				'checked_fields_square' => $plan['choosen_fields_square'],
				'outputs_plan' => $plan['plan_inputs'],
				'outputs_actual' => $plan['actual_inputs'],
				'income_plan' => $plan['plan_income'],
				'income_actual' => $plan['actual_income'],
				'profit_plan' => $plan['plan_profit'],
				'profit_actual' => $plan['actual_profit'],
				'rentablilty_plan' => round($plan['plan_rentability'], 2),
				'rentablilty_actual' => round($plan['actual_rentability'], 2),
				'cultures' => array()
			);
			
			$cultures = Jelly::select('client_planning_plan2culture')->with('culture')->where('plan', '=', $plan['_id'])->execute()->as_array();
			foreach($cultures as $culture){
				$result['farm_'.$fi]['plans']['plan_'.$plan['_id']]['cultures']['culture_'.$culture['_id']] = array(
					'planculture_id' => $culture['_id'],
					'culture_id' => $culture[':culture:_id'],
					'culture_name' => $culture[':culture:title'],
					'culture_color' => $culture[':culture:color'],
					'plan_square' => $culture['planned_square'],
					'priority' => $culture['priority'],
					'checked_fields_count' => $culture['choosen_fields_count'],
					'checked_fields_square' => $culture['choosen_fields_square'],
					'outputs_plan' => $culture['plan_inputs'],
					'outputs_actual' => $culture['actual_inputs'],
					'income_plan' => $culture['plan_income'],
					'income_actual' => $culture['actual_income'],
					'profit_plan' => $culture['plan_profit'],
					'profit_actual' => $culture['actual_profit'],
					'rentablilty_plan' => round($culture['plan_rentability'], 2),
					'rentablilty_actual' => round($culture['actual_rentability'], 2),
					'fields' => array()
				);
				
				$fields = Jelly::select('client_planning_planculture2field')->with('field')->with('atk')->where('plan', '=', $plan['_id'])->and_where('plan_culture', '=', $culture['_id'])->execute()->as_array();
				foreach($fields as $field){
					$result['farm_'.$fi]['plans']['plan_'.$plan['_id']]['cultures']['culture_'.$culture['_id']]['fields'][] = array(
						'field_id' => $field[':field:_id'],
						'field_name' => $field[':field:title'],
						'field_color' => $culture[':culture:color'],
						'atk' => array('id'=>$field[':atk:_id'], 'name'=>$field[':atk:name']),
						'field_square' => $field['field_square'],
						'outputs_plan' => $field['plan_inputs'],
						'outputs_actual' => $field['actual_inputs'],
						'income_plan' => $field['plan_income'],
						'income_actual' => $field['actual_income'],
						'profit_plan' => $field['plan_profit'],
						'profit_actual' => $field['actual_profit'],
						'rentablilty_plan' => round($field['plan_rentability'], 2),
						'rentablilty_actual' => round($field['actual_rentability'], 2),
					);
				}
				
			}
		}
		
		foreach($result as &$f){
			$f['profit_plan'] = $f['income_plan'] - $f['outputs_plan'];
			$f['profit_actual'] = $f['income_actual'] - $f['outputs_actual'];
			$f['rentablilty_plan'] = $f['outputs_plan']>0 ? round(($f['profit_plan']/$f['outputs_plan'])*100, 2) : 0.00;
			$f['rentablilty_actual'] = $f['outputs_actual']>0 ? round(($f['profit_actual']/$f['outputs_actual'])*100, 2) : 0.00;
		}
		
		return $result;
	}
	
	
	
	
//	public function copyPlan($plan, $farm, $period){
	public function copyPlan($plan, $period){
        $plan = Jelly::select('client_planning_plan', (int)$plan);
        if(!$plan instanceof Jelly_Model || !$plan->loaded()) return;


        $new_plan = Jelly::factory('client_planning_plan');
        $arr = $plan->as_array();
        unset($arr['id']);unset($arr['_id']);
        $new_plan->set($arr);
        $new_plan->name = UTF8::str_ireplace(' копия', '', $plan->name).' копия';
//        $new_plan->farm = $farm;
        $new_plan->period = $period;
//        $new_plan->copied_from_farm = $plan->farm->id()!=$farm ? $plan->farm->id() : 0;
        $new_plan->copied_from_period = $plan->period->id()!=$period ? $plan->period->id() : 0;
        $new_plan->cultures = array();
        $new_plan->save();



        foreach($plan->cultures as $culture){
            $new_plan_culture = Jelly::factory('Client_Planning_Plan2Culture');
            $arr = $culture->as_array();
            unset($arr['id']);unset($arr['_id']);
            $new_plan_culture->set($arr);
            $new_plan_culture->plan = $new_plan;
			$new_plan_culture->fields = array();
            $new_plan_culture->save();
			
			
			// Проверяем культуру
			$t_culture = Jelly::select('client_handbook')->where('model', '=', 'glossary_culture')
														 ->where('item', '=', $new_plan_culture->culture->id())
														 ->where('period', '=', $period)
//														 ->where('farm', '=', $farm)
														 ->where('farm', '=', $new_plan->farm->id())->load();

			if(!($t_culture instanceof Jelly_Model) or !$t_culture->loaded())
			{
				$c = Jelly::factory('client_handbook');
				$c->model 	= 'glossary_culture';
				$c->item  	= $new_plan_culture->culture->id();
				$c->period  = $period;
//				$c->farm	= $farm;
				$c->farm	= $new_plan->farm->id();
				$c->deleted = 0;
				$c->update_date = time();
				$c->license = Auth::instance()->get_user()->license->id();
				$c->save();

				unset($c);

				// Так же добавим родителей
				if($new_plan_culture->culture->group->id()){
				   $c_parent = Jelly::select('glossary_culturegroup', (int)$new_plan_culture->culture->group->id());

				   while($c_parent and $c_parent instanceof Jelly_Model and $c_parent->loaded()){
						$t_group = Jelly::select('client_handbook')->where('model', '=', 'glossary_culturegroup')
																   ->where('item', '=', $c_parent->id())
																   ->where('period', '=', $period)
//																   ->where('farm', '=', $farm)
																   ->where('farm', '=', $new_plan->farm->id())->load();

						if(!$t_group instanceof Jelly_Model or !$t_group->loaded()){
							$c = Jelly::factory('client_handbook');
							$c->model 	= 'glossary_culturegroup';
							$c->item  	= $c_parent->id();
							$c->period  = $period;
							$c->farm	= $new_plan->farm->id();
//							$c->farm	= $farm;
							$c->deleted = 0;
							$c->update_date = time();
							$c->license = Auth::instance()->get_user()->license->id();
							$c->save();

							unset($c);
						}

						if($c_parent->parent->id()){
							$c_parent = Jelly::select('glossary_culturegroup', (int)$c_parent->parent->id());
						}else{
							unset($c_parent);
							$c_parent = null;
						}
				   }
				}
			}
			

			foreach($culture->fields as $field){
				$atk = 0;
				if($field->atk->id()){
//					$atk = Jelly::factory('client_planning_atk')->copyAtk($field->atk->id(), $farm, $period);
					$atk = Jelly::factory('client_planning_atk')->copyAtk($field->atk->id(), $new_plan->farm->id(), $period);
				}
				
				$new_plan_culture_field = Jelly::factory('Client_Planning_PlanCulture2Field');
				$arr = $field->as_array();
				unset($arr['id']);unset($arr['_id']);
				$new_plan_culture_field->set($arr);
				$new_plan_culture_field->plan = $new_plan;
				$new_plan_culture_field->plan_culture = $new_plan_culture;
				$new_plan_culture_field->atk = $atk;
				$new_plan_culture_field->atk_clone = 0;
				$new_plan_culture_field->save();
				
				$atk_clone = Jelly::factory('client_planning_atkclone_atk')->update_field_atk($new_plan_culture->id(), $new_plan_culture_field->id(), $field->atk->id(), true);
				if($atk_clone){
					$new_plan_culture_field->atk_clone = $atk_clone;
					$new_plan_culture_field->save();
				}
			}
        }
    }
	
	
	
	public function update_plan_finances($plan_id, $handbook_version_id){
		setlocale(LC_NUMERIC, 'C');
		if(!$handbook_version_id) return;

        $plan = Jelly::select('client_planning_plan', (int)$plan_id);
		if(!$plan instanceof Jelly_Model || !$plan->loaded()) return;
		
		$p_plan_inputs = 0; $p_plan_income = 0; //для всего плана (сумма по культурам)
		
		foreach($plan->cultures as $culture){
			
			$c_plan_inputs = 0; $c_plan_income = 0; //для культуры (сумма по полям)
			
			foreach($culture->fields as $field){
				if($field->atk->id()){
					$new_prices = Jelly::factory('client_planning_atk')->get_atk_finances($field->atk->id(), $handbook_version_id, true, true);
					
					$field->plan_inputs = ((float)$new_prices['inputs']) * $field->field_square;
					$field->plan_income = ((float)$new_prices['income']) * $field->field_square;
					$field->plan_profit = ((float)$new_prices['profit']) * $field->field_square;
					$field->plan_rentability = $new_prices['rentability'];
					$field->atk_clone = 0;
					$field->save();
					
					$atk_clone = Jelly::factory('client_planning_atkclone_atk')->update_field_atk($culture->id(), $field->id(), $field->atk->id());
					if($atk_clone){
						$field->atk_clone = $atk_clone;
						$field->save();
					}
					
					$c_plan_inputs += $field->plan_inputs;
					$c_plan_income += $field->plan_income;
				}
			}
			
			$c_plan_profit = $c_plan_income - $c_plan_inputs;
			$c_plan_rentability = $c_plan_inputs>0 ? ($c_plan_profit/$c_plan_inputs)*100 : 0.00;
			
			$culture->plan_inputs = $c_plan_inputs;
			$culture->plan_income = $c_plan_income;
			$culture->plan_profit = $c_plan_profit;
			$culture->plan_rentability = $c_plan_rentability;
			$culture->save();

			$p_plan_inputs += $c_plan_inputs;
			$p_plan_income += $c_plan_income;
		}
		
		$p_plan_profit = $p_plan_income - $p_plan_inputs;
		$p_plan_rentability = $p_plan_inputs>0 ? ($p_plan_profit/$p_plan_inputs)*100 : 0.00;
		
		$plan->plan_inputs = $p_plan_inputs;
		$plan->plan_income = $p_plan_income;
		$plan->plan_profit = $p_plan_profit;
		$plan->plan_rentability = $p_plan_rentability;
		$plan->save();
		
    }
	
	
	
	
	public function update_fields_culture($license_id, $farm_id, $period_id, $cultures){
		$fields = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', '=', $farm_id)->and_where('period', '=', $period_id)->execute();
		
		foreach($fields as $field){
			$found = false;
			
			foreach($cultures as $culture){
				foreach($culture['culture_fields'] as $fld){
					if($fld['field']==$field->id()){
						$found = true;
						$field->culture = $culture['culture'];
					}
				}
			}
			
			if(!$found) $field->culture = 0;
			$field->save();
		}
	}
	
    
    
	
	public function get_properties(){
		$properties = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->execute();
		$t = array();
		foreach($properties as $property){
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
			if(($v instanceof Jelly_Model) and $v->loaded()) {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}else{
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}
		}
		return $t;
	}

	public function set_property($id, $property_name, $property_value = ''){
		$property = null;
        if($id){
            $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();
            if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		}
		if(!$id){
			$property = Jelly::factory('client_model_properties');
			$property->model 	= $this->_meta->model();
//			$property->license 	= $this->license;
			$property->name 	= $property_name;
			$property->save();
		}else{
            $property->name 	= $property_name;
			$property->save();
        }

		$value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
		if(!($value instanceof Jelly_Model) or !$value->loaded()){
			$value = Jelly::factory('client_model_values');
			$value->property 	= $property;
			$value->item_id 	= $this->id();
		}

		$value->value	 	= $property_value;
		$value->save();
	}


	public function delete_property($id){
		$property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();
		if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		Jelly::delete('client_model_values')->where('property', '=', $property->id())->execute();
		Jelly::delete('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->execute();
	}


    public function delete($key = NULL){
        //wtf? falling back to parent
        if (!is_null($key)){
            return parent::delete($key);
        }

		$this->deleted = true;
        $this->save();
    }
	
}