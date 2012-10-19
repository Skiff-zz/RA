<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_Atk extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('planning_atk')
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

				'culture'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_culture',
                        'column'	=> 'culture_id',
                        'label'		=> 'Культура'
                )),

				'atk_type'	 => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_planning_atktype',
					'column'	=> 'type_id',
					'label'		=> 'Тип АТК'
                )),

				'handbook_version_update_datetime' => Jelly::field('String', array('label' => 'Дата и время последнего обновления данных по версии справочника', 'type' => 'hiddenfield')),
				'handbook_version'	=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_handbookversionname',
					'column'	=> 'handbook_version_id',
					'label'		=> 'Версия справочника'
                )),

				'atk_status' => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_planning_atkstatus',
					'column'	=> 'status_id',
					'label'		=> 'Статус АТК'
                )),

				'inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
				'income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
				'profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
				'rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),

				'seeds' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atk2seed',
                    'label'	=> 'Семена',
                )),

                'operations' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atk2operation',
                    'label'	=> 'Операции',
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
				)),
				'outdated' => Jelly::field('Integer', array('label' => '"Свежесть" версии. Внесено для окрашивания кнопки "обновить" когда обновляются зарплаты персонала в операциях и в глоссарии',
					'type' => 'hiddenfield'))

			 ));

	}

	public function get_atk_finances($atk_id, $handbook_version_id, $update = false, $set_new_hv = false){
		setlocale(LC_NUMERIC, 'C');
        $result = array('inputs'=>0, 'income'=>0, 'profit'=>0, 'rentability'=>0);

        $atk = Jelly::select('client_planning_atk', (int)$atk_id);
		if(!$handbook_version_id){ //если версия пустая, то ничего делать не надо, отдаём цены из АТК

            if(!$atk instanceof Jelly_Model || !$atk->loaded()) return $result;
            $result = array('inputs'=>(float)$atk->inputs, 'income'=>(float)$atk->income, 'profit'=>(float)$atk->profit, 'rentability'=>(float)$atk->rentability);
            return $result;
        }else{
			$handbook_versionname = Jelly::select('Client_HandbookVersionName', (int)$handbook_version_id);
			$handbook_version_rows = Jelly::select('client_handbookversion')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('version_date', '=', $handbook_versionname->datetime)->
				where('license', '=', $handbook_versionname->license->id())->
				where('farm', '=', $handbook_versionname->farm->id())->
				where('period', '=', $handbook_versionname->period->id())->
				execute();
			$total_wastes = 0;
			foreach($atk->operations as $operation){
				$op_mat_wastes = 0;
				foreach($operation->materials as $material){
					foreach($handbook_version_rows as $row){
						if(
								$material->material_model==$row->nomenclature_model &&
								$material->material_id==$row->nomenclature_id &&
										(
											$material->units->order==$row->planned_price_units ||
											(  in_array($material->units->order,array(1,2,3,4)) && in_array($row->planned_price_units,array(1,2,3,4))  )
										)
						){
							if($material->units->order != $row->planned_price_units){
								if($material->units->order==1) {
									if($row->planned_price_units==2)$row->planned_price *= 10;
									if($row->planned_price_units==3)$row->planned_price *= 1000;
									if($row->planned_price_units==4)$row->planned_price *= 1000000;

								}
								if($material->units->order==2) {
									if($row->planned_price_units==1)$row->planned_price *= 0.1;
									if($row->planned_price_units==3)$row->planned_price *= 100;
									if($row->planned_price_units==4)$row->planned_price *= 100000;

								}
								if($material->units->order==3) {
									if($row->planned_price_units==1)$row->planned_price *= 0.001;
									if($row->planned_price_units==2)$row->planned_price *= 0.01;
									if($row->planned_price_units==4)$row->planned_price *= 1000;

								}
								if($material->units->order==4) {
									if($row->planned_price_units==1)$row->planned_price *= 0.000001;
									if($row->planned_price_units==2)$row->planned_price *= 0.00001;
									if($row->planned_price_units==3)$row->planned_price *= 0.001;

								}
							}


							$wastes = $material->count * $row->planned_price * $operation->processing_koef;
							$total_wastes += $wastes;

							if($update){$material->set(array(
								'price'=>$row->planned_price,
								'total'=>$wastes,
								'profit'=>$atk->income*($material->crop_lost/100) - $wastes,
								'rentability'=> $wastes>0 ? ($atk->income*($material->crop_lost/100) - $wastes)/$wastes : 0
							))->save();}


							break;
						}
					}
					$op_mat_wastes += $material->total;
				}

				if($update){
					$operation->set(array(
						'materials_costs'=>$op_mat_wastes
					))->save();
				}

				$op_tech_wastes = 0;
				foreach($operation->technics as $technic){
					if($technic->gsm){
						$found = false;
						foreach($handbook_version_rows as $row){
							if(
									'gsm'==$row->nomenclature_model &&
									$technic->gsm->id()==$row->nomenclature_id &&
											(
												5==$row->planned_price_units
											)
							){
								$found = true;
								$wastes = $technic->fuel_work * $row->planned_price * $operation->processing_koef;

								if($technic->checked)$total_wastes += $wastes;
								if($update){$technic->set(array( 'price'=>$row->planned_price, 'total'=>$wastes ))->save();}
								break;
							}
						}

					}else{
						$gsm_price = 0;
						$gsm_count = 0;
						foreach($technic->aggregates_block as $block){
							if($block->gsm){
								foreach($handbook_version_rows as $row){
									if(
											'gsm'==$row->nomenclature_model &&
											$block->gsm->id()==$row->nomenclature_id &&
													(
														5==$row->planned_price_units
													)
									){
										$gsm_price += $row->planned_price;
										$gsm_count++;

										if($update){$block->set(array( 'price'=>$row->planned_price, 'total'=>$row->planned_price*$block->fuel_work*$operation->processing_koef ))->save();}

										break;
									}
								}
							}
						}
						if(!$gsm_price){
							$avg_price = 0;
						}else{
							$avg_price = $gsm_price/$gsm_count;
						}

						$wastes = $technic->fuel_work * $avg_price * $operation->processing_koef;

						if($technic->checked)$total_wastes += $wastes;
						if($update){$technic->set(array( 'price'=>$avg_price, 'total'=>$wastes ))->save();}

					}
                    
					if($technic->checked)$op_tech_wastes += $technic->total;
				}
                

				$op_personal_wastes = 0;
				foreach($operation->personal as $personal_item){

					if($update){
						$in_operation = Jelly::select('client_operations2personal')->
								where('client_operation_id','=',$operation->operation->id())->
								where('personal','=',$personal_item->personal->id())->
								limit(1)->
								execute();
						$personal_item->set(array(
							'price'=>$in_operation->salary,
							'total'=>$in_operation->salary*$personal_item->personal_count
						))->save();

					}


					if($personal_item->checked){
						$op_personal_wastes += $personal_item->total;
					}

				}

				$total_wastes += $op_personal_wastes;

				if($update){
					$operation->set(array(
						'technics_costs'=>$op_tech_wastes,
						'total_costs'=>$operation->materials_costs + $op_tech_wastes + $operation->personal_costs
					))->save();

					$operation->set(array(
						'profit'=>$atk->income*($operation->crop_lost/100) - $operation->total_costs
					))->save();

					$operation->set(array(
						'rentability'=>$operation->total_costs>0 ? ($operation->profit/$operation->total_costs)*100 : 0
					))->save();
				}

			}

			$atk_production_incomes = 0;
			foreach($atk->seeds as $seed){
				foreach($seed->productions as $production){
					foreach($handbook_version_rows as $row){

						if(
								$row->nomenclature_model=='productionclass' &&
								$row->nomenclature_id==$production->productionclass->id() && (

											$row->planned_price_units==$production->bio_crop_units->order ||
											(  in_array($production->bio_crop_units->order,array(1,2,3,4)) && in_array($row->planned_price_units,array(1,2,3,4)) )



										)

							){

								if($production->bio_crop_units->order != $row->planned_price_units){
									if($production->bio_crop_units->order==1) {
										if($row->planned_price_units==2)$row->planned_price *= 10;
										if($row->planned_price_units==3)$row->planned_price *= 1000;
										if($row->planned_price_units==4)$row->planned_price *= 1000000;

									}
									if($production->bio_crop_units->order==2) {
										if($row->planned_price_units==1)$row->planned_price *= 0.1;
										if($row->planned_price_units==3)$row->planned_price *= 100;
										if($row->planned_price_units==4)$row->planned_price *= 100000;

									}
									if($production->bio_crop_units->order==3) {
										if($row->planned_price_units==1)$row->planned_price *= 0.001;
										if($row->planned_price_units==2)$row->planned_price *= 0.01;
										if($row->planned_price_units==4)$row->planned_price *= 1000;

									}
									if($production->bio_crop_units->order==4) {
										if($row->planned_price_units==1)$row->planned_price *= 0.000001;
										if($row->planned_price_units==2)$row->planned_price *= 0.00001;
										if($row->planned_price_units==3)$row->planned_price *= 0.001;

									}
								}

								if(!$seed->disabled){
									$incomes = $row->planned_price * $production->calc_crop;
									$atk_production_incomes += $incomes;
								}

								if($update){$production->set(array(
									'price'=>$row->planned_price,
								))->save();}

								break;
						}
					}
				}

			}

			$atk->set(array(
				'income'=>$atk_production_incomes,
			));

			$result = array(
                'inputs'=>(float)$total_wastes, 
                'income'=>(float)$atk->income, 
                'profit'=>$atk->income - $total_wastes, 
                'rentability'=>($total_wastes ? (($atk->income - $total_wastes)/$total_wastes)*100 : 0)
            );
            
			if($update){
				$atk->set($result);
				if($set_new_hv)$atk->handbook_version = $handbook_version_id;
				$atk->save();
			}

			return $result;


		}


    }

    public function get_cultures_tree($license_id){

        $exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $license_id);
        $exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $license_id);
        $exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
        $cultures =	Jelly::factory('glossary_culturegroup')->get_tree($license_id, true, $exclude);

        $farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);
        $periods = Session::instance()->get('periods');
        if(!count($periods)) $periods = array(-1);

		$names = Jelly::select('client_planning_atk')->with('culture')
                                                     ->where('deleted', '=', false)
                                                     ->and_where('license', '=', $license_id)
                                                     ->and_where('farm', 'IN', $farms)
                                                     ->and_where('period', '=', $periods[0])
                                                     ->order_by('name', 'asc')->execute()->as_array();

        for($i=0; $i<count($cultures); $i++){
            if(substr($cultures[$i]['id'], 0, 1)=='g'){
                $cultures[$i]['children_n'] = array();
                foreach($cultures[$i]['children_g'] as &$ch) if(substr($ch, 0, 1)=='n') $ch = 'g'.$ch;
            }else{
                $cultures[$i]['id'] = 'g'.$cultures[$i]['id'];
                $cultures[$i]['children_n'] = $this->get_children_ids(substr($cultures[$i]['id'], 2), $names);
            }
        }

        return $cultures;
    }





    private function get_children_ids($culture_id, $names){
        $res = array();

        foreach($names as $n){
            if($n[':culture:_id']==$culture_id) $res[] = 'n'.$n['_id'];
        }

        return $res;
    }





	public function get_tree($license_id){

		$res = array();

        $farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);
        $periods = Session::instance()->get('periods');
        if(!count($periods)) $periods = array(-1);

        $cultures =	Jelly::factory('glossary_culture')->get_tree($license_id);
        $np = array(); $wp = array();
        foreach ($cultures as $culture) {
            if($culture['parent']) $wp[ ] = $culture;
            else $np[] = $culture;
        }
        $cultures = array_merge($np, $wp);


		$names_unsorted = Jelly::select('client_planning_atk')->with('culture')->with('atk_status')->with('farm')
                                                     ->where('deleted', '=', false)
                                                     ->and_where('license', '=', $license_id)
                                                     ->and_where('farm', 'IN', $farms)
                                                     ->and_where('period', '=', $periods[0])
                                                     ->order_by('name', 'asc')->execute()->as_array();

		//для группировки по фермам

		$n_paths = array();
		for ($i=0;$i<count($names_unsorted);$i++){
			$n_paths[(string)($i)] = $names_unsorted[$i][':farm:path'].$names_unsorted[$i][':farm:_id'].'/';
		}
		asort($n_paths);

		$names = array();
		$id_order = array();
		$str_farms_path = array();
		foreach($n_paths as $i => $path){
			$path_farms = explode('/', $path);
			$path_farms_names = array();
			for($j=1;$j<count($path_farms)-1;$j++){
				$farm = Jelly::select('farm',(int)$path_farms[$j]);
				array_push($path_farms_names, $farm->name);
			}
			$str_farms_path[(string)($i)] = implode(', ', $path_farms_names);
			$names_unsorted[(int)($i)]['str_farm_path'] = $str_farms_path[(string)($i)];
			array_push($names, $names_unsorted[(int)($i)]);
			array_push($id_order, $names_unsorted[(int)($i)]['_id']);
		}
		// для группировки по фермам

		foreach($cultures as $group){
			$items = array();
			foreach($names as $name){
				if($name[':culture:_id']==substr($group['id'], 1)){ $items[] = $name; }
			}

			foreach($items as $item) {
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
                    'title'    => $item['name'].'</div>'.
                                  '<div style="color: #666666; width: auto; height: 28px; font-size:13px; margin-right:-3px; display:-webkit-box; -webkit-box-orient: horizontal;">'.
                                        (((int)$item['copied_from_farm'] && (int)$item['copied_from_period']) ? '<div class="zamo4ek">&nbsp;</div>' : '').
                                        ($item[':atk_status:_id'] ? '<div style="padding-top:4px;">'.$item[':atk_status:name'].'</div>' : '').
                                  '</div><div>',
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':culture:_id'] ? 'gn'.$item[':culture:_id'] : '',
					'color'    => $item['color'],
					'farm_path'	=> $item['str_farm_path'],
					'parent_color' => $item[':culture:color'] ? $item[':culture:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF'),
                    'copied_from' => array('farm'=>(int)$item['copied_from_farm'], 'period'=>(int)$item['copied_from_period']),
					'handbook_version' => $item['handbook_version']
				);
			}
		}

		//для группировки по фермам
		$final = array();
                foreach($id_order as $i){
					$found = NULL;
                    foreach($res as $item){
                        if($item['id']=='n'.$i){
                           $found = $item;
                           break;
                        }
                    }
                    if ($found) array_push($final, $found);
                }
		return $final;
		//для группировки по фермам
	}





    public function get_simple_tree($license_id, $farm, $period, $culture, $handbook_version){
		$res = array();
		$names = Jelly::select('client_planning_atk')->with('atk_status')
                                                     ->where('deleted', '=', false)
                                                     ->and_where('license', '=', $license_id)
                                                     ->and_where('farm', '=', $farm)
                                                     ->and_where('period', '=', $period)
                                                     ->and_where('culture', '=', $culture)
                                                     ->and_where('atk_status', '=', 3)
                                                     ->order_by('name', 'asc')->execute()->as_array();

        foreach($names as $item){
            $res[] = array(
                'id'	   => 'n'.$item['_id'],
                'title'    => '<div style="float: left;">'.$item['name'].'</div></div>'.
                              '<div style="color: #666; height: 28px; margin-top:-2px; font-size:13px; margin-right:-3px;">'.
                                    ($item[':atk_status:_id'] ? '<div style="padding-top:4px;">'.$item[':atk_status:name'].'</div>' : '').
                              '</div><div>',
                'clear_title' => $item['name'],
                'is_group' => true,
                'is_group_realy' => true,
                'level'	   => 0,
                'children_g' => array(),
                'children_n' => array(),
                'parent'   => '',
                'color'    => $item['color'],
                'parent_color' => $item['color'],
                'finances' => $this->get_atk_finances($item['_id'], $handbook_version)
            );
        }
		return $res;
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





    public function copyAtk($atk, $farm, $period){
        $atk = Jelly::select('client_planning_atk', (int)$atk);
        if(!$atk instanceof Jelly_Model || !$atk->loaded()) return;


        $new_atk = Jelly::factory('client_planning_atk');
        $arr = $atk->as_array();
        unset($arr['id']);unset($arr['_id']);
        $new_atk->set($arr);
        $new_atk->name = UTF8::str_ireplace(' копия', '', $atk->name).' копия';
        $new_atk->farm = $farm;
        $new_atk->period = $period;
        $new_atk->copied_from_farm = $atk->farm->id()!=$farm ? $atk->farm->id() : 0;
        $new_atk->copied_from_period = $atk->farm->id()!=$farm ? $atk->period->id() : 0;
        $new_atk->culture = $atk->culture->id();
        $new_atk->seeds = array();
        $new_atk->operations = array();
        $new_atk->save();


        // Проверяем культуру
        $t_culture = Jelly::select('client_handbook')->where('model', '=', 'glossary_culture')->where('item', '=', $atk->culture->id())->where('period', '=', $period)->where('farm', '=', $farm)->load();

        if(!($t_culture instanceof Jelly_Model) or !$t_culture->loaded())
        {
       		$c = Jelly::factory('client_handbook');
       		$c->model 	= 'glossary_culture';
       		$c->item  	= $atk->culture->id();
       		$c->period  = $period;
       		$c->farm	= $farm;
       		$c->deleted = 0;
       		$c->update_date = time();
       		$c->license = Auth::instance()->get_user()->license->id();
       		$c->save();

       		unset($c);

       		// Так же добавим родителей
       		if($atk->culture->group->id())
       		{
			   $c_parent = Jelly::select('glossary_culturegroup', (int)$atk->culture->group->id());

			   while($c_parent and $c_parent instanceof Jelly_Model and $c_parent->loaded())
			   {
			   		$t_group = Jelly::select('client_handbook')->where('model', '=', 'glossary_culturegroup')->where('item', '=', $c_parent->id())->where('period', '=', $period)->where('farm', '=', $farm)->load();

			   		if(!$t_group instanceof Jelly_Model or !$t_group->loaded())
			   		{
		   				$c = Jelly::factory('client_handbook');
			       		$c->model 	= 'glossary_culturegroup';
			       		$c->item  	= $c_parent->id();
			       		$c->period  = $period;
			       		$c->farm	= $farm;
			       		$c->deleted = 0;
			       		$c->update_date = time();
			       		$c->license = Auth::instance()->get_user()->license->id();
			       		$c->save();

			       		unset($c);
			   		}

			   		if($c_parent->parent->id())
			   		{
		   				$c_parent = Jelly::select('glossary_culturegroup', (int)$c_parent->parent->id());
			   		}
			   		else
			   		{
		   				unset($c_parent);
		   				$c_parent = null;
			   		}
			   }

       		}
		}

        foreach($atk->seeds as $seed){
            $new_atk_seed = Jelly::factory('Client_Planning_Atk2Seed');
            $arr = $seed->as_array();
            unset($arr['id']);unset($arr['_id']);
            $new_atk_seed->set($arr);
            $new_atk_seed->atk = $new_atk;
			$new_atk_seed->productions = array();
            $new_atk_seed->save();

			foreach($seed->productions as $production){
				$new_atk_seed_production = Jelly::factory('Client_Planning_AtkSeed2Production');
				$arr = $production->as_array();
				unset($arr['id']);unset($arr['_id']);
				$new_atk_seed_production->set($arr);
				$new_atk_seed_production->atk = $new_atk;
				$new_atk_seed_production->atk_seed = $new_atk_seed;
				$new_atk_seed_production->save();
			}
        }

        foreach($atk->operations as $operation){
            $new_atk_operation = Jelly::factory('Client_Planning_Atk2Operation');
            $arr = $operation->as_array();
            unset($arr['id']);unset($arr['_id']);
            $new_atk_operation->set($arr);
            $new_atk_operation->atk = $new_atk;
            $new_atk_operation->materials = array();
            $new_atk_operation->technics = array();
            $new_atk_operation->personal = array();
            $new_atk_operation->save();

            foreach($operation->materials as $material){
                $new_atk_operation_material = Jelly::factory('Client_Planning_AtkOperation2Material');
                $arr = $material->as_array();
                unset($arr['id']);unset($arr['_id']);
                $new_atk_operation_material->set($arr);
                $new_atk_operation_material->atk = $new_atk;
                $new_atk_operation_material->atk_operation = $new_atk_operation;
                $new_atk_operation_material->save();
            }

			foreach($operation->personal as $personal){
                $new_atk_operation_personal = Jelly::factory('Client_Planning_AtkOperation2Personal');
                $arr = $personal->as_array();
                unset($arr['id']);unset($arr['_id']);
                $new_atk_operation_personal->set($arr);
                $new_atk_operation_personal->atk = $new_atk;
                $new_atk_operation_personal->atk_operation = $new_atk_operation;
                $new_atk_operation_personal->save();
            }

            foreach($operation->technics as $technic){
                $new_atk_operation_technic = Jelly::factory('Client_Planning_AtkOperation2Technic');
                $arr = $technic->as_array();
                unset($arr['id']);unset($arr['_id']);
                $new_atk_operation_technic->set($arr);
                $new_atk_operation_technic->atk = $new_atk;
                $new_atk_operation_technic->atk_operation = $new_atk_operation;
				$new_atk_operation_technic->mobile_block = array();
				$new_atk_operation_technic->trailer_block = array();
				$new_atk_operation_technic->aggregates_block = array();
                $new_atk_operation_technic->save();

				foreach($technic->mobile_block as $mobile){
					$new_atk_operation_technic_mobile = Jelly::factory('Client_Planning_AtkOperationTechnicMobileBlock');
					$arr = $mobile->as_array();
					unset($arr['id']);unset($arr['_id']);
					$new_atk_operation_technic_mobile->set($arr);
					$new_atk_operation_technic_mobile->atk = $new_atk;
					$new_atk_operation_technic_mobile->atk_operation = $new_atk_operation;
					$new_atk_operation_technic_mobile->atk_operation_technic = $new_atk_operation_technic;
					$new_atk_operation_technic_mobile->save();
				}

				foreach($technic->trailer_block as $trailer){
					$new_atk_operation_technic_trailer = Jelly::factory('Client_Planning_AtkOperationTechnicTrailerBlock');
					$arr = $trailer->as_array();
					unset($arr['id']);unset($arr['_id']);
					$new_atk_operation_technic_trailer->set($arr);
					$new_atk_operation_technic_trailer->atk = $new_atk;
					$new_atk_operation_technic_trailer->atk_operation = $new_atk_operation;
					$new_atk_operation_technic_trailer->atk_operation_technic = $new_atk_operation_technic;
					$new_atk_operation_technic_trailer->save();
				}

				foreach($technic->aggregates_block as $aggregate){
					$new_atk_operation_technic_aggregate = Jelly::factory('Client_Planning_AtkOperationTechnicAggregateBlock');
					$arr = $aggregate->as_array();
					unset($arr['id']);unset($arr['_id']);
					$new_atk_operation_technic_aggregate->set($arr);
					$new_atk_operation_technic_aggregate->atk = $new_atk;
					$new_atk_operation_technic_aggregate->atk_operation = $new_atk_operation;
					$new_atk_operation_technic_aggregate->atk_operation_technic = $new_atk_operation_technic;
					$new_atk_operation_technic_aggregate->save();
				}
            }

        }

		return $new_atk;
    }





	public function get_table($license_id){

		$data = array();

        $farms = Jelly::factory('farm')->get_session_farms();
        $periods = Session::instance()->get('periods');
        if (!count($periods)) {
            $periods = array(-1);
        }
		$atk_list = Jelly::select('client_planning_atk')->with('culture')
                                                        ->where('deleted', '=', false)
                                                        ->and_where('license', '=', $license_id)
                                                        ->and_where('farm', 'IN', $farms)
                                                        ->and_where('period', '=', $periods[0])
                                                        ->order_by('handbook_version', 'asc')->order_by('name', 'asc')->execute();

        foreach ($atk_list as $atk) {

            $farm =& $data['farm'][$atk->farm->get('_id')];
            $farm['id'] = $atk->farm->get('_id');
            $farm['name'] = $atk->farm->get('name');
            $farm['color'] = $atk->farm->get('color');

            $farm['inputs'] = array_key_exists('inputs', $farm) ? $farm['inputs'] + $atk->get('inputs') : $atk->get('inputs');
            $farm['income'] = array_key_exists('income', $farm) ? $farm['income'] + $atk->get('income') : $atk->get('income');
            $farm['profit'] = array_key_exists('profit', $farm) ? $farm['profit'] + $atk->get('profit') : $atk->get('profit');

            $culture =& $farm['culture'][$atk->culture->get('_id')];
            $culture['id'] = $atk->culture->get('_id');
            $culture['name'] = $atk->culture->get('name');
            $culture['color'] = $atk->culture->get('color');

            $culture['inputs'] = array_key_exists('inputs', $culture) ? $culture['inputs'] + $atk->get('inputs') : $atk->get('inputs');
            $culture['income'] = array_key_exists('income', $culture) ? $culture['income'] + $atk->get('income') : $atk->get('income');
            $culture['profit'] = array_key_exists('profit', $culture) ? $culture['profit'] + $atk->get('profit') : $atk->get('profit');


            $item =& $culture['atk'][$atk->get('_id')];
            $item['id'] = $atk->get('_id');
            $item['name'] = $atk->get('name');
            $item['type'] = $atk->atk_type->name;
            $item['locked'] = !is_null($atk->copied_from_farm->id()) && !is_null($atk->copied_from_period->id());

            $item['color'] = $atk->get('color');
			$item['handbook_version'] = $atk->handbook_version->name;
            $item['status'] = $atk->atk_status->get('name');

            foreach ($atk->seeds as $seed) {
				$productions = array();
				foreach($seed->productions as $production){
					$productions[] = array(
						'bio_crop' => $production->bio_crop,
						'calc_crop_percent' => sprintf('%s%%', $production->calc_crop_percent),
						'calc_crop' => $production->calc_crop,
						'production' => $production->production->name,
						'productionclass' => $production->productionclass->name,
						'price' => number_format($production->price, 2)
					);
				}

                $item['seed'][] = array(
                    'name' => $seed->seed->name,
					'productions' => $productions
                );
            }

            $item['inputs'] = $atk->get('inputs');
            $item['income'] = $atk->get('income');
            $item['profit'] = $atk->get('profit');
            $item['rentability'] = $atk->get('rentability');

            $data['inputs'] = array_key_exists('inputs', $data) ? $data['inputs'] + $atk->get('inputs') : $atk->get('inputs');
            $data['income'] = array_key_exists('income', $data) ? $data['income'] + $atk->get('income') : $atk->get('income');
            $data['profit'] = array_key_exists('profit', $data) ? $data['profit'] + $atk->get('profit') : $atk->get('profit');

        }

        return $data;
	}





	public function get_table_tree($license_id){
        $culture_list = $this->get_cultures_tree($license_id);
        $atk_list = $this->get_atk_tree_with_hversions($license_id);

        $result = array();
        foreach($culture_list as &$culture) {
			$culture['children_n'] = array();
			$culture['is_group_realy'] = true;

			$versions = array();
			foreach($atk_list as $key => &$version){
				if($version['parent']!=$culture['id']) continue;

				$culture['children_g'][] = $version['id'];
				$version['level'] = $culture['level']+1;
				$atks = $version['atks'];
				unset($version['atks']);
				$versions[] = $version;
				foreach($atks as &$atk){
					$atk['level'] = $version['level']+1;
					$versions[] = $atk;
				}
			}

			$result[] = $culture;
			$result = array_merge($result, $versions);
        }

		return $result;
	}



	public function get_atk_tree_with_hversions($license_id){

		$res = array();

        $farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);
        $periods = Session::instance()->get('periods');
        if(!count($periods)) $periods = array(-1);


		$atks = Jelly::select('client_planning_atk')->with('handbook_version')->with('culture')->with('atk_type')
                                                     ->where('deleted', '=', false)
                                                     ->and_where('license', '=', $license_id)
                                                     ->and_where('farm', 'IN', $farms)
                                                     ->and_where('period', '=', $periods[0])
                                                     ->order_by('name', 'asc')->execute()->as_array();

		$result = array();

		foreach($atks as $atk){

			$hv_id = (int)$atk[':handbook_version:_id'];
			$empty_version = !$hv_id;
			$id = $atk[':culture:_id'].'-'.$hv_id;

			if(!isset($result[$id])) $result[$id] = array(
				'id'	   => 'gh'.$id,
				'title'    => $empty_version ? 'Без справочника' : $atk[':handbook_version:name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => 0,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => $atk[':culture:_id'] ? 'gn'.$atk[':culture:_id'] : '',
				'color'    => $empty_version ? '92b7e9' : $atk[':handbook_version:color'],
				'parent_color' => $atk[':culture:color'] ? $atk[':culture:color'] : 'FFFFFF',
				'atks' => array()
			);


			$result[$id]['children_g'][] = 'n'.$atk['_id'];
			$result[$id]['children_n'][] = 'n'.$atk['_id'];
			$result[$id]['atks'][] = array(
				'id'	   => 'n'.$atk['_id'],
				'title'    => $atk['name'],
				'is_group' => true,
				'is_group_realy' => false,
				'level'	   => 1,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => $result[$id]['id'],
				'color'    => $atk['color'],
				'parent_color' => $result[$id]['color'],
				'copied_from' => array('farm'=>(int)$atk['copied_from_farm'], 'period'=>(int)$atk['copied_from_period'])
			);

		}

		return $result;
	}
}