<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_User extends AC_Controller
{
	public $auto_render  = false;
	
    
    /** Для добавления юзеров в лицензии **/
    public function action_list_users()
    {
        $template = Twig::factory('client/user/_list');
        $this->request->response = JSON::reply($template->render());;            
    }
    
    public function action_check_password()
    {
   		if(!Auth::instance()->get_user())
   			return $this->request->response = JSON::error('User not logged in!');
   		
   		if(!$_POST)
   			return $this->request->response = JSON::error('POST method requered!');
		   	
   		$license = Auth::instance()->get_user()->license;
		$admin 	 = $license->user;
		
		$password = Arr::get($_POST, 'password', '');
		
		if($password != $admin->password_text)
				return $this->request->response = JSON::error('Пароль неправильный!');	   
				
		$this->request->response = JSON::reply('ok');
 	}
    
    public function action_list_users_do()
    {
        $edit = (int)Arr::get($_GET, 'edit', 0);
        
        $users = Jelly::select('user')->where('license', '=', Auth::instance()->get_user()->license->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('is_active', '=', 1)->execute()->as_array();
        
        $template = Twig::factory('client/user/list');
        
        if($edit)
        {
            $template->edit = 1;
        }
        
        $template->users = $users;
        
        $this->request->response = JSON::reply($template->render());;            
    }
    
    public function action_update_list()
    {
        if(!$_POST)
            throw new Kohana_Exception(__('Post Method Required'));
        
        $user_list = Arr::get($_POST, 'user_list', null);
        
        if($user_list == '')
            throw new Kohana_Exception(__('No user info was found!'));
        
        $user_list = json_decode($user_list, true);
        
        if(!is_array($user_list))
            throw new Kohana_Exception(__('No user info was found!'));
        
        // Подымем сохраненных
        $users = Jelly::select('user')->where('license', '=', Auth::instance()->get_user()->license->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)
                 ->where_close()/*->where(':primary_key', '!=', Auth::instance()->get_user()->id())*/->where('is_active', '=', 1)->execute();
        
        $ids = array();
        $deleted = array();
        
        $new_ids = array();
        
        foreach($user_list as $u)
        {
            if(is_numeric($u['rowId']))
            {
                $new_ids[] = (int)$u['rowId'];
            }    
        }
        
        
        foreach($users as $us)
			{
            $ids[] = (int)$us->id();
            
            if(!in_array($us->id(), $new_ids) && $us->id()!=Auth::instance()->get_user()->id())
			{
                $deleted[] = (int)$us->id();
            }
        }
        
        if(count($deleted))
        {
       		Jelly::update('user')->where('license', '=', Auth::instance()->get_user()->license->id())
	   		->set(array('is_active' => 0, 'deleted' => 1))->where(':primary_key', 'IN', $deleted)/*->where(':primary_key', '!=', Auth::instance()->get_user()->license->user->id())*/->execute();
	    }
        
        @reset($user_list);
        
        // Простенькая валидация ДО сохранения
        foreach($user_list as $u)
        {
       		if(trim($u['username'])=='' ||  trim($u['password_text'])==''){
				throw new Kohana_Exception('Поля "Логин" и "Пароль" являются обязательными!');
			}
            
	    }
        
        @reset($user_list);
        
        foreach($user_list as $u)
        {
            $found = false;
            
            if(is_numeric($u['rowId']))
            {
                /** Обрабатывается сохраненный юзверь **/
                if(!in_array($u['rowId'], $ids))
                {
                    /** Айдишник принадлежит другой лицензии **/
                    continue;
                }
                
                
                foreach($users as $usr)
                {
                    if($usr->id() == (int)$u['rowId'])
                    {
                        $found = true;
                        break;
                    }
                }
                
                if(!$found)
                    continue;
            }
            
            if(!$found)
            {
                $usr = Jelly::factory('user');
                $usr->license = Auth::instance()->get_user()->license->id();
                
                if(trim($u['username'])=='' ||  trim($u['password_text'])==''){
					throw new Kohana_Exception('Поля "Логин" и "Пароль" являются обязательными!');
				}
            }
            $old  = $usr->as_array();
                
            $usr->last_name  = $u['last_name'];
            $usr->first_name = $u['first_name'];
            $usr->middle_name = $u['middle_name'];
            $usr->username    = $u['username'];
            $usr->password_text = $usr->password_confirm = $usr->password = $u['password_text'];
            $usr->email      = $u['email'];
            $usr->is_active  = 1;
            
            if(!is_numeric($u['rowId']))
            {
				$usr->is_root    = 0;
			}	
            
            $usr->deleted    = 0;
            
            // Посчитаем бойцов - не вылазим ли мы за лимиты
            $count = Jelly::select('user')->where('license', '=', Auth::instance()->get_user()->license->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where(':primary_key', '!=', Auth::instance()->get_user()->id())->where('is_active', '=', 1)->count();
        	
        	$license = Jelly::select('license', Auth::instance()->get_user()->license->id());
        	
        	if(!($license instanceof Jelly_Model) or !$license->loaded())
        	{
       			throw new Kohana_Exception('License not found');
	   		}
            
            if($count + 1 > $license->max_users)
            {
           		throw new Kohana_Exception('Внимание! Сохранены не все пользователи! Вы достигли ограничения лицензии на количество пользователей!');
	   		}
            
            $usr->save();
            
            
            if(
                $old['last_name'] != $u['last_name'] or
                $old['middle_name'] != $u['middle_name'] or
                $old['first_name'] != $u['first_name'] or
                $old['username'] != $u['username'] or
                $old['email'] != $u['email'] or
                $old['password_text'] != $u['password_text'])
            {    
                if(trim($u['email'])) Controller_Client_Farm::send_welcome_email($usr, $usr->last_name.' '.$usr->first_name.' '.$usr->middle_name);
            }
        }
        
        $this->request->response = JSON::success(array('script' => "Лицензиаты сохранен успешно!",
													   'url'    => '/clientpc/user/user_list/',
													   'success'    => true));    
    }
    
	public function action_index()
	{
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

	    $data =	Jelly::select('user', $user->id())->get_tree();

		$this->request->response = Json::arr($data, count($data));
	}
	
	public function action_list($ids = '')
	{
	  $elems = array();
	  foreach (explode('-', $ids) as $id)
	  {
	    $elems[] = (int)trim($id);
	  }

          if (count($elems)==1)
          {
              //согласно требованям тикета AGC-24
              return $this->action_edit($elems[0], true);
          }

	  $users = array(); $it = 0;
	  $addons = Jelly::select('field')->order_by('_id', 'ASC')->execute();
	  foreach (Jelly::select('user')->
                        where(':primary_key', 'IN', $elems)->
                        //substr_count in mysql :)
                        order_by(db::expr('(LENGTH(`path`)-LENGTH(REPLACE(`path`,\'/\', \'\')))'), 'ASC')->
                        order_by('name', 'ASC')->
                        execute() as $user)
	  {
			$users[$it] = $user->as_array();


			$values = Jelly::select('value')->
                                    with('field')->
                                    where('user', '=', $user->id())->
                                    execute();

                        $v = array();
			foreach($values as $t){
				$v[$t->field->id()] = $t->value;
			}
			$r = array();
			foreach($addons as $a){
				$r[] = array('_id' 	=> $a->id(),
							 'name'	=> $a->name,
							 'value' => array_key_exists($a->id(), $v) ? $v[$a->id()] : '');
			}
			$users[$it]['global_addons'] = $r;

			$users[$it]['org_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user->id())->and_where('block', '=', 'organization')->execute()->as_array();
			$users[$it]['admin_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user->id())->and_where('block', '=', 'admin')->execute()->as_array();

			//цвета
			$format = Jelly::select('client_format')->where('farm', '=', $user->id())->and_where('name', '=', 'colors_count')->limit(1)->execute();
			$users[$it]['max_colors'] = ($format instanceof Jelly_Model && $format->loaded()) ? $format->value : 8;

			$it++;
	  }
	  
        if (empty($users))
		{
			$this->request->response = JSON::error(__("User IDs not specified or wrong."));
			return;
		}
		
		$view = Twig::factory('client/user/list');
		$view->users = $users;
		$view->colors = Model_User::$colors;
		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_read($id = null)
	{
		return $this->action_edit($id, true);
	}
	
	public function action_edit($id = null, $read = false, $parent_id = false)
	{

		$root_user = Auth::instance()->get_user();
		if(!($root_user instanceof Jelly_Model) or !$root_user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		
        $user = null;
        
        if($id)
        {
            $user = Jelly::select('user')->with('parent')->load((int)$id);
            if(!($user instanceof Jelly_Model) or !$user->loaded())
            {
                $this->request->response = JSON::error('Не найдено Хозяйство!');
				return;
			}
        }   
				
		if(!$read)
		{		
			$view = Twig::factory('client/user/edit');
			$view->edit			 	= true;

			$view->parent_id = $parent_id;
			$view->hasChildren = Jelly::select('user')->where('parent_id', '=', $id)->count() > 0;
		}
		else
		{
			$view = Twig::factory('client/user/read');
		}
		
        if($user)		
		  $view->user 			= $user->as_array();

		//глобальные доп.поля
		$addons = Jelly::select('field')->order_by('_id', 'ASC')->execute();
		
        /**
         *  А если юзер создается, что делать изволите?
         *  Есть же административный контроллер, по которому я говорил делать.
         * 
         * */
        $v = array();
        
        if($user)
        {
            $values = Jelly::select('value')->with('field')->where('user', '=', $user->id())->execute();
    		
    		foreach($values as $t){
    			$v[$t->field->id()] = $t->value;
    		}
        }
        else
        {
            $view->user = array();
        }
        
        $r = array();
		foreach($addons as $a){
			$r[] = array('_id' 	=> $a->id(),
						 'name'	=> $a->name,
						 'value' => array_key_exists($a->id(), $v) ? $v[$a->id()] : '');
		}
		$view->user['global_addons'] = $r;
        
        
		
        if($user)
        {
            //локальные доп. поля
            if($user)
                $user_uid = $user->id();
            else
                $user_uid = Auth::instance()->get_user()->id();
                        
    		$view->user['org_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user_uid)->and_where('block', '=', 'organization')->execute()->as_array();
    		$view->user['admin_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user_uid)->and_where('block', '=', 'admin')->execute()->as_array(); 
        }

		$format = Jelly::select('client_format')->where('farm', '=', $root_user->id())->and_where('name', '=', 'colors_count')->limit(1)->execute();
		$view->user['max_colors'] = ($format instanceof Jelly_Model && $format->loaded()) ? $format->value : 8;
        
		$view->colors = Model_User::$colors;
		$view->parent_list = Jelly::factory('user')->get_children_list($root_user->id(), $user ? $user->id() : 0);
				
		$this->request->response = JSON::reply($view->render());
	}

    public function action_create($parent_id)
    {
        if(array_key_exists(Jelly::meta('user')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('user')->primary_key()]);
        
        return $this->action_edit(null, false, $parent_id);
    }

	public function action_update()
	{
		$model_meta = Jelly::meta('user');
		//Редактирование или обновление

		

        if($primary_key = arr::get($_POST, $model_meta->primary_key(), NULL))
		{
			$values = array('name', 'address', 'phone', 'color', 'first_name', 'last_name', 'middle_name', 'username', 'password_text', 'parent');
			$model = Jelly::select('user', (int)$primary_key);
		} 
		else 
		{
			$values = array('name', 'address', 'phone', 'color', 'first_name', 'last_name', 'middle_name', 'username', 'password_text', 'email', 'parent');
			$model = Jelly::factory('user');
			$model->username = 'ac'.text::random('alpha', 15).'@forbidden.agroclever.com';
			$model->is_active = 0;
			//TMP
			$parent = Jelly::select('user')->load(Arr::get($_POST,'parent', 0));
			if(!($parent instanceof Jelly_Model) or !$parent->loaded()){
                $this->request->response = JSON::error('Родительское хозяйство не найдено!');
				return;
			}
			$childrenCount = Jelly::select('user')->where('parent', '=', Arr::get($_POST,'parent', 0))->count();
			$model->number = $parent->number.($childrenCount+1);
			$model->activate_date	= $parent->activate_date;
			$model->expire_date	    = $parent->expire_date;
			$model->max_ms	        = $parent->max_ms;
			//$model->max_pc	        = $parent->max_pc;
			$model->max_users	    = $parent->max_users ? $parent->max_users : 1;
			$model->square	        = $parent->square;
			
			/*
			if(!Arr::get($_POST,'password_text', NULL))
			{
				$this->request->response = JSON::error('Не задан пароль!');
				return;
			}*/
		}

		$model->update_date = time();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);

		if(Arr::get($_POST,'password_text',NULL))
		{
			$_POST['password_confirm'] = $_POST['password'] = $_POST['password_text'] = trim($_POST['password_text']); 
			
			$values[]='password';
			$values[]='password_text';
			$values[]='password_confirm';
		}
				
		$model->set(Arr::extract($_POST, $values));
		
		$address = arr::get($_POST,'address','');
		$address = @json_decode($address, true);

		$model->address_country = $address['country'];
		$model->address_region   = $address['region'];
		$model->address_city      = $address['city'];
		$model->address_zip       = $address['zip'];
		$model->address_street   = $address['street'];
		
		

		$model->deleted = 0;
		//$model->is_root = 0;
				
		$model->save();
		$primary_key = $model->id();
		
		$model->set_status();


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
		Jelly::factory('client_extraproperties')->updateOldFields((int)$primary_key, 'organization', $addons);

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
		Jelly::factory('client_extraproperties')->updateOldFields((int)$primary_key, 'admin', $addons);


		//глобальные доп.поля
		$addons = Jelly::select('field')->execute();
		foreach($addons as $add) {
			$val = arr::get($_POST,'add_field_'.$add->id(),NULL);
			if(!is_null($val)){
				$a = Jelly::select('value')->where('user', '=', (int)$primary_key)->and_where('field', '=', $add->id())->limit(1)->execute();
				if(!($a instanceof Jelly_Model) or !$a->loaded()){
					$a = Jelly::factory('value');
				}
				$a->user = (int)$primary_key;
				$a->field = $add->id();
				$a->value = $val;
				$a->save();
			}
		}

		$this->request->response = JSON::reply("Лицензиат сохранен успешно!");
	}


	public function action_tree(){
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$data =	Auth::instance()->get_user()->get_tree($user->license->id());
		$this->request->response = Json::arr($data, count($data));
	}


}
