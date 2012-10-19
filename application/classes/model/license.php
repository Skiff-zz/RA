<?php

class Model_License extends Jelly_Model
{
    const ACTIVATE_NO_FREE_SLOTS = 0;
	const ACTIVATE_OK = 1;
	const SERVER_ERROR = 0;
	
	// Статусы, установленные вручную
	const STATUS_OK	  			= 1; // Все хорошо, зеленый, можно обходить заложенные ограничения до посинения
	const STATUS_WARNING	  	= 2; // Предупреждение, желтый. По факту операций - то же самое, что и зеленый
	const STATUS_STOP		  	= 3; // Остановка любых операций по профилю, красный. Никакие новые активации и прочее невозможно
    
    public static function initialize(Jelly_Meta $meta)
	{
        $meta->table('licenses')
		    ->fields(array(
			// Первичный ключ
			'_id' 			=> new Field_Primary,
			'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
            'status'		=> Jelly::field('Integer', array('label' => 'Статус')),
			'manual'		=> Jelly::field('Boolean', array('label' => 'Вручную')),
			'is_active'		=> Jelly::field('Boolean', array('label' => 'Активна')),
			// Лицензия
			'name'		=>  Jelly::field('String', array('label' => 'Имя лицензиата', 'unique' => TRUE,
				'rules' => array(
					'not_empty' => NULL
				))),
			'number'		=>  Jelly::field('String', array('label' => 'Номер лицензии', 'unique' => TRUE,
				'rules' => array(
					'not_empty' => NULL
				))),
			'activate_date'	=>  Jelly::field('Integer', array('label' => 'Дата активации',
				'rules' => array(
					'not_empty' => NULL
				))),
			'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата обновления информации',
				'rules' => array(
					'not_empty' => NULL
			))),
			'expire_date'	=>  Jelly::field('Integer', array('label' => 'Дата окончания',
				'rules' => array(
					'not_empty' => NULL
				))),
//			'last_login'	=>  Jelly::field('Integer', array('label' => 'Дата последнего логина под лицензией')),
			'max_ms'		=>  Jelly::field('Integer', array('label' => 'Моб. станции')),
             'square'		=>  Jelly::field('Float', array('label' => 'Поля',
								'rules' => array(
									'not_empty' => NULL,
									'range' => array(0.01, 99999)
								))),
			//ограничения
			'max_users' => Jelly::field('Integer', array('label' => 'Мах. пользователей')),
			'max_fields' => Jelly::field('Integer', array('label' => 'Мах. полей')),
            
            // Мобильные станции согласно API 2.0
            'stations' 		=> Jelly::field('ManyToMany', array(
                									'foreign' => 'station',
													
									                'through' => array(
									                    'model'   => 'license_stations',
									                    'columns' => array('license_id', 'station_id'),
									                ),
            )),

			'user'		    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'user',
							'column'	=> 'user_id',
							'label'		=> 'Администратор',
						)),
            
            'farms'         => Jelly::field('HasMany',array(
                            			    'foreign'	=> 'farm',
                            			    'label'	=> 'Хозяйства',
                              )),
			   
            ));				
            
			
     }
	 
	public function get_fields_count($period_id = null)
	{
		if(!$this->loaded())
			return 0;
		
        if(!$period_id)
        {
    		$periods = Jelly::select('period')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute();
    		
    				
    		$counts = array();
    		
    		foreach($periods as $period)
    		{
    			$counts[] = Jelly::select('field')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period->id())->count();
    
    		}
        }
        else
        {
            return Jelly::select('field')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period_id)->count();
        }
		
		$count = 0;
		
		foreach($counts as $c)
		{
			if($c > $count) $count = $c;
		}
			
		return $count;
	}
	
	public function get_square($period_id = null, $field_id = null)
	{
		if(!$this->loaded())
			return 0;
		
        if(!$period_id)
        {
    		$periods = Jelly::select('period')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute();
    		
    		$counts = array();
    		
    		foreach($periods as $period)
    		{
    			if(!$field_id)
    			{
					$fields = Jelly::select('field')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period->id());
                }
                else
                {
               		$fields = Jelly::select('field')->where(':primary_key', '!=', (int)$field_id)->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period->id());
	   			}
	   			
                $fields = $fields->execute();
                
                $sum = 0;
                
                foreach($fields as $f)
                {
                    $sum += $f->area;
                }
                
                $counts[] = $sum;
    		}
        }
        else
        {
                if(!$field_id)
                {
					$fields = Jelly::select('field')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period_id);
                }
                else
                {
               		$fields = Jelly::select('field')->where(':primary_key', '!=', (int)$field_id)->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('period', '=', $period_id);
	   			}
                	
                $fields = $fields->execute();
                
                $sum = 0;
                
                foreach($fields as $f)
                {
                    $sum += $f->area;
                }
                
                return $sum;
        }
		
		$count = 0;
		
		foreach($counts as $c)
		{
			if($c > $count) $count = $c;
		}
			
		return $count;
	}

	public function list_licencees($query = null)
	{
		$r = Jelly::select($this->meta()->model())->with('user')->where('deleted', '=', 0)->select('_id', array('_id', 'top_parent'), 'is_active', 'name', 'activate_date', 'expire_date', 'max_ms', 'number', 'status', 'manual', 'deleted')->select(array(db::expr('(SELECT COUNT(*) FROM `'.Jelly::meta('farm')->table().'` WHERE `license_id` = `top_parent` AND `deleted` = 0)'), 'count_childs'));
		
		if($query)
		{
			$r = $r->where_open()->where('name', 'LIKE', '%'.$query.'%')->or_where('user.email', 'LIKE', '%'.$query.'%')->or_where('user.first_name', 'LIKE', '%'.$query.'%')->or_where('user.middle_name', 'LIKE', '%'.$query.'%')->or_where('user.last_name', 'LIKE', '%'.$query.'%')->where_close();
		}
		
		$records =  $r->order_by('name', 'ASC')->execute();
		
		$status 			= Twig::factory('farm/status');
		
		$r = array();
		
		foreach($records as $record)
		{
			
			$count_childs = $record->get('count_childs');
						
			$status->status 	= (int)$record->status;
			$status->manual 	= (int)$record->manual;
			
			$r[] = array(
				'id'		=> 'license_'.$record->id(),
				'text'		=> UTF8::strlen($record->name) < 25 ? $record->name : UTF8::substr($record->name, 0, 22).'...',
				'source' 	=> '/license/read/'.$record->id(),
				'children'	=> $count_childs,
				'status'	=> $status->render(),
				'number'	=> $record->number,
				'parent'	=> null,
				'leaf'		=> true
			);
		}
		
		
		return JSON::tree($r, count($r));	
	}

     public static function generate_number()
     {
        do
        {
        	$license = rand(100000000, 999999999);
        	$test	 = Jelly::select('license')->where('number', '=', $license)->limit(1)->execute();
        }
        while ($test instanceof Jell_Model and $test->loaded());
   		
   		return $license;
     }

     public function get_stations_count()
	{
		if($this->loaded())
			return $this->stations->count();
		else 
			return false;	 
	}
	
	public function get_ms_alert($count)
	{
		if((int)$count >= (int)$this->max_ms)
			return 'yellow';
		
		/*
		if((int)((int)$count/(int)$this->max_ms)*100 <= (int)Kohana::config('application.ms_warning_persent'))
			return 'yellow';
		*/
		return 'green';		
	}
	
	public function get_pc_alert($count)
	{
			
		if((int)$count >= (int)$this->max_pc)
			return 'yellow';
		
		/*
		if((int)((int)$count/(int)$this->max_pc)*100 <= (int)Kohana::config('application.pc_warning_persent'))
			return 'yellow';
		*/
		
		return 'green';	
	}

	public function get_subusers_alert($count)
	{

		if((int)$count >= (int)$this->max_users)
			return 'yellow';

		/*
		if((int)((int)$count/(int)$this->max_pc)*100 <= (int)Kohana::config('application.pc_warning_persent'))
			return 'yellow';
		*/

		return 'green';
	}
	
	public function get_expire_alert()
	{
		$expire_date 	=  $this->expire_date;
					
		if(time() >= $expire_date) {
			return 'red';
		} else if(($expire_date - time()) < (int)Kohana::config('application.expire_warning')) {
			return 'yellow';
		} else {
			return 'green';
		}
		
		return false;	
	}
	
	public function get_pc_count()
	{
		return 0;	 
	}
	
	public function get_valid()
	{
		$builder = Jelly::select('user')->where('is_active','=',1)
										->where('is_root', '=',0)
										->where('deleted', '=',0)
										->and_where_open()
										->and_where_open()
										->where('activate_date','<', time())
										->where('expire_date','>', time())
										->where('manual','=', 0)
										->and_where_close()
										->or_where_open()
										->where('manual','=', 1)
										->where('status','!=', self::STATUS_STOP)
										->or_where_close()
										->and_where_close()
										;

		return $builder;
	}
    
    public function get_subuser_count() {
		if($this->loaded()){
			$count = Jelly::select('user')->where('license', '=', $this->id())->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('is_active', '=', 1)->count();
			
			return $count ? $count : false;
		}
		else{
			return false;
		}
	}
	
	public function activate_ms($id)
	{
		if(!$id) return false;
		
		if(!$this->is_active) return false;
		if(!$this->loaded()) return false;
		
		/** Может, уже активирована такая МС у этого юзера? */
		$station = Jelly::factory('station')->create_if_not_exists($id);
		
		if($station instanceof Jelly_Model and $station->loaded())
		{
			foreach($this->stations as $st)
			{
				if($st->id() == $station->id())
					return self::ACTIVATE_OK;
			}
		}
		
		/** Считаем количество активированных у текущего юзера **/
        /**		
        $count = $this->get_stations_count();
					
		if($count >= $this->max_ms and (!$this->status or $this->status != self::STATUS_STOP) )
		{
			return self::ACTIVATE_NO_FREE_SLOTS;
		}
		**/
        
		if($station instanceof Jelly_Model and $station->loaded())
		{
			$this->add('stations', $station);
			$this->save();
			
			$this->set_status();
			
			return self::ACTIVATE_OK;
		}
				
		return self::SERVER_ERROR;
	}
	
	public function set_status()
	{
				if(!$this->loaded()) 
					return false;
				
				if($this->manual) 
				{
					if($this->status != 3)
					{
						$this->is_active = 1;
						$this->save();
					}
					return false;
				}
			// Сверка по датам
				$expire_date 	=  $this->expire_date;
				$activate_date	= $this->activate_date;

				if(time() >= $expire_date or time() < $activate_date)
				{
					$this->status = self::STATUS_STOP;
					$this->is_active = 0;
					$this->save();
					return;
				}
				else if(($expire_date - time()) < (int)Kohana::config('application.expire_warning'))
				{
					$this->status = self::STATUS_WARNING;
					$this->is_active = 1;
					$this->save();
					return;
				}
				
				/*	
				//Показываем адекватный статус текущему состоянию поциента
				$count = $this->get_stations_count();
				
				if ($count >= $this->max_ms)
				{
					$this->status =  self::STATUS_WARNING;
					$this->is_active = 1;
					$this->save();
					return;
				}*/
				/*
				else if($count >= intval(($this->max_ms/100)*(100 - (int)Kohana::config('application.ms_warning_persent')) + 0.5))
				{
					
					$this->status = self::STATUS_WARNING;
					$this->is_active = 1;
					$this->save();
					return;
					
				}*/
				
								
					
			$this->status = self::STATUS_OK;
			$this->is_active = 1;
			$this->save();
			return;
		
	}
    
//    public function get_fields_count() {
//		if($this->loaded()){
//			$count = Jelly::select('area')->where('farm', '=', $this->_id)->and_where('deleted', '=', 0)->count();
//			return $count ? $count : false;
//		}
//		else{
//			return false;
//		}
//	}


	public function get_fields_sq_count() {
		if($this->loaded()){
			$fields = Jelly::select('area')->where('farm', '=', $this->_id)->and_where('deleted', '=', 0)->execute();
			$count = 0;
			foreach($fields as $field){
				$count += $field->size;
			}

			return $count ? $count : false;
		}
		else{
			return false;
		}
	}

}
