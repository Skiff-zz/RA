<?php
class Model_Client_Seed extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('client_seed')
			->fields(array(
					// Первичный ключ
					'_id'	=> Jelly::field('Primary'),
					'name'	=> Jelly::field('String', array('label' => 'Название свойства')),
					'value'	=> Jelly::field('String', array('label' => 'Значение свойства')),
					'order'	=> Jelly::field('Integer', array('label' => 'Порядок вывода')),
					'license'	=> Jelly::field('BelongsTo',array(
						'foreign'	=> 'license',
						'column'	=> 'license_id',
						'label'	=> 'Лицензия'
					))
			 ));
	}

}
