<?php

class Model_UserAddress extends AC_UserInfoData
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='address', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('user_addresses')
	    ->fields(array(
		'address' => Jelly::field('String', array('label' => 'Адрес')),
	    ));
    }

}
