<?php defined('SYSPATH') or die('No direct script access.');

class Controller_License extends AC_Controller
{
	
	public    $auto_render  = false;
	
	public function action_debug()
	{
		$view = Twig::factory('farm/debug');
		
		$this->request->response = $view->render();
	}
	
	public function action_update()
	{
		$model_meta = Jelly::meta('license');
		//Редактирование или обновление
        
        $lic_number = Arr::get($_POST,'number', '');
        $lic_number = trim($lic_number);
        $_POST['number'] = $lic_number;
        if(!$lic_number || strlen($lic_number)!=9 || !ctype_digit($lic_number) ) {
            $this->request->response = JSON::error('Номер лицензии задан неверно! (должен состоять из 9 цифр)');
            return;
        }
        
        $test	 = Jelly::select('license')->where('number', '=', $lic_number)->limit(1)->execute();
        if($test instanceof Jell_Model and $test->loaded()){
            $this->request->response = JSON::error('Заданный номер лицензии не уникален!');
            return;
        }
        
		if($primary_key = arr::get($_POST, $model_meta->primary_key(), NULL)){
			
			$model = Jelly::select('license')->with('user')->load((int)$primary_key);
			
			$old 	  = $model->as_array();
			$old_time = $old['expire_date'];
			
			
			$values = array('username','status', 'name', 'number','max_ms',/*'max_pc',*/'max_users','square', 'phone', 'address_country', 'address_region', 'address_city', 'address_zip', 'address_street', 'username', 'password_text', 'contact', 'max_fields', 'square');
		} 
        else 
        {
			$values = array('username', 'status', 'name',  'number','contact','max_ms',/*'max_pc',*/'max_users','square', 'email', 'phone', 'address_country', 'address_region', 'address_city', 'address_zip', 'address_street', 'contact',  'max_fields' );
			$model = Jelly::factory('license');           
			/*
			if(!Arr::get($_POST,'password_text', NULL))
			{
				$this->request->response = JSON::error('Не задан пароль!');
				return;
			}*/
		}
		
		$model->update_date = time();
		
		/*
		if(Arr::get($_POST, 'max_ms', null))
		{
			$max_ms = (int) Arr::get($_POST,'max_ms', null);
			$count = $model->get_stations_count();
			
			if($count and $count > $max_ms)
			{
					$this->request->response = JSON::error('Количество Моб.станций не может быть меньше уже активированных!');
					return;
			}
		}*/
		
	
		
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

		if(!$primary_key)
		{
			$t_user = Jelly::factory('user');
			$t_user->is_active	  = 1;
			$t_user->is_root	  = 0;
			$t_user->deleted	  = 0;
            $t_user->username          =  Arr::get($_POST, 'username', null);
		}
		else
		{
			$t_user = $model->user;
		}	
		
		$t_user->email	  = Arr::get($_POST, 'email', null);
        $t_user->username	  = Arr::get($_POST, 'username', null);
		
		if($t_user->email != '' and $t_user->username == '')
			$t_user->username = $t_user->email;
		
        if($t_user->username == '')
		{
			$t_user->username = 'ac'.Text::random('alpha', 15).'@forbidden.agroclever.com';	
			$t_user->is_active = 0;
		}
		
		$t_user->first_name = Arr::get($_POST, 'first_name', null);
		$t_user->last_name = Arr::get($_POST, 'last_name', null);
		$t_user->middle_name = Arr::get($_POST, 'middle_name', null);
//		$t_user->address = Arr::get($_POST, 'address', null);
		
		$address = arr::get($_POST,'address','');
		$address = @json_decode($address, true);

		$t_user->address_country = $address['country'];
		$t_user->address_region   = $address['region'];
		$t_user->address_city      = $address['city'];
		$t_user->address_zip       = $address['zip'];
		$t_user->address_street   = $address['street'];
		
		
		$t_user->phone = Arr::get($_POST, 'phone', null);
				
		$t_user->password_text = $t_user->password_confirm = $t_user->password = trim(Arr::get($_POST, 'password_text', null));
		
		$t_user->validate();
		$model->validate();

		$t_user->save();
		$model->user = $t_user->id();
		$model->save();
		
		$t_user->license  = $model->id();
		$t_user->save();
        
        //Стопицот старого кода по аддонам начинается здесь. 
        /*
		$addons = array();
		foreach($_POST as $key => $value)
		{
			if(UTF8::strpos($key, 'add_field_') !== false and UTF8::strpos($key, 'label') === false)
			{
				$addons[(int)UTF8::str_ireplace('add_field_', '', $key)] = $value;
			}
		}
		
		$add 		= Jelly::select('licenseproperty')->execute();
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
			Jelly::delete('licensepropertyvalue')->where('license', '=', $primary_key)->execute();
		}
				
		if(count($to_remove))
		{
			Jelly::delete('licensepropertyvalue')->where('field', 'IN', $to_remove)->execute();
			Jelly::delete('licenseproperty')->where('_id', 'IN', $to_remove)->execute();
		}
		foreach($new_fields as $field)
		{
			$f = Jelly::factory('licenseproperty');
			$f->name = $field['name'];
			$f->save();
			
			$v 			= Jelly::factory('licensepropertyvalue');
			$v->license	= $model->id();
			$v->field 	= $f->id();
			$v->value	= $field['value'];
			$v->save();
			
			unset($v);
			unset($f);		
		}
		
		foreach($addons as $key => $value)
		{
			$v 			= Jelly::factory('licensepropertyvalue');
			$v->license	= $model->id();
			$v->field 	= $key;
			$v->value	= $value;
			$v->save();
			
			unset($v);
		}
        */
        
        //Стопицот старого кода по аддонам кончается здесь. 
		
        //Тут в идеале должен быть один иф - и вьюхи сразу в один заход я не рискнул править
        //Этот цикл засовывает все доп. филды в один массив
		$addons = array();
		foreach($_POST as $key => $value)
		{
			if(UTF8::strpos($key, 'add_field_') !== false and UTF8::strpos($key, 'label') === false)
			{
				$addons[$_POST[$key.'_label']] = $value;
			}

			if(UTF8::strpos($key, 'insert_field_') !== false)
			{
				$id = (int)UTF8::str_ireplace('insert_field_', '', $key);
				
				if(array_key_exists('name_insert_'.$id, $_POST))
				{
					$addons[$_POST['name_insert_'.$id]] = $value;
				}
			}
		}
        
        //Получаем все доп. поля, убиваем ненужные
		$fields = $to_remove = array();
		
		foreach(Jelly::select('licenseproperty')->execute() as $a)
		{
            $fields[$a->name] = $a->id();
			
			if(!array_key_exists($a->name, $addons))
            {
                $to_remove[] = $a->id();
            }
            
		}
        
		if(count($to_remove))
		{
			Jelly::delete('licensepropertyvalue')->where('field', 'IN', $to_remove)->execute();
			Jelly::delete('licenseproperty')->where('_id', 'IN', $to_remove)->execute();
		}        
        
		//Убираем нах все значения дополнительных полей у текущего юзера 
		if($primary_key) Jelly::delete('licensepropertyvalue')->where('license', '=', $primary_key)->execute();
        
		foreach($addons as $field => $value)
		{
            //Поиск по индексу будет быстрее чем выбирать всё подряд.
            //Эта строка при отсутствии елемента вернет пустой обьект типа licenseproperty - лишний factory Делать не надо 
			
            if (!isset($fields[$field]))
            {
                $f = Jelly::factory('licenseproperty');
                $f->name = $field;
                $f->save();
                $fields[$f->name] = $f->id();
            }
			
			$v 			= Jelly::factory('licensepropertyvalue');
			$v->license	= $model->id();
			$v->field 	= $fields[$field];
			$v->value	= $value;
			$v->save();	
		}
        
		        
		$model->set_status();
		
		//если сохранение нового хозяйства
		if(!Arr::get($_POST, $model_meta->primary_key(), false))
        {
	 		$this->send_welcome_email($model);
	 		
	 		// Копируем поля из форматов для лицензии
//	 		$culture_types = Jelly::select('glossary_culturetype')->execute();
//
//	 		foreach($culture_types as $c)
//	 		{
// 				$t = Jelly::factory('client_culturetype');
// 				$t->name = $c->name;
// 				$t->license = $model;
// 				$t->save();
//			}
	 		
	 		
		}
		
		if(isset($old_time) and $old_time != $model->expire_date)
		{
			$email = Twig::factory('user/email_license_changed');
					
			$from_email = (string)Kohana::config('application.from_email');

			$email->user    		= $model->user->as_array();
			$email->activate_date   = date('d.m.Y', (int)$model->activate_date);
		    $email->expire_date 	= date('d.m.Y', (int)$model->expire_date);
											
			Email::connect();
			Email::send($model->user->email,
						(string)$from_email,
						(string)'Срок действия Вашей лицензии в системе АгроКлевер изменился',
						(string)$email->render(),
						 true);
		}

		$this->request->response = JSON::success(array('script' => "Лицензиат сохранен успешно!",
													   'url'    => null,
													   'success'    => true,
													   'license_id' => $model->id()));
	}
	
