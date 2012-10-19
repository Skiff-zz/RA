<?php

class Model_UserPhone extends AC_UserInfoData
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='phone', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('user_phones')
	    ->fields(array(
		'phone' => Jelly::field('String', array('label' => 'Номер телефона')),
	    ));
    }

}
