<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Station extends AC_Controller
{
	
	public    $auto_render  = false;
	
	public function action_blacklist()
	{
		$records = Jelly::select('blacklist')->with('station');
		
		$query 	= Arr::get($_REQUEST, 'query', null);
		
		if($query) $records =  $records->where('station.name', 'LIKE', '%'.$query.'%');
		
		$count = clone $records;
		$count = $count->count();
		
		$records = $records->order_by('station.name', 'ASC');
				
		$records = $records->execute();
		//echo $records->compile(Database::instance());
				
		$r = array();
		
		foreach($records as $record)
		{
			
			$r[] = array(
				'id'		=> $record->id(),
				'text'		=> UTF8::strlen($record->station->name) < 25 ? $record->station->name : UTF8::substr($record->station->name, 0, 22).'...',
				'source' 	=> '/station/blackread/'.$record->id(),
				'leaf'		=> true
			);
		}
				
		$this->request->response = JSON::tree($r, $count);
	}
	
	public function action_blackread($id)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("Mobile Station ID is not specified"));
			return;
		}
		
		$record = Jelly::select('blacklist')->with('station')->load($id);
		
		if(!($record instanceof Jelly_Model) or !$record->loaded() or !$record->station->id())
		{
			$this->request->response = JSON::error(__("No such blacklisted mobile station exists"));
			return;
		}
		
		$view = Twig::factory('station/blackread');
		
		$view->blacklist 	= $record;
		
		$view->user_count   = $record->station->users->count();
		$view->block_date   = ACDate::formatDateWithTime($record->create_date);
								
		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_licensees($id)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("Mobile Station ID is not specified"));
			return;
		}
		
		$record = Jelly::select('blacklist')->with('station')->load($id);
		
		if(!($record instanceof Jelly_Model) or !$record->loaded() or !$record->station->id())
		{
			$this->request->response = JSON::error(__("No such blacklisted mobile station exists"));
			return;
		}
		
		$view = Twig::factory('station/licensees');
		$view->blacklist 	= $record;
		
		$view->licensees    = $record->station->users->as_array();
		
		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_ms_update()
	{
		
		$id = Arr::get($_POST, 'user_id', null);
		
		if(!$id)
		{
			$this->request->response = JSON::error(__("No such user"));
			return;
		}
		
		$user = Jelly::select('user')->load($id);
		
		if(!($user instanceof Jelly_Model) or !$user->loaded())
		{
			$this->request->response = JSON::error(__("No such user"));
			return;
		}
		
		$ids = Arr::get($_REQUEST, 'rm', null);
		
		$ids = explode(',', $ids);
		
		if(is_array($ids) and count($ids))
		{
			for($i = 0; $i < $k = count($ids); $i++)
			{
				$ids[$i] = (int)$ids[$i];
				$user->remove('stations', $ids[$i]);
			}
			
			$user->save();
			$user->set_status();
		}
		/*
		foreach($_REQUEST as $key => $value)
		{
			if(UTF8::strpos($key, 'ms_name_') !== false)
			{
				$id = (int)UTF8::str_ireplace('ms_name_', '', $key);
				
				if($id)
				{
					$station = Jelly::select('station', $id);
					if($station instanceof Jelly_Model and $station->loaded())
					{
						if($value != '') {
							$station->name = $value;
							$station->save();
						}	
					}
				}
			}
		}*/
		
		$count = $user->get_stations_count();
		
		if(!$count)
		{
			$this->request->response = JSON::reply("Информация о МС успешно обновлена", '/user/read/'.$user->id());
		}
		else $this->request->response = JSON::reply("Информация о МС успешно обновлена", '/station/ms_list/'.$user->id());
	}
	
	
	public function action_ms_list($id = null)
	{
		return $this->action_ms_edit($id, true);
	}
	
	public function action_ms_edit($id = null, $read = false)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}
		
		$user = Jelly::select('user')->with('stations')->load($id);
		
		if(!($user instanceof Jelly_Model) or !$user->loaded())
		{
			$this->request->response = JSON::error(__("No such user"));
			return;
		}
		
		$view = Twig::factory('station/ms_list');
		
		$view->user_id 	= $user->id();
		
		if(!$read)
		{
			$view->edit	 	= true;
		}
		
		$view->ms = $user->stations->as_array();
						
		$this->request->response = JSON::reply($view->render());
	}
	
	
	public function action_ms($id)
	{
		if(!$id) 
		{
			$this->request->response = JSON::error(__("Station ID is not specified"));
			return;
		}
		
		$mode = Arr::get($_REQUEST, 'mode', null);
		
		$view = Twig::factory('station/ms');
		
		$station = Jelly::select('station')->where('station._id', '=', (int)$id)->load();
		
		if(!($station instanceof Jelly_Model) or !$station->loaded())
		{
			$this->request->response = JSON::error(__("Invalid station Id"));
			return;
		}
		
		$view->user_id 	= Arr::get($_GET, 'user_id', null);
		$view->station 	= $station;
		
		if($mode == 'blacklist')
		{
			$view->blacklist 	= true;
			$view->blacklist_id = Arr::get($_REQUEST, 'blacklist_id', 0);
		}
		
		if($mode == 'blacklist')
			$stats = Jelly::select('stat')->where('station', '=', $station->id())->where('type', '=', 1)->order_by('date', 'DESC')->order_by('_id', 'DESC')->execute();
		else
			$stats = Jelly::select('stat')->where('station', '=', $station->id())->order_by('date', 'DESC')->order_by('_id', 'DESC')->execute();
		
		$_r = array();

		foreach($stats as $s)
		{
			$_r[] = array(
				'date'		=> @ACDate::formatDate($s->date),
				'time'		=> @date('H:i:s', $s->date),
				// Stage 1 only
				'user' 		=> 'Неизвестный пользователь',
				'message'       => $s->message,
				'type'		=> (int)$s->type,
				'in'   		=> $s->in,
				'out'  		=> $s->out	 
			);
		}
		
		$view->stats = $_r;
						
		$this->request->response = JSON::reply($view->render());
		
	}


} // End Welcome
