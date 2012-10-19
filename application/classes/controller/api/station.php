<?php
/**
 * Controller_API_Station
 * 
 * @package   
 * @author Sergei / Ardea / Creatiff / 
 * @version 2010
 * @access public
 * @description Класс реализует внешний программный интерфейс к БД (без интерфейсов).
 * 				На входе: GET / POST данные, на выходе - JSON
 */
class Controller_API_Station extends Ac_Controller
{
	
	/**
	 *  Принудительной авторизации не требуется
	 **/
	 protected $request_auth = false;
	 public    $auto_render  = false;
	 
	/** Нумерация изменена в соответствиями с пожеланиями Антона **/
	
	const HARDWARE_ID_IS_EMPTY 					= 0x0000;
	const LICENSE_NUMBER_IS_EMPTY 				= 0x0002;
	const LICENSE_NUMBER_IS_INVALID				= 0x0002;
	const NO_FREE_SLOTS							= 0x0004;
	
	const MOBILE_STATION_BLACKLISTED			= 0x0001;
	const LICENSE_IS_BLOCKED					= 0x0003;
	const ACTIVATION_CODE_IS_EMPTY				= 0x0005;
	const ACTIVATION_CODE_IS_INCORRECT			= 0x0005;
	const MOBILE_STATION_IS_NOT_REGISTERED		= 0x0006;
	const SERVER_ERROR							= 0x0007;
	const MOBILE_STATION_IS_NOT_BLACKLISTED		= 0x0009;
    const LICENSE_IS_NEW                		= 0x0008;
    const USERNAME_IS_EMPTY                     = 10000;
	const PASSWORD_IS_EMPTY                     = 10001;
    const INCORRECT_LOGIN                       = 10002;
    
	public function action_send()
	{
		$b = Jelly::select('blacklist', 15);
		$b->send_code();
	}
	
    /**
	 * Controller_Station_API::action_login()
	 * Производит первоначальную активацию (регистрацию) МС путем сверки номера лицензии и аппаратного ключа
     * 
     * @param username - имя пользователя
     * @param password - пароль
     * @param license - номер лицензии 
	 **/
    public function action_login()
    {
       	
        $username 		= UTF8::trim(Arr::get($_REQUEST, 'username', null));
		$password      	= UTF8::trim(Arr::get($_REQUEST, 'password', null)); 
        $license      	= UTF8::trim(Arr::get($_REQUEST, 'license', null));
        $hardware_id   	= UTF8::trim(Arr::get($_REQUEST, 'id', null));
        
        if(!$username)
		{
			return $this->report_error(self::USERNAME_IS_EMPTY);
		}
        
        if(!$password)
		{
			return $this->report_error(self::PASSWORD_IS_EMPTY);
		}
        
        $login_result = Auth::instance()->login($username,$password);
        
        if(!$login_result)
		{
			return $this->report_error(self::INCORRECT_LOGIN);
		}
        /*
        if($license == '')
        {
            $this->request->response = JSON::success(array());
            return true;
        }*/
       	
        $user = Jelly::select('license')->where('number', '=', $license)->where('deleted', '=', 0)->limit(1)->execute();
        
        if($user instanceof Jelly_Model and $user->loaded())
		{
			if(
				(
					(!$user->is_active or (time() >= $user->expire_date))
					and !$user->manual
				)
				or ($user->manual and $user->status == 3)
			)
				{
					
				if(!$hardware_id)
				{
					return $this->report_error(self::HARDWARE_ID_IS_EMPTY);
				}
					
				// Отключенная лицензия
				$attempts = Jelly::factory('attempts')->increment($hardware_id);
				
				$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);

				$station->append_log('Лицензия заблокирована', $this->get_request_length(), $this->get_response_length(), null, 1);
				
				/** Не в черном ли списочке станция после инкремента? **/
				if($this->is_blacklisted($hardware_id))
				{
					$station->append_log('Станция занесена в черный список', $this->get_request_length(), $this->get_response_length(), null, 1);
					return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
				}
				
				return $this->report_error(self::LICENSE_IS_BLOCKED, $attempts->attempts, 
				$station->name); // 0x0004
			}
        }
        else
        {
            return $this->report_error(self::LICENSE_NUMBER_IS_INVALID);
        }
        
