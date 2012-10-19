<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Farm extends AC_Controller
{
	
	public    $auto_render  = false;
	
	public function action_debug()
	{
		$view = Twig::factory('farm/debug');
		
		$this->request->response = $view->render();
	}
	
	public function action_update()
	{
		$model_meta = Jelly::meta('farm');
		//Редактирование или обновление
				
		if($primary_key = arr::get($_POST, $model_meta->primary_key(), NULL)){
			$model = Jelly::select('farm', (int)$primary_key);
			$values = array('status', 'name', 'parent', 'number','expire_date','max_ms',/*'max_pc',*/'max_users','square', 'phone', 'address');
		} 
        else 
        {
			$values = array('status', 'name', 'parent', 'number','expire_date','contact','operator','max_ms',/*'max_pc',*/'max_users','square', 'email', 'phone', 'address');
			$model = Jelly::factory('user');
			$model->username = 'ac'.text::random('alpha', 6);
            
            $parent = Arr::get($_POST, 'parent', 0);
            
			$model->color = $model->getNewUserColor($parent);
			
			if(!Arr::get($_POST,'password_text', NULL))
			{
				$this->request->response = JSON::error('Не задан пароль!');
				return;
			}
		}
		
		$model->update_date = time();
		
		
		if(Arr::get($_POST, 'max_ms', null))
		{
			$max_ms = (int) Arr::get($_POST,'max_ms', null);
			$count = $model->get_stations_count();
			
			if($count and $count > $max_ms)
			{
					$this->request->response = JSON::error('Количество Моб.станций не может быть меньше уже активированных!');
					return;
			}
		}
		
				
		if(Arr::get($_POST,'password_text',NULL))
		{
			
			$_POST['password_confirm'] = $_POST['password'] = $_POST['password_text']; 
			
			$values[]='password';
			$values[]='password_text';
			$values[]='password_confirm';
		}
		
		if(arr::get($_POST,'parent', NULL))
		{
			$_POST['parent'] = (int)$_POST['parent'];
			if($_POST['parent'] == $model->id()) $_POST['parent'] = 0;
		}
        else
            $_POST['parent'] = 0;
				
		
		$model->set(Arr::extract($_POST, $values));
		
		$status = Arr::get($_POST, 'status', 1);
		
		if($status < 0 or $status > 6) $status = 1;
				
		if($status > 3) {
			
			$status -= 3;
			$model->status = $status;
			
			$model->manual = 1;
			
		}
		else
		{
			$model->manual = 0;
		}
		
		if((int)$model->activate_date == 0)
		{
				$model->activate_date	= time();
		}
		
		if(Arr::get($_POST, 'expire_date', null))
		{
			$time = strtotime(ACDate::convertMonth($_POST['expire_date']));
			
			unset($_POST['expire_date']);
			$model->expire_date	= $time; 
		}
							
		if($status == 3) 	$model->is_active = 0;	
		else 				$model->is_active = 1;	
		
		
		
		$model->deleted = 0;
		$model->is_root = 0;
		
		if($model->parent->id())
		{
			$model->path = $model->parent->path.'/'.$model->parent->id().'/';
		}
		else
		{
			$model->path = '';
		}
		$model->save();
		$this->_append($model);
		
		$addons = array();
		foreach($_POST as $key => $value)
		{
			if(UTF8::strpos($key, 'add_field_') !== false and UTF8::strpos($key, 'label') === false)
			{
				$addons[(int)UTF8::str_ireplace('add_field_', '', $key)] = $value;
			}
		}
		
		$add 		= Jelly::select('farm_field')->execute();
		$to_remove 	= array();
		
		foreach($add as $a)
		{
			if(array_key_exists('add_field_'.$a->id().'_label', $_REQUEST))
			{
				if(UTF8::strlen($_REQUEST['add_field_'.$a->id().'_label']))
					$a->name = $_REQUEST['add_field_'.$a->id().'_label'];
				else	
					$a->name = '(не задано)';
				
				$a->save();	
			}
			
			if(!array_key_exists($a->id(), $addons))
			{
				$to_remove[] = $a->id();
			}
		}
		
		$new_fields = array();
		
		foreach($_POST as $key => $value)
		{
			if(UTF8::strpos($key, 'insert_field_') !== false)
			{
				$id = (int)UTF8::str_ireplace('insert_field_', '', $key);
				
				if(array_key_exists('name_insert_'.$id, $_POST))
				{
					$new_fields[] = array(	'name' => $_POST['name_insert_'.$id],
											'value' => $value
										 );
				}
			}
		}
		
		//Убираем нах все значения дополнительных полей у текущего юзера 
		
		if($primary_key)
		{
			Jelly::delete('value')->where('user', '=', $primary_key)->execute();
		}
		
				
		if(count($to_remove))
		{
			Jelly::delete('farm_value')->where('field', 'IN', $to_remove)->execute();
			Jelly::delete('farm_field')->where('_id', 'IN', $to_remove)->execute();
		}
		foreach($new_fields as $field)
		{
			$f = Jelly::factory('farm_field');
			$f->name = $field['name'];
			$f->save();
			
			$v 			= Jelly::factory('value');
			$v->user 	= $model->id();
			$v->field 	= $f->id();
			$v->value	= $field['value'];
			$v->save();
			
			unset($v);
			unset($f);		
		}
		
		foreach($addons as $key => $value)
		{
			$v 			= Jelly::factory('value');
			$v->user 	= $model->id();
			$v->field 	= $key;
			$v->value	= $value;
			$v->save();
			
			unset($v);
		}
		
		$model->set_status();

		//если сохранение нового хозяйства
		if(!Arr::get($_POST, $model_meta->primary_key(), false))
        {
			$this->send_welcome_email($model, (string)Arr::get($_POST,'contact',''));
		}

		$this->request->response = JSON::reply("Лицензиат сохранен успешно!");
	}
	
	public function action_delete()
	{
		$id 		= Arr::get($_POST, '_id', null);
		$password 	= Arr::get($_POST, 'password', null);
		
		if(!$id or !$password)
		{
			$this->request->response = JSON::error("password_invalid");
			return;
		}
		
		$user = Auth::instance()->get_user();
		
		$salt = Auth::instance()->find_salt(Auth::instance()->password($user->username));
		$password = Auth::instance()->hash_password($password, $salt);
		
		if($password == $user->password)
		{
			$remove = Jelly::select('user')->with('parent')->where('deleted', '=', 0)->where('is_root', '=', 0)->where('_id', '=', $id)->limit(1)->execute();
			
			if(!($remove instanceof Jelly_Model) or !$remove->loaded())
			{
				$this->request->response = JSON::error("Пользователя не существует!");
				return;
			}
			
			$count = Jelly::select('user')->where('parent', '=', $remove->id())->where('deleted', '=', 0)->count();
			
			if($count)
			{
				$this->request->response = JSON::error("Измените родительский лицензиат у всех дочерних лицензиатов или удалите их!");
				return;
			}
			
			$remove->is_active 		= 1;
			$remove->deleted 		= 1;
			$remove->status		 	= Model_User::STATUS_STOP;
			$remove->manual		 	= 1;
			
			$remove->save();	 
			
			if($remove->parent->id())
			{
				$count = Jelly::select('user')->where('parent', '=', $remove->parent->id())->where('deleted', '=', 0)->count();
				
				if(!$count)
				{
					if($remove->parent->parent->id()) {
						$this->request->response = JSON::reply("Пользователь удален успешно", '/user/list/'.$remove->parent->parent->id());
					}
					else
					{
						$this->request->response = JSON::reply("Пользователь удален успешно", '/user/list/');
					}
				}
				else 
					$this->request->response = JSON::reply("Пользователь удален успешно");
			}	
			else
				$this->request->response = JSON::reply("Пользователь удален успешно");
			
			Database::instance()->query(Database::DELETE, 'DELETE FROM user_stations WHERE user_id = '.(int)$id, false);	
		}
		else $this->request->response = JSON::error("password_invalid");
		
		
	}
	
	
	public function action_create_root()
	{
		// Рут всегда должен быть один (логин root и is_root = 1)
		$root = Jelly::select('user')->where('username', '=', 'root')->limit(1)->execute();
		
		if($root instanceof Jelly_Model and $root->loaded())
		{
			$root->password 		= 'agroclever';
			$root->password_confirm = 'agroclever';
			$root->is_active 	= 1;
			$root->is_root	 	= 1;
			$root->save();
			
			return;
		}
		
		$root = Jelly::factory('user');
		
		$root->username 		= 'root';
		$root->password 		= 'agroclever';
		$root->password_confirm = 'agroclever';
		$root->email			= 'root@ac.invoodoo.com';
		$root->is_active 		= 1;
		$root->is_root	 		= 1;
		$root->name		 		= 'Root User';
		$root->activate_date	= date('d.m.Y');
		
		try 
		{
			$root->save();
		}
		catch(Validate_Exception $e)
		{
			echo implode('; ', $e->array->errors('validate',true));
		}
	}
	
	
	public function action_license()
	{
		while(1)
		{
			$license = rand(100000000, 999999999);
			$test	 = Jelly::select('license')->where('number', '=', $license)->limit(1)->execute();
			if($test instanceof Jell_Model and $test->loaded()) coninue;
			else break;
		}
		
		$this->request->response = JSON::reply($license);
		return $license;
	}
	
	public function action_list($parent = null)
	{
		
		// Костыль, фиксящий баг Сенчи
		if(!$parent) $parent	 = Arr::get($_REQUEST, 'node', null);
		
		$plain	 = Arr::get($_REQUEST, 'plain', null);
		
		if($parent == 'root') $parent = null;
		if($plain) $parent = null;
		
		$query 	= Arr::get($_REQUEST, 'query', null);
		
		if(!$parent)
		{
			$this->request->response = Jelly::factory('license')->list_licencees($query);
			return;
		}
		else
		{
			$records = Jelly::select('farm')->with('license')->select('_id', array('_id', 'top_parent'), 'is_active', 'name', 'parent', 'activate_date', 'expire_date', 'max_ms', 'number', 'status', 'manual', 'deleted')->where('deleted','=', 0);
			
			$license = false;
			
			if(UTF8::strpos($parent, 'license') !== false)
			{
				$license_id = (int)str_replace('license_', '', $parent);
				$license = true;
				$parent = $license_id;
			}
		}
		
		
		
		if($query) $records =  $records->where_open()->where('name', 'LIKE', '%'.$query.'%')->or_where('license.name', 'LIKE', '%'.$query.'%')->or_where('license.number', 'LIKE', '%'.$query.'%')->where_close();

		if($parent)
		{
			if($license)
				$records =  $records->where('license', '=', (int)$license_id)->where('parent', '=', 0);
			else	
				$records =  $records->where('parent', '=', (int)$parent);	
		} 
		
		//if(!$plain and !$parent) $records->where('parent', '=', 0);
		
		$count = clone $records;
		$count = $count->count();
		
		if(!$plain) 
		{
			//$records =  $records->select(array('('.db::expr(Jelly::select('user')->select(db::expr('count(*)'))->where('parent', '=', db::expr('top_parent'))).')', 'count_childs'));
			$records =  $records->select(array(db::expr('(SELECT COUNT(*) FROM `'.Jelly::meta('farm')->table().'` WHERE `path` LIKE CONCAT(\'%/\',`top_parent`, \'/%\') AND `deleted` = 0)'), 'count_childs'));
		}
		
		$records = $records->order_by('status', 'DESC')->order_by('manual', 'DESC')->order_by('name', 'ASC');
		
		$records = $records->execute();
		//echo $records->compile(Database::instance());
		                		
		$r = array();
		
		$status 			= Twig::factory('farm/status');
		
		foreach($records as $record)
		{
			
			$count_childs = $record->get('count_childs');
			
			
			$status->status 	= (int)$record->status;
			$status->manual 	= (int)$record->manual;
			
			$r[] = array(
				'id'		=> $record->id(),
				'text'		=> UTF8::strlen($record->name) < 25 ? $record->name : UTF8::substr($record->name, 0, 22).'...',
				'source' 	=> '/farm/read/'.$record->id(),
				'children'	=> $count_childs,
				'status'	=> $status->render(),
				'number'	=> $record->number,
				'parent'	=> (int)$record->get('parent_id') ? (int)$record->get('parent_id') : 'license_'.$license_id,
				'leaf'		=> true
			);
		}
				
		$this->request->response = JSON::tree($r, $count);
	}
	
	public function action_create()
	{
		$view = Twig::factory('farm/create');
		$view->properties 	= true;		
		$view->create 		= true;
		$view->edit 		= true;
		
		$view->user 		= array('number' => $this->action_license(), 'activate_date' => date('d.m.Y'));
		
		$view->count_ms			= 0;
		$view->count_pc			= 0;
		$view->addons =  Jelly::select('farm_field')->order_by('order', 'ASC')->execute();

		$view->activate_date 	= array('day' => date('d', (int)time()), 'month' => date('m', (int)time()), 'year' => date('Y', (int)time()) );
		$view->expire_date 		= array('day' => date('d', (int)time()), 'month' => date('m', (int)time()), 'year' => date('Y', (int)time() )+1);		
						
		$this->request->response = JSON::reply($view->render());
	}
	
	
	public function action_read($id = null)
	{
		return $this->action_edit($id, true);
	}
	
	public function action_edit($id = null, $read = false, $billing = false)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		
		$user = Jelly::select('farm')->with('parent')->with('license')->load($id);
		
		if(!($user instanceof Jelly_Model) or !$user->loaded())
		{
			$this->request->response = JSON::error(__("No such user"));
			return;
		}
		
		if(!$read)
		{		
			$view = Twig::factory('farm/edit');
			$view->edit			 	= true;

			$view->hasChildren 		= Jelly::select('farm')->where('parent_id', '=', $id)->count() > 0;
		}
		else
		{
			$view = Twig::factory('farm/read');
		}
		
				
		$view->user 			= $user->as_array();
		
		if(!$billing)
			$view->properties 		= true;
		else	
			$view->billing	 		= true;
		
		$view->count_ms			= (int)$user->license->get_stations_count();
		$view->count_pc			= (int)$user->license->get_pc_count();
		$view->count_subuser	= (int)$user->license->get_subuser_count();
		
		$alert					= $user->license->get_ms_alert($view->count_ms);
		
		if($alert !== false)
			$view->ms_alert = $alert;
		
		//$alert					= $user->get_pc_alert($view->count_pc);
		$alert					= $user->license->get_subusers_alert($view->count_subuser);
		
		if($alert !== false)
			$view->subusers_alert = $alert;
			//$view->pc_alert = $alert;
			
		$alert					= $user->license->get_expire_alert();
		
		if($alert !== false)
			$view->expire_alert = $alert;	
		else
			$view->expire_blank_alert = true;
			
		if(!$user->square)
			$view->field_alert = 'yellow';
		else		
			$view->field_alert = 'green';
		
        $addons = array();
        $values = array();
        
		$addons = Jelly::select('farm_field')->order_by('_id', 'ASC')->execute();
		$values = Jelly::select('farm_value')->with('field')->where('farm', '=', $user->id())->execute();
		
		$v 		= array();
		foreach($values as $t)
		{
			$v[$t->field->id()] = $t->value;
		}
			
		$r = array();
			
		foreach($addons as $a)
		{
			$r[] = array( 	'_id' 	=> $a->id(),
							'name'	=> $a->name,
							'value' => array_key_exists($a->id(), $v) ? $v[$a->id()] : ''
						);
		}
			
		$view->addons =  $r;

		$view->activate_date 	= array('day' => date('d', (int)$user->activate_date), 'month' => date('m', (int)$user->activate_date), 'year' => date('Y', (int)$user->activate_date) );
		
		if(!$id)
		{
			$view->expire_date 		= array('day' => date('d'), 'month' => date('m'), 'year' => (int)date('Y') + 1 );
		}
		else
		{
			$view->expire_date 		= array('day' => date('d', (int)$user->expire_date), 'month' => date('m', (int)$user->expire_date), 'year' => date('Y', (int)$user->expire_date) );	
		}
		
				
		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_map($id = null)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		
		$user = Jelly::select('user')->with('parent')->load($id);
		
		if(!($user instanceof Jelly_Model) or !$user->loaded())
		{
			$this->request->response = JSON::error(__("No such user"));
			return;
		}
		
		$view = Twig::factory('user/map');
		$view->map = true;		
		$view->user = $user;
				
		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_billing($id = null)
	{
		return $this->action_edit($id, true, true);
	}
	

	public function action_list_root()
	{
		$list = Jelly::select('user')->where('deleted', '=', 0)->where('is_active', '=', 1)->where('is_root', '=', 0);
		
		$query = Arr::get($_REQUEST, 'query', null);
		
		if($query)
		{
			$list->where('name', 'LIKE', '%'.$query.'%');
			$count = clone $list;
		}
		else
		{ 
			$count = clone $list;
		}
		
		$count = $count->count();
		
		$list = $list->order_by('status', 'DESC')->order_by('manual', 'DESC')->order_by('name', 'ASC')->execute();
		
		$r = array();
		
		$r[] = array(	'value' => null,
						'text'	=> '(отсуствует)');
		
		$status 			= Twig::factory('user/status');
		
		foreach($list as $l)
		{
			
			$status->status = (int)$l->status;
			$status->manual = (int)$l->manual;
						
			$r[] = array(	'value' => $l->id(),
							'text'	=> UTF8::strlen($l->name) < 25 ? $l->name : UTF8::substr($l->name, 0, 22).'...',
							'status'=> $status->render()
						);
		}
		
		$this->request->response = JSON::tree($r, $count);
	}
	

	public function action_build_tree()
	{
		$users = Jelly::select('user')->where('parent', '=', 0)->execute();
		
		foreach($users as $user)
		{
			$user->path = '';
			$user->save();
			$this->_append($user);
		}
		
	}
	
	private function _append($user)
	{
		$children = Jelly::select('user')->where('parent', '=', $user->id())->execute();
		
		foreach($children as $child)
		{
			$child->path = $user->path.'/'.$user->id().'/';
			$child->save();
			$this->_append($child);
		}
	}
	
	private function send_welcome_email($user, $contact)
	{
	    $email =  Twig::factory('user/email');

		$from_email = (string)Kohana::config('application.from_email');

		$email->contact = $contact;
		$email->user = $user->as_array();
        $email->activate_date = date('d.m.Y', (int)$user->activate_date);
        $email->expire_date = date('d.m.Y', (int)$user->expire_date);
		$email->link = Kohana::config('application.root_url').'client/login?license='.$user->number;

		Email::connect();
		Email::send((string)$user->email,
					(string)$from_email,
					(string)'Ваша учетная запись в системе АгроКлевер',
					(string)$email->render(),
					 true);
	}
	
} // End Welcome