	public function action_delete()
	{
		$id 		= Arr::get($_POST, '_id', null);
		$password 	= Arr::get($_POST, 'password', null);
		
		if(!$id or !$password or (Auth::instance()->get_user()->password_text != $password))
		{
			$this->request->response = JSON::error("password_invalid");
			return;
		}
		
		$remove = Jelly::select('license')->where('deleted', '=', 0)->where('_id', '=', $id)->limit(1)->execute();
		
		if(!($remove instanceof Jelly_Model) or !$remove->loaded())
		{
			$this->request->response = JSON::error("Лицензии не существует!");
			return;
		}
		
		$count = Jelly::select('farm')->where('license', '=', $remove->id())->where('deleted', '=', 0)->count();
		
		if($count)
		{
			$this->request->response = JSON::error("Измените лицензию у всех дочерних хозяйств или удалите их!");
			return;
		}
		
		$remove->is_active 		= 0;
		$remove->deleted 		= 1;
		$remove->status		 	= Model_License::STATUS_STOP;
		$remove->manual		 	= 1;
		
		$remove->save();
		
		$this->request->response = JSON::reply("Лицензиат успешно удален!", '/farm/list/');	 
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
			if($test instanceof Jell_Model and $test->loaded()) continue;
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
		
		$records = Jelly::select('farm')->with('license')->with('admin')->select('_id', array('_id', 'top_parent'), 'is_active', 'name', 'parent', 'activate_date', 'expire_date', 'max_ms', 'number', 'status', 'manual', 'deleted')->where('deleted','=', 0)->where('is_root', '=', 0);
		
		$query 	= Arr::get($_REQUEST, 'query', null);
		
		if($query) $records =  $records->where('name', 'LIKE', '%'.$query.'%');

		if($parent) $records =  $records->where('parent', '=', (int)$parent);
		if(!$plain and !$parent) $records->where('parent', '=', 0);
		
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
				'parent'	=> (int)$record->get('parent_id'),
				'leaf'		=> true
			);
		}
				
		$this->request->response = JSON::tree($r, $count);
	}
	
	public function action_create()
	{
		$view = Twig::factory('license/create');
		$view->properties 	= true;		
		$view->create 		= true;
		$view->edit 		= true;
		
//		$view->user 		= array('number' => $this->action_license(), 'activate_date' => date('d.m.Y'));
        $view->user 		= array('number' => '', 'activate_date' => date('d.m.Y'));
		
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
		
		$user = Jelly::select('license')->with('user')->load($id);
		
		if(!($user instanceof Jelly_Model) or !$user->loaded())
		{
			$this->request->response = JSON::error(__("No such license"));
			return;
		}
		
		if(!$read)
		{		
			$view = Twig::factory('license/edit');
			$view->edit = true;

		}
		else
		{
			$view = Twig::factory('license/read');
		}
		
				
		$view->user 			= $user->as_array();
		$view->user['user']		= $user->user->as_array();
		
		if(stripos($user->user->username, 'forbidden.agroclever.com'))
		{
			$view->user['user']['username'] = '';
		}
		
		if(!$billing)
			$view->properties 		= true;
		else	
			$view->billing	 		= true;
		
		$view->count_ms			= (int)$user->get_stations_count();
		$view->count_pc			= (int)$user->get_pc_count();
		$view->count_fields		= (int)$user->get_fields_count();
		$view->square			= (int)$user->get_square();
		$view->count_subuser	= (int)$user->get_subuser_count();
		
		$alert					= $user->get_ms_alert($view->count_ms);
		
		if($alert !== false)
			$view->ms_alert = $alert;
		
		//$alert					= $user->get_pc_alert($view->count_pc);
		$alert					= $user->get_subusers_alert($view->count_subuser);
		
		if($alert !== false)
			$view->subusers_alert = $alert;
			//$view->pc_alert = $alert;
			
		$alert					= $user->get_expire_alert();
		
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
        
		$addons = Jelly::select('licenseproperty')->order_by('_id', 'ASC')->execute();
		$values = Jelly::select('licensepropertyvalue')->with('field')->where('license', '=', $user->id())->execute();
		
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
	
	private function send_welcome_email($user)
	{
	    if(!$user->user->email)
	    	return;
	    	
		$email =  Twig::factory('license/email');

		$from_email = (string)Kohana::config('application.from_email');

		$email->user = $user->as_array();
        $email->activate_date = date('d.m.Y', (int)$user->activate_date);
        $email->expire_date = date('d.m.Y', (int)$user->expire_date);
		$email->link = Kohana::config('application.root_url').'client/login?license='.$user->number;

		Email::connect();
		Email::send((string)$user->user->email, // ($to, $from, $subject, $message, $html = FALSE, $attachments = array())
					(string)$from_email,
					(string)'Ваша учетная запись в системе АгроКлевер',
					(string)$email->render(),
					 true,
					 array($_SERVER['DOCUMENT_ROOT'].'/media/Agro Clever login page.website'));
	}
    
} // End Welcome
