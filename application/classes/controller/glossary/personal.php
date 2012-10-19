<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Personal extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_personal';
	protected $model_group_name = 'glossary_personalgroup';

	public function inner_edit(&$view){
//		if($view->model && isset($view->model['_id'])){
//			$view->model['grasp_units'] = array('_id'=>$view->model['grasp_units']->id(), 'name' => $view->model['grasp_units']->name);
//			$view->model['gsm'] = array('_id'=>$view->model['gsm']->id(), 'name' => $view->model['gsm']->name);
//		}

		$view->hours_units = Jelly::factory('glossary_units')->getUnits('personal_time');
		$view->productivity_units = Jelly::factory('glossary_units')->getUnits('personal_productivity');
		$view->payment_units = Jelly::factory('glossary_units')->getUnits('personal_payment');

		$view->model_fields = array();
	}

	public function inner_update($id){
		Jelly::factory('personalpreset')->insert_preset($id, false);

		$model = Jelly::select('glossary_personal', (int) $id)->as_array();
		unset($model['id']);
		unset($model['_id']);
		unset($model['group_id']);
		$handbook_siblings = Jelly::select('client_handbook_personalgroup')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
						where('is_position','=',true)->
						where('id_in_glossary','=',$id)->
                        execute();

		foreach($handbook_siblings as $sibling){
			$sibling->set($model)->save();
			$sal_units = $sibling->average_salary_units->id();
			$operation_personal = Jelly::select('operations2personal')->
					where('personal_id','=',$sibling->id())->
					execute();

			foreach($operation_personal as $pers){
				$pers['salary'] = $sal_units==52 ? $sibling->average_salary : 0;
				$db = Database::instance();
				$db->query(DATABASE::UPDATE, 'UPDATE operations2personal SET salary = '.$pers['salary'].', salary_units_id = 52 WHERE (_id='.$pers['_id'].')', true);

				$parent_operation = Jelly::select('client_operation',(int)$pers['client_operation_id']);
				$atk_operations = Jelly::select('planning_atk2operation')->
						where('client_operation_id','=',$parent_operation->id())->
						execute();
				foreach($atk_operations as $atk_operation){
					$atk = Jelly::select('client_planning_atk',   (int)$atk_operation['client_planning_atk_id']);
					$atk->set(array('outdated'=>1))->save();
				}


			}
		}

	}

	public function save($key = null)
	{
		$res = parent::save($key);

		if(!$key)
		{
			Jelly::factory('personalpreset')->insert_preset($this->id(), false);
		}

		return $res;
	}
}