<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Share extends AC_Controller
{
	
	public $auto_render  = false;
	public function action_index(){}
	
	
	
	public function action_share_tree(){
		$user = Auth::instance()->get_user();
		$sort = arr::get($_GET, 'sort', 'shareholder');
		$data = Jelly::factory('client_share')->get_share_tree($user->license->id(), $sort);
		$this->request->response = Json::arr($data, count($data));
	}
	

	
	public function action_date_tree(){
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_share')->get_date_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}

	
	
	public function action_read($id){
		return $this->action_edit($id, true);
	}
	
	
	
	public function action_fields_list($farm_id){
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_share')->get_field_list($user->license->id(), $farm_id);
		$this->request->response = Json::arr($data, count($data));
	}
	
	
	
	public function action_create($shareholder_id){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edit(null, false, $shareholder_id);
    }
	
	
	
	public function action_edit($id = null, $read = false, $shareholder_id = false){
		
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified")); return;
		}

        $item = null;

        if($id){
            $item = Jelly::select('client_share')->with('shareholder')->load((int)$id);
            if(!($item instanceof Jelly_Model) or !$item->loaded()){
                $this->request->response = JSON::error('Запись не найдена.'); return;
			}
        }
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(count($farms)!=1 && !$id){ $this->request->response = JSON::error('Для создания пая необходимо указать одно хозяйство.'); return; }

		$view = Twig::factory('client/share/item');
		

        if($item){	
			$view->model = $item->as_array();
			$view->model['main_properties'] = $item->get_properties('share_main');
			$view->model['conditions_properties'] = $item->get_properties('share_conditions');
			$view->model['registration_properties'] = $item->get_properties('share_registration');
			$view->model['order_properties'] = $item->get_properties('share_order');
        }else{
			$view->model = array();
			$view->model['shareholder'] = Jelly::select('client_shareholder', (int)$shareholder_id);
			$view->model['farm'] = Jelly::select('farm', (int)$farms[0]);
			$view->model['main_properties']  = Jelly::factory('client_share')->get_properties('share_main');
			$view->model['conditions_properties']  = Jelly::factory('client_share')->get_properties('share_conditions');
			$view->model['registration_properties']  = Jelly::factory('client_share')->get_properties('share_registration');
			$view->model['order_properties']  = Jelly::factory('client_share')->get_properties('share_order');
		}
		
		$view->edit	= !$read;
		$this->request->response = JSON::reply($view->render());
		
	}
	
	
	
	public function action_update(){

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_share', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_share');
		}
		
		$_POST['rent_start_date'] = strtotime(ACDate::convertMonth($_POST['rent_start_date']));
		$_POST['rent_end_date'] = strtotime(ACDate::convertMonth($_POST['rent_end_date']));
		$_POST['registration_date'] = strtotime(ACDate::convertMonth($_POST['registration_date']));
		$_POST['order_date'] = strtotime(ACDate::convertMonth($_POST['order_date']));
		$_POST['order_act_date'] = strtotime(ACDate::convertMonth($_POST['order_act_date']));
		$_POST['announce_date'] = strtotime(ACDate::convertMonth($_POST['announce_date']));
		
		if(((int)$_POST['rent_start_date'])<=0 || ((int)$_POST['rent_end_date'])<=0){ 
			$this->request->response = JSON::error('Необходимо задать дату начала и конца аренды.'); return false; 
		}
		
		$user = Auth::instance()->get_user();
        $periods = Session::instance()->get('periods');
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->period = $periods[0];
		$model->last_status_update = time()-86400;
		$model->last_alert_show = time()-86400;
		
		$coordinates = Arr::get($_POST, 'coordinates', false);
		$c_arr = explode(',', $coordinates);
		if(count($c_arr)<8){ $this->request->response = JSON::error('Координаты пая не заданы'); return false; }
		
		$model->save();
		$item_id = $model->id();

		
		$add_blocks = array('main', 'conditions', 'registration', 'order');
		
		foreach($add_blocks as $block){
		
			// Допполя
			$add = array();

			// Удаляем старые
			$properties = $model->get_properties('share_'.$block);
			foreach($properties as $property_id => $property){
				if(!array_key_exists($block.'_property_'.$property_id, $_POST)){
					$model->delete_property('share_'.$block, $property_id);
				}
			}

			//Новые допполя
			foreach($_POST as $key => $value){
				if(UTF8::strpos($key, 'insert_'.$block.'_property_') !== false){
					$property_id = (int)UTF8::str_ireplace('insert_'.$block.'_property_', '', $key);
					$add[$_POST[$block.'_name_insert_'.$property_id]] = $_POST['insert_'.$block.'_property_'.$property_id];
				}
			}

			foreach($add as $key => $value){
				$model->set_property('share_'.$block, 0, $key, $value);
			}

			// Старые допполя
			foreach($_POST as $key => $value){
				if(UTF8::strpos($key, $block.'_property_') !== false){
					$id = (int)UTF8::str_ireplace($block.'_property_', '', $key);
					if(array_key_exists($block.'_property_'.$id.'_label', $_POST)){
						  $model->set_property('share_'.$block, $id, $_POST[$block.'_property_'.$id.'_label'], $_POST[$block.'_property_'.$id]);
					}
				}
			}
		}
		
		$payments_grid = Arr::get($_POST, 'shareholder_payments_grid', false);
		if($payments_grid){
			$payments_grid = @json_decode($payments_grid, true);
			if(!$payments_grid) $payments_grid = array();
			Jelly::factory('client_sharepayment')->save_from_grid($payments_grid, $item_id);
		}

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $item_id));
	}
	
	
	
	
	public function action_table(){
		$sort = arr::get($_GET, 'sort', 'shareholder');
		
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_share')->get_share_grid_data($user->license->id(), $sort);

        $view = Twig::factory('client/share/share_grid');
		$view->data = $data;

		setlocale(LC_NUMERIC, 'C');
        $this->request->response = JSON::reply($view->render());
	}
	

	
	
	public function action_delete(){
		$del_ids = Arr::get($_POST, 'del_ids', '');
		$tree 	 = Arr::get($_POST, 'tree', 'names');
		$deleted = array();
		
		if($del_ids){
			$del_ids = explode(',', $del_ids);
		} else {
			$del_ids = array();
		}
	
		for($i=0; $i<count($del_ids); $i++){
			$id = substr($del_ids[$i], 1);
			$model = false;
			
			if($tree=='groups'){
				if(substr($del_ids[$i], 0, 1)=='g') $model = Jelly::select('Client_ShareholderGroup', (int)$id);
				else								$model = Jelly::select('Client_Shareholder', (int)$id);
			}else{
				if(substr($del_ids[$i], 0, 1)=='s') $model = Jelly::select('Client_Share', (int)$id);
			}
			
			if($model instanceof Jelly_Model && $model->loaded()){
				$model->delete();
				if(!isset($deleted[$model->name()])) $deleted[$model->name()] = array();
				$deleted[$model->name()][] = $model->id();
			}
		}
		
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true, 'deleted' => $deleted ));
	}
	
	
	
	
	public function action_alert(){
		$user = Auth::instance()->get_user();
		$alerts = Jelly::factory('Client_Share')->get_alert_shares($user->license->id());
		$this->request->response = JSON::success(array('script' => 'Alert', 'url' => null, 'success' => true, 'alerts' => $alerts ));
	}
}
