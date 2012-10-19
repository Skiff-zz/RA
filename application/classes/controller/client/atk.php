<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Atk extends AC_Controller
{

	public $auto_render  = false;
	public function action_index(){}


	public function action_tree(){
        $user = Auth::instance()->get_user();
		$data =	Jelly::factory('client_planning_atk')->get_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}



    public function action_simple_tree(){
        $culture = arr::get($_GET, 'culture', 0);
        $farm = arr::get($_GET, 'farm', 0);
        $period = arr::get($_GET, 'period', 0);
        $handbook_version = arr::get($_GET, 'handbook_version', 0);
        $user = Auth::instance()->get_user();

        $data =	Jelly::factory('client_planning_atk')->get_simple_tree($user->license->id(), $farm, $period, $culture, $handbook_version);
		$this->request->response = Json::arr($data, count($data));
    }



	public function action_cultures_tree(){
        $user = Auth::instance()->get_user();
        $data =	Jelly::factory('client_planning_atk')->get_cultures_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}



	public function action_create($culture_id = 0){
        if(array_key_exists(Jelly::meta('client_planning_atk')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('client_planning_atk')->primary_key()]);

		if(!$culture_id){ $this->request->response = JSON::error('Для создания АТК необходимо указать культуру.'); return; }
        return $this->action_edit(null, false, $culture_id);
    }



	public function action_read($id = null){
        if(!$id)return true;
		return $this->action_edit($id, true);
	}



    public function action_version($id){
		return $this->action_edit($id, false, false, true);
	}




	public function action_version_from_plan($id){
		$id = explode('_', $id);
		return $this->action_edit($id[0], false, false, true, true, $id[1]);
	}



	public function action_edit($id = null, $read = false, $culture_id = false, $version = false, $from_plan = false, $plan_hv=0){

        $model = null;

        if($id){
            $model = Jelly::select('client_planning_atk')->with('operations')
//					->with('atk_type')
//					->with('culture')
//					->with('handbook_version')
//					->with('period')
//					->with('atk_status')
//					->with('farm')
					->where(':primary_key', '=', (int)$id)->load();
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }else{
			if(!$culture_id && !$read){ $this->request->response = JSON::error('Для создания АТК необходимо указать культуру.'); return; }
		}

		if(!$read){
			$periods = Session::instance()->get('periods');
			if(count($periods)!=1 && !$read && $culture_id){ $this->request->response = JSON::error('Для создания АТК необходимо указать период.'); return; }
			$period = Jelly::select('client_periodgroup', (int)$periods[0]);
			if(!($period instanceof Jelly_Model) or !$period->loaded()){ $this->request->response = JSON::error('Указанный период не найден.'); return; }


			$farms = Jelly::factory('farm')->get_session_farms();
			if(count($farms)!=1 && !$read && $culture_id){ $this->request->response = JSON::error('Для создания АТК необходимо указать одно хозяйство.'); return; }
			$farm = Jelly::select('farm', (int)$farms[0]);
			if(!($farm instanceof Jelly_Model) or !$farm->loaded()){ $this->request->response = JSON::error('Указанное хозяйство не найдено.'); return; }


			// Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
			if((int)$culture_id){
				$culture = Jelly::select('glossary_culture')->where(':primary_key', '=', $culture_id)->load();
				if(!($culture instanceof Jelly_Model) or !$culture->loaded()){ $this->request->response = JSON::error('Культура не найдена!'); return; }
			}
		}

		$view = Twig::factory('client/atk/atk');

		if($model){//если просмотр или редактирование
			$view->model = $model->as_array();
            $view->model['operations'] = Jelly::factory('client_planning_atk2operation')->prepare_operations($view->model['operations']);
            $view->model['properties'] = $model->get_properties();

            if($view->model['copied_from_farm']->id() || $view->model['copied_from_period']->id()){
                $view->model['period']->set(array('name'=>$view->model['period']->name.'  (скопировано с "'.$view->model['copied_from_period']->name.'")'));
                $view->model['farm']->set(array('name'=>$view->model['farm']->name.'  (скопировано с "'.$view->model['copied_from_farm']->name.'")'));
            }
        }

		if((int)$culture_id){//если создание
			$view->model = array();
			$view->model['culture'] = $culture;
			$view->model['farm'] = $farm->as_array();
            $view->model['period'] = $period;
			$view->model['properties']  = Jelly::factory('client_planning_atk')->get_properties();
		}


		$view->model['culture_operations'] = Jelly::select('operations2cultures')->where('culture_id', '=', $view->model['culture']->id())->execute()->as_array();
		$view->bio_units = Jelly::factory('glossary_units')->getUnits('seed_crop_norm'); 
		if(!$read){
			$view->amount_units = Jelly::factory('glossary_units')->getUnits('amount');
			$view->amount_units_seed = Jelly::factory('glossary_units')->getUnits('amount_seed');
			$view->szr_units = Jelly::factory('glossary_units')->getUnits('szr_norm_amount');
			$view->fuel_units = Jelly::factory('glossary_units')->getUnits('tech_fuel_work');
			$view->atk_types = Jelly::select('client_planning_atktype')->order_by('order')->execute()->as_array();
			$view->atk_statuses = Jelly::select('client_planning_atkstatus')->order_by('order')->execute()->as_array();
		}
		$view->edit	= !$read;
		$view->from_plan = $from_plan;
		$view->plan_hv = $plan_hv;
		$view->is_version = false;

        if($version){
            unset($view->model['_id']);
            $view->model['name'] = $view->model['name'].'-1';
			$view->is_version = true;
            //$view->model['handbook_version'] = false;
        }

		if(isset($view->model['handbook_version']) && $view->model['handbook_version'] && !$read){

            $view->model['linked_operations'] = Jelly::select('Model_Client_Planning_PlanCulture2Field')->
				with('plan')->
				where('atk', '=', (int)$id)->
				execute();
            
            $view->model['linked_plans'] = Jelly::select('Model_Client_Planning_PlanCulture2Field')->
				with('plan')->
				where('atk', '=', (int)$id)->
				execute();

		}
		
		//new AC_Profiler();

		$this->request->response = JSON::reply($view->render());
	}



    public function action_update(){

        $user = Auth::instance()->get_user();

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_planning_atk', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_planning_atk');
		}


        $farms = Jelly::factory('farm')->get_session_farms();
        $_POST['farm'] = arr::get($_POST, 'farm', $farms[0]);
        $periods = Session::instance()->get('periods');
        $_POST['period'] = arr::get($_POST, 'period', $periods[0]);

        $summary = @json_decode(arr::get($_POST, 'atk_summary_grid', ''), true);
        if(!$summary) $summary = array();
        else $summary = $summary[0];
        $_POST['inputs'] = (float)arr::get($summary, 'inputs', 0)+0;
        $_POST['income'] = (float)arr::get($summary, 'income', 0)+0;
        $_POST['profit'] = (float)arr::get($summary, 'profit', 0)+0;
        $_POST['rentability'] = (float)arr::get($summary, 'rentability', 0)+0;
        $_POST['atk_type'] = (int)$_POST['atk_type'];
        $_POST['handbook_version'] = (int)$_POST['handbook_version'];
		$_POST['handbook_version_update_clicked'] = (int)(arr::get($_POST, 'handbook_version_update_clicked', 0));
        $_POST['atk_status'] = (int)$_POST['atk_status'];
		$is_version = (bool)arr::get($_POST, 'is_version', false);


		$model->set($_POST);
        $model->license = $user->license->id();
		$model->deleted = 0;
		if($id){
			if((int)$_POST['handbook_version']>0 && (int)$_POST['handbook_version_update_clicked']>0){
				$model->handbook_version_update_datetime = time();
				$model->outdated = 0;
			}else{}
		}else{
			if($_POST['handbook_version']){
				$model->outdated = 0;
			}else{}
		}
		$model->save();


        //СЕМЕНА
        $seeds = @json_decode(arr::get($_POST, 'atk_seeds_grid', ''), true);
        if(!$seeds) $seeds = array();
        Jelly::factory('client_planning_atk2seed')->save_from_grid($seeds, $model->id(), $is_version);





        //ОПЕРАЦИИ
        $operations = @json_decode(arr::get($_POST, 'atk_operations_grid', ''), true);
        if(!$operations) $operations = array();
        $atk_operations = Jelly::factory('Client_Planning_Atk2Operation')->save_from_grid($operations, $model->id(), $is_version);

        //МАТЕРИАЛЫ
        $materials = @json_decode(arr::get($_POST, 'atk_materials_grid', ''), true);
        if(!$materials) $materials = array();
        for($i=0; $i<count($materials); $i++) $materials[$i]['atk_operation'] = $atk_operations[$i];
        Jelly::factory('Client_Planning_AtkOperation2Material')->save_from_grid($materials, $model->id(), $is_version);

        //ТЕХНИКА
        $technics = @json_decode(arr::get($_POST, 'atk_technics_grid', ''), true);
        if(!$technics) $technics = array();
        for($i=0; $i<count($technics); $i++) $technics[$i]['atk_operation'] = $atk_operations[$i];
        Jelly::factory('client_planning_atkoperation2technic')->save_from_grid($technics, $model->id(), $is_version);

        //ПЕРСОНАЛ
        $personal = @json_decode(arr::get($_POST, 'atk_personal_grid', ''), true);
        if(!$personal) $personal = array();
        for($i=0; $i<count($personal); $i++) $personal[$i]['atk_operation'] = $atk_operations[$i];
        Jelly::factory('Client_Planning_AtkOperation2Personal')->save_from_grid($personal, $model->id(), $is_version);



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

		$from_plan = arr::get($_POST, 'from_plan', 0);
		$finances = array();
		if($from_plan){
			$plan_hv = arr::get($_POST, 'plan_hv', 0);
			$finances = Jelly::factory('client_planning_atk')->get_atk_finances($item_id, $plan_hv);
		}

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $item_id, 'item_name'=>$model->name, 'finances' => $finances));
	}


    public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];
            $model = Jelly::select('Client_Planning_Atk', (int)$id);

            if(!($model instanceof Jelly_Model) or !$model->loaded())	{
                $this->request->response = JSON::error('Записи не найдены.');
                return;
            }

            $model->delete();
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}


    public function action_copy(){
		$farms = arr::get($_POST, 'farms', '');
        $farms = explode(',', $farms);
        if(!$farms[0]) $farms = array();

        $periods = arr::get($_POST, 'periods', '');
        $periods = explode(',', $periods);
        if(!$periods[0]) $periods = array();

        $atks = arr::get($_POST, 'atks', '');
        $atks = explode(',', $atks);
        if(!$atks[0]) $atks = array();

        if(!count($farms) || !count($periods) || !count($atks)){
            $this->request->response = JSON::error('Необходимо указать хозяйства, периоды и АТК.');
            return;
        }

        foreach($farms as $farm){
            foreach($periods as $period){
                foreach($atks as $atk){
                    Jelly::factory('client_planning_atk')->copyAtk($atk, $farm, $period);
                }
            }
        }

        $this->request->response = JSON::success(array('script' => 'Copied', 'url' => null, 'success' => true));
	}


	public function action_table(){
        $view = Twig::factory('client/atk/atk_table');
		$view->data = Jelly::factory('client_planning_atk')->get_table(Auth::instance()->get_user()->license->id());
		$this->request->response = JSON::reply($view->render());
	}


    public function action_get_handbook_mismatches(){
        $farm = arr::get($_POST, 'farm', false);
        $period = arr::get($_POST, 'period', false);
        $handbook_version = arr::get($_POST, 'handbookVersion', false);
		$planned = arr::get($_POST, 'planned', 0);
		$price_key = $planned ? 'planned_price' : 'discount_price';
		$price_units_key = $planned ? 'planned_price_units' : 'discount_price_units';
        $user = Auth::instance()->get_user();

        if(!$farm || !$period || !$handbook_version){ $this->request->response = JSON::success(array('mismatches' => array(), 'success' => true)); return; }

        $handbook_version_name = Jelly::select('client_handbookversionname', (int)$handbook_version);
        if(!$handbook_version_name instanceof Jelly_Model || !$handbook_version_name->loaded()){ $this->request->response = JSON::success(array('mismatches' => array(), 'success' => true)); return; }
        $date = $handbook_version_name->datetime;

        $version_records = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                                  ->and_where('license', '=', $user->license->id())
                                                                  ->and_where('farm', '=', $farm)
                                                                  ->and_where('period', '=', $period)
                                                                  ->and_where('version_date', '=', $date)->execute()->as_array();

        $current_records = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                                  ->and_where('license', '=', $user->license->id())
                                                                  ->and_where('farm', '=', $farm)
                                                                  ->and_where('period', '=', $period)
                                                                  ->and_where('version_date', '=', 0)->execute()->as_array();
        $mismatches = array();
        foreach($current_records as $current_record){

            $found = false;

            foreach($version_records as $version_record){
                if($current_record['nomenclature_model']!=$version_record['nomenclature_model'] || $current_record['nomenclature_id']!=$version_record['nomenclature_id'] ||
                   $current_record['amount_units']!=$version_record['amount_units'] || $current_record[$price_units_key]!=$version_record[$price_units_key]) continue;

                $found = true;

                if($current_record[$price_key]!=$version_record[$price_key])
                    $mismatches[] = array('model'=>$current_record['nomenclature_model'], 'id'=>$current_record['nomenclature_id'], 'amount_units'=>$current_record['amount_units'], 'new_price'=>$current_record[$price_key]);
            }

            if(!$found){
                $mismatches[] = array('model'=>$current_record['nomenclature_model'], 'id'=>$current_record['nomenclature_id'], 'amount_units'=>$current_record['amount_units'], 'new_price'=>$current_record[$price_key]);
            }

        }


        $this->request->response = JSON::success(array('mismatches' => $mismatches, 'success' => true));
    }

	public function action_table_tree(){
        $user = Auth::instance()->get_user();
		$data =	Jelly::factory('client_planning_atk')->get_table_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}


	public function action_get_atk_prices(){
		$handbook_version = arr::get($_POST, 'handbookVersion', false);
		$atks = arr::get($_POST, 'atks', '');
		$atks = explode(',', $atks);
        if(!$atks[0]) $atks = array();

		$result = array();
		foreach($atks as $atk){
			$prices = Jelly::factory('client_planning_atk')->get_atk_finances($atk, $handbook_version);
			$result['atk_'.$atk] = $prices;
		}

		$this->request->response = JSON::success(array('atks' => $result, 'success' => true));
	}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////ТИПЫ АТК/////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function action_treetype(){
		$data =	Jelly::factory('client_planning_atktype')->get_tree();
		$this->request->response = Json::arr($data, count($data));
    }


    public function action_createtype(){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edittype(null, false);
    }

    public function action_readtype($id = null){
        if(!$id)return true;
		return $this->action_edittype($id, true);
    }

    public function action_edittype($id = null, $read = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_planning_atktype')->where(':primary_key', '=', (int)$id)->load();
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/atk/atktype');

		if($model){//если просмотр или редактирование
			$view->model = $model->as_array();
            $view->model['properties'] = $model->get_properties();
        }

		if(!$id){//если создание
			$view->model = array();
			$view->model['properties']  = Jelly::factory('client_planning_atktype')->get_properties();
		}


		$view->edit	= !$read;
		$this->request->response = JSON::reply($view->render());
    }

    public function action_updatetype(){

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_planning_atktype', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_planning_atktype');
		}

		$model->set($_POST);
		$model->save();

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

}