<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_ProducerGroup extends Controller_Glossary_AbstractGroup
{
	protected $model_name = 'client_producer';
	protected $model_group_name = 'client_producergroup';

	protected $SYSTEM_PRODUCERS = array('g1001','g1002','g1003','g1004');

	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		if( count( array_intersect($del_ids,$this->SYSTEM_PRODUCERS) ) )	{
			$this->request->response = JSON::error('Нельзя удалять системных производителей.');
			return;
		}

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='n' ? $this->model_name : $this->model_group_name;

			if ($id==-2 || $id=='-2') {

				$items_to_delete =								 Jelly::select($this->model_name)->with('_id')->where('group_id','IS',NULL)->execute()->as_array();
				$items_to_delete = array_merge($items_to_delete, Jelly::select($this->model_name)->with('_id')->where('group_id','=',0)->execute()->as_array());
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select($this->model_name, (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}

			} else {

				$model = Jelly::select($m, (int)$id);

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}

				Jelly::delete($this->model_name)->where('group', '=', $model->id())->execute();

				$model->delete();
			}

		}
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

	public function action_update(){

		$user = Auth::instance()->get_user();

		$values = array('name', 'color', 'parent');
        if($group_id = arr::get($_POST, '_id', NULL)){

			if( in_array( 'g'.$group_id , $this->SYSTEM_PRODUCERS) )	{
				$this->request->response = JSON::error('Нельзя редактировать системных производителей.');
				return;
			}



			$model = Jelly::select($this->model_group_name, (int)$group_id);
		}else{
			$model = Jelly::factory($this->model_group_name);
		}

		$this->validate_data($_POST);

       	$model->set($_POST);

		$model->update_date = time();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);
		$model->set(Arr::extract($_POST, $values));
		$model->name = trim($model->name);
		$model->save();
        
        $this->action_savephoto($model->id());
        
		$group_id = $model->id();

		$this->inner_update($group_id);


		//если редактировали группу "без группы", то всех безхозных чаилдов цепляем к ней
		if(Arr::get($_POST,'fake_group',false)){
			$db = Database::instance();
			$db->query(DATABASE::UPDATE, 'UPDATE '.Jelly::meta($this->model_name)->table().' SET group_id = '.$group_id.' WHERE (group_id=0 OR group_id IS NULL) AND deleted = 0', true);
		}

		$this->request->response = JSON::success(array('script'	   => "Группа сохранена успешно!",
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $group_id));
	}

	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id && $id!=-2){
            $model = Jelly::select('client_producergroup')->with('parent')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/producer/read_producergroup');

        if($id)
			$view->id = $id;

		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($model ? $model->parent->id() : 0);
			$view->hasChildren = false;
		}

        if($model){
			$view->model 	 = $model->as_array();
            $this->action_getphoto($view, $model->id());
        }else{
			$view->model	=	array();
		}

        if($model){
			$view->model['properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'producergroup_prop')->execute()->as_array();
        }

		$view->fake_group = $id==-2;

		$this->request->response = JSON::reply($view->render());
	}


	public function action_tree(){

		$user = Auth::instance()->get_user();

		$exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
				$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' ? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		$with_cultures = Arr::get($_GET, 'both_trees', false);
		$with_country = Arr::get($_GET, 'with_country', false);

		$data =	Jelly::factory($this->model_group_name)->get_tree('delete_this_shit', $with_cultures, $exclude, $this->items_field, $with_country);

//		if($with_country){
//			$countries = Jelly::select('client_country')->where('deleted', '=', false)->execute()->as_array();
//			foreach ($data as $producer){
//
//			}
//		}

		$this->request->response = Json::arr($data, count($data));
	}


}