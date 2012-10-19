<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Shareholder extends AC_Controller
{
	
	public $auto_render  = false;
	protected $model_name = 'shareholder';
	public function action_index(){}
	
	
	
	public function action_shareholder_tree(){
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_shareholdergroup')->get_shareholder_tree($user->license->id(), true, true);
		$this->request->response = Json::arr($data, count($data));
	}
	
	
	
	public function action_simple_group_tree(){
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_shareholdergroup')->get_shareholder_tree($user->license->id(), false);
		$this->request->response = Json::arr($data, count($data));
	}
	
	
	
	public function action_read_group($id = null){
		return $this->action_edit_group($id, true);
	}
	
	
	
	public function action_create_group($parent_id = 0){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edit_group(null, false, $parent_id);
    }
	
	
	
	public function action_edit_group($id = null, $read = false, $parent_id = false){
		
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified")); return;
		}

        $group = null;

        if($id){
            $group = Jelly::select('client_shareholdergroup')->with('parent')->load((int)$id);
            if(!($group instanceof Jelly_Model) or !$group->loaded()){
                $this->request->response = JSON::error('Группа не найдена.'); return;
			}
        }
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(count($farms)!=1 && !$id){ $this->request->response = JSON::error('Для создания группы пайщиков необходимо указать одно хозяйство.'); return; }

		$view = Twig::factory('client/shareholder/group');
		

        if($group){	
			$view->model = $group->as_array();
			$view->model['properties'] = $group->get_properties();
        }else{
			$view->model = array();
			$view->model['parent'] = $parent_id;
			$view->model['farm'] = Jelly::select('farm', (int)$farms[0]);
			$view->model['properties']  = Jelly::factory('client_shareholdergroup')->get_properties();
		}
		
		$view->edit	= !$read;
		$this->request->response = JSON::reply($view->render());
		
	}
	
	
	
	public function action_update_group(){

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_shareholdergroup', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_shareholdergroup');
		}
		
		$user = Auth::instance()->get_user();
        $periods = Session::instance()->get('periods');
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->period = $periods[0];
		$model->save();
		$item_id = $model->id();

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

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $item_id));
	}
	
	
	
	///////////////////////////////////   ITEMS   //////////////////////////////////////////


	
	public function action_read($id = null){
		return $this->action_edit($id, true);
	}
	
	
	
	public function action_create($parent_id = 0){
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edit(null, false, $parent_id);
    }
	
	
	
	public function action_edit($id = null, $read = false, $parent_id = false){
		
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified")); return;
		}

        $item = null;

        if($id){
            $item = Jelly::select('client_shareholder')->with('parent')->load((int)$id);
            if(!($item instanceof Jelly_Model) or !$item->loaded()){
                $this->request->response = JSON::error('Запись не найдена.'); return;
			}
        }
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(count($farms)!=1 && !$id){ $this->request->response = JSON::error('Для создания пайщика необходимо указать одно хозяйство.'); return; }

		$view = Twig::factory('client/shareholder/item');
		

        if($item){	
			$arr = $item->as_array();
			$arr['parent'] = $arr['parent']->id();
			$view->model = $arr;
			$view->model['properties'] = $item->get_properties();
        }else{
			$view->model = array();
			$view->model['parent'] = $parent_id;
			$view->model['farm'] = Jelly::select('farm', (int)$farms[0]);
			$view->model['properties']  = Jelly::factory('client_shareholder')->get_properties();
		}
		
		$view->edit	= !$read;
		$this->request->response = JSON::reply($view->render());
		
	}
	
	
	
	public function action_update(){

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select('client_shareholder', (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory('client_shareholder');
		}
		
		$user = Auth::instance()->get_user();
        $periods = Session::instance()->get('periods');
		$model->set($_POST);
        $model->license = $user->license->id();
		$model->period = $periods[0];
		$model->save();
		$item_id = $model->id();

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

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!', 'url' => null, 'success' => true, 'item_id' => $item_id));
	}
	
	
	
	////////////////////////////////////   PHOTO   //////////////////////////////////////////////
	
	
	
	public function action_photo(){
		if(!$_POST or !$_FILES)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image = Arr::get($_FILES, 'image', null);

		if(!$image)
			throw new Kohana_Exception(__('No image given'));

		$name = 'original_'.Text::random('alnum', 15).'.jpg';
		$filename = Upload::save($image, $name, DOCROOT.'media/pictures/', 0777);

		$this->request->response = json_encode(array('success' => true, 'image' => '/media/pictures/'.$name));
	}

	
	
	public function action_delphoto(){

		if(!$_POST)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image_path = Arr::get($_POST, 'img_path', null);

		if(!$image_path)
			throw new Kohana_Exception(__('No image given'));

		$dirs = explode('/',$image_path);
		$filename = $dirs[count($dirs)-1];
		$subdir = $dirs[count($dirs)-2];

		if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename)){
			unlink(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename);
		}

		if(is_file(DOCROOT.'media/pictures/'.$filename)){ // удаляем превью
			unlink(DOCROOT.'media/pictures/'.$filename);
		}

		$this->request->response = json_encode(array('success' => true, 'image' => $filename));
	}

	
	
	public function action_savephoto($id){

	// image manipulating
		$photo = Arr::get($_POST, 'photo', null);
		if($photo != '')
		{
			$photos = explode(',',$photo);
			foreach($photos as $photo){
				// Посмотрим, или это временное что-то
				if(preg_match('#(.*)original_(.*).jpg#', $photo))
				{
					// Да, надо перенести куда следует
					$subdir = floor($id / 2000);

					if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'))
					{
						@mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/');
					}

					if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
					{
						@mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir, 0777);
					}
					if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg')){
						rename(	DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg',
								DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'_changed_'.time().'_'.Text::random('alnum', 15).'.jpg');
					}

					if(is_file(DOCROOT.$photo)){
						copy(DOCROOT.$photo, DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg');
						chmod(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg', 0777);
					}
				}
				// Если же нет - тогда делать ничего не надо. И это прекрасно!
			}
		}

	}

	
	
	public function action_getphoto(&$view, $id){
		$subdir = floor($id / 2000);
		$view->model['images'] = array();
		if(is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
		{
			$files = scandir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir);
			foreach($files as $file){
				if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file) && ( !(strpos($file, 'item_'.$id.'_')===FALSE) || !(strpos($file, 'item_'.$id.'.')===FALSE)       )   ){
					$view->model['images'][] = Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file;
				}
			}
		}
	}
	
	
	
	public function action_move(){
		$move_ids = Arr::get($_POST, 'move_ids', '');
		$target 	 = Arr::get($_POST, 'target', false);
		
		if(!$target){
			$this->request->response = JSON::error('Не выбрана группа для переноса.'); return;
		}else{
			$target = substr($target, 1);
			if(strlen($target)<1){
				$this->request->response = JSON::error('Не выбрана группа для переноса.'); return;
			}
			if($target=='-2')$target = '0';
		}
			
		
		if($move_ids){
			$move_ids = explode(',', $move_ids);
		} else {
			$move_ids = array();
		}
	
		for($i=0; $i<count($move_ids); $i++){
			$id = $move_ids[$i];
			
			$item = Jelly::select('Client_Shareholder', (int)$id);
			if($item instanceof Jelly_Model && $item->loaded()){
				$item->parent = $target;
				$item->save();
			}
		}
		
		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}
}
