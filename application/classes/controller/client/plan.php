<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Plan extends AC_Controller
{

	public $auto_render  = false;
    public function action_index(){}



	public function action_tree(){
        $user = Auth::instance()->get_user();
		$data =	Jelly::factory('client_planning_plan')->get_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}



	public function action_create(){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edit(null, false);
    }



	public function action_read($id = null){
        if(!$id)return true;
		return $this->action_edit($id, true);
	}
	
	
	
	public function action_version($id){
		return $this->action_edit($id, false, true);
	}



	public function action_edit($id = null, $read = false, $version = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_planning_plan')->with('cultures')->with('plan_status')->with('period')->with('farm')->with('handbook_version')->where(':primary_key', '=', (int)$id)->load();
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$farms = Jelly::factory('farm')->get_session_farms();
		if(count($farms)!=1 && !$id){ $this->request->response = JSON::error('Для создания плана необходимо указать одно хозяйство.'); return; }


		$view = Twig::factory('client/plan/plan');

		if($model){//если просмотр или редактирование
			$view->model = $model->as_array();
			$view->model['cultures'] = Jelly::select('client_planning_plan2culture')->where('plan', '=', $model->id())->order_by('priority', 'ASC')->execute();
			$view->model['farm'] = $view->model['farm']->as_array();
            $view->model['properties'] = $model->get_properties();
        }

		if(!$id){//если создание
            $farm = Jelly::select('farm', (int)$farms[0]);
            if(!($farm instanceof Jelly_Model) or !$farm->loaded()){ $this->request->response = JSON::error('Указанное хозяйство не найдено.'); return; }

            $periods = Session::instance()->get('periods');
            if(count($periods)!=1 && !$read && $culture_id){ $this->request->response = JSON::error('Для создания плана необходимо указать период.'); return; }
            $period = Jelly::select('client_periodgroup', (int)$periods[0]);
            if(!($period instanceof Jelly_Model) or !$period->loaded()){ $this->request->response = JSON::error('Указанный период не найден.'); return; }

			$view->model = array();
			$view->model['farm'] = $farm->as_array();
            $view->model['period'] = $period;
			$view->model['properties']  = Jelly::factory('client_planning_plan')->get_properties();
		}


        $view->farm_fields = Jelly::select('field')->with('culture')->with('culture_before')->where('deleted', '=', false)->and_where('farm', '=', $view->model['farm']['_id'])->and_where('period', '=', $view->model['period']->id())->execute()->as_array();
		$view->farm_fields_total_square = 0;
        foreach ($view->farm_fields as &$field) {
			$view->farm_fields_total_square += (float)$field['area'];
            $field['coordinates'] = unserialize($field['coordinates']);
			$field['culture_color'] = 'transparent';
			$field['culture_title'] = '';
			$field['predecessor_color'] = $field[':culture:color'];
            $field['predecessor_title'] = $field[':culture:title'];
			
			if($model && !$read){//если редактирование, ищем среди отмеченых полей
				foreach($view->model['cultures'] as $culture){
					$fields = $culture->fields->as_array();
					foreach($fields as $f){
						if($f['field']==$field['_id']){
							$field['culture_color'] = $culture->culture->color;
							$field['culture_title'] = $culture->culture->title;
						}
					}
				}
			}
        }
		
		$view->is_version = false;
		if($version){
            unset($view->model['_id']);
            $view->model['name'] = $view->model['name'].'-1';
			$view->is_version = true;
            //$view->model['handbook_version'] = false;
        }
		
		if(!$read && isset($view->model['handbook_version']) && $view->model['handbook_version']){
			$view->model['linked_atks'] = Jelly::select('Client_Planning_PlanCulture2Field')->
//                    where('atk:deleted', '=', false)->
                    where('plan', '=', $view->model['_id'])->
                    execute();
		}

        $view->plan_statuses = Jelly::select('client_planning_planstatus')->order_by('order')->execute()->as_array();
		$view->edit	= !$read;
		$this->request->response = JSON::reply($view->render());
	}
	
	
	
	
	public function action_table(){
		$user = Auth::instance()->get_user();
		
		$plans =  Jelly::factory('client_planning_plan')->get_table_plans($user->license->id());	
		
		$view = Twig::factory('client/plan/table');
		$view->data = $plans;
		
		$this->request->response = JSON::reply($view->render());
	}
	
	
	
	
	public function action_update(){

        $user = Auth::instance()->get_user();
		
		$farms = Jelly::factory('farm')->get_session_farms();
        $_POST['farm'] = arr::get($_POST, 'farm', $farms[0]);
        $periods = Session::instance()->get('periods');
        $_POST['period'] = arr::get($_POST, 'period', $periods[0]);

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_planning_plan', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_planning_plan');
		}
		
		
		if((int)arr::get($_POST, 'plan_status', 0)==3){
			$check_plan_status = Jelly::select('client_planning_plan')->where('deleted', '=', false)
																->and_where('license', '=', $user->license->id())
																->and_where('farm', '=', (int)arr::get($_POST, 'farm', 0))
																->and_where('period', '=', (int)arr::get($_POST, 'period', 0))
																->and_where('plan_status', '=', 3);
			if($id){
				$check_plan_status = $check_plan_status->and_where('_id', '!=', $id);
			}
			
			$check_plan_status = $check_plan_status->limit(1)->load();
			
			if($check_plan_status instanceof Jelly_Model && $check_plan_status->loaded()){
				$this->request->response = JSON::error('В этом хозяйстве/периоде уже существует утверждённый план.'); return;
			}
		}


		$cultures_grid = @json_decode(arr::get($_POST, 'plan_culture_grid', ''), true);
        if(!$cultures_grid) $cultures_grid = array();
		$cultures = array(); $it = -1;
		
		foreach($cultures_grid as $row){
			
			if(UTF8::strpos($row['rowId'], 'farm_') !== false){
				$cf_count_square = arr::get($row, 'choosen_fields_count_square', '0/0'); $cf_count_square = explode('/', $cf_count_square);
				$_POST['choosen_fields_count']  = (int)arr::get($cf_count_square, 0, 0)+0;
				$_POST['choosen_fields_square'] = (float)arr::get($cf_count_square, 1, 0)+0;
				$_POST['planned_square']		= (float)arr::get($row, 'planned_square', 0)+0;
				
				$_POST['plan_inputs']	   = (float)arr::get($row, 'plan_inputs', 0)+0;
				$_POST['plan_income']	   = (float)arr::get($row, 'plan_income', 0)+0;
				$_POST['plan_profit']	   = (float)arr::get($row, 'plan_profit', 0)+0;
				$_POST['plan_rentability'] = (float)arr::get($row, 'plan_rentability', 0)+0;

				$_POST['actual_inputs']		 = (float)arr::get($row, 'actual_inputs', 0)+0;
				$_POST['actual_income']		 = (float)arr::get($row, 'actual_income', 0)+0;
				$_POST['actual_profit']		 = (float)arr::get($row, 'actual_profit', 0)+0;
				$_POST['actual_rentability'] = (float)arr::get($row, 'actual_rentability', 0)+0;
			}
			
			if(UTF8::strpos($row['rowId'], 'culture_') !== false){
				$it++;
				$cf_count_square = arr::get($row, 'choosen_fields_count_square', '0/0'); $cf_count_square = explode('/', $cf_count_square);
				$cultures[$it] = array(
					'id'					=> arr::get($row, 'rowId', ''),
					'culture'				=> (int)arr::get($row['culture'], 'id', 0),
					'choosen_fields_count'	=> (int)arr::get($cf_count_square, 0, 0)+0,
					'choosen_fields_square' => (float)arr::get($cf_count_square, 1, 0)+0,
					'planned_square'		=> (float)arr::get($row, 'planned_square', 0)+0,
					'priority'				=> (int)arr::get($row, 'priority', 0)+0,
					'plan_inputs'			=> (float)arr::get($row, 'plan_inputs', 0)+0,
					'plan_income'			=> (float)arr::get($row, 'plan_income', 0)+0,
					'plan_profit'			=> (float)arr::get($row, 'plan_profit', 0)+0,
					'plan_rentability'		=> (float)arr::get($row, 'plan_rentability', 0)+0,
					'actual_inputs'			=> (float)arr::get($row, 'actual_inputs', 0)+0,
					'actual_income'			=> (float)arr::get($row, 'actual_income', 0)+0,
					'actual_profit'			=> (float)arr::get($row, 'actual_profit', 0)+0,
					'actual_rentability'	=> (float)arr::get($row, 'actual_rentability', 0)+0,
					'culture_fields'		=> array()
				);
			}
			
			if(UTF8::strpos($row['rowId'], 'field_') !== false){
				$atk = arr::get($row['field'], 'atk', array('id'=>0));
				$cultures[$it]['culture_fields'][] = array(
					'id'					=> arr::get($row, 'rowId', ''),
					'field'					=> (int)arr::get($row['field'], 'id', 0),
					'atk'					=> (int)$atk['id'],
					'field_square'			=> (float)arr::get($row, 'field_square', 0)+0,
					'plan_inputs'			=> (float)arr::get($row, 'plan_inputs', 0)+0,
					'plan_income'			=> (float)arr::get($row, 'plan_income', 0)+0,
					'plan_profit'			=> (float)arr::get($row, 'plan_profit', 0)+0,
					'plan_rentability'		=> (float)arr::get($row, 'plan_rentability', 0)+0,
					'actual_inputs'			=> (float)arr::get($row, 'actual_inputs', 0)+0,
					'actual_income'			=> (float)arr::get($row, 'actual_income', 0)+0,
					'actual_profit'			=> (float)arr::get($row, 'actual_profit', 0)+0,
					'actual_rentability'	=> (float)arr::get($row, 'actual_rentability', 0)+0
				);
			}
			
		}

        $_POST['plan_status'] = (int)$_POST['plan_status'];
        $_POST['handbook_version'] = (int)$_POST['handbook_version'];
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->deleted = 0;
		$model->save();
		$plan_id = $model->id();


        //КУЛЬТУРЫ
        Jelly::factory('client_planning_plan2culture')->save_from_grid($cultures, $plan_id);

		if((int)arr::get($_POST, 'plan_status', 0)==3){
			Jelly::factory('client_planning_plan')->update_fields_culture((int)$user->license->id(), (int)arr::get($_POST, 'farm', 0), (int)arr::get($_POST, 'period', 0), $cultures);
		}
		
		if(arr::get($_POST, 'handbook_version_update_clicked', false) && $_POST['handbook_version']){
			foreach($cultures as $culture){
				foreach($culture['culture_fields'] as $fld){
					if($fld['atk']) Jelly::factory('client_planning_atk')->get_atk_finances((int)$fld['atk'], $_POST['handbook_version'], true, true);
				}
			}
		}

        // Допполя
        $add = array();

        // Удаляем старые
        $properties = $model->get_properties();
        foreach($properties as $property_id => $property){
            if(!array_key_exists('property_'.$property_id, $_POST)){
                $model->delete_property($property_id);
            }
        }

        //Новые допполя
        foreach($_POST as $key => $value){
            if(UTF8::strpos($key, 'insert_property_') !== false){
                $property_id = (int)UTF8::str_ireplace('insert_property_', '', $key);
                $add[$_POST['name_insert_'.$property_id]] = $_POST['insert_property_'.$property_id];
            }
        }

        foreach($add as $key => $value){
            $model->set_property(0, $key, $value);
        }

        // Старые допполя
        foreach($_POST as $key => $value){
            if(UTF8::strpos($key, 'property_') !== false){
                $id = (int)UTF8::str_ireplace('property_', '', $key);
                if(array_key_exists('property_'.$id.'_label', $_POST)){
                      $model->set_property($id, $_POST['property_'.$id.'_label'], $_POST['property_'.$id]);
                }
            }
        }

		

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $plan_id));
	}
	
	
	
	
	public function action_copy(){
//		$farms = arr::get($_POST, 'farms', '');
//		$farms = explode(',', $farms);
//		if(!$farms[0]) $farms = array();

        $periods = arr::get($_POST, 'periods', '');
        $periods = explode(',', $periods);
        if(!$periods[0]) $periods = array();

        $plans = arr::get($_POST, 'plans', '');
        $plans = explode(',', $plans);
        if(!$plans[0]) $plans = array();

//        if(!count($farms) || !count($periods) || !count($plans)){
		if(!count($periods) || !count($plans)){
//			$this->request->response = JSON::error('Необходимо указать хозяйства, периоды и планы.');
			$this->request->response = JSON::error('Необходимо указать периоды и планы.');
            return;
        }

//		foreach($farms as $farm){
			foreach($periods as $period){
				foreach($plans as $plan){
//					Jelly::factory('client_planning_plan')->copyPlan($plan, $farm, $period);
					Jelly::factory('client_planning_plan')->copyPlan($plan, $period);
				}
			}
//		}

        $this->request->response = JSON::success(array('script' => 'Copied', 'url' => null, 'success' => true));
	}

	


    public function action_get_cultures_tree(){
        $is_group = arr::get($_GET, 'is_group', false);
        $farm = arr::get($_GET, 'farm', 0);
        $period = arr::get($_GET, 'period', 0);
        $user = Auth::instance()->get_user();

        $atks = Jelly::select('client_planning_atk')->where('deleted', '=', false)
													->and_where('atk_status', '=', 3)
                                                    ->and_where('license', '=', $user->license->id())
                                                    ->and_where('farm', '=', $farm)
                                                    ->and_where('period', '=', $period)->execute()->as_array();
        $cultures = array();
        foreach($atks as $atk){
            $cultures[] = 'n'.$atk['culture'];
        }
        $cultures = array_unique($cultures);

        if($is_group){
            $with_cultures = Arr::get($_GET, 'both_trees', false);
            $data =	Jelly::factory('glossary_culturegroup')->get_tree('', $with_cultures);
            if(!$with_cultures){
                foreach($data as $item){
                    foreach($item['children_n'] as $child){
                        if(array_search($child, $cultures)!==false) $cultures[] = $item['id'];
                    }
                }
            }
        }else{
            $data =	Jelly::factory('glossary_culture')->get_tree('');
        }

        for($i=count($data)-1; $i>=0; $i--){
            if(array_search($data[$i]['id'], $cultures)===false){
                array_splice($data, $i, 1);
            }else{
                if($data[$i]['parent']) $cultures[] = $data[$i]['parent'];
            }
        }

        $this->request->response = Json::arr($data, count($data));
    }



    public function action_process_culture(){
        $culture = arr::get($_GET, 'culture', 0);
        $farm = arr::get($_GET, 'farm', 0);
        $period = arr::get($_GET, 'period', 0);
		$handbook_version = arr::get($_GET, 'handbookVersion', 0);
		$edit = arr::get($_GET, 'edit', 0);
        $user = Auth::instance()->get_user();

        if(!$culture || !$farm || !$period) return;

        $fields = Jelly::select('field')->where('deleted', '=', false)
                                        ->and_where('license', '=', $user->license->id())
                                        ->and_where('farm', '=', $farm)
                                        ->and_where('period', '=', $period)->execute()->as_array();

//        foreach($fields as &$field){
//            $numbers = array();
//            if($field['crop_rotation_number']) $numbers[] = $field['crop_rotation_number'];
//            if($field['number']) $numbers[] = $field['number'];
//            if($field['sector_number']) $numbers[] = $field['sector_number'];
//            $field['title'] = implode('.', $numbers).($field['name'] ? ' '.$field['name'] : '');
//        }


        $predecessors = Jelly::select('glossary_predecessor')->with('predecessor')->where('deleted', '=', false)->and_where('culture', '=', $culture)->order_by('outer_mark')->order_by('inner_mark')->execute()->as_array();

        $mark_1 = array(); $mark_2 = array(); $mark_3 = array(); $fields_in_use = array();
        foreach($predecessors as $predecessor){
            $predecessor = array(
                'id' => $predecessor[':predecessor:_id'],
                'title' => $predecessor[':predecessor:title'],
                'color' => $predecessor[':predecessor:color'],
                'outer_mark' => $predecessor['outer_mark'],
                'inner_mark' => $predecessor['inner_mark'],
                'total_area' => 0,
                'fields' => array()
            );

            foreach($fields as $f){
                if($f['culture_before']==$predecessor['id']){
                    $predecessor['fields'][] = $f;
                    $predecessor['total_area'] += $f['area'];
                    $fields_in_use[] = $f['_id'];
                }
            }
			
			if(count($predecessor['fields'])){
				switch($predecessor['outer_mark']){
					case 1: $mark_1[] = $predecessor; break;
					case 2: $mark_2[] = $predecessor; break;
					case 3: $mark_3[] = $predecessor; break;
				}
			}
        }
        $all_predecessors = array('mark_1'=>$mark_1, 'mark_2'=>$mark_2, 'mark_3'=>$mark_3);

        $not_used_fields = array();
        foreach($fields as $f) {
            if(array_search($f['_id'], $fields_in_use)===false){
                $not_used_fields[] = $f;
            }
        }
		
		
		//утверждённые АТК
		$atk = Jelly::select('client_planning_atk')->where('deleted', '=', false)
                                                    ->where('license', '=', $user->license->id())
                                                    ->where('farm', '=', $farm)
                                                    ->where('period', '=', $period)
													->where('culture', '=', $culture)
													->where('atk_status', '=', 3)->execute()->as_array();
		if(count($atk)){
			$atk = $atk[0];
			$atk['finances'] = Jelly::factory('client_planning_atk')->get_atk_finances($atk['_id'], $handbook_version);
		}
		

        $view = Twig::factory('client/plan/fields');
		$view->total_fields_count = count($fields);
        $view->predesessors = $all_predecessors;
        $view->not_used_fields = $not_used_fields;
        $view->farm = Jelly::select('farm', (int)$farm);
        $view->culture = Jelly::select('glossary_culture', (int)$culture);
        $view->edit = $edit;
		$view->atk = $atk;
        $this->request->response = JSON::reply($view->render());
    }

	
	
	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){
			if(mb_substr($del_ids[$i], 0, 1)=='g') continue;
			
			$id = mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];
            $model = Jelly::select('client_planning_plan', (int)$id);

            if(!($model instanceof Jelly_Model) or !$model->loaded())	{
                $this->request->response = JSON::error('Записи не найдены.');
                return;
            }

            $model->delete();
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}


}