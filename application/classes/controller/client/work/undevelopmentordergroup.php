<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Work_UndevelopmentOrderGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'client_work_undevelopmentorder';
	protected $model_group_name = 'client_work_undevelopmentordergroup';
	
	public function inner_update($group_id)
	{
		$model = Jelly::select($this->model_group_name, $group_id);
		
		$user = Auth::instance()->get_user();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$farm = $farms[0];
		$period = $periods[0];
		
		$model->license = $user->license->id();
		$model->farm	= $farm;
		$model->period	= $period;
		
		$model->save();
	}
}
