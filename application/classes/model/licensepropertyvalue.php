<?php
class Model_LicensePropertyValue extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('lic_properties_val')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'license'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',
													)),
							'field'			=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'licenseproperty',
														'column'	=> 'field_id',
														'label'		=> 'Поле',
													)),						
							'value'			=>  Jelly::field('String', array('label' => 'Значение')),
			 ));
	}
}
?>
