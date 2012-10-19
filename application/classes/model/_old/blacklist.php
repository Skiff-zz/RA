<?php
class Model_Blacklist extends Jelly_Model
{
	
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('blacklist')
			->fields(array( 
							'_id'			=> Jelly::field('Primary'),
							'hardware_id'	=> Jelly::field('String', array('label' => 'Уникальный аппаратный номер МС')),
							'unblock_code'	=> Jelly::field('String', array('label' => 'Разблокировочный ключ')),
							'attempts'		=> Jelly::field('Integer', array('label' => 'Количество попыток')),
							'station'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'station',
														'column'	=> 'station_id',
														'label'		=> 'Моб. станция',
													)),
							'create_date'	=> Jelly::field('Integer', array('label' => 'Дата занесения')),
			 ));
	}
	
	public function send_code()
	{
		if(!$this->station or !$this->station->id())
			return;
		
		$db = Database::instance();
		
		$user_ids = $db->query(DATABASE::SELECT, 'SELECT user_id FROM user_stations WHERE station_id = '.$this->station->id(), true);
		
		$ids = array();
		foreach($user_ids as $id)
		{
			$ids[] = $id->user_id;
		}
		
		$ids = array_unique($ids);
		
		if(!count($ids)) return;
		
		$users = Jelly::select('user')->where('_id', 'IN', $ids)->execute();
		
		$email =  Twig::factory('station/email');
		
		$from_email = (string)Kohana::config('application.from_email');
		
		foreach($users as $user)
		{
			
			$email->station = $this->station->as_array();
			$email->user 	= $user->as_array();
			$email->unblock_code= $this->unblock_code;
			
			Email::connect();
			Email::send((string)$user->email, 
						  (string)$from_email,
						  (string)'[AGROCLEVER] Мобильная станция '.$this->station->name.' была заблокирована ',
						  (string)$email->render(),
						  true);
		}	
	}
}
?>
