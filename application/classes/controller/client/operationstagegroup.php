<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_OperationStageGroup extends AC_Controller
{

	public $auto_render  = false;


	public function action_tree(){
	
		$user = Auth::instance()->get_user();
        
        $exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
			$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' ? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

        
		$with_names = Arr::get($_GET, 'both_trees', false);
		$data =	Jelly::factory('client_operationstagegroup')->get_tree($user->license->id(), $with_names, $exclude);
		$this->request->response = Json::arr($data, count($data));
	}
    
    
    public function action_create($parent_id = 0){
        if(array_key_exists(Jelly::meta('client_operationstagegroup')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('client_operationstagegroup')->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }
    
    
    public function action_read($id = null){
		return $this->action_edit($id, true);
	}
    
    
    public function action_edit($id = null, $read = false, $parent_id = false){

        $group = null;
        
        if($id && $id!=-2){
            $group = Jelly::select('client_operationstagegroup')->with('parent')->load((int)$id);
            if(!($group instanceof Jelly_Model) or !$group->loaded()){
                $this->request->response = JSON::error('Не найдена Запись');
				return;
			}
        }
        
		$view = Twig::factory('client/operationstage/operationstagegroup');

		if($id) $view->id = $id;
		
		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($group ? $group->parent->id() : 0);
			$view->hasChildren = false;
		}
        
        if($group){
			$view->model                = $group->as_array();
            $view->model['properties']  = $group->get_properties();
        }else{
			$view->model	=	array();
            $view->model['properties']  = Jelly::factory('client_operationstagegroup')->get_properties();
		}

		$view->fake_group = $id==-2;

		$this->request->response = JSON::reply($view->render());
	}
    
    
    
    public function action_update(){

		$user = Auth::instance()->get_user();

        if($group_id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_operationstagegroup', (int)$group_id);
		}else{
			$model = Jelly::factory('client_operationstagegroup');
		}

		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);
		$model->update_date = time();
		$model->set($_POST);
		$model->deleted = 0; 
        $model->license = $user->license->id();
		$model->save();
		$group_id = $model->id();
        
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


		//если редактировали группу "без группы", то всех безхозных чаилдов цепляем к ней
		if(Arr::get($_POST,'fake_group',false)){
			$db = Database::instance();
			$db->query(DATABASE::UPDATE, 'UPDATE client_operationstagegroup SET group_id = '.$group_id.' WHERE (group_id=0 OR group_id IS NULL) AND deleted = 0', true);
		}
		
		$this->request->response = JSON::success(array('script' => 'Группа сохранена успешно!','url' => null,'success' => true, 'item_id' => $group_id));
	}
    
    
    public function action_move(){

		$target = arr::get($_POST, 'target', '');
        if($target == 'g-2' || $target == '') $target = 'g0';
		$target = mb_substr($target, 0, 1)=='g' || mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='g' || mb_substr($move_ids[$i], 0, 1)=='n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
			$model = Jelly::select('client_operationstagegroup', (int)$id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error('Группа не найдена.');
				return;
			}

			$model->parent = $target;
			$model->save();
		}

		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}
    
    
    public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		
		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='n' ? 'client_operationstage' : 'client_operationstagegroup';
			
			if ($id==-2 || $id=='-2') {
				
				$items_to_delete =								 Jelly::select('client_operationstage')->with('_id')->where('group_id','IS',NULL)->execute()->as_array();
				$items_to_delete = array_merge($items_to_delete, Jelly::select('client_operationstage')->with('_id')->where('group_id','=',0)->execute()->as_array());
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select('client_operationstage', (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}
				
			} else {
				
				$model = Jelly::select($m, (int)$id);

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}
				
				Jelly::delete('client_operationstage')->where('group', '=', $model->id())->execute();

				$model->delete();				
			}
			
		}
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

}
