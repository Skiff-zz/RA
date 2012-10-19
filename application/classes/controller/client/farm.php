<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Farm extends AC_Controller
{

	public $auto_render  = false;
    protected $model_name 		= 'client_farm';
    
    public function action_photo()
	{
		if(!$_POST or !$_FILES)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image = Arr::get($_FILES, 'image', null);

		if(!$image)
			throw new Kohana_Exception(__('No image given'));

		$name = 'original_'.Text::random('alnum', 15).'.jpg';
		$filename = Upload::save($image, $name, DOCROOT.'media/pictures/', 0777);

		$this->request->response = json_encode(array('success' => true, 'image' => '/media/pictures/'.$name));
	}

	public function action_delphoto()
	{

		if(!$_POST)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image_path = Arr::get($_POST, 'img_path', null);

		if(!$image_path)
			throw new Kohana_Exception(__('No image given'));

		$dirs = explode('/',$image_path);
		$filename = $dirs[count($dirs)-1];
		$subdir = $dirs[count($dirs)-2];

		if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename))
		{

			unlink(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename);
		}

		if(is_file(DOCROOT.'media/pictures/'.$filename)) // удаляем превью
		{
			unlink(DOCROOT.'media/pictures/'.$filename);
		}

		$this->request->response = json_encode(array('success' => true, 'image' => $filename));
	}

	public function action_savephoto($id)
	{

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

	public function action_getphoto(&$view, $id)
	{
		$subdir = floor($id / 2000);
		$view->farm['images'] = array();
		if(is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
		{
			$files = scandir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir);
			foreach($files as $file){
				if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file) && ( !(strpos($file, 'item_'.$id.'_')===FALSE) || !(strpos($file, 'item_'.$id.'.')===FALSE)       )  ){
					$view->farm['images'][] = Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file;
				}
			}
		}
	}

	public function action_index($group_id = false){
		
	}
	
	public function action_setdefaultfarms()
	{
		$ids = trim(Arr::get($_POST, 'ids', ''));
		
		if($ids == '')
			return;
		
		$ids = explode(',', $ids);
		
		if(!count($ids))
			return;
			
		$farms 			= array();	
		$farm_groups 	= array();
		
		foreach($ids as $id)
		{
			if(UTF8::substr($id, 0, 1) == 'g')
			{
				$id = (int)str_replace('g', '', $id);
				$farm_groups[] = $id;
			}
			else
			{
				$id = (int)str_replace('n', '', $id);
				$farms[] = $id;
			}
		}
		
		$session = Session::instance();
		
		$session->set('farms', $farms);
		$session->set('farm_groups', $farm_groups);
		
		$user =  Auth::instance()->get_user();
		
		$settings = $user->get_settings();
		$settings['farms'] 		 = $farms;
		$settings['farm_groups'] = $farm_groups;
		
		$user->save_settings($settings);

		$farms_data = (count($farms) + count($farm_groups))>0 ? Jelly::select('farm')->where('_id', 'IN', array_merge($farms, $farm_groups))->execute()->as_array() : array();
		
		//$this->request->response = JSON::reply(count($farms) + count($farm_groups));
		$this->request->response = JSON::success(array('farms' => $farms_data, 'success' => true));
				
	}


	public function action_tree($is_group){
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		


		$both_trees = Arr::get($_GET, 'both_trees', false);
		$filtrate = Arr::get($_GET, 'filtrate', false);
		$with_fields_count = Arr::get($_GET, 'with_fields_count', false);

		if($user->license->user->id() == $user->id()){
			$data =	$both_trees ? Jelly::factory('farm')->get_full_tree($user->license->id(), 0, false, $filtrate, $with_fields_count) : Jelly::factory('farm')->get_tree($user->license->id(), (bool)$is_group);
		}else{
			$farms = Jelly::select('farm')->where('admin', '=', $user->id())->and_where('deleted', '=', 0)->execute();
			$data = array();
			if($farms->count()>0){
				foreach($farms as $farm)
				{
					//был закоменчен if. зачем? если его закоментить, то в списке групп выводится название у администратора одного безгрупного хозяйства
					if(!($farm->is_group == false and (int)$is_group) || $both_trees)
					{
						$data = array_merge($data, $both_trees ? Jelly::factory('farm')->get_full_tree($user->license->id(), $farm->id(), true, $filtrate, $with_fields_count) : Jelly::factory('farm')->get_tree($user->license->id(), (bool)$is_group,  $farm->id(), true));
					}
				}
			}else{
				$data =	$both_trees ? Jelly::factory('farm')->get_full_tree($user->license->id(), 0, false, $filtrate, $with_fields_count) : Jelly::factory('farm')->get_tree($user->license->id(), (bool)$is_group);
			}
		}

		$this->request->response = Json::arr($data, count($data));
	}



	public function action_simple_tree(){
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
			$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		if($user->license->user->id() == $user->id()){
			$data =	Jelly::factory('farm')->get_simple_tree($user->license->id(), $exclude);
		}else{
			$groups = Jelly::select('farm')->where('admin', '=', $user->id())->and_where('deleted', '=', 0)->and_where('is_group', '=', true)->execute();
			$data = array();
			foreach($groups as $g){
					$data = array_merge($data, Jelly::factory('farm')->get_simple_tree($user->license->id(), $exclude, $g->id()));
			}
		}
	
		$this->request->response = Json::arr($data, count($data));
		//new AC_Profiler();
	}


	
	public function action_move(){
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$target = arr::get($_POST, 'target', '');
        
        if($target == 'g-2' || $target == '')
            $target = 'g0';
        
		$target = mb_substr($target, 0, 1)=='g' || mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='g' || mb_substr($move_ids[$i], 0, 1)=='n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];

			$farm_model = Jelly::select('farm', (int)$id);

			if(!($farm_model instanceof Jelly_Model) or !$farm_model->loaded())	{
				$this->request->response = JSON::error(__("Farm is not specified"));
				return;
			}

			$farm_model->parent = $target;
			$farm_model->save();
		}


		$this->request->response = JSON::success(array('script' => "Moved",
																		    'url'    => null,
																		    'success'    => true));
	}



	public function action_read($id = null){
		return $this->action_edit($id, true);
	}

	public function action_edit($id = null, $read = false, $parent_id = false, $is_group = null){
		
		$root_user = Auth::instance()->get_user();
		if(!($root_user instanceof Jelly_Model) or !$root_user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

        $farm = null;

        if($id && $id!=-2){
            $farm = Jelly::select('farm')->with('parent')->with('admin')->load((int)$id);
            if(!($farm instanceof Jelly_Model) or !$farm->loaded()){
                $this->request->response = JSON::error('Не найдено Хозяйство!');
				return;
			}
        }

		$view = Twig::factory('client/farm/read');
		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($farm ? $farm->parent->id() : 0);
			$view->parent_color = $parent_id ? Jelly::select('farm')->load((int)$parent_id)->color : '';
			if(!is_null($is_group)) { $view->is_group = $is_group; }
			$view->hasChildren = false;
		}

        if($farm){	
			$view->farm 			= $farm->as_array();
			$view->admin 			= ($farm->admin instanceof Model_User && $farm->admin->loaded()) ? $farm->admin->as_array('_id','last_name','first_name','middle_name','email','username','password_text') : array();
			
			if(array_key_exists('username', $view->admin) and strpos($farm->admin->username, 'forbidden.agroclever.com'))
			{
				$view->admin['username'] = '';
			}
            
            $this->action_getphoto($view, $farm->id());
            
        }else{
			$view->farm=array();
			if(!is_null($is_group)) { $view->farm['is_group'] = $is_group; }
			if($id==-2){ $view->farm['is_group'] = true; $view->farm['_id'] = 'g-2';  }
		}
		
		//локальные доп. поля
    		
   		$view->farm['org_addons']		= Jelly::factory('client_model_properties')->get_properties('farm', $id);
		$view->farm['admin_addons'] 	= Jelly::factory('client_model_properties')->get_properties('user', $id);
		
//		$view->max_colors = 8;
//		$view->colors = Model_User::$colors;
		$view->fake_user = $id==-2;
		//$view->parent_list = Jelly::factory('user')->get_children_list($root_user->id(), $user ? $user->id() : 0);

		$this->request->response = JSON::reply($view->render());
	}

    public function action_create_group($parent_id = 0){
        if(array_key_exists(Jelly::meta('farm')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('farm')->primary_key()]);

        return $this->action_edit(null, false, $parent_id, true);
    }

	public function action_create_farm($parent_id = 0){
        if(array_key_exists(Jelly::meta('farm')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('farm')->primary_key()]);

        return $this->action_edit(null, false, $parent_id, false);
    }

	public function action_update(){

		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		
		if($user_id = arr::get($_POST, 'user_id', NULL)){
			$user_model = Jelly::select('user', (int)$user_id);
		}else{
			$user_model = Jelly::factory('user');
		}

		$values = array('name', 'address', 'phone', 'color', 'parent', 'is_group');
        if($farm_id = arr::get($_POST, '_id', NULL)){
			$farm_model = Jelly::select('farm', (int)$farm_id);
		}else{
			$farm_model = Jelly::factory('farm');
		}

		$farm_model->update_date = time();
		$farm_model->admin = $user_id;
		$farm_model->license = $user->license->id();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);
		$farm_model->set(Arr::extract($_POST, $values));
		
		
		$address = arr::get($_POST,'address','');
		$address = @json_decode($address, true);

		$farm_model->address_country = $address['country'];
		$farm_model->address_region   = $address['region'];
		$farm_model->address_city      = $address['city'];
		$farm_model->address_zip       = $address['zip'];
		$farm_model->address_street   = $address['street'];
		
		
		
		$farm_model->name = trim($farm_model->name);
		
        $farm_model->validate();
        
		$values = array('first_name', 'last_name', 'middle_name', 'password_text', 'email', 'username');
        
        $is_user_valid = false;
		
		if(! Arr::get($_POST, 'username', null))
		{
			$user_model->username = 'ac'.Text::random('alpha', 15).'@forbidden.agroclever.com';
			$values[]='username';
			$_POST['username'] = $user_model->username;
		}
		else
		{
			$user_model->username = Arr::get($_POST, 'username', null);
            $is_user_valid        = true;
		}
		

		$user_model->update_date = time();
		
		if(Arr::get($_POST,'password_text',NULL))
		{
			$_POST['password_confirm'] = $_POST['password'] = $_POST['password_text'] = trim($_POST['password_text']);

			$values[]='password';
			$values[]='password_text';
			$values[]='password_confirm';
		}
        else
        {
            $is_user_valid = false;
        }
        
        if($is_user_valid)
        {
            $user_count = Jelly::select('user')->where('is_active', '=', 0)->where_open()
            ->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', Auth::instance()->get_user()->license->id());
            if($user_model->loaded())
            {
                $user_count->where( ':primary_key', '!=', $user_model->id());
            }
            
            $count = $user_count->count();
            
            
            if($count >= $farm_model->license->max_users)
            {
                throw new Kohana_Exception('Невозможно обновить информацию об Администраторе -- действует ограничение на общее количество пользователей для этой лицензии');
            }
            
        }
        
		$user_model->set(Arr::extract($_POST, $values));
		$user_model->license = $user->license->id();
		
		if($is_user_valid)
		{
			$user_model->is_active = true;
        }
        else
        {
       		$user_model->is_active = false;
   		}
   		
        $farm_model->validate();
        $user_model->save();
                
		$user_id = $user_model->id();

		$farm_model->admin = $user_id;
		
        $farm_model->email = $user_model->email;
        $farm_model->save();
		$farm_id = $farm_model->id();
        
        $this->action_savephoto($farm_id);
        
        //если сохранение нового хозяйства
		if($is_user_valid)
        {
			
			$user_model->farm = $farm_model->id();
			
			if(trim($user_model->email))$this->send_welcome_email($user_model, (string)Arr::get($_POST,'contact',''));
		}
		
		Jelly::factory('client_model_properties')->update_properties('farm', $_POST, 'property', $farm_model->id());
		Jelly::factory('client_model_properties')->update_properties('user', $_POST, 'groundprop', $farm_model->id(), 'groundprop');

		/*
		//локальные доп. поля
		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'org_add_field_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('org_add_field_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'org_insert_field_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'org_name_insert_'.UTF8::str_ireplace('org_insert_field_', '', $key),''),
								  'value' => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$farm_id, 'farm', $addons);
		*/
		
		//если редактировали группу "без группы", то всех безхозных чаилдов цепляем к ней
		if(Arr::get($_POST,'fake_user',false)){ Jelly::factory('farm')->connectNoGroupChildren($farm_id, $user->license->id()); }

		/*
		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'adm_add_field_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('adm_add_field_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'adm_insert_field_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'adm_name_insert_'.UTF8::str_ireplace('adm_insert_field_', '', $key),''),
								  'value' => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$user_id, 'user', $addons);
		*/

        if(!arr::get($_POST, '_id', false)){
			Jelly::factory('farmpreset')->insert_preset($farm_id);
		}

		$this->request->response = JSON::success(array('script' => "Хозяйство сохранено успешно!",
													   'url'    => null,
													   'success'    => true,
													   'item_id' => $farm_id,
													   'is_group' => $farm_model->is_group));
	}
	
	
    public static function send_welcome_email($user, $contact)
	{
	    $email =  Twig::factory('user/email_client');

		$from_email = (string)Kohana::config('application.from_email');

		$email->contact = $contact;
		$email->user = $user->as_array();
        
        $license = Auth::instance()->get_user()->license;
        
        $email->activate_date = date('d.m.Y', (int)$license->activate_date);
        $email->expire_date = date('d.m.Y', (int)$license->expire_date);
		$email->link = Kohana::config('application.root_url').'client/login?';

		Email::connect();
		Email::send((string)$user->email,
					(string)$from_email,
					(string)'Ваша учетная запись в системе АгроКлевер',
					(string)$email->render(),
					 true);
	}
    
	public function action_delete($id = null){
		
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];
			if ($id==-2 || $id=='-2') {
				
				$farm_model = Jelly::select('farm')->with('_id')->where('is_group','=',0)->and_where('parent_id', '=', 0)->execute()->as_array();
				for ($j=0; $j<count($farm_model); $j++) {
					$farm_item = Jelly::select('farm', (int)($farm_model[$j]['_id']));
					$farm_item->delete();
					$this->delete_from_defaults((int)($farm_model[$j]['_id']));
				}
				
			} else {
				$farm_model = Jelly::select('farm', (int)$id);	
				if(!($farm_model instanceof Jelly_Model) or !$farm_model->loaded())	{
					$this->request->response = JSON::error(__("Farm is not specified"));
					return;
				}

				$farm_model->delete();
				$this->delete_from_defaults((int)$id);
			}
				
			
		}
		
		$this->request->response = JSON::success(array('script' => "Deleted",
																		    'url'    => null,
																		     'success'    => true));
  }

	public function delete_from_defaults($id){
		$session = Session::instance();
		$child_farm_names = Jelly::select('farm')->where('is_group', '=', false)->and_where('parent', '=', (int)$id)->execute()->as_array();
		$child_farm_groups = Jelly::select('farm')->where('is_group', '=', true)->and_where('parent', '=', (int)$id)->execute()->as_array();

		$farms = Jelly::factory('farm')->get_session_farms(false);

		$key = array_search($id, $farms['names']);
		if($key!==false) array_splice($farms['names'], $key, 1);

		$key = array_search($id, $farms['groups']);
		if($key!==false) array_splice($farms['groups'], $key, 1);

		foreach($child_farm_names as $child_farm) {
			$key = array_search($child_farm['_id'], $farms['names']);
			if($key!==false) array_splice($farms['names'], $key, 1);
		}

		$session->set('farms', $farms['names']);
		$session->set('farm_groups', $farms['groups']);

		foreach($child_farm_groups as $child_group) {
			$this->delete_from_defaults($child_group['_id']);
		}
	}
	
}
