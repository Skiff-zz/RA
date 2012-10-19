<?php

class Model_Type extends Jelly_Model
{
	const TYPE_USER		= 1;
	const TYPE_AREA		= 2;
	const TYPE_PLAN		= 3;
	const TYPE_TASK		= 4;
	const TYPE_PRODUCT	= 5;
	const TYPE_CULTURE	= 6;
	const TYPE_ATK		= 7;
	const TYPE_CULTURETYPE 	= 8;

	const TYPE_USEREMAIL 	= 9;
	const TYPE_USERDATE 	= 10;
	const TYPE_USERADDRESS 	= 11;
	const TYPE_USERPHONE 	= 12;
	const TYPE_USERNOTE 	= 13;

	const TYPE_EMAILLABEL 	= 14;
	const TYPE_DATELABEL 	= 15;
	const TYPE_ADDRESSLABEL = 16;
	const TYPE_PHONELABEL 	= 17;
	const TYPE_NOTELABEL 	= 18;

	const TYPE_LICENSEEEMAILLABEL 	= 19;
	const TYPE_LICENSEEDATELABEL 	= 20;
	const TYPE_LICENSEEADDRESSLABEL = 21;
	const TYPE_LICENSEEPHONELABEL 	= 22;
	const TYPE_LICENSEENOTELABEL 	= 23;


	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('object_types')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'name'			=> Jelly::field('String', array('label' => 'Название')),
							'slug'			=> Jelly::field('String', array('label' => 'Псевдоним')),
							'model'			=> Jelly::field('String', array('label' => 'Модель')),
			));
	}
	
}
?>
