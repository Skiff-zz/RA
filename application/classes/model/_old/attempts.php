<?php
class Model_Attempts extends Jelly_Model
{
	
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('attempts')
			->fields(		
							array(
							'_id'			=> Jelly::field('Primary'),
							'hardware_id'	=> Jelly::field('String', array('label' => 'Уникальный аппаратный номер МС')),
							'attempts'		=> Jelly::field('Integer', array('label' => 'Количество попыток')),
							'station'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'station',
														'column'	=> 'station_id',
														'label'		=> 'Моб. станция',
							))
			 ));
	}
	
	public function increment($hardware_id)
	{
		if($hardware_id == '') return false;
		
		$station 			= Jelly::factory('station')->create_if_not_exists($hardware_id);
		
		$attempts = Jelly::select('attempts')->where('hardware_id', '=', $hardware_id)->load();
			
		if($attempts instanceof Jelly_Model and $attempts->loaded())
		{
			if($attempts->attempts == Kohana::config('application.blacklist_attempts') - 1)
			{
				/** Заносим в блеклист **/
				$attempts->attempts = (int)Kohana::config('application.blacklist_attempts');
				$attempts->save();
				
				$blacklist			= Jelly::factory('blacklist');
									
				if($station instanceof Jelly_Model and $station->loaded())
				{
					$blacklist->station = $station;
				}
				
				$blacklist->hardware_id 	= $hardware_id;
				$blacklist->create_date 	= time();
				$blacklist->attempts	 	= 0;
				$blacklist->unblock_code 	= mt_rand(100000000, 999999999);
				
				Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();
				
				$blacklist->save();
				
				//Тут добавить рассылку писем счастья
				$blacklist->send_code();
			}
			else
			{
				if($station->loaded())
				{
					$attempts->station = $station;
				}
				
				$attempts->attempts++;
				$attempts->save();
			}
		}
		else
		{
			unset($attempts);
			
			$attempts = Jelly::factory('attempts');
			
			$attempts->attempts 		= 1;
			$attempts->hardware_id 		= $hardware_id;
			
			if($station->loaded())
			{
				$attempts->station 		= $station;
			}
			
			$attempts->save();
		}
		
		return $attempts;
	}
	
}
?>
