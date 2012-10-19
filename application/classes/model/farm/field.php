<?php
class Model_Farm_Field extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('farm_fields')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'name'			=> Jelly::field('String', array('label' => 'Название свойства')),
							'order'			=> Jelly::field('Integer', array('label' => 'Порядок вывода')),
							
			 ));
	}
}
?>
