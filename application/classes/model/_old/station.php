<?php
class Model_Station extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('mobile_stations')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'users' 		=> Jelly::field('ManyToMany', array(
				                									'foreign' => 'user',
																	
													                'through' => array(
													                    'model'   => 'user_stations',
													                    'columns' => array('station_id', 'user_id'),
													                ),
				            )),
							'name'			=> Jelly::field('String', array('label' => 'Кастомное имя МС')),
							'hardware_id'	=> Jelly::field('String', array('label' => 'Уникальный аппаратный номер МС')),
							'activate_date'	=> Jelly::field('Integer', array('label' => 'Дата активации')),
							

			 ));
	}
	
	/**
	 *  Да, я знаю про существование HasMany
	 **/
	 
	public function append_log($message, $in, $out, $user, $type = 0)
	{
		if(!$this->loaded()) return false;
		
		$log = Jelly::factory('stat');
		
		$log->date 		= db::expr('NOW()');
		$log->station 	= $this->id();
		$log->message 	= __($message);
		$log->in	 	= (int)$in > 0 ? (int)$in : 0;
		$log->out	 	= (int)$out > 0 ? (int)$out : 0;
		$log->type		= (int)$type;
		
		// currently unsupported
		$log->user	 	= null;	
		
		$log->save();
	}
	
	public function create_if_not_exists($hardware_id)
	{
		if(!$hardware_id) { return false; }
		$station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		
		if($station instanceof Jelly_Model  and $station->loaded())
			return $station;
		
		unset($station);
		
		$station 					= Jelly::factory('station');
		$station->hardware_id 		= $hardware_id;
		$station->save();
		
		$station->name			 	= 'МС'.sprintf('%04d', $station->id());
		$station->save();
		
		return $station;	
	}
}
?>