        if(!(int)$user->user->last_login)
        {
			
            $user->user->last_login = time();
            $user->user->save();
            
            return $this->report_error(self::LICENSE_IS_NEW);
        }
        
       	$this->request->response = JSON::success(array());
    }

	/**
	 * Controller_API::action_register()
	 * Производит первоначальную активацию (регистрацию) МС путем сверки номера лицензии и аппаратного ключа
	 * 
	 * @return массив JSON:
	 *  result - признак успешности операции (true - успешна, false - неуспешна)
	 *  server - если операция успешна, то содержит домен сервера синхронизации
	 *  error  - код ошибки в константах выше
	 * 
	 * @param license 	- номер лицензии
	 * @param id		- аппаратный ключ МС
	 */
	public function action_register()
	{
		
		$license 		= UTF8::trim(Arr::get($_REQUEST, 'license', null));
		$hardware_id 	= UTF8::trim(Arr::get($_REQUEST, 'id', null));
		
		if($license 	== '') $license 	= null;
		if($hardware_id == '') $hardware_id = null;
		
		$log 			= Kohana_Log::instance();
		
		if(!$hardware_id)
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0001
		}
						
		/** Не в черном ли списочке станция **/
		if($this->is_blacklisted($hardware_id))
		{
			return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
		}
				
		if(!$license)
		{
			
			$attempts = Jelly::factory('attempts')->increment($hardware_id);
			
			$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);	
				
			if($station instanceof Jelly_Model and $station->loaded())
			{
				$station->append_log('Неверный ключ лицензии '.$license, $this->get_request_length(), $this->get_response_length(), null, 1);
			}
			
			/** Не в черном ли списочке станция после инкремента? **/
			if($this->is_blacklisted($hardware_id))
			{
				if($station instanceof Jelly_Model and $station->loaded())
				{
					$station->append_log('Станция занесена в черный список', $this->get_request_length(), $this->get_response_length(), null, 1);
				}
				return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
			}
			
			return $this->report_error(self::LICENSE_NUMBER_IS_EMPTY, $attempts->attempts,
			$attempts->station->id() ? $attempts->station->name : null); // 0x0000
		}
		
		/** Есть ли у нас вообще такой номер лицензии? **/
		
		$user = Jelly::select('license')->where('number', '=', $license)->where('deleted', '=', 0)->limit(1)->execute();
		
		if($user instanceof Jelly_Model and $user->loaded())
		{
			if(
				(
					(!$user->is_active or (time() >= $user->expire_date))
					and !$user->manual
				)
				or ($user->manual and $user->status == 3)
			)
				{
				// Отключенная лицензия
				$attempts = Jelly::factory('attempts')->increment($hardware_id);
				
				$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);

				$station->append_log('Лицензия заблокирована', $this->get_request_length(), $this->get_response_length(), null, 1);
				
				/** Не в черном ли списочке станция после инкремента? **/
				if($this->is_blacklisted($hardware_id))
				{
					$station->append_log('Станция занесена в черный список', $this->get_request_length(), $this->get_response_length(), null, 1);
					return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
				}
				
				return $this->report_error(self::LICENSE_IS_BLOCKED, $attempts->attempts, 
				$station->name); // 0x0004
			}
			
			
			$result = $user->activate_ms($hardware_id); 
			
			if($result == Model_LICENSE::ACTIVATE_OK)
			{
				Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();
				Jelly::delete('blacklist')->where('hardware_id', '=', $hardware_id)->execute();
				
				return $this->report_init_ok($hardware_id, $license);
			}
			else if ($result == self::SERVER_ERROR)
			{
				return $this->report_error($result);
			}
			else
			{
		
				$log->add('license error', 'Mobile station with hardware id #:hardware_id tries to perform activation with license #:license, but there are no free slots at the moment ', array(':hardware_id' => $hardware_id, ':license' => $license, ':license_old' => $user->number));
				
				$attempts = Jelly::factory('attempts')->increment($hardware_id);
				
				$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);	
				
				if($station instanceof Jelly_Model and $station->loaded())
				{
					$station->append_log('Неверный ключ лицензии '.$license, $this->get_request_length(), $this->get_response_length(), false, 1);
				}
				
				/** Не в черном ли списочке станция после инкремента? **/
				if($this->is_blacklisted($hardware_id))
				{
					if($station instanceof Jelly_Model and $station->loaded())
					{
						$station->append_log('Станция занесена в черный список', $this->get_request_length(), $this->get_response_length(), false, 1);
					}	
					return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
				}
			
									
				return $this->report_error(self::NO_FREE_SLOTS, $attempts->attempts, 
				($station instanceof Jelly_Model and $station->loaded()) ? $station->name : null ); // 0x0004
			};
			
		}
		else
		{
			/** Номера нет **/
			
			$log->add('license error', 'Mobile station with hardware id #:hardware_id tries to perform activation with license #:license, but this license number is invalid (no such number present in DB or user is deactivated) ', array(':hardware_id' => $hardware_id, ':license' => $license));
			
			/** Заносим в попытки **/
			$attempts = Jelly::factory('attempts')->increment($hardware_id);
			
			$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);	
				
			if($station instanceof Jelly_Model and $station->loaded())
			{
				$station->append_log('Неверный ключ лицензии '.$license, $this->get_request_length(), $this->get_response_length(), false, 1);
			}
			
			/** Не в черном ли списочке станция после инкремента? **/
			if($this->is_blacklisted($hardware_id))
			{
				if($station instanceof Jelly_Model and $station->loaded())
				{
					$station->append_log('Станция занесена в черный список', $this->get_request_length(), $this->get_response_length(), false, 1);
				}
				return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
			}
			
			if($attempts instanceof Jelly_Model)
				$station = $attempts->station;
			else
				$station = false;	
				
			return $this->report_error(self::LICENSE_NUMBER_IS_INVALID, $attempts->attempts, 
			($station instanceof Jelly_Model and $station->loaded()) ? $station->name : null ); // 0x0004
		}

	}
	
	/**
	 * self::report_error()
	 * Показывает JSON-описание ошибки
	 * 
	 * @param mixed $err -- текст ошибки
	 * @return
	 */
	private function report_error($err, $attempts = null, $name = null)
	{
		$arr = array('result' => false, 'error' => $err );
		
		if(!is_null($attempts))
		{
			if($attempts == 0) $attempts = 1;
			$arr['attempt'] = Kohana::config('application.blacklist_attempts') - (int)$attempts;
		}
		
		if($name)
		{
			$arr['name'] = $name;
		}
		//$this->request->headers['Content-type'] = 'application/json';
		$this->request->response = json_encode($arr); 
	}
	
	/**
	 * self::report_init_ok()
	 * Показывает информацию о том, что инициализация успешно завершена
	 * @param mixed $hardware_id -- аппаратный ключ МС
	 * @return
	 */
	private function report_init_ok($hardware_id, $license)
	{
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		
		$arr = array('result' => true );
		
		if($station instanceof Jelly_Model and $station->loaded()) {
			$arr['name'] = $station->name; 
			$station->append_log('МС зарегистрирована (' . $license . ')', $this->get_request_length(), $this->get_response_length(), null);
		}
		
		$this->request->response = json_encode($arr);
	}

	private function get_request_length() {
		$req_str = '';
		
		foreach($_REQUEST as $key => $value) {
			$req_str .= $key."\t".$value."\n";
		}

		$rh = $this->get_headers();

		$req_head = '';

		foreach($rh as $key => $value) {
			$req_head .= $key."\t".$value."\n";
		}

		return UTF8::strlen($req_str.$req_head);
	}
	
	private function get_response_length() {
		$resp_head = '';
		
		foreach($this->request->headers as $key => $value) {
			$resp_head .= $key."\t".$value."\n";
		}
	
		return UTF8::strlen($resp_head . $this->request->response);
	}
	
	private function get_headers()
	{
		if (!function_exists('getallheaders')) { 
            foreach($_SERVER as $key=>$value) { 
                if (substr($key,0,5) == "HTTP_") { 
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
                    $out[$key]=$value; 
                }
				else
				{ 
                    $out[$key]=$value; 
        		} 
            } 
            return $out; 
		}
		else return getallheaders();
	}
	
   /**
	* 
	* 	API 2.0
	* 
	**/
	public function action_check()
	{
		$hardware_id 	= Arr::get($_REQUEST, 'id', null);
		$log 			= Kohana_Log::instance();
		
		if(!$hardware_id)
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0001
		}
		
		// Зачем создавать, если нам нужно только проверить МС?
		$station = Jelly::factory('station')->create_if_not_exists($hardware_id);
		
