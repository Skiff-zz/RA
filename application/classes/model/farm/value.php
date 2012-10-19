<?php
class Model_Farm_Value extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('farm_values')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'farm'			=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'farm',
														'column'	=> 'farm_id',
														'label'		=> 'Хозяйство',
													)),
							'field'			=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'farm_field',
														'column'	=> 'field_id',
														'label'		=> 'Поле',
													)),						
							'value'			=>  Jelly::field('String', array('label' => 'Значение')),
			 ));
	}
}
?>
