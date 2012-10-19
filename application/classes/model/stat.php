<?php
class Model_Stat extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('mobile_stations_stats')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'station'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'station',
														'column'	=> 'station_id',
														'label'		=> 'Станция'
													)),
							'license'			=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Пользователь'
													)),
							'message'		=> Jelly::field('String', array('label' => 'Текстовое сообщение')),
							'date'			=> Jelly::field('Timestamp', array('label' => 'Дата сообщения')),
							'type'			=> Jelly::field('Integer', array('label' => 'Тип записи')), // 0 - траффик, 1 - ощибка
							'in'			=> Jelly::field('Integer', array('label' => 'Входящий траффик (байт)')),
							'out'			=> Jelly::field('Integer', array('label' => 'Исходящий траффик (байт)'))

			 ));
	}
}
?>