/*
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		
		if(!($station instanceof Jelly_Model) or !$station->loaded())
		{
			return $this->report_error(self::MOBILE_STATION_IS_NOT_BLACKLISTED);
		}
*/
		
		$blacklist = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', $hardware_id)->limit(1)->execute();

		if($blacklist->loaded())
		{
			$this->request->response = JSON::success(array( 'name' => $blacklist->station->name ));
			return;
		}
		unset($station);
				
		// Попытки
		$station = Jelly::select('attempts')->with('station')->where('hardware_id', '=', $hardware_id)->load();
		$st 	 = Jelly::factory('station')->create_if_not_exists($hardware_id);
		
		if($station->loaded())
		{
			
			if(!$station->station->id())
			{
				$station->station = $st;
				$station->save();
			}
			
			$this->request->response = JSON::fail(array( 'attempt' => Kohana::config('application.blacklist_attempts') - $station->attempts, 'name' => $station->station->name));
			return;
		}
		unset($station);
		
		$this->request->response = JSON::fail(array( 'attempt' => Kohana::config('application.blacklist_attempts'), 'name' => $st->name));
		
	}
	
	private function is_blacklisted($hardware_id)
	{
		$station = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', $hardware_id)->load();
		
		if($station->loaded())
		{
			return $station;
		}
		
		return false;
	}
	
		/**
	 * self::action_activate()
	 * Производит активацию раннее занесенной в черный список МС путем сверки аппаратного ключа и кода разблокировки
	 * 
	 * @return массив JSON:
	 *  result - признак успешности операции (true - успешна, false - неуспешна)
	 *  server - если операция успешна, то содержит домен сервера синхронизации
	 *  error  - код ошибки в константах выше
	 * 
	 * @param code	 	- активационный код
	 * @param id		- аппаратный ключ МС
	 */
	public function action_activate()
	{
		$hardware_id 	= Arr::get($_REQUEST, 'id', null);
		$unblock_code	= Arr::get($_REQUEST, 'code', null);
		
		$log 			= Kohana_Log::instance();
		
		if(!$hardware_id)
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0001
		}
		
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		if(!($station instanceof Jelly_Model) or !$station->loaded())
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0001
		}
		
		/** Не в черном ли списочке станция **/
		if(!$this->is_blacklisted($hardware_id))
		{
			return $this->report_error(self::MOBILE_STATION_IS_NOT_BLACKLISTED);
		}
		
		if(!$unblock_code)
		{
			//$station = Jelly::factory('station')->create_if_not_exists($hardware_id);
			
			$station->append_log('Попытка активации с пустым кодом', $this->get_request_length(), $this->get_response_length(), null, 1);
			
			return $this->report_error(self::ACTIVATION_CODE_IS_EMPTY); // 0x0005
		}
			
						
		/** Есть ли у нас вообще такой номер активации? **/
		$blacklist = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', $hardware_id)->load();
		
		if($blacklist instanceof Jelly_Model and $blacklist->loaded())
		{
			if($blacklist->unblock_code == UTF8::trim($unblock_code))
			{
				$this->request->response = JSON::success(array('attempt' => Kohana::config('application.blacklist_attempts')));
				Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();
				Jelly::delete('blacklist')->where('hardware_id', '=', $hardware_id)->execute();

				$blacklist->station->append_log('Станция активирована', $this->get_request_length(), $this->get_response_length(), null);
	
				return;
			}
			/** Заносим в попытки **/
		
			if($blacklist->attempts == Kohana::config('application.blacklist_attempts') - 1)
			{
				/** Регененируем код **/
				$blacklist->attempts	 	= 0;
				$blacklist->unblock_code 	= mt_rand(100000000, 999999999);
				$blacklist->save();
				
				Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();

				$blacklist->station->append_log('Попытка активации, код: ' . UTF8::trim($unblock_code), $this->get_request_length(), $this->get_response_length(), null, 1);
				$blacklist->station->append_log('Сгенерирован новый код активации: ' . $blacklist->unblock_code, $this->get_request_length(), $this->get_response_length(), null, 1);
				
				$blacklist->send_code();
				//Тут добавить рассылку писем счастья
			}
			else
			{
				$blacklist->attempts++;
				$blacklist->save();

				$blacklist->station->append_log('Попытка активации, код: ' . UTF8::trim($unblock_code), $this->get_request_length(), $this->get_response_length(), null, 1);
			}
							
			return $this->report_error(self::ACTIVATION_CODE_IS_INCORRECT); 
		}
		
		// Кстати странно, сюда код дойти не должен в теории ;)
		return $this->report_error(self::MOBILE_STATION_IS_NOT_BLACKLISTED);
	}
	
		/**
	 * self::action_register()
	 * Производит первоначальную активацию (регистрацию) МС путем сверки номера лицензии и аппаратного ключа
	 * 
	 * @return массив JSON:
	 *  result - признак успешности операции (true - успешна, false - неуспешна)
	 *  server - если операция успешна, то содержит домен сервера синхронизации
	 *  error  - код ошибки в константах выше
	 * 
	 * @param license 	- номер лицензии
	 * @param id		- аппаратный ключ МС
	 */
	public function action_autologin()
	{
	   return $this->action_login();
       
		
		$license 		= Arr::get($_REQUEST, 'license', null);
		$hardware_id 	= Arr::get($_REQUEST, 'id', null);
		$log 			= Kohana_Log::instance();
		
		if(!$hardware_id)
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0000
		}
		
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		if(!($station instanceof Jelly_Model) or !$station->loaded())
		{
			return $this->report_error(self::HARDWARE_ID_IS_EMPTY); // 0x0000
		}
		
		if(!$license)
		{
			return $this->report_error(self::LICENSE_NUMBER_IS_EMPTY); // 0x0005
		}
		
		/** Не в черном ли списочке станция **/
		if($this->is_blacklisted($hardware_id))
		{
			return $this->report_error(self::MOBILE_STATION_BLACKLISTED);
		}
		
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		
		if(!($station instanceof Jelly_Model) or !$station->loaded())
			return $this->report_error(self::SERVER_ERROR);
		
		/** Есть ли у нас вообще такой номер лицензии? **/
		
		$user = Jelly::select('license')->with('stations')->where('number', '=', $license)->load();
		
		if(!($user instanceof Jelly_Model) or !$user->loaded() or $user->deleted)
		{
			return $this->report_error(self::LICENSE_NUMBER_IS_INVALID);
		}
		
		if(!$user->manual) {
			if(!$user->is_active 
				or $user->get_expire_alert() == 'red')
				{
					return $this->report_error(self::LICENSE_IS_BLOCKED);
				}
		} else {
			if ($user->status == Model_License::STATUS_STOP) {
				return $this->report_error(self::LICENSE_IS_BLOCKED);
			}
		}
		
		foreach($user->stations as $s)
		{
			if($s->id() == $station->id())
			{
				$this->request->response = JSON::success(array());
				return;
			}	
		}
			
		return $this->report_error(self::MOBILE_STATION_IS_NOT_REGISTERED);
	}
	
}

?>