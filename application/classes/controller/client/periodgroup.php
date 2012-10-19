<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_PeriodGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = '';
	protected $model_group_name = 'client_periodgroup';


	public function action_edit($id = null, $read = false, $parent_id = false){

		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		$lic_id = $user->license->id();

		$production = null;

        if($id){
            $production = Jelly::select('client_periodgroup')->with('_id')->load((int)$id);

            if(!($production instanceof Jelly_Model) or !$production->loaded()){
                $this->request->response = JSON::error('Не найдена Запись');
				return;
			}
        }

		$view = Twig::factory('client/period/read_periodgroup');

		if($id){
			$view->id = $id;
			$view->fields_count = Jelly::select('field')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $id)->execute()->count();
			$view->previous_period = $this->get_previous_period($production->start);
		}else{
			$view->fields_count = 0;
			$view->previous_period = array('period'=> false, 'fields_count' => 0);
		}

		if(!$read){
			$view->edit = true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($production ? $production->parent->id() : 0);
			$view->hasChildren = false;
		}



        if($production){

			$view->model = array();
			$view->model['_id'] = $production->_id;
			$view->model['status'] = $production->status;
			$view->model['color'] = $production->color;
			$view->model['name'] = $production->name;
			$s = $production->start;
			$f = $production->finish;
			$view->model['start'] = date('Y-m-d H:i:s',$s);
			$view->model['s_year'] = date('Y',$s);
			$view->model['s_month'] = date('m',$s);
			$view->model['s_day'] = date('d',$s);

			$view->model['finish'] = date('Y-m-d H:i:s',$f);
			$view->model['f_year'] = date('Y',$f);
			$view->model['f_month'] = date('m',$f);
			$view->model['f_day'] = date('d',$f);

        } else {
			$view->model = array();
			$def_start = Jelly::select('client_format')->where('license', '=', $lic_id)->and_where('name', '=', 'start')->execute()->as_array();
			$def_finish = Jelly::select('client_format')->where('license', '=', $lic_id)->and_where('name', '=', 'finish')->execute()->as_array();

			$def_start  = $def_start  ? $def_start[0]['value']  : $user->license->activate_date;
			$def_finish = $def_finish ? $def_finish[0]['value'] : $user->license->activate_date;

			$view->model['start'] = date('Y-m-d H:i:s',$def_start);
			$view->model['s_year'] = date('Y');
			$view->model['s_month'] = date('m',$def_start);
			$view->model['s_day'] = date('d',$def_start);

			$view->model['finish'] = date('Y-m-d H:i:s',$def_finish);
			$view->model['f_year'] = date('Y')+1;
			$view->model['f_month'] = date('m',$def_finish);
			$view->model['f_day'] = date('d',$def_finish);
		}

		$view->model['properties']  = Jelly::factory('client_model_properties')->get_properties('periodgroup_prop', $id); 
        
		$this->request->response = JSON::reply($view->render());
	}

	public function action_setdefaultperiods()
	{
		$ids = trim(Arr::get($_POST, 'ids', ''));

		if($ids == '')
			return;

		$ids = explode(',', $ids);

		if(!count($ids))
			return;

		$periods = array();

		foreach($ids as $id)
		{
				$id = (int)str_replace('g', '', $id);
				$periods[] = $id;
		}

		$session = Session::instance();

		$session->set('periods', $periods);

		$settings = Auth::instance()->get_user()->get_settings();
		$settings['periods'] 	 = $periods;
		Auth::instance()->get_user()->save_settings($settings);


		$period = Jelly::select('client_periodgroup', $periods[0]);
		$period_data = array('name'=>$period->name,'start'=>$period->start,'finish'=>$period->finish);

		//$this->request->response = JSON::reply(count($periods));
		$this->request->response = JSON::success(array('period' => $period_data, 'success' => true));
	}

	public function action_update(){

		$time_start = strtotime(ACDate::convertMonth($_POST['start_f']));
		$time_finish = strtotime(ACDate::convertMonth($_POST['finish_f']));

		$user = Auth::instance()->get_user();

		$values = array('name', 'color', 'parent', 'start', 'finish', 'status');
        if($group_id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select($this->model_group_name, (int)$group_id);
		}else{
			$model = Jelly::factory($this->model_group_name);
		}

		$model->update_date = time();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);
		$model->set(Arr::extract($_POST, $values));
		$model->name = trim($model->name);
		$model->start = $time_start;
		$model->finish = $time_finish;
		$model->deleted = 0;
		$model->license   = Auth::instance()->get_user()->license->id();
		$model->save();
		$group_id = $model->id();


		Jelly::factory('client_model_properties')->update_properties('periodgroup_prop', $_POST, 'property', $model->id());


		$copy_from_previous = (bool)Arr::get($_POST,'copy_from_previous',0);
		$copy_handbook_from_previous = (bool)Arr::get($_POST,'copy_handbook_from_previous',0);
		$previous = (int)Arr::get($_POST,'previous_period',0);
		$user = Auth::instance()->get_user();
		if($copy_from_previous && $previous) Jelly::factory('client_periodgroup')->copy_fields($previous, $group_id, $user->license->id());
		if($copy_handbook_from_previous && $previous) Jelly::factory('client_periodgroup')->copy_handbook($previous, $group_id, $user->license->id());

		$this->request->response = JSON::success(array('script'	   => "Группа сохранена успешно!",
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $group_id));
	}


	public function action_tree(){
		$user = Auth::instance()->get_user();
		$check = Arr::get($_GET, 'check', false);
		$data =	Jelly::factory('client_periodgroup')->get_tree($user->license->id(), 'delete_this_shit', $check);

		$this->request->response = Json::arr($data, count($data));
	}


	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = (int)(   str_replace('g', '', str_replace('n', '', $del_ids[$i]))  );
			$model = Jelly::select('client_periodgroup', $id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error('Записи не найдены.');
				return;
			}

			$model->delete();
			$this->delete_from_defaults($id);
		}
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}


	public function delete_from_defaults($id){
		$session = Session::instance();
		$periods = $session->get('periods');

		if (!$periods) {return;}

		$key = array_search($id, $periods);
		if($key!==false) array_splice($periods, $key, 1);

		$session->set('periods', $periods);

		$settings = Auth::instance()->get_user()->get_settings();
		$settings['periods'] 	 = $periods;

		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$user->save_settings($settings);
	}


	public function get_previous_period($periodStart, $current = 0){
		if(!is_numeric($periodStart)) $periodStart = strtotime(ACDate::convertMonth($periodStart));

		$previous_periods = Jelly::select('client_periodgroup')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where(':primary_key', '!=', $current)->where('start', '<', $periodStart)->where('license', '=', Auth::instance()->get_user()->license->id())->order_by('start', 'desc')->execute()->as_array();
		$period = (count($previous_periods)>0) ? $previous_periods[0] : false;

		if($period){ $result = array('period' => $period, 'fields_count' => Jelly::select('field')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period['_id'])->where('license', '=', Auth::instance()->get_user()->license->id())->execute()->count()); }
		else{$result = array('period'=> false, 'fields_count' => 0);}

		return $result;
	}

	public function action_get_previous_period(){
		$current = (int)Arr::get($_POST,'current',0);
		$periodStart = Arr::get($_POST,'date','');

		if(!is_numeric($periodStart)) $periodStart = strtotime(ACDate::convertMonth($periodStart));

		$result = $this->get_previous_period($periodStart, $current);

		$this->request->response = JSON::success(array('period' => $result, 'success' => true));
	}
}