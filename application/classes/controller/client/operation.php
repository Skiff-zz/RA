<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Operation extends AC_Controller
{
	protected $model_name = 'client_operation';
	protected $model_group_name = 'client_operationgroup';

	public $auto_render  = false;


	public function action_tree(){
		$user = Auth::instance()->get_user();
		$extras = Arr::get($_GET, 'with_extras', false);
		$farm = Arr::get($_GET, 'farm', 0);

		if($farm){
			$farms = array($farm);
		}else{
			$farms = Jelly::factory('farm')->get_session_farms();
			if(!count($farms)) $farms = array(-1);
		}


		$data =	Jelly::factory('client_operation')->get_tree($user->license->id(), false, false, $extras, $farms);
		$this->request->response = Json::arr($data, count($data));
	}


    public function action_create($parent_id = 0){
        if(array_key_exists(Jelly::meta('client_operation')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('client_operation')->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }


    public function action_read($id = null){
		return $this->action_edit($id, true);
	}


    public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $id = (int)$id;
            $model = Jelly::select('client_operation')->with('group')->with('stages')->with('cultures')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id){
            $group = Jelly::select('client_operationgroup')->where(':primary_key', '=', $parent_id)->load();

            if(!($group instanceof Jelly_Model) or !$group->loaded()){
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }
        }

		$view = Twig::factory('client/operation/operation');

        if($model){
            $view->model  			    = $model->as_array();
            $view->model['group']       = $model->get('group')->id();
            $view->model['group_name']  = $model->get('group')->name;
            $view->model['group_color'] = $model->get('group')->color;
            $view->model['materials']   = Jelly::factory('client_operations2materials')->prepare_materials($view->model['materials']->as_array());
            $view->model['personal']    = Jelly::factory('client_operations2personal')->prepare_personal($view->model['personal']->as_array());
			if(!$read){
				 $view->model['technics'] = Jelly::factory('client_operations2technics')->prepare_technics($view->model['technics']->as_array());
			}
        }


        if(!$read){
			$view->edit	= true;

            if((int)$parent_id){
				$view->model				= array();
				$view->model['group']		= $parent_id;
				$view->parent_color			= $group->color;
				$view->model['group_name']	= $group->name;
				$view->model['group_color'] = $view->parent_color;
            }
		}


        if($model){
            $view->model['properties']  = $model->get_properties();
        }else{
            if(!(int)$parent_id) $view->model = array();//если не создание и не редактирование
            $view->model['properties']  = Jelly::factory('client_operation')->get_properties();
        }


        if($model || $parent_id){
            $parent = Jelly::select('client_operationgroup', ($parent_id ? $parent_id : $model->group->id()));
            if($parent instanceof Jelly_Model && $parent->loaded()){
                $view->parent_stages = $parent->stages;
                $view->parent_cultures = $parent->cultures;
            }
        }


        $view->crop_seed_units = Jelly::factory('glossary_units')->getUnits('seed_crop_norm');
        $view->crop_szr_units = Jelly::factory('glossary_units')->getUnits('szr_norm_amount');

        $view->salary_units	   = Jelly::factory('glossary_units')->getUnits('personal_payment');
        $view->fuel_units = Jelly::factory('glossary_units')->getUnits('tech_fuel_work');
        $view->personal_names = $this->get_personal_names();

        $view->group_field = 'group';

		if(!$id){
			if($farm_id = Arr::get($_GET, 'selected', '')){

			}else{

				$farms = Jelly::factory('farm')->get_session_farms();
				if (!count($farms))
					$farms = array(-1);

				$farm_id = Session::instance()->get('last_create_farm', '');
				if($farm_id && in_array($farm_id,$farms) ) {
				}else{
					if(count($farms)) {
						$farm_id      = $farms[0];

					}
				}
			}


			$farm = Jelly::select('farm')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					and_where('license', '=', Auth::instance()->get_user()->license->id())->
					load((int)$farm_id);
			if(($farm instanceof Jelly_Model) and $farm->loaded()){
				$view->model['farm'] = $farm;
			}

		}
        

        if($id){
            $atk2operations = Jelly::select('Model_Client_Planning_Atk2Operation')->
                    where('operation', '=', $id)->
                    execute();
            
            $atk_ids = array();
            foreach($atk2operations as $atk2op){
                $atk_ids[] = $atk2op->atk->id();
            }
            
            $atk_ids = array_unique($atk_ids);
			
			if (!count($atk_ids)) $atk_ids = array(-1);
            
            $planculture2fields = Jelly::select('Model_Client_Planning_PlanCulture2Field')->
                    where('client_planning_atk_id', 'IN', $atk_ids)->
                    execute();
            
            $atks_raw = Jelly::select('Model_Client_Planning_Atk')->
                    where('_id', 'IN', $atk_ids)->
                    execute();
            
            $atks = array();
            foreach($atks_raw as $atk_raw){
                $arr = array(
                    'atk'=>$atk_raw,
                    'linked_plans'=>array()
                );
                foreach($planculture2fields as $pc2f){
                    if($pc2f->atk->id() == $atk_raw->id()){
                        $arr['linked_plans'][] = $pc2f->plan->id();
                    }
                }
                
                $atks[] = $arr;
            }
            
            $plans = array();
            foreach($planculture2fields as $pc2f){
                if(!isset($plans[$pc2f->plan->id()])){
                    $plans[$pc2f->plan->id()] = $pc2f->plan;
                }
            }
            $plans_arr = array();
            foreach($plans as $id => $plan){
                $plans_arr[] = array(
                    'plan'=>$plan,
                    'linked_atks'=>array()
                );
            }
            
            foreach($planculture2fields as $pc2f){
                foreach($plans_arr as &$pl){
                    if( strcmp( $pc2f->plan->id(), $pl['plan']->id() ) == 0 ){
                        $pl['linked_atks'][] = $pc2f->atk->id();
                    }
                }
            }
            
            foreach($atks as &$atk){
                $atk['linked_plans'] = implode(',',$atk['linked_plans']);
            }
            
            foreach($plans_arr as &$plan){
                $plan['linked_atks'] = implode(',',$plan['linked_atks']);
                
            }
            
            $view->model['atks'] = $atks;
            $view->model['plans'] = $plans_arr;
            
        }        

		$this->request->response = JSON::reply($view->render());
	}



    public function action_update(){

		if (is_null($user = Auth::instance()->get_user())) return;

		$farm = Arr::get($_POST, 'farm', null);

		$license_id   = Auth::instance()->get_user()->license->id();

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$_POST['period']           = (int)$periods[0];


        if($id = arr::get($_POST, '_id', NULL)){

			$model = Jelly::select($this->model_name, (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');

		}else{
			$model = Jelly::factory($this->model_name);

			if($farm)
            {
                $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int)$farm);
                if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded())
                {
                    Session::instance()->set('last_create_farm', (int)$farm);
                }
                else throw new Kohana_Exception('Хозяйства не существует');
            }
		}

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_operation', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_operation');
		}

        $dates = explode(',', arr::get($_POST, 'operation_date', ''));
        if(isset($dates[0])) $_POST['from_date'] = (int)$dates[0];
        if(isset($dates[1])) $_POST['to_date'] = (int)$dates[1];

		$model->update_date = time();
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->deleted = 0;

        $stages = array(); $cultures = array();
        foreach($_POST as $key => $value){
            if(UTF8::strpos($key, 'stage_')!==false){
                $stages[] = (int)UTF8::str_ireplace('stage_', '', $key);
            }
            if(UTF8::strpos($key, 'culture_')!==false){
                $cultures[] = (int)UTF8::str_ireplace('culture_', '', $key);
            }
        }
        $model->remove('stages', $model->stages);
        $model->remove('cultures', $model->cultures);
        $model->add('stages', $stages);
        $model->add('cultures', $cultures);

		$model->save();


        $parent = Jelly::select('client_operationgroup', (int)$model->group->id());
        if($parent instanceof Jelly_Model && $parent->loaded()){
            $parent->add('stages', $stages);
            $parent->add('cultures', $cultures);
            $parent->save();
        }


        //МАТЕРИАЛЫ
        $materials = @json_decode(arr::get($_POST, 'materials_grid', ''), true);
        if(!$materials) $materials = array();
        Jelly::factory('client_operations2materials')->save_from_grid($materials, $model->id());

        //ТЕХНИКА
        $technics = @json_decode(arr::get($_POST, 'technics_grid', ''), true);
        if(!$technics) $technics = array();
        Jelly::factory('client_operations2technics')->save_from_grid($technics, $model->id());

        //ПЕРСОНАЛ
        $personal = @json_decode(arr::get($_POST, 'personal_grid', ''), true);
        if(!$personal) $personal = array();
        Jelly::factory('client_operations2personal')->save_from_grid($personal, $model->id());


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

		$item_id = $model->id();

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $item_id));
	}


    public function action_move(){

		$target = arr::get($_POST, 'target', '');
        if($target == 'g-2' || $target == '') $target = 'g0';
		$target = mb_substr($target, 0, 1)=='g' || mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='g' || mb_substr($move_ids[$i], 0, 1)=='n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
			$model = Jelly::select('client_operation', (int)$id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error("Запись не найдена");
				return;
			}

			$model->group = $target;
			$model->save();
		}

		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}


    public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='g' ? 'client_operationgroup' : 'client_operation';
			if ($id==-2 || $id=='-2') {

				$items_to_delete = Jelly::select('client_operation')->with('_id')->where('group_id','=',NULL || 0)->execute()->as_array();
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select('client_operation', (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}

			} else {

				$model = Jelly::select($m, (int)$id);

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}

				$model->delete();
			}
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}



    private function get_personal_names(){
        $user = Auth::instance()->get_user();
        $ids = array();
		$data =	Jelly::factory('client_handbook_personalgroup')->get_tree($user->license->id());
        foreach($data as $item){
            if(!$item['is_group_realy'])$ids[] = $item['id_in_glossary'];
        }

		return $ids;
    }

	public function action_copy(){
		$farms = arr::get($_POST, 'farms', '');
        $farms = explode(',', $farms);
        if(!$farms[0]) $farms = array();

        $periods = arr::get($_POST, 'periods', '');
        $periods = explode(',', $periods);
        if(!$periods[0]) $periods = array();

        $operations = arr::get($_POST, 'operations', '');
        $operations = explode(',', $operations);
        if(!$operations[0]) $operations = array();

        if(!count($farms) || !count($periods) || !count($operations)){
            $this->request->response = JSON::error('Необходимо указать хозяйства, периоды и Операции.');
            return;
        }

        foreach($farms as $farm){
            foreach($periods as $period){
                foreach($operations as $operation){
                    Jelly::factory('Client_Operation')->copyOperation($operation, $farm, $period);
                }
            }
        }

        $this->request->response = JSON::success(array('script' => 'Copied', 'url' => null, 'success' => true));
	}

	public function action_get_personal_price(){
		$atk_operation_id = arr::get($_POST,'operation_id',0);
		$atk_operation_personal_id = arr::get($_POST,'personal_id',0);
		if(!$atk_operation_personal_id || !$atk_operation_id){
			$this->request->response = JSON::success(array('script' => 'Given', 'url' => null, 'success' => true, 'price' => 0));
		}else{
			$atk_operation = Jelly::select('client_planning_atk2operation',$atk_operation_id);

			$operation_personal = Jelly::select('client_operations2personal')->
								where('client_operation_id','=',$atk_operation->operation->id())->
								where('personal','=',$atk_operation_personal_id)->
								limit(1)->
								execute();

			$this->request->response = JSON::success(array('script' => 'Given', 'url' => null, 'success' => true, 'price' => $operation_personal->salary));

		}
	}

}



