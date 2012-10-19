<?php
class Controller_API_System extends AC_API
{
	private $__protocol = array();
	
	const TIME_NOT_DEFINED 		= 0x0006;
	const TIME_IS_INVALID	 	= 0x0007;
	const USER_NOT_DEFINED 		= 0x0008;
	const USER_INVALID	 	= 0x0009;
	const USER_RESTRICTED 		= 0x000A;
	const DATA_NOT_DEFINED 		= 0x000B;
	const DATA_INVALID	 	= 0x000C;
	const VERSION_NOT_DEFINED	= 0x000D;
	const VERSION_NOT_FOUND	 	= 0x000E;
	
	private function set($slug, $data, $deleted)
	{
		$this->__protocol[$slug] = array('updated' => $data, 'deleted' => $deleted);
	}
	
	private function get($slug)
	{
		return Arr::get($this->__protocol, $slug, null);
	}
	
	public function action_read()
	{
		$time = Arr::get($_REQUEST, 'time', NULL);
		
		if(!array_key_exists('time', $_REQUEST) or $_REQUEST['time'] == '')
		{
			return $this->report_error(self::TIME_NOT_DEFINED);
		}
		
		
		if(!is_numeric($time))
		{
			return $this->report_error(self::TIME_IS_INVALID);
		}
		
		$time = (int)$time;
		if($time < 0)
		{
			return $this->report_error(self::TIME_IS_INVALID);
		}
		
		// Получаем полный перечень того, что должно входить в протокол
		$types = Jelly::select('type')->order_by('slug', 'ASC')->execute();
		
		try
		{
			foreach($types as $type)
			{
				$this->set( 
							$type->slug, 
							Jelly::factory($type->model)->get_updated($this->license, $time),
							Jelly::factory($type->model)->get_deleted($this->license, $time)
						  );
			}
		}
		catch(Exception $e)
		{
			if(Kohana::$environment != Kohana::PRODUCTION)
			{
				Kohana::exception_handler($e);
			}
			else
				return $this->report_error(AC_API::SERVER_EXCEPTION);	
		}
		
		$this->__protocol['result'] = true;
		$this->__protocol['time'] 	= time();
		$this->request->response = json_encode($this->__protocol);
	}
	
