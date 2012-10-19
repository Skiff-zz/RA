<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_HandOrder extends AC_Controller
{

	public $auto_render  = false;
    public function action_index(){}



	public function action_tree(){
		$data =	Jelly::factory('client_work_handorder')->get_tree();
		$this->request->response = Json::arr($data, count($data));
	}
	
	
	public function action_table(){
		$filter = arr::get($_POST, 'filter', '');
		$filter = @json_decode($filter, true);
		if(!$filter) $filter = array('status'=>array(), 'culture'=>array(), 'operation'=>array(), 'field'=>array());
		
		$view = Twig::factory('client/work/planned_order_table');
		$view->data = Jelly::factory('client_work_handorder')->get_table_data($filter);
		$this->request->response = JSON::reply($view->render());
	}



	public function action_create($data_ids){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
		$data_ids = explode('_', $data_ids);
        return $this->action_edit(null, false, array('operation_id'=>$data_ids[0], 'atk_id'=>$data_ids[1], 'plan_id'=>$data_ids[2]));
    }



	public function action_read($id = null){
        if(!$id)return true;
		return $this->action_edit($id, true);
	}


	public function action_edit($id = null, $read = false, $create_data = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_work_handorder')->where(':primary_key', '=', (int)$id)->load();
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/work/hand_order');

		if($model){//если просмотр или редактирование
			$view->model = $model->as_array();
			$view->model['fields'] = Jelly::factory('client_work_handorder')->get_fields_data((int)$id);
        }

		
		//если создание
		if(!$id){
			$view->model = array(
				'atk' => array('_id'=>$create_data['atk_id']),
				'plan' => array('_id'=>$create_data['plan_id']),
				'culture' => Jelly::factory('client_work_plannedorder')->get_culture($create_data['atk_id']),
				'operation' => array('_id'=>$create_data['operation_id']),
				'fields' => Jelly::factory('client_work_plannedorder')->get_plan_fields($create_data['plan_id'], $create_data['atk_id']),
				'plan_inputs' => 0,
				'actual_inputs' => 0,
				'fields_square' => 0,
				'process_square' => 0,
				'processed_square' => 0,
				'order_status' => array('_id'=>2, 'name'=>'Новый наряд'),
				'order_date' => time()
			);
			
			$atk_operation = Jelly::select('client_planning_atk2operation')->where('operation', '=', (int)$create_data['operation_id'])->where('atk', '=', (int)$create_data['atk_id'])->limit(1)->load();
			if($atk_operation instanceof Jelly_Model && $atk_operation->loaded()){
				$view->model['planned_from_date'] = $atk_operation->from_date;
				$view->model['planned_to_date'] = $atk_operation->to_date;
				$view->model['actual_from_date'] = $atk_operation->from_date;
				$view->model['actual_to_date'] = $atk_operation->to_date;
			}
				
			
			for($i=0; $i<count($view->model['fields']); $i++){
				$field = $view->model['fields'][$i];
				$view->model['plan_inputs'] += $field['plan_inputs'];
				$view->model['fields_square'] += $field['field']->area;
				
				$field['order_status'] = array('_id'=>2, 'name'=>'Новый наряд');
				$field['order_date'] = time();
				$field['planned_from_date'] = $view->model['planned_from_date'];
				$field['planned_to_date'] = $view->model['planned_to_date'];
				$field['actual_from_date'] = $view->model['actual_from_date'];
				$field['actual_to_date'] = $view->model['actual_to_date'];
				
				//материалы из атк
				$field['materials'] = Jelly::factory('client_work_plannedorder')->get_materials($field['atk_clone'], $create_data['operation_id'], $field);
				
				//техника из атк
				$technics = Jelly::factory('client_work_plannedorder')->get_technics($field['atk_clone'], $create_data['operation_id'], $field);
				$field['technics'] = Jelly::factory('client_work_plannedorderfield2technic')->prepare_technics_on_create($technics);
				
				//персонал из атк
				$field['personal'] = Jelly::factory('client_work_plannedorder')->get_personals($field['atk_clone'], $create_data['operation_id'], $field);
				
				$view->model['fields'][$i] = $field;
			}
			
		}
		//создание конец
		
		
		$view->seed_norm_units = Jelly::factory('glossary_units')->getUnits('seed_crop_norm');
		$view->szr_norm_units = Jelly::factory('glossary_units')->getUnits('szr_norm_amount');
		$view->fuel_units = Jelly::factory('glossary_units')->getUnits('tech_fuel_work');
		
		$view->edit	= !$read;
		$view->create = !$read && !$id;
		$this->request->response = JSON::reply($view->render());
	}
	
	
	
	public function action_update(){

		$data = arr::get($_POST, 'data', '');
		$data = @json_decode($data, true);
		$user = Auth::instance()->get_user();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		$periods = Session::instance()->get('periods');
		if(!count($farms)){
			throw new Kohana_Exception('No farm specified!');
		}
		if(!count($periods)){
			throw new Kohana_Exception('No period specified!');
		}
//		print_r($data); exit;
		
		$not_delete_fields = array();
		
		foreach($data as $row){	
			
			if(UTF8::strpos($row['rowId'], 'porder') !== false){
				$id = explode('_', $row['rowId']); $id = $id[1];
				
				if($id=='new'){
					$id = false;
					$model = Jelly::factory('client_work_handorder');
				}else{
					$model = Jelly::select('client_work_handorder', (int)$id);
					if(!($model instanceof Jelly_Model) or !$model->loaded()) throw new Kohana_Exception('Record Not Found!');
				}
				
				$date_from = 0; $date_to = 0;
				$dates = explode(' - ', $row['planned_dates']);
				if(isset($dates[0])) $date_from = strtotime($dates[0]);
				if(isset($dates[1])) $date_to = strtotime($dates[1]);
				$model->planned_from_date = (int)$date_from;
				$model->planned_to_date = (int)$date_to;
				
				$date_from = 0; $date_to = 0;
				$dates = explode(' - ', $row['actual_dates']);
				if(isset($dates[0])) $date_from = strtotime($dates[0]);
				if(isset($dates[1])) $date_to = strtotime($dates[1]);
				$model->actual_from_date = (int)$date_from;
				$model->actual_to_date = (int)$date_to;
				
				$model->executor = (int)$row['executor']['id'];
				$model->fields_square = (float)$row['fields_square'];
				$model->process_square = (float)$row['process_square'];
				$model->processed_square = (float)$row['processed_square'];
				$model->atk = $row['name']['atk'];
				$model->plan = $row['name']['plan'];
				$model->culture = $row['name']['culture'];
				$model->operation = $row['name']['operation'];
				$model->name = $row['name']['value'];
				$model->color = $row['name']['color'];
				$model->plan_inputs = (float)$row['plan_inputs'];
				$model->actual_inputs = (float)$row['actual_inputs'];
				$model->order_status = (int)$row['status_date']['status']['id'];
				$model->order_date = (int)$row['status_date']['date'];
				$model->farm = $farms[0];
				$model->period = $periods[0];
				$model->license = $user->license->id();
				$model->deleted = 0;
				$model->save();
				$order_id = $model->id();
			}
			
			
			if(UTF8::strpos($row['rowId'], 'field') !== false){
				$field_id = explode('_', $row['rowId']); $field_id = $field_id[1];
				
				if(UTF8::strpos($field_id, 'new') !== false){
					$field_id = false;
					$field_model = Jelly::factory('client_work_handorder2field');
				}else{
					$field_model = Jelly::select('client_work_handorder2field', (int)$field_id);
					if(!($field_model instanceof Jelly_Model) or !$field_model->loaded()) throw new Kohana_Exception('Field record Not Found!');
				}
				
				$date_from = 0; $date_to = 0;
				$dates = explode(' - ', $row['planned_dates']);
				if(isset($dates[0])) $date_from = strtotime($dates[0]);
				if(isset($dates[1])) $date_to = strtotime($dates[1]);
				$field_model->planned_from_date = (int)$date_from;
				$field_model->planned_to_date = (int)$date_to;
				
				$date_from = 0; $date_to = 0;
				$dates = explode(' - ', $row['actual_dates']);
				if(isset($dates[0])) $date_from = strtotime($dates[0]);
				if(isset($dates[1])) $date_to = strtotime($dates[1]);
				$field_model->actual_from_date = (int)$date_from;
				$field_model->actual_to_date = (int)$date_to;
				
				$field_model->executor = (int)$row['executor']['id'];
				$field_model->hand_order = $order_id;
				$field_model->field = $row['field']['field_id'];
				$field_model->process_square = (float)$row['process_square'];
				$field_model->processed_square = (float)$row['processed_square'];
				$field_model->order_status = (int)$row['status_date']['status']['id'];
				$field_model->order_date = (int)$row['status_date']['date'];
				$field_model->plan_inputs = (float)$row['plan_inputs'];
				$field_model->actual_inputs = (float)$row['actual_inputs'];
				
				$field_model->save();
				$field_id = $field_model->id();
				$not_delete_fields[] = $field_id;
			}
			
			
			if(UTF8::strpos($row['rowId'], 'materials') !== false && UTF8::strpos($row['rowId'], 'title') === false){
				foreach($row as $key => $value){ if(UTF8::strpos($key, 'materials_for') !== false) $materials_data = $value; }
				
				$materials_data = json_decode($materials_data, true);
				if(!$materials_data) $materials_data = array();
				Jelly::factory('client_work_handorderfield2material')->save_from_grid($materials_data, $order_id, $field_id);
			}
			
			if(UTF8::strpos($row['rowId'], 'technics') !== false && UTF8::strpos($row['rowId'], 'title') === false){
				foreach($row as $key => $value){ if(UTF8::strpos($key, 'technics_for') !== false) $technics_data = $value; }
				
				$technics_data = json_decode($technics_data, true);
				if(!$technics_data) $technics_data = array();
				Jelly::factory('client_work_handorderfield2technic')->save_from_grid($technics_data, $order_id, $field_id);
			}
			
			if(UTF8::strpos($row['rowId'], 'personals') !== false && UTF8::strpos($row['rowId'], 'title') === false){
				foreach($row as $key => $value){ if(UTF8::strpos($key, 'personals_for') !== false) $personals_data = $value; }
				
				$personals_data = json_decode($personals_data, true);
				if(!$personals_data) $personals_data = array();
				Jelly::factory('client_work_handorderfield2personal')->save_from_grid($personals_data, $order_id, $field_id);
			}
			
		}
		
		if(count($not_delete_fields)) Jelly::delete('client_work_handorder2field')->where('hand_order', '=', $order_id)->and_where('_id', 'NOT IN', $not_delete_fields)->execute();
		else						  Jelly::delete('client_work_handorder2field')->where('hand_order', '=', $order_id)->execute();

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $order_id));
	}
	
	
	public function action_delete($id = null){
		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){
			$id = $del_ids[$i];
            $model = Jelly::select('client_work_handorder', (int)$id);

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                continue;
            }

            $model->delete();
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}


}