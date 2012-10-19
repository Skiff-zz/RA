<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Format extends AC_Controller
{

	public $auto_render  = false;
	
	public function action_update(){
		
		$_POST['start'] = strtotime(ACDate::convertMonth($_POST['start']));
		$_POST['finish'] = strtotime(ACDate::convertMonth($_POST['finish']));
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		

		$values = Arr::extract($_POST, array('field_name', 'crop_rotation_n', 'field_n', 'sector_n', 'road_name', 'road_n', 'note_name', 'note_n', 'start', 'finish', 'share_alert', 'share_alert_period'));

		try{
			foreach($values as $key => $value){
				Jelly::factory('client_format')->saveValue($user->license->id(), $key, $value);
			}
			$this->update_shares($user->license->id());
			//$this->request->response = JSON::reply("Информация о форматах успешно сохранена");
		} catch(Validate_Exception $e) {
			$this->request->response = JSON::error(implode(' ', $e->array->errors('validate',true)));
		}

		$ctypes = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'ctype_field_') !== false){
				$ctypes[] = array('id'=> str_replace('ctype_field_', '', $key), 'name' => trim($value));
			}
		}
		Jelly::factory('glossary_culturetype')->saveCultureTypes($ctypes);
		
		$atypes = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'atype_field_') !== false){
				$atypes[] = array('id'=> str_replace('atype_field_', '', $key), 'name' => trim($value));
			}
		}
		Jelly::factory('client_planning_atktype')->saveAtkTypes($atypes);

		$this->request->response = JSON::reply("Информация о форматах успешно сохранена");
	}


	public function action_read(){
		return $this->action_edit(true);
	}

	
	public function action_edit($read = false){

		$user = Auth::instance()->get_user();

		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$lic_id = $user->license->id();

		$formats = Jelly::factory('client_format')->get_formats($lic_id);

		$view = Twig::factory('client/format/read');

		$view->culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();
		$view->atk_types = Jelly::select('client_planning_atktype')->execute()->as_array();
		$view->share_alert_periods = Jelly::select('client_sharealertperiod')->execute()->as_array();

		$view->edit = !$read;
		$view->formats = $formats;
		$view->def_val = 1;
		
		if ($formats) {
			$s = $view->formats['start'] ? $view->formats['start'] : $user->license->activate_date;
			$f = $view->formats['finish'] ? $view->formats['finish'] : $user->license->activate_date;
			
			$view->formats['start'] = date('Y-m-d',$s);
			$view->formats['finish'] = date('Y-m-d',$f);
			
			$view->formats['s_year'] = date('Y',$s);
			$view->formats['s_month'] = date('m',$s);
			$view->formats['s_day'] = date('d',$s);
			
			$view->formats['f_year'] = date('Y',$f);
			$view->formats['f_month'] = date('m',$f);
			$view->formats['f_day'] = date('d',$f);
			
			$view->formats['share_alert_period_name'] = Jelly::factory('client_sharealertperiod')->get_name_by_id(isset($formats['share_alert_period']) ? $formats['share_alert_period'] : Model_Client_ShareAlertPeriod::$default_id);
		}
		
		
		$this->request->response = JSON::reply($view->render());
	}


	public function action_checkCultureType($id){
		$in_use = Jelly::select('glossary_culture')->where('type', '=', $id)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();
		$this->request->response = JSON::success(array('success' => !count($in_use)));
	}
	
	public function action_checkAtkType($id){
		$in_use = Jelly::select('client_planning_atk')->where('atk_type', '=', $id)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();
		$this->request->response = JSON::success(array('success' => !count($in_use)));
	}
	
	private function update_shares($license_id){
		$records = Jelly::select('client_share')->where('license', '=', $license_id)->execute();
		foreach($records as $record){
			$record->last_status_update = time()-86400;
			$record->last_alert_show = time()-86400;
			$record->save();
		}
	}

}
