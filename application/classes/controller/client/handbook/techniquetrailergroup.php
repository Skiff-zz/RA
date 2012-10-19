<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Handbook_TechniqueTrailerGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'client_handbook_techniquetrailer';
	protected $model_group_name = 'client_handbook_techniquetrailergroup';
	
	
	
	public function action_delete($id = null){
		
		$user = Auth::instance()->get_user();
		$license_id = $user->license->id();
			

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		
		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='n' ? 'client_handbook_techniquetrailer' : 'client_handbook_techniquetrailergroup';
			
			if ($id==-2 || $id=='-2') {
				
				$items_to_delete =								 Jelly::select($this->model_name)->with('_id')->where('group_id','=',NULL)->where('license', '=', $license_id)->execute()->as_array();
				$items_to_delete = array_merge($items_to_delete, Jelly::select($this->model_name)->with('_id')->where('group_id','=',0)->where('license', '=', $license_id)->execute()->as_array());
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select($this->model_name, (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}
				
			} else {
				
				$model = Jelly::select($m, (int)$id);
				
				// удаляем дочерние Названия
				$names_to_delete = Jelly::select('client_handbook_techniquetrailer')->where('group','=',$model->id())->where('license', '=', $license_id)->execute();
				foreach ($names_to_delete as $item){
					$item->delete();				
				}
				// ----

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}

				$model->delete();				
			}
			
		}
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}
	
	
	
	
	public function action_addnomenclature(){
		$model = Arr::get($_POST, 'model', false);
		$model_to = Arr::get($_POST, 'model_to', false);
		$model_ids = Arr::get($_POST, 'ids', '');
		$model_ids = explode(',', $model_ids);
		$farm_id = Arr::get($_POST, 'farm_id', false);
		
		
		if(isset($model_ids[0]) && !trim($model_ids[0])) $model_ids = array();

		if(!$model){
			$this->request->response = JSON::error('Номенклатура не найдена.'); return;
		}

		$user = Auth::instance()->get_user();
		Jelly::factory($model_to)->add_nomenclature($model, $model_ids, $user->license->id(),$farm_id);

		$this->request->response = JSON::success(array('script' => "Added", 'url' => null, 'success' => true));
	}
	
	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;
		
        if($id && $id!=-2){
            $model = Jelly::select('client_handbook_techniquetrailergroup')->with('parent')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/handbook/techniquetrailer/read_techniquetrailergroup');

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
			$view->model['properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbooktechniquetrailergroup_prop')->execute()->as_array();
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
			$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		$with_cultures = Arr::get($_GET, 'both_trees', false);

		$data =	Jelly::factory($this->model_group_name)->get_tree($user->license->id(), $with_cultures, $exclude, $this->items_field);
		
		$this->request->response = Json::arr($data, count($data));
	}
	
	public function action_update(){

		$user = Auth::instance()->get_user();

		$values = array('name', 'color', 'parent');
        if($group_id = arr::get($_POST, '_id', NULL)){
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
		$model->license   = Auth::instance()->get_user()->license->id();
		$model->save();
		$group_id = $model->id();
        
        $this->action_savephoto($model->id());

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

}
