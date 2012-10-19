<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_OperationStage extends AC_Controller
{

	public $auto_render  = false;
    protected $ignore_addons = false;


	public function action_tree(){
		$user = Auth::instance()->get_user();
		
		$data =	Jelly::factory('client_operationstage')->get_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}
    
    
    public function action_create($parent_id = 0){
        if(array_key_exists(Jelly::meta('client_operationstage')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('client_operationstage')->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }
    
    
    public function action_read($id = null){
		return $this->action_edit($id, true);
	}
    
    
    public function action_edit($id = null, $read = false, $parent_id = false){
		
        $model = null;

        if($id){
            $model = Jelly::select('client_operationstage')->with('group')->where(':primary_key', '=', (int)$id)->load();
            
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }
        
        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id){
            $group = Jelly::select('client_operationstagegroup')->where(':primary_key', '=', $parent_id)->load();
            
            if(!($group instanceof Jelly_Model) or !$group->loaded()){
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }    
        }

		$view = Twig::factory('client/operationstage/operationstage');
		
        if($model){
            $view->model  			               = $model->as_array();
            $view->model['group']                  = $model->get('group')->id();
            $view->model['group_name']             = $model->get('group')->name;
            $view->model['group_color']            = $model->get('group')->color;
        }
        
        
        if(!$read){
			$view->edit			 	= true;
            
            if((int)$parent_id){
                    $view->model          = array();
                    $view->model['group'] = $parent_id;
					$view->parent_color   = $group->color;
                    $view->model['group_name']   = $group->name;
                    $view->model['group_color']  = $view->parent_color;
            }
		}
		

        if($model){
            $view->model['properties']  = $model->get_properties();
        }else{
            if(!(int)$parent_id) $view->model = array();//если не создание и не редактирование
            $view->model['properties']  = Jelly::factory('client_operationstage')->get_properties();
        }
        
        $view->group_field = 'group';
        //print_r($view); exit;
		$this->request->response = JSON::reply($view->render());
	}
    
    
    
    public function action_update(){
        
        $user = Auth::instance()->get_user();

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_operationstage', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_operationstage');
		}

		$model->update_date = time();
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->deleted = 0;       
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
    
    
    public function action_move(){

		$target = arr::get($_POST, 'target', '');
        if($target == 'g-2' || $target == '') $target = 'g0';
		$target = mb_substr($target, 0, 1)=='g' || mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='g' || mb_substr($move_ids[$i], 0, 1)=='n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
			$model = Jelly::select('client_operationstage', (int)$id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error("Запись не найдена");
				return;
			}

			$model->group = $target;
			$model->save();
		}

		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}
    
    
    public function action_delete($id = null){
		
		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='g' ? 'client_operationstagegroup' : 'client_operationstage';
			if ($id==-2 || $id=='-2') {
				
				$items_to_delete = Jelly::select('client_operationstage')->with('_id')->where('group_id','=',NULL || 0)->execute()->as_array();
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

				$model->delete();				
			}
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

}
