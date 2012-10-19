<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkClone_Atk extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){

		$meta->table('planning_atkclone')
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
				
				'plan_field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_planculture2field',
                        'column'	=> 'client_planning_planculture2field_id',
                        'label'		=> 'Культура плана'
                )),	
				
				'plan_culture'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_plan2culture',
                        'column'	=> 'client_planning_plan2culture_id',
                        'label'		=> 'Культура плана'
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
                    'foreign'	=> 'client_planning_atkclone_atk2seed',
                    'label'	=> 'Семена',
                )),

                'operations' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkclone_atk2operation',
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

	public function get_atk_finances($atk_id, $handbook_version_id, $update = false){
		setlocale(LC_NUMERIC, 'C');
        $result = array('inputs'=>0, 'income'=>0, 'profit'=>0, 'rentability'=>0);

        $atk = Jelly::select('client_planning_atkclone_atk', (int)$atk_id);
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
				and_where('farm', '=', $handbook_versionname->farm->id())->
				and_where('period', '=', $handbook_versionname->period->id())->
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

								$incomes = $row->planned_price * $production->calc_crop;
								$atk_production_incomes += $incomes;

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

			$result = array('inputs'=>(float)$total_wastes, 'income'=>(float)$atk->income, 'profit'=>$atk->income - $total_wastes, 'rentability'=>($total_wastes ? (($atk->income - $total_wastes)/$total_wastes)*100 : 0));
			if($update){$atk->set($result)->save();}

			return $result;


		}


    }
	
	
	public function update_field_atk($plan_culture_id, $plan_culture_field_id, $atk_id, $copy_from_another_clone = false){
		$old_items = Jelly::select('client_planning_atkclone_atk')->where('plan_culture', '=', (int)$plan_culture_id)->where('plan_field', '=', (int)$plan_culture_field_id)->execute();
		foreach($old_items as $old_item) $old_item->delete();
		
		
		//копируем атк
		if(!((int)$atk_id))return false;
		$model_name = $copy_from_another_clone ? 'client_planning_atkclone_atk' : 'client_planning_atk';
		$atk = Jelly::select($model_name, (int)$atk_id);
        if(!$atk instanceof Jelly_Model || !$atk->loaded()) return false;


        $new_atk = Jelly::factory('client_planning_atkclone_atk');
        $arr = $atk->as_array();
        unset($arr['id']);unset($arr['_id']);
        $new_atk->set($arr);
        $new_atk->plan_field = $plan_culture_field_id;
		$new_atk->plan_culture = $plan_culture_id;
        $new_atk->culture = $atk->culture->id();
        $new_atk->seeds = array();
        $new_atk->operations = array();
        $new_atk->save();


        foreach($atk->seeds as $seed){
            $new_atk_seed = Jelly::factory('client_planning_atkclone_atk2seed');
            $arr = $seed->as_array();
            unset($arr['id']);unset($arr['_id']);
            $new_atk_seed->set($arr);
            $new_atk_seed->atk = $new_atk;
			$new_atk_seed->productions = array();
            $new_atk_seed->save();

			foreach($seed->productions as $production){
				$new_atk_seed_production = Jelly::factory('client_planning_atkclone_atkseed2production');
				$arr = $production->as_array();
				unset($arr['id']);unset($arr['_id']);
				$new_atk_seed_production->set($arr);
				$new_atk_seed_production->atk = $new_atk;
				$new_atk_seed_production->atk_seed = $new_atk_seed;
				$new_atk_seed_production->save();
			}
        }

        foreach($atk->operations as $operation){
            $new_atk_operation = Jelly::factory('client_planning_atkclone_atk2operation');
            $arr = $operation->as_array();
            unset($arr['id']);unset($arr['_id']);
            $new_atk_operation->set($arr);
            $new_atk_operation->atk = $new_atk;
            $new_atk_operation->materials = array();
            $new_atk_operation->technics = array();
            $new_atk_operation->personal = array();
            $new_atk_operation->save();

            foreach($operation->materials as $material){
                $new_atk_operation_material = Jelly::factory('client_planning_atkclone_atkoperation2material');
                $arr = $material->as_array();
                unset($arr['id']);unset($arr['_id']);
                $new_atk_operation_material->set($arr);
                $new_atk_operation_material->atk = $new_atk;
                $new_atk_operation_material->atk_operation = $new_atk_operation;
                $new_atk_operation_material->save();
            }

			foreach($operation->personal as $personal){
                $new_atk_operation_personal = Jelly::factory('client_planning_atkclone_atkoperation2personal');
                $arr = $personal->as_array();
                unset($arr['id']);unset($arr['_id']);
                $new_atk_operation_personal->set($arr);
                $new_atk_operation_personal->atk = $new_atk;
                $new_atk_operation_personal->atk_operation = $new_atk_operation;
                $new_atk_operation_personal->save();
            }

            foreach($operation->technics as $technic){
                $new_atk_operation_technic = Jelly::factory('client_planning_atkclone_atkoperation2technic');
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
					$new_atk_operation_technic_mobile = Jelly::factory('client_planning_atkclone_atkoperationtechnicmobileblock');
					$arr = $mobile->as_array();
					unset($arr['id']);unset($arr['_id']);
					$new_atk_operation_technic_mobile->set($arr);
					$new_atk_operation_technic_mobile->atk = $new_atk;
					$new_atk_operation_technic_mobile->atk_operation = $new_atk_operation;
					$new_atk_operation_technic_mobile->atk_operation_technic = $new_atk_operation_technic;
					$new_atk_operation_technic_mobile->save();
				}

				foreach($technic->trailer_block as $trailer){
					$new_atk_operation_technic_trailer = Jelly::factory('client_planning_atkclone_atkoperationtechnictrailerblock');
					$arr = $trailer->as_array();
					unset($arr['id']);unset($arr['_id']);
					$new_atk_operation_technic_trailer->set($arr);
					$new_atk_operation_technic_trailer->atk = $new_atk;
					$new_atk_operation_technic_trailer->atk_operation = $new_atk_operation;
					$new_atk_operation_technic_trailer->atk_operation_technic = $new_atk_operation_technic;
					$new_atk_operation_technic_trailer->save();
				}

				foreach($technic->aggregates_block as $aggregate){
					$new_atk_operation_technic_aggregate = Jelly::factory('client_planning_atkclone_atkoperationtechnicaggregateblock');
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
	
	
	public function delete($key = NULL){
        if(!is_null($key)) return parent::delete($key);

		$atk_clone_id = $this->id();
		parent::delete();
		
		Jelly::delete('client_planning_atkclone_atk2seed')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkseed2production')->where('atk', '=', (int)$atk_clone_id)->execute();
		
		Jelly::delete('client_planning_atkclone_atk2operation')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperation2material')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperation2personal')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperation2technic')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperationtechnicmobileblock')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperationtechnictrailerblock')->where('atk', '=', (int)$atk_clone_id)->execute();
		Jelly::delete('client_planning_atkclone_atkoperationtechnicaggregateblock')->where('atk', '=', (int)$atk_clone_id)->execute();
    }

}