	public function action_update()
	{
		$user_id = Arr::get($_REQUEST, 'user_id', NULL);
		
		if(!array_key_exists('user_id', $_REQUEST) or $_REQUEST['user_id'] == '')
		{
			return $this->report_error(self::USER_NOT_DEFINED);
		}
		
		if(UTF8::strpos($user_id, 'root_' ) !== false)
		{
			$user_id = UTF8::str_ireplace('root_', '', $user_id);
			$user_id = (int)$user_id;
			
			if($user_id != $this->license->id())
			{
				return $this->report_error(self::USER_RESTRICTED);
			}
		}
		else
		{
			$user_id = (int)$user_id;
			
			$user 	 = Jelly::factory('subuser')->get_valid()->where('farm', '=', $this->license->id())->load($user_id);
			
			if(!($user instanceof Jelly_Model) or !$user->loaded() or !$user->is_root)
			{
				return $this->report_error(self::USER_INVALID);
			}
		}
				
		// Создаем правку
		$version = Jelly::factory('version');
		
		$version->create_date 	= time();
		$version->update_date 	= 0;
		$version->deleted 		= 0;
		$version->result 		= 0;
		$version->farm	 		= $this->license->id();
		$version->station 		= $this->station->id();
		$version->user	 		= isset($user) ? $user : null;
		$version->manager 		= null;
		
		$version->save();
		
		// Получаем полный перечень того, что должно входить в протокол
		$types = Jelly::select('type')->order_by('slug', 'ASC')->execute();
		
		$data  = Arr::get($_REQUEST, 'data', null);
		
		if(!$data)
		{
			return $this->report_error(self::DATA_NOT_DEFINED);
		}
		
		$tdata = $data;
		$data = json_decode($data, true);
		
		if(!$data)
		{
			$data = stripslashes($tdata);
			$data = json_decode($data, true);
		}	
		
		if(!is_array($data))
		{
			//var_dump($tdata);
			//var_dump($data);
			return $this->report_error(self::DATA_INVALID);
		}
		
		try
		{
			foreach($types as $type)
			{
				$objects = Arr::get($data, $type->slug, array());
				
				if(!is_array($objects))
					continue;
				
				if(array_key_exists('updated', $objects) and is_array($objects['updated']))
				{
					foreach($objects['updated'] as $o)
					{
						if(!array_key_exists('_id', $o))
							continue;
						
						$obj = Jelly::factory('object');
							
						$obj->deleted 		= 0;
						$obj->result		= Model_Object::STATUS_WAIT;
						$obj->data			= serialize($o);
						$obj->create_date	= time();
						$obj->update_date	= 0;
						$obj->type			= $type->id();
						$obj->version		= $version->id();
						
						if(		is_null($o['_id']) 
							or  trim($o['_id']) == '' 
							or $o['_id'] == '0' 
							or (is_numeric($o['_id']) and $o['_id'] == 0)
							or $o['_id'] == 'null')
						{
							
							$obj->operation		= Model_Object::OPERATION_CREATE;
							$obj->object_id		= null;
						
							$obj->save();
							
							continue;
						}
						
						if(!Jelly::factory($type->model)->valid_id($this->license, $o['_id']))
							continue;
						
						$obj->object_id		= (string)$o['_id'];
						$obj->operation		= Model_Object::OPERATION_UPDATE;
						$obj->save();	
					}
				}
				
				if(array_key_exists('deleted', $objects) and is_array($objects['deleted']))
				{
					foreach($objects['deleted'] as $o)
					{
						$obj = Jelly::factory('object');
							
						$obj->deleted 		= 0;
						$obj->result		= Model_Object::STATUS_WAIT;
						$obj->data			= null;
						$obj->create_date	= time();
						$obj->update_date	= 0;
						$obj->type			= $type->id();
						$obj->version		= $version->id();
						
						if(!Jelly::factory($type->model)->valid_id($this->license, $o['_id']))
							continue;
						
						$obj->object_id		= $o['_id'];
						$obj->operation		= Model_Object::OPERATION_DELETE;
						$obj->save();	
					}
				}	
				
			}
			
			// Автоматическое применение правки (commit). Временное явление типа.
			$version->commit();
		}
		catch(Exception $e)
		{
			if(Kohana::$environment != Kohana::PRODUCTION)
			{
				Kohana::exception_handler($e);
			}
			else
				return $this->report_error(AC_API::SERVER_EXCEPTION);	
		}
	
		$this->request->response = json_encode(array('result' => true, 'version' => $version->id()));
	}


	public function action_status(){
	    $version_id = Arr::get($_REQUEST, 'version', NULL);

	    if(is_null($version_id)){
		return $this->report_error(self::VERSION_NOT_DEFINED);
	    }

	    $version = Jelly::select('version')->where('_id', '=', (int)$version_id)->limit(1)->execute();
	    if(!($version instanceof Jelly_Model) || !$version->loaded()){
		return $this->report_error(self::VERSION_NOT_FOUND);
	    }

	    if($version->result==Model_Version::STATUS_WAIT || $version->deleted==true){
		$this->request->response = json_encode(array('result' => false));
		return;
	    }

	    if($version->result==Model_Version::STATUS_SUCCESS){
		$this->request->response = json_encode(array('result' => true));
		return;
	    }

	    if($version->result==Model_Version::STATUS_FAIL){
		
		$data = array();
		$types = Jelly::select('type')->order_by('slug', 'ASC')->execute();

		foreach($types as $type){
		    $updated = Jelly::factory('object')->get_errors($version->id(), $type->id())->and_where('operation', '<>', Model_Object::OPERATION_DELETE)->execute();
		    $deleted = Jelly::factory('object')->get_errors($version->id(), $type->id())->and_where('operation', '=', Model_Object::OPERATION_DELETE)->execute();

		    if($updated || $deleted) { $data[$type->slug] = array(); }
		    foreach($updated as $u){
			$u_data = unserialize($u->data);
			$err = unserialize($u->errors);
			if($err==false){
			    $u_data['errors'] = $u->errors;
			}else{
			    $u_data['errors'] = $err;
			}
			$data[$type->slug]['updated'][] = $u_data;
		    }
		    foreach($deleted as $d){
			$d_data['_id'] = $d->object_id;
			$err = @unserialize($d->errors);
			if($err==false){
			    $d_data['errors'] = $d->errors;
			}else{
			    $d_data['errors'] = $err;
			}
			$data[$type->slug]['deleted'][] = $d_data;
		    }
		}

		$data['result'] = false;
		$this->request->response = json_encode($data);
		return;
	    }

	    $this->request->response = json_encode(array('result' => false));
	    return;
	}
	
	
} 
?>
