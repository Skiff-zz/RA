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

class Controller_API_License extends Ac_Controller
{
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
 
    
    protected $request_auth = false;
	public    $auto_render  = false;
    
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
    
    public function action_license()
    {
       	
        $license      	= UTF8::trim(Arr::get($_REQUEST, 'license', null));
        $hardware_id   	= UTF8::trim(Arr::get($_REQUEST, 'id', null));
        
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
        
        $r = $user->as_array();
        $farms = Jelly::select('farm')->where('license', '=', $user->id())->execute()->as_array();
        $r['farms'] = $farms;
        
        $this->request->response = JSON::success($r);
    }
    
    public function action_users()
    {
        $license      	= UTF8::trim(Arr::get($_REQUEST, 'license', null));
        $hardware_id   	= UTF8::trim(Arr::get($_REQUEST, 'id', null));
        
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
        
        $users = Jelly::select('user')->where('deleted', '=', 0)->where('license', '=', $user->id())->execute();
        
        $this->request->response = JSON::success($users->as_array());
    }
